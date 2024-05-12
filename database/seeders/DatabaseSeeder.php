<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
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
            User::updateOrInsert(["name" => $user["name"]], $user);
        }
    }
}
