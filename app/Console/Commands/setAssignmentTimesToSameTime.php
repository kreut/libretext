<?php

namespace App\Console\Commands;

use App\AssignToTiming;
use App\Course;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class setAssignmentTimesToSameTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:assignmentTimesToSameTime {course_id} {key} {year} {month} {day} {hour} {minute}';

    //set:assignmentTimesToSameTime 3870 available_from 2024 8 19 0 0
    //set:assignmentTimesToSameTime 3870 due 2024 12 14 23 59
    //set:assignmentTimesToSameTime 3874 available_from 2024 5 27 0 0
    //set:assignmentTimesToSameTime 3874 due 2024 7 17 23 59
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
            // DB::beginTransaction();
            $course_id = $this->argument('course_id');
            $key =  $this->argument('key');
            $hour =  $this->argument('hour');
            $year =  $this->argument('year');
            $month =  $this->argument('month');
            $day=  $this->argument('day');
            $minute =  $this->argument('minute');

            if (!in_array($key,['due','available_from','final_submission_deadline'])){
                echo "Invalid key.";
                return 1;
            }
            if ($hour > 23 or $hour < 0){
                echo "Invalid hour.";
                return 1;
            }
            if ($minute > 59 or $minute < 0){
                echo "Invalid minute.";
                return 1;
            }
            $course = Course::find($course_id);
            $time_zone = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('courses.id', $course->id)->first()->time_zone;
            $assignment_ids = $course->assignments->pluck('id')->toArray();
            $assign_to_timings = AssignToTiming::whereIn('assignment_id', $assignment_ids)->get();
            foreach ($assign_to_timings as $assign_to_timing) {
                    if ($assign_to_timing->{$key}) {
                        $dateString = $assign_to_timing->{$key};

                        // Create a Carbon instance from the input date string in the user's timezone
                        $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $dateString, $time_zone);

                        // Set the time to 00:00:00 while respecting DST
                        $offset =$carbon->utcOffset()/60;
                        $carbon->setDate( $year, $month, $day)->setTime( $hour-$offset, $minute);

                        // Format the date back to the desired string format
                        $assign_to_timing->{$key} = $carbon->format('Y-m-d H:i:s');
                        $assign_to_timing->save();

                }

            }
            DB::commit();
            echo "Times have been updated for $course->name.";
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();


        }
        return 0;
    }
}
