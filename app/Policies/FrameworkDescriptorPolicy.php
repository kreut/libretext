<?php

namespace App\Policies;

use App\FrameworkDescriptor;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class FrameworkDescriptorPolicy
{
    use HandlesAuthorization;

    public function move(User $user, FrameworkDescriptor $frameworkDescriptor, int $level_from_id, int $level_to_id, int $descriptor_id)
    {
        $has_access = true;
        $message = '';
        $from_has_access = DB::table('framework_levels')
            ->join('frameworks', 'framework_levels.framework_id', '=', 'frameworks.id')
            ->join('framework_level_framework_descriptor', 'framework_levels.id', '=', 'framework_level_framework_descriptor.framework_level_id')
            ->where('framework_levels.id', $level_from_id)
            ->where('framework_descriptor_id', $descriptor_id)
            ->where('user_id', $user->id)
            ->first();
        $to_has_access =
            DB::table('framework_levels')
                ->join('frameworks', 'framework_levels.framework_id', '=', 'frameworks.id')
                ->where('framework_levels.id', $level_to_id)
                ->where('user_id', $user->id)
                ->first();
        if (!$from_has_access) {
            $message = "That descriptor doesn't exist in one of your frameworks.";
            $has_access = false;
        }
        if (!$to_has_access) {
            $message = "You cannot move the descriptor to a framework level that you do not own.";
            $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);


    }

    public function destroy(User $user, FrameworkDescriptor $frameworkDescriptor): Response
    {
        $has_access = DB::table('framework_descriptors')
            ->join('framework_level_framework_descriptor', 'framework_descriptors.id', '=', 'framework_level_framework_descriptor.framework_descriptor_id')
            ->join('framework_levels', 'framework_level_framework_descriptor.framework_level_id', '=', 'framework_levels.id')
            ->join('frameworks', 'framework_levels.framework_id', '=', 'frameworks.id')
            ->where('framework_descriptors.id', $frameworkDescriptor->id)
            ->where('user_id', $user->id)
            ->first();
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to remove that descriptor from the framework.');

    }

    public function store(User $user, FrameworkDescriptor $frameworkDescriptor, int $framework_level_id): Response
    {
        $has_access = DB::table('framework_levels')
            ->join('frameworks', 'framework_levels.framework_id', '=', 'frameworks.id')
            ->where('framework_levels.id', $framework_level_id)
            ->where('user_id', $user->id)
            ->first();
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to add descriptors to that framework level.');

    }

    public function update(User $user, FrameworkDescriptor $frameworkDescriptor): Response
    {

        $has_access = DB::table('framework_descriptors')
            ->join('framework_level_framework_descriptor', 'framework_descriptors.id', '=', 'framework_level_framework_descriptor.framework_descriptor_id')
            ->join('framework_levels', 'framework_level_framework_descriptor.framework_level_id', '=', 'framework_levels.id')
            ->join('frameworks', 'framework_levels.framework_id', '=', 'frameworks.id')
            ->where('framework_descriptors.id', $frameworkDescriptor->id)
            ->where('user_id', $user->id)
            ->first();
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to update that descriptor.');

    }
}
