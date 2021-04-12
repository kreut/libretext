<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Assignment;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Traits\DateFormatter;
use Illuminate\Http\Request;
use App\Http\Requests\StoreExtension;
use Illuminate\Support\Facades\DB;

use App\Exceptions\Handler;
use \Exception;

class ExtensionController extends Controller
{
    use DateFormatter;


    /**
     * @param StoreExtension $request
     * @param Assignment $assignment
     * @param User $user
     * @param Extension $extension
     * @return array
     * @throws Exception
     */
    public function store(StoreExtension $request, Assignment $assignment, User $user, Extension $extension)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$extension, $assignment->id, $user->id]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }


        try {


            $data = $request->validated();

            Extension::updateOrCreate(
                ['user_id' => $user->id, 'assignment_id' => $assignment->id],
                ['extension' => $this->convertLocalMysqlFormattedDateToUTC($data['extension_date'] . ' ' . $data['extension_time'], Auth::user()->time_zone)]
            );

            $response['type'] = 'success';
            $response['message'] = 'The student has been given an extension.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the extension.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param User $user
     * @param Extension $extension
     * @return array
     * @throws Exception
     */
    public function show(Request $request, Assignment $assignment, User $user, Extension $extension)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('view', [$extension, $assignment->id, $user->id]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assign_to_user = DB::table('assign_to_users')
                ->join('assign_to_timings', 'assign_to_users.assign_to_timing_id', '=', 'assign_to_timings.id')
                ->where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->first();
            $extension = DB::table('extensions')
                ->where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->first();
            $response['type'] = 'success';
            $response['extension_date'] = '';
            $response['extension_time'] = '';
            $response['originally_due'] =  $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assign_to_user->due, Auth::user()->time_zone, 'F d, Y \a\t g:i a');
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
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the extension.  Please try again or contact us for assistance.";
        }
        return $response;

    }


}
