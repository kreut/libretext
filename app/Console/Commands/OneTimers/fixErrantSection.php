<?php

namespace App\Console\Commands\OneTimers;

use App\Assignment;
use App\AssignToGroup;
use App\AssignToTiming;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixErrantSection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:errantSection';

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
            $bad_section_id = 2404;
            AssignToGroup::where('group', 'section')
                ->where('group_id', $bad_section_id)->delete();

        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
