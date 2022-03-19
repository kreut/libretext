<?php

namespace App\Console\Commands\OneTimers;

use App\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixAssignmentGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:assignmentGroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes the empty assignment groups';

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
            if ( empty($issues_by_course[$course->id])) {
                unset($issues_by_course[$course->id]);
            }
        }
        dd( $issues_by_course);
        return 0;
    }
}
