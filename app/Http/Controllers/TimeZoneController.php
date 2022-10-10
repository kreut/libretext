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

    public function update(Request $request): array
    {

        $response['type'] = 'error';
        if ($request->user()->id !== 1) {
            $response['message'] = 'No access';
            return $response;
        }
        try {
            $time_zones = $request->time_zones;
            foreach ($time_zones as $time_zone) {
                TimeZone::updateOrCreate(['value' => $time_zone['value']], ['text' => $time_zone['text']]);
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
