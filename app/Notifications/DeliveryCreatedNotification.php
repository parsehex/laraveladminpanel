<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Delivery $delivery) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $customer = $this->delivery->customer_name;
        $type = $this->delivery->delivery_type;
        $timeframe = $this->delivery->delivery_timeframe ?: 'unspecified timeframe';

        return [
            'title' => 'New delivery',
            'message' => "{$customer} — {$type} ({$timeframe})",
            'url' => route('admin.deliveries.index', ['status' => 'active']),
            'delivery_id' => $this->delivery->id,
        ];
    }
}
