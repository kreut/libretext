<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use App\SubmissionFileConversion;
use App\SubmissionFilePage;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Renders every page of a student's uploaded PDF (assignments/{assignment_id}/{submission}) to a 150 DPI JPEG
 * so graders can view and mark up the pages as images. An uploaded image becomes a single page as-is.
 *
 * Images are keyed by the uploaded file, not by question: a compiled PDF and every question whose page range
 * points into it share one set of images. The original PDF is never modified.
 *
 * Requires poppler-utils (pdftoppm): in the Docker image on Vapor, or set PDFTOPPM_PATH locally.
 */
class ConvertSubmissionPdfToImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const DPI = 150;
    const JPEG_QUALITY = 80;

    /**
     * Keep this below queue-timeout in vapor.yml.
     *
     * @var int
     */
    public $timeout = 540;

    /**
     * @var int
     */
    public $tries = 2;

    /**
     * @var int
     */
    private $assignment_id;
    /**
     * @var string
     */
    private $submission;
    /**
     * @var bool
     */
    private $force;

    /**
     * @param int $assignment_id
     * @param string $submission the filename stored in submission_files.submission
     * @param bool $force re-render even if a completed conversion exists
     */
    public function __construct(int $assignment_id, string $submission, bool $force = false)
    {
        $this->assignment_id = $assignment_id;
        $this->submission = $submission;
        $this->force = $force;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $conversion = SubmissionFileConversion::firstOrCreate(
            ['assignment_id' => $this->assignment_id, 'submission' => $this->submission],
            ['status' => SubmissionFileConversion::PENDING]
        );
        if ($conversion->status === SubmissionFileConversion::COMPLETED && !$this->force) {
            return;
        }
        $conversion->update(['status' => SubmissionFileConversion::PROCESSING, 'error_message' => null]);

        $work_dir = rtrim(sys_get_temp_dir(), '/') . '/pdf-pages-' . md5(uniqid('', true));
        try {
            if (!mkdir($work_dir, 0700, true)) {
                throw new Exception("Could not create the working directory $work_dir.");
            }
            $page_rows = self::isImage($this->submission)
                ? $this->_imagePageRows($work_dir)
                : $this->_renderPdfPages($work_dir);

            DB::transaction(function () use ($page_rows, $conversion) {
                SubmissionFilePage::where('assignment_id', $this->assignment_id)
                    ->where('submission', $this->submission)
                    ->delete();
                SubmissionFilePage::insert($page_rows);
                $conversion->update([
                    'status' => SubmissionFileConversion::COMPLETED,
                    'number_of_pages' => count($page_rows),
                    'error_message' => null
                ]);
            });
        } catch (Throwable $e) {
            $conversion->update([
                'status' => SubmissionFileConversion::FAILED,
                'error_message' => mb_substr($e->getMessage(), 0, 2000)
            ]);
            $h = new Handler(app());
            $h->report($e);
        } finally {
            $this->_removeDirectory($work_dir);
        }
    }

    /**
     * Renders each page of the PDF to a JPEG and uploads it.
     *
     * @param string $work_dir
     * @return array page rows for submission_file_pages
     * @throws Exception
     */
    private function _renderPdfPages(string $work_dir): array
    {
        $pdf_path = "$work_dir/source.pdf";
        $this->_downloadSubmission($pdf_path);

        $process = new Process([
            config('submission_pages.pdftoppm_path', 'pdftoppm'),
            '-r', (string)self::DPI,
            '-jpeg',
            '-jpegopt', 'quality=' . self::JPEG_QUALITY,
            $pdf_path,
            "$work_dir/page"
        ]);
        $process->setTimeout($this->timeout - 60);
        $process->run();
        if (!$process->isSuccessful()) {
            // e.g. an encrypted or damaged PDF; the grader keeps the PDF view for these
            throw new Exception('pdftoppm failed: ' . trim($process->getErrorOutput()));
        }
        // pdftoppm still "succeeds" when a font the PDF needs isn't installed: it just leaves that text out, so the
        // pages come out blank. Treat that as a failure, so the grader keeps the PDF view and the error is reported.
        if (preg_match_all("/Couldn't find a font for '([^']+)'/i", $process->getErrorOutput(), $matches)) {
            throw new Exception('pdftoppm is missing fonts (' . implode(', ', array_unique($matches[1]))
                . '); install fonts in the Docker image (see production.Dockerfile).');
        }

        // pdftoppm names files page-1.jpg or page-01.jpg (zero-padded to the page count's width)
        $rendered_pages = [];
        foreach (glob("$work_dir/page-*.jpg") as $file) {
            if (preg_match('/page-(\d+)\.jpg$/', $file, $matches)) {
                $rendered_pages[(int)$matches[1]] = $file;
            }
        }
        ksort($rendered_pages);
        if (!$rendered_pages) {
            throw new Exception('pdftoppm did not produce any pages.');
        }

        $page_rows = [];
        foreach ($rendered_pages as $page => $file) {
            $s3_key = SubmissionFilePage::s3Key($this->assignment_id, $this->submission, $page);
            $stream = fopen($file, 'r');
            try {
                Storage::disk('s3')->put($s3_key, $stream, ['ContentType' => 'image/jpeg']);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            $size = getimagesize($file);
            $page_rows[] = [
                'assignment_id' => $this->assignment_id,
                'submission' => $this->submission,
                'page' => $page,
                's3_key' => $s3_key,
                'width' => $size ? $size[0] : null,
                'height' => $size ? $size[1] : null,
                'created_at' => now(),
                'updated_at' => now()
            ];
            @unlink($file); // keep /tmp usage low on long PDFs
        }
        return $page_rows;
    }

    /**
     * An uploaded photo or scan is already an image: it becomes page 1 as-is, so graders can mark it up too.
     * Nothing new is uploaded; the page row points at the original file.
     *
     * @param string $work_dir
     * @return array
     * @throws Exception
     */
    private function _imagePageRows(string $work_dir): array
    {
        $path = "$work_dir/source";
        $this->_downloadSubmission($path);
        $size = @getimagesize($path);
        if (!$size) {
            throw new Exception('The uploaded image could not be read.');
        }
        return [[
            'assignment_id' => $this->assignment_id,
            'submission' => $this->submission,
            'page' => 1,
            's3_key' => "assignments/$this->assignment_id/$this->submission",
            'width' => $size[0],
            'height' => $size[1],
            'created_at' => now(),
            'updated_at' => now()
        ]];
    }

    /**
     * @param string $submission
     * @return bool
     */
    public static function isImage(string $submission): bool
    {
        return in_array(strtolower(pathinfo($submission, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
    }

    /**
     * Files that get page images: PDFs (rendered) and images (used as-is).
     *
     * @param string $submission
     * @return bool
     */
    public static function canConvert(string $submission): bool
    {
        return strtolower(pathinfo($submission, PATHINFO_EXTENSION)) === 'pdf' || self::isImage($submission);
    }

    /**
     * Streams the uploaded file from S3 to disk rather than loading it into memory.
     *
     * @param string $pdf_path
     * @return void
     * @throws Exception
     */
    private function _downloadSubmission(string $pdf_path): void
    {
        $source_key = "assignments/$this->assignment_id/$this->submission";
        $source = Storage::disk('s3')->readStream($source_key);
        if (!$source) {
            throw new Exception("Could not read $source_key from S3.");
        }
        $destination = fopen($pdf_path, 'w');
        try {
            stream_copy_to_stream($source, $destination);
        } finally {
            fclose($destination);
            if (is_resource($source)) {
                fclose($source);
            }
        }
    }

    /**
     * @param string $dir
     * @return void
     */
    private function _removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob("$dir/*") as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
