<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\TimeZone;
use Exception;
use Illuminate\Http\Request;

class TimeZoneController extends Controller
{
    /**
     * @param TimeZone $timeZones
     * @return array
     * @throws Exception
     */
    public function index(TimeZone $timeZones): array
    {
        $response['type'] = 'error';
        try {
            $us_time_zones = [
                '' => 'Please choose a time zone',
                'divider-1' => '----------US Time Zones-------------',
                'America/New_York' => 'Eastern Time Zone',
                'America/Chicago' => 'Central Time Zone',
                'America/Denver' => 'Mountain Time Zone',
                'America/Phoenix' => 'Mountain Time Zone (no DST)',
                'America/Los_Angeles' => 'Pacific Time Zone',
                'America/Anchorage' => 'Alaska Time Zone',
                'America/Adak' => 'Hawaii-Aleutian Time Zone',
                'Pacific/Honolulu' => 'Hawaii-Aleutian Time Zone (no DST)',
                'divider-2' => '----------Non US Time Zones----------'
            ];
            $non_us_timezones = $timeZones->select('value', 'text')->get();
            foreach ($us_time_zones as $time_zone => $formatted_time_zone) {
                $time_zones[] = ['value' => $time_zone,
                    'text' => $formatted_time_zone,
                    'disabled' => in_array($time_zone, ['divider-1', 'divider-2'])];
            }
            foreach ($non_us_timezones as $non_us_timezone) {
                $time_zones[] = ['value' => $non_us_timezone->value, 'text' => $non_us_timezone->text];
            }
            $response['time_zones'] = $time_zones;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the time zones. Please try again or contact us.";

        }
        return $response;
    }

}
