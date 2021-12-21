<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use \App\Traits\DateFormatter;
class Extension extends Model
{
    protected $fillable = ['user_id', 'assignment_id', 'extension'];

    use DateFormatter;

    /**
     * @param Assignment $assignment
     * @param User $user
     * @return array
     */
    public function show(Assignment $assignment, User $user)
    {
        $assign_to_user = DB::table('assign_to_users')
            ->join('assign_to_timings', 'assign_to_users.assign_to_timing_id', '=', 'assign_to_timings.id')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();
        $extension = DB::table('extensions')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        $response['extension_date'] = '';
        $response['extension_time'] = '';
        $response['originally_due'] =  $assign_to_user
            ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assign_to_user->due, Auth::user()->time_zone, 'F d, Y \a\t g:i a')
            : null;
        $response['extension_warning'] = '';
        if ($assignment->show_scores) {
            $response['extension_warning'] .= "The assignment scores have been released.  ";
        }
        if ($assignment->solutions_released) {
            $response['extension_warning'] .= "The assignment solutions are available.";
        }
        if ($response['extension_warning']) {
            $response['extension_warning'] = "Before providing an extension please note that:  " . $response['extension_warning'];
        }
        if ($extension) {
            $response['extension_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($extension->extension, Auth::user()->time_zone);
            $response['extension_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($extension->extension, Auth::user()->time_zone);
        }
        return $response;
    }

    /**
     * @param $user
     * @return array
     */
    public function getUserExtensionsByAssignment($user)
    {
        $extensions = $user->extensions;

        $extensions_by_assignment = [];
        if ($extensions->isNotEmpty()) {
            foreach ($extensions as $extension) {
                $extensions_by_assignment[$extension->assignment_id] = $extension->extension;
            }
        }

        return $extensions_by_assignment;
    }

    public function getAssignmentExtensionByUser(Assignment $assignment, User $user)
    {
        $extension = DB::table('extensions')->where('user_id', $user->id)
            ->where('assignment_id', $assignment->id)
            ->first();
        if ($extension){
            return $extension->extension;
        }
        return false;
    }
}
