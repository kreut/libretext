<?php

namespace App\Console;

use App\Jobs\LogFromCRONJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\OneTimers\storeQuestions::class,
        Commands\H5P\storeH5P::class,
        Commands\OneTimers\storeWebwork::class,
        Commands\Database\DbBackup::class,
        Commands\Notifications\sendAssignmentDueReminderEmails::class,
        Commands\dataShopToS3::class

    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        if (env('APP_ENV') === 'local') {
            $schedule->command('backup:VaporDB')
                ->dailyAt('12:00');

        }

        $schedule->command('get:usersWithZeroRole')->everyMinute();
        $schedule->command('import:allH5P', ['minutes', '15'])
            ->everyFifteenMinutes();

        if (env('APP_ENV') !== 'local') {
            $schedule->command('notify:LatestErrors')->everyFiveMinutes();
            $schedule->command('retry:FailedGradePassbacks')->daily();
            $schedule->command('insert:reviewHistories')->hourly();
        }

        if (env('APP_ENV') === 'production') {
            if (!env('APP_VAPOR')) {
                $schedule->command('db:backup')->twiceDaily();
            }

            $schedule->command('notify:instructorCanvasMaxAttemptsError')->hourly();


            $schedule->command('get:NonTechnologiesWithNullNonTechnologyHtml')->hourly();

            $schedule->command('get:badWebworks')->everySixHours();

            $schedule->command('notification:sendAssignmentDueReminderEmails')->everyMinute();

            $schedule->command('remove:oldCurrentQuestionEditors')->everyMinute();
            $schedule->command('remove:pendingQuestionOwnershipTransfers')->daily();
            $schedule->command('remove:unenrolledTestingStudents')->daily();

            $schedule->command('notify:BetaCourseApprovals')->daily();

            $schedule->command('email:instructorsWithConcludedCourses')->daily();
            $schedule->command('remove:localQtiFiles')->weekly();

            $schedule->command('check:nullH5pTypes')->daily();

            /* grader notifications */
            $schedule->command('notify:gradersForDueAssignments')->hourly();
            $schedule->command('notify:gradersForLateSubmissions')->daily();
            $schedule->command('notify:gradersReminders')->daily();
            /* end grader notifications */
            $schedule->command('check:AssignTos')->twiceDaily();
            $schedule->command('remove:oldAccessCodes')->daily();
            $schedule->command('check:repeatedAssignmentGroups')->daily();

            /** Larry's trees were the same when using the same page ID.  Not sure why so I'm watching for duplicates */
            $schedule->command('count:LearningTreesWithSameRootNodeByUser')->daily();


        }

        if (env('APP_ENV') === 'dev') {
            $schedule->command('s3:backup')->hourly();
        }

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
