<?php

namespace App\Console\Commands;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Exceptions\Handler;
use Throwable;


class addTechnologyId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:technologyID';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds the technlogy ID to the database base on the technology iframe';

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
     * @throws Throwable
     */
    public function handle(Question $question)
    {
        $questions = $question
            ->where('technology','<>','text')
            ->where('technology_id',null)
            ->select('id', 'technology_iframe','technology')
            ->get();
        $domd = new \DOMDocument();
        foreach ($questions as $question){
            try {
                $technology_src = $question->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                switch ($question->technology) {
                    case('webwork'):
                        $technology_id = $question->getQueryParamFromSrc($technology_src, 'sourceFilePath');
                        break;
                    case('imathas'):
                        $technology_id = $question->getQueryParamFromSrc($technology_src, 'id');
                        break;
                    case('h5p'):
                        $technology_id = str_replace(['https://studio.libretexts.org/h5p/', '/embed'], '', $technology_src);
                        break;
                    default:
                        throw new Exception ("$question->technology does not exist");
                }
                $question->update(['technology_id' => $technology_id]);
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
                echo $e->getMessage();
            }
        }
        return 0;

    }
}
