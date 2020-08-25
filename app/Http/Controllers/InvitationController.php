<?php

namespace App\Http\Controllers;


use App\Invitation;
use App\Course;
use App\TaAccessCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\inviteTa;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\EmailInvitation;
use App\Traits\AccessCodes;
use App\Exceptions\Handler;
use \Exception;

class InvitationController extends Controller
{

    use AccessCodes;
    public function emailInvitation(EmailInvitation $request, Course $course, Invitation $invitation, TaAccessCode $ta_access_code)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('emailInvitation', [$invitation, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $data = $request->validated();

            //create an access code and save it to the database

            $to_name = 'Eric Kean';
            $to_email = 'kreut@hotmail.com';
            $access_code =  $this->createTaAccessCode();
            $ta_access_code->create(['course_id' => $course->id,
                'access_code' => $access_code]);

            Mail::to($data['email'])->send(new inviteTa($access_code));
            $response['message'] = 'Your TA has been sent an email inviting them to this course.';
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error sending out this invitation.  Please try again by refreshing the page or contact us for assistance.";
            return $response;
        }
        return $response;

    }
}
