<?php

namespace App\Console\Commands\OneTimers;

use App\Exceptions\Handler;
use App\Libretext;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class updatePrivateTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:PrivateTitles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the actual titles for the private ones';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $questions_with_private_titles = Question::whereIn('title',['None provided.','Private title: contact us'])->get();
        $results = count($questions_with_private_titles) . " with private titles\r\n";
        foreach ($questions_with_private_titles as $key => $private_question){
            try {
                $Libretext = new Libretext(['library' =>  $private_question->library]);
                $contents = $Libretext->getPrivatePage('contents', $private_question->page_id);
                $attribute = '@title';
                $private_question->title = $contents->$attribute;

                $results .= $key + 1 . ' ' .$private_question->library . ' ' . $private_question->page_id  ." was updated.\r\n";
            } catch (Exception $e) {
                $message = $e->getMessage();
                $private_question->title = 'None provided.';
               $results .= $key + 1 . ' ' . $private_question->library . ' ' . $private_question->page_id . ": $message .\r\n";
            }
            $private_question->save();
        }
        Storage::disk('s3')->put("update_private_titles.txt", $results);
        return 0;
    }
}
