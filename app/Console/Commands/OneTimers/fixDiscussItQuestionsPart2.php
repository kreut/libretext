<?php

namespace App\Console\Commands\OneTimers;

use App\AssignmentSyncQuestion;
use App\Discussion;
use App\Question;
use App\QuestionMediaUpload;
use App\QuestionRevision;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixDiscussItQuestionsPart2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:discussItQuestionsPart2';

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
        $this->info('starting');

        try {
            DB::beginTransaction();
            $assignment_questions = AssignmentSyncQuestion::whereIn('question_id', function ($query) {
                $query->select('id')
                    ->from('questions')
                    ->where('qti_json_type', 'discuss_it');
            })
                ->orderBy('updated_at', 'DESC')
                ->get();
            $assignment_questions_with_discussions = [];
            foreach ($assignment_questions as $assignment_question) {
                $assignment_id = $assignment_question->assignment_id;
                $question_id = $assignment_question->question_id;
                $discussions = Discussion::where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->exists();
                if ($discussions) {
                    $assignment_questions_with_discussions[] = [
                        'assignment_id' => $assignment_id,
                        'question_id' => $question_id
                    ];
                }
            }
            $info_by_instructor = [];
            foreach ($assignment_questions_with_discussions as $assignment_question) {
                $assignment_id = $assignment_question['assignment_id'];
                $question_id = $assignment_question['question_id'];
                $assignment_question = AssignmentSyncQuestion::where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->first();
                $question_revision_id = $assignment_question->question_revision_id;
                if ($question_revision_id) {
                    $assignment_info = DB::table('assignments')
                        ->join('courses', 'assignments.course_id', 'courses.id')
                        ->join('users', 'courses.user_id', 'users.id')
                        ->where('assignments.id', $assignment_id)
                        ->select(DB::raw('CONCAT(first_name, " " , last_name) AS instructor_name'),
                            'assignments.name AS assignment_name', 'courses.name AS course_name')
                        ->first();
                    $assignment_info->question_id = $question_id;
                    if (!isset($info_by_instructor[$assignment_info->instructor_name])) {
                        $info_by_instructor[$assignment_info->instructor_name] = [];
                    }
                    if (!isset($info_by_instructor[$assignment_info->instructor_name][$assignment_info->course_name])) {
                        $info_by_instructor[$assignment_info->instructor_name][$assignment_info->course_name] = [];
                    }
                    if (!isset($info_by_instructor[$assignment_info->instructor_name][$assignment_info->course_name][$assignment_info->assignment_name])) {
                        $info_by_instructor[$assignment_info->instructor_name][$assignment_info->course_name][$assignment_info->assignment_name] = [];
                    }

                    $discussions = Discussion::where('assignment_id', $assignment_id)
                        ->where('question_id', $question_id)
                        ->get();
                    if ($discussions) {
                        $info_by_instructor[$assignment_info->instructor_name][$assignment_info->course_name][$assignment_info->assignment_name][] = $question_id;
                        foreach ($discussions as $discussion) {
                            $incorrect_questionMediaUpload = QuestionMediaUpload::find($discussion->media_upload_id);
                            $s3_key = $incorrect_questionMediaUpload->s3_key;
                            $correct_questionMediaUpload = QuestionMediaUpload::where('question_id', $question_id)
                                ->where('s3_key', $s3_key)
                                ->where('question_revision_id', $assignment_question->question_revision_id)
                                ->first();
                            $discussion->media_upload_id = $correct_questionMediaUpload->id;
                            DB::table('old_discussion_media_uploads')->insert(['discussion_id' => $discussion->id,
                                'question_media_upload_id' => $incorrect_questionMediaUpload->id]);
                            $this->info('updating discussion');
                            $discussion->save();
                        }
                    }
                }
            }
            DB::commit();
            $this->info('worked');
        } catch (Exception $e) {
            DB::rollback();
            $this->info($e->getMessage());
        }
        $this->info('done');
        return 0;
    }
}
