<?php


namespace App\Traits;


trait AccessCodes
{
    public function createCourseAccessCode() {
        return substr(sha1(mt_rand()), 17, 8);
    }
}
