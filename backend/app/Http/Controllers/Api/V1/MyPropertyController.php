<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * "My listings" dashboard for the authenticated owner/agent — returns their
 * properties in EVERY status (pending, active, sold, rejected …), unlike the
 * public index which only shows active.
 */
class MyPropertyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Property::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('agent_id', $user->id);
            })
            ->with(['city', 'category', 'images'])
            ->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return ApiResponse::success(
            PropertyResource::collection($query->paginate(12)->withQueryString()),
            'Your properties retrieved.'
        );
    }
}
