<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Extension;
use Faker\Generator as Faker;

$factory->define(Extension::class, function (Faker $faker) {
    //need to add in the user and assignment id
    return [
        'extension' => '2030-09-03 9:00:00'
    ];
});
