<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use App\Models\Role;


class UserFactory extends Factory
{

    protected static ?string $password;

    public function definition(): array
    {
        $faker = Faker::create('ja_JP');
        return [
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= bcrypt('password'),
            'remember_token' => Str::random(10),
            'role_id' => Role::where('name', 'user')->first()->id ?? Role::factory(),
        ];
    }
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role_id' => Role::where('name', 'admin')->first()->id ?? Role::factory(),
            'email' => 'admin@example.com',
            'name' => 'Admin User',
            'password' => Hash::make('adminpass'),
        ]);
    }
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
