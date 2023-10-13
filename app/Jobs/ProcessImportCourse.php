<?php

namespace App\Jobs;


use App\Course;
use App\Events\ImportCopyCourse;
use App\Exceptions\Handler;
use App\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImportCourse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $request;
    private $import_as_beta;
    private $betaCourse;
    private $assignmentGroup;
    private $assignmentGroupWeight;
    private $assignmentSyncQuestion;
    private $enrollment;
    private $finalGrade;
    private $section;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Course
     */
    private $course;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Course $course,
                                User   $user,
                                       $request)
    {
        $this->course = $course;
        $this->user = $user;
        $this->request = $request;

    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle()
    {

        $response = $this->course->import($this->user,
            $this->request);
        try {
            sleep(2);
            event(new ImportCopyCourse($this->user->id, $response['type'], $response['message']));
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }


    }
}
