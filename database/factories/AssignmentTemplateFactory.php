<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\AssignmentTemplate;
use Faker\Generator as Faker;

$factory->define(AssignmentTemplate::class, function (Faker $faker) {
    return [
        'template_name' => 'First Template',
        'template_description' => 'Some Description',
        'formative' => 0,
        'can_view_hint' => 0,
        'scoring_type' => 'p',
        'source' => 'a',
        'points_per_question' => 'number of points',
        'default_points_per_question' => 2,
        'students_can_view_assignment_statistics' => 0,
        'include_in_weighted_average' => 1,
        'late_policy' => 'not accepted',
        'algorithmic' => 0,
        'assessment_type' => 'delayed',
        'default_open_ended_submission_type' => 'file',
        'instructions' => 'Some instructions',
        "number_of_randomized_assessments" => null,
        'notifications' => 1,
        'assignment_group_id' => 1,
        'file_upload_mode' => 'both',
        'order' => 1,
        'assign_to_everyone' => 0
    ];
});
