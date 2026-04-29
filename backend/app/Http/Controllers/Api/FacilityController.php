<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => Facility::query()->orderBy('name')->get(),
        ]);
    }
}
