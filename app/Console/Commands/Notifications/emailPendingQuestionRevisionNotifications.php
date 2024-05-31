<?php

namespace App\Console\Commands\Notifications;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\PendingQuestionRevision;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class emailPendingQuestionRevisionNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:pendingQuestionRevisionNotifications';

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
     * @param PendingQuestionRevision $pendingQuestionRevision
     * @return int
     * @throws Exception
     */
    public function handle(PendingQuestionRevision $pendingQuestionRevision): int
    {
        try {

            $startTime = Carbon::now()->subDay(); // Get the current time minus 24 hours

            $pending_question_revisions =
                DB::table('pending_question_revisions')
                    ->join('assignments', 'pending_question_revisions.assignment_id', '=', 'assignments.id')
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->join('users', 'courses.user_id', '=', 'users.id')
                    ->whereBetween('pending_question_revisions.created_at', [$startTime, Carbon::now()])
                    ->select('users.email',
                        'users.id AS user_id',
                        'users.first_name',
                        'courses.name AS course_name',
                        'assignments.name AS assignment_name',
                        'assignments.id AS assignment_id',
                        'question_id')
                    ->get();
            $pending_question_revisions_to_email = [];
            foreach ($pending_question_revisions as $pending_revision) {
                if (!isset($pending_question_revisions_to_email[$pending_revision->user_id])) {
                    $pending_question_revisions_to_email[$pending_revision->user_id] = [
                        'email' => $pending_revision->email,
                        'first_name' => $pending_revision->first_name,
                        'pending_question_revisions' => []];
                }
                $schema_and_host = Helper::schemaAndHost();
                $pending_question_revisions_to_email[$pending_revision->user_id]['pending_question_revisions'][]
                    = ['course_name' => $pending_revision->course_name,
                    'assignment_name' => $pending_revision->assignment_name,
                    'assignment_id' => $pending_revision->assignment_id,
                    'question_id' => $pending_revision->question_id,
                    'url' => $schema_and_host . "assignments/$pending_revision->assignment_id/questions/view/$pending_revision->question_id"];
            }

            $pending_question_revisions_to_email = array_values($pending_question_revisions_to_email);

            $pendingQuestionRevision->email($pending_question_revisions_to_email);
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
        }

        return 1;

    }
}
