<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AutoRelease;
use App\Course;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AutoReleaseController extends Controller
{
    /**
     * @param Assignment $assignment
     * @param AutoRelease $autoRelease
     * @return array
     * @throws Exception
     */
    public function getStatuses(Assignment $assignment, AutoRelease $autoRelease): array
    {
        $response['type'] = 'error';
        try {
            $auto_release_statuses = [];
            $auto_release_keys = $autoRelease->keys();
            foreach ($auto_release_keys as $key) {
                $auto_release_statuses[$key] = $assignment->{$key};
            }
            $response['type'] = 'success';
            $response['auto_release_statuses'] = $auto_release_statuses;
            return $response;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get ";
        }
        return $response;

    }

    /**
     * @param Assignment $assignment
     * @param Course $course
     * @param AutoRelease $autoRelease
     * @return array
     * @throws Exception
     */
    public function compareAssignmentToCourseDefault(Assignment  $assignment,
                                                     Course      $course,
                                                     AutoRelease $autoRelease): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('compareAssignmentToCourseDefault', $autoRelease);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $non_matching_auto_releases = [];
            if (!$assignment->formative && $assignment->assessment_type !== 'clicker') {
                $assignment_auto_release = $autoRelease->where('type', 'assignment')->where('type_id', $assignment->id)->first();

                $assignment_auto_releases = ['shown' => null,
                    'show_scores' => null,
                    'solutions_released' => null,
                    'students_can_view_assignment_statistics' => null];


                if ($assignment_auto_release) {
                    $assignment_auto_releases = [
                        'shown' => $assignment_auto_release->auto_release_shown,
                        'show_scores' => $assignment_auto_release->auto_release_show_scores,
                        'show_scores_after' => $assignment_auto_release->auto_release_show_scores_after,
                        'solutions_released' => $assignment_auto_release->auto_release_solutions_released,
                        'solutions_released_after' => $assignment_auto_release->auto_release_solutions_released_after,
                        'students_can_view_assignment_statistics' => $assignment_auto_release->auto_release_students_can_view_assignment_statistics,
                        'students_can_view_assignment_statistics_after' => $assignment_auto_release->auto_release_students_can_view_assignment_statistics_after];
                }

                $course_default_auto_releases = [
                    'shown' => $course->auto_release_shown,
                    'show_scores' => $course->auto_release_show_scores,
                    'show_scores_after' => $assignment->assessment_type !== 'real time' ? $course->auto_release_show_scores_after : null,
                    'solutions_released' => $course->auto_release_solutions_released,
                    'solutions_released_after' => $course->auto_release_solutions_released_after,
                    'students_can_view_assignment_statistics' => $course->auto_release_students_can_view_assignment_statistics,
                    'students_can_view_assignment_statistics_after' => $course->auto_release_students_can_view_assignment_statistics_after
                ];

                $auto_release_keys = $autoRelease->keys();
                $labels['shown'] = 'Assignment';
                $labels['show_scores'] = 'Scores';
                $labels['solutions_released'] = 'Solutions';
                $labels['students_can_view_assignment_statistics'] = 'Statistics';
                foreach ($auto_release_keys as $value) {
                    if ($value === 'shown') {
                        $assignment_auto_release = $assignment_auto_releases['shown'] ? $assignment_auto_releases['shown'] . ' before your "available on"' : null;
                        $course_default_auto_release = $course_default_auto_releases['shown'] ? $course_default_auto_releases['shown'] . ' before your "available on"' : null;
                    } else {
                        $assignment_condition = $assignment_auto_releases[$value . '_after'] ?? null;
                        $course_default_condition = null;
                        if (isset($course_default_auto_releases[$value . '_after'])) {
                            if ($assignment->late_policy !== 'not accepted') {
                                $course_default_condition = $course_default_auto_releases[$value . '_after'];
                            } else {
                                $course_default_condition = 'due date';
                            }
                        }
                        $assignment_auto_release = $assignment_auto_releases[$value] ? $assignment_auto_releases[$value] . ' after your "' . $assignment_condition . '"' : null;
                        $course_default_auto_release = $course_default_auto_releases[$value] ? $course_default_auto_releases[$value] . ' after your "' . $course_default_condition . '"' : null;

                    }
                    if ($assignment_auto_release !== $course_default_auto_release) {
                        $non_matching_auto_releases[] = [
                            'key' => $value,
                            'label' => $labels[$value],
                            'assignment' => $assignment_auto_release,
                            'course_default' => $course_default_auto_release
                        ];
                    }
                }
            }
            $response['non_matching_auto_releases'] = $non_matching_auto_releases;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to compare the assignment to the course default. Please try again or contact us for assistance.";
        }
        return $response;
    }
}
