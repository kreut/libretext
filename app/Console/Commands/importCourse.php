<?php

namespace App\Console\Commands;

use App\Course;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class importCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:course';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user_id = 1;
        $course_id_to_import = 2185;
        $user = User::find(1);
        $request = new Request();
        $request->merge(['action' => 'import', 'due_date' => '', 'import_as_beta' => '', 'shift_dates' => '',
            'user' => $user]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });


        $course = Course::find($course_id_to_import);
        $course->import($request,
            $school,
            $import_as_beta,
            $betaCourse,
            $assignmentGroup,
            $assignmentGroupWeight,
            $assignmentSyncQuestion,
            $enrollment,
            $finalGrade,
            $section);

        return 0;
    }
}
