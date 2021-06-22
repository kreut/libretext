<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Section;
use Faker\Generator as Faker;

$factory->define(Section::class, function (Faker $faker) {
    return [
        'name' => 'Section 1',
        'course_id' => 1,
        'access_code' =>'some_access_code',
        'crn' => '123123'
    ];
});
