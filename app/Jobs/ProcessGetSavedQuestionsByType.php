<?php

namespace App\Jobs;

use App\Events\GetSavedQuestionsFoldersByType;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\SavedQuestionsFolder;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use phpcent\Client;

class ProcessGetSavedQuestionsByType implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    private $type;
    private $withH5P;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $type, $withH5P)
    {
        $this->user = $user;
        $this->type = $type;
        $this->withH5P = $withH5P;

    }


    /**
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @return void
     * @throws Exception
     */
    public function handle(SavedQuestionsFolder $savedQuestionsFolder)
    {
        $client = Helper::centrifuge();
        try {
            $response = $savedQuestionsFolder->getSavedQuestionsFoldersByType($this->user, $this->type, $this->withH5P);
            $saved_questions_folders = json_encode($response['saved_questions_folders']);
            $client->publish("saved-questions-folders-my_questions-{$this->user->id}", [
                "type" => $this->type,
                "user_id"=> $this->user->id,
                "saved_questions_folders" => $saved_questions_folders,
                "error_message"=> '']);
        } catch (Exception $e) {
            $error_message = "There was an error retrieving your questions: {$e->getMessage()}";
            $client->publish("saved-questions-folders-my_questions-{$this->user->id}", [
                "type" => $this->type,
                "user_id"=> $this->user->id,
                "saved_questions_folders" => '',
                'error_message' => $error_message]);
            $h = new Handler(app());
            $h->report($e);
        }

    }
}
