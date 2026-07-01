<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInquiryRequest;
use App\Http\Resources\InquiryResource;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Notifications\NewInquiryReceived;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class InquiryController extends Controller
{
    /**
     * POST /api/v1/inquiries  (public, rate-limited + honeypot)
     * Stores an inquiry and notifies the listing's agent/owner by mail+WhatsApp.
     */
    public function store(StoreInquiryRequest $request)
    {
        $property = Property::active()->findOrFail($request->integer('property_id'));

        $inquiry = $property->inquiries()->create([
            'name'       => $request->string('name'),
            'phone'      => $request->string('phone'),
            'email'      => $request->input('email'),
            'message'    => $request->input('message'),
            'status'     => 'new',
            'ip_address' => $request->ip(),
        ]);

        // Notify whoever should follow up: assigned agent first, else owner.
        $recipient = $property->agent ?: $property->user;
        if ($recipient) {
            $recipient->notify(new NewInquiryReceived($inquiry->loadMissing('property')));
        }

        return ApiResponse::success(
            new InquiryResource($inquiry),
            'Your inquiry has been sent. The agent will contact you shortly.',
            201
        );
    }

    /**
     * GET /api/v1/admin/inquiries  (auth: agent/admin)
     * Paginated list; agents see only inquiries for their listings.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PropertyInquiry::class);

        $query = PropertyInquiry::query()->with('property')->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        // Scope agents to their own listings.
        if ($request->user()->isAgent()) {
            $query->whereHas('property', function ($p) use ($request) {
                $p->where('user_id', $request->user()->id)
                  ->orWhere('agent_id', $request->user()->id);
            });
        }

        return ApiResponse::success(
            InquiryResource::collection($query->paginate(20)->withQueryString()),
            'Inquiries retrieved successfully.'
        );
    }

    /**
     * PATCH /api/v1/admin/inquiries/{inquiry}  (auth: agent/admin)
     * Update status: new | contacted | closed.
     */
    public function update(Request $request, PropertyInquiry $inquiry)
    {
        $this->authorize('update', $inquiry);

        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
        ]);

        $inquiry->update($validated);

        return ApiResponse::success(
            new InquiryResource($inquiry),
            'Inquiry status updated.'
        );
    }
}
