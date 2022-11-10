<?php

namespace App\Console\Commands\NoRole;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Score;
use App\Submission;
use App\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class deleteUsersWithNoRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:usersWithNoRole';

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
        try {
            $num_courses = Course::count();
            $num_assignments = Assignment::count();
            $num_scores = Score::count();
            $num_submissions = Submission::count();
            $num_enrollments = Enrollment::count();
            echo "Courses: $num_courses\r\n";
            echo "Assignments: $num_assignments\r\n";
            echo "Scores: $num_scores\r\n";
            echo "Submissions: $num_submissions\r\n";
            echo "Enrollments: $num_enrollments\r\n";

            $users = User::where('role', 0)
                ->where('created_at', '<', '2022-11-04 00:00:00')
                ->get();
            $num_users = count($users);
            DB::beginTransaction();
            foreach ($users as $user) {
                DB::table('users_with_no_role')->insert([
                    'user_id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'student_id' => $user->student_id,
                    'status' => 1,
                    'created_at' => $user->created_at,
                    'updated_at' => now()]);
                DB::table('users')
                    ->where('id', $user->id)
                    ->where('role', 0)
                    ->delete();
            }
            $num_courses_2 = Course::count();
            $num_assignments_2 = Assignment::count();
            $num_scores_2 = Score::count();
            $num_submissions_2 = Submission::count();
            $num_enrollments_2 = Enrollment::count();
            echo "Courses: $num_courses_2\r\n";
            echo "Assignments: $num_assignments_2\r\n";
            echo "Scores: $num_scores_2\r\n";
            echo "Submissions: $num_submissions_2\r\n";
            echo "Enrollments: $num_enrollments_2\r\n";
            if (($num_courses !== $num_courses_2)
                || ($num_assignments !== $num_assignments_2)
                || ($num_scores !== $num_scores_2)
                || ($num_submissions !== $num_submissions_2)
                || ($num_enrollments !== $num_enrollments_2)) {
                throw new Exception ("Not all matches.");
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        echo "Role $num_users users removed.";
        return 0;
    }
}
