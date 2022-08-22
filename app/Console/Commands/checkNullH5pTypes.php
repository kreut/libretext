<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class checkNullH5pTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:nullH5pTypes';

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
            $nullH5pTypes = DB::table('questions')
                ->where('technology', 'h5p')
                ->whereNull('h5p_type')
                ->get('id')
                ->pluck('id')
                ->toArray();

            $nullH5pTypes = implode(', ', $nullH5pTypes);
            if ($nullH5pTypes) {
                throw new Exception ("The following H5P questions have null types:  $nullH5pTypes.");
            }
            return 0;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
    }
}
