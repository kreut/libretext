<?php

namespace App\Console\Commands\OneTimers;

use App\Libretext;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class updateNonH5PLicenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:NonH5PLicenses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds the non-h5p licenses';

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
    public function handle(Question $question)
    {
//SELECT author, license, license_version FROM questions where updated_at like '2021-11-14%' and technology <> 'h5p'
        $start = microtime(true);
        $output = "Starting\r\n";
        //h5p
        $questions = DB::table('questions')
            ->join('assignment_question', 'questions.id', '=', 'assignment_question.question_id')
            ->where('technology', '<>', 'h5p')
            ->where('private_license_fixed', 0)
            ->limit(1000)
            ->get();

        $questions_by_id = [];
        foreach ($questions as $value) {
            $questions_by_id[$value->question_id] = $value;
        }
        $questions_by_id = array_values($questions_by_id);
        $domd = new \DOMDocument();
        if ($questions_by_id) {
            foreach ($questions_by_id as $key => $value) {
                try {
                    $question = Question::find($value->question_id);
                    $output .= count($questions_by_id) - $key . " $question->id\r\n";
                    $libretext = new Libretext(['library' => $question->library]);
                    $info = $question->getAuthorAndLicense($domd,
                        $libretext,
                        $question->technology_iframe);
                    $question->author = $info['author'];
                    $question->license = $info['license'];
                    $question->license_version = $info['license_version'];
                } catch (Exception $e) {
                    $output .= "Error with $question->id: {$e->getMessage()}\r\n";
                }
                $question->private_license_fixed = 1;
                $question->save();
            }
        }
        $output .= microtime(true) - $start;
        Storage::disk('s3')->put("logs/updateLicenses_$question->id.txt", $output, ['StorageClass' => 'STANDARD_IA']);
        return 0;
    }
}
