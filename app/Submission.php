<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class Submission extends Model
{
    protected $fillable = ['user_id', 'submission', 'assignment_id', 'question_id', 'score'];

    public function getSubmissionsCountByAssignmentIdsAndUser(Collection $assignment_ids, User $user)
    {
        $submissions_count_by_assignment_id = [];
        $submissions_count = DB::table('submissions')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->groupBy('assignment_id')
            ->select(DB::raw('count(*) as num_submissions'), 'assignment_id')
            ->get();
        //reoorganize by assignment id
        foreach ($submissions_count as $key => $value) {
            $submissions_count_by_assignment_id[$value->assignment_id] = $value->num_submissions;
        }
        return $submissions_count_by_assignment_id;
    }



    public function getNumberOfUserSubmissionsByCourse($course, $user)
    {
        $AssignmentSyncQuestion = new AssignmentSyncQuestion();
        $num_sumbissions_per_assignment = [];
        $assignment_ids = $course->assignments()->pluck('id');

        if ($assignment_ids->isNotEmpty()) {
            $questions_count_by_assignment_id = $AssignmentSyncQuestion->getQuestionCountByAssignmentIds($assignment_ids);


            $submissions_count_by_assignment_id = $this->getSubmissionsCountByAssignmentIdsAndUser($assignment_ids, $user);
            //set to 0 if there are no questions
            foreach ($assignment_ids as $assignment_id) {
                $num_questions = $questions_count_by_assignment_id[$assignment_id] ?? 0;
                $num_submissions = $submissions_count_by_assignment_id[$assignment_id] ?? 0;

                $num_sumbissions_per_assignment[$assignment_id] = ($num_questions === 0) ? "No questions" : "$num_submissions/$num_questions";
            }
        }
      return $num_sumbissions_per_assignment;


    }
}
