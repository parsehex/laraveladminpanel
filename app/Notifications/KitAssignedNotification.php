<?php

namespace App\Notifications;

use App\Models\KitAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KitAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public KitAssignment $assignment) {}

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
        $kit = $this->assignment->kit;
        $code = $kit?->code ?? 'Kit';
        $name = $kit?->name;
        $label = $name ? "{$code} ({$name})" : $code;
        $due = $this->assignment->due_date?->format('M j, Y') ?? 'no due date';
        $platform = ucfirst((string) $this->assignment->platform);

        return [
            'title' => 'New kit assignment',
            'message' => "{$this->assignment->quantity} × {$label} — {$platform}, due {$due}",
            'url' => route('admin.kits.index', ['assign' => $this->assignment->id]),
            'assignment_id' => $this->assignment->id,
        ];
    }
}
