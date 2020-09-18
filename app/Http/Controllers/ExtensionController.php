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
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
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
                [ 'user_id' => $user->id, 'assignment_id' => $assignment->id],
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
     * Display the specified resource.
     *
     * @param \App\Extension $extension
     * @return \Illuminate\Http\Response
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
            $extension = DB::table('extensions')
                ->where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->first();
            $response['type'] = 'success';
            $response['extension_date'] = '';
            $response['extension_time'] = '';
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
