<?php

namespace App;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Snowfire\Beautymail\Beautymail;

class QuestionMediaUpload extends Model
{

    protected $guarded = [];

    /**
     * @return string
     */
    public function getText(): string
    {
        if (strpos($this->s3_key, '.html') !== false) {
            try {
                $text = $this->text;
                if (!$text) {
                    $this->text = Storage::disk('s3')->get($this->getDir() . '/' . $this->s3_key);
                    $this->save();
                    $text = $this->text;
                }
            } catch (Exception $e) {
                $text = "Unable to retrieve the text for this discuss it question.";
            }
        } else {
            $text = '';
        }
        return $text;
    }

    /**
     * @throws Exception
     */
    public function deleteFileAndVttFile($filename)
    {

        $s3_key = $filename ? $this->getDir() . "/" . $filename : false;
        if ($s3_key && (Storage::disk('s3')->exists($s3_key))) {
            Storage::disk('s3')->delete($s3_key);
        }

        $vtt_file = $this->getVttFileNameFromS3Key($s3_key);
        if ($vtt_file && (Storage::disk('s3')->exists($vtt_file))) {
            Storage::disk('s3')->delete($vtt_file);
        }
    }

    /**
     * @param string $media
     * @return string
     * @throws Exception
     */
    public function getVttFileNameFromS3Key(string $media = ''): string
    {
        $s3_key = $media ?: $this->s3_key;
        $file_name_without_ext = pathinfo($s3_key, PATHINFO_FILENAME);
        return $file_name_without_ext ? "$file_name_without_ext.vtt" : '';
    }


    /**
     * @param string $type
     * @return void
     * @throws Exception
     */
    public function emailResult(string $type)
    {
        try {

            $instructor = DB::table('question_media_uploads')
                ->join('questions', 'question_media_uploads.question_id', '=', 'questions.id')
                ->join('users', 'questions.question_editor_user_id', '=', 'users.id')
                ->where('question_media_uploads.id', $this->id)
                ->select('first_name', 'last_name', 'email')
                ->first();
            $mail_info = [
                'first_name' => $instructor->first_name,
                'question_id' => $this->question_id,
                'original_filename' => $this->original_filename,
                'url' => Helper::schemaAndHost() . "source/edit/{$this->question_id}/$this->id",
                'success' => $this->status === 'completed'
            ];
            $beautymail = app()->make(Beautymail::class);
            $beautymail->send('emails.question_media_uploads_transcription_results', $mail_info, function ($message)
            use ($instructor) {
                $message->from('adapt@noreply.libretexts.org', 'ADAPT')
                    ->to($instructor->email, $instructor->first_name . ' ' . $instructor->last_name)
                    ->subject('Transcript Completed');
            });
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

        }

    }

    /**
     * @return string
     */
    function getDir(): string
    {
        return "uploads/question-media";
    }

    /**
     * @param $array
     * @return string
     */
    function convertArrayToVTT($array): string
    {
        $vtt = "WEBVTT\n\n";
        foreach ($array as $entry) {
            $vtt .= $entry['start'] . " --> " . $entry['end'] . "\n";
            $vtt .= $entry['text'] . "\n\n";
        }
        return $vtt;
    }

    /**
     * @param $vttContent
     * @return array
     */
    function parseVtt($vttContent): array
    {
        $lines = explode("\n", $vttContent);
        $result = [];
        $timePattern = '/^(\d{2}:\d{2}:\d{2}\.\d{3}) --> (\d{2}:\d{2}:\d{2}\.\d{3})$/';

        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match($timePattern, $lines[$i], $matches)) {
                $start = $matches[1];
                $end = $matches[2];
                $text = $lines[$i + 1];
                $result[] = [
                    'start' => $start,
                    'end' => $end,
                    'text' => $text
                ];
                $i++; // Skip the text line
            }
        }

        return $result;
    }

    function convertMovToMP4()
    {

        $inputVideo = '/Users/franciscaparedes/adapt_laravel_8/storage/app/videos/Spanish_Recording.mov';
        $outputVideo = '/Users/franciscaparedes/adapt_laravel_8/storage/app/videos/Spanish_Recording.mp4';

        $command = escapeshellcmd("ffmpeg -y -i {$inputVideo} -vcodec h264 -acodec aac {$outputVideo}");

        $output = shell_exec($command);
        $return_var = null;
        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            // Command failed, handle the error
            echo "Error occurred during video conversion.\n";
            echo "Command output:\n";
            echo implode("\n", $output);
        } else {
            // Command succeeded
            echo "Video conversion completed successfully.\n";
        }

    }
}
