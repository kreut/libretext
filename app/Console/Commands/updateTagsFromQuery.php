<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libretext;

class updateTagsFromQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'query:updateTags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the tags which can be updated by the users';

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
        $libretext = new Libretext(['library' => 'query']);
        $libretext->updateTags();
    }
}
