<?php

namespace App\Exports\Concerns;

trait SanitizesFormulas
{
    /**
     * Neutralizes CSV/Excel formula injection: a cell value starting with
     * =, +, -, @, or a tab/CR gets interpreted as a formula by Excel when the
     * file is reopened. Prefixing a single quote forces it to render as text
     * instead. Only applies to fields sourced from user/import input (names,
     * notes, imported item text) — not to values we generate ourselves.
     */
    protected function sanitize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
