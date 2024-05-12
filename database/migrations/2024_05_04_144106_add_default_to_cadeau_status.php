<?php

use App\Enums\CadeauStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cadeaus', function (Blueprint $table) {
            $table->string('status')->default(CadeauStatus::AVAILABLE->value)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cadeaus', function (Blueprint $table) {
            //
        });
    }
};
