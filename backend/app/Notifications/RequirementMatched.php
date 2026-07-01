<?php

namespace App\Notifications;

use App\Models\Property;
use App\Models\Requirement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a buyer whose open requirement matches a newly-active property.
 */
class RequirementMatched extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Property $property,
        public Requirement $requirement
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontend = rtrim(config('app.frontend_url'), '/');
        $name = $this->requirement->name ?: 'there';

        return (new MailMessage)
            ->subject('A new property matches what you are looking for')
            ->greeting('Hello ' . $name . ',')
            ->line('Good news — a new listing matches your requirement:')
            ->line('**' . $this->property->title . '**')
            ->line('Location: ' . optional($this->property->city)->name)
            ->action('View Property', $frontend . '/property/' . $this->property->slug)
            ->line('If this is not relevant, you can ignore this email.');
    }
}
