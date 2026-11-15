<?php

namespace App\Infrastructure\Cadeaus;

use App\Domain\Cadeaus\CadeauPersistence;
use App\Models\Cadeau;
use Illuminate\Support\Facades\DB;

final class EloquentCadeauPersistence implements CadeauPersistence
{
    /**
     * @param  list<array<string, mixed>>  $cadeaus
     */
    public function replace(array $cadeaus): void
    {
        DB::transaction(function () use ($cadeaus): void {
            DB::table('cadeaus')->delete();
            DB::table('media')->delete();

            foreach ($cadeaus as $attributes) {
                $cadeau = new Cadeau;
                $cadeau->forceFill($attributes);
                $cadeau->save();
            }
        });
    }
}
