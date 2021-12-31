<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\SavedQuestionsFolder;
use Faker\Generator as Faker;

$factory->define(SavedQuestionsFolder::class, function (Faker $faker) {
    return [
       'name' => 'Default',
        'type' => 'my_favorites'
    ];
});
