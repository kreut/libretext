<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */


use App\CannedResponse;
use Faker\Generator as Faker;

$factory->define(CannedResponse::class, function (Faker $faker) {
    return [
        'user_id' =>1,
        'canned_response' => 'some canned response'

    ];
});
