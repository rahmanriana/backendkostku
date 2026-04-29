<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KostController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'city' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['putra', 'putri', 'campur'])],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:0'],
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

        if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
            $latitude = (float) $validated['latitude'];
            $longitude = (float) $validated['longitude'];
            $radius = (float) ($validated['radius'] ?? 5);

            $query
                ->selectRaw("kosts.*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance", [$latitude, $longitude, $latitude])
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'owner') {
            return response()->json(['message' => 'Hanya owner yang boleh membuat kost.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'type' => ['required', Rule::in(['putra', 'putri', 'campur'])],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['integer', 'exists:facilities,id'],
        ]);

        $kost = DB::transaction(function () use ($validated, $user) {
            $kost = Kost::create([
                'owner_id' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'type' => $validated['type'],
            ]);

            if (!empty($validated['facility_ids'])) {
                $kost->facilities()->sync($validated['facility_ids']);
            }

            return $kost;
        });

        return response()->json([
            'data' => $kost->load(['owner', 'rooms', 'facilities']),
        ], 201);
    }

    public function show(string $id)
    {
        $kost = Kost::with(['owner', 'rooms.photos', 'rooms.facilities', 'facilities'])->findOrFail($id);

        return response()->json([
            'data' => $kost,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $kost = Kost::findOrFail($id);

        if ($user->role !== 'owner' || $kost->owner_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'province' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'type' => ['sometimes', Rule::in(['putra', 'putri', 'campur'])],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['integer', 'exists:facilities,id'],
        ]);

        DB::transaction(function () use ($kost, $validated) {
            $kost->fill($validated);
            $kost->save();

            if (array_key_exists('facility_ids', $validated)) {
                $kost->facilities()->sync($validated['facility_ids'] ?? []);
            }
        });

        return response()->json([
            'data' => $kost->load(['owner', 'rooms', 'facilities']),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $kost = Kost::findOrFail($id);

        if ($user->role !== 'owner' || $kost->owner_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $kost->delete();

        return response()->json([
            'message' => 'Kost berhasil dihapus.',
        ]);
    }

    public function myKosts(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'owner') {
            return response()->json(['message' => 'Hanya owner yang memiliki daftar kost.'], 403);
        }

        $kosts = Kost::where('owner_id', $user->id)
            ->with(['owner', 'rooms', 'facilities'])
            ->get();

        return response()->json([
            'data' => $kosts,
        ]);
    }
}
