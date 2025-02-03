<?php

namespace App\Console\Commands\OneTimers;

use App\AssignmentSyncQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class updateDiscussItWithCompletionCriteria extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:discussItWithCompletionCriteria';

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
            $assignment_questions = AssignmentSyncQuestion::whereNotNull('discuss_it_settings')->get();
            $assignment_ids = [];
            foreach ($assignment_questions as $assignment_question) {
                $assignment_ids[] = $assignment_question->assignment_id;
            }
            $assignment_ids = array_unique($assignment_ids);
            $users = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->whereIn('assignments.id', $assignment_ids)
                ->select('assignments.id AS assignment_id', 'courses.user_id')
                ->get();
            $assignment_users = [];
            foreach ($users as $user) {
                $assignment_users[$user->assignment_id] = $user->user_id;
            }

            DB::beginTransaction();
            $lemari_assignments = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->where('courses.user_id', 7420)
                ->select('assignments.id')
                ->pluck('id')
                ->toArray();

            foreach ($assignment_questions as $assignment_question) {
                $discuss_it_settings = json_decode($assignment_question->discuss_it_settings);
                $discuss_it_settings->completion_criteria = in_array($assignment_question->assignment_id,$lemari_assignments) ? "0" : "1";
                $assignment_question->discuss_it_settings = json_encode($discuss_it_settings);
                $assignment_question->save();
                Cache::put("discuss_it_settings_{$assignment_users[$assignment_question->assignment_id]}", $assignment_question->discuss_it_settings);
            }

            DB::commit();
            echo "Done!";
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
