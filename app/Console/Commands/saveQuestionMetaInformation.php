<?php

namespace App\Console\Commands;

use App\Libretext;
use Illuminate\Console\Command;

class saveQuestionMetaInformation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:questionMetaInformation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Saves the meta information to the database';

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
    public function handle(Libretext $libretext)
    {
       $libretext->saveQuestionMetaInformation();
    }
}
