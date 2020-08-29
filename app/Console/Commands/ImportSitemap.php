<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SiteMap;

class ImportSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:Sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the libreverse sitemap';

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
       $siteMap = new SiteMap();
       $siteMap->init();
    }
}
