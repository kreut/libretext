<?php

namespace App\Console\Commands\LMS;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class passbackByAssignmentId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passback:byAssignmentId {id}';

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
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        try {
            $assignment_id = $this->argument('id');
            $lti_grade_passbacks = DB::table('lti_grade_passbacks')
                ->where('assignment_id', $assignment_id)
                ->get();
            foreach ($lti_grade_passbacks as $lti_grade_passback) {
                $this->call('passback:byLtiGradePassbackId', ['id' => $lti_grade_passback->id]);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);

            return 1;

        }
        return 0;
    }
}
