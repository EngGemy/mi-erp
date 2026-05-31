<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class InventoryDatabaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?Model $subject = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title'          => $this->title,
            'body'           => $this->body,
            'url'            => $this->url,
            'subject_type'   => $this->subject?->getMorphClass(),
            'subject_id'     => $this->subject?->getKey(),
        ];
    }
}
