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
            'nama' => fake()->name(),
            'angkatan' => (string) fake()->numberBetween(2018, 2026),
            'program_studi' => fake()->randomElement([
                'Teknik Informatika',
                'Akuntansi',
                'Manajemen',
                'Sistem Informasi',
            ]),
        ];
    }
}
