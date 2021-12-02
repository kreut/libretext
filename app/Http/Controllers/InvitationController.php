<?php

namespace App\Http\Controllers;


use App\Invitation;
use App\Course;
use App\GraderAccessCode;
use App\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\EmailInvitation;
use App\Traits\AccessCodes;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Log;

class InvitationController extends Controller
{

    use AccessCodes;

    /**
     * @param EmailInvitation $request
     * @param Course $Course
     * @param Section $section
     * @param Invitation $invitation
     * @return array
     * @throws Exception
     */
    public function emailGraderInvitation(EmailInvitation $request,
                                          Course $Course,
                                          Section $section,
                                          Invitation $invitation)
    {

        $response['type'] = 'error';
        $course = $Course->find($request->course_id);
        $authorized = Gate::inspect('emailInvitation', [$invitation, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $data = $request->validated();

            //create an access code and save it to the database
            $section_ids = $data['selected_sections'];
            $course_section_names = [];
            $access_code = $this->createGraderAccessCode();
            foreach ($section_ids as $section_id) {
                $course_section_names [] = $course->name . ' - ' . $section->find($section_id)->name;
                $grader_access_code = new GraderAccessCode();
                $grader_access_code->section_id = $section_id;
                $grader_access_code->access_code = $access_code;
                $grader_access_code->save();
            }
            $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
            $to_email = $data['email'];
            $instructor = Auth::user();
            $instructor_info = ['instructor' => "{$instructor->first_name} {$instructor->last_name}",
                'course_section_names' => implode(', ', $course_section_names),
                'access_code' => $access_code,
                'login_link' => request()->getSchemeAndHttpHost() . '/login',
                'signup_link' => request()->getSchemeAndHttpHost() . '/register/grader'];

            $beauty_mail->send('emails.grader_invitation', $instructor_info, function ($message)
            use ($to_email) {
                $message
                    ->from('adapt@noreply.libretexts.org','ADAPT')
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
