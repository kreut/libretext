<?php

namespace App\Console\Commands\OneTimers;

use App\AssignmentSyncQuestion;
use App\Question;
use App\QuestionRevision;
use App\RubricTemplate;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addRubricShownToRubrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:RubricShownToRubrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        try {
            DB::beginTransaction();
            $rubric_templates = RubricTemplate::get();
            echo count($rubric_templates) . "\r\n";
            foreach ($rubric_templates as $rubric_template) {
                $rubric = $this->_fixRubric($rubric_template->rubric);
                $rubric_template->rubric = $rubric;
                $rubric_template->save();
            }
            $question_revision_rubrics = QuestionRevision::whereNotNull('rubric')->get();
            echo count($question_revision_rubrics) . "\r\n";
            foreach ($question_revision_rubrics as $question_revision_rubric) {
                $rubric = $this->_fixRubric($question_revision_rubric->rubric);
                $question_revision_rubric->rubric = $rubric;
                $question_revision_rubric->save();
            }
            $question_rubrics = Question::whereNotNull('rubric')->get();
            foreach ($question_rubrics as $question_rubric) {
                $rubric = $this->_fixRubric($question_rubric->rubric);
                $question_rubric->rubric = $rubric;
                $question_rubric->save();
            }
            echo count($question_rubrics) . "\r\n";
            $assignment_question_rubrics = AssignmentSyncQuestion::whereNotNull('custom_rubric')->get();
            foreach ($assignment_question_rubrics as $assignment_question_rubric) {
                $rubric = $this->_fixRubric($assignment_question_rubric->custom_rubric);
                $assignment_question_rubric->custom_rubric = $rubric;
                $assignment_question_rubric->save();
            }
            echo count($assignment_question_rubrics) . "\r\n";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }

    private function _fixRubric($rubric_to_fix)
    {
        $rubric = json_decode($rubric_to_fix, 1);
        $rubric['rubric_shown'] = true;
        return json_encode($rubric);

    }

}
