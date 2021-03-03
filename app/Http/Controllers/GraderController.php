<?php

namespace App\Http\Controllers;

use App\Course;
use App\Grader;
use App\GraderAccessCode;
use App\Http\Requests\StoreGrader;
use App\Http\Requests\UpdateGrader;
use App\Section;
use App\User;
use App\Exceptions\Handler;
use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GraderController extends Controller
{

    public function update(UpdateGrader $request, Course $course, User $user, Grader $grader)
    {

        $response['type'] = 'error';
        $course = Course::find($request->course_id);
        $authorized = Gate::inspect('update', [$grader, $course]);

          if (!$authorized->allowed()) {
              $response['message'] = $authorized->message();
              return $response;
          }
        try {
            $data = $request->validated();
            $course_id = $request->course_id;
            $grader_user_id = $user->id;
            $section_ids = Course::find($course_id)->sections->pluck('id')->toArray();
            DB::beginTransaction();
            $grader->whereIn('section_id', $section_ids)
                ->where('user_id', $grader_user_id )
                ->delete();
            foreach ($data['selected_sections'] as $section_id) {
                $grader = new Grader();
                $grader->user_id = $grader_user_id;
                $grader->section_id = $section_id;
                $grader->save();
            }
            $response['message'] = "The grader's sections have been updated.";
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the sections.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }

    public function store(StoreGrader $request,
                          Grader $grader,
                          GraderAccessCode $graderAccessCode,
                          Section $section)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $grader);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $data = $request->validated();
        $grader_acccess_codes = $graderAccessCode->where('access_code', $data['access_code'])
            ->get();
        if ($grader_acccess_codes->isEmpty()) {
            $response['message'] = "There are no sections associated with that access code.";
            return $response;
        }
        $course_section_names = [];
        try {
            DB::beginTransaction();
            foreach ($grader_acccess_codes as $grader_acccess_code) {
                $section = Section::find($grader_acccess_code->section_id);
                $course_section_names[] = $section->course->name . ' - ' . $section->name;
                $grader = new Grader();
                $grader->user_id = $request->user()->id;
                $grader->section_id = $section->id;
                $grader->save();
            }
            $course_section_names = implode(', ', $course_section_names);
            DB::table('grader_access_codes')->delete(['access_code' => $data['access_code']]);
            DB::commit();
            $response['message'] = "You have been added as a grader to <strong>$course_section_names</strong>.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding you as a grader.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;


    }

    public function getGradersByCourse(Request $request, Course $course, Grader $Grader)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getGraders', [$Grader, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['graders'] = [];
            foreach ($course->graderNamesAndIds() as $grader) {
                $response['graders'][] = [
                    'name' => $grader->first_name . ' ' . $grader->last_name,
                    'id' => $grader->id
                ];
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your graders.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }

    public function removeGraderFromCourse(Request $request, Course $Course, User $User, Grader $Grader)
    {
        $response['type'] = 'error';
        $course = $Course::find($request->course->id);
        $student_user = $User::find($request->user->id);
        $authorized = Gate::inspect('removeGraderFromCourse', [$Grader, $course, $student_user]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $course_section_ids = $course->sections->pluck('id')->toArray();
        try {
            $Grader->where('user_id', $student_user->id)->
            whereIn('section_id', $course_section_ids)
                ->delete();
            $response['type'] = 'success';
            $response['message'] = 'The grader has been removed from your course.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing your grader.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }
}
