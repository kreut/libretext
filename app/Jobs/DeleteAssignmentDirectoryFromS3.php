<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteAssignmentDirectoryFromS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $assignment_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($assignment_id)
    {
        $this->assignment_id = $assignment_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        try {
            $assignments_directory = "assignments/$this->assignment_id";
            if (Storage::disk('s3')->exists($assignments_directory)) {
                Storage::disk('s3')->deleteDirectory($assignments_directory);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
