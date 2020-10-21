<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Cutup;
use Faker\Generator as Faker;

$factory->define(Cutup::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'assignment_id' => 1,
        'file' => 'some_file_name.pdf',
    ];
});
