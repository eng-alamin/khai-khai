<?php
// app/Services/DeliveryChargeService.php

namespace App\Services;

use App\Models\AdminSetting;

class DeliveryChargeService
{
    /**
     * Backward-compatible entry point — returns fee only (Taka, decimal).
     * Used anywhere the caller doesn't need the distance value.
     */
    public function calculate(
        ?float $originLat,
        ?float $originLng,
        ?float $destLat,
        ?float $destLng,
        float $fallbackFee
    ): float {
        return $this->calculateWithDistance($originLat, $originLng, $destLat, $destLng, $fallbackFee)['fee'];
    }

    /**
     * Full calculation — returns both distance (km) and fee (Taka, decimal).
     * Falls back to a flat fee (and null distance) when either point is missing.
     *
     * @return array{distance_km: float|null, fee: float}
     */
    public function calculateWithDistance(
        ?float $originLat,
        ?float $originLng,
        ?float $destLat,
        ?float $destLng,
        float $fallbackFee
    ): array {
        if (is_null($originLat) || is_null($originLng) || is_null($destLat) || is_null($destLng)) {
            return [
                'distance_km' => null,
                'fee' => $fallbackFee,
            ];
        }

        $distanceKm = $this->haversineDistanceKm($originLat, $originLng, $destLat, $destLng);

        $minimumFee  = (float) AdminSetting::get('minimum_delivery_charge', 40);
        $perKmRate   = (float) AdminSetting::get('per_km_delivery_rate', 10);
        $includedKm  = (float) AdminSetting::get('included_km_in_minimum', 1);

        $extraKm = max(0, $distanceKm - $includedKm);
        $chargeableExtraKm = (int) ceil($extraKm);

        $fee = round($minimumFee + ($chargeableExtraKm * $perKmRate), 2);

        return [
            'distance_km' => round($distanceKm, 2),
            'fee' => $fee,
        ];
    }

    private function haversineDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}