<?php

/** @var Factory $factory */

use App\School;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(School::class, function (Faker $faker) {
    return [
       'name'=> "some name"
    ];
});
