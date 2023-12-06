<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use App\LmsAPI;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUpdateLMSAssignmentDates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $course;
    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($course, $data)
    {
        $this->course = $course;
        $this->data = $data;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle()
    {

        $lmsApi = new LmsAPI();
        foreach ($this->course->assignments as $assignment) {
            Log::info(print_r($this->data,1));
            Log::info($this->course->start_date);
            Log::info($this->course->end_date);
            try {
                $lms_result = $lmsApi->updateAssignment(
                    $this->course->getLtiRegistration(),
                    $this->course->user_id,
                    $this->course->lms_course_id,
                    $assignment->lms_assignment_id,
                    $this->course->getIsoStartAndEndDates($this->data));
                if ($lms_result['type'] === 'error') {
                    throw new Exception("Error updating assignment $assignment->id on  LMS: " . $lms_result['message']);
                }
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
            }
        }
    }
}
