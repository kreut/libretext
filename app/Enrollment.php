<?php

namespace App;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Enrollment extends Model
{

    protected $guarded = [];


    public function completeEnrollmentDetails($user_id, Section $section, $course_id, $actual_student)
    {
        $assignToUser = new AssignToUser();
        $section_id = $section->id;
        $this->user_id = $user_id;
        $this->section_id = $section_id;
        $this->course_id = $course_id;
        $this->save();
        $course = Course::find($course_id);
        if (!$course->shown) {
            $course->shown = 1;
            $course->save();
        }
        $assignments = $section->course->assignments;
        $assignToUser->assignToUserForAssignments($assignments, $user_id, $section->id);

        if ($actual_student) {
            $data_shops_enrollment = DB::table('data_shops_enrollments')
                ->where('course_id', $course_id)
                ->first();
            try {
                if (!$data_shops_enrollment) {
                    $course_info = DB::table('courses')
                        ->join('users', 'courses.user_id', '=', 'users.id')
                        ->join('schools', 'courses.school_id', '=', 'schools.id')
                        ->select('term',
                            'courses.name AS course_name',
                            'schools.name AS school_name',
                            DB::raw('CONCAT(first_name, " " , last_name) AS instructor_name'))
                        ->where('courses.id', $course_id)
                        ->first();

                    $data = ['course_id' => $course_id,
                        'course_name' => $course_info->course_name,
                        'school_name' => $course_info->school_name,
                        'term' => $course_info->term,
                        'instructor_name' => $course_info->instructor_name,
                        'number_of_enrolled_students' => 1,
                        'created_at' => now(),
                        'updated_at' => now()];
                    DB::table('data_shops_enrollments')->insert($data);
                } else {
                    DB::table('data_shops_enrollments')->where(['course_id' => $course_id])
                        ->update(['number_of_enrolled_students' => $data_shops_enrollment->number_of_enrolled_students + 1,
                            'updated_at' => now()]);
                }
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
            }
        }

        $notification_exists = DB::table('notifications')->where('user_id', $user_id)->first();
        if ($actual_student && !$notification_exists) {
            $notification_data = [
                'user_id' => $user_id,
                'hours_until_due' => 24,
                'created_at' => now(),
                'updated_at' => now()];
            DB::table('notifications')->insert($notification_data);
        }
    }

    public function removeAllRelatedEnrollmentInformation(User             $user,
                                                          int              $course_id,
                                                          AssignToUser     $assignToUser,
                                                          Assignment       $assignment,
                                                          Submission       $submission,
                                                          SubmissionFile   $submissionFile,
                                                          Score            $score,
                                                          Extension        $extension,
                                                          LtiGradePassback $ltiGradePassback,
                                                          Seed             $seed,
                                                          ExtraCredit      $extraCredit,
                                                          Section          $section)
    {
        $assignments_to_remove_ids = [];
        $assign_to_timings_to_remove_ids = [];
        $assignment_timings_and_assignment_info = $assignToUser->assignToTimingsAndAssignmentsByAssignmentIdByCourse($course_id);
        foreach ($assignment_timings_and_assignment_info as $value) {
            $assignments_to_remove_ids[] = $value->assignment_id;
            $assign_to_timings_to_remove_ids[] = $value->assign_to_timing_id;
        }
        $assignment->removeUserInfo($user,
            $assignments_to_remove_ids,
            $assign_to_timings_to_remove_ids,
            $submission,
            $submissionFile,
            $score,
            $assignToUser,
            $extension,
            $ltiGradePassback,
            $seed);

        $extraCredit->where('user_id', $user->id)->where('course_id', $course_id)->delete();
        DB::table('enrollments')->where('user_id', $user->id)->where('section_id', $section->id)->delete();
    }

    public function firstNonFakeStudent($section_id)
    {
        return DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->where('enrollments.section_id', $section_id)
            ->where('users.fake_student', 0)
            ->select('users.id')
            ->first()
            ->id;
    }


    public function fakeStudent($section_id)
    {
        return DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->where('enrollments.section_id', $section_id)
            ->where('users.fake_student', 1)
            ->select('users.id')
            ->first()
            ->id;
    }

    public function enrolledUsers()
    {
        return $this->hasMany('App\User');
    }

    /**
     * @param int $role
     * @param Course $course
     * @param int $section_id
     * @return mixed
     */
    public function getEnrolledUsersByRoleCourseSection(int $role, Course $course, int $section_id)
    {
        $enrolled_users = collect([]);
        switch ($section_id === 0) {
            case(true):
                $grader = new Grader();
                $enrolled_users = in_array($role, [2, 3, 6])
                    ? $course->enrolledUsers
                    : $grader->enrollmentsByCourse($course->id);

                break;
            case(false):
                $section = new Section();
                $enrolled_users = $section->find($section_id)->enrolledUsers;
                break;
        }
        return $enrolled_users;
    }

    /**
     * @return Collection
     */
    public function index(): Collection
    {
        return DB::table('courses')
            ->join('sections', 'courses.id', '=', 'sections.course_id')
            ->join('enrollments', 'sections.id', '=', 'enrollments.section_id')
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->where('enrollments.user_id', '=', auth()->user()->id)
            ->where('courses.shown', 1)
            ->select(DB::raw('CONCAT(first_name, " " , last_name) AS instructor'),
                DB::raw('CONCAT(courses.name, " - " , sections.name) AS course_section_name'),
                'courses.start_date',
                'courses.end_date',
                'courses.id',
                'courses.public_description',
                'courses.lms',
                'courses.lms_only_entry')
            ->get();
    }
}
