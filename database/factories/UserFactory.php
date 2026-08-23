<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nik' => fake()->numerify('################'),
            'nip' => fake()->numerify('##################'),
            'nama' => fake()->name(),
            'gelar_depan' => 'Dr.',
            'gelar_belakang' => 'S.T., M.Kom.',
            'agama' => 'Islam',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Denpasar',
            'tanggal_lahir' => '1990-05-15',
            'jenjang_pendidikan' => 'S-I',
            'bidang_pendidikan' => 'Teknik Informatika',
            'status_kawin' => 'Menikah',
            'provinsi_ktp' => 'Bali',
            'kabupaten_ktp' => 'Buleleng',
            'kecamatan_ktp' => 'Sukasada',
            'desa_kelurahan_ktp' => 'Tegallinggah',
            'kode_pos_ktp' => '81161',
            'alamat_lengkap_ktp' => 'Banjar Dinas Tegallinggah, RT/RW 001/002',
            'provinsi_domisili' => 'Bali',
            'kabupaten_domisili' => 'Buleleng',
            'kecamatan_domisili' => 'Sukasada',
            'desa_kelurahan_domisili' => 'Tegallinggah',
            'kode_pos_domisili' => '81161',
            'alamat_lengkap_domisili' => 'Banjar Dinas Tegallinggah, RT/RW 001/002',
            'latitude_domisili' => -8.1568224,
            'longitude_domisili' => 115.0972345,
            'telepon' => '6285123456789',
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password123'),
            'photo' => null,
            'role' => 'administrator',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
