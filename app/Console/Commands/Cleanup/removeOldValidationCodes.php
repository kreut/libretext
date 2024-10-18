<?php

namespace App\Console\Commands\Cleanup;

use App\Exceptions\Handler;
use App\InstructorAccessCode;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class removeOldValidationCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:oldValidationCodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes codes older than 10 minutes';

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
    public function handle(): int
    {
        try {
            DB::table('link_to_account_validation_codes')
                ->where('created_at', '<=', Carbon::now()->subMinutes(10)->toDateTimeString())
                ->delete();
            return 0;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            echo $e->getMessage();
            return 1;
        }
    }
}
