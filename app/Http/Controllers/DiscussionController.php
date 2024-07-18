<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Discussion;
use App\DiscussionComment;
use App\Exceptions\Handler;
use App\Http\Requests\StoreDiscussionRequest;
use App\Question;
use App\QuestionMediaUpload;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DiscussionController extends Controller
{


    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param string $media_upload_id
     * @return Application|Factory|View]
     */
    public function mediaPlayer(Assignment $assignment, Question $question, string $media_upload_id)
    {
        $s3_key = '';
        $media_uploads = json_decode($question->qti_json)->mediaUploads;
        foreach ($media_uploads as $media_upload) {
            if ($media_upload->id === $media_upload_id) {
                $s3_key = $media_upload->s3_key;
            }
        }
        if (!$s3_key) {
            return view('media_player_error', ['message' => "$media_upload_id is not a valid ID for a discussion upload."]);
        }
        //$questionMediaUpload = new QuestionMediaUpload();
        //$vtt_file = $questionMediaUpload->getVttFileNameFromS3Key($media);
        $type = strpos($s3_key, '.mp3') !== false ? 'audio' : 'video';
        $temporary_url = Storage::disk('s3')->temporaryUrl($s3_key, Carbon::now()->addDays(7));
        // $vtt_file = Storage::disk('s3')->temporaryUrl("{$questionMediaUpload->getDir()}/$vtt_file", Carbon::now()->addDays(7));
        return view('media_player', ['type' => $type, 'temporary_url' => $temporary_url, 'vtt_file' => '', 'start_time' => 0]);
    }

    /**
     * @param StoreDiscussionRequest $request
     * @param Assignment $assignment
     * @param Question $question
     * @param string $media_upload_id
     * @param int $discussion_id
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public function store(StoreDiscussionRequest $request,
                          Assignment             $assignment,
                          Question               $question,
                          string                 $media_upload_id,
                          int                    $discussion_id,
                          AssignmentSyncQuestion $assignmentSyncQuestion

    ): array
    {

        try {
            $response['type'] = 'error';
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
            $satisfied_requirement = true;
            switch ($type) {
                case('text'):
                    if ($discuss_it_settings->min_number_of_words) {
                        $satisfied_requirement = str_word_count($data['text']) >= $discuss_it_settings->min_number_of_words;
                    }
                    break;
                case('file'):

                    break;

            }

            $discussionComment->satisfied_requirement = $satisfied_requirement;
            $discussionComment->save();

            DB::commit();
            $discussion_comment_submission_results = $discussionComment->satisfiedRequirements($assignment->id, $question->id, $request->user()->id, $discussion, $assignmentSyncQuestion);
            $response['type'] = $type;
            $response['message'] = $message;
            $response['discussion_comment_id'] = $discussionComment->id;
            $response['discussion_comment_submission_results'] = $discussion_comment_submission_results;

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
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Discussion $discussion
     * @param User $user
     * @return array
     * @throws Exception
     */
    public
    function show(Request    $request,
                  Assignment $assignment,
                  Question   $question,
                  Discussion $discussion,
                  User       $user): array
    {

        try {
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
