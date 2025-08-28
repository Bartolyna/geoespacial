<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Cali Centro',
                'city' => 'Cali',
                'country' => 'Colombia',
                'state' => 'Valle del Cauca',
                'latitude' => 3.4516,
                'longitude' => -76.5320,
                'active' => true,
            ],
            [
                'name' => 'Bogotá Centro',
                'city' => 'Bogotá',
                'country' => 'Colombia',
                'state' => 'Cundinamarca',
                'latitude' => 4.7110,
                'longitude' => -74.0721,
                'active' => true,
            ],
            [
                'name' => 'Medellín Centro',
                'city' => 'Medellín',
                'country' => 'Colombia',
                'state' => 'Antioquia',
                'latitude' => 6.2442,
                'longitude' => -75.5812,
                'active' => true,
            ],
            [
                'name' => 'New York Centro',
                'city' => 'New York',
                'country' => 'United States',
                'state' => 'New York',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'active' => true,
            ],
            [
                'name' => 'Milan Centro',
                'city' => 'Milan',
                'country' => 'Italy',
                'state' => 'Lombardy',
                'latitude' => 45.4642,
                'longitude' => 9.1900,
                'active' => true,
            ],
            [
                'name' => 'Madrid Centro',
                'city' => 'Madrid',
                'country' => 'Spain',
                'state' => 'Community of Madrid',
                'latitude' => 40.4168,
                'longitude' => -3.7038,
                'active' => true,
            ],
        ];

        foreach ($locations as $locationData) {
            Location::create($locationData);
        }
    }
}
