<?php

namespace App\Console\Commands;

use App\Course;
use App\Section;
use Illuminate\Console\Command;

class createSections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:sections';

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
     * @return mixed
     */
    public function handle()
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            $section = Section::where('course_id', $course->id)->first();
            if (!$section) {
                $section = new Section();
                $section->name = 'Main';
                $section->course_id = $course->id;
                $section->save();
            }

            $enrollments = $course->enrollments;

            foreach ($enrollments as $enrollment){
                $enrollment->section_id = $section->id;
                $enrollment->save();
            }
        }
    }
}
