<?php

namespace App\Http\Controllers;

use App\CoInstructor;
use App\ContactGraderOverride;
use App\Course;
use App\CourseOrder;
use App\Exceptions\Handler;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CoInstructorController extends Controller
{


    /**
     * @param Course $course
     * @param User $user
     * @param CoInstructor $coInstructor
     * @param CourseOrder $courseOrder
     * @param ContactGraderOverride $contactGraderOverride
     * @return array
     * @throws Exception
     */
    public function destroy(Course                $course,
                            User                  $user,
                            CoInstructor          $coInstructor,
                            CourseOrder           $courseOrder,
                            ContactGraderOverride $contactGraderOverride): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('destroy', [$coInstructor, $course]);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $co_instructor_name = $user->getFullName($user->id);
            DB::beginTransaction();
            $coInstructor->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->delete();
            $courseOrder->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->delete();
            if ($contactGraderOverride->where('user_id', $user->id)
                ->where('course_id', $course->id)->exists()) {
                $contactGraderOverride->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->update(['user_id' => $course->user_id]);
            }
            $courseOrder->reOrderAllCourses($user);
            $response['type'] = 'info';

            $response['message'] = "$co_instructor_name is no longer a co-instructor.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the co-instructor.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param CoInstructor $coInstructor
     * @param User $user
     * @param CourseOrder $courseOrder
     * @return array
     * @throws Exception
     */
    public function store(Request      $request,
                          CoInstructor $coInstructor,
                          User         $user,
                          CourseOrder  $courseOrder): array
    {
        try {
            $response['type'] = 'error';
            $pending_co_instructor = $coInstructor->where('access_code', $request->access_code)->first();
            if (!$pending_co_instructor) {
                $response['message'] = 'That does not appear to be a valid link.  Please ask the course instructor for another link.';
                return $response;
            }
            DB::beginTransaction();
            $pending_co_instructor->status = 'accepted';
            $pending_co_instructor->access_code = null;
            $pending_co_instructor->save();
            $course = Course::find($pending_co_instructor->course_id);
            $instructor_name = $user->getFullName($course->user_id);
            $max_course_order = count($courseOrder->where('user_id', $pending_co_instructor->user_id)->get());
            $courseOrder = new CourseOrder();
            $courseOrder->order = $max_course_order + 1;
            $courseOrder->user_id = $pending_co_instructor->user_id;
            $courseOrder->course_id = $pending_co_instructor->course_id;
            $courseOrder->save();
            $response['type'] = 'success';
            $response['message'] = "You are now a co-instructor for $course->name, which is taught by $instructor_name.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding you as a co-instructor.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
