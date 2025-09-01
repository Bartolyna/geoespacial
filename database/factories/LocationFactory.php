<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cities = [
            ['name' => 'Madrid Centro', 'city' => 'Madrid', 'country' => 'Spain', 'lat' => 40.4168, 'lng' => -3.7038],
            ['name' => 'Barcelona Puerto', 'city' => 'Barcelona', 'country' => 'Spain', 'lat' => 41.3851, 'lng' => 2.1734],
            ['name' => 'Valencia Playa', 'city' => 'Valencia', 'country' => 'Spain', 'lat' => 39.4699, 'lng' => -0.3763],
            ['name' => 'Sevilla Centro', 'city' => 'Sevilla', 'country' => 'Spain', 'lat' => 37.3891, 'lng' => -5.9845],
            ['name' => 'Bilbao Casco Viejo', 'city' => 'Bilbao', 'country' => 'Spain', 'lat' => 43.2627, 'lng' => -2.9253],
            ['name' => 'Rome Centro', 'city' => 'Rome', 'country' => 'Italy', 'lat' => 41.9028, 'lng' => 12.4964],
            ['name' => 'Milan Duomo', 'city' => 'Milan', 'country' => 'Italy', 'lat' => 45.4642, 'lng' => 9.1900],
            ['name' => 'Naples Historic', 'city' => 'Naples', 'country' => 'Italy', 'lat' => 40.8518, 'lng' => 14.2681],
            ['name' => 'Paris Torre Eiffel', 'city' => 'Paris', 'country' => 'France', 'lat' => 48.8566, 'lng' => 2.3522],
            ['name' => 'Lyon Centro', 'city' => 'Lyon', 'country' => 'France', 'lat' => 45.7640, 'lng' => 4.8357],
            ['name' => 'New York Manhattan', 'city' => 'New York', 'country' => 'United States', 'lat' => 40.7128, 'lng' => -74.0060],
            ['name' => 'Los Angeles Downtown', 'city' => 'Los Angeles', 'country' => 'United States', 'lat' => 34.0522, 'lng' => -118.2437],
            ['name' => 'Bogotá Zona Rosa', 'city' => 'Bogotá', 'country' => 'Colombia', 'lat' => 4.7110, 'lng' => -74.0721],
            ['name' => 'Medellín El Poblado', 'city' => 'Medellín', 'country' => 'Colombia', 'lat' => 6.2442, 'lng' => -75.5812],
            ['name' => 'Mexico City Zócalo', 'city' => 'Mexico City', 'country' => 'Mexico', 'lat' => 19.4326, 'lng' => -99.1332],
            ['name' => 'Buenos Aires Puerto Madero', 'city' => 'Buenos Aires', 'country' => 'Argentina', 'lat' => -34.6118, 'lng' => -58.3960],
            ['name' => 'Lima Centro', 'city' => 'Lima', 'country' => 'Peru', 'lat' => -12.0464, 'lng' => -77.0428],
            ['name' => 'Santiago Las Condes', 'city' => 'Santiago', 'country' => 'Chile', 'lat' => -33.4489, 'lng' => -70.6693],
            ['name' => 'London Westminster', 'city' => 'London', 'country' => 'United Kingdom', 'lat' => 51.5074, 'lng' => -0.1278],
            ['name' => 'Berlin Mitte', 'city' => 'Berlin', 'country' => 'Germany', 'lat' => 52.5200, 'lng' => 13.4050],
        ];

        $location = $this->faker->randomElement($cities);

        return [
            'name' => $location['name'],
            'city' => $location['city'],
            'country' => $location['country'],
            'state' => $this->getStateForCountry($location['country']),
            'latitude' => $location['lat'] + $this->faker->randomFloat(4, -0.01, 0.01), // Pequeña variación
            'longitude' => $location['lng'] + $this->faker->randomFloat(4, -0.01, 0.01), // Pequeña variación
            'openweather_id' => $this->faker->optional(0.7)->numberBetween(1000000, 9999999),
            'active' => $this->faker->boolean(85), // 85% activas
            'metadata' => $this->generateMetadata(),
        ];
    }

    /**
     * Estado para ubicaciones activas
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Estado para ubicaciones inactivas
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Estado para ubicaciones españolas
     */
    public function spanish(): static
    {
        $spanishCities = [
            ['name' => 'Madrid Centro', 'city' => 'Madrid', 'lat' => 40.4168, 'lng' => -3.7038],
            ['name' => 'Barcelona Eixample', 'city' => 'Barcelona', 'lat' => 41.3851, 'lng' => 2.1734],
            ['name' => 'Valencia Ciudad', 'city' => 'Valencia', 'lat' => 39.4699, 'lng' => -0.3763],
            ['name' => 'Sevilla Triana', 'city' => 'Sevilla', 'lat' => 37.3891, 'lng' => -5.9845],
            ['name' => 'Zaragoza Centro', 'city' => 'Zaragoza', 'lat' => 41.6488, 'lng' => -0.8891],
            ['name' => 'Málaga Puerto', 'city' => 'Málaga', 'lat' => 36.7213, 'lng' => -4.4214],
            ['name' => 'Murcia Centro', 'city' => 'Murcia', 'lat' => 37.9922, 'lng' => -1.1307],
            ['name' => 'Palma Casco Antiguo', 'city' => 'Palma', 'lat' => 39.5696, 'lng' => 2.6502],
            ['name' => 'Las Palmas Vegueta', 'city' => 'Las Palmas', 'lat' => 28.1248, 'lng' => -15.4300],
            ['name' => 'Bilbao Casco Viejo', 'city' => 'Bilbao', 'lat' => 43.2627, 'lng' => -2.9253],
        ];

        $city = $this->faker->randomElement($spanishCities);

        return $this->state(fn (array $attributes) => [
            'name' => $city['name'],
            'city' => $city['city'],
            'country' => 'Spain',
            'state' => $this->faker->randomElement(['Madrid', 'Cataluña', 'Valencia', 'Andalucía', 'País Vasco']),
            'latitude' => $city['lat'] + $this->faker->randomFloat(4, -0.01, 0.01),
            'longitude' => $city['lng'] + $this->faker->randomFloat(4, -0.01, 0.01),
        ]);
    }

    /**
     * Estado para ubicaciones europeas
     */
    public function european(): static
    {
        $europeanCities = [
            ['name' => 'Rome Centro', 'city' => 'Rome', 'country' => 'Italy', 'lat' => 41.9028, 'lng' => 12.4964],
            ['name' => 'Paris Centro', 'city' => 'Paris', 'country' => 'France', 'lat' => 48.8566, 'lng' => 2.3522],
            ['name' => 'Berlin Mitte', 'city' => 'Berlin', 'country' => 'Germany', 'lat' => 52.5200, 'lng' => 13.4050],
            ['name' => 'Amsterdam Centro', 'city' => 'Amsterdam', 'country' => 'Netherlands', 'lat' => 52.3676, 'lng' => 4.9041],
            ['name' => 'Vienna Centro', 'city' => 'Vienna', 'country' => 'Austria', 'lat' => 48.2082, 'lng' => 16.3738],
            ['name' => 'Prague Centro', 'city' => 'Prague', 'country' => 'Czech Republic', 'lat' => 50.0755, 'lng' => 14.4378],
            ['name' => 'Lisbon Baixa', 'city' => 'Lisbon', 'country' => 'Portugal', 'lat' => 38.7223, 'lng' => -9.1393],
            ['name' => 'Brussels Centro', 'city' => 'Brussels', 'country' => 'Belgium', 'lat' => 50.8503, 'lng' => 4.3517],
        ];

        $city = $this->faker->randomElement($europeanCities);

        return $this->state(fn (array $attributes) => [
            'name' => $city['name'],
            'city' => $city['city'],
            'country' => $city['country'],
            'state' => $this->getStateForCountry($city['country']),
            'latitude' => $city['lat'] + $this->faker->randomFloat(4, -0.01, 0.01),
            'longitude' => $city['lng'] + $this->faker->randomFloat(4, -0.01, 0.01),
        ]);
    }

    /**
     * Obtiene el estado/provincia según el país
     */
    private function getStateForCountry(string $country): ?string
    {
        $states = [
            'Spain' => ['Madrid', 'Cataluña', 'Valencia', 'Andalucía', 'País Vasco', 'Galicia'],
            'Italy' => ['Lazio', 'Lombardia', 'Campania', 'Veneto', 'Sicilia'],
            'France' => ['Île-de-France', 'Provence-Alpes-Côte d\'Azur', 'Rhône-Alpes', 'Nord-Pas-de-Calais'],
            'Germany' => ['Bayern', 'Baden-Württemberg', 'Nordrhein-Westfalen', 'Berlin'],
            'United States' => ['California', 'New York', 'Texas', 'Florida', 'Illinois'],
            'Colombia' => ['Cundinamarca', 'Antioquia', 'Valle del Cauca', 'Santander'],
        ];

        return isset($states[$country]) 
            ? $this->faker->randomElement($states[$country])
            : null;
    }

    /**
     * Genera metadata de ejemplo
     */
    private function generateMetadata(): array
    {
        return [
            'elevation' => $this->faker->numberBetween(0, 2000),
            'population' => $this->faker->numberBetween(10000, 5000000),
            'area_km2' => $this->faker->randomFloat(2, 10, 500),
            'climate_zone' => $this->faker->randomElement(['Mediterranean', 'Continental', 'Oceanic', 'Subtropical']),
            'last_updated' => now()->toISOString(),
        ];
    }
}
