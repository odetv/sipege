<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WilayahController extends Controller
{
    private const BASE_URL = 'https://wilayah.id/api';

    public function provinces(): JsonResponse
    {
        $data = Cache::remember('wilayah_provinces', 86400, function () {
            try {
                $response = Http::timeout(5)->get(self::BASE_URL . '/provinces.json');
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                // Ignore and try fallback
            }

            try {
                $fallback = Http::timeout(5)->get('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json');
                return $fallback->successful() ? ['data' => $fallback->json()] : null;
            } catch (\Exception $e) {
                return null;
            }
        });

        return response()->json($data ?? ['data' => []]);
    }

    public function regencies(string $provinceCode): JsonResponse
    {
        $data = Cache::remember("wilayah_regencies_{$provinceCode}", 86400, function () use ($provinceCode) {
            try {
                $response = Http::timeout(5)->get(self::BASE_URL . "/regencies/{$provinceCode}.json");
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                // Ignore and try fallback
            }

            try {
                $clean = str_replace('.', '', $provinceCode);
                $fallback = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$clean}.json");
                return $fallback->successful() ? ['data' => $fallback->json()] : null;
            } catch (\Exception $e) {
                return null;
            }
        });

        return response()->json($data ?? ['data' => []]);
    }

    public function districts(string $regencyCode): JsonResponse
    {
        $data = Cache::remember("wilayah_districts_{$regencyCode}", 86400, function () use ($regencyCode) {
            try {
                $response = Http::timeout(5)->get(self::BASE_URL . "/districts/{$regencyCode}.json");
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                // Ignore and try fallback
            }

            try {
                $clean = str_replace('.', '', $regencyCode);
                $fallback = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/districts/{$clean}.json");
                return $fallback->successful() ? ['data' => $fallback->json()] : null;
            } catch (\Exception $e) {
                return null;
            }
        });

        return response()->json($data ?? ['data' => []]);
    }

    public function villages(string $districtCode): JsonResponse
    {
        $data = Cache::remember("wilayah_villages_{$districtCode}", 86400, function () use ($districtCode) {
            try {
                $response = Http::timeout(5)->get(self::BASE_URL . "/villages/{$districtCode}.json");
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                // Ignore and try fallback
            }

            try {
                $clean = str_replace('.', '', $districtCode);
                $fallback = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/villages/{$clean}.json");
                return $fallback->successful() ? ['data' => $fallback->json()] : null;
            } catch (\Exception $e) {
                return null;
            }
        });

        return response()->json($data ?? ['data' => []]);
    }
}
