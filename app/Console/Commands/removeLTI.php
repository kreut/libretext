<?php

namespace App\Console\Commands;

use App\Assignment;
use App\Course;
use Exception;
use Illuminate\Console\Command;

class removeLTI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:LTI';

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

        if (app()->environment() !== 'local') {
            echo("Only in Local.");
            exit;
        }
        try {
            Assignment::whereNotNull('lms_assignment_id')->update([
                'lms_assignment_id' => null,
                'lms_resource_link_id' => null,
            ]);

// Update courses
            Course::whereNotNull('lms_course_id')->update([
                'lms_course_id' => null,
                'lms' => 0,
            ]);
        } catch (Exception $e) {
            echo $e->getMessage();

        }
        return 0;
    }
}
