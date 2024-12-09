<?php

namespace App\Console\Commands\OneTimers\webwork\privateToNative;

use App\Console\Commands\OneTimers\webwork\Exception;
use App\Question;
use App\QuestionRevision;
use App\WebworkAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class saveWebworkOPLtoPrivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:webworkOplToPrivate';

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
        try {
            DB::beginTransaction();

            $opl_questions = Question::where('technology', 'webwork')
                ->whereNull('webwork_code')
                ->where(function ($query) {
                    $query->where('technology_id', 'LIKE', 'Library%')
                        ->orWhere('technology_id', 'LIKE', 'Contrib%');
                })
                ->get();

            foreach ($opl_questions as $key => $opl_question) {
                echo $key . "\r\n";
                $old_dir = "/Users/franciscaparedes/Downloads/webwork-open-problem-library/";
                $new_dir = "/Users/franciscaparedes/Downloads/webwork-local/";
                $old_path = $old_dir . $opl_question->technology_id;
                $directory = dirname($old_path);
                $pg_file = basename($old_path); // Corrected missing closing parenthesis

                try {
                    if (!is_file($directory) && !is_dir($directory)) {
                        echo $old_path . "\r\n";
                    } else {
                        $question_id = $opl_question->id;
                        $dir = $new_dir . $question_id;

                        if (!file_exists($dir)) {
                            mkdir($dir);
                        }

                        $new_path = "$dir/$pg_file";
                        copy($old_path, $new_path);

                        $files = scandir($directory);

                        // Filter files that do not have a .pg extension
                        $nonPgFiles = array_filter($files, function ($file) use ($directory) {
                            $filePath = $directory . DIRECTORY_SEPARATOR . $file;
                            return is_file($filePath)
                                && !is_dir($filePath)
                                && pathinfo($file, PATHINFO_EXTENSION) !== 'pg'
                                && pathinfo($file, PATHINFO_EXTENSION) !== 'html'
                                && pathinfo($file, PATHINFO_EXTENSION) !== 'htm'
                                && pathinfo($file, PATHINFO_EXTENSION) !== 'pl'
                                && pathinfo($file, PATHINFO_EXTENSION) !== 'DS_Store';
                        });
                        $pg_code = file_get_contents($old_path);
                        if ($nonPgFiles) {
                            foreach ($nonPgFiles as $nonPgFile) {
                                if (strpos($pg_code, $nonPgFile) !== false) {
                                    $old_asset = dirname($old_path) . "/$nonPgFile";
                                    $new_asset = dirname($new_path) . "/$nonPgFile";
                                    copy($old_asset, $new_asset);
                                    $format_old_asset = str_replace($old_dir, '', $old_asset);
                                    $format_new_asset = str_replace($new_dir, '', $new_asset);
                                    //$this->info("Asset from $format_old_asset to $format_new_asset");
                                    $question_revisions = QuestionRevision::where('question_id', $question_id)->get();
                                    if (!$question_revisions->isEmpty()) {
                                        dd($question_revisions);
                                    } else {
                                        $webwork_attachment = WebworkAttachment::where('question_id', $question_id)
                                            ->where('filename', $nonPgFile)
                                            ->first();

                                        if (!$webwork_attachment) {
                                            if ($format_new_asset != '34093/2-62384.png') {
                                                $image_size = getimagesize($new_asset);
                                            } else {
                                                $image_size = [400, 400];
                                                $this->info("$new_asset was set to 400 by 400");
                                            }
                                            if (is_bool($image_size)) {
                                                $image_size = [400, 400];
                                                $this->info("$new_asset was set to 400 by 400");
                                            }
                                            $webwork_attachment = new WebworkAttachment();
                                            $webwork_attachment->question_id = $question_id;
                                            $webwork_attachment->filename = $nonPgFile;
                                            $webwork_attachment->width = $image_size[0];
                                            $webwork_attachment->height = $image_size[1];
                                            $webwork_attachment->save();
                                        }
                                    }
                                }
                            }
                        }
                        if (!DB::table('opls')->where('question_id', $question_id)->first()) {
                            $length = strlen($pg_code);
                            if (strlen($pg_code) > 65000) {
                                echo "$new_path too big: $length\r\n";
                                continue;
                            }
                            DB::table('opls')->insert(['question_id' => $question_id,
                                'webwork_path' => $opl_question->technology_id,
                                'webwork_code' => $pg_code,
                                'updated_at' => now(),
                                'created_at' => now()]);
                        }
                        /** Then, remove that information from the questions database.**/
                    }
                } catch (Exception $e) {
                    echo $e->getMessage() . "\r\n";
                }
            }

            DB::commit();
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            echo $e->getMessage() . "\r\n";
            return 1;
        }
    }
}
