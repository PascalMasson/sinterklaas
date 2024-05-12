<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cadeaus', function (Blueprint $table) {
            $table->dropColumn('location_address');
            $table->dropColumn("lat");
            $table->dropColumn("lng");
        });
    }

    public function down(): void
    {
        Schema::table('cadeaus', function (Blueprint $table) {
            //
        });
    }
};
