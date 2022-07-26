<?php

namespace App;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Snowfire\Beautymail\Beautymail;

class PendingQuestionOwnershipTransfer extends Model
{
    /**
     * @param User $new_owner
     * @param User $old_owner
     * @param array $question_ids
     * @return array
     * @throws BindingResolutionException
     */
    public function createPendingOwnershipTransferRequest(User $new_owner, User $old_owner, array $question_ids): array
    {
        $questions_to_transfer_html = $this->getQuestionsToTransferHtml($question_ids);
        $response['type'] = 'error';
        $pending_ownership_transfer_question_ids = DB::table('pending_question_ownership_transfers')
            ->get('question_id')
            ->pluck('question_id')
            ->toArray();
        $already_pendings = [];
        foreach ($question_ids as $question_id) {
            if (in_array($question_id, $pending_ownership_transfer_question_ids)) {
                $already_pendings[] = $question_id;
            }
        }
        if ($already_pendings) {
            $plural = count($already_pendings) > 1 ? 's are' : ' is';
            $already_pendings = implode(', ', $already_pendings);
            $response['timeout'] = 12000;
            $response['message'] = "The following question$plural in a state of pending ownership transfer: $already_pendings.<br><br>If the new owner does not accept transfer of ownership within 24 hours, you can re-request a transfer of question ownership.";
            return $response;
        }
        $token = substr(sha1(mt_rand()), 17, 12);
        foreach ($question_ids as $question_id) {
            $pendingQuestionOwnershipTransfer = new PendingQuestionOwnershipTransfer();
            $pendingQuestionOwnershipTransfer->new_owner_user_id = $new_owner->id;
            $pendingQuestionOwnershipTransfer->question_id = $question_id;
            $pendingQuestionOwnershipTransfer->token = $token;
            $pendingQuestionOwnershipTransfer->save();
        }
        $beauty_mail = app()->make(Beautymail::class);

        $transfer_info = ['old_owner_info' => "$old_owner->first_name $old_owner->last_name ($old_owner->email)",
            'new_owner_name' => $new_owner->first_name,
            'questions_to_transfer_html' => $questions_to_transfer_html,
            'action_url' => request()->getSchemeAndHttpHost() . "/pending-question-ownership-transfer-request",
            'token' => $token];

        $beauty_mail->send('emails.pending_question_ownership_transfer_request', $transfer_info, function ($message)
        use ($new_owner, $old_owner) {
            $message
                ->from('adapt@noreply.libretexts.org', 'ADAPT')
                ->to($new_owner->email)
                ->replyTo($old_owner->email)
                ->subject('Pending Question Ownership Transfer');
        });


        $response['type'] = 'success';
        return $response;
    }

    /**
     * @param $question_ids
     * @return string
     */
    public function getQuestionsToTransferHtml($question_ids): string
    {
        $questions_to_transfer = Question::whereIn('id', $question_ids)->get();
        $questions_to_transfer_array = [];
        foreach ($questions_to_transfer as $question_to_transfer) {
            $title = $question_to_transfer->title ?: 'No title';
            $questions_to_transfer_array[]= "$title ($question_to_transfer->id)";
        }
        return implode(', ',  $questions_to_transfer_array);
    }
}
