<?php

namespace App\Console\Commands\OneTimers\webwork;

use App\Question;
use App\WebworkAttachment;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class movePrivateFilesToNewFolderStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:privateFilesToNewFolderStructure';

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
        /** Steps:
         *
         * 1. Download all of the folders from the webwork server that are in the private folder (excluding the ww_files folder since those are already migrated)
         * 2. Query my own database to get all of the paths of the webwork files that are potential "private" questions: start with the private path but are not yet native (private/chem_4b/Weekly_1/chem_4B_W_1_3.pg as an example --- I see 4843 of these.  Does that seem reasonable?)
         * 3. Locally create my new question folders, copying over any assets as well (At this stage I'll have to potentially prune some assets but have a plan based on my discussion with Andrew --- you won't care about the details)
         * 4. Save a mapping of the ADAPT question ID to these paths as we might need them later (Request from Andrew, but something I would have done anyway in case I need a quick revert to the old paths)
         *  7. put all attachments in the attachments database
         * 5. Send the folders back up to the server
         * 6. Script to update the technology_ids and to update the iframes
         * 7. Script to undo the update of the technology_ids and to un-update the iframes
         *
         */

        try {
            DB::beginTransaction();
            $private_non_native_questions = Question::where('technology', 'webwork')
                ->where('technology_id', 'LIKE', 'private%')
                ->whereNULL('webwork_code')
                ->get();
            foreach ($private_non_native_questions as $question) {
                $path_to_file = trim($question->technology_id);
                $pg_code = storage_path() . "/app/webwork-old-private/" . $path_to_file;
                if (!file_exists($pg_code)) {
                    if (!DB::table('webwork_private_to_natives')
                        ->where('question_id', $question->id)
                        ->first()) {
                        DB::table('webwork_private_to_natives')->insert([
                            'question_id' => $question->id,
                            'original_path' => $question->technology_id,
                            'status' => 'ADAPT path exists but pg file does not exist',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                } else {
                    $src = dirname($pg_code);
                    $dst = storage_path() . "/app/webwork-new-private/$question->id";
                    if (!is_dir($dst)) {
                        mkdir($dst);
                    }
                    if (!copy($pg_code, $dst . "/code.pg")) {
                        dd("failed to copy $pg_code.");
                    }
                    $pg_code = file_get_contents($dst . "/code.pg");
                    $files = glob("$src/*.*");
                    $non_pg_files = array_filter($files, function ($file) {
                        return (!in_array(pathinfo($file, PATHINFO_EXTENSION), ["pg", "pg_done", "save", "1", "txt", "bak"]));
                    });
                    if ($non_pg_files) {
                        echo "Destination: $dst\r\n";
                        foreach ($non_pg_files as $non_pg_file) {
                            echo "$non_pg_file\r\n";
                        }
                    }
                    foreach ($non_pg_files as $path_to_file) {
                        if (is_dir($path_to_file)) {
                            dd("$path_to_file is a directory.");
                        }
                        $filename = basename($path_to_file);
                        if (strpos($pg_code, $filename) !== false) {
                            copy($path_to_file, $dst . "/$filename");
                            if (!DB::table('webwork_attachments')
                                ->where('question_id', $question->id)
                                ->where('filename', $filename)
                                ->first()) {
                                $webworkAttachment = new WebworkAttachment();
                                $webworkAttachment->question_id = $question->id;
                                $webworkAttachment->filename = $filename;
                                $webworkAttachment->save();
                            }
                        } else {
                            echo "$src does not contain $filename.\r\n";
                        }
                    }
                    if (!DB::table('webwork_private_to_natives')
                        ->where('question_id', $question->id)
                        ->first()) {
                        DB::table('webwork_private_to_natives')->insert([
                            'question_id' => $question->id,
                            'original_path' => $question->technology_id,
                            'webwork_code'=> $pg_code,
                            'status' => 'Copied',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }
            DB::commit();
        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;

        }

        return 0;
    }

    function copyfolder($from, $to, $ext = "*")
    {
        if (!is_dir($from)) {
            exit("$from does not exist");
        }

        if (!is_dir($to)) {
            if (!mkdir($to)) {
                exit("Failed to create $to");
            };
            echo "$to created\r\n";
        }

        $all = glob("$from$ext", GLOB_MARK);
        if (count($all) > 0) {
            foreach ($all as $a) {
                $ff = basename($a); // CURRENT FILE/FOLDER
                if (is_dir($a)) {
                    $this->copyfolder("$from$ff/", "$to$ff/");
                } else {
                    if (!copy($a, "$to$ff")) {
                        exit("Error copying $a to $to$ff");
                    }
                    echo "$a copied to $to$ff\r\n";
                }
            }
        }
    }
}
