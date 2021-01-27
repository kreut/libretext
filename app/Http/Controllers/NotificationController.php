<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Notification;
use \Exception;
use App\Http\Requests\UpdateHoursUntilDue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function update(UpdateHoursUntilDue $request, Notification $notification)
    {

        $response['type'] = 'error';
        $data = $request->validated();

        try {
            $user_id = $request->user()->id;
            DB::beginTransaction();
            $notification->where('user_id', $user_id)->delete();
            if ($data['hours_until_due']) {
                $notification->create(['user_id' => $user_id,
                    'hours_until_due' => $data['hours_until_due']
                ]);
            }
            $response['message'] = 'Your notification preference has been updated.';
            $response['type'] = 'success';
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating this notification.  Please try again by refreshing the page or contact us for assistance.";

        }
        return $response;

    }
}
