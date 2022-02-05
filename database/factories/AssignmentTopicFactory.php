<?php

/** @var Factory $factory */

use App\AssignmentTopic;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(AssignmentTopic::class, function (Faker $faker) {
    return [
      'name' => 'some topic'
    ];
});
