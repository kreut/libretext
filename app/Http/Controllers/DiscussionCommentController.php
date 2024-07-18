<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Discussion;
use App\DiscussionComment;
use App\Exceptions\Handler;
use App\Http\Requests\UpdateDiscussionRequest;
use App\Jobs\ProcessTranscribe;
use App\Question;
use App\QuestionMediaUpload;
use App\Score;
use App\Submission;
use App\SubmissionFile;
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
     * @param DiscussionComment $discussionComment
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public function deletingWillMakeRequirementsNotSatisfied(DiscussionComment      $discussionComment,
                                                             AssignmentSyncQuestion $assignmentSyncQuestion): array
    {
        try {
            $response['type'] = 'error';
            $discussion = Discussion::find($discussionComment->discussion_id);
            $authorized = Gate::inspect('deletingWillMakeRequirementsNotSatisfied', [$discussionComment, $discussion->assignment_id, $discussion->question_id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $user = User::find($discussionComment->user_id);
            if ($user->role === 2) {
                $response['type'] = 'success';
                $response['deleting_will_make_requirements_not_satisfied'] = false;
            }

            $assignment = Assignment::find($discussion->assignment_id);
            $satisfied_requirements = $discussionComment->satisfiedRequirements($assignment,
                $discussion->question_id,
                $discussionComment->user_id,
                $discussion,
                $assignmentSyncQuestion);
            $satisfied_all_requirements = $satisfied_requirements['satisfied_min_number_of_comments_requirement']
                && $satisfied_requirements['satisfied_min_number_of_discussion_threads_requirement'];
            if (!$satisfied_all_requirements) {
                $response['type'] = 'success';
                $response['deleting_will_make_requirements_not_satisfied'] = false;
            } else {
                DB::beginTransaction();
                $discussionComment->delete();
                $satisfied_requirements = $discussionComment->satisfiedRequirements($assignment,
                    $discussion->question_id,
                    $discussionComment->user_id,
                    $discussion,
                    $assignmentSyncQuestion);
                $satisfied_all_requirements = $satisfied_requirements['satisfied_min_number_of_comments_requirement']
                    && $satisfied_requirements['satisfied_min_number_of_discussion_threads_requirement'];
                $response['type'] = 'success';
                $response['deleting_will_make_requirements_not_satisfied'] = !$satisfied_all_requirements;
                DB::rollback();
                return $response;
            }
        } catch (Exception $e) {
            if (DB::transactionLevel()) {
                DB::rollback();
            }
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to determine whether deleting the comment would make the requirements not satisfied.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param QuestionMediaUpload $questionMediaUpload
     * @param DiscussionComment $discussionComment
     * @return array
     * @throws Exception
     */
    public function storeAudioDiscussionComment(Request             $request,
                                                Assignment          $assignment,
                                                Question            $question,
                                                QuestionMediaUpload $questionMediaUpload,
                                                DiscussionComment   $discussionComment): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('storeAudioDiscussionComment', [$discussionComment, $assignment->id, $question->id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $file = md5(uniqid('', true)) . '.mp3';
            $path = $questionMediaUpload->getDir() . "/$file";
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
     * @throws Exception
     */
    public function satisfiedRequirements(Assignment             $assignment,
                                          Question               $question,
                                          User                   $user,
                                          AssignmentSyncQuestion $assignmentSyncQuestion,
                                          DiscussionComment      $discussionComment,
                                          Discussion             $discussion): array
    {
        try {

            $authorized = Gate::inspect('getSatisfiedRequirements', [$discussionComment, $assignment->id, $user->id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $satisfied_requirements = $discussionComment->satisfiedRequirements($assignment,
                $question->id,
                $user->id,
                $discussion,
                $assignmentSyncQuestion);
            $response['type'] = 'success';
            $response['satisfied_requirements'] = $satisfied_requirements;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We are unable to see if the requirements were satisfied.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param DiscussionComment $discussionComment
     * @param Submission $submission
     * @param QuestionMediaUpload $questionMediaUpload
     * @return array
     * @throws Exception
     */
    public function destroy(Request             $request,
                            DiscussionComment   $discussionComment,
                            Submission          $submission,
                            QuestionMediaUpload $questionMediaUpload): array
    {
        try {
            $discussion = DB::table('discussion_comments')
                ->join('discussions', 'discussion_comments.discussion_id', '=', 'discussions.id')
                ->where('discussion_comments.id', $discussionComment->id)
                ->first();

            $response['type'] = 'error';
            $authorized = Gate::inspect('destroy', [$discussionComment, $discussion->assignment_id, $discussion->question_id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

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


            $discussionComment->delete();
            $deleted_discussion = false;
            if (!DB::table('discussion_comments')->where('discussion_id', $discussion_id)->first()) {
                Discussion::find($discussion_id)->delete();
                $deleted_discussion = true;
            }
            $questionMediaUpload->deleteFileAndVttFile($discussionComment->file);

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
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Submission $submission
     * @param Score $score
     * @param SubmissionFile $submissionFile
     * @return array
     * @throws Exception
     */
    public function update(UpdateDiscussionRequest $request,
                           DiscussionComment       $discussionComment,
                           AssignmentSyncQuestion  $assignmentSyncQuestion,
                           Submission              $submission,
                           Score                   $score,
                           SubmissionFile          $submissionFile): array
    {
        $response['type'] = 'error';
        $data = $request->validated();
        $type = $request->type;

        try {

            $discussion_info = DB::table('discussion_comments')
                ->join('discussions', 'discussion_comments.discussion_id', '=', 'discussions.id')
                ->where('discussion_comments.id', $discussionComment->id)
                ->first();
            $discussion = Discussion::find($discussion_info->discussion_id);
            $assignment = Assignment::find($discussion_info->assignment_id);
            $question = Question::find($discussion_info->question_id);

            $response['type'] = 'error';
            $authorized = Gate::inspect('update', [$discussionComment, $discussion->assignment_id, $discussion->question_id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $discuss_it_settings = json_decode($assignmentSyncQuestion->discussItSettings($assignment->id, $question->id));
            $satisfied_requirement = $discussionComment->satisfiedRequirement($type, $discuss_it_settings, $request);

            $discussionComment->{$type} = $data[$type];
            $discussionComment->created_at = now();
            if ($type === 'file') {
                ProcessTranscribe::dispatch($data['file'], 'discussion_comment');
            }
            if ($satisfied_requirement) {
                $discussionComment->satisfied_requirement = 1;
            }
            $discussionComment->save();
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
     * @param QuestionMediaUpload $questionMediaUpload
     * @return Application|Factory|View
     * @throws Exception
     */
    public function mediaPlayer(string $key, string $key_id, QuestionMediaUpload $questionMediaUpload)
    {

        switch ($key) {
            case('discussion-comment-id'):
                $discussionComment = DiscussionComment::find($key_id);
                $file = $discussionComment->file;
                $type = strpos($file, '.mp3') !== false ? 'audio' : 'video';
                break;
            case('filename'):
                $type = 'video';
                $file = $key_id;
                break;
            default:
                return view('media_player_error', ['message' => "$key is not a valid key for the media player"]);

        }
        $vtt_file_exists = false;
        try {
            $vtt_file = $questionMediaUpload->getVttFileNameFromS3Key($file);
            $vtt_file_exists = true;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
        $vtt_file = $vtt_file_exists
            ? Storage::disk('s3')->temporaryUrl("{$questionMediaUpload->getDir()}/$vtt_file", Carbon::now()->addDays(7))
            : '';
        $temporary_url = Storage::disk('s3')->temporaryUrl("{$questionMediaUpload->getDir()}/$file", Carbon::now()->addDays(7));
        if (!Storage::disk('s3')->exists("{$questionMediaUpload->getDir()}/$file")) {
            return view('media_player_error', ['message' => "The file {$questionMediaUpload->getDir()}/$file was not found on the server."]);
        }
        return view('media_player', ['type' => $type, 'temporary_url' => $temporary_url, 'vtt_file' => $vtt_file, 'start_time' => 0]);
    }
}
