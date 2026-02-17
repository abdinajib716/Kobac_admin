<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LocationSeeder extends Seeder
{
    /**
     * Seed location data from JSON configuration files.
     * 
     * This seeder loads country, region, and district data from JSON files
     * located in database/data/locations/ directory.
     * 
     * JSON Structure:
     * {
     *   "country": {
     *     "name": "Country Name",
     *     "code": "ISO3",
     *     "code_alpha2": "IS",
     *     "phone_code": "+123",
     *     "currency": "CUR",
     *     "flag": "🏳️"
     *   },
     *   "regions": {
     *     "Region Name": ["District 1", "District 2", ...]
     *   }
     * }
     */
    public function run(): void
    {
        $locationsPath = database_path('data/locations');
        
        if (!File::isDirectory($locationsPath)) {
            $this->command->warn("Locations directory not found: {$locationsPath}");
            return;
        }

        $jsonFiles = File::files($locationsPath);
        
        if (empty($jsonFiles)) {
            $this->command->warn("No JSON files found in: {$locationsPath}");
            return;
        }

        foreach ($jsonFiles as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $this->seedFromFile($file->getPathname());
        }
    }

    /**
     * Seed location data from a single JSON file.
     */
    protected function seedFromFile(string $filePath): void
    {
        $this->command->info("Loading location data from: " . basename($filePath));

        $jsonContent = File::get($filePath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("Invalid JSON in file: " . basename($filePath));
            Log::error("LocationSeeder: Invalid JSON", ['file' => $filePath, 'error' => json_last_error_msg()]);
            return;
        }

        if (!isset($data['country']) || !isset($data['regions'])) {
            $this->command->error("Invalid structure in file: " . basename($filePath));
            return;
        }

        DB::beginTransaction();

        try {
            // Create or update country
            $country = Country::updateOrCreate(
                ['code' => $data['country']['code']],
                [
                    'name' => $data['country']['name'],
                    'code_alpha2' => $data['country']['code_alpha2'] ?? null,
                    'phone_code' => $data['country']['phone_code'] ?? null,
                    'currency' => $data['country']['currency'] ?? null,
                    'flag' => $data['country']['flag'] ?? null,
                    'is_active' => true,
                ]
            );

            $this->command->info("  ✓ Country: {$country->name}");

            $regionCount = 0;
            $districtCount = 0;

            // Create regions and districts
            foreach ($data['regions'] as $regionName => $districts) {
                $region = Region::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $regionName,
                    ],
                    [
                        'is_active' => true,
                        'sort_order' => $regionCount,
                    ]
                );
                $regionCount++;

                // Create districts for this region
                $districtOrder = 0;
                foreach ($districts as $districtName) {
                    District::updateOrCreate(
                        [
                            'region_id' => $region->id,
                            'name' => $districtName,
                        ],
                        [
                            'is_active' => true,
                            'sort_order' => $districtOrder,
                        ]
                    );
                    $districtOrder++;
                    $districtCount++;
                }
            }

            DB::commit();

            $this->command->info("  ✓ Regions: {$regionCount}");
            $this->command->info("  ✓ Districts: {$districtCount}");
            $this->command->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Failed to seed from: " . basename($filePath));
            Log::error("LocationSeeder: Seeding failed", [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Static method to seed a specific country from a JSON file.
     * Can be called programmatically to add new countries.
     */
    public static function seedCountry(string $jsonFilePath): array
    {
        if (!File::exists($jsonFilePath)) {
            return ['success' => false, 'message' => 'File not found: ' . $jsonFilePath];
        }

        $jsonContent = File::get($jsonFilePath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid JSON format'];
        }

        if (!isset($data['country']) || !isset($data['regions'])) {
            return ['success' => false, 'message' => 'Invalid JSON structure'];
        }

        DB::beginTransaction();

        try {
            $country = Country::updateOrCreate(
                ['code' => $data['country']['code']],
                [
                    'name' => $data['country']['name'],
                    'code_alpha2' => $data['country']['code_alpha2'] ?? null,
                    'phone_code' => $data['country']['phone_code'] ?? null,
                    'currency' => $data['country']['currency'] ?? null,
                    'flag' => $data['country']['flag'] ?? null,
                    'is_active' => true,
                ]
            );

            $regionCount = 0;
            $districtCount = 0;

            foreach ($data['regions'] as $regionName => $districts) {
                $region = Region::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $regionName,
                    ],
                    [
                        'is_active' => true,
                        'sort_order' => $regionCount,
                    ]
                );
                $regionCount++;

                $districtOrder = 0;
                foreach ($districts as $districtName) {
                    District::updateOrCreate(
                        [
                            'region_id' => $region->id,
                            'name' => $districtName,
                        ],
                        [
                            'is_active' => true,
                            'sort_order' => $districtOrder,
                        ]
                    );
                    $districtOrder++;
                    $districtCount++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Country seeded successfully',
                'data' => [
                    'country' => $country->name,
                    'regions' => $regionCount,
                    'districts' => $districtCount,
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Seeding failed: ' . $e->getMessage(),
            ];
        }
    }
}
