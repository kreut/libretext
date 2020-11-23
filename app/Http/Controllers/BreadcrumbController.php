<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Assignment;
use App\Course;
use App\Exceptions\Handler;

use \Exception;

class BreadcrumbController extends Controller
{
    public function index(Request $request)
    {
        $name = $request->name;
        $params = $request->params;
        $course_id = $params['courseId'] ?? 0;
        $assignment_id = $params['assignmentId'] ?? 0;
        $course = $assignment = null;
        if ($course_id) {
            $course = Course::find($course_id);
        }
        if ($assignment_id) {
            $assignment = Assignment::find($assignment_id);
        }
        $users = (Auth::user()->role === 3) ? 'students' : 'instructors';
        $response['type'] = 'error';
        $breadcrumbs = [];
        try {
            if (Auth::check()) {
                $breadcrumbs[0] = ['text' => 'My Courses', 'href' => "/$users/courses"];
                switch ($name) {
                    case('settings.profile'):
                    case('settings.password'):
                        $breadcrumbs[] = ['text' => 'Settings',
                            'href' => "#",
                            'active' => true];
                        break;
                    case('instructors.assignments.index'):
                        //My courses / the assignment's course
                        $breadcrumbs[] = ['text' => $course->name,
                            'href' => "/instructors/courses/{$course->id}/assignments",
                            'active' => true];
                        break;
                    case('students.assignments.index'):
                        $breadcrumbs[] = ['text' => $course->name,
                            'href' => "/students/courses/{$course->id}/assignments",
                            'active' => true];
                        break;
                    case('assignments.summary'):
                        //My courses / The assignment's course / that assignment;
                        $breadcrumbs[] = ['text' => $assignment->course->name,
                            'href' => "/$users/courses/{$assignment->course->id}/assignments"];
                        $breadcrumbs[] = ['text' => $assignment->name,
                            'href' => "#",
                            'active' => true];
                        break;
                    case('questions.view'):
                        //My courses / The assignment's course / that assignment summary / the assignment questions

                        $breadcrumbs[] = ['text' => $assignment->course->name,
                            'href' => "/$users/courses/{$assignment->course->id}/assignments"];

                        if (Auth::user()->role === 3) {
                            if ($assignment->instructions || $assignment->students_can_view_assignment_statistics) {
                                $breadcrumbs[] = ['text' => "{$assignment->name}",
                                    'href' => "/assignments/{$assignment_id}/summary"];
                            }
                        } else {
                            $breadcrumbs[] = ['text' => "{$assignment->name}",
                                'href' => "/assignments/{$assignment_id}/summary"];
                        }
                        $breadcrumbs[] = ['text' => "View Questions",
                            'href' => "#",
                            'active' => true];

                        break;
                    case('questions.get'):
                        $assignment_id = $params['assignmentId'];
                        $breadcrumbs[] = ['text' => $assignment->course->name,
                            'href' => "/instructors/courses/{$assignment->course->id}/assignments"];
                        $breadcrumbs[] = ['text' => $assignment->name,
                            'href' => "/assignments/{$assignment_id}/summary"];
                        $breadcrumbs[] = ['text' => "Add Questions",
                            'href' => "#",
                            'active' => true];
                        break;
//My courses / The assignment's course / that assignment / questions get
                    case('scores.index'):
                        //My courses / that course
                        $breadcrumbs[] = ['text' => $course->name,
                            'href' => "/instructors/courses/{$course->id}/assignments"];
                        $breadcrumbs[] = ['text' => 'Scores',
                            'href' => "#",
                            'active' => true];
                        break;
                    case('assignment.files.index'):
                        $breadcrumbs[] = ['text' => $assignment->course->name,
                            'href' => "/instructors/courses/{$assignment->course->id}/assignments"];
                        $breadcrumbs[] = ['text' => 'Grade File Submissions',
                            'href' => "#",
                            'active' => true];
                        break;
                    case('question.view'):
                        $breadcrumbs[] = ['text' => $assignment->course->name,
                            'href' => "/instructors/courses/{$assignment->course->id}/assignments"];
                        if (in_array($assignment->submission_files, ['q', 'a'])) {
                            $type = $assignment->submission_files === 'q' ? 'question' : 'assignment';
                            $breadcrumbs[] = ['text' => 'Grade File Submissions',
                                'href' => "/assignments/{$assignment->id}/$type-files"];
                        }
                        $breadcrumbs[] = ['text' => 'View Question',
                            'href' => "#",
                            'active' => true];
                }
            }

            $response['type'] = 'success';
            $response['breadcrumbs'] = $breadcrumbs;
        } catch (Exception $e) {
            //no message for the user: just for me
            $h = new Handler(app());
            $h->report($e);
        }
        return $response;

    }
}
