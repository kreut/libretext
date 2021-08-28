<?php

namespace App\Console\Commands\OneTimers;

use App\FinalGrade;
use Illuminate\Console\Command;
use App\Course;

class populateCoursesWithLetterGrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'populate:letterGrades';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates all current courses with the letter grades';

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
     * @return mixed
     */
    public function handle()
    {
        $courses = Course::all();
        $finalGrade = new FinalGrade();
        foreach ($courses as $course){
            if (!$course->finalGrades()->exists()){
                FinalGrade::create(['course_id'=>$course->id,
                    'letter_grades' => $finalGrade->defaultLetterGrades() ]);
            }
        }

    }
}
