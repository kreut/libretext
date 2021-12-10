<?php

namespace App\Console\Commands\H5P;

use App\Question;
use App\User;
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
     * @return void
     */
    public function handle(Question $question)
    {
        try {
            $endpoint = "https://studio.libretexts.org/api/h5p/all";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $output = curl_exec($ch);
            curl_close($ch);
            $infos = json_decode($output, 1);
            if ($infos) {
                $default_question_editor = User::where('email', 'Default Question Editor has no email')->first();
                if (!$default_question_editor)
                    throw new Exception ("No Default Question Editor so can't import the H5P questions.");

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
                        $data['technology_iframe'] = $question->getTechnologyIframeFromTechnology('h5p',$technology_id);
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
                dd($output);
                return 1;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;
        }

        return 0;
    }
}
