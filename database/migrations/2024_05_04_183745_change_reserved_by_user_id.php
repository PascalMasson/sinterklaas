<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cadeaus', function (Blueprint $table) {
            $table->unsignedBigInteger('reserverd_by_user_id')->nullable()->change();
            $table->renameColumn("reserverd_by_user_id", "reservered_by_user_id");
        });
    }

    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
            //
        });
    }
};
