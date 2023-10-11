<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ClickerStatus implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $status;
    public $assignment_id;
    public $question_id;
    public $time_left;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($assignment_id, $question_id, $status, $time_left = 0)
    {
        $this->status = $status;
        $this->assignment_id = $assignment_id;
        $this->question_id = $question_id;
        $this->time_left = $time_left;

    }

    public function broadcastOn(): array
    {
        /** public vs private needs to be looked at */
        return [
            new Channel("clicker-status-$this->assignment_id"),
        ];

    }

}
