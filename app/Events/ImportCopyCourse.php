<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

class ImportCopyCourse implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var int
     */
    public $user_id;
    public $type;
    public $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $user_id, string $type, string $message)
    {
        $this->user_id = $user_id;
        $this->type = $type;
        $this->message = $message;
    }


    public function broadcastOn(): array
    {
        return [new Channel("import-copy-course-$this->user_id")];
    }
}
