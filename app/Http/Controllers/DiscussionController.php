<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Discussion;
use App\DiscussionComment;
use App\Exceptions\Handler;
use App\Http\Requests\StoreDiscussionRequest;
use App\Jobs\ProcessTranscribe;
use App\Question;
use App\QuestionMediaUpload;
use App\Score;
use App\Submission;
use App\SubmissionFile;
use App\User;
use Carbon\Carbon;
use DOMDocument;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class DiscussionController extends Controller
{


    /**
     * @param StoreDiscussionRequest $request
     * @param Assignment $assignment
     * @param Question $question
     * @param string $media_upload_id
     * @param int $discussion_id
     * @param Discussion $discussion
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Submission $submission
     * @param SubmissionFile $submissionFile
     * @param Score $score
     * @return array
     * @throws Exception
     */
    public function store(StoreDiscussionRequest $request,
                          Assignment             $assignment,
                          Question               $question,
                          string                 $media_upload_id,
                          int                    $discussion_id,
                          Discussion             $discussion,
                          AssignmentSyncQuestion $assignmentSyncQuestion,
                          Submission             $submission,
                          SubmissionFile         $submissionFile,
                          Score                  $score
    ): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('store', [$discussion, $assignment, $question]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $data = $request->validated();
            $type = $request->type;
            DB::beginTransaction();
            if (!$discussion_id) {
                $discussion = new Discussion();
                $discussion->assignment_id = $assignment->id;
                $discussion->question_id = $question->id;
                $discussion->user_id = $request->user()->id;
                $discussion->media_upload_id = $media_upload_id;
                $discussion->save();
                $message = 'You have started a new discussion.';
            } else {
                $discussion = Discussion::find($discussion_id);
                $message = 'Thank you for adding your comment!';
            }
            $discuss_it_settings = json_decode($assignmentSyncQuestion->discussItSettings($assignment->id, $question->id));
            $discussionComment = new DiscussionComment();
            $discussionComment->discussion_id = $discussion->id;
            $discussionComment->user_id = $request->user()->id;
            $discussionComment->{$type} = $data[$type];

            $satisfied_requirement = $discussionComment->satisfiedRequirement($type, $discuss_it_settings, $request);

            $discussionComment->satisfied_requirement = $satisfied_requirement;
            $discussionComment->save();
            if ($type === 'file') {
                ProcessTranscribe::dispatch($data['file'], 'discussion_comment');
            }


            $discussion_comment_submission_results = $discussionComment->satisfiedRequirements($assignment, $question->id, $request->user()->id, $discussion, $assignmentSyncQuestion);
            $discussionComment->updateScore($discuss_it_settings,
                $discussion_comment_submission_results,
                $assignmentSyncQuestion,
                $request->user()->id,
                $assignment,
                $question,
                $submission,
                $submissionFile,
                $score);

            DB::commit();
            $response['type'] = 'success';

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating this new discussion.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param string $media_upload_id
     * @param Discussion $discussion
     * @return array
     * @throws Exception
     */
    public
    function getByAssignmentQuestionMediaUploadId(
        Assignment $assignment,
        Question   $question,
        string     $media_upload_id,
        Discussion $discussion): array
    {
        try {
            $response['type'] = 'error';
            $page = 1;

            $discussions = $discussion->getByAssignmentQuestionMediaUploadId($assignment, $question, $media_upload_id)['discussions'];

            $response['page'] = $page;
            $response['discussions'] = $discussions;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your discussions.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param Discussion $discussion
     * @param User $user
     * @return array
     * @throws Exception
     */
    public
    function show(Assignment $assignment,
                  Question   $question,
                  Discussion $discussion,
                  User       $user): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('show', [$discussion, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $enrolled_student_ids = $assignment->course->enrolledUsersWithFakeStudent->pluck('id')->toArray();
            $enrolled_students = $user->whereIn('id', $enrolled_student_ids)->get();
            $discussions = $discussion->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->whereIn('user_id', $enrolled_students)
                ->get();
            //foreach ($enrolled_students )
            $response['discussions'] = $discussions;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the discussions.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
