<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Kost;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $favorites = Favorite::where('user_id', $user->id)
            ->with(['kost.owner', 'kost.rooms', 'kost.facilities'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $favorites,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'kost_id' => ['required', 'integer', 'exists:kosts,id'],
        ]);

        $existing = Favorite::where('user_id', $user->id)
            ->where('kost_id', $validated['kost_id'])
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'favorited' => false,
            ]);
        }

        Favorite::create([
            'user_id' => $user->id,
            'kost_id' => $validated['kost_id'],
        ]);

        return response()->json([
            'favorited' => true,
        ], 201);
    }

    public function destroy(Request $request, Kost $kost)
    {
        $user = $request->user();

        Favorite::where('user_id', $user->id)
            ->where('kost_id', $kost->id)
            ->delete();

        return response()->json([
            'message' => 'Favorite dihapus.',
        ]);
    }
}
