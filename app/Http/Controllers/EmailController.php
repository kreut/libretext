<?php

namespace App\Http\Controllers;


use App\Exceptions\Handler;
use \Exception;


use App\Http\Requests\Send;
use App\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Email;


class EmailController extends Controller
{
    /**
     * @param Send $request
     * @param Email $email
     * @return array
     * @throws Exception
     */
    public function send(Send $request, Email $email): array
    {
        $response['type'] = 'error';
        $response['message'] = 'We do not support that email type.';
        switch ($request->type) {
            case('contact_us'):
                $response = $this->contactUs($request, $email);
                break;
            case('contact_grader'):
                $response = $this->contactGrader($request, $email);
                break;

        }
        return $response;
    }

    /**
     * @param Send $request
     * @param Email $email
     * @return array
     * @throws Exception
     */
    public function contactGrader(Send $request, Email $email): array
    {
        $extra_params = $request->extraParams;
        $assignment_id = $extra_params['assignment_id'];
        $question_id = $extra_params['question_id'];
        $to_user_id = $request->to_user_id;
        $authorized = Gate::inspect('contactGrader', [$email, $to_user_id, $assignment_id, $question_id]);

        $response['type'] = 'error';
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        try {
            $to_email = User::find($to_user_id)->email;
            $from_name = $request->user()->first_name . ' ' . $request->user()->last_name;
            $from_email = $request->user()->email;
            $student_user_id = Auth::user()->id;
            $link = $request->getSchemeAndHttpHost() . "/assignments/$assignment_id/grading/$question_id/$student_user_id";
            Mail::to($to_email)
                ->send(new \App\Mail\ContactGrader($data['subject'], $data['text'], $from_email, $from_name, $link));

            $response['type'] = 'success';
            $response['message'] = 'Thank you for your message!  Please expect a response within 1 business day.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error sending the email.  Please try again.";
        }
        return $response;

    }

    /**
     * @param Send $request
     * @return array
     * @throws Exception
     */
    public function contactUs(Send $request): array
    {
        $response['type'] = 'error';
        $to_user_id = $request->to_user_id;
        if ((int)$to_user_id !== 0) {
            $response['message'] = 'You are not allowed to send that person an email.';
            return $response;
        }
        $data = $request->validated();

        try {
            $to_email = in_array($data['subject'], ['General Inquiry', 'Request Instructor Access Code'])
                ? 'delmar@libretexts.org'
                : 'adapt@libretexts.org';
            if ($data['subject'] === 'Request Instructor Access Code') {
                $data['text'] = "{$data['name']} from {$data['school']} with email {$data['email']} would like an instructor access code. --- {$data['text']}";
            }
            Mail::to($to_email)
                ->send(new \App\Mail\Email($data['subject'], $data['text'], $data['email'], $data['name']));

            $response['type'] = 'success';
            $response['message'] = 'Thank you for your message!  Please expect a response within 1 business day.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error sending the email.  Please try again.";
        }
        return $response;
    }
}
