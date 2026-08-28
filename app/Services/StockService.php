<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\StockMovementLine;
use App\Models\User;
use App\Notifications\LowStockDetected;
use App\Notifications\StockCountCompleted;
use App\Notifications\StockMovementCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Wraps every operation that changes products.stock in a DB transaction, so the
 * movement/count documents and the product balances they affect never drift apart —
 * mirrors how the prototype adjusts stock immediately on save (support.js).
 */
class StockService
{
    /**
     * @param  array{type: string, date: string, party: ?string, note: ?string}  $header
     * @param  array<int, array{product_id: int, product_variant_id?: ?int, qty: int, unit_price: float, category_name?: ?string, unit?: ?string}>  $lines
     */
    public function createMovement(array $header, array $lines, User $user): StockMovement
    {
        $newlyLowProducts = [];

        $movement = DB::transaction(function () use ($header, $lines, $user, &$newlyLowProducts) {
            $movement = StockMovement::create([
                'doc_no' => $this->nextDocNo($header['type']),
                'type' => $header['type'],
                'date' => $header['date'],
                'party' => $header['party'] ?? null,
                'note' => $header['note'] ?? null,
                'total' => 0,
                'user_id' => $user->id,
            ]);

            $total = 0;

            foreach ($lines as $line) {
                $product = Product::lockForUpdate()->findOrFail($line['product_id']);
                $wasHealthy = $product->stock_status['tone'] === 'accent';
                $qty = (int) $line['qty'];
                $unitPrice = (float) $line['unit_price'];
                $lineTotal = $qty * $unitPrice;
                $total += $lineTotal;

                $variant = ! empty($line['product_variant_id'])
                    ? ProductVariant::where('product_id', $product->id)->findOrFail($line['product_variant_id'])
                    : null;
                $unitQty = $variant ? (float) $variant->unit_qty : 1;
                $baseQty = $qty * $unitQty;

                if ($header['type'] === 'out' && $baseQty > (float) $product->stock) {
                    throw new \RuntimeException("\"{$product->name}\" คงเหลือไม่พอ (คงเหลือ {$product->stock_display} แต่พยายามเบิก {$baseQty})");
                }

                $movement->lines()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'category_name' => $line['category_name'] ?? $product->category?->name,
                    'unit' => $line['unit'] ?? $product->unit?->name ?? '',
                    'qty' => $qty,
                    'unit_qty' => $unitQty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $product->stock += $header['type'] === 'in' ? $baseQty : -$baseQty;
                $product->save();

                $nowUnhealthy = in_array($product->stock_status['tone'], ['warn', 'danger'], true);
                if ($wasHealthy && $nowUnhealthy) {
                    $newlyLowProducts[] = $product;
                }
            }

            $movement->update(['total' => $total]);

            return $movement->load('lines');
        });

        $this->notifyOthers($user, new StockMovementCreated($movement, $user));

        foreach ($newlyLowProducts as $product) {
            $this->notifyOthers(null, new LowStockDetected($product));
        }

        return $movement;
    }

    public function deleteMovement(StockMovement $movement): void
    {
        DB::transaction(function () use ($movement) {
            foreach ($movement->lines as $line) {
                if ($line->product_id && $product = Product::lockForUpdate()->find($line->product_id)) {
                    $baseQty = $line->qty * (float) $line->unit_qty;
                    if ($movement->type === 'in' && $baseQty > (float) $product->stock) {
                        throw new \RuntimeException("ลบเอกสารไม่ได้ เพราะ \"{$product->name}\" จะติดลบ (คงเหลือ {$product->stock_display} แต่เอกสารนี้รับเข้าไว้ {$baseQty})");
                    }
                    $product->stock -= $movement->type === 'in' ? $baseQty : -$baseQty;
                    $product->save();
                }
            }

            $movement->lines()->delete();
            $movement->delete();
        });
    }

    /**
     * Remove a single line from a document: reverses just that line's stock
     * effect, then deletes the whole document if no lines are left.
     */
    public function deleteLine(StockMovementLine $line): void
    {
        DB::transaction(function () use ($line) {
            $movement = $line->stockMovement;

            if ($line->product_id && $product = Product::lockForUpdate()->find($line->product_id)) {
                $baseQty = $line->qty * (float) $line->unit_qty;
                if ($movement->type === 'in' && $baseQty > (float) $product->stock) {
                    throw new \RuntimeException("ลบรายการไม่ได้ เพราะ \"{$product->name}\" จะติดลบ (คงเหลือ {$product->stock_display} แต่รายการนี้รับเข้าไว้ {$baseQty})");
                }
                $product->stock -= $movement->type === 'in' ? $baseQty : -$baseQty;
                $product->save();
            }

            $line->delete();

            if ($movement->lines()->count() === 0) {
                $movement->delete();
            } else {
                $movement->update(['total' => $movement->lines()->sum('line_total')]);
            }
        });
    }

    /**
     * Edit an existing line's quantity/price — reverses the old stock effect and applies
     * the new one atomically (same product/variant; only qty & unit price change), so the
     * product balance never drifts. Recomputes the parent document's total.
     *
     * @param  array{qty: int, unit_price: float}  $data
     */
    public function updateLine(StockMovementLine $line, array $data): void
    {
        DB::transaction(function () use ($line, $data) {
            $movement = $line->stockMovement;
            $qty = (int) $data['qty'];
            $unitPrice = (float) $data['unit_price'];

            if ($line->product_id && $product = Product::lockForUpdate()->find($line->product_id)) {
                $oldBaseQty = $line->qty * (float) $line->unit_qty;
                $newBaseQty = $qty * (float) $line->unit_qty;
                $delta = $newBaseQty - $oldBaseQty;

                $projectedStock = (float) $product->stock + ($movement->type === 'in' ? $delta : -$delta);

                if ($projectedStock < 0) {
                    throw new \RuntimeException("แก้ไขไม่ได้ เพราะ \"{$product->name}\" จะติดลบ (คงเหลือ {$product->stock_display})");
                }

                $product->stock = $projectedStock;
                $product->save();
            }

            $line->update([
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $qty * $unitPrice,
            ]);

            $movement->update(['total' => $movement->lines()->sum('line_total')]);
        });
    }

    /**
     * Close a count session: every line with a real_qty entered adjusts the
     * product's stock to match the counted amount.
     */
    public function completeStockCount(StockCount $count, User $user): StockCount
    {
        $count = DB::transaction(function () use ($count) {
            foreach ($count->lines as $line) {
                if (is_null($line->real_qty) || ! $line->product_id) {
                    continue;
                }

                Product::lockForUpdate()->whereKey($line->product_id)->update(['stock' => $line->real_qty]);
            }

            $count->update(['status' => 'completed']);

            return $count->fresh('lines');
        });

        $this->notifyOthers($user, new StockCountCompleted($count, $user));

        return $count;
    }

    /**
     * Send a notification to every active user except (optionally) the actor
     * who triggered it — keeps the notification bell from telling someone
     * about their own action while still reaching the rest of the team.
     * Anyone who's muted this notification's type is skipped too.
     */
    protected function notifyOthers(?User $exclude, $notification): void
    {
        $recipients = User::where('active', true)
            ->when($exclude, fn ($q) => $q->whereKeyNot($exclude->id))
            ->get()
            ->reject(fn (User $u) => $u->mutesNotificationType($notification->notificationType()));

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }
    }

    /**
     * Next sequence number, based on the highest existing suffix rather than a
     * row count — a plain count breaks the moment the sequence has a gap (as
     * the seeded stock-count numbers do, starting at 010 rather than 001).
     */
    /**
     * Numbers are issued from a dedicated monotonic sequence (doc_sequences), not by
     * scanning for the current max doc_no — the latter reuses a number the moment the
     * highest-numbered document gets deleted, which then makes any notification or
     * reference to the old document ambiguous with whatever new one reused its number.
     */
    public function nextDocNo(string $type): string
    {
        $prefix = $type === 'in' ? 'IN' : 'OUT';
        $next = $this->nextSequenceNumber("movement_{$type}");

        return sprintf('%s-%04d', $prefix, $next);
    }

    protected function nextSequenceNumber(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $sequence = DB::table('doc_sequences')->where('key', $key)->lockForUpdate()->first();

            if (! $sequence) {
                DB::table('doc_sequences')->insert(['key' => $key, 'last_number' => 1, 'created_at' => now(), 'updated_at' => now()]);

                return 1;
            }

            $next = $sequence->last_number + 1;
            DB::table('doc_sequences')->where('key', $key)->update(['last_number' => $next, 'updated_at' => now()]);

            return $next;
        });
    }

    public function nextCountNo(): string
    {
        $buddhistYear = now()->year + 543;
        $prefix = "CC-{$buddhistYear}-";
        $next = $this->nextSequenceNumber("stock_count_{$buddhistYear}");

        return sprintf('%s%03d', $prefix, $next);
    }
}
