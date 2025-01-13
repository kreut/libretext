<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\MetaTag;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MetaTagPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param MetaTag $metaTag
     * @param bool $admin_view
     * @return Response
     */
    public function getMetaTagsByFilter(User $user, MetaTag $metaTag, bool $admin_view): Response
    {
        $has_access = true;
        $message = '';
        switch ($admin_view) {
            case(true):
                if (!Helper::isAdmin()) {
                    $message = 'You are not allowed to retrieve the meta-tags from the database; you claim to be an admin but are not.';
                    $has_access = false;
                }
                break;
            case(false):
                if (!in_array($user->role, [2, 5])) {
                    $message = 'You are not allowed to retrieve the meta-tags from the database; you must be an instructor or non-instructor question editor.';
                    $has_access = false;
                }
                break;
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);

    }

    public function update(User $user, MetaTag $metaTag, array $filter_by): Response
    {
        return in_array($user->role, [2, 5])
            ? Response::allow()
            : Response::deny('You are not allowed to update the meta-tags.');

    }


}
