<?php

namespace App\Http\Controllers;


use App\Invitation;
use App\Course;
use App\GraderAccessCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\EmailInvitation;
use App\Traits\AccessCodes;

use App\Exceptions\Handler;
use \Exception;

class InvitationController extends Controller
{

    use AccessCodes;
    public function emailInvitation(EmailInvitation $request, Course $course, Invitation $invitation, GraderAccessCode $ta_access_code)
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
            $access_code =  $this->createGraderAccessCode();
            $ta_access_code->create(['course_id' => $course->id,
                'access_code' => $access_code]);

            $beautymail = app()->make(\Snowfire\Beautymail\Beautymail::class);
            $to_email = $data['email'];
            $instructor = Auth::user();
            $instructor_info = ['instructor' => "{$instructor->first_name} {$instructor->last_name}" ,
                'course' => $course->name,
                'access_code' => $access_code,
                'link' => request()->getSchemeAndHttpHost() . '/register/grader'];

            $beautymail->send('emails.grader_invitation', $instructor_info, function($message)
            use ($to_email) {
                $message
                    ->from('adapt@libretexts.org')
                    ->to($to_email)
                    ->replyTo(Auth::user()->email)
                    ->subject('Invitation to Grade');
            });

            $response['message'] = 'Your grader has been sent an email inviting them to this course.';
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
