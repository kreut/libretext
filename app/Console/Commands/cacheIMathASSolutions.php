<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class cacheIMathASSolutions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:IMathSolutions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $access_token = config('myconfig.imathas_token');
            $authorization = "Authorization: Bearer $access_token"; // Prepare the authorisation token

            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', $authorization]); // Inject the token into the header
            curl_setopt($ch, CURLOPT_URL, "https://" . Helper::iMathASDomain() ."/imathas/adapt/questions_with_solutions.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch); // Execute the cURL statement
            if ($result === false) {
                throw new Exception('Connection issue with IMathAS: ' . curl_error($ch));
            } else {
                $imathas_questions_with_solutions = json_decode($result)->ids;
                Cache::forever('imathas_questions_with_solutions', $imathas_questions_with_solutions);
                echo "Cached " . count($imathas_questions_with_solutions) . " questions.";
            }
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
    }
}
