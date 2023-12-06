<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;

class LmsAPI extends Model
{
    /**
     * @param int $assignment_group_id
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function handleAssignmentGroup(int $assignment_group_id, Course $course): array
    {
        $response['type'] = 'error';
        $assignment_group = AssignmentGroup::find($assignment_group_id)->assignment_group;
        $lms_result = $this->getAssignmentGroups($course->getLtiRegistration(), $course->user_id, $course->lms_course_id);
        if ($lms_result['type'] === 'error') {
            $response['message'] = 'Error getting the assignment groups from your LMS: ' . $lms_result['message'];
        } else {
            $lms_assignment_group_id = 0;
            foreach ($lms_result['message'] as $lms_assignment_group_info) {
                if ($lms_assignment_group_info->name === $assignment_group) {
                    $lms_assignment_group_id = $lms_assignment_group_info->id;
                }
            }
            if (!$lms_assignment_group_id) {
                $lms_result = $this->createAssignmentGroup($course->getLtiRegistration(), $course->user_id, $course->lms_course_id, $assignment_group);
                $lms_assignment_group_id = $lms_result['message']->id;
                if ($lms_result['type'] === 'error') {
                    $response['message'] = 'Error creating the assignment group on your LMS: ' . $lms_result['message'];
                    return $response;
                }
            }
            $response['type'] = 'success';
            $response['lms_assignment_group_id'] = $lms_assignment_group_id;
        }
        return $response;
    }

    /**
     * @param object $lti_registration
     * @param int $user_id
     * @param $course_id
     * @param string $assignment_group
     * @return array
     * @throws Exception
     */
    public function createAssignmentGroup(object $lti_registration, int $user_id, $course_id, string $assignment_group): array
    {
        switch ($lti_registration->iss) {
            case('https://canvas.instructure.com'):
            case('https://dev-canvas.libretexts.org'):
                $canvasAPI = new CanvasAPI($lti_registration, $user_id);
                $response = $canvasAPI->createAssignmentGroup($course_id, $assignment_group);
                break;
            default:
                throw new Exception("$lti_registration->iss is not set up to create assignments through the LMS API.");

        }
        return $response;
    }


    /**
     * @param object $lti_registration
     * @param int $user_id
     * @param $course_id
     * @return array
     * @throws Exception
     */
    public function getAssignmentGroups(object $lti_registration, int $user_id, $course_id): array
    {
        switch ($lti_registration->iss) {
            case('https://canvas.instructure.com'):
            case('https://dev-canvas.libretexts.org'):
                $canvasAPI = new CanvasAPI($lti_registration, $user_id);
                $response = $canvasAPI->getAssignmentGroups($course_id);
                break;
            default:
                throw new Exception(" $lti_registration->iss is not set up to update assignments through the LMS API.");

        }
        return $response;
    }

    /**
     * @param object $lti_registration
     * @param int $user_id
     * @param $course_id
     * @param $assignment_id
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function updateAssignment(object $lti_registration, int $user_id, $course_id, $assignment_id, array $data): array
    {
        switch ($lti_registration->iss) {
            case('https://canvas.instructure.com'):
            case('https://dev-canvas.libretexts.org'):
                $canvasAPI = new CanvasAPI($lti_registration, $user_id);
                $response = $canvasAPI->updateAssignment($course_id, $assignment_id, $data);
                break;
            default:
                throw new Exception(" $lti_registration->iss is not set up to update assignments through the LMS API.");

        }
        return $response;
    }

    /**
     * @param object $lti_registration
     * @param int $user_id
     * @param $course_id
     * @param $assignment_id
     * @return array
     * @throws Exception
     */
    public function deleteAssignment(object $lti_registration, int $user_id, $course_id, $assignment_id): array
    {
        switch ($lti_registration->iss) {
            case('https://canvas.instructure.com'):
            case('https://dev-canvas.libretexts.org'):
                $canvasAPI = new CanvasAPI($lti_registration, $user_id);
                $response = $canvasAPI->deleteAssignment($course_id, $assignment_id);
                break;
            default:
                throw new Exception("$lti_registration->iss is not set up to delete assignments through the LMS API.");

        }
        return $response;
    }


    /**
     * @param object $lti_registration
     * @param int $user_id
     * @param int $course_id
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function createAssignment(object $lti_registration, int $user_id, int $course_id, array $data): array
    {
        switch ($lti_registration->iss) {
            case('https://canvas.instructure.com'):
            case('https://dev-canvas.libretexts.org'):
                $canvasAPI = new CanvasAPI($lti_registration, $user_id);
                $response = $canvasAPI->createAssignment($course_id, $data);
                break;
            default:
                throw new Exception("$lti_registration->iss is not set up to create assignments through the LMS API.");

        }
        return $response;
    }

    /**
     * @param object $lti_registration
     * @param int $user_id
     * @param $course_id
     * @return array
     * @throws Exception
     */
    public function getAssignments(object $lti_registration, int $user_id, $course_id): array
    {
        switch ($lti_registration->iss) {
            case('https://canvas.instructure.com'):
            case('https://dev-canvas.libretexts.org'):
                $canvasAPI = new CanvasAPI($lti_registration, $user_id);
                $response = $canvasAPI->getAssignments($course_id);
                break;
            default:
                throw new Exception("$lti_registration->iss is not set up to get assignments through the LMS API.");

        }
        return $response;

    }


    /**
     * @param string $iss
     * @param $course_id
     * @return string
     * @throws Exception
     */
    public function getCourseUrl(string $iss, $course_id): string
    {
        switch ($iss) {
            case('https://canvas.instructure.com'):
            case('https://dev-canvas.libretexts.org'):
                $url = "$iss/courses/$course_id";
                break;
            default:
                throw new Exception("$iss is not set up to get a course URL through the LMS API.");

        }
        return $url;

    }

    /**
     * @throws Exception
     */
    public function getCourse(object $lti_registration,int $user_id,  $course_id): array
    {
        switch ($lti_registration->iss) {
            case('https://canvas.instructure.com'):
            case('https://dev-canvas.libretexts.org'):
                $canvasAPI = new CanvasAPI($lti_registration, $user_id);
                $response = $canvasAPI->getCourse($course_id);
                break;
            default:
                throw new Exception("$lti_registration->iss is not set up to get a course through the LMS API.");

        }
        if ($response['type'] === 'success') {
            $response['course'] = $response['message'];
        }
        return $response;
    }


    /**
     * @throws Exception
     */
    public function getCourses(object $lti_registration, int $user_id): array
    {
        switch ($lti_registration->iss) {
            case('https://canvas.instructure.com'):
            case('https://dev-canvas.libretexts.org'):
                $canvasAPI = new CanvasAPI($lti_registration, $user_id);
                $response = $canvasAPI->getCourses();
                if ($response['type'] === 'success') {
                    $courses = $response['message'];
                    $message = [];
                    foreach ($courses as $course) {
                        if (property_exists($course, 'access_restricted_by_date') && $course->access_restricted_by_date) {
                            continue;
                        } else {
                            $message[] = $course;
                        }
                    }
                    $response['message'] = $message;
                }
                break;
            default:
                throw new Exception("$lti_registration->iss is not set up to create courses through the LMS API.");

        }
        if ($response['type'] === 'success') {
            $response['courses'] = $response['message'];
        }
        return $response;
    }

}
