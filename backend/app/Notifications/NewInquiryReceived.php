<?php

namespace App\Notifications;

use App\Models\PropertyInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the listing's agent/owner when a buyer submits an inquiry.
 * Queued so the public request returns immediately.
 */
class NewInquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PropertyInquiry $inquiry)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->inquiry->property;
        $frontend = rtrim(config('app.frontend_url'), '/');

        return (new MailMessage)
            ->subject('New inquiry: ' . $property->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have received a new inquiry on your listing **' . $property->title . '**.')
            ->line('Name: ' . $this->inquiry->name)
            ->line('Phone: ' . $this->inquiry->phone)
            ->when($this->inquiry->email, fn ($m) => $m->line('Email: ' . $this->inquiry->email))
            ->when($this->inquiry->message, fn ($m) => $m->line('Message: ' . $this->inquiry->message))
            ->action('View Listing', $frontend . '/property/' . $property->slug)
            ->line('Please follow up promptly.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'inquiry',
            'inquiry_id'  => $this->inquiry->id,
            'property_id' => $this->inquiry->property_id,
            'name'        => $this->inquiry->name,
            'phone'       => $this->inquiry->phone,
        ];
    }
}
