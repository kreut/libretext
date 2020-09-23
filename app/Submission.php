<?php

namespace App;

use App\Exceptions\Handler;
use App\Http\Requests\StoreSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

use App\Traits\DateFormatter;

class Submission extends Model
{
    use DateFormatter;

    protected $fillable = ['user_id', 'submission', 'assignment_id', 'question_id', 'score'];


    public function store(StoreSubmission $request, Submission $submission, Assignment $Assignment, Score $score)
    {

        $response['type'] = 'error';//using an alert instead of a noty because it wasn't working with post message

        // $data = $request->validated();//TODO: validate here!!!!!
        // $data = $request->all(); ///maybe request->all() flag in the model or let it equal request???
       // Log::info(print_r($request->all(), true));


        $data = $request;
        $data['user_id'] = Auth::user()->id;
        $assignment = $Assignment->find($data['assignment_id']);

        $assignment_question = DB::table('assignment_question')->where('assignment_id', $assignment->id)
            ->where('question_id', $data['question_id'])
            ->select('points')
            ->first();

        if (!$assignment_question) {
            $response['message'] = 'That question is not part of the assignment.';
            return $response;

        }

        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment->id, $data['question_id']]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        if (env('DB_DATABASE') === 'test_libretext') {
            $data['score'] = $assignment->default_points_per_question;
        } else {

            switch ($data['technology']) {
                case('h5p'):
                    $submission = json_decode($data['submission']);
                    $data['score'] = floatval($assignment_question->points) * (floatval($submission->result->score->raw) / floatval($submission->result->score->max));
                    break;
                case('imathas'):
                    $submission = $data['submission'];
                    $data['score'] = floatval($submission->score);
                    $data['submission'] = json_encode($data['submission'], JSON_UNESCAPED_SLASHES);
                    break;
                case('webwork'):
                   // Log::info('case webwork');
                    $submission = $data['submission'];
                    $data['score'] = 0;
                    $num_questions = 0;
                    foreach ($submission->score as $value) {
                        $data['score'] = $data['score'] + floatval($value->score);
                        $num_questions++;
                    }

                   // Log::info($num_questions);
                    $data['score'] = $num_questions
                        ? floatval($assignment_question->points) * floatval($data['score'] / $num_questions)
                        : 0;
                    $data['submission'] = json_encode($data['submission']);
                    break;
                default:
                    $response['message'] = 'That is not a valid technology.';
                    break;
            }

        }


        try {

            //do the extension stuff also

            $submission = Submission::where('user_id', '=', $data['user_id'])
                ->where('assignment_id', '=', $data['assignment_id'])
                ->where('question_id', '=', $data['question_id'])
                ->first();

            if ($submission) {

                $submission->submission = $data['submission'];

                $submission->score = $data['score'];
                $submission->save();

            } else {
                Submission::create(['user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'question_id' => $data['question_id'],
                    'submission' => $data['submission'],
                    'score' => $data['score']]);

            }

            //update the score if it's supposed to be updated
            switch ($assignment->scoring_type) {
                case 'c':
                    $num_submissions_by_assignment = DB::table('submissions')
                        ->where('user_id', $data['user_id'])
                        ->where('assignment_id', $assignment->id)
                        ->count();
                    if ((int)$num_submissions_by_assignment === count($assignment->questions)) {
                        Score::firstOrCreate(['user_id' => $data['user_id'],
                            'assignment_id' => $assignment->id,
                            'score' => 'C']);
                        $response['message'] = "Your assignment has been marked completed.";
                    }
                    $response['type'] = 'success';
                    break;
                case 'p':
                    $score->updateAssignmentScore($data['user_id'], $assignment->id, $assignment->submission_files);
                    $response['type'] = 'success';
                    break;
            }

            $response['message'] = 'Your question submission was saved.';
            $response['last_submitted'] = 'The date of your last submission was ' . $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date("Y-m-d H:i:s"), Auth::user()->time_zone);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving your response.  Please try again or contact us for assistance.";
        }

        return $response;

    }

    public function getSubmissionDatesByAssignmentIdAndUser($assignment_id, User $user)
    {
        $last_submitted_by_user = [];
        $submissions = DB::table('submissions')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', $user->id)
            ->select('updated_at', 'question_id')
            ->get();

        foreach ($submissions as $key => $value) {
            $last_submitted_by_user[$value->question_id] = $value->updated_at;
        }

        return $last_submitted_by_user;
    }

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
