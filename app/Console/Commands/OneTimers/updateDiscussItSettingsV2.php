<?php

namespace App\Console\Commands\OneTimers;

use App\AssignmentSyncQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class updateDiscussItSettingsV2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:discussItSettingsV2';

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
            $assignment_ids = array_values(array_unique($assignment_ids));
            $assignment_users = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->whereIn('assignments.id', $assignment_ids)
                ->select('assignments.id AS assignment_id', 'user_id')
                ->get();
            $users_by_assignment_id = [];
            foreach ($assignment_users as $assignment) {
                $users_by_assignment_id[$assignment->assignment_id] = $assignment->user_id;
            }

            DB::beginTransaction();
            foreach ($assignment_questions as $assignment_question) {
                echo $assignment_question->id . "\r\n";
                $discuss_it_settings = json_decode($assignment_question->discuss_it_settings);
                $discuss_it_settings->min_number_of_initiated_discussion_threads = "0";
                $discuss_it_settings->min_number_of_replies = "0";
                $discuss_it_settings->min_number_of_initiate_or_reply_in_threads = $discuss_it_settings->min_number_of_discussion_threads;
                unset($discuss_it_settings->min_number_of_discussion_threads);
                $assignment_question->discuss_it_settings = json_encode($discuss_it_settings);
                $assignment_question->save();
                $user_id = $users_by_assignment_id[$assignment_question->assignment_id];
                $cache_key = "discuss_it_settings_$user_id";
                if (Cache::has($cache_key)) {
                    Cache::forget($cache_key);
                }
            }
            ///delete the cache
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
