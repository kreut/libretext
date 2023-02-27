<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\TimeZone;
use Exception;
use Illuminate\Http\Request;

class TimeZoneController extends Controller
{

    public function index(TimeZone $timeZones)
    {
        $response['type'] = 'error';
        try {
            $response['time_zones'] = $timeZones->select('value','text')->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the time zones. Please try again or contact us.";

        }
        return $response;
    }

    public function update(Request $request, TimeZone $timeZone): array
    {

        $response['type'] = 'error';
        if ($request->user()->id !== 1) {
            $response['message'] = 'No access';
            return $response;
        }
        try {
            $time_zones = $request->time_zones;
            foreach ($time_zones as $time_zone) {
                if (!$timeZone->where('text',$time_zone['text'])) {
                    $timeZone = new TimeZone();
                    $timeZone->text = $time_zone['text'];
                    $timeZone->value = $time_zone['value'];
                    $timeZone->save();
                }
            }
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the time zones. Please try again or contact us.";

        }
        return $response;
    }
}
