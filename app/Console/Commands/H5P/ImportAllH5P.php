<?php

namespace App\Console\Commands\H5P;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAllH5P extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:allH5P {timeframe} {number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all h5p questions into the database';

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
     * @param Question $question
     * @return int
     * @throws Exception
     */
    public function handle(Question $question): int
    {
        $start = microtime(true);
        try {
            switch ($this->argument('timeframe')) {
                case('minutes'):
                    $date = Carbon::now()->subMinutes($this->argument('number'))->format('Y-m-d');//extra buffer
                    break;
                case('days'):
                    $date = Carbon::now()->subDays($this->argument('number'))->format('Y-m-d');
                    break;
                default:
                    throw new Exception('invalid time in h5p import');
            }
            $endpoint = "https://studio.libretexts.org/api/h5p/all?changed=$date";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)

            $output = curl_exec($ch);
            $error_msg = curl_errno($ch) ? curl_error($ch) : '';

            curl_close($ch);

            if ($error_msg) {
                throw new Exception ("Import All H5P CRON job did not work: $error_msg");
            }


            $infos = json_decode($output, 1);
            $new = 0;
            $old = 0;
            if ($infos) {
                $default_question_editor = Helper::defaultNonInstructorEditor();
                if ($default_question_editor->email !== 'Default Non-Instructor Editor has no email')
                    throw new Exception ("$default_question_editor->id is not the default question editor");


                foreach ($infos as $key => $info) {
                    try {
                        $technology_id = $info['id'];
                        if (!$technology_id){
                            continue;
                        }
                        $h5p_in_database =Question::where('technology', 'h5p')
                            ->where('technology_id', $technology_id)
                            ->first();
                        if (!$question){
                            $question = new Question();
                        }
                        $license = $question->mapLicenseTextToValue($info['license']);
                        $author = $question->getH5PAuthor($info);
                        $title = $question->getH5PTitle($info);
                        $license_version = $license ? $info['license_version'] : null;
                        DB::beginTransaction();
                        if (!$h5p_in_database) {
                            $new++;
                            $data['license'] = $license;
                            $data['author'] = $author;
                            $data['title'] = $title;
                            $data['license_version'] = $license_version;
                            $data['library'] = 'adapt';
                            $data['technology'] = 'h5p';
                            $data['question_editor_user_id'] = $default_question_editor->id;
                            $data['url'] = null;
                            $data['cached'] = true;
                            $data['h5p_type'] = $info['type'];
                            $data['source_url'] = $info['h5p_source'];
                            $data['technology_id'] = $technology_id;
                            $data['technology_iframe'] = $question->getTechnologyIframeFromTechnology('h5p', $technology_id);
                            $data['non_technology'] = 0;
                            $data['public'] = 1;
                            $data['page_id'] = 1 + $question->where('library', 'adapt')
                                    ->orderBy('page_id', 'desc')->value('page_id');//just to avoid collision
                            $question = Question::create($data);
                            $question->page_id = $question->id;
                            echo "$key $technology_id imported\r\n";
                        } else {
                            $old++;
                            $question = Question::where('technology', 'h5p')
                                ->where('library', 'adapt')
                                ->where('technology_id', $technology_id)
                                ->first();
                            if (!$question){
                                if (DB::transactionLevel()) {
                                    DB::rollback();
                                }
                                continue;
                            }
                            $question->license = $license;
                            $question->author = $author;
                            $question->title = $title;
                            $question->h5p_type = $info['type'];
                            $question->license_version = $license_version;
                            $question->updated_at = Carbon::now();
                            echo "$key $technology_id updated\r\n";
                        }
                        $question->save();
                        $tags = $question->getH5PTags($info);
                        if ($tags) {
                            $tags = array_unique($tags);
                            $question->addTags($tags);
                        }
                        DB::commit();

                    } catch (Exception $e) {
                        DB::rollback();
                        if ($technology_id) {
                            try {
                                throw new Exception("Error in importing h5p with id $technology_id: {$e->getMessage()}", 0, $e);
                            } catch (Exception $e) {
                                $h = new Handler(app());
                                $h->report($e);
                            }
                        } else {
                            $h = new Handler(app());
                            $h->report($e);

                        }
                    }
                }
            }
            echo "New: $new\r\n";
            echo "Old: $old\r\n";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        echo microtime(true) - $start;

        return 0;
    }
}
