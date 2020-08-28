<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(SubmissionFile::class, function (Faker $faker) {
    $file_path = "assignments/1/fake_1.pdf";
    if (!Storage::disk('local')->exists($file_path)) {
        Storage::disk('local')->put($file_path, 'some contents');
    }
    $submissionContents = Storage::disk('local')->get($file_path);
    if (!Storage::disk('s3')->exists($file_path)) {
        Storage::disk('s3')->put($file_path, $submissionContents);
    }

    return ['user_id' => 2,
        'assignment_id' => 1,
        'submission' => 'fake_1.pdf',
        'original_filename' => 'orig_fake_1.pdf',
        'date_submitted' => Carbon::now()];
});
