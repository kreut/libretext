<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\FrameworkDescriptor;
use Faker\Generator as Faker;

$factory->define(FrameworkDescriptor::class, function (Faker $faker) {
    return [
        'descriptor' => 'some descriptor'
    ];
});
