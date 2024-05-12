<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('foppers', function (Blueprint $table) {
            $table->id();
            $table->text('inhoud');
            $table->foreignId('created_by_user_id');
            $table->foreignId('created_for_user_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foppers');
    }
};
