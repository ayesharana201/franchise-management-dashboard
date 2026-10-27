<?php

namespace App\Notifications;

use App\Models\RoyaltyStatement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoyaltyOverdue extends Notification
{
    public function __construct(public RoyaltyStatement $statement)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->statement;

        return (new MailMessage)
            ->subject("Overdue royalty statement for {$s->period->format('F Y')}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The royalty and fee statement for {$s->location->name} ({$s->period->format('F Y')}) is past due.")
            ->line('Amount due: $' . number_format((float) $s->total_due, 2))
            ->line("It was due on {$s->due_date->format('F j, Y')}.")
            ->action('View statements', url('/royalties'))
            ->line('Please arrange payment or contact head office if there is a problem.');
    }
}
