<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoomPhotoController extends Controller
{
    public function store(Request $request, Room $room)
    {
        $user = $request->user();

        if ($user->role !== 'owner' || $room->kost->owner_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $isPrimary = (bool) ($validated['is_primary'] ?? false);

        $path = $request->file('photo')->store('room_photos', 'public');
        $photoUrl = url('/storage/' . $path);

        $roomPhoto = DB::transaction(function () use ($room, $photoUrl, $isPrimary) {
            if ($isPrimary) {
                $room->photos()->where('is_primary', true)->update(['is_primary' => false]);
            }

            return RoomPhoto::create([
                'room_id' => $room->id,
                'photo_url' => $photoUrl,
                'is_primary' => $isPrimary,
            ]);
        });

        return response()->json([
            'data' => $roomPhoto,
        ], 201);
    }

    public function destroy(Request $request, RoomPhoto $photo)
    {
        $user = $request->user();
        $photo->loadMissing('room.kost');

        if ($user->role !== 'owner' || $photo->room->kost->owner_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $this->deleteStoredFileIfPossible($photo->photo_url);

        $photo->delete();

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    private function deleteStoredFileIfPossible(string $photoUrl): void
    {
        $path = parse_url($photoUrl, PHP_URL_PATH);
        if (!is_string($path)) {
            return;
        }

        $prefix = '/storage/room_photos/';
        if (!str_starts_with($path, $prefix)) {
            return;
        }

        $relative = substr($path, strlen($prefix));
        if ($relative === false || $relative === '') {
            return;
        }

        Storage::disk('public')->delete('room_photos/' . $relative);
    }
}
