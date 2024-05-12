<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cadeaus', function (Blueprint $table) {
            $table->renameColumn("reservered_by_user_id", "reserved_by_user_id");
        });
    }

    public function down(): void
    {
        Schema::table('cadeaus', function (Blueprint $table) {
            //
        });
    }
};
