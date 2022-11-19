<?php

namespace App\Policies;

use App\FrameworkLevel;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class FrameworkLevelPolicy
{
    use HandlesAuthorization;

    private function _ownsFramework($user, $framework_id)
    {
        return DB::table('frameworks')
            ->where('frameworks.id', $framework_id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function getAllChildren(User $user, FrameworkLevel $frameworkLevel, int $framework_level_id): Response
    {
        return $this->_ownsFrameworkLevel($user->id, $framework_level_id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the children for this framework level.');
    }

    public function destroy(User $user, FrameworkLevel $frameworkLevel, int $framework_level_id): Response
    {

        return $this->_ownsFrameworkLevel($user->id, $framework_level_id)
            ? Response::allow()
            : Response::deny('You are not allowed to delete that framework level.');

    }

    /**
     * @param User $user
     * @param FrameworkLevel $frameworkLevel
     * @param int $framework_level_id
     * @return Response
     */
    public function update(User $user, FrameworkLevel $frameworkLevel, int $framework_level_id): Response
    {

        return $this->_ownsFrameworkLevel($user->id, $framework_level_id)
            ? Response::allow()
            : Response::deny('You are not allowed to update that framework level.');

    }

    public function store(User $user, FrameworkLevel $frameworkLevel, int $framework_id): Response
    {
        return $this->_ownsFramework($user, $framework_id)
            ? Response::allow()
            : Response::deny('You are not allowed to add new framework levels to this framework.');

    }

    private function _ownsFrameworkLevel($user_id, $framework_level_id)
    {
        return DB::table('framework_levels')
            ->join('frameworks', 'framework_levels.framework_id', '=', 'frameworks.id')
            ->where('framework_levels.id', $framework_level_id)
            ->where('user_id', $user_id)
            ->first();
    }

    /**
     * @param User $user
     * @param FrameworkLevel $frameworkLevel
     * @return Response
     */
    public function getFrameworkLevelsWithSameParent(User $user, FrameworkLevel $frameworkLevel): Response
    {
        return $this->_ownsFrameworkLevel($user->id, $frameworkLevel->id)
            ? Response::allow()
            : Response::deny("You are not allowed to get the framework levels with the same parent of the current level.");


    }

    /**
     * @param User $user
     * @param FrameworkLevel $frameworkLevel
     * @param int $level_id
     * @return Response
     */
    public function changePosition(User $user, FrameworkLevel $frameworkLevel, int $level_id): Response
    {

        return $this->_ownsFrameworkLevel($user->id, $level_id)
            ? Response::allow()
            : Response::deny("You are not allowed to change the position of that framework level.");
    }

    public function moveLevel(User $user, FrameworkLevel $frameworkLevel, int $level_from, int $level_to): Response
    {
        $has_access = true;
        $message = '';
        if (!$this->_ownsFrameworkLevel($user->id, $level_from)) {
            $message = "You do not own the 'level from' framework level'.";
            $has_access = false;
        }
        if ($level_to && !$this->_ownsFrameworkLevel($user->id, $level_to)) {
            $message = "You do not own the 'level to' framework level'.";
            $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);

    }

    /**
     * @param User $user
     * @param FrameworkLevel $frameworkLevel
     * @param int $framework_id
     * @return Response
     */
    public function getFrameworkLevelChildren(User $user, FrameworkLevel $frameworkLevel, int $framework_id): Response
    {
        return $this->_ownsFramework($user, $framework_id)
            ? Response::allow()
            : Response::deny('You are not allowed to upload to get the framework levels for this framework.');
    }

    public function storeWithDescriptors(User $user, FrameworkLevel $frameworkLevel, int $framework_id): Response
    {
        return $this->_ownsFramework($user, $framework_id)
            ? Response::allow()
            : Response::deny('You are not allowed to upload to this framework.');
    }

    /**
     * @param User $user
     * @param FrameworkLevel $frameworkLevel
     * @param int $framework_id
     * @return Response
     */
    public function upload(User $user, FrameworkLevel $frameworkLevel, int $framework_id): Response
    {

        return $this->_ownsFramework($user, $framework_id)
            ? Response::allow()
            : Response::deny('You are not allowed to upload to this framework.');
    }

    /**
     * @param User $user
     * @return Response
     */
    public function getTemplate(User $user): Response
    {
        return $user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to get a framework template.');
    }
}
