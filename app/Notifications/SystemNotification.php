<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'       => $this->payload['title']       ?? 'Notification',
            'message'     => $this->payload['message']     ?? '',
            'type'        => $this->payload['type']        ?? 'general',
            'severity'    => $this->payload['severity']    ?? 'info',
            'action_url'  => $this->payload['action_url']  ?? null,
            'action_text' => $this->payload['action_text'] ?? 'View',
            'stage'       => $this->payload['stage']       ?? null,
            'appointment_id' => $this->payload['appointment_id'] ?? null,
            'appointment_date' => $this->payload['appointment_date'] ?? null,
            'appointment_time' => $this->payload['appointment_time'] ?? null,
        ];
    }
}