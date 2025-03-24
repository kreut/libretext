<?php

namespace App;

use App\Custom\LTIDatabase;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Overrides\IMSGlobal\LTI\LTI_Names_Roles_Provisioning_Service;
use Overrides\IMSGlobal\LTI\LTI_Service_Connector;

class LtiNamesAndRoles extends Model
{
    /**
     * @throws Exception
     */
    public
    function existsInLmsRoster(LtiNamesAndRolesUrl $ltiNamesAndRolesUrl,
                               LTIDatabase  $LTIDatabase,
                               Course $course,
                               User $user): bool
    {

        $names_and_roles_url = $ltiNamesAndRolesUrl->where('course_id', $course->id)->first();
        if (!$names_and_roles_url) {
            throw new Exception ("Course ID $course->id should have a names and roles URL but does not.");
        }

        $lti_registration = $course->getLtiRegistration();
        $url = $names_and_roles_url->url;
        $service_connector = new LTI_Service_Connector($LTIDatabase->find_registration_by_client_id($lti_registration->client_id));
        $names_and_roles = new LTI_Names_Roles_Provisioning_Service($service_connector,
            ['context_memberships_url' => $url]);
        $members = $names_and_roles->get_members();
        $exists_in_lms_roster = false;
        foreach ($members as $member) {
            if (isset($member['email']) && $member['email'] === $user->email) {
                $user->lms_user_id = $member['user_id'];
                $user->save();
                $exists_in_lms_roster = true;
            }
        }
        return $exists_in_lms_roster;
    }
}
