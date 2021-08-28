<?php

namespace App\Console\Commands\OneTimers;

use App\Solution;
use App\Cutup;
use Carbon\Carbon;
use \Exception;
use Illuminate\Console\Command;
use App\Course;

class solutionsRecompile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'solutions:recompile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompiles all solutions for a given course';

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
        $user_id = 5;
        $course_id = 2;
        $course = Course::find($course_id);
        $assignments = $course->assignments;
        $cutup = new Cutup();
        $solution = new Solution();
        foreach ($assignments as $key => $assignment) {
            try {
                $compiled_filename = $cutup->forcePDFRecompileSolutionsByAssignment($assignment->id, $user_id, $solution);
                if ($compiled_filename) {
                    $compiled_file_data = [
                        'file' => $compiled_filename,
                        'original_filename' => str_replace(' ', '', $assignment->name . '.pdf'),
                        'updated_at' => Carbon::now()];
                    $solution->updateOrCreate(
                        [
                            'user_id' => $user_id,
                            'type' => 'a',
                            'assignment_id' => $assignment->id,
                            'question_id' => null
                        ],
                        $compiled_file_data
                    );
                }
                echo "Assignment: $assignment->name, Filename: $compiled_filename\r\n";
            } catch (Exception $e) {
                echo "Assignment: $assignment->name, {$e->getMessage()}\r\n";
            }
        }
    }
}
