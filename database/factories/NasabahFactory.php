<?php

namespace Database\Factories;

use App\Models\Nasabah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nasabah>
 */
class NasabahFactory extends Factory
{
    protected $model = Nasabah::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_nasabah' => 'NSB'.fake()->unique()->numerify('#####'),
            'nama' => fake()->name(),
            'kelas' => 'X-'.fake()->numberBetween(1, 9),
            'alamat' => fake()->address(),
            'no_hp' => fake()->numerify('08##########'),
        ];
    }
}
