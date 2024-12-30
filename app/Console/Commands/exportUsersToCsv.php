<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportUsersToCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:exportCSV';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export users to a CSV file with first name, last name, email, and role.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = '/Users/franciscaparedes/downloads/users.csv';
        $file = fopen($filePath, 'w');

        // Set UTF-8 BOM for proper encoding
        fprintf($file, "\xEF\xBB\xBF");

        // Write the CSV headers
        fputcsv($file, ['First Name', 'Last Name', 'Email', 'Password', 'Time Zone', 'Role', 'School']);

        // Fetch users from the database with valid emails
        $users = DB::table('users')
            ->whereNotNull('email')
            ->whereRaw("email REGEXP '^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$' COLLATE utf8mb4_general_ci")
            ->get(['id', 'first_name', 'last_name', 'email', 'password', 'time_zone', 'role']);
        $instructors = [];
        $students = [];
        foreach ($users as $user) {
            if ($user->role === 2) {
                $instructors[] = $user->id;
            }
            if ($user->role === 3) {
                $students[] = $user->id;
            }
        }
        $course_schools = DB::table('courses')
            ->join('schools', 'courses.school_id', '=', 'schools.id')
            ->whereIn('courses.user_id', $instructors)
            ->get();
        foreach ($course_schools as $course_school) {
            $user_school[$course_school->user_id] = $course_school->name;
        }
        $course_schools = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->join('schools', 'courses.school_id', '=', 'schools.id')
            ->select('enrollments.user_id', 'schools.name')
            ->whereIn('enrollments.user_id', $students)
            ->get();
        foreach ($course_schools as $course_school) {
            echo $course_school->name . "\r\n";
            $student_school[$course_school->user_id] = $course_school->name;
        }


        foreach ($users as $user) {
            // Map role numbers to role names
            $role = $this->getRoleName($user->role);

            // If role mapping fails, skip the user
            if ($role === null) {
                continue;
            }
            $school = '';
            if ($role === 'student') {
                $school = $student_school[$user->id] ?? '';
                if ($school !== '') {
                    echo $user->email .
                         ' ' . $school . "\r\n";
                }
            }
            else if ($role === 'instructor') {
                $school = $user_school[$user->id] ?? '';
            }

            // Write user data to the CSV
            fputcsv($file, [$user->first_name, $user->last_name, $user->email, $user->password, $user->time_zone, $role, $school]);
        }

        fclose($file);

        $this->info("Users have been exported to: $filePath");
    }

    /**
     * Map role numbers to role names.
     *
     * @param int $role
     * @return string|null
     */
    private function getRoleName(int $role): ?string
    {
        switch ($role) {
            case 2:
                return 'instructor';
            case 3:
                return 'student';
            case 4:
                return 'grader';
            case 5:
                return 'question-editor';
            case 6:
                return 'tester';
            default:
                return null;
        }
    }
}
