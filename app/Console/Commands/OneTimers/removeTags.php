<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;

class removeTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:tags {tag}';

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
        $tag = $this->argument('tag');
        try {
START: remove article:topic show:toc

        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;

        }
        return 0;
    }
}
