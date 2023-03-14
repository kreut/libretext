<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Assignment;
use Faker\Generator as Faker;

$factory->define(Assignment::class, function (Faker $faker) {
    return ['course_id' => 1,
        'name' => 'First Assignment',
        'scoring_type' => 'p',
        'default_points_per_question' => 2,
        'points_per_question' => 'number of points',
        'default_open_ended_submission_type' => 'file',
        'assignment_group_id' => 1,
        'assessment_type' => 'delayed',
        'late_policy' => 'not accepted',
        'include_in_weighted_average' => 1,
        'notifications' => 1,
        'order' => 1,
        'formative'=>0,
        'file_upload_mode'=>'both'
        ];
});
