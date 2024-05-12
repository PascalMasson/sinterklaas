<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('partnerId')->nullable();
            $table->boolean('kind')->nullable();
            $table->dropColumn("email");
            $table->dropColumn("email_verified_at");
            $table->dropColumn("password");
            $table->dropColumn("remember_token");
        });

        $seeddata = [
            ["name" => "Thiemo", "partnerId" => 2, "kind"=>0],
            ["name" => "Marianne", "partnerId" => 1, "kind"=>0],
            ["name" => "Jurrie", "partnerId" => 4, "kind"=>0],
            ["name" => "Ettie", "partnerId" => 3, "kind"=>0],
            ["name" => "Peter", "partnerId" => 6, "kind"=>0],
            ["name" => "Esther", "partnerId" => 5, "kind"=>0],
            ["name" => "Pascal", "partnerId" => 0, "kind"=>0],
            ["name" => "Rogier", "partnerId" => 0, "kind"=>0],
            ["name" => "Pepijn", "partnerId" => 0, "kind"=>1],
            ["name" => "Jasper", "partnerId" => 0, "kind"=>1],
            ["name" => "Matthijs", "partnerId" => 0, "kind"=>1],
        ];
        foreach ($seeddata as $user) {
            User::firstOrCreate($user);
        }

    }

    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
            $table->dropColumn('partnerId');
            $table->dropColumn('kind');
        });
    }
};
