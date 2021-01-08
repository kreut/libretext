<?php

namespace App\Http\Controllers;


use App\Exceptions\Handler;
use \Exception;


use App\Http\Requests\Send;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Email;


class EmailController extends Controller
{
    public function send(Send $request, Email $email)
    {
        $response['type'] = 'error';
        $to_user_id = $request->to_user_id;
        $type = $request->type;

        if (Auth::user()) {
            switch ($request->type) {
                case('contact_us'):
                    $authorized = Gate::inspect('contactUs', [$email, $to_user_id]);
                    break;
                case('contact_grader'):
                    $extra_params = $request->extraParams;
                    $assignment_id = $extra_params['assignment_id'];
                    $question_id = $extra_params['question_id'];
                    $authorized = Gate::inspect('contactGrader', [$email, $to_user_id, $assignment_id, $question_id]);
                    break;
            }
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
        } else {
            //can only send to me
            if ($to_user_id !== 0) {
                $response['message'] = 'You are not allowed to send that person an email.';
                return $response;
            }
        }

        $data = $request->validated();

        $to_email = ($to_user_id === 0) ? 'adapt@libretexts.org' : User::find($to_user_id)->email;

        try {
            if ($type === 'contact_grader') {
                $student_user_id = Auth::user()->id;
                $link = $request->getSchemeAndHttpHost() . "/assignments/grading/$assignment_id/$question_id/$student_user_id";
                Mail::to($to_email)
                    ->send(new \App\Mail\ContactGrader($data['subject'], $data['text'], $data['email'], $data['name'], $link));
            } else {
                Mail::to($to_email)
                    ->send(new \App\Mail\Email($data['subject'], $data['text'], $data['email'], $data['name']));
            }
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
