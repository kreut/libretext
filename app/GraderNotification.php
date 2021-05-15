<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GraderNotification extends Model
{
    protected $guarded = [];

    public function submissionSQL($where)
    {

        return <<<EOD
SELECT
    submission_files.assignment_id,
    submission_files.user_id,
    question_id,
    assignments.id,
    assignments.name AS assignment_name,
    courses.name AS course_name,
    courses.id AS course_id,
    section_id,
    sections.name AS section_name
FROM
    assign_to_users
INNER JOIN assign_to_timings ON(
        assign_to_users.assign_to_timing_id = assign_to_timings.id
    )
INNER JOIN submission_files ON(
        assign_to_users.user_id = submission_files.user_id AND assign_to_timings.assignment_id = submission_files.assignment_id
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
EOD;
    }


    public function gradersInfo($section_ids)
    {
        $graders_info = DB::table('graders')
            ->join('users', 'graders.user_id', '=', 'users.id')
            ->join('sections', 'graders.section_id', '=', 'sections.id')
            ->whereIn('section_id', $section_ids)
            ->select('users.id AS user_id', 'first_name', 'email', 'sections.name AS section_name', 'sections.id AS section_id')
            ->get();
        $graders_by_id = [];
        foreach ($graders_info as $grader_info) {
            if (!isset($graders_by_id[$grader_info->user_id])) {
                $graders_by_id[$grader_info->user_id] = ['first_name' => $grader_info->first_name, 'email' => $grader_info->email];
                $graders_by_id[$grader_info->user_id]['section_ids'] = [];
                $grader_ids[] = $grader_info->user_id;
            }
            $graders_by_id[$grader_info->user_id]['section_ids'][] = $grader_info->section_id;

        }
        return $graders_by_id;
    }

    public function submissionsBySection($submissions)
    {
        $submissions_by_section = [];
        $section_ids = [];
        $course_ids =[];
        foreach ($submissions as $submission) {
            if (!in_array($submission->course_id, $course_ids)){
                $course_ids[] = $submission->course_id;
            }
            if (!isset($submissions_by_section[$submission->section_id])) {
                $submissions_by_section[$submission->section_id] = [];
                $section_ids[] = $submission->section_id;
            }
            if (!in_array($submission, $submissions_by_section[$submission->section_id])) {
                $submissions_by_section[$submission->section_id][] = $submission;
            }

        }
        return compact('submissions_by_section', 'section_ids', 'course_ids');

    }
}
