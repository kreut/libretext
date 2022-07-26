<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use App\PendingQuestionOwnershipTransfer;
use App\Question;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use Snowfire\Beautymail\Beautymail;

class removePendingQuestionOwnershipTransfers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:pendingQuestionOwnershipTransfers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param Question $question
     * @param PendingQuestionOwnershipTransfer $pendingQuestionOwnershipTransfer
     * @return int
     * @throws BindingResolutionException
     */
    public function handle(Question $question, PendingQuestionOwnershipTransfer $pendingQuestionOwnershipTransfer): int
    {

        $last_24_hours = Carbon::now()->subDay()->format('Y-m-d H:i:s');
        $old_pending_question_ownership_transfers = DB::table('pending_question_ownership_transfers')
            ->where('created_at', "<=",   $last_24_hours)
            ->get();
        $old_pending_question_ownership_transfers_by_token = [];
        foreach ($old_pending_question_ownership_transfers as $old_pending_question_ownership_transfer) {
            if (!isset($old_pending_question_ownership_transfers_by_token[$old_pending_question_ownership_transfer->token])) {
                $old_pending_question_ownership_transfers_by_token[$old_pending_question_ownership_transfer->token]['question_ids'] = [];
                $old_owner = $question->getQuestionEditorInfoByQuestionId($old_pending_question_ownership_transfer->question_id);
                $new_owner = User::find($old_pending_question_ownership_transfer->new_owner_user_id);

                $old_pending_question_ownership_transfers_by_token[$old_pending_question_ownership_transfer->token]['old_owner'] = [];
                $old_pending_question_ownership_transfers_by_token[$old_pending_question_ownership_transfer->token]['old_owner']['email'] = $old_owner->email;
                $old_pending_question_ownership_transfers_by_token[$old_pending_question_ownership_transfer->token]['old_owner']['first_name'] = $old_owner->first_name;

                $old_pending_question_ownership_transfers_by_token[$old_pending_question_ownership_transfer->token]['new_owner'] = [];
                $old_pending_question_ownership_transfers_by_token[$old_pending_question_ownership_transfer->token]['new_owner']['name_email'] = "$new_owner->first_name $new_owner->last_name ($new_owner->email)";


            }
            $old_pending_question_ownership_transfers_by_token[$old_pending_question_ownership_transfer->token]['question_ids'][] = $old_pending_question_ownership_transfer->question_id;
        }


            $beauty_mail = app()->make(Beautymail::class);

            foreach ($old_pending_question_ownership_transfers_by_token as $token => $info) {
                try {
                $transfer_info = ['old_owner_name' => $info['old_owner']['first_name'],
                    'new_owner_user_info' => $info['new_owner']['name_email'],
                    'questions_to_transfer_html' => $pendingQuestionOwnershipTransfer->getQuestionsToTransferHtml($info['question_ids'])];
                $beauty_mail->send('emails.question_ownership_transfer_non_response', $transfer_info, function ($message)
                use ($info) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->to($info['old_owner']['email'])
                        ->subject('Non-response for Question Ownership');
                });
                $pendingQuestionOwnershipTransfer->where('token', $token)->delete();
                } catch (Exception $e) {
                    $h = new Handler(app());
                    $h->report($e);
                    return 1;
                }
            }
        return 0;
    }
}
