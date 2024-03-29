<?php

namespace App\Console\Commands\OneTimers;

use App\Course;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class checkRepeatedAssignmentGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:repeatedAssignmentGroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Checks for repeated assignment groups.  This should not be an issue moving forward but just in case it is, I'm checking.";

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
        try {
            $courses = Course::all();
            $issues_by_course = [];
            foreach ($courses as $course) {
                $assignment_groups_by_name = [];
                $issues_by_course[$course->id] = [];
                $assignment_groups = DB::table('assignments')
                    ->join('assignment_groups', 'assignments.assignment_group_id', '=', 'assignment_groups.id')
                    ->select('assignments.id AS assignment_id', 'assignments.name', 'assignment_group_id', 'assignment_group')
                    ->where('assignments.course_id', $course->id)
                    ->get();
                foreach ($assignment_groups as $assignment_group) {
                    if (!isset($assignment_groups_by_name[$assignment_group->assignment_group])) {
                        $assignment_groups_by_name[$assignment_group->assignment_group] = [];
                    }
                    if (!isset($assignment_groups_by_name[$assignment_group->assignment_group][$assignment_group->assignment_group_id])) {
                        $assignment_groups_by_name[$assignment_group->assignment_group][$assignment_group->assignment_group_id] = $assignment_group->assignment_id;
                    }
                }
                foreach ($assignment_groups_by_name as $assignment_group_by_name) {
                    if (count($assignment_group_by_name) > 1) {
                        $issues_by_course[$course->id][] = $assignment_group_by_name;
                    }
                }
                if (empty($issues_by_course[$course->id])) {
                    unset($issues_by_course[$course->id]);
                }
            }
            if (count($issues_by_course)) {
                $message = "The following courses have repeated assignment groups: " . implode(', ', array_keys($issues_by_course));
                throw new Exception ($message);
            }
            return 0;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }

    }
}
