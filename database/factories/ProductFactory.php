<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Sales\Products\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true),
            'category' => null,
            'description' => fake()->sentence(),
            'benefits' => null,
            'video_url' => null,
            'price' => fake()->randomFloat(2, 50, 500),
            'commission_amount' => fake()->randomFloat(2, 10, 100),
            'stock_control' => false,
            'stock_quantity' => 0,
            'minimum_stock' => 0,
            'status' => Product::STATUS_ACTIVE,
            'sort_order' => 0,
            'is_demo' => false,
        ];
    }

    public function withStock(int $quantity = 10, int $minimum = 2): self
    {
        return $this->state([
            'stock_control' => true,
            'stock_quantity' => $quantity,
            'minimum_stock' => $minimum,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(['status' => Product::STATUS_INACTIVE]);
    }
}
