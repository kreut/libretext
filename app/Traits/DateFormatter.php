<?php


namespace App\Traits;

use Carbon\Carbon;

trait DateFormatter

{
    public function getDateFromSqlTimestamp(string $date)
    {
        return date('Y-m-d', strtotime($date));

    }

    public function getTimeFromSqlTimestamp(string $date)
    {
        return date('H:i:00', strtotime($date));

    }

    public function convertLocalMysqlFormattedDateToUTC(string $datetime, string $from_time_zone)
    {

        $dt = new \DateTime($datetime, new \DateTimeZone($from_time_zone));
        $dt->setTimeZone(new \DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');

    }
}
