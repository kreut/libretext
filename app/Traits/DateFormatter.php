<?php


namespace App\Traits;


trait DateFormatter

{
    public function getDateFromSqlTimestamp(string $date) {
        return date('Y-m-d', strtotime($date));

    }

    public function getTimeFromSqlTimestamp(string $date) {
        return date('H:i:00', strtotime($date));

    }
}
