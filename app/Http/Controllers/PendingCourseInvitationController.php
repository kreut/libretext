<?php

namespace App\Http\Controllers;

use App\Course;
use App\Exceptions\Handler;
use App\PendingCourseInvitation;
use Exception;
use Illuminate\Support\Facades\Gate;


class PendingCourseInvitationController extends Controller
{

    /**
     * @param PendingCourseInvitation $pendingCourseInvitation
     * @return array
     * @throws Exception
     */
    public function destroy(PendingCourseInvitation $pendingCourseInvitation): array
    {

        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('destroy', $pendingCourseInvitation);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $pendingCourseInvitation->delete();
            $response['type'] = 'info';
            $response['message'] = "The invitation has been revoked.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the pending course invitations.  Please try again or contact us for assistance.";

        }
        return $response;

    }

    /**
     * @param Course $course
     * @param PendingCourseInvitation $pendingCourseInvitation
     * @return array
     * @throws Exception
     */
    public function getPendingCourseInvitations(Course $course, PendingCourseInvitation $pendingCourseInvitation): array
    {

        try {

            $authorized = Gate::inspect('getPendingCourseInvitations', [$pendingCourseInvitation, $course]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $pending_course_invitations = $pendingCourseInvitation
                ->join('sections','pending_course_invitations.section_id','=','sections.id')
                ->where('pending_course_invitations.course_id', $course->id)
                ->orderBy('first_name')
                ->select('pending_course_invitations.*','sections.name AS section')
                ->get();
            foreach ($pending_course_invitations as $key => $pending_course_invitation) {
                $pending_course_invitation->name = $pending_course_invitation->first_name . ' ' . $pending_course_invitation->last_name;
                $pending_course_invitation->invitation_sent = $pending_course_invitation->created_at->format('n/j/y');

            }
            $response['pending_course_invitations'] = $pending_course_invitations;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the pending course invitations.  Please try again or contact us for assistance.";

        }
        return $response;

    }

}
