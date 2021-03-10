<?php

namespace App\Http\Controllers;

use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use App\Course;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Http\Requests\StoreSection;
use App\Section;
use App\Submission;
use App\SubmissionFile;
use Exception;
use Illuminate\Http\Request;
use App\Traits\AccessCodes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SectionController extends Controller
{
    use AccessCodes;

    public function realEnrolledUsers(Request $request, Section $section){

        try {

            $response['number_of_enrolled_users'] =count($section->enrolledUsers()->pluck('user_id'));
            $response['type'] = 'success';

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the number of enrolled users.  Please try again or contact us for assistance.";

        }
        return $response;


    }
    public function destroy(Request $request,
                            Section $section,
                            Enrollment $enrollment,
                            Submission $submission,
                            SubmissionFile $submissionFile)
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('destroy', $section);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            DB::beginTransaction();
            $course = $section->course;
            $section_name = $section->name;
            $enrolled_user_ids = $section->enrolledUsers(true)->pluck('user_id')->toArray();

            foreach ($course->assignments as $assignment) {
                $assignment->scores()->whereIn('user_id', $enrolled_user_ids)->delete();
                $assignment->seeds()->whereIn('user_id', $enrolled_user_ids)->delete();
                $submission->where('assignment_id', $assignment->id)
                    ->whereIn('user_id', $enrolled_user_ids)
                    ->delete();
                $submissionFile->where('assignment_id', $assignment->id)
                    ->whereIn('user_id', $enrolled_user_ids)
                    ->delete();
            }
            $enrollment->where('section_id', $section->id)->whereIn('user_id', $enrolled_user_ids)->delete();

            $section->graders()->delete();

            $section->delete();
            DB::commit();
            $response['message'] = "<strong>$section_name</strong> has been deleted.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the section.  Please try again or contact us for assistance.";

        }
        return $response;
    }

    public function index(Request $request, Course $course)
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('view', $course);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $response['sections'] = $course->sections;
            $response['type'] = 'success';

        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your sections.  Please try again or contact us for assistance.";

        }
        return $response;
    }

    public function refreshAccessCode(Request $request, Section $section)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('refreshAccessCode', [$section]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $section->access_code = $this->createSectionAccessCode();
            $section->save();
            $response['access_code'] = $section->access_code;
            $response['message'] = 'The access code has been refreshed.';
            $response['type'] = 'success';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error refreshing the access code.  Please try again or contact us for assistance.";


        }
        return $response;
    }

    public function store(StoreSection $request,
                          Course $course,
                          Section $section,
                          Enrollment $enrollment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$section, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();

        try {
            DB::beginTransaction();
            $section->name = $data['name'];
            $section->course_id = $course->id;
            $section->access_code = $this->createSectionAccessCode();
            $section->save();
            $course->enrollFakeStudent($course->id, $section->id, $enrollment);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The section <strong>{$data['name']}</strong> has been created.";
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>{$data['name']}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    public function update(StoreSection $request, Section $section)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $section);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $original_name = $section->name;
        $data = $request->validated();
        try {
            $section->name = $data['name'];
            $section->save();
            $name_changed = $original_name !== $data['name'];
            $response['type'] = $name_changed ? 'success' : 'info';
            $response['message'] = $name_changed
                ? "The section <strong>$original_name</strong> has been changed to <strong>{$data['name']}</strong>."
                : "Your new name is the same as the old name.";
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>$original_name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;

    }

}
