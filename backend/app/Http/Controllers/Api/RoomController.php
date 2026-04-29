<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function index(Request $request, Kost $kost)
    {
        return response()->json([
            'data' => $kost->rooms()->with(['photos', 'facilities'])->get(),
        ]);
    }

    public function store(Request $request, Kost $kost)
    {
        $user = $request->user();

        if ($user->role !== 'owner' || $kost->owner_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_available' => ['nullable', 'boolean'],
            'size' => ['nullable', 'integer', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'description' => ['required', 'string'],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['integer', 'exists:facilities,id'],
        ]);

        $room = DB::transaction(function () use ($kost, $validated) {
            $room = $kost->rooms()->create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'is_available' => $validated['is_available'] ?? true,
                'size' => $validated['size'] ?? null,
                'capacity' => $validated['capacity'] ?? 1,
                'description' => $validated['description'],
            ]);

            if (!empty($validated['facility_ids'])) {
                $room->facilities()->sync($validated['facility_ids']);
            }

            return $room;
        });

        return response()->json([
            'data' => $room->load(['kost', 'photos', 'facilities']),
        ], 201);
    }

    public function show(Request $request, Kost $kost, Room $room)
    {
        if ($room->kost_id !== $kost->id) {
            return response()->json(['message' => 'Room tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $room->load(['photos', 'facilities', 'kost']),
        ]);
    }

    public function update(Request $request, Kost $kost, Room $room)
    {
        $user = $request->user();

        if ($room->kost_id !== $kost->id) {
            return response()->json(['message' => 'Room tidak ditemukan.'], 404);
        }

        if ($user->role !== 'owner' || $kost->owner_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
            'size' => ['sometimes', 'integer', 'min:0'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'description' => ['sometimes', 'string'],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['integer', 'exists:facilities,id'],
        ]);

        DB::transaction(function () use ($room, $validated) {
            $room->fill($validated);
            $room->save();

            if (array_key_exists('facility_ids', $validated)) {
                $room->facilities()->sync($validated['facility_ids'] ?? []);
            }
        });

        return response()->json([
            'data' => $room->load(['photos', 'facilities', 'kost']),
        ]);
    }

    public function destroy(Request $request, Kost $kost, Room $room)
    {
        $user = $request->user();

        if ($room->kost_id !== $kost->id) {
            return response()->json(['message' => 'Room tidak ditemukan.'], 404);
        }

        if ($user->role !== 'owner' || $kost->owner_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $room->delete();

        return response()->json([
            'message' => 'Room berhasil dihapus.',
        ]);
    }
}
