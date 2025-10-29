<?php

namespace Database\Seeders;

use App\Enums\BrazilianState;
use App\Models\Employee;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('pt_BR');
        $states = BrazilianState::cases();

        for ($i = 0; $i < 50; $i++) {
            Employee::create([
                'user_id' => rand(1, 5),
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'cpf' => preg_replace('/\D/', '', $faker->cpf()),
                'city' => $faker->city,
                'state' => $faker->randomElement($states)->value,
            ]);
        }
    }
}
