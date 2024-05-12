<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cadeaus', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('status');
            $table->decimal('price');
            $table->string('location_type');
            $table->string('location_url');
            $table->string('location_address');
            $table->string('location_other');
            $table->foreignIdFor(User::class, 'created_by_user_id')->constrained('users');
            $table->foreignIdFor(User::class, 'list_user_id')->constrained('users');
            $table->foreignIdFor(User::class, 'reserverd_by_user_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadeaus');
    }
};
