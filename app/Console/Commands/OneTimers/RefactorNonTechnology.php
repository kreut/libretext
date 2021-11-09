<?php

namespace App\Console\Commands\OneTimers;

use App\Libretext;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Question;
use App\Traits\LibretextFiles;

class RefactorNonTechnology extends Command
{

    use LibretextFiles;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nontechnology:refactor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes current nontechnology questions and refactors the js, css, and sends to s3';

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
     * @return mixed
     */
    public function handle()
    {


        $Libretext = new Libretext(['library' => 'query']);
        $all_files = Storage::disk('public')->allFiles();
        foreach ($all_files as $key => $file) {
            if (strpos($file, '.') === 0 || strpos($file, 'config') !== false || strpos($file, 'query') !== false || strpos($file, 'img') !== false) {
                unset($all_files[$key]);
            }
        }
        foreach ($all_files as $file) {
            $page_id = str_replace('.html', '', $file);
echo $page_id . "\r\n";
            try {
                // id=102629;  //Frankenstein test
                //Public type questions
echo "page id $page_id";
                $contents = $Libretext->getContentsByPageId($page_id);
                $body = $contents['body'][0];
            } catch (Exception $e) {

                if (strpos($e->getMessage(), '403 Forbidden') === false) {
                    //some other error besides forbidden
                    echo json_encode(['type' => 'error',
                        'message' => 'We tried getting that page but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                        'timeout' => 12000]);
                    exit;
                }

                //private page so try again!
                try {
                    $body = $Libretext->getPrivatePage('contents', $page_id);
                } catch (Exception $e) {
                    echo json_encode(['type' => 'error',
                        'message' => 'We tried getting that page but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                        'timeout' => 12000]);
                    exit;
                }
            }

            try {

                if ($technology = $Libretext->getTechnologyFromBody($body)) {
                    $technology_iframe = $Libretext->getTechnologyIframeFromBody($body, $technology);

                    $non_technology = str_replace($technology_iframe, '', $body);
                    $has_non_technology = trim($non_technology) !== '';

                    if ($has_non_technology) {
                        //Frankenstein type problem
                        $non_technology = $Libretext->addExtras($non_technology,
                            ['glMol' => strpos($body, '/Molecules/GLmol/js/GLWrapper.js') !== false,
                                'MathJax' => false]);
                        Storage::disk('local')->put("query/{$page_id}.php", $non_technology);
                        Storage::disk('s3')->put("query/{$page_id}.php", $non_technology);
                    }
                } else {
                    $non_technology = $Libretext->addExtras($body,
                        ['glMol' => false,
                            'MathJax' => true
                        ]);

                    Storage::disk('local')->put("query/{$page_id}.php", $non_technology);
                    Storage::disk('s3')->put("query/{$page_id}.php", $non_technology);
                }

            } catch (Exception $e) {
                echo json_encode(['type' => 'error',
                    'message' => 'We tried getting that page but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                    'timeout' => 12000]);
                exit;
            }
            sleep(1);
        }
    }
}
