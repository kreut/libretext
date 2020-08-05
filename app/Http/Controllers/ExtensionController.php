<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Assignment;
use App\User;
use Illuminate\Support\Facades\Gate;
use App\Traits\DateFormatter;
use Illuminate\Http\Request;
use App\Http\Requests\StoreExtension;
use Illuminate\Support\Facades\DB;

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

            Extension::create(
                ['extension' => $data['extension_date'] . ' ' . $data['extension_time'],
                    'user_id' => $user->id,
                    'assignment_id' => $assignment->id
                ]
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

        $extension = DB::table('extensions')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first()
            ->extension;

        if ($extension) {
            $response['type'] = 'success';
            $response['extension_date'] = $this->getDateFromSqlTimestamp($extension);
            $response['extension_time'] = $this->getTimeFromSqlTimestamp($extension);

        }
        return $response;

    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Extension $extension
     * @return \Illuminate\Http\Response
     */
    public function update(StoreExtension $request, Assignment $assignment, User $user, Extension $extension)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$extension, $assignment->id, $user->id]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }


        try {


            $data = $request->validated();
            Extension::where('user_id', $user->id)
                ->where('assignment_id', $assignment->id)
                ->update(['extension' => $data['extension_date'] . ' ' . $data['extension_time']]);


            $response['type'] = 'success';
            $response['message'] = 'The extension has been updated.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating.  Please try again or contact us for assistance.";
        }
        return $response;
    }


}
