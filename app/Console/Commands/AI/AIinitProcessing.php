<?php

namespace App\Console\Commands\AI;

use App\Exceptions\Handler;
use App\RubricCategory;
use App\RubricCategorySubmission;
use Exception;
use Illuminate\Console\Command;

class AIinitProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:initProcessing {rubric_category_submission_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        try {
            $rubric_category_submission_id = $this->argument('rubric_category_submission_id');
            $rubricCategorySubmission = RubricCategorySubmission::find($rubric_category_submission_id);
            $rubricCategory = RubricCategory::find($rubricCategorySubmission->rubric_category_id);
             $rubricCategorySubmission->initProcessing($rubricCategory, $rubricCategorySubmission, $rubricCategorySubmission->submission);
            $rubricCategorySubmission = RubricCategorySubmission::find($rubric_category_submission_id);
            echo $rubricCategorySubmission->status .":" .$rubricCategorySubmission->message . "\r\n";
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
