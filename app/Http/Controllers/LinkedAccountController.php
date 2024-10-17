<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Http\Requests\AccountValidationCodeRequest;
use App\Http\Requests\EmailLinkToAccountRequest;
use App\LinkedAccount;
use App\LinkToAccountValidationCode;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Snowfire\Beautymail\Beautymail;

class LinkedAccountController extends Controller
{
    /**
     * @param Request $request
     * @param User $account_to_switch_to
     * @return array
     */
    public function switch(Request $request, User $account_to_switch_to): array
    {

        $response['type'] = 'error';
        $linked_accounts = json_decode(Helper::getLinkedAccounts($request->user()->id), 1);
        $can_switch = false;
        foreach ($linked_accounts as $linked_account) {
            if ($linked_account['id'] === $account_to_switch_to->id) {
                $can_switch = true;
            }
        }
        if (!$can_switch) {
            $response['message'] = "You cannot switch to that account.";
            return $response;
        }

        $response['type'] = 'success';
        $response['token'] = \JWTAuth::fromUser($account_to_switch_to);
        return $response;

    }

    /**
     * @param Request $request
     * @param LinkedAccount $linkedAccount
     * @param User $account_to_unlink
     * @return array
     * @throws Exception
     */
    public function unlink(Request $request, LinkedAccount $linkedAccount, User $account_to_unlink): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('unlink', [$linkedAccount, $account_to_unlink]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $linkedAccount->where('user_id', $request->user()->id)
                ->where('linked_to_user_id', $account_to_unlink->id)
                ->delete();
            Helper::getLinkedAccounts($request->user()->id);
            session()->put('linked_accounts', Helper::getLinkedAccounts($request->user()->id));
            $response['type'] = 'info';
            $response['message'] = "$account_to_unlink->email is no longer linked.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to unlink your account.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param AccountValidationCodeRequest $request
     * @param LinkToAccountValidationCode $linkToAccountValidationCode
     * @return array
     * @throws Exception
     */
    public function validateCodeToLinkToAccount(AccountValidationCodeRequest $request,
                                                LinkToAccountValidationCode  $linkToAccountValidationCode): array
    {
        try {
            $response['type'] = 'error';
            DB::beginTransaction();
            $data = $request->validated();
            $linked_account_validation_code = $linkToAccountValidationCode
                ->where('validation_code', $data['validation_code'])
                ->first();
            $linked_account = User::where('email', $linked_account_validation_code->email)->first();
            $linkToAccountValidationCode->where('validation_code', $data['validation_code'])->delete();
            $linkedAccount = new LinkedAccount();
            $linkedAccount->user_id = $request->user()->id;
            $linkedAccount->linked_to_user_id = $linked_account->id;
            $linkedAccount->save();
            session()->put('linked_accounts', Helper::getLinkedAccounts($request->user()->id));
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The accounts have been linked.";

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to validate the code.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param EmailLinkToAccountRequest $request
     * @return array
     * @throws Exception
     */
    public function emailLinkToAccountValidationCode(EmailLinkToAccountRequest $request): array
    {

        try {
            $response['type'] = 'error';
            $email = trim($request->email);
            $account_to_link_to = User::where('email', $email)->first();
            $data = $request->validated();
            $email = $data['email'];
            if ($account_to_link_to) {
                if ($account_to_link_to->id === $request->user()->id) {
                    $response['message'] = "You are trying to link to your current account.";
                    return $response;
                }
                $validation_code = Helper::createAccessCode(15);

                LinkToAccountValidationCode::updateOrCreate(['email' => $email], ['validation_code' => $validation_code]);

                $mail_info = [
                    'first_name' => $request->user()->first_name,
                    'validation_code' => $validation_code
                ];
                $beautymail = app()->make(Beautymail::class);
                $beautymail->send('emails.link_to_account_validation_code', $mail_info, function ($message)
                use ($account_to_link_to) {
                    $message->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->to($account_to_link_to->email, $account_to_link_to->first_name . ' ' . $account_to_link_to->last_name)
                        ->subject('Validation Code to Link Accounts');
                });
            }
            $response['type'] = 'success';
            $response['message'] = "Please check your email for a validation code.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error sending the email.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
