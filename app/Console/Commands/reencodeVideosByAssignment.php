<?php

namespace App\Console\Commands;

use App\Assignment;
use App\QuestionMediaUpload;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class reencodeVideosByAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reencode:VideosByAssignment';

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
            $assignment_id = 152633;
            $assignment = Assignment::find($assignment_id);
            $question_ids = $assignment->questions->pluck('id')->toArray();
            $question_media_uploads = QuestionMediaUpload::whereIn('question_id', $question_ids)
                ->get()
                ->groupBy('s3_key')
                ->map->first();
            foreach ($question_media_uploads as $file) {
                $s3_key = $file->s3_key;
                // Download the file
                $contents = Storage::disk('production_s3')->get("uploads/question-media/$s3_key");
                $localPath = "/Users/franciscaparedes/Downloads/converted_to_new_format/$s3_key";
                file_put_contents($localPath, $contents);

                // Check codec with ffprobe
                $command = "ffprobe -v error -select_streams v:0 -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($localPath);
                $codec = trim(shell_exec($command));

                // If it's HEVC (hevc or h265), convert to H.264
                if ($codec === 'hevc' || $codec === 'h265') {
                    $outputPath = $localPath; // Same filename as input
                    $tempPath = "/Users/franciscaparedes/Downloads/temp_$s3_key";

                    // Convert to temp file first
                    $ffmpegCommand = "ffmpeg -i " . escapeshellarg($localPath) . " -c:v libx264 -crf 23 -c:a aac -movflags +faststart " . escapeshellarg($tempPath);
                    shell_exec($ffmpegCommand);

                    // Replace original with converted file
                    rename($tempPath, $outputPath);

                   Log::info("Converted HEVC to H.264: $s3_key\n");
                } else {
                    Log::info("Skipped (codec: $codec): $s3_key\n");
                }
            }

        } catch (Exception $e) {
            echo $e->getMessage();

        }
        return 0;
    }
}
