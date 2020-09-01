<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Question;
use Faker\Generator as Faker;

$factory->define(Question::class, function (Faker $faker) {
    return
        [
            'title' => 'some title',
            'author' =>'some_author',
            'page_id' => 1,
            'technology' => 'webwork',
            'location' => 'https://www/webwork.com/some_path'];
});
