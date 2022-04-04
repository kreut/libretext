<?php

namespace Tests\Feature\Students;

use App\Assignment;
use App\AssignmentQuestionLearningTree;
use App\AssignToTiming;
use App\Course;
use App\Enrollment;
use App\Question;
use App\Section;
use App\Traits\Test;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentLearningTreesInAssignmentsTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;


        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);


        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id,
            'solutions_released' => 0,
            'assessment_type' => 'learning tree',
            'submission_count_percent_decrease' => 10,
            'percent_earned_for_exploring_learning_tree' => 50]);
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student_user->id);
        $this->question = factory(Question::class)->create(['id' => 1, 'page_id' => 1860, 'library' => 'query']);


        $assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 10
        ]);
        $learning_tree = '{"html":"<div class=\'blockelem noselect block\' style=\'border: 1px solid rgb(0, 96, 188); left: 302px; top: 127.797px;\'>\n        <input type=\'hidden\' name=\'blockelemtype\' class=\'blockelemtype\' value=\'2\'>\n        <input type=\'hidden\' name=\'page_id\' value=\'1860\'>\n        <input type=\'hidden\' name=\'library\' value=\'query\'>\n\n      \n    <input type=\'hidden\' name=\'blockid\' class=\'blockid\' value=\'0\'><div class=\'blockyleft\'>\n<p class=\'blockyname\' style=\'margin-bottom:0;\'> <img src=\'/assets/img/query.svg\' alt=\'query\' style=\'#0060bc\'><span class=\'library\'>Query</span> - <span class=\'page_id\'>1860</span> \n<span class=\'extra\'></span></p></div><p></p>\n<div class=\'blockydiv\'></div>\n<div class=\'blockyinfo\'>Cláusulas con \'si\' en situaci...\n</div><div class=\'indicator invisible\' style=\'left: 116px; top: 116px;\'></div></div><div class=\'blockelem noselect block\' style=\'border: 1px solid #0060bc; left: 220px; top: 292.797px\'>\n        <input type=\'hidden\' name=\'blockelemtype\' class=\'blockelemtype\' value=\'2\'>\n        <input type=\'hidden\' name=\'page_id\' value=\'103264\'>\n        <input type=\'hidden\' name=\'library\' value=\'query\'>\n\n      \n    <input type=\'hidden\' name=\'blockid\' class=\'blockid\' value=\'1\'><div class=\'blockyleft\'>\n<p class=\'blockyname\' style=\'margin-bottom:0;\'><img src=\'/assets/img/query.svg\' alt=\'query\' style=\'#0060bc\'><span class=\'library\'>Query</span> - <span class=\'page_id\'>103264</span></p></div><p></p>\n<div class=\'blockydiv\'></div>\n<div class=\'blockyinfo\'>Title</div></div><div class=\'arrowblock\' style=\'left: 336px; top: 242.797px;\'><input type=\'hidden\' class=\'arrowid\' value=\'1\'><svg preserveAspectRatio=\'none\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M146 0L146 25L5 25L5 50\' stroke=\'#C5CCD0\' stroke-width=\'2px\'></path><path d=\'M0 45H10L5 50L0 45Z\' fill=\'#C5CCD0\'></path></svg></div><div class=\'blockelem noselect block\' style=\'border: 1px solid rgb(0, 96, 188); left: 79px; top: 457.797px;\'>\n        <input type=\'hidden\' name=\'blockelemtype\' class=\'blockelemtype\' value=\'2\'>\n        <input type=\'hidden\' name=\'page_id\' value=\'1864\'>\n        <input type=\'hidden\' name=\'library\' value=\'query\'>\n\n      \n    <input type=\'hidden\' name=\'blockid\' class=\'blockid\' value=\'3\'><div class=\'blockyleft\'>\n<p class=\'blockyname\' style=\'margin-bottom:0;\'> <img src=\'/assets/img/query.svg\' alt=\'query\' style=\'#0060bc\'><span class=\'library\'>Query</span> - <span class=\'page_id\'>1864</span> \n<span class=\'extra\'></span></p></div><p></p>\n<div class=\'blockydiv\'></div>\n<div class=\'blockyinfo\'>7.1 Actividad # 2: Cláusulas ...\n</div></div><div class=\'arrowblock\' style=\'left: 195px; top: 407.797px;\'><input type=\'hidden\' class=\'arrowid\' value=\'3\'><svg preserveAspectRatio=\'none\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M146 0L146 25L5 25L5 50\' stroke=\'#C5CCD0\' stroke-width=\'2px\'></path><path d=\'M0 45H10L5 50L0 45Z\' fill=\'#C5CCD0\'></path></svg></div><div class=\'blockelem noselect block\' style=\'border: 1px solid rgb(0, 96, 188); left: 361px; top: 457.797px;\'>\n        <input type=\'hidden\' name=\'blockelemtype\' class=\'blockelemtype\' value=\'2\'>\n        <input type=\'hidden\' name=\'page_id\' value=\'1865\'>\n        <input type=\'hidden\' name=\'library\' value=\'query\'>\n\n      \n    <input type=\'hidden\' name=\'blockid\' class=\'blockid\' value=\'4\'><div class=\'blockyleft\'>\n<p class=\'blockyname\' style=\'margin-bottom:0;\'> <img src=\'/assets/img/query.svg\' alt=\'query\' style=\'#0060bc\'><span class=\'library\'>Query</span> - <span class=\'page_id\'>1865</span> \n<span class=\'extra\'></span></p></div><p></p>\n<div class=\'blockydiv\'></div>\n<div class=\'blockyinfo\'>7.1 Actividad #3: Cláusulas c...\n</div></div><div class=\'arrowblock\' style=\'left: 321px; top: 407.797px;\'><input type=\'hidden\' class=\'arrowid\' value=\'4\'><svg preserveAspectRatio=\'none\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M20 0L20 25L161 25L161 50\' stroke=\'#C5CCD0\' stroke-width=\'2px\'></path><path d=\'M156 45H166L161 50L156 45Z\' fill=\'#C5CCD0\'></path></svg></div><div class=\'blockelem noselect block\' style=\'border: 1px solid rgb(0, 96, 188); left: 643px; top: 292.797px;\'>\n        <input type=\'hidden\' name=\'blockelemtype\' class=\'blockelemtype\' value=\'2\'>\n        <input type=\'hidden\' name=\'page_id\' value=\'103761\'>\n        <input type=\'hidden\' name=\'library\' value=\'query\'>\n\n      \n    <input type=\'hidden\' name=\'blockid\' class=\'blockid\' value=\'5\'><div class=\'blockyleft\'>\n<p class=\'blockyname\' style=\'margin-bottom:0;\'> <img src=\'/assets/img/query.svg\' alt=\'query\' style=\'#0060bc\'><span class=\'library\'>Query</span> - <span class=\'page_id\'>103761</span> \n<span class=\'extra\'></span></p></div><p></p>\n<div class=\'blockydiv\'></div>\n<div class=\'blockyinfo\'>3.27: Example 3.3.2\n</div></div><div class=\'arrowblock\' style=\'left: 462px; top: 242.797px;\'><input type=\'hidden\' class=\'arrowid\' value=\'5\'><svg preserveAspectRatio=\'none\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M20 0L20 25L302 25L302 50\' stroke=\'#C5CCD0\' stroke-width=\'2px\'></path><path d=\'M297 45H307L302 50L297 45Z\' fill=\'#C5CCD0\'></path></svg></div>","blockarr":[{"childwidth":806,"parent":-1,"id":0,"x":823,"y":185.296875,"width":242,"height":115},{"childwidth":524,"parent":0,"id":1,"x":682,"y":350.296875,"width":242,"height":115},{"childwidth":0,"parent":1,"id":3,"x":541,"y":515.296875,"width":242,"height":115},{"childwidth":0,"parent":1,"id":4,"x":823,"y":515.296875,"width":242,"height":115},{"childwidth":0,"parent":0,"id":5,"x":1105,"y":350.296875,"width":242,"height":115}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"1860"},{"name":"library","value":"query"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 96, 188); left: 302px; top: 127.797px;"}]},{"id":1,"parent":0,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"103264"},{"name":"library","value":"query"},{"name":"blockid","value":"1"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid #0060bc; left: 220px; top: 292.797px"}]},{"id":3,"parent":1,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"1864"},{"name":"library","value":"query"},{"name":"blockid","value":"3"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 96, 188); left: 79px; top: 457.797px;"}]},{"id":4,"parent":1,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"1865"},{"name":"library","value":"query"},{"name":"blockid","value":"4"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 96, 188); left: 361px; top: 457.797px;"}]},{"id":5,"parent":0,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"103761"},{"name":"library","value":"query"},{"name":"blockid","value":"5"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 96, 188); left: 643px; top: 292.797px;"}]}]}';

        $this->learning_tree_id = DB::table('learning_trees')->insertGetId(
            ['title' => 'some title',
                'description' => 'some description',
                'root_node_page_id' => 1860,
                'root_node_library' => 'query',
                'user_id' => $this->user->id,
                'learning_tree' => $learning_tree
            ]);


        $this->assignment_question_learning_tree_id = DB::table('assignment_question_learning_tree')->insertGetId([
            'assignment_question_id' => $assignment_question_id,
            'learning_tree_id' => $this->learning_tree_id,
            'learning_tree_success_level' => 'branch',
            'learning_tree_success_criteria' => 'assessment based',
            'min_number_of_successful_assessments' => 1,
            'number_of_successful_branches_for_a_reset' => 1,
            'number_of_resets' => 1,
            'free_pass_for_satisfying_learning_tree_criteria' => 0]);

        //root node submission
        factory(Question::class)->create(['id' => 98165, 'page_id' => 103264, 'library' => 'query', 'technology' => 'h5p']);

        //within the tree
        factory(Question::class)->create(['id' => 3, 'page_id' => 1864, 'library' => 'query', 'technology' => 'h5p']);
        factory(Question::class)->create(['id' => 4, 'page_id' => 1865, 'library' => 'query', 'technology' => 'h5p']);
        factory(Question::class)->create(['id' => 5, 'page_id' => 103761, 'library' => 'query', 'technology' => 'h5p']);

        $submission = ['assignment_id' => $this->assignment->id,
            'learning_tree_id' => $this->learning_tree_id,
            'is_remediation' => false,
            'question_id' => 1,
            'submission' => '{"actor":{"account":{"name":"eaed7736-328c-4394-845c-d633c57df532","homePage":"https://studio.libretexts.org/"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://studio.libretexts.org/h5p/122/embed","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":122,"https://h5p.org/x-api/line-breaks":[]},"name":{"en-US":"7.3 Actividad # 1: Cláusulas con &quot;si&quot; en situac..."},"description":{"en-US":"7.3 Actividad # 1<br/>Instrucciones: Seleccione la forma apropiada de los verbos indicados en el imperfecto del subjuntivo o el condicional. [Consultar tema] [Diccionario]<br/>"},"type":"http://adlnet.gov/expapi/activities/cmi.interaction","interactionType":"choice","correctResponsesPattern":["4[,]7[,]13[,]21[,]28[,]31"],"choices":[{"id":"0","description":{"en-US":"Si"}},{"id":"1","description":{"en-US":"nosotros"}},{"id":"2","description":{"en-US":"tendríamos"}},{"id":"3","description":{"en-US":"/"}},{"id":"4","description":{"en-US":"tuviéramos"}},{"id":"5","description":{"en-US":"dinero"}},{"id":"6","description":{"en-US":"nos"}},{"id":"7","description":{"en-US":"compraríamos"}},{"id":"8","description":{"en-US":"/"}},{"id":"9","description":{"en-US":"compráramos"}},{"id":"10","description":{"en-US":"un"}},{"id":"11","description":{"en-US":"coche"}},{"id":"12","description":{"en-US":"nuevo"}},{"id":"13","description":{"en-US":"Iría"}},{"id":"14","description":{"en-US":"/"}},{"id":"15","description":{"en-US":"fuera"}},{"id":"16","description":{"en-US":"al"}},{"id":"17","description":{"en-US":"supermercado"}},{"id":"18","description":{"en-US":"si"}},{"id":"19","description":{"en-US":"sería"}},{"id":"20","description":{"en-US":"/"}},{"id":"21","description":{"en-US":"fuera"}},{"id":"22","description":{"en-US":"absolutamente"}},{"id":"23","description":{"en-US":"necesario"}},{"id":"24","description":{"en-US":"Si"}},{"id":"25","description":{"en-US":"yo"}},{"id":"26","description":{"en-US":"tendría"}},{"id":"27","description":{"en-US":"/"}},{"id":"28","description":{"en-US":"tuviera"}},{"id":"29","description":{"en-US":"tiempo"}},{"id":"30","description":{"en-US":"te"}},{"id":"31","description":{"en-US":"ayudaría"}},{"id":"32","description":{"en-US":"/"}},{"id":"33","description":{"en-US":"ayudara"}}]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.MarkTheWords-1.9","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":6,"raw":1,"scaled":0.1667},"completion":true,"success":false,"duration":"PT4.16S","response":"4"}}',
            'technology' => "h5p"
        ];
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'info']);//does not get it correct

        $this->remediation_submission = ['assignment_id' => $this->assignment->id,
            'branch_id' => 1,
            'is_remediation' => true,
            'learning_tree_id' => $this->learning_tree_id,
            'question_id' => 98165,
            'submission' => '{"actor":{"account":{"name":"eaed7736-328c-4394-845c-d633c57df532","homePage":"https://studio.libretexts.org/"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://studio.libretexts.org/h5p/733/embed","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":733},"name":{"en-US":"Nutrition Basics 1.111"},"description":{"en-US":"Alcohol is considered a nutrient because it provides energy.n"},"type":"http://adlnet.gov/expapi/activities/cmi.interaction","interactionType":"choice","correctResponsesPattern":["1"],"choices":[{"id":"0","description":{"en-US":"Truen"}},{"id":"1","description":{"en-US":"Falsen"}}]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.MultiChoice-1.14","objectType":"Activity"}]}},"result":{"score":{"min":0,"max":1,"raw":1,"scaled":1},"completion":true,"success":true,"duration":"PT13.13S","response":"1"}}',
            'technology' => "h5p"
        ];
    }



    /** @test */
    public function time_left_cannot_be_updated_if_not_during_due_period()
    {

        $assignToTiming = AssignToTiming::where('assignment_id', $this->assignment->id)->first();
        $assignToTiming->due = Carbon::yesterday();
        $assignToTiming->save();
        $time_left_data = ['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'learning_tree_id' => $this->learning_tree_id,
            'branch_id' => 1,
            'level' => 'branch',
            'seconds' => 0];

        $this->actingAs($this->student_user)->patchJson("/api/learning-tree-time-left", $time_left_data)
            ->assertJson(['message' => 'No responses will be saved since the due date for this assignment has passed.']);


    }

    /** @test */
    public function remediation_submission_will_not_be_processed_if_the_assignment_is_past_due()

    {
        $assignToTiming = AssignToTiming::where('assignment_id', $this->assignment->id)->first();
        $assignToTiming->due = Carbon::yesterday();
        $assignToTiming->save();
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->remediation_submission)
            ->assertJson(['message'=> 'Your submission was correct.  However, this assignment is closed and will not count towards a Root Assessment reset.']);

    }
    /** @test */
    public function for_a_branch_level_assignment_when_there_are_enough_successful_branches_the_students_get_a_reset()
    {
        DB::table('assignment_question_learning_tree')
            ->where('id', $this->assignment_question_learning_tree_id)
            ->update(['number_of_successful_branches_for_a_reset' => 2]);
        DB::table('learning_tree_successful_branches')
            ->insert(['user_id' => $this->student_user->id,
                'assignment_id' => $this->assignment->id,
                'learning_tree_id' => $this->learning_tree_id,
                'branch_id' => 12,
                'applied_to_reset' => 0]);

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->remediation_submission);
        $submission = DB::table('submissions')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->first();
        $this->assertEquals(1, $submission->reset_count);
    }

    /** @test */
    public function for_a_branch_level_assignment_will_not_give_a_reset_if_there_are_not_enough_successful_branches()
    {
        DB::table('assignment_question_learning_tree')
            ->where('id', $this->assignment_question_learning_tree_id)
            ->update(['number_of_successful_branches_for_a_reset' => 2]);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->remediation_submission);
        $submission = DB::table('submissions')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->first();
        $this->assertEquals(0, $submission->reset_count);


    }

    /** @test */
    public function for_a_branch_level_assignment_when_time_runs_out_student_gets_a_successful_branch()
    {
        DB::table('assignment_question_learning_tree')
            ->where('id', $this->assignment_question_learning_tree_id)
            ->update(['learning_tree_success_criteria' => 'time based']);

        DB::table('learning_tree_time_lefts')->insert(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'level' => 'branch',
            'learning_tree_id' => $this->learning_tree_id,
            'branch_id' => 1,
            'time_left' => 2]);
        $time_left_data = ['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'learning_tree_id' => $this->learning_tree_id,
            'branch_id' => 1,
            'level' => 'branch',
            'seconds' => 0];

        $this->actingAs($this->student_user)->patchJson("/api/learning-tree-time-left", $time_left_data)
            ->assertJson(['can_resubmit_root_node_question' => true]);
        $this->assertDatabaseHas('learning_tree_successful_branches', ['user_id' => $this->student_user->id, 'branch_id' => 1]);
    }

    /** @test */
    public function for_a_tree_level_assignment_when_time_runs_out_student_gets_a_successful_tree()
    {
        DB::table('assignment_question_learning_tree')
            ->where('id', $this->assignment_question_learning_tree_id)
            ->update(['learning_tree_success_level' => 'tree',
                'learning_tree_success_criteria' => 'time based']);

        DB::table('learning_tree_time_lefts')->insert(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'level' => 'tree',
            'learning_tree_id' => $this->learning_tree_id,
            'time_left' => 2]);
        $time_left_data = ['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'learning_tree_id' => $this->learning_tree_id,
            'level' => 'tree',
            'seconds' => 0];
        $this->actingAs($this->student_user)->patchJson("/api/learning-tree-time-left", $time_left_data)
            ->assertJson(['can_resubmit_root_node_question' => true]);
    }

    /** @test */
    public function for_a_branch_level_assignment_when_the_correct_number_of_assessments_are_answered_students_get_a_successful_branch()
    {
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->remediation_submission);
        $this->assertDatabaseHas('learning_tree_successful_branches', ['user_id' => $this->student_user->id, 'branch_id' => 1]);

    }




    /** @test */
    public function for_a_tree_level_assignment_will_get_reset_if_correct_number_of_remediations_are_answered()
    {
        DB::table('assignment_question_learning_tree')
            ->where('id', $this->assignment_question_learning_tree_id)
            ->update(['learning_tree_success_level' => 'tree',
                'learning_tree_success_criteria'=>'assessment based',
                'min_number_of_successful_assessments' => 1]);

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->remediation_submission)
            ->assertJson(['can_resubmit_root_node_question' => true]);
        $submission = DB::table('submissions')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->first();
        $this->assertEquals(1, $submission->reset_count);

    }

    /** @test */
    public function for_a_tree_level_assignment_will_not_get_reset_if_correct_number_of_remediations_are_not_answered()
    {

        DB::table('assignment_question_learning_tree')
            ->where('id', $this->assignment_question_learning_tree_id)
            ->update(['learning_tree_success_level' => 'tree',
                'learning_tree_success_criteria'=>'assessment based',
                'min_number_of_successful_assessments' => 2]);


        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->remediation_submission)
            ->assertJson(['can_resubmit_root_node_question' => false]);
        $submission = DB::table('submissions')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->first();
        $this->assertEquals(0, $submission->reset_count);

    }


}
