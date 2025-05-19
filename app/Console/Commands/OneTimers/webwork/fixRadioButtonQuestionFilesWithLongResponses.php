<?php

namespace App\Console\Commands\OneTimers\webwork;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class fixRadioButtonQuestionFilesWithLongResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:radioButtonQuestionFilesWithLongResponses';

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
            $long_radio_button_questions = DB::table('webwork_long_radio_buttons')
                ->select('question_id')->get()
                ->pluck('question_id')
                ->toArray();

            $latest_revisions = DB::table('question_revisions as qr1')
                ->whereRaw('qr1.revision_number = (
        SELECT MAX(qr2.revision_number)
        FROM question_revisions qr2
        WHERE qr2.question_id = qr1.question_id
    )')->whereIn('question_id', $long_radio_button_questions)
                ->get();
            $latest_revision_folders = [];
            $latest_revision_folder_question_ids = [];
            foreach ($latest_revisions as $latest_revision) {
                $latest_revision_folders[$latest_revision->question_id] = "$latest_revision->question_id-$latest_revision->id";
                $latest_revision_folder_question_ids[] = $latest_revision->question_id;
            }
            $long_radio_button_question_folders = [];
            foreach ($long_radio_button_questions as $long_radio_button_question_id) {
                $long_radio_button_question_folders[$long_radio_button_question_id] =
                    in_array($long_radio_button_question_id, $latest_revision_folder_question_ids) ?
                        $latest_revision_folders[$long_radio_button_question_id]
                        : $long_radio_button_question_id;
            }


            $localBase = env('DOWNLOAD_PATH') . "/long-radio-buttons";

            $remoteUser = env('WEBWORK_REMOTE_USER');
            $remoteHost = env('WEBWORK_REMOTE_HOST');
            $sshKeyPath = env('WEBWORK_SSH_KEY_PATH');
            $remoteBasePath = "/opt/webwork/private/ww_files";

            $directories = File::directories($localBase);

            foreach ($directories as $dirPath) {
                $folderName = basename($dirPath);
                $localFile = $dirPath . '/code.pg';
                $remoteFile = "$remoteUser@$remoteHost:$remoteBasePath/$folderName/code.pg";

                if (!File::exists($localFile)) {
                    $this->warn("No code.pg file in $folderName, skipping...");
                    continue;
                }

                if (false) {
                    $this->line("[DRY RUN] Would upload: $localFile → $remoteFile");
                } else {
                    $this->info("Uploading $localFile to $remoteFile");

                    $process = new Process([
                        'scp',
                        '-i', $sshKeyPath,
                        $localFile,
                        $remoteFile,
                    ]);

                    $process->run();

                    if ($process->isSuccessful()) {
                        $this->info("Uploaded $folderName/code.pg successfully.");
                    } else {
                        $this->error("Failed to upload $folderName/code.pg");
                        $this->error($process->getErrorOutput());
                    }

                }
            }
            exit;

            //the following code does the downloading and updates the code.
            foreach ($long_radio_button_question_folders as $dir) {
                $localDir = $localBase . DIRECTORY_SEPARATOR . $dir;
                if (!is_dir($localDir)) {
                    mkdir($localDir, 0755, true);
                    $this->info("Created directory $localDir");
                }

                $remoteFile = "{$remoteUser}@{$remoteHost}:{$remoteBasePath}/{$dir}/code.pg";
                $localFile = "{$localDir}/code.pg";

                $this->info("Downloading $remoteFile to $localFile ...");

                $command = [
                    'scp',
                    '-i',
                    $sshKeyPath,
                    $remoteFile,
                    $localFile,
                ];

                $process = new Process($command);
                $process->run();

                if ($process->isSuccessful()) {
                    $this->info("Downloaded $dir/code.pg successfully.");
                } else {
                    $this->error("Failed to download $dir/code.pg");
                    $this->error($process->getErrorOutput());
                    exit;
                }
            }
            //save the original
            File::copyDirectory($localBase, env('DOWNLOAD_PATH') . "/long-radio-buttons-original");

            //update the code
            foreach ($long_radio_button_questions as $long_radio_button_question_id) {
                $file_path =
                    in_array($long_radio_button_question_id, $latest_revision_folder_question_ids) ?
                        $latest_revision_folders[$long_radio_button_question_id]
                        : $long_radio_button_question_id;
                $new_webwork_code = DB::table('webwork_long_radio_buttons')
                    ->where('question_id', $long_radio_button_question_id)
                    ->first()
                    ->new_webwork_code;
                file_put_contents("$localBase/$file_path/code.pg", $new_webwork_code);
                echo "Updated $file_path";
            }


            $this->info('All done!');
        } catch (Exception $e) {
            echo $e->getMessage();

        }

        return 0;
    }
}
