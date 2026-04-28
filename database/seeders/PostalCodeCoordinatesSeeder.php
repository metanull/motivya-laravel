<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PostalCodeCoordinate;
use Illuminate\Database\Seeder;

final class PostalCodeCoordinatesSeeder extends Seeder
{
    /**
     * Seed Belgian postal-code → coordinate reference data.
     * Uses upsert so the seeder is safe to re-run at any time.
     */
    public function run(): void
    {
        $rows = [
            ['postal_code' => '1000', 'municipality' => 'Bruxelles/Brussel', 'latitude' => 50.8503000, 'longitude' => 4.3517000],
            ['postal_code' => '1005', 'municipality' => 'Bruxelles', 'latitude' => 50.8467000, 'longitude' => 4.3525000],
            ['postal_code' => '1010', 'municipality' => 'Bruxelles', 'latitude' => 50.8667000, 'longitude' => 4.3500000],
            ['postal_code' => '1020', 'municipality' => 'Laeken', 'latitude' => 50.8833000, 'longitude' => 4.3417000],
            ['postal_code' => '1030', 'municipality' => 'Schaerbeek', 'latitude' => 50.8667000, 'longitude' => 4.3833000],
            ['postal_code' => '1040', 'municipality' => 'Etterbeek', 'latitude' => 50.8333000, 'longitude' => 4.3833000],
            ['postal_code' => '1050', 'municipality' => 'Ixelles', 'latitude' => 50.8250000, 'longitude' => 4.3667000],
            ['postal_code' => '1060', 'municipality' => 'Saint-Gilles', 'latitude' => 50.8333000, 'longitude' => 4.3500000],
            ['postal_code' => '1070', 'municipality' => 'Anderlecht', 'latitude' => 50.8333000, 'longitude' => 4.3000000],
            ['postal_code' => '1080', 'municipality' => 'Molenbeek-Saint-Jean', 'latitude' => 50.8500000, 'longitude' => 4.3167000],
            ['postal_code' => '1081', 'municipality' => 'Koekelberg', 'latitude' => 50.8583000, 'longitude' => 4.3250000],
            ['postal_code' => '1082', 'municipality' => 'Berchem-Sainte-Agathe', 'latitude' => 50.8583000, 'longitude' => 4.2917000],
            ['postal_code' => '1083', 'municipality' => 'Ganshoren', 'latitude' => 50.8750000, 'longitude' => 4.3083000],
            ['postal_code' => '1090', 'municipality' => 'Jette', 'latitude' => 50.8833000, 'longitude' => 4.3250000],
            ['postal_code' => '1140', 'municipality' => 'Evere', 'latitude' => 50.8833000, 'longitude' => 4.3833000],
            ['postal_code' => '1150', 'municipality' => 'Woluwe-Saint-Pierre', 'latitude' => 50.8333000, 'longitude' => 4.4167000],
            ['postal_code' => '1160', 'municipality' => 'Auderghem', 'latitude' => 50.8000000, 'longitude' => 4.4167000],
            ['postal_code' => '1170', 'municipality' => 'Watermael-Boitsfort', 'latitude' => 50.7833000, 'longitude' => 4.4167000],
            ['postal_code' => '1180', 'municipality' => 'Uccle', 'latitude' => 50.8000000, 'longitude' => 4.3333000],
            ['postal_code' => '1190', 'municipality' => 'Forest', 'latitude' => 50.8083000, 'longitude' => 4.3333000],
            ['postal_code' => '1200', 'municipality' => 'Woluwe-Saint-Lambert', 'latitude' => 50.8500000, 'longitude' => 4.4167000],
            ['postal_code' => '1210', 'municipality' => 'Saint-Josse-ten-Noode', 'latitude' => 50.8583000, 'longitude' => 4.3667000],
            ['postal_code' => '2000', 'municipality' => 'Antwerpen', 'latitude' => 51.2194000, 'longitude' => 4.4025000],
            ['postal_code' => '2018', 'municipality' => 'Antwerpen', 'latitude' => 51.2056000, 'longitude' => 4.4014000],
            ['postal_code' => '2060', 'municipality' => 'Antwerpen', 'latitude' => 51.2278000, 'longitude' => 4.4069000],
            ['postal_code' => '3000', 'municipality' => 'Leuven', 'latitude' => 50.8795000, 'longitude' => 4.7005000],
            ['postal_code' => '3010', 'municipality' => 'Kessel-Lo', 'latitude' => 50.8900000, 'longitude' => 4.7267000],
            ['postal_code' => '4000', 'municipality' => 'Liège', 'latitude' => 50.6326000, 'longitude' => 5.5797000],
            ['postal_code' => '4020', 'municipality' => 'Liège', 'latitude' => 50.6328000, 'longitude' => 5.6003000],
            ['postal_code' => '5000', 'municipality' => 'Namur', 'latitude' => 50.4669000, 'longitude' => 4.8675000],
            ['postal_code' => '6000', 'municipality' => 'Charleroi', 'latitude' => 50.4108000, 'longitude' => 4.4446000],
            ['postal_code' => '7000', 'municipality' => 'Mons', 'latitude' => 50.4542000, 'longitude' => 3.9562000],
            ['postal_code' => '8000', 'municipality' => 'Brugge', 'latitude' => 51.2093000, 'longitude' => 3.2247000],
            ['postal_code' => '9000', 'municipality' => 'Gent', 'latitude' => 51.0543000, 'longitude' => 3.7174000],
            ['postal_code' => '9050', 'municipality' => 'Ledeberg', 'latitude' => 51.0333000, 'longitude' => 3.7333000],
        ];

        PostalCodeCoordinate::upsert(
            $rows,
            ['postal_code'],
            ['municipality', 'latitude', 'longitude'],
        );
    }
}
