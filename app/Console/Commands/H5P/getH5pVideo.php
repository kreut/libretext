<?php

namespace App\Console\Commands\H5P;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;

class getH5pVideo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:h5pVideo';

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
        $start = microtime(true);
        try {

            $endpoint = "https://studio.libretexts.org/api/h5p/9482";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)

            $output = curl_exec($ch);
            $error_msg = curl_errno($ch) ? curl_error($ch) : '';

            curl_close($ch);
            dd(json_encode($output,1));
            if ($error_msg) {
                throw new Exception ("Import All H5P CRON job did not work: $error_msg");
            }


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        echo microtime(true) - $start;

        return 0;

    }
}
