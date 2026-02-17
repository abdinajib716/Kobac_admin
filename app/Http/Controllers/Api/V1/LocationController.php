<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Country;
use App\Models\District;
use App\Models\Region;
use Illuminate\Http\JsonResponse;

class LocationController extends BaseController
{
    /**
     * Get all active countries
     * GET /api/v1/locations/countries
     */
    public function countries(): JsonResponse
    {
        $countries = Country::active()
            ->ordered()
            ->get()
            ->map(fn ($country) => [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
                'code_alpha2' => $country->code_alpha2,
                'phone_code' => $country->phone_code,
                'currency' => $country->currency,
                'flag' => $country->flag,
            ]);

        return $this->success([
            'countries' => $countries,
        ]);
    }

    /**
     * Get regions for a specific country
     * GET /api/v1/locations/countries/{countryId}/regions
     */
    public function regions(int $countryId): JsonResponse
    {
        $country = Country::find($countryId);

        if (!$country) {
            return $this->error('Country not found', 'NOT_FOUND', 404);
        }

        $regions = $country->activeRegions()
            ->get()
            ->map(fn ($region) => [
                'id' => $region->id,
                'name' => $region->name,
                'code' => $region->code,
            ]);

        return $this->success([
            'country' => [
                'id' => $country->id,
                'name' => $country->name,
            ],
            'regions' => $regions,
        ]);
    }

    /**
     * Get districts for a specific region
     * GET /api/v1/locations/regions/{regionId}/districts
     */
    public function districts(int $regionId): JsonResponse
    {
        $region = Region::with('country')->find($regionId);

        if (!$region) {
            return $this->error('Region not found', 'NOT_FOUND', 404);
        }

        $districts = $region->activeDistricts()
            ->get()
            ->map(fn ($district) => [
                'id' => $district->id,
                'name' => $district->name,
                'code' => $district->code,
            ]);

        return $this->success([
            'country' => [
                'id' => $region->country->id,
                'name' => $region->country->name,
            ],
            'region' => [
                'id' => $region->id,
                'name' => $region->name,
            ],
            'districts' => $districts,
        ]);
    }

    /**
     * Get complete location hierarchy
     * GET /api/v1/locations/hierarchy
     * 
     * Returns complete country -> region -> district hierarchy
     * Useful for caching on mobile apps
     */
    public function hierarchy(): JsonResponse
    {
        $countries = Country::active()
            ->ordered()
            ->with(['activeRegions.activeDistricts'])
            ->get()
            ->map(fn ($country) => [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
                'phone_code' => $country->phone_code,
                'flag' => $country->flag,
                'regions' => $country->activeRegions->map(fn ($region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'districts' => $region->activeDistricts->map(fn ($district) => [
                        'id' => $district->id,
                        'name' => $district->name,
                    ]),
                ]),
            ]);

        return $this->success([
            'locations' => $countries,
            'cached_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Search locations by name
     * GET /api/v1/locations/search?q=mogadishu
     */
    public function search(): JsonResponse
    {
        $query = request('q');

        if (!$query || strlen($query) < 2) {
            return $this->error('Search query must be at least 2 characters', 'VALIDATION_ERROR', 422);
        }

        $districts = District::active()
            ->where('name', 'like', "%{$query}%")
            ->with(['region.country'])
            ->limit(20)
            ->get()
            ->map(fn ($district) => [
                'type' => 'district',
                'id' => $district->id,
                'name' => $district->name,
                'region' => [
                    'id' => $district->region->id,
                    'name' => $district->region->name,
                ],
                'country' => [
                    'id' => $district->region->country->id,
                    'name' => $district->region->country->name,
                ],
                'full_name' => "{$district->name}, {$district->region->name}, {$district->region->country->name}",
            ]);

        $regions = Region::active()
            ->where('name', 'like', "%{$query}%")
            ->with('country')
            ->limit(10)
            ->get()
            ->map(fn ($region) => [
                'type' => 'region',
                'id' => $region->id,
                'name' => $region->name,
                'country' => [
                    'id' => $region->country->id,
                    'name' => $region->country->name,
                ],
                'full_name' => "{$region->name}, {$region->country->name}",
            ]);

        return $this->success([
            'query' => $query,
            'results' => $districts->merge($regions),
        ]);
    }
}
