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
            $response['message'] = "$user->first_name $user->last_name given an extension for $assignment->name.";

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
            $response = $extension->show($assignment, $user);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the extension.  Please try again or contact us for assistance.";
        }
        return $response;

    }


}
