<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\LearningTree;
use Faker\Generator as Faker;

$factory->define(LearningTree::class, function (Faker $faker) {
    return [
       'learning_tree' => 'some learning tree',
        'title'=>'Learning Tree title',
        'description'=> 'Learning Tree description'
    ];
});
