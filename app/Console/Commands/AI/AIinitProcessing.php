<?php

namespace App\Console\Commands\AI;

use App\Exceptions\Handler;
use App\RubricCategory;
use App\RubricCategorySubmission;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AIinitProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:initProcessing {type} {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        try {
            $type = $this->argument('type');
            switch ($type) {
                case('assignment'):
                    $assignment_id = $this->argument('id');
                    $rubric_category_submissions = DB::table('rubric_category_submissions')
                        ->join('rubric_categories', 'rubric_category_submissions.rubric_category_id', '=', 'rubric_categories.id')
                        ->where('assignment_id', $assignment_id)
                        ->select('rubric_category_submissions.*')
                        ->get();
                    break;
                case('single'):
                    $rubric_category_submission = RubricCategorySubmission::find($this->argument('id'));
                    $rubric_category_submissions = [$rubric_category_submission];
                    $assignment_id = $rubric_category_submission->assignment_id;
                    break;
                default:
                    echo "Not a valid type.";
                    return 1;
            }
            foreach ($rubric_category_submissions as $rubric_category_submission) {
                $rubricCategorySubmission = RubricCategorySubmission::find($rubric_category_submission->id);
                $rubricCategory = RubricCategory::find($rubricCategorySubmission->rubric_category_id);
                $rubricCategorySubmission->initProcessing($rubricCategory, $rubricCategorySubmission, $assignment_id, $rubricCategorySubmission->submission);
                $rubricCategorySubmission = RubricCategorySubmission::find($rubric_category_submission->id);
                echo $rubricCategorySubmission->status . ":" . $rubricCategorySubmission->message . "\r\n";
            }
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
        }
        return 1;
    }
}
