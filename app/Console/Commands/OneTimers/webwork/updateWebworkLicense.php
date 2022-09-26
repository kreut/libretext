<?php

namespace App\Console\Commands\OneTimers\webwork;

use App\Question;
use Illuminate\Console\Command;

class updateWebworkLicense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webwork:updateLicense';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates webwork to the correct license';

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
       Question::where('technology','webwork')->update(['license' => 'opl_license',
       'license_version'=> null]);

    }
}
