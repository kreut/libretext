<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\LearningTree;
use Faker\Generator as Faker;

$factory->define(LearningTree::class, function (Faker $faker) {
    return [
       'learning_tree' => 'some learning tree',
        'title'=>'Learning Tree title',
        'description'=> 'Learning Tree description',
        'public' => 0,
        'root_node_page_id' => 102685,
        'root_node_library' => 'query'
    ];
});
