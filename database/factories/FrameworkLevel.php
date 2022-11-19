<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\FrameworkLevel;

use Faker\Generator as Faker;

$factory->define(FrameworkLevel::class, function (Faker $faker) {
    return [
        'level' => 1,
        'title' => 'some title',
        'order' => 1,
        'parent_id' => 0
    ];
});
