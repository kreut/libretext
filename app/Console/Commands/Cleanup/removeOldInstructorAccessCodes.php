<?php

namespace App\Console\Commands\Cleanup;

use App\Exceptions\Handler;
use App\InstructorAccessCode;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class removeOldInstructorAccessCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:oldInstructorAccessCodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes codes older than 48 hours';

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
     * @param InstructorAccessCode $instructorAccessCode
     * @return int
     * @throws Exception
     */
    public function handle(InstructorAccessCode $instructorAccessCode): int
    {
        try {
            $instructorAccessCode->where('created_at', '<=', Carbon::now()->subDays(2)->toDateTimeString())->delete();
            return 0;
        } catch (Exception $e){
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
    }
}
