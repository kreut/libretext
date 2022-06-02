<?php

namespace App\Policies;

use App\Course;
use App\Grader;
use App\H5pCollection;
use App\Http\Requests\H5PCollectionImport;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class H5pCollectionPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function index(User $user): Response
    {
        return (int)$user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to get the list of H5P collections.');
    }

    /**
     * @param User $user
     * @param H5pCollection $h5PCollection
     * @param int $folder_id
     * @param int $course_to_import
     * @param $assignment_template
     * @return Response
     */
    public function validateImport(User          $user,
                                   H5PCollection $h5PCollection,
                                   int           $folder_id,
                                   int           $course_to_import,
                                                 $assignment_template): Response
    {
        $allow = true;
        $message = '';
        $owns_folder = DB::table('saved_questions_folders')
            ->where('id', $folder_id)
            ->where('user_id', $user->id)
            ->first();
        if (!$owns_folder) {
            $allow = false;
            $message = "You do not own that folder.";
        }
        if ($allow && $course_to_import) {
            $owns_course = DB::table('courses')
                ->where('id', $course_to_import)
                ->where('user_id', $user->id)
                ->first();
            if (!$owns_course) {
                $allow = false;
                $message = "You do not own that course.";
            } else {
                $owns_assignment_template = DB::table('assignment_templates')
                    ->where('user_id', $user->id)
                    ->where('id', $assignment_template)
                    ->first();
                if (!$owns_assignment_template) {
                    $allow = false;
                    $message = "You do not own that assignment template.";
                }
            }
        }
        return $allow
            ? Response::allow()
            : Response::deny($message);
    }
}
