<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeedProductPhotos extends Command
{
    protected $signature = 'app:seed-product-photos';

    protected $description = 'Copy the original product photos into storage/app/public/products the first time it is empty (e.g. a freshly-attached Railway Volume), without touching anything already uploaded there.';

    /**
     * storage/app/public is where product photos actually get read/written from
     * (Storage::disk('public')) — on Railway that path lives on a persistent Volume so
     * uploads survive redeploys, but a brand-new (or just-attached) Volume starts empty.
     * storage/app/seed-photos/products is a plain, git-tracked copy of the photos that
     * existed locally before deployment — kept outside the volume mount so it survives
     * every deploy regardless of what the volume contains, and only gets copied across
     * when the destination is empty, so it never overwrites/duplicates real uploads.
     */
    public function handle(): int
    {
        $source = storage_path('app/seed-photos/products');
        $dest = storage_path('app/public/products');

        if (! File::isDirectory($source)) {
            $this->info('No seed photos bundled — nothing to do.');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists($dest);

        if (count(File::files($dest)) > 0) {
            $this->info('storage/app/public/products already has files — leaving it as is.');

            return self::SUCCESS;
        }

        File::copyDirectory($source, $dest);
        $this->info('Copied '.count(File::files($dest)).' seed photos into storage/app/public/products.');

        return self::SUCCESS;
    }
}
