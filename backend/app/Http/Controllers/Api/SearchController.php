<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SearchController extends Controller
{
    public function nearby(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:0'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $radius = (float) ($validated['radius'] ?? 5);

        $kosts = Kost::selectRaw(
            "kosts.*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance",
            [$latitude, $longitude, $latitude]
        )
            ->having('distance', '<', $radius)
            ->orderBy('distance', 'asc')
            ->with(['owner', 'rooms', 'facilities'])
            ->get();

        return response()->json([
            'data' => $kosts,
        ]);
    }

    public function filter(Request $request)
    {
        $validated = $request->validate([
            'city' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['putra', 'putri', 'campur'])],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['integer', 'exists:facilities,id'],
        ]);

        $query = Kost::query()->with(['owner', 'rooms', 'facilities']);

        if (!empty($validated['city'])) {
            $query->where('city', $validated['city']);
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['min_price']) || isset($validated['max_price'])) {
            $min = $validated['min_price'] ?? null;
            $max = $validated['max_price'] ?? null;

            $query->whereHas('rooms', function ($q) use ($min, $max) {
                if ($min !== null) {
                    $q->where('price', '>=', $min);
                }
                if ($max !== null) {
                    $q->where('price', '<=', $max);
                }
            });
        }

        if (!empty($validated['facility_ids'])) {
            $facilityIds = $validated['facility_ids'];
            $query->whereHas('facilities', function ($q) use ($facilityIds) {
                $q->whereIn('facilities.id', $facilityIds);
            });
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }
}
