<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a property owner when an admin approves or rejects their listing.
 * Dispatch from the Filament approve/reject actions:
 *   $property->user->notify(new ListingStatusChanged($property));
 */
class ListingStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Property $property)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontend = rtrim(config('app.frontend_url'), '/');
        $approved = $this->property->status === 'active';

        $mail = (new MailMessage)->greeting('Hello ' . $notifiable->name . ',');

        if ($approved) {
            return $mail
                ->subject('Your listing is now live')
                ->line('Great news — “' . $this->property->title . '” has been approved and is now live.')
                ->action('View Listing', $frontend . '/property/' . $this->property->slug);
        }

        return $mail
            ->subject('Update on your listing')
            ->line('Your listing “' . $this->property->title . '” was not approved.')
            ->when($this->property->rejection_reason, fn ($m) => $m->line('Reason: ' . $this->property->rejection_reason))
            ->line('You can edit and resubmit it from your dashboard.')
            ->action('Go to Dashboard', $frontend . '/dashboard');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'listing_status',
            'property_id' => $this->property->id,
            'status'      => $this->property->status,
        ];
    }
}
