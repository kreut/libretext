<?php

namespace App\Console\Commands\H5P;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
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
    protected $signature = 'import:allH5P';

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
        try {
            $endpoint = "https://studio.libretexts.org/api/h5p/all";
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
            if ($infos) {
                $default_question_editor = Helper::defaultNonInstructorEditor();
                if ($default_question_editor->email !== 'Default Non-Instructor Editor has no email')
                    throw new Exception ("$default_question_editor->id is not the default question editor");

                $h5p_in_database = $question->where('technology', 'h5p')
                    ->select('technology_id')
                    ->pluck('technology_id')
                    ->toArray();

                foreach ($infos as $key => $info) {
                    $technology_id = $info['id'];
                    if (!in_array($technology_id, $h5p_in_database)) {
                        $data['license'] = $question->mapLicenseTextToValue($info['license']);
                        $data['author'] = $question->getH5PAuthor($info);
                        $data['title'] = $question->getH5PTitle($info);
                        $data['library'] = 'adapt';
                        $data['technology'] = 'h5p';
                        $data['license_version'] = $data['license'] ? $info['license_version'] : null;
                        $data['question_editor_user_id'] = $default_question_editor->id;
                        $data['url'] = null;
                        $data['cached'] = true;
                        $data['technology_id'] = $technology_id;
                        $data['technology_iframe'] = $question->getTechnologyIframeFromTechnology('h5p', $technology_id);
                        $data['non_technology'] = 0;
                        $data['public'] = 1;
                        $data['page_id'] = 1 + $question->where('library', 'adapt')
                                ->orderBy('page_id', 'desc')->value('page_id');//just to avoid collision
                        DB::beginTransaction();
                        $question = Question::create($data);
                        $question->page_id = $question->id;
                        $question->save();
                        $tags = $question->getH5PTags($info);
                        $question->addTags($tags);
                        DB::commit();
                        echo "$key $technology_id imported\r\n";
                    } else {
                        echo "$key $technology_id exists\r\n";
                    }
                }
            } else {
                throw new Exception ("Import All H5P CRON job returned an empty array.");
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
