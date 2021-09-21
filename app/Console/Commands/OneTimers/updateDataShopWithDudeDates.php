<?php

namespace App\Console\Commands\OneTimers;

use Illuminate\Console\Command;

class updateDataShopWithDudeDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:dataShopWithDueDates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds the assignment due dates to the datashop information';

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
        return 0;
    }
}
