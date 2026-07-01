<?php

namespace App\Jobs;

use App\Models\Property;
use App\Models\Requirement;
use App\Notifications\RequirementMatched;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * When a property becomes `active`, find every OPEN requirement whose buyer is
 * looking for the same transaction_type + category + city with a budget band
 * that contains the property's price, and notify those buyers.
 *
 * Dispatched from the admin approval flow (and the model observer) so matching
 * happens off the request cycle.
 */
class MatchRequirementsToProperty implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $propertyId)
    {
    }

    public function handle(): void
    {
        $property = Property::find($this->propertyId);

        // Only match listings that are actually live.
        if (! $property || $property->status !== 'active') {
            return;
        }

        Requirement::query()
            ->where('status', 'open')
            ->where('transaction_type', $property->transaction_type)
            ->where('category_id', $property->category_id)
            ->where('city_id', $property->city_id)
            // Budget band overlaps the price (null bound = open-ended).
            ->where(function ($q) use ($property) {
                $q->whereNull('min_budget')->orWhere('min_budget', '<=', $property->price);
            })
            ->where(function ($q) use ($property) {
                $q->whereNull('max_budget')->orWhere('max_budget', '>=', $property->price);
            })
            ->with('user')
            ->chunkById(200, function ($requirements) use ($property) {
                foreach ($requirements as $requirement) {
                    $this->notify($requirement, $property);
                }
            });
    }

    protected function notify(Requirement $requirement, Property $property): void
    {
        $notifiable = $requirement->user;

        if ($notifiable) {
            $notifiable->notify(new RequirementMatched($property, $requirement));
        } elseif ($requirement->email) {
            // Guest requirement — notify the bare email address on the fly.
            Notification::route('mail', $requirement->email)
                ->notify(new RequirementMatched($property, $requirement));
        }

        $requirement->forceFill(['last_matched_at' => now()])->save();
    }
}
