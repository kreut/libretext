<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\PendingQuestionOwnershipTransfer;
use App\Question;
use App\SavedQuestionsFolder;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Snowfire\Beautymail\Beautymail;
use voku\helper\ASCII;

class PendingQuestionOwnershipTransferController extends Controller
{
    /**
     * @param Request $request
     * @param PendingQuestionOwnershipTransfer $pendingQuestionOwnershipTransfer
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public function update(Request                          $request,
                           PendingQuestionOwnershipTransfer $pendingQuestionOwnershipTransfer,
                           SavedQuestionsFolder             $savedQuestionsFolder,
                           Question                         $question): array
    {

        $response['type'] = 'error';
        $action = $request->action;
        $token = $request->token;
        $authorized = Gate::inspect('update', [$pendingQuestionOwnershipTransfer, $request->token]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $pending_ownership_transfer_requests = $pendingQuestionOwnershipTransfer->where('token', $token)->get();
        $new_user_id = $pending_ownership_transfer_requests[0]->new_owner_user_id;
        $question_id = $pending_ownership_transfer_requests[0]->question_id;

        $old_owner = $question->getQuestionEditorInfoByQuestionId($question_id);

        $new_owner = User::find($new_user_id);
        $new_owner_user_info = "$new_owner->first_name $new_owner->last_name ($new_owner->email)";
        $question_ids = [];
        foreach ($pending_ownership_transfer_requests as $pending_ownership_transfer_request) {
            $question_ids[] = $pending_ownership_transfer_request->question_id;
        }
        DB::beginTransaction();
        try {
            switch ($action) {
                case('accept') :
                    $savedQuestionsFolder->moveQuestionsToNewOwnerInTransferredQuestions($new_user_id, $question_ids);
                    $pendingQuestionOwnershipTransfer->where('token', $token)->delete();
                    $message = 'You have accepted the question owner transfer request. The questions will now appear in your Transferred Questions folder under My Questions.';
                    break;
                case('reject'):
                    $pendingQuestionOwnershipTransfer->where('token', $token)->delete();
                    $message = 'You have reject the question owner transfer request. If this was an error, please ask the owner to initiate a transfer of ownership once again.';
                    break;
                default:
                    $message = "$action is not a valid action.";
                    break;
            }

            $beauty_mail = app()->make(Beautymail::class);

            $questions_to_transfer_html = $pendingQuestionOwnershipTransfer->getQuestionsToTransferHtml($question_ids);
            $transfer_info = ['old_owner_name' => $old_owner->first_name,
                'new_owner_user_info' => $new_owner_user_info,
                'questions_to_transfer_html' => $questions_to_transfer_html,
                'action' => $action];

            $beauty_mail->send('emails.pending_question_ownership_transfer_response', $transfer_info, function ($message)
            use ($old_owner) {
                $message
                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
                    ->to($old_owner->email)
                    ->subject('Pending Question Ownership Transfer Response');
            });


            $response['message'] = $message;
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error transferring the question ownership.  Please try again or contact us for assistance.";

        }
        return $response;
    }
}
