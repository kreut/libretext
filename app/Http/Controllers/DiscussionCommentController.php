<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Discussion;
use App\DiscussionComment;
use App\Exceptions\Handler;
use App\Http\Requests\UpdateDiscussionRequest;
use App\Question;
use App\Submission;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DiscussionCommentController extends Controller
{

    /**
     * @param Request $request
     * @param User $user
     * @param Assignment $assignment
     * @param Question $question
     * @param DiscussionComment $discussionComment
     * @return array
     * @throws Exception
     */
    public function storeAudioDiscussionComment(Request           $request,
                                                User              $user,
                                                Assignment        $assignment,
                                                Question          $question,
                                                DiscussionComment $discussionComment)
    {

        $response['type'] = 'error';
        /* $assignment_id = $assignment->id;
         $question_id = $question->id;
         $student_user_id = $user->id;


         $authorized = Gate::inspect('storeAudioDiscussionComment', [$assignmentFile, $user->find($student_user_id), $assignment->find($assignment_id)]);
         if (!$authorized->allowed()) {
             $response['message'] = $authorized->message();
             return $response;
         }*/

        try {

            $file = md5(uniqid('', true)) . '.mp3';
            $path = $discussionComment->getDir() . "/$file";
            Storage::disk('s3')->put($path, file_get_contents($request->file('audio')));

            $response['type'] = 'success';
            $response['file'] = $file;


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your audio comment.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param User $user
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param DiscussionComment $discussionComment
     * @param Discussion $discussion
     * @return array
     */
    public function satisfiedRequirements(Assignment             $assignment,
                                          Question               $question,
                                          User                   $user,
                                          AssignmentSyncQuestion $assignmentSyncQuestion,
                                          DiscussionComment      $discussionComment,
                                          Discussion             $discussion): array
    {
        $response = $discussionComment->satisfiedRequirements($assignment->id, $question->id, $user->id, $discussion, $assignmentSyncQuestion);
        $response['type'] = 'success';
        return $response;
    }

    /**
     * @param Request $request
     * @param DiscussionComment $discussionComment
     * @param Submission $submission
     * @return array
     * @throws Exception
     */
    public function destroy(Request $request, DiscussionComment $discussionComment, Submission $submission): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('destroy', $discussionComment);
            if (!$authorized->allowed()) {
                $response['message'] = 'You are not allowed to delete this discussion comment.';
                return $response;
            }
            $discussion = DB::table('discussion_comments')
                ->join('discussions', 'discussion_comments.discussion_id', '=', 'discussions.id')
                ->where('discussion_comments.id', $discussionComment->id)
                ->first();
            $already_graded = $request->user()->role === 3 && $submission->where('assignment_id', $discussion->assignment_id)
                    ->where('question_id', $discussion->question_id)
                    ->where('user_id', $request->user()->id)
                    ->first();
            if ($already_graded) {
                $response['message'] = "Since this comment is part of a discussion that has already been graded, you cannot delete it.";
                return $response;
            }
            DB::beginTransaction();
            $discussion_id = $discussionComment->discussion_id;


            $s3_key = $discussionComment->file ? $discussionComment->getDir() . "/" . $discussionComment->file : false;
            $discussionComment->delete();
            $deleted_discussion = false;
            if (!DB::table('discussion_comments')->where('discussion_id', $discussion_id)->first()) {
                Discussion::find($discussion_id)->delete();
                $deleted_discussion = true;
            }

            if ($s3_key && (Storage::disk('s3')->exists($s3_key))) {
                Storage::disk('s3')->delete($s3_key);
            }
            $response['type'] = 'info';
            $response['message'] = $deleted_discussion ?
                "The comment and discussion thread have been deleted."
                : "The comment has been deleted.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We are unable to delete this discussion comment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param UpdateDiscussionRequest $request
     * @param DiscussionComment $discussionComment
     * @return array
     * @throws Exception
     */
    public function update(UpdateDiscussionRequest $request,
                           DiscussionComment       $discussionComment): array
    {
        $response['type'] = 'error';
        $data = $request->validated();
        $type = $request->type;
        try {
            $discussionComment->{$type} = $data[$type];
            $discussionComment->created_at = now();
            $discussionComment->save();
            $response['type'] = 'success';
            $response['message'] = 'The comment has been updated.';
            $response['discussion_comment_id'] = $discussionComment->id;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We are unable to update this discussion comment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param string $key
     * @param string $key_id
     * @return Application|Factory|View
     */
    public function mediaPlayer(string $key, string $key_id)
    {
        //$questionMediaUpload = new QuestionMediaUpload();
        //$vtt_file = $questionMediaUpload->getVttFileNameFromS3Key($media);
        switch ($key) {
            case('discussion-comment-id'):
                $discussionComment = DiscussionComment::find($key_id);
                $file = $discussionComment->file;
                $type = strpos($file, '.mp3') !== false ? 'audio' : 'video';
                break;
            case('filename'):
                $type = 'video';
                $discussionComment = new DiscussionComment();
                $file = $key_id;
                break;
            default:
                return view('media_player_error', ['message' => "$key is not a valid key for the media player"]);

        }
        $temporary_url = Storage::disk('s3')->temporaryUrl("{$discussionComment->getDir()}/$file", Carbon::now()->addDays(7));
        if (!Storage::disk('s3')->exists("{$discussionComment->getDir()}/$file")) {
            return view('media_player_error', ['message' => "The file {$discussionComment->getDir()}/$file was not found on the server."]);
        }
        //$vtt_file = Storage::disk('s3')->temporaryUrl("{$discussionComment->getDir()}/$vtt_file", Carbon::now()->addDays(7));
        return view('media_player', ['type' => $type, 'temporary_url' => $temporary_url, 'vtt_file' => '', 'start_time' => 0]);
    }
}
