<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\ContactGraderOverride;
use App\Course;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ContactGraderOverrideController extends Controller
{
    /**
     * @param Request $request
     * @param Course $course
     * @param ContactGraderOverride $contactGraderOverride
     * @return array
     * @throws Exception
     */
    public function update(Request $request, Course $course, ContactGraderOverride $contactGraderOverride): array
    {

        $response['type'] = 'error';
        $contact_grader_override_id = $request->contact_grader_override;
        $authorized = Gate::inspect('update', [$contactGraderOverride, $course, $contact_grader_override_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::beginTransaction();
            $contactGraderOverride->where('course_id', $course->id)->delete();
            if ($contact_grader_override_id) {
                $contactGraderOverride->course_id = $course->id;
                $contactGraderOverride->user_id = $contact_grader_override_id;
                $contactGraderOverride->save();
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The grader contact information has been updated.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the grader contact information. Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function show(Request $request, Assignment $assignment, ContactGraderOverride $contactGraderOverride): array
    {

        $response['type'] = 'error';
        $course = $assignment->course;
        $authorized = Gate::inspect('show', [$contactGraderOverride, $course]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $contact_grader_override = DB::table('contact_grader_overrides')
                ->where('course_id', $course->id)
                ->first();
            $enrollment = DB::table('enrollments')
                ->where('user_id', $request->user()->id)
                ->where('course_id', $course->id)
                ->first();
            $section_grader = DB::table('graders')->where('section_id', $enrollment->section_id)->first();
            $contact_grader_override_id = $contact_grader_override ? $contact_grader_override->user_id : null;
            $response['type'] = 'success';
            $response['default_grader_id'] = $section_grader ? $section_grader->user_id : $course->user_id;
            $response['contact_grader_override_id'] = $contact_grader_override_id;
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the grader's contact information. Please try again or contact us for assistance.";
        }

        return $response;
    }

}
