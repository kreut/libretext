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
    /**
     * @param Send $request
     * @param Email $email
     * @return array
     * @throws Exception
     */
    public function send(Send $request, Email $email)
    {
        $response['type'] = 'error';
        $response['message'] = 'We do not support that email type.';
        switch ($request->type) {
            case('contact_us'):
               $response =  $this->contactUs($request, $email);
                break;
            case('contact_grader'):
               $response=  $this->contactGrader($request, $email);
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
    public function contactGrader(Send $request, Email $email)
    {
        $extra_params = $request->extraParams;
        $assignment_id = $extra_params['assignment_id'];
        $question_id = $extra_params['question_id'];
        $to_user_id = $request->to_user_id;
        $to_email = User::find($to_user_id)->email;
        $authorized = Gate::inspect('contactGrader', [$email, $to_user_id, $assignment_id, $question_id]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        try {
            $student_user_id = Auth::user()->id;
            $link = $request->getSchemeAndHttpHost() . "/assignments/$assignment_id/grading/$question_id/$student_user_id";
            Mail::to($to_email)
                ->send(new \App\Mail\ContactGrader($data['subject'], $data['text'], $data['email'], $data['name'], $link));

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
     * @param Email $email
     * @return array
     * @throws Exception
     */
    public function contactUs(Send $request, Email $email)
    {
        $response['type'] = 'error';
        $to_user_id = $request->to_user_id;
        $authorized = Gate::inspect('contactUs', [$email, $to_user_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();

        try {

            Mail::to('adapt@libretexts.org')
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
