<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequirementRequest;
use App\Http\Resources\RequirementResource;
use App\Models\Requirement;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class RequirementController extends Controller
{
    /**
     * GET /api/v1/requirements  (public)
     * Open buyer requirements, newest first — powers the "buyers looking"
     * wall and lets agents browse demand.
     */
    public function index(Request $request)
    {
        $requirements = Requirement::query()
            ->where('status', 'open')
            ->with(['category', 'city'])
            ->latest()
            ->paginate(min((int) $request->integer('per_page', 12), 48))
            ->withQueryString();

        return ApiResponse::success(
            RequirementResource::collection($requirements),
            'Requirements retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/requirements  (public, rate-limited + honeypot)
     * "Didn't find a property?" form. Links to the logged-in user if present.
     */
    public function store(StoreRequirementRequest $request)
    {
        $requirement = Requirement::create([
            ...$request->validated(),
            'user_id' => $request->user()?->id,
            'status'  => 'open',
        ]);

        return ApiResponse::success(
            new RequirementResource($requirement->load(['category', 'city'])),
            'Your requirement has been posted. We will alert you on matches.',
            201
        );
    }
}
