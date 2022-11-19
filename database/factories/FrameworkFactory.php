<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Framework;
use Faker\Generator as Faker;

$factory->define(Framework::class, function (Faker $faker) {
    return [
        'title' => 'some title',
        'descriptor_type' => 'concept',
        'author' => 'some author',
        'license' => 'publicdomain',
        'source_url' => 'some url',
        'user_id' => 1,
        'description' => 'some description'
    ];
});
