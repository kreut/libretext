<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Snowfire\Beautymail\Beautymail;

class PendingQuestionRevision extends Model
{
    protected $guarded = [];

    /**
     * @param Assignment $assignment
     * @return array
     */
    public function getCurrentOrUpcomingByAssignment(Assignment $assignment): array
    {
        $pending_question_revisions_by_question_id = [];
        $now = Carbon::now();
        $current_or_upcoming = DB::table('assign_to_timings')
            ->where('assignment_id', $assignment->id)
            ->where(function ($query) use ($now) {
                $query->where('due', '>', $now)
                    ->orWhere('final_submission_deadline', '>', $now);
            })
            ->orderBy('created_at')
            ->first();
        if ($current_or_upcoming || $assignment->formative) {
            $pending_question_revision_ids_by_assignment = DB::table('pending_question_revisions')
                ->where('assignment_id', $assignment->id)
                ->select('question_revision_id')
                ->pluck('question_revision_id')
                ->toArray();
            if ($pending_question_revision_ids_by_assignment) {
                $question_revisions = DB::table('question_revisions')
                    ->whereIn('id', $pending_question_revision_ids_by_assignment)
                    ->get();
                $question_editor_user_ids = [];
                foreach ($question_revisions as $question_revision) {
                    $question_editor_user_ids[] = $question_revision->question_editor_user_id;
                }
                $question_editor_names = DB::table('users')
                    ->whereIn('id', $question_editor_user_ids)
                    ->select('id AS question_editor_user_id', DB::raw('CONCAT(first_name, " " , last_name) AS question_editor_name'))
                    ->get();
                $question_editor_names_by_question_editor_user_id = [];
                foreach ($question_editor_names as $question_editor_name) {
                    $question_editor_names_by_question_editor_user_id[$question_editor_name->question_editor_user_id] = $question_editor_name->question_editor_name;
                }
                foreach ($question_revisions as $question_revision) {
                    $rubric_categories = DB::table('rubric_categories')
                        ->where('question_revision_id', $question_revision->id)
                        ->get()
                        ->toArray();
                    $question_revision->rubric_categories = $rubric_categories;
                    $question_revision->question_editor_name = $question_editor_names_by_question_editor_user_id[$question_revision->question_editor_user_id] ?? 'N/A';
                    $pending_question_revisions_by_question_id[$question_revision->question_id] = $question_revision;
                }
            }
        }

        return $pending_question_revisions_by_question_id;
    }

    public function email(array $pending_question_revisions_to_email)
    {
        foreach ($pending_question_revisions_to_email as $pending_question_revision) {
            $beauty_mail = app()->make(Beautymail::class);

            $email_info = ['pending_question_revisions' => $pending_question_revision['pending_question_revisions'],
                'first_name' => $pending_question_revision['first_name']
            ];
            $to_email = $pending_question_revision['email'];
            $beauty_mail->send('emails.pending_question_revisions', $email_info, function ($message)
            use ($to_email) {
                $message
                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
                    ->to($to_email)
                    ->subject('Pending Question Revisions');
            });


        }

    }
}
