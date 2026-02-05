<?php


namespace Tests\Feature\Accounting;

use App\Question;
use App\SavedQuestionsFolder;
use App\Submission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);

        $this->qti_question_info = [
            "question_type" => "assessment",
            "folder_id" => $this->saved_questions_folder->id,
            "public" => "0",
            "title" => "Accounting Report Test Question",
            "author" => "Instructor Kean",
            "tags" => [],
            "technology" => "qti",
            "technology_id" => null,
            "non_technology_text" => null,
            "text_question" => null,
            "a11y_auto_graded_question_id" => null,
            "answer_html" => null,
            "solution_html" => null,
            "notes" => null,
            "hint" => null,
            "license" => "publicdomain",
            "license_version" => null,
            "open_ended_submission_type" => "0",
            "source_url" => "https://adapt.libretexts.org",
            "qti_prompt" => "<p>Prepare an income statement.</p>",
            "qti_json" => json_encode([
                "questionType" => "accounting_report",
                "prompt" => "<p>Prepare an income statement.</p>",
                "reportHeading" => ["Wildhorse Co.", "Income Statement", "For the Year Ended December 31, 2025"],
                "orderMode" => "exact",
                "columns" => [
                    [
                        "identifier" => "col-1",
                        "header" => "",
                        "type" => "text",
                        "textInputMode" => "text",
                        "dropdownOptions" => []
                    ],
                    [
                        "identifier" => "col-2",
                        "header" => "",
                        "type" => "numeric",
                        "textInputMode" => "text",
                        "dropdownOptions" => []
                    ]
                ],
                "rows" => [
                    [
                        "identifier" => "row-0",
                        "isHeader" => true,
                        "headerText" => "Revenues"
                    ],
                    [
                        "identifier" => "row-1",
                        "isHeader" => false,
                        "headerText" => "",
                        "cells" => [
                            ["identifier" => "cell-1-0", "mode" => "display", "value" => "Sales Revenue", "underline" => "none"],
                            ["identifier" => "cell-1-1", "mode" => "answer", "value" => "475000", "underline" => "single"]
                        ]
                    ],
                    [
                        "identifier" => "row-2",
                        "isHeader" => true,
                        "headerText" => "Expenses"
                    ],
                    [
                        "identifier" => "row-3",
                        "isHeader" => false,
                        "headerText" => "",
                        "cells" => [
                            ["identifier" => "cell-3-0", "mode" => "display", "value" => "Salaries Expense", "underline" => "none"],
                            ["identifier" => "cell-3-1", "mode" => "answer", "value" => "80000", "underline" => "none"]
                        ]
                    ],
                    [
                        "identifier" => "row-4",
                        "isHeader" => false,
                        "headerText" => "",
                        "cells" => [
                            ["identifier" => "cell-4-0", "mode" => "display", "value" => "Rent Expense", "underline" => "none"],
                            ["identifier" => "cell-4-1", "mode" => "answer", "value" => "36000", "underline" => "single"]
                        ]
                    ],
                    [
                        "identifier" => "row-5",
                        "isHeader" => false,
                        "headerText" => "",
                        "cells" => [
                            ["identifier" => "cell-5-0", "mode" => "display", "value" => "Total Expenses", "underline" => "none"],
                            ["identifier" => "cell-5-1", "mode" => "answer", "value" => "116000", "underline" => "single"]
                        ]
                    ],
                    [
                        "identifier" => "row-6",
                        "isHeader" => false,
                        "headerText" => "",
                        "cells" => [
                            ["identifier" => "cell-6-0", "mode" => "display", "value" => "Net Income", "underline" => "none"],
                            ["identifier" => "cell-6-1", "mode" => "answer", "value" => "359000", "underline" => "double"]
                        ]
                    ]
                ]
            ])
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    private function getQtiJson(): array
    {
        return json_decode($this->qti_question_info['qti_json'], true);
    }

    private function setQtiJson(array $qtiJson): void
    {
        $this->qti_question_info['qti_json'] = json_encode($qtiJson);
    }

    private function submitAndGetErrors(): array
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions", $this->qti_question_info);
        $response->assertStatus(422);
        $errors = $response->json('errors.qti_json');
        $this->assertNotNull($errors);
        return json_decode($errors[0], true);
    }

    private function buildQtiArray(string $orderMode = 'exact', array $rowOverrides = []): array
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['orderMode'] = $orderMode;
        if (!empty($rowOverrides)) {
            $qtiJson['rows'] = $rowOverrides;
        }
        return $qtiJson;
    }

    // =========================================================================
    // QUESTION CREATION - VALIDATION
    // =========================================================================

    /** @test */
    public function can_create_accounting_report_question()
    {
        $this->actingAs($this->user)->postJson("/api/questions", $this->qti_question_info)
            ->assertJson(['type' => 'success']);

        $this->assertDatabaseHas('questions', [
            'title' => 'Accounting Report Test Question'
        ]);
    }

    /** @test */
    public function prompt_is_required()
    {
        $this->qti_question_info['qti_prompt'] = '';

        $response = $this->actingAs($this->user)->postJson("/api/questions", $this->qti_question_info);
        $response->assertStatus(422);
        $this->assertNotNull($response->json('errors.qti_prompt'));
    }

    /** @test */
    public function at_least_one_column_must_exist()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['columns'] = [];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('at least one column', strtolower($errorData['general']));
    }

    /** @test */
    public function at_least_one_row_must_exist()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['rows'] = [];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('at least one row', strtolower($errorData['general']));
    }

    /** @test */
    public function at_least_one_data_row_must_exist()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['rows'] = [
            ["identifier" => "row-0", "isHeader" => true, "headerText" => "Revenues"]
        ];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('at least one data row', strtolower($errorData['general']));
    }

    /** @test */
    public function at_least_one_answer_cell_must_exist()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['rows'] = [
            ["identifier" => "row-0", "isHeader" => true, "headerText" => "Revenues"],
            [
                "identifier" => "row-1",
                "isHeader" => false,
                "headerText" => "",
                "cells" => [
                    ["identifier" => "c1", "mode" => "display", "value" => "Sales", "underline" => "none"],
                    ["identifier" => "c2", "mode" => "display", "value" => "100", "underline" => "none"]
                ]
            ]
        ];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('at least one answer cell', strtolower($errorData['general']));
    }

    /** @test */
    public function at_least_one_display_cell_or_section_header_must_exist()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['rows'] = [
            [
                "identifier" => "row-1",
                "isHeader" => false,
                "headerText" => "",
                "cells" => [
                    ["identifier" => "c1", "mode" => "answer", "value" => "100", "underline" => "none"],
                    ["identifier" => "c2", "mode" => "answer", "value" => "200", "underline" => "none"]
                ]
            ]
        ];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('at least one display cell or section header', strtolower($errorData['general']));
    }

    /** @test */
    public function section_header_text_cannot_be_empty()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['rows'][0]['headerText'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Section header text is required.', $errorData['specific']['0']['header']);
    }

    /** @test */
    public function display_cell_values_cannot_be_empty()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['rows'][1]['cells'][0]['value'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Display cell must have a value.', $errorData['specific']['1']['0']['value']);
    }

    /** @test */
    public function answer_cells_must_have_expected_values()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['rows'][1]['cells'][1]['value'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('An expected answer is required.', $errorData['specific']['1']['1']['value']);
    }

    /** @test */
    public function numeric_answer_values_must_be_numeric()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['rows'][1]['cells'][1]['value'] = 'abc';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Value must be numeric.', $errorData['specific']['1']['1']['value']);
    }

    /** @test */
    public function numeric_display_values_must_be_numeric()
    {
        $qtiJson = $this->getQtiJson();
        // Change a display cell to numeric column and set non-numeric value
        $qtiJson['rows'][1]['cells'][0]['value'] = 'abc';
        $qtiJson['columns'][0]['type'] = 'numeric';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Value must be numeric.', $errorData['specific']['1']['0']['value']);
    }

    /** @test */
    public function cell_count_must_match_column_count()
    {
        $qtiJson = $this->getQtiJson();
        // Remove one cell from a row
        $qtiJson['rows'][1]['cells'] = [
            ["identifier" => "c1", "mode" => "display", "value" => "Sales", "underline" => "none"]
        ];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('expected 2 cells but found 1', strtolower($errorData['general']));
    }

    /** @test */
    public function dropdown_columns_must_have_at_least_two_options()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['columns'][0]['textInputMode'] = 'dropdown';
        $qtiJson['columns'][0]['dropdownOptions'] = ['Only One'];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('fewer than 2 options', strtolower($errorData['general']));
    }

    // =========================================================================
    // SCORING - EXACT MODE
    // =========================================================================

    /** @test */
    public function exact_mode_scores_all_correct_as_100_percent()
    {
        $qtiArray = $this->buildQtiArray('exact');
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '80000'],
            '4' => ['1' => '36000'],
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertEquals(1.0, $result['proportionCorrect']);
        foreach ($result['results'] as $ri => $row) {
            foreach ($row as $ci => $cell) {
                $this->assertTrue($cell['isCorrect'], "Cell [$ri][$ci] should be correct");
            }
        }
    }

    /** @test */
    public function exact_mode_scores_partially_correct_proportionally()
    {
        $qtiArray = $this->buildQtiArray('exact');
        $submission = new Submission();

        // Only first answer correct
        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => ''],
            '4' => ['1' => ''],
            '5' => ['1' => ''],
            '6' => ['1' => '']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertGreaterThan(0, $result['proportionCorrect']);
        $this->assertLessThan(1, $result['proportionCorrect']);
        // 1 out of 5 correct
        $this->assertEquals(round(1 / 5, 4), $result['proportionCorrect']);
    }

    /** @test */
    public function exact_mode_scores_all_incorrect_as_zero()
    {
        $qtiArray = $this->buildQtiArray('exact');
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => ''],
            '3' => ['1' => ''],
            '4' => ['1' => ''],
            '5' => ['1' => ''],
            '6' => ['1' => '']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertEquals(0, $result['proportionCorrect']);
    }

    /** @test */
    public function exact_mode_accepts_values_with_commas()
    {
        $qtiArray = $this->buildQtiArray('exact');
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475,000'],
            '3' => ['1' => '80,000'],
            '4' => ['1' => '36,000'],
            '5' => ['1' => '116,000'],
            '6' => ['1' => '359,000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertEquals(1.0, $result['proportionCorrect']);
    }

    /** @test */
    public function exact_mode_accepts_values_with_dollar_signs()
    {
        $qtiArray = $this->buildQtiArray('exact');
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '$475,000'],
            '3' => ['1' => '$80,000'],
            '4' => ['1' => '$36,000'],
            '5' => ['1' => '$116,000'],
            '6' => ['1' => '$359,000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertEquals(1.0, $result['proportionCorrect']);
    }

    /** @test */
    public function exact_mode_numeric_comparison_uses_tolerance()
    {
        $qtiArray = $this->getQtiJson();
        $qtiArray['orderMode'] = 'exact';
        // Change expected value to a decimal
        $qtiArray['rows'][1]['cells'][1]['value'] = '475000.50';

        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000.50'],
            '3' => ['1' => '80000'],
            '4' => ['1' => '36000'],
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertTrue($result['results']['1']['1']['isCorrect']);
    }

    /** @test */
    public function exact_mode_text_comparison_is_case_insensitive()
    {
        $qtiArray = $this->getQtiJson();
        $qtiArray['orderMode'] = 'exact';
        // Make a text column answer cell
        $qtiArray['rows'][1]['cells'][0]['mode'] = 'answer';
        $qtiArray['rows'][1]['cells'][0]['value'] = 'Sales Revenue';

        $submission = new Submission();

        $studentSubmission = [
            '1' => ['0' => 'sales revenue', '1' => '475000'],
            '3' => ['1' => '80000'],
            '4' => ['1' => '36000'],
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertTrue($result['results']['1']['0']['isCorrect']);
    }

    // =========================================================================
    // SCORING - FLEXIBLE MODE
    // =========================================================================

    /** @test */
    public function flexible_mode_scores_100_when_rows_in_correct_order()
    {
        $qtiArray = $this->buildQtiArray('flexible');
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '80000'],
            '4' => ['1' => '36000'],
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertEquals(1.0, $result['proportionCorrect']);
    }

    /** @test */
    public function flexible_mode_scores_100_when_rows_within_section_are_reordered()
    {
        // Swap Salaries Expense (row 3) and Rent Expense (row 4) values
        // Student puts Rent Expense amount in row 3 and Salaries Expense amount in row 4
        $qtiArray = $this->buildQtiArray('flexible');
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '36000'],  // Rent Expense value in Salaries row
            '4' => ['1' => '80000'],  // Salaries Expense value in Rent row
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertEquals(1.0, $result['proportionCorrect']);
    }

    /** @test */
    public function flexible_mode_does_not_match_rows_across_sections()
    {
        $qtiArray = $this->buildQtiArray('flexible');
        $submission = new Submission();

        // Put Revenues section answer in Expenses section
        $studentSubmission = [
            '1' => ['1' => ''],       // Revenue section: empty
            '3' => ['1' => '475000'], // Expense section: Revenue value (should not match)
            '4' => ['1' => '36000'],
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        // Revenue answer is wrong (empty), and 475000 in Expenses section should not match across
        $this->assertLessThan(1.0, $result['proportionCorrect']);
    }

    /** @test */
    public function flexible_mode_partially_correct_reordered_rows_score_proportionally()
    {
        $qtiArray = $this->buildQtiArray('flexible');
        $submission = new Submission();

        // Swap rows within Expenses section, but one value is wrong
        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '36000'],  // Rent value in Salaries row (flexible match)
            '4' => ['1' => '99999'],  // Wrong value
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        // 3 out of 5 correct: 475000 ✓, 36000 matched to wrong expected (greedy),
        // 99999 ✗, 116000 ✓, 359000 ✓
        $this->assertGreaterThan(0, $result['proportionCorrect']);
        $this->assertLessThan(1, $result['proportionCorrect']);
        $this->assertEquals(round(3 / 5, 4), $result['proportionCorrect']);
    }
}
