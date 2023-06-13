<?php


namespace App\Traits;

use App\Assignment;
use App\AssignmentGroupWeight;
use App\AssignToGroup;
use App\AssignToTiming;
use App\AssignToUser;
use App\Enrollment;
use App\Grader;
use App\Question;
use App\Score;
use App\Submission;
use App\SubmissionFile;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait Test
{
    public function headers(){
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($this->student_user);
        return [
            'Accept' => 'application/json',
            'AUTHORIZATION' => 'Bearer ' . $token
        ];
    }

    public function addQuestionRevisionInfo($question_info){
        $question_info['revision_action'] = 'notify';
        $question_info['reason_for_edit'] = 'blah blah';
        $question_info['automatically_update_revision'] = false;
        return $question_info;

    }

    public function createAssignTosFromGroups($assignment_info, $groups){
        $assign_tos= [
            [
                'groups' =>  $groups,
                'available_from' => '2020-06-10 09:00:00',
                'available_from_date' => '2020-06-10',
                'available_from_time' => '9:00 AM',
                'due' => '2020-06-12 09:00:00',
                'due_date' => '2020-06-12',
                'due_time' => '9:00 AM',
                'final_submission_deadline' => '2021-06-12 09:00:00',
                'final_submission_deadline_date' => '2021-06-12',
                'final_submission_deadline_time' => '9:00 AM',
            ]
        ];
        $assignment_info['assign_tos'] = $assign_tos;
        foreach ( $assignment_info['assign_tos'][0]['groups'] as $key => $group) {
            $group_info = ["groups_$key" => [$groups],
                "due_$key" => '2020-06-12 09:00:00',
                "due_date_$key" => '2020-06-12',
                "due_time_$key" => '9:00 AM',
                "available_from_$key" => '2020-06-10',
                "available_from_date_$key" => '2020-06-12',
                "available_from_time_$key" => '9:00 AM',
                "final_submission_deadline_date_$key" => '2021-06-12',
                "final_submission_deadline_time_$key" => '9:00 AM'];
            foreach ($group_info as $info_key => $info_value) {
                $assignment_info[$info_key] = $info_value;
            }
        }
        return $assignment_info;
    }

    public function assignUserToAssignment(int $assignment_id, string $group, int $group_id, int $student_user_id = 0)
    {
        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $assignment_id;
        $assignToTiming->available_from = Carbon::now();
        $assignToTiming->due = Carbon::now()->addHours(1);
        $assignToTiming->save();

        $assignToGroup = new AssignToGroup();
        $assignToGroup->group = $group;
        $assignToGroup->group_id = $group_id;
        $assignToGroup->assign_to_timing_id = $assignToTiming->id;
        $assignToGroup->save();

        if ($student_user_id) {
            $assignToUser = new AssignToUser();
            $assignToUser->user_id = $student_user_id;
            $assignToUser->assign_to_timing_id = $assignToTiming->id;
            $assignToUser->save();
        }
    }

    function question()
    {
        return $question = factory(Question::class)->create(['page_id' => rand(1, 1000000000)]);
    }


    function createAssignmentGroupWeightsAndAssignments()
    {


        //2 groups of assignments
        AssignmentGroupWeight::create([
            'course_id' => $this->course->id,
            'assignment_group_id' => 1,
            'assignment_group_weight' => 10
        ]);

        AssignmentGroupWeight::create([
            'course_id' => $this->course->id,
            'assignment_group_id' => 2,
            'assignment_group_weight' => 90
        ]);

        //GROUP 1
//assignment has 1 question
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question()->id,
            'order' => 1,
            'open_ended_submission_type' => 'none',
            'points' => 2
        ]);


        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'open_ended_submission_type' => 'none',
            'score' => 2
        ]);


        //assignment 1 has 3 questions
        $this->assignment_1 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_1',
            'show_scores' => 1,
            'assignment_group_id' => 1
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_1->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 10
        ]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_1->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 20
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_1->id,
            'score' => 5
        ]);
        //Assignment 1: 5/30

        //GROUP 1
        //assignment 2 has 2 questions
        $this->assignment_2 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_2',
            'show_scores' => 1,
            'assignment_group_id' => 1
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_2->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 1
        ]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_2->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 2
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_2->id,
            'score' => 2
        ]);

        //Assignment 2: 2/3

        //GROUP 2
        //assignment 3 has 2 questions
        $this->assignment_3 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_3',
            'show_scores' => 1,
            'assignment_group_id' => 2
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_3->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 50
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_3->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 50
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_3->id,
            'score' => 25
        ]);

        //Assignment 3: 25/100
        $this->assignment_4 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_4',
            'assignment_group_id' => 2,
            'source' => 'x',
            'show_scores' => 1,
            'external_source_points' => 100
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_4->id,
            'score' => 75
        ]);

        //GROUP 1 scores: 5/30 and 2/3 weight of 10
        //GROUP 2 scores: 25/30 weight of 90
        //10*((2/2 + 5/30 + 2/3)/3)+90*(.5*(25/100 + 75/100))=51.11%

        //Leaving out $this->assignment
        ////10*(( 5/30 + 2/3)/2)+90*(.5*(25/100 + 75/100))=49.17%
    }

    public function createStudentUsers()
    {
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->student_user->save();
        Enrollment::create(['course_id' => $this->course->id,
            'section_id' => $this->section->id,
            'user_id' => $this->student_user->id]);

        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;
        $this->student_user_2->save();
        Enrollment::create(['course_id' => $this->course->id,
            'section_id' => $this->section_1->id,
            'user_id' => $this->student_user_2->id]);
    }

    public function createAssignmentQuestions()
    {
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create(['page_id' => 3]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 4]);
        $this->question_points = 10;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);
    }

    public function createSubmissions()
    {
        $data = [
            'type' => 'q',
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => 'fake_1.pdf',
            'original_filename' => 'orig_fake_1.pdf',
            'date_submitted' => Carbon::now()];
        SubmissionFile::create($data);
        $data['user_id'] = $this->student_user_2->id;
        SubmissionFile::create($data);

        $this->h5pSubmission = [
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'score' => '0.00',
            'answered_correctly_at_least_once' => 0,
            'submission_count' => 1,
            'submission' => '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}'
        ];
        Submission::create($this->h5pSubmission);
        $this->h5pSubmission['user_id'] = $this->student_user_2->id;

        Submission::create($this->h5pSubmission);
    }

    public function createScores()
    {
        Score::create(['assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user->id,
            'score' => 10]);
        Score::create(['assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user_2->id,
            'score' => 10]);

    }

    public function addGraders()
    {
        $this->grader_user = factory(User::class)->create();
        $this->grader_user->role = 4;
        Grader::create(['user_id' => $this->grader_user->id, 'section_id' => $this->section_1->id]);
    }




}
