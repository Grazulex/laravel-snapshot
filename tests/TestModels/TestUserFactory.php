<?php

declare(strict_types=1);

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestUserFactory extends Factory
{
    protected $model = TestUser::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ];
    }
}
