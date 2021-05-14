<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GraderNotification extends Model
{
    protected $guarded = [];

    /**
     * @param $where
     * @return string
     */
    public function submissionSQL($where): string
    {
        return <<<EOD
SELECT
    assignments.id as assignment_id,
    courses.id AS course_id,
    section_id,
    assignment_question.order,
    assignments.order AS assignment_order
FROM
    assign_to_users
INNER JOIN assign_to_timings ON(
        assign_to_users.assign_to_timing_id = assign_to_timings.id
    )
INNER JOIN submission_files ON(
        assign_to_users.user_id = submission_files.user_id AND assign_to_timings.assignment_id = submission_files.assignment_id
    )
        INNER JOIN assignment_question ON (
            submission_files.assignment_id = assignment_question.assignment_id AND submission_files.question_id = assignment_question.question_id
        )
INNER JOIN assignments ON(
        submission_files.assignment_id = assignments.id
    )
INNER JOIN enrollments ON(
        submission_files.user_id = enrollments.user_id AND assignments.course_id = enrollments.course_id
    )
INNER JOIN sections ON(
        enrollments.section_id = sections.id
    )
INNER JOIN courses ON(
    assignments.course_id = courses.id
)
WHERE
    $where
GROUP BY course_id, assignment_id, section_id, assignment_question.order
ORDER by assignment_order, assignment_question.order
EOD;
    }

    /**
     * @param $ungraded_submissions
     * @param Assignment $assignment
     * @return array
     */
    public function processUngradedSubmissions($ungraded_submissions, Assignment $assignment): array
    {
        $section_ids = [];
        $assignment_ids = [];
        foreach ($ungraded_submissions as $ungraded_submission) {
            $section_ids[] = $ungraded_submission->section_id;
            $assignment_ids[] = $ungraded_submission->assignment_id;
        }

        $assignments = $assignment->whereIn('id', $assignment_ids)
            ->select('name', 'id')
            ->get();
        $assignments_by_id = [];

        foreach ($assignments as $assignment) {
            $assignments_by_id[$assignment->id] = $assignment->name;
        }


        $graders = DB::table('graders')
            ->join('users', 'graders.user_id', '=', 'users.id')
            ->join('sections', 'graders.section_id', '=', 'sections.id')
            ->whereIn('section_id', $section_ids)
            ->select('first_name', 'last_name', 'email', 'users.id', 'section_id', 'course_id')
            ->get();
        $graders_by_id = [];
        $grader_sections_by_id = [];
        foreach ($graders as $grader) {
            $graders_by_id[$grader->id] = ['first_name' => $grader->first_name,
                'last_name' => $grader->last_name,
                'email' => $grader->email,
                'course_id' => $grader->course_id];
            if (!isset($grader_sections_by_id[$grader->id])) {
                $grader_sections_by_id[$grader->id] = [];
            }
            $grader_sections_by_id[$grader->id][] = $grader->section_id;
        }
        $formatted_ungraded_submissions_by_grader = [];
        $grader_array = [];

        //create the emails for the graders
        foreach ($ungraded_submissions as $ungraded_submission) {
            foreach ($graders_by_id as $grader_id => $grader) {
                if (in_array($ungraded_submission->section_id, $grader_sections_by_id[$grader_id])) {
                    if (!isset ($grader_array[$grader_id])) {
                        $grader_array[$grader_id] = [];
                    }
                    if (!isset ($formatted_ungraded_submissions_by_grader[$grader_id])) {
                        $formatted_ungraded_submissions_by_grader[$grader_id] = '';
                    }

                    $assignment_name = $assignments_by_id[$ungraded_submission->assignment_id];
                    $assignment_id = $ungraded_submission->assignment_id;
                    $order = $ungraded_submission->order;
                    if (!isset($grader_array[$grader_id][$ungraded_submission->assignment_id])) {
                        $grader_array[$grader_id][$ungraded_submission->assignment_id] = [
                            'assignment_name' => $assignment_name,
                            'assignment_id' => $assignment_id,
                            'questions_needing_grading' => []];

                    }
                    if (!in_array($order, $grader_array[$grader_id][$ungraded_submission->assignment_id]['questions_needing_grading'])) {
                        $grader_array[$grader_id][$ungraded_submission->assignment_id]['questions_needing_grading'][] = $order;
                    }

                }
            }
        }
        $app_url = config('app.url');
        foreach ($grader_array as $grader_id => $assignments) {
            if (!isset($formatted_ungraded_submissions_by_grader[$grader_id])) {
                $formatted_ungraded_submissions_by_grader[$grader_id] = '';
            }
            foreach ($assignments as $assignment) {
                $questions = implode(', ', $assignment['questions_needing_grading']);
                $formatted_ungraded_submissions_by_grader[$grader_id] .= "<li><a href='$app_url/assignments/{$assignment['assignment_id']}/grading'>{$assignment['assignment_name']}</a>: $questions</li>";
            }
        }
        return compact('formatted_ungraded_submissions_by_grader', 'graders_by_id');
    }

    public function sendReminder($grader, $formatted_ungraded_submissions_by_grader, $email_blade)
    {
        $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
        $to_email = $grader['email'];

        $grading_info = ['formatted_ungraded_submissions_by_grader' => $formatted_ungraded_submissions_by_grader,
            'first_name' => $grader['first_name']
        ];

        $beauty_mail->send($email_blade, $grading_info, function ($message)
        use ($to_email) {
            $message
                ->from('adapt@libretexts.org')
                ->to($to_email)
                ->subject("Grading Reminder");
        });
    }
}
