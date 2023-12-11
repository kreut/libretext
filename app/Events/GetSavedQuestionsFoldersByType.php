<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

class GetSavedQuestionsFoldersByType implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $type;
    public $error_message;
    public $saved_questions_folders;
    /**
     * @var int
     */
    private $user_id;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $type, int $user_id, string $saved_questions_folders, string $error_message = '')
    {
        $this->user_id = $user_id;
        $this->type = $type;
        $this->saved_questions_folders = $saved_questions_folders;
        $this->error_message = $error_message;
    }


    public function broadcastOn(): array
    {
        return [new Channel("saved-questions-folders-$this->type-$this->user_id")];
    }
}
