<?php

namespace App\Console\Commands\OneTimers;

use App\SavedQuestionsFolder;
use Exception;
use Illuminate\Console\Command;

class updateDefaultToMain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:defaultToMain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the Default name to Main';

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
            SavedQuestionsFolder::where('name', 'Default')->update(['name'=>'Main']);
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 1;
    }
}
