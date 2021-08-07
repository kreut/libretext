<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\MakeRefreshQuestionRequest;
use App\Question;
use App\RefreshQuestionRequest;
use App\User;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class RefreshQuestionRequestController extends Controller
{
    /**
     * @param Question $question
     * @param RefreshQuestionRequest $refreshQuestionRequest
     * @return array
     * @throws Exception
     */
    public function denyRefreshQuestionRequest(Question               $question,
                                               RefreshQuestionRequest $refreshQuestionRequest)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('denyRefreshQuestion', $refreshQuestionRequest);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $to_user = DB::table('refresh_question_requests')
                ->join('users', 'refresh_question_requests.user_id', '=', 'users.id')
                ->where('question_id', $question->id)
                ->select('users.first_name', 'users.email')
                ->first();
            $question_to_update = $refreshQuestionRequest->where('question_id', $question->id)->first();
            $question_to_update->status = 'denied';
            $question_to_update->save();
            if (!app()->environment('testing')) {
                $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);

                $refresh_question_denied_info = [
                    'first_name' => $to_user->first_name,
                    'question_id' => $question->id
                ];
                $beauty_mail->send('emails.refresh_question_denied', $refresh_question_denied_info, function ($message)
                use ($to_user) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'Adapt')
                        ->to($to_user->email)
                        ->subject("Refresh Question Denied");
                });
            }
            $response['message'] = 'You have denied this request and the instructor has been notified by email.';
            $response['type'] = 'info';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to deny the refresh question request.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param MakeRefreshQuestionRequest $request
     * @param Question $question
     * @param RefreshQuestionRequest $refreshQuestionRequest
     * @return array
     * @throws Exception
     */
    public function makeRefreshQuestionRequest(MakeRefreshQuestionRequest $request,
                                               Question                   $question,
                                               RefreshQuestionRequest     $refreshQuestionRequest): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('makeRefreshQuestionRequest', $refreshQuestionRequest);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $data = $request->validated();
            $refreshQuestionRequest = RefreshQuestionRequest::where('question_id', $question->id)
                ->first();
            if ($refreshQuestionRequest) {
                if ($refreshQuestionRequest->status === 'pending') {
                    $response['type'] = 'info';
                    $response['message'] = ($refreshQuestionRequest->user_id === $request->user()->id)
                        ? "You have already made a request for this question.  Please be patient while your request is reviewed."
                        : "This question is already in our queue to be reviewed by another instructor.";
                    return $response;
                }
            } else {
                $refreshQuestionRequest = new RefreshQuestionRequest();
            }
            $refreshQuestionRequest->user_id = $request->user()->id;
            $refreshQuestionRequest->question_id = $question->id;
            $refreshQuestionRequest->status = 'pending';
            $refreshQuestionRequest->nature_of_update = $data['nature_of_update'];
            $refreshQuestionRequest->save();

            if (!app()->environment('testing')) {

                $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
                $to_user_id = app()->environment('production') ? 5 : 1;
                $to_user = User::find($to_user_id);
                $refresh_question_approval_info = [
                    'first_name' => $to_user->first_name,
                    'refresh_question_approval_link' => request()->getSchemeAndHttpHost() . '/admin/refresh-question-requests'
                ];
                $beauty_mail->send('emails.pending_refresh_question_approval', $refresh_question_approval_info, function ($message)
                use ($to_user) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'Adapt')
                        ->to($to_user->email)
                        ->subject("Pending Refresh Question Approval");
                });
            }
            $response['type'] = 'success';
            $response['message'] = 'You will receive a notification when your refresh request has been reviewed.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to make the refresh question request on your behalf.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function index(RefreshQuestionRequest $refreshQuestionRequest)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $refreshQuestionRequest);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $refresh_question_requests = DB::table('refresh_question_requests')
                ->join('users', 'refresh_question_requests.user_id', '=', 'users.id')
                ->join('questions', 'refresh_question_requests.question_id', '=', 'questions.id')
                ->select('questions.id AS question_id',
                    'users.id AS user_id',
                    DB::raw("CONCAT(users.first_name, ' ',users.last_name) AS instructor"),
                    'email',
                    'status',
                    'title',
                    'refresh_question_requests.updated_at')
                ->orderBy('updated_at', 'desc')
                ->get();
            $refresh_question_requests_by_status = [
                'pending' => [],
                'approved' => [],
                'denied' => []
            ];
            foreach ($refresh_question_requests as $refresh_question_request) {
                $refresh_question_requests_by_status[$refresh_question_request->status][] = $refresh_question_request;
            }
            $response['refresh_question_requests_by_status'] = $refresh_question_requests_by_status;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get a list of the refresh question requests.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
