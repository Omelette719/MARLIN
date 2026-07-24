<?php

namespace App\Concerns;

/**
 * Auto-derives the free-text `wilayah` column from structured jalan/rt/kelurahan
 * parts whenever those are supplied and `wilayah` itself isn't explicitly set —
 * keeps every existing display/search/groupBy on `wilayah` working unchanged
 * while letting forms capture the address as separate fields.
 */
trait ComposesWilayah
{
    protected static function bootComposesWilayah(): void
    {
        static::saving(function ($model) {
            if (blank($model->wilayah) && (filled($model->jalan) || filled($model->rt) || filled($model->kelurahan))) {
                $model->wilayah = static::composeWilayah($model->jalan, $model->rt, $model->kelurahan);
            }
        });
    }

    public static function composeWilayah(?string $jalan, ?string $rt, ?string $kelurahan): string
    {
        $parts = [];

        if (filled($jalan)) {
            $parts[] = "Jl. {$jalan}";
        }

        if (filled($rt)) {
            $parts[] = "RT. {$rt}";
        }

        if (filled($kelurahan)) {
            $parts[] = "Kel. {$kelurahan}";
        }

        return implode(' ', $parts);
    }
}
