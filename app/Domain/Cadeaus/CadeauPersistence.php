<?php

namespace App\Domain\Cadeaus;

interface CadeauPersistence
{
    /**
     * @param  list<array<string, mixed>>  $cadeaus
     */
    public function replace(array $cadeaus): void;
}
