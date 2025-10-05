<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'brand' => $this->faker->randomElement(['Samsung', 'Apple', 'Huawei', 'Xiaomi']),
            'model' => $this->faker->regexify('[A-Z]{2}[0-9]{2}'),
            'stock_qty' => $this->faker->numberBetween(0, 100),
            'category' => $this->faker->randomElement(['camera', 'phone', 'tablet', 'laptop']),
            'description' => $this->faker->optional()->sentence(),
            'min_stock_level' => $this->faker->numberBetween(5, 20),
        ];
    }
}
