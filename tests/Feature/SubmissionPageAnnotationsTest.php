<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Question;
use App\Section;
use App\SubmissionFileConversion;
use App\SubmissionFilePage;
use App\SubmissionPageAnnotation;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Grader markup endpoints, page ranges and end-page validation.
 */
class SubmissionPageAnnotationsTest extends TestCase
{
    use Test;

    private $instructor;
    private $other_instructor;
    private $student;
    private $course;
    private $assignment;
    private $question;

    public function setup(): void
    {
        parent::setUp();
        $this->instructor = factory(User::class)->create();
        $this->other_instructor = factory(User::class)->create();
        $this->student = factory(User::class)->create(['role' => 3]);

        $this->course = factory(Course::class)->create(['user_id' => $this->instructor->id]);
        $section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create();
        Enrollment::create(['course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'section_id' => $section->id]);
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student->id);

        // the student's compiled PDF has 6 pages; this question was given pages 2-4
        DB::table('submission_files')->insert([
            'type' => 'q',
            'user_id' => $this->student->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'original_filename' => 'homework.pdf',
            'submission' => 'compiled.pdf',
            'page' => 2,
            'end_page' => 4,
            'date_submitted' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        SubmissionFileConversion::create([
            'assignment_id' => $this->assignment->id,
            'submission' => 'compiled.pdf',
            'status' => SubmissionFileConversion::COMPLETED,
            'number_of_pages' => 6
        ]);
    }

    private function _url(?int $page = null): string
    {
        $url = "/api/submission-page-annotations/assignment/{$this->assignment->id}/question/{$this->question->id}/student/{$this->student->id}";
        return $page ? "$url/page/$page" : $url;
    }

    private function _state(int $number_of_markers = 1): array
    {
        $markers = [];
        for ($i = 0; $i < $number_of_markers; $i++) {
            $markers[] = ['typeName' => 'ArrowMarker', 'x1' => 10, 'y1' => 10, 'x2' => 200, 'y2' => 150,
                'strokeColor' => '#d0021b', 'strokeWidth' => 3];
        }
        return ['version' => 3, 'width' => 1275, 'height' => 1650, 'markers' => $markers];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_instructor_can_save_and_read_markup_on_a_page_in_the_questions_range()
    {
        $this->actingAs($this->instructor)
            ->putJson($this->_url(3), ['state' => $this->_state()])
            ->assertJson(['type' => 'success', 'message' => 'Your markup on Page 3 has been saved.']);

        $this->assertDatabaseHas('submission_page_annotations', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student->id,
            'page' => 3,
            'grader_id' => $this->instructor->id
        ]);
        $this->actingAs($this->instructor)
            ->getJson($this->_url())
            ->assertJson(['type' => 'success', 'annotations' => ['3' => ['width' => 1275, 'height' => 1650]]]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function saving_again_replaces_the_pages_markup()
    {
        $this->actingAs($this->instructor)->putJson($this->_url(2), ['state' => $this->_state(1)]);
        $this->actingAs($this->instructor)->putJson($this->_url(2), ['state' => $this->_state(3)]);

        $annotations = SubmissionPageAnnotation::where('page', 2)->where('user_id', $this->student->id)->get();
        $this->assertCount(1, $annotations);
        $this->assertCount(3, $annotations[0]->state['markers']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function saving_no_markers_removes_the_markup()
    {
        $this->actingAs($this->instructor)->putJson($this->_url(2), ['state' => $this->_state()]);
        $this->actingAs($this->instructor)
            ->putJson($this->_url(2), ['state' => $this->_state(0)])
            ->assertJson(['type' => 'success', 'message' => 'The markup on Page 2 has been removed.']);
        $this->assertDatabaseMissing('submission_page_annotations', ['user_id' => $this->student->id, 'page' => 2]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_page_outside_the_questions_range_cannot_be_marked_up()
    {
        $this->actingAs($this->instructor)
            ->putJson($this->_url(5), ['state' => $this->_state()])
            ->assertJson(['type' => 'error', 'message' => "Page 5 isn't part of this student's submission for this question."]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function an_old_start_page_only_assignment_cannot_be_marked_up()
    {
        DB::table('submission_files')
            ->where('user_id', $this->student->id)
            ->where('question_id', $this->question->id)
            ->update(['end_page' => null]);
        $this->actingAs($this->instructor)
            ->putJson($this->_url(2), ['state' => $this->_state()])
            ->assertJson(['type' => 'error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function markup_needs_a_finished_conversion()
    {
        SubmissionFileConversion::where('submission', 'compiled.pdf')
            ->update(['status' => SubmissionFileConversion::PROCESSING]);
        $this->actingAs($this->instructor)
            ->putJson($this->_url(2), ['state' => $this->_state()])
            ->assertJson(['type' => 'error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_state_is_rejected()
    {
        $this->actingAs($this->instructor)
            ->putJson($this->_url(2), ['state' => ['markers' => 'nope']])
            ->assertJson(['type' => 'error', 'message' => 'The markup could not be read.  Please try again.']);
        $this->assertDatabaseMissing('submission_page_annotations', ['user_id' => $this->student->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function too_many_markers_are_rejected()
    {
        $this->actingAs($this->instructor)
            ->putJson($this->_url(2), ['state' => $this->_state(501)])
            ->assertJson(['type' => 'error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_student_cannot_save_markup()
    {
        $this->actingAs($this->student)
            ->putJson($this->_url(2), ['state' => $this->_state()])
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to mark up this submission.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function another_instructor_cannot_read_or_save_markup()
    {
        $this->actingAs($this->other_instructor)
            ->putJson($this->_url(2), ['state' => $this->_state()])
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to mark up this submission.']);
        $this->actingAs($this->other_instructor)
            ->getJson($this->_url())
            ->assertJson(['type' => 'error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_end_page_cannot_be_before_the_start_page()
    {
        $this->actingAs($this->student)
            ->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/page",
                ['page' => 4, 'end_page' => 2, 'question_number' => 1])
            ->assertJsonValidationErrors(['end_page']);
    }

    private function _mineUrl(): string
    {
        return "/api/submission-page-annotations/assignment/{$this->assignment->id}/question/{$this->question->id}/mine";
    }

    private function _releaseScores(bool $released): void
    {
        $this->assignment->show_scores = $released ? 1 : 0;
        $this->assignment->save();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_student_cannot_see_markup_before_scores_are_released()
    {
        $this->_releaseScores(false);
        $this->actingAs($this->instructor)->putJson($this->_url(3), ['state' => $this->_state()]);

        $this->actingAs($this->student)
            ->getJson($this->_mineUrl())
            ->assertJson(['type' => 'error', 'message' => "Your grader's markup will be available once scores are released."]);
        // nor through the grader's endpoint
        $this->actingAs($this->student)
            ->getJson($this->_url())
            ->assertJson(['type' => 'error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_student_sees_their_marked_up_pages_once_scores_are_released()
    {
        $this->_releaseScores(true);
        foreach ([2, 3, 4] as $page) {
            SubmissionFilePage::create([
                'assignment_id' => $this->assignment->id,
                'submission' => 'compiled.pdf',
                'page' => $page,
                's3_key' => SubmissionFilePage::s3Key($this->assignment->id, 'compiled.pdf', $page),
                'width' => 1275,
                'height' => 1650
            ]);
        }
        $this->actingAs($this->instructor)->putJson($this->_url(3), ['state' => $this->_state()]);

        $response = $this->actingAs($this->student)
            ->getJson($this->_mineUrl())
            ->assertJson(['type' => 'success', 'annotations' => ['3' => ['width' => 1275]]]);
        $this->assertEquals([2, 3, 4], array_column($response->json('pages'), 'page'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_student_with_no_markup_gets_no_pages()
    {
        $this->_releaseScores(true);
        $this->actingAs($this->student)
            ->getJson($this->_mineUrl())
            ->assertJson(['type' => 'success', 'pages' => []]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_student_cannot_read_another_students_markup()
    {
        $this->_releaseScores(true);
        $other_student = factory(User::class)->create(['role' => 3]);
        $this->actingAs($other_student)
            ->getJson($this->_url())
            ->assertJson(['type' => 'error']);
        $this->actingAs($other_student)
            ->getJson($this->_mineUrl())
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to view this submission.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function page_ranges_follow_the_rules()
    {
        // a PDF uploaded for one question: all of it
        $this->assertEquals([1, 6], SubmissionFilePage::pageRange(null, null, 6));
        // a range from a compiled PDF
        $this->assertEquals([2, 4], SubmissionFilePage::pageRange(2, 4, 6));
        // an end page past the end of the PDF is clamped
        $this->assertEquals([5, 6], SubmissionFilePage::pageRange(5, 9, 6));
        // a start page only (assigned before end pages existed): the PDF view
        $this->assertNull(SubmissionFilePage::pageRange(2, null, 6));
        // no page count: nothing to show
        $this->assertNull(SubmissionFilePage::pageRange(2, 4, null));
    }
}
