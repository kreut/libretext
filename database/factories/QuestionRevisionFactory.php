<?php

/** @var Factory $factory */

use App\QuestionRevision;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(QuestionRevision::class, function (Faker $faker) {
    return
        [
            'revision_number' => 1,
            'action' => 'propagate',
            'page_id' => 1,
            'technology' => 'webwork',
            'library' => 'query',
            'technology_iframe' => '<iframe class="webwork_problem" src="https://webwork.libretexts.org/webwork2/html2xml?answersSubmitted=0&amp;sourceFilePath=Library/Rochester/setLimitsRates2Limits/ur_lr_2_9.pg&amp;problemSeed=1234567&amp;courseID=anonymous&amp;userID=anonymous&amp;course_password=anonymous&amp;showSummary=1&amp;displayMode=MathJax&amp;problemIdentifierPrefix=102&amp;language=en&amp;outputformat=libretexts" width="100%"></iframe>'];
});
