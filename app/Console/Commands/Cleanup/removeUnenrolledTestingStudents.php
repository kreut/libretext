<?php

namespace App\Console\Commands\Cleanup;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Laravel\Facades\Telegram;

class removeUnenrolledTestingStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:unenrolledTestingStudents';

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
     * @throws Exception
     */
    public function handle()
    {
        try {
            $not_enrolled_testing_students = [];
            $testing_students = DB::table('users')
                ->select('id')
                ->where('testing_student', 1)
                ->get()
                ->pluck('id')
                ->toArray();
            if ($testing_students) {
                $enrolled_testing_students = DB::table('enrollments')
                    ->select('user_id')
                    ->whereIn('user_id', $testing_students)
                    ->get()
                    ->pluck('user_id')
                    ->toArray();

                $not_enrolled_testing_students = array_diff($testing_students, $enrolled_testing_students);
            }
            DB::beginTransaction();
            DB::table('tester_students')
                ->whereIn('student_user_id', $not_enrolled_testing_students)
                ->delete();
            DB::table('users')
                ->whereIn('id', $not_enrolled_testing_students)
                ->where('testing_student', 1) //not needed but just in case I screwed something up
                ->delete();
            DB::commit();
            $num_not_enrolled_testing_students = count($not_enrolled_testing_students);
            if ($num_not_enrolled_testing_students) {
                Telegram::sendMessage([
                    'chat_id' => config('myconfig.telegram_channel_id'),
                    'parse_mode' => 'HTML',
                    'text' => "$num_not_enrolled_testing_students testing students were removed the database."
                ]);
            }
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            echo $e->getMessage();
            return 1;
        }
        echo "$num_not_enrolled_testing_students testing students were removed the database.";
        return 0;
    }
}
