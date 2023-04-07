<?php

namespace App\Console\Commands;

use App\JWE;
use Illuminate\Console\Command;

class decryptJWT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decrypt:jwt {token} {technology?}';

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
        $JWE = new JWE();
        $token = $this->argument('token');
        $technology = $this->argument('technology') ? $this->argument('technology') : 'webwork';
        echo $JWE->decrypt($token, $technology);

    }
}
