<?php

namespace App\Policies;

use App\AssignmentTemplate;
use App\Helpers\Helper;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class AssignmentTemplatePolicy
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
            : Response::deny('You are not allowed to get the assignment templates.');
    }

    /**
     * @param User $user
     * @return Response
     */
    public function store(User $user): Response
    {
        return (int)$user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to save assignment templates.');
    }

    /**
     * @param User $user
     * @param AssignmentTemplate $assignmentTemplate
     * @return Response
     */
    public function show(User $user, AssignmentTemplate $assignmentTemplate): Response
    {
        return $assignmentTemplate->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve this assignment template.');
    }

    /**
     * @param User $user
     * @param AssignmentTemplate $assignmentTemplate
     * @return Response
     */
    public function update(User $user, AssignmentTemplate $assignmentTemplate): Response
    {
        return $assignmentTemplate->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to update this assignment template.');
    }

    /**
     * @param User $user
     * @param AssignmentTemplate $assignmentTemplate
     * @return Response
     */
    public function destroy(User $user, AssignmentTemplate $assignmentTemplate): Response
    {
        return $assignmentTemplate->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to delete this assignment template.');
    }

    /**
     * @param User $user
     * @param AssignmentTemplate $assignmentTemplate
     * @return Response
     */
    public function copy(User $user, AssignmentTemplate $assignmentTemplate): Response
    {
        return $assignmentTemplate->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to copy this assignment template.');
    }

    public function order(User $user, AssignmentTemplate $assignmentTemplate, $ordered_assignment_templates): Response
    {
        $owner_assignment_templates = DB::table('assignment_templates')
            ->where('user_id', $user->id)
            ->select('id')
            ->pluck('id')
            ->toArray();
        $has_access = true;
        foreach ($ordered_assignment_templates as $ordered_assignment_template) {
            if (!in_array($ordered_assignment_template, $owner_assignment_templates)) {
                $has_access = false;
            }
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to re-order an assignment template that is not yours.');
    }


}
