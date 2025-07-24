<?php

namespace App\Http\Controllers;


use App\CoInstructor;
use App\Helpers\Helper;
use App\Http\Requests\EmailCoInstructorInvitation;
use App\Invitation;
use App\Course;
use App\GraderAccessCode;
use App\Section;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\EmailGraderInvitation;


use App\Exceptions\Handler;
use \Exception;
use Snowfire\Beautymail\Beautymail;

class InvitationController extends Controller
{
    /**
     * @param EmailCoInstructorInvitation $request
     * @param Course $Course
     * @param Invitation $invitation
     * @return array
     * @throws Exception
     */
    public function emailCoInstructorInvitation(EmailCoInstructorInvitation $request,
                                                Course                      $Course,
                                                Invitation                  $invitation): array
    {

        $response['type'] = 'error';
        $course = $Course->find($request->course_id);
        $authorized = Gate::inspect('emailCoInstructorInvitation', [$invitation, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            $data = $request->validated();
            $access_code = Helper::createAccessCode();
            $coInstructor = new CoInstructor();
            $user = User::where('email', $request->email)->first();
            $user_id = $user->id;
            if ($coInstructor->where('course_id', $request->course_id)
                ->where('user_id', $user_id)
                ->where('status', 'accepted')
                ->exists()) {
                $response['message'] = "$user->first_name $user->last_name is already a co-instructor in this course.";
                $response['type'] = 'info';
                return $response;
            }
            $coInstructor->where('course_id', $request->course_id)
                ->where('user_id', $user_id)
                ->where('status', 'pending')
                ->delete();
            $coInstructor->access_code = $access_code;
            $coInstructor->course_id = $request->course_id;
            $coInstructor->user_id = $user_id;
            $coInstructor->status = 'pending';
            $coInstructor->save();

            $beauty_mail = app()->make(Beautymail::class);
            $to_email = $data['email'];
            $instructor = Auth::user();
            $instructor_info = ['instructor' => "{$instructor->first_name} {$instructor->last_name}",
                'course_name' => $course->name,
                'accept_co_instructor_invitation_link' => request()->getSchemeAndHttpHost() . "/invitations/co-instructor/accept/$access_code"];
            $beauty_mail->send('emails.co_instructor_invitation', $instructor_info, function ($message)
            use ($to_email) {
                $message
                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
                    ->to($to_email)
                    ->replyTo(Auth::user()->email)
                    ->subject('Co-Instructor Invitation');
            });

            $response['message'] = 'The instructor has been sent an email inviting them to be a co-instructor for this course.';
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error sending out this invitation.  Please try again by refreshing the page or contact us for assistance.";
            return $response;
        }
        return $response;

    }

    /**
     * @param EmailGraderInvitation $request
     * @param Course $Course
     * @param Section $section
     * @param Invitation $invitation
     * @return array
     * @throws Exception
     */
    public function emailGraderInvitation(EmailGraderInvitation $request,
                                          Course                $Course,
                                          Section               $section,
                                          Invitation            $invitation): array
    {

        $response['type'] = 'error';
        $course = $Course->find($request->course_id);
        $authorized = Gate::inspect('emailGraderInvitation', [$invitation, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $data = $request->validated();

            //create an access code and save it to the database
            $section_ids = $data['selected_sections'];
            $course_section_names = [];
            $access_code = Helper::createAccessCode();
            foreach ($section_ids as $section_id) {
                $course_section_names [] = $course->name . ' - ' . $section->find($section_id)->name;
                $grader_access_code = new GraderAccessCode();
                $grader_access_code->section_id = $section_id;
                $grader_access_code->access_code = $access_code;
                $grader_access_code->save();
            }
            $beauty_mail = app()->make(Beautymail::class);
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
                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
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
