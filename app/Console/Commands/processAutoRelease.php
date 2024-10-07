<?php

namespace App\Console\Commands;

use App\Assignment;
use App\AutoRelease;
use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class processAutoRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:autoRelease';

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


    public function handle(AutoRelease $autoRelease): int
    {
        try {
            $auto_releases = DB::table('auto_releases')
                ->join('assignments', 'assignments.id', '=', 'auto_releases.type_id')
                ->join('assign_to_timings', 'assignments.id', '=', 'assign_to_timings.assignment_id')
                ->where('auto_releases.type', 'assignment')
                ->where(function ($query) {
                    $query->where('assignments.shown', 0)
                        ->orWhere('assignments.show_scores', 0)
                        ->orWhere('assignments.solutions_released', 0)
                        ->orWhere('assignments.students_can_view_assignment_statistics', 0);
                })
                ->select(
                    'assign_to_timings.available_from',
                    'assign_to_timings.due',
                    'assign_to_timings.final_submission_deadline',
                    'auto_releases.type_id AS assignment_id',
                    'auto_releases.shown',
                    'auto_releases.shown_activated',
                    'auto_releases.show_scores',
                    'auto_releases.show_scores_activated',
                    'auto_releases.show_scores_after',
                    'auto_releases.solutions_released',
                    'auto_releases.solutions_released_activated',
                    'auto_releases.solutions_released_after',
                    'auto_releases.students_can_view_assignment_statistics',
                    'auto_releases.students_can_view_assignment_statistics_activated',
                    'auto_releases.students_can_view_assignment_statistics_after'
                )->get();
            $auto_releases_by_assignment_id = $autoRelease->getAutoReleasesByAssignmentId($auto_releases);

            $now = Carbon::now()->toImmutable();

            foreach ($auto_releases_by_assignment_id as $auto_release) {
                try {
                    $assignment = Assignment::find($auto_release['assignment_id']);

                    $data = [];
                    $first_available_from = Carbon::parse($auto_release['first_available_from'])->toImmutable();
                    if (!$assignment->shown
                        && $auto_release['shown']
                        && $auto_release['shown_activated']
                        && $first_available_from->sub($auto_release['shown'])->isBefore($now)) {
                        $data['shown'] = 1;
                        // Log::info("Shown: " . $auto_release['first_available_from']);
                    }

                    foreach (['show_scores', 'solutions_released', 'students_can_view_assignment_statistics'] as $value) {
                        if (!$assignment->{$value}
                            && $auto_release[$value]
                            && $auto_release["{$value}_activated"]) {
                            $last_due = $value . "_last_due";
                            $last_due_as_time[$value] = Carbon::parse($auto_release[$last_due])->toImmutable();
                            /* Log::info('assignment_id');
                             Log::info($auto_release['assignment_id']);
                             Log::info("now: " . $now->toDateTimeString());
                             Log::info("last due: " . $last_due_as_time[$value]->toDateTimeString());
                             Log::info("last due plus auto-release: " . $last_due_as_time[$value]->add($auto_release[$value]));
                             Log::info("After: " . $now->isAfter($last_due_as_time[$value]->add($auto_release[$value])));
                             Log::info($now->isAfter($last_due_as_time[$value]->add($auto_release[$value])));*/
                            if ($now->isAfter($last_due_as_time[$value]->add($auto_release[$value]))) {
                                $data[$value] = 1;
                                //Log::info("$value: " . $auto_release[$value]);
                            }
                        }
                    }
                    if ($data) {
                        $assignment->update($data);
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                    $h = new Handler(app());
                    $h->report($e);
                }
            }

            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
            return 1;

        }

    }
}
