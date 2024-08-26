<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessResetCourse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $course;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($course)
    {
       $this->course = $course;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $client = Helper::centrifuge();
        try {
            $response = $this->course->reset();
            sleep(2);
            $client->publish("reset-course-{$this->course->id}", ["type" => $response['type'], "message"=> $response['message']]);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $client->publish("reset-course-{$this->course->id}", ["type" =>'error', "message"=> 'There was an error resetting the course. Please try again or contact us for assistance.']);
        }
    }
}
