<?php

namespace App\Console\Commands;

use App\Enrollment;
use Illuminate\Console\Command;

class moveStudentsToNewSections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:newSections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move current students to new sections for testing purposes.';

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
        $enrollments = Enrollment::where('course_id', 16)->get();
        foreach ($enrollments as $enrollment) {
            if ($enrollment->id % 3 === 1) {
                $enrollment->section_id = 36;
            }
            if ( $enrollment->id % 3 === 2) {
                $enrollment->section_id = 37;
            }
            $enrollment->save();

        }
    }
}
