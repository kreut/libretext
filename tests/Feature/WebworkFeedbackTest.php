<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\Assignment;
use App\Course;
use App\JWE;
use App\Question;
use App\Submission;
use App\User;
use stdClass;
use Tests\TestCase;
use function factory;

class WebworkFeedbackTest extends TestCase
{
    private $student;
    private $instructor;
    private $question;
    private $assignment_question;

    public function setup(): void
    {
        parent::setUp();
        $this->student = factory(User::class)->create(['role' => 3]);
        $this->instructor = factory(User::class)->create(['role' => 2]);

        $this->question = new Question();
        $this->question->id = 1;
        $this->question->technology = 'webwork';

        // getSubmissionArray() only reads ->points from this
        $this->assignment_question = new stdClass();
        $this->assignment_question->points = 10;
    }

    /**
     * getSubmissionArray() reads request()->user(), which actingAs() alone doesn't set
     * when the method is called directly instead of through an HTTP request.
     */
    private function actAs(User $user): void
    {
        $this->actingAs($user);
        request()->setUserResolver(function () use ($user) {
            return $user;
        });
    }

    private function makeAssignment(string $assessment_type, int $solutions_released = 0): Assignment
    {
        $assignment = new Assignment();
        $assignment->id = 1;
        $assignment->assessment_type = $assessment_type;
        $assignment->solutions_released = $solutions_released;
        return $assignment;
    }

    private function makeSubmission(array $answers): stdClass
    {
        $submission = new stdClass();
        $submission->submission = json_encode(['score' => ['result' => 1, 'answers' => $answers]]);
        return $submission;
    }

    private function getSubmissionArray(Assignment $assignment, array $answers): array
    {
        return (new Submission())->getSubmissionArray($assignment,
            $this->question,
            $this->makeSubmission($answers),
            false,
            $this->assignment_question);
    }

    #[Test]
    public function feedback_from_ans_message_is_included_in_the_submission_array()
    {
        $this->actAs($this->student);
        $submission_array = $this->getSubmissionArray($this->makeAssignment('real time'), [
            'AnSwEr0001' => [
                'original_student_ans' => '42',
                'score' => 0,
                'ans_message' => "Your answer isn't scientific notation (it looks like a real number)"
            ]
        ]);
        $this->assertEquals("Your answer isn't scientific notation (it looks like a real number)", $submission_array[0]['feedback']);
        $this->assertFalse($submission_array[0]['feedback_has_html']);
    }

    #[Test]
    public function html_feedback_is_flagged_as_html()
    {
        $this->actAs($this->student);
        $submission_array = $this->getSubmissionArray($this->makeAssignment('real time'), [
            'AnSwEr0001' => [
                'original_student_ans' => '42',
                'score' => 0,
                'ans_message' => '<b>Check your units.</b>'
            ]
        ]);
        $this->assertTrue($submission_array[0]['feedback_has_html']);
    }

    #[Test]
    public function no_feedback_key_when_ans_message_is_empty_or_missing()
    {
        $this->actAs($this->student);
        $submission_array = $this->getSubmissionArray($this->makeAssignment('real time'), [
            'AnSwEr0001' => ['original_student_ans' => '42', 'score' => 1, 'ans_message' => '  '],
            'AnSwEr0002' => ['original_student_ans' => '7', 'score' => 1]
        ]);
        $this->assertArrayNotHasKey('feedback', $submission_array[0]);
        $this->assertArrayNotHasKey('feedback', $submission_array[1]);
    }

    #[Test]
    public function student_sees_feedback_on_delayed_assignment_once_solutions_are_released()
    {
        $this->actAs($this->student);
        $submission_array = $this->getSubmissionArray($this->makeAssignment('delayed', 1), [
            'AnSwEr0001' => ['original_student_ans' => '42', 'score' => 0, 'ans_message' => 'Try again.']
        ]);
        $this->assertEquals('Try again.', $submission_array[0]['feedback']);
    }

    #[Test]
    public function student_does_not_get_the_submission_array_on_delayed_assignment_before_solutions_are_released()
    {
        $this->actAs($this->student);
        $submission_array = $this->getSubmissionArray($this->makeAssignment('delayed', 0), [
            'AnSwEr0001' => ['original_student_ans' => '42', 'score' => 0, 'ans_message' => 'Try again.']
        ]);
        $this->assertEquals([], $submission_array);
    }

    #[Test]
    public function instructor_sees_feedback_on_delayed_assignment_before_solutions_are_released()
    {
        $this->actAs($this->instructor);
        $submission_array = $this->getSubmissionArray($this->makeAssignment('delayed', 0), [
            'AnSwEr0001' => ['original_student_ans' => '42', 'score' => 0, 'ans_message' => 'Try again.']
        ]);
        $this->assertEquals('Try again.', $submission_array[0]['feedback']);
    }

    /**
     * Builds the signed answerJWT that the WeBWorK renderer posts to /api/jwt/process-answer-jwt.
     */
    private function makeAnswerJWT(int $assignment_id, int $question_id, array $answers): string
    {
        $jwe = new JWE();
        $secret = $jwe->getSecret('webwork');
        // JWE::encrypt() expects a signed JWT (same approach as WebworkTest::createWebworkProblemJWT)
        \JWTAuth::getJWTProvider()->setSecret($secret);
        $token = \JWTAuth::claims([
            'adapt' => [
                'assignment_id' => $assignment_id,
                'question_id' => $question_id,
                'technology' => 'webwork'
            ]
        ])->fromUser($this->student);
        $problemJWT = $jwe->encrypt($token, 'webwork');
        \JWTAuth::getJWTProvider()->setSecret(config('myconfig.jwt_secret'));

        $base64Url = function ($text) {
            return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
        };
        $header = $base64Url(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = $base64Url(json_encode([
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'problemJWT' => $problemJWT,
            'score' => ['result' => 0, 'answers' => $answers]
        ]));
        $signature = $base64Url(hash_hmac('sha256', "$header.$payload", $secret, true));
        return "$header.$payload.$signature";
    }

    #[Test]
    public function format_error_submission_is_accepted_instead_of_rejected()
    {
        // unconfirmed_submissions has foreign keys to assignments and questions, so these must be real rows
        $course = factory(Course::class)->create(['user_id' => $this->instructor->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);
        $question = factory(Question::class)->create(['page_id' => 23482671]);

        $answerJWT = $this->makeAnswerJWT($assignment->id, $question->id, [
            'AnSwEr0001' => [
                'original_student_ans' => '42',
                'score' => 0,
                'ans_message' => "Your answer isn't scientific notation (it looks like a real number)",
                'error_message' => "Your answer isn't scientific notation (it looks like a real number)"
            ]
        ]);

        $this->call('POST', '/api/jwt/process-answer-jwt', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $answerJWT)
            ->assertJson(['type' => 'unconfirmed']);

        $this->assertDatabaseHas('unconfirmed_submissions', [
            'user_id' => $this->student->id,
            'assignment_id' => $assignment->id,
            'question_id' => $question->id
        ]);
        $this->assertDatabaseMissing('webwork_submission_errors', ['user_id' => $this->student->id]);
    }
}
