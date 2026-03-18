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

        // Income Statement:
        //   Revenues (section header)
        //     row 1: Sales Revenue        | 475000  (answer)
        //   Expenses (section header)
        //     row 3: Salaries Expense     | 80000   (answer)
        //     row 4: Rent Expense         | 36000   (answer)
        //     row 5: Total Expenses       | 116000  (answer)
        //     row 6: Net Income           | 359000  (answer)
        //
        // By default: orderMode = 'exact', no rows are flexible.
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
                        "flexible" => false,
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
                        "flexible" => false,
                        "headerText" => "",
                        "cells" => [
                            ["identifier" => "cell-3-0", "mode" => "display", "value" => "Salaries Expense", "underline" => "none"],
                            ["identifier" => "cell-3-1", "mode" => "answer", "value" => "80000", "underline" => "none"]
                        ]
                    ],
                    [
                        "identifier" => "row-4",
                        "isHeader" => false,
                        "flexible" => false,
                        "headerText" => "",
                        "cells" => [
                            ["identifier" => "cell-4-0", "mode" => "display", "value" => "Rent Expense", "underline" => "none"],
                            ["identifier" => "cell-4-1", "mode" => "answer", "value" => "36000", "underline" => "single"]
                        ]
                    ],
                    [
                        "identifier" => "row-5",
                        "isHeader" => false,
                        "flexible" => false,
                        "headerText" => "",
                        "cells" => [
                            ["identifier" => "cell-5-0", "mode" => "display", "value" => "Total Expenses", "underline" => "none"],
                            ["identifier" => "cell-5-1", "mode" => "answer", "value" => "116000", "underline" => "single"]
                        ]
                    ],
                    [
                        "identifier" => "row-6",
                        "isHeader" => false,
                        "flexible" => false,
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

    /**
     * Return a qtiArray with orderMode set and rows optionally modified.
     * Pass $flexibleRowIndices to mark specific row indices as flexible: true.
     */
    private function buildQtiArray(string $orderMode = 'exact', array $flexibleRowIndices = []): array
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['orderMode'] = $orderMode;
        foreach ($flexibleRowIndices as $ri) {
            if (isset($qtiJson['rows'][$ri])) {
                $qtiJson['rows'][$ri]['flexible'] = true;
            }
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
                "flexible" => false,
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
                "flexible" => false,
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

        // Only the first answer correct; 1 out of 5
        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => ''],
            '4' => ['1' => ''],
            '5' => ['1' => ''],
            '6' => ['1' => '']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

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

    /** @test */
    public function exact_mode_wrong_row_order_is_marked_incorrect()
    {
        // In exact mode, putting Rent Expense value (36000) where Salaries Expense (80000)
        // is expected scores that cell wrong even though 36000 is correct elsewhere.
        $qtiArray = $this->buildQtiArray('exact');
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '36000'],  // Wrong: expected 80000
            '4' => ['1' => '80000'],  // Wrong: expected 36000
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertFalse($result['results']['3']['1']['isCorrect']);
        $this->assertFalse($result['results']['4']['1']['isCorrect']);
        // 3 out of 5 correct
        $this->assertEquals(round(3 / 5, 4), $result['proportionCorrect']);
    }

    // =========================================================================
    // SCORING - WITHIN_SECTIONS MODE, NO FLEXIBLE ROWS (all positional)
    // =========================================================================

    /** @test */
    public function within_sections_mode_with_no_flexible_rows_behaves_like_exact()
    {
        // When orderMode is within_sections but no rows are marked flexible,
        // every row is positional — identical behaviour to exact mode.
        $qtiArray = $this->buildQtiArray('within_sections'); // no flexibleRowIndices

        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '36000'],  // Wrong positionally: expected 80000
            '4' => ['1' => '80000'],  // Wrong positionally: expected 36000
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertFalse($result['results']['3']['1']['isCorrect']);
        $this->assertFalse($result['results']['4']['1']['isCorrect']);
        $this->assertEquals(round(3 / 5, 4), $result['proportionCorrect']);
    }

    // =========================================================================
    // SCORING - WITHIN_SECTIONS MODE, WITH FLEXIBLE ROWS
    // =========================================================================

    /** @test */
    public function flexible_rows_score_100_when_submitted_in_correct_order()
    {
        // rows 3 and 4 (Salaries, Rent) marked flexible; row 5 (Total) and row 6 (Net Income) positional
        // Student submits everything in the expected order — should still be 100%.
        $qtiArray = $this->buildQtiArray('within_sections', [3, 4]);
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
    public function flexible_rows_score_100_when_submitted_in_swapped_order()
    {
        // rows 3 and 4 marked flexible; student swaps their values — should still be 100%.
        $qtiArray = $this->buildQtiArray('within_sections', [3, 4]);
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '36000'],  // Rent value in Salaries row — flexible match
            '4' => ['1' => '80000'],  // Salaries value in Rent row — flexible match
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertEquals(1.0, $result['proportionCorrect']);
    }

    /** @test */
    public function positional_rows_within_within_sections_mode_still_require_correct_position()
    {
        // row 5 (Total Expenses) is NOT marked flexible — swapping it should score wrong.
        $qtiArray = $this->buildQtiArray('within_sections', [3, 4]); // row 5 stays positional

        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '80000'],
            '4' => ['1' => '36000'],
            '5' => ['1' => '999999'], // Wrong value for positional Total Expenses
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertFalse($result['results']['5']['1']['isCorrect']);
        // 4 out of 5 correct
        $this->assertEquals(round(4 / 5, 4), $result['proportionCorrect']);
    }

    /** @test */
    public function flexible_rows_do_not_match_across_sections()
    {
        // rows 1 (Revenue section) and 3 (Expenses section) both marked flexible,
        // but they are in different sections and must not match each other.
        $qtiArray = $this->buildQtiArray('within_sections', [1, 3]);
        $submission = new Submission();

        // Student puts Revenue value (475000) in Expenses section row 3
        $studentSubmission = [
            '1' => ['1' => ''],       // Revenue section: blank
            '3' => ['1' => '475000'], // Expenses section: Revenue value — must NOT cross-match
            '4' => ['1' => '36000'],
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        // row 1 is blank (wrong), row 3 has 475000 which != 80000 (wrong)
        $this->assertFalse($result['results']['1']['1']['isCorrect']);
        $this->assertFalse($result['results']['3']['1']['isCorrect']);
        $this->assertLessThan(1.0, $result['proportionCorrect']);
    }

    /** @test */
    public function flexible_rows_with_one_wrong_value_score_proportionally()
    {
        // rows 3 and 4 flexible. Student swaps correctly but one value is wrong.
        $qtiArray = $this->buildQtiArray('within_sections', [3, 4]);
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '36000'],  // Rent value — greedy matches expected Salaries row (row 3) first, scores wrong
            '4' => ['1' => '99999'],  // Wrong value — matched to expected Rent row (row 4), scores wrong
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        // Greedy processes expected row 3 (80000) first:
        //   student row 3 (36000) and row 4 (99999) both have 0 matches → picks row 3 (36000 ≠ 80000 ✗)
        // Then expected row 4 (36000): only row 4 (99999) left → 99999 ≠ 36000 ✗
        // Result: 475000 ✓, 36000 ✗, 99999 ✗, 116000 ✓, 359000 ✓ → 3/5
        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertEquals(round(3 / 5, 4), $result['proportionCorrect']);
    }

    /** @test */
    public function flexible_rows_blank_submission_counts_as_wrong()
    {
        $qtiArray = $this->buildQtiArray('within_sections', [3, 4]);
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => ''],  // Blank — wrong
            '4' => ['1' => ''],  // Blank — wrong
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        // 3 out of 5 correct
        $this->assertEquals(round(3 / 5, 4), $result['proportionCorrect']);
    }

    /** @test */
    public function flexible_rows_partial_blank_still_awards_matching_cells()
    {
        // rows 3 and 4 flexible. One row blank, one row correct.
        // The non-blank row should still match its best expected row.
        $qtiArray = $this->buildQtiArray('within_sections', [3, 4]);
        $submission = new Submission();

        $studentSubmission = [
            '1' => ['1' => '475000'],
            '3' => ['1' => '80000'],  // Matches expected Salaries row exactly
            '4' => ['1' => ''],       // Blank — wrong for remaining Rent row
            '5' => ['1' => '116000'],
            '6' => ['1' => '359000']
        ];

        // 4 out of 5 correct
        $result = $submission->computeScoreForAccountingReport($qtiArray, $studentSubmission);

        $this->assertEquals(round(4 / 5, 4), $result['proportionCorrect']);
    }
}
