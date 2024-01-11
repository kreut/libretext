<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SetCurrentPage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $assignment_id;
    public $question_id;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($assignment_id, $question_id)
    {

        $this->assignment_id = $assignment_id;
        $this->question_id = $question_id;


    }

    public function broadcastOn(): array
    {
        /** public vs private needs to be looked at */
        return [
            new Channel("set-current-page-$this->assignment_id"),
        ];

    }

}
