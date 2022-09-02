<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getBadWebworks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:badWebworks';

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
            $bad_webworks = Question::where('technology', 'webwork')
                ->whereNull('technology_id')
                ->whereNull('webwork_code')
                ->select('id', 'technology_iframe')
                ->count();
           if ($bad_webworks){
               throw new Exception ("$bad_webworks bad webworks exist.");
            }

        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
        }
        return 0;
    }
}
