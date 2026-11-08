<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cadeaus', function (Blueprint $table): void {
            $table->decimal('price')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cadeaus', function (Blueprint $table): void {
            $table->decimal('price')->nullable(false)->change();
        });
    }
};
