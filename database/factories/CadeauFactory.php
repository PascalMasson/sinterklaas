<?php

namespace Database\Factories;

use App\Models\Cadeau;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CadeauFactory extends Factory
{
    protected $model = Cadeau::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'description' => $this->faker->text(),
            'status' => $this->faker->word(),
            'price' => $this->faker->randomFloat(),
            'location_type' => $this->faker->randomElement(["website", "address", "other"]),
            'location_url' => $this->faker->url(),
            'location_address' => $this->faker->address(),
            'location_other' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'lat' => $this->faker->latitude(),
            'lng' => $this->faker->longitude(),
            'reservered_by_user_id' => $this->faker->randomNumber(),

            'created_by_user_id' => User::factory(),
            'list_user_id' => User::factory(),
        ];
    }
}
