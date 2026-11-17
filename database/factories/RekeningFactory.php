<?php

namespace Database\Factories;

use App\Models\Nasabah;
use App\Models\Rekening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rekening>
 */
class RekeningFactory extends Factory
{
    protected $model = Rekening::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nasabah_id' => Nasabah::factory(),
            'no_rekening' => 'REK'.fake()->unique()->numerify('#####'),
            'saldo' => '0.00',
            'status' => Rekening::STATUS_AKTIF,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['status' => Rekening::STATUS_NONAKTIF]);
    }
}
