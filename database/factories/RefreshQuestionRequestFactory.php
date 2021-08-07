<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\RefreshQuestionRequest;
use Faker\Generator as Faker;

$factory->define(RefreshQuestionRequest::class, function (Faker $faker) {
    return [
        'user_id' =>1,
        'question_id' =>1,
        'nature_of_update' => 'something',
        'status' => 'pending'
    ];
});
