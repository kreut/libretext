<?php

namespace App\Console\Commands\LMS;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class passbackAllScoresByAssignmentId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passback:allScoresByAssignmentId';

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
            $passback_by_assignments = DB::table('passback_by_assignments')
                ->where('status','pending')
                ->get();
            foreach ($passback_by_assignments as $passback_by_assignment){
                DB::table('passback_by_assignments')
                    ->where('id',  $passback_by_assignment->id)
                    ->where('status','pending')
                    ->update(['status'=> 'processing']);
                $this->call('passback:byAssignmentId', ['id' => $passback_by_assignment->assignment_id]);
                DB::table('passback_by_assignments')
                    ->where('id',  $passback_by_assignment->id)
                    ->where('status','processing')
                    ->update(['status'=> 'completed']);
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
