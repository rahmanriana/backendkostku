<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Kost;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Contact::query()->with(['seeker', 'owner', 'kost']);

        if ($user->role === 'owner') {
            $query->where('owner_id', $user->id);
        } else {
            $query->where('seeker_id', $user->id);
        }

        return response()->json([
            'data' => $query->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'seeker') {
            return response()->json(['message' => 'Hanya seeker yang bisa menghubungi owner.'], 403);
        }

        $validated = $request->validate([
            'kost_id' => ['required', 'integer', 'exists:kosts,id'],
            'message' => ['required', 'string'],
        ]);

        $kost = Kost::findOrFail($validated['kost_id']);

        if ($kost->owner_id === $user->id) {
            return response()->json(['message' => 'Tidak bisa menghubungi kost milik sendiri.'], 422);
        }

        $contact = Contact::create([
            'seeker_id' => $user->id,
            'owner_id' => $kost->owner_id,
            'kost_id' => $kost->id,
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => $contact->load(['seeker', 'owner', 'kost']),
        ], 201);
    }

    public function updateStatus(Request $request, Contact $contact)
    {
        $user = $request->user();

        if ($user->role !== 'owner' || $contact->owner_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'responded', 'closed'])],
        ]);

        $contact->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'data' => $contact->load(['seeker', 'owner', 'kost']),
        ]);
    }
}
