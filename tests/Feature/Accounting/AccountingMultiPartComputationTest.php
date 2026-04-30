<?php

namespace Tests\Feature\Accounting;

use App\SavedQuestionsFolder;
use App\Submission;
use App\User;
use Tests\TestCase;

class AccountingMultiPartComputationTest extends TestCase
{
    // =========================================================================
    // SETUP
    // =========================================================================

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create([
            'user_id' => $this->user->id,
            'type'    => 'my_questions'
        ]);

        // Two tables:
        //   Table 1 (type: 'table')   — dollar answer, percentage answer
        //   Table 2 (type: 'lineItems') — ratio, custom, general, dropdown
        $this->qti_question_info = [
            'question_type'              => 'assessment',
            'folder_id'                  => $this->saved_questions_folder->id,
            'public'                     => '0',
            'title'                      => 'Multi-part Computation Test',
            'author'                     => 'Instructor Kean',
            'tags'                       => [],
            'technology'                 => 'qti',
            'technology_id'              => null,
            'non_technology_text'        => null,
            'text_question'              => null,
            'a11y_auto_graded_question_id' => null,
            'answer_html'                => null,
            'solution_html'              => null,
            'notes'                      => null,
            'hint'                       => null,
            'license'                    => 'publicdomain',
            'license_version'            => null,
            'open_ended_submission_type' => '0',
            'source_url'                 => 'https://adapt.libretexts.org',
            'qti_prompt'                 => '<p>Complete the following computations.</p>',
            'qti_json'                   => json_encode([
                'questionType'       => 'accounting_multi_part_computation',
                'prompt'             => '<p>Complete the following computations.</p>',
                'randomizeDropdowns' => false,
                'tables'             => [
                    // Table 0 — grid, dollar + percentage
                    [
                        'identifier' => 'table-0',
                        'label'      => 'Part A',
                        'tableType'  => 'table',
                        'columns'    => [
                            ['identifier' => 'col-0-0', 'header' => 'Item'],
                            ['identifier' => 'col-0-1', 'header' => 'Amount'],
                        ],
                        'rows' => [
                            [
                                'identifier'      => 'row-0-0',
                                'rowType'         => 'data',
                                'cells'           => [
                                    ['identifier' => 'cell-0-0-0', 'mode' => 'display', 'value' => 'Net Income'],
                                    [
                                        'identifier'    => 'cell-0-0-1',
                                        'mode'          => 'answer',
                                        'answerType'    => 'dollar',
                                        'value'         => '50000',
                                        'dollarRounding'=> 'dollar',
                                        'decimalPlaces' => 2,
                                        'customUnit'    => '',
                                        'dropdownOptions' => [],
                                        'correctIndex'  => null,
                                    ],
                                ],
                            ],
                            [
                                'identifier' => 'row-0-1',
                                'rowType'    => 'data',
                                'cells'      => [
                                    ['identifier' => 'cell-0-1-0', 'mode' => 'display', 'value' => 'Profit Margin'],
                                    [
                                        'identifier'    => 'cell-0-1-1',
                                        'mode'          => 'answer',
                                        'answerType'    => 'percentage',
                                        'value'         => '25',
                                        'dollarRounding'=> 'dollar',
                                        'decimalPlaces' => 2,
                                        'customUnit'    => '',
                                        'dropdownOptions' => [],
                                        'correctIndex'  => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Table 1 — lineItems, ratio + custom + general + dropdown
                    [
                        'identifier' => 'table-1',
                        'label'      => 'Part B',
                        'tableType'  => 'lineItems',
                        'columns'    => [
                            ['identifier' => 'col-1-0', 'header' => ''],
                            ['identifier' => 'col-1-1', 'header' => ''],
                        ],
                        'rows' => [
                            [
                                'identifier' => 'row-1-0',
                                'rowType'    => 'data',
                                'cells'      => [
                                    ['identifier' => 'cell-1-0-0', 'mode' => 'display', 'value' => 'Current Ratio'],
                                    [
                                        'identifier'    => 'cell-1-0-1',
                                        'mode'          => 'answer',
                                        'answerType'    => 'ratio',
                                        'value'         => '2.50',
                                        'dollarRounding'=> 'dollar',
                                        'decimalPlaces' => 2,
                                        'customUnit'    => '',
                                        'dropdownOptions' => [],
                                        'correctIndex'  => null,
                                    ],
                                ],
                            ],
                            [
                                'identifier' => 'row-1-1',
                                'rowType'    => 'data',
                                'cells'      => [
                                    ['identifier' => 'cell-1-1-0', 'mode' => 'display', 'value' => 'Units Sold'],
                                    [
                                        'identifier'    => 'cell-1-1-1',
                                        'mode'          => 'answer',
                                        'answerType'    => 'custom',
                                        'value'         => '1500',
                                        'dollarRounding'=> 'dollar',
                                        'decimalPlaces' => 0,
                                        'customUnit'    => 'units',
                                        'dropdownOptions' => [],
                                        'correctIndex'  => null,
                                    ],
                                ],
                            ],
                            [
                                'identifier' => 'row-1-2',
                                'rowType'    => 'data',
                                'cells'      => [
                                    ['identifier' => 'cell-1-2-0', 'mode' => 'display', 'value' => 'Score'],
                                    [
                                        'identifier'    => 'cell-1-2-1',
                                        'mode'          => 'answer',
                                        'answerType'    => 'general',
                                        'value'         => '42',
                                        'dollarRounding'=> 'dollar',
                                        'decimalPlaces' => 0,
                                        'customUnit'    => '',
                                        'dropdownOptions' => [],
                                        'correctIndex'  => null,
                                    ],
                                ],
                            ],
                            [
                                'identifier' => 'row-1-3',
                                'rowType'    => 'data',
                                'cells'      => [
                                    ['identifier' => 'cell-1-3-0', 'mode' => 'display', 'value' => 'Account Type'],
                                    [
                                        'identifier'      => 'cell-1-3-1',
                                        'mode'            => 'answer',
                                        'answerType'      => 'dropdown',
                                        'value'           => 'Asset',
                                        'dollarRounding'  => 'dollar',
                                        'decimalPlaces'   => 2,
                                        'customUnit'      => '',
                                        'dropdownOptions' => ['Asset', 'Liability', 'Equity'],
                                        'correctIndex'    => 0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ];
    }

    // =========================================================================
    // HELPERS
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
        $response = $this->actingAs($this->user)->postJson('/api/questions', $this->qti_question_info);
        $response->assertStatus(422);
        $errors = $response->json('errors.qti_json');
        $this->assertNotNull($errors);
        return json_decode($errors[0], true);
    }

    /** Return a Submission and the base qtiArray for scoring tests */
    private function makeSubmission(): array
    {
        return [new Submission(), $this->getQtiJson()];
    }

    /** Build a minimal correct student response for both tables */
    private function correctStudentResponse(): array
    {
        return [
            '0' => [
                '0' => ['1' => '50000'],
                '1' => ['1' => '25'],
            ],
            '1' => [
                '0' => ['1' => '2.50'],
                '1' => ['1' => '1500'],
                '2' => ['1' => '42'],
                '3' => ['1' => 'Asset'],
            ],
        ];
    }

    // =========================================================================
    // VALIDATION — QUESTION CREATION
    // =========================================================================

    /** @test */
    public function can_create_accounting_multi_part_computation_question()
    {
        $this->actingAs($this->user)
            ->postJson('/api/questions', $this->qti_question_info)
            ->assertJson(['type' => 'success']);

        $this->assertDatabaseHas('questions', [
            'title' => 'Multi-part Computation Test',
        ]);
    }

    /** @test */
    public function tables_must_exist()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'] = [];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('At least one table is required.', $errorData['general']);
    }

    /** @test */
    public function each_table_must_have_at_least_one_column()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][0]['columns'] = [];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('Table 1 must have at least one column', $errorData['general']);
    }

    /** @test */
    public function each_table_must_have_at_least_one_row()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][0]['rows'] = [];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('Table 1 must have at least one row', $errorData['general']);
    }

    /** @test */
    public function each_table_must_have_at_least_one_answer_cell()
    {
        $qtiJson = $this->getQtiJson();
        // Make all cells in table 0 display
        foreach ($qtiJson['tables'][0]['rows'] as &$row) {
            foreach ($row['cells'] as &$cell) {
                $cell['mode'] = 'display';
                $cell['value'] = 'Some text';
            }
        }
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('Table 1 must have at least one answer cell', $errorData['general']);
    }

    /** @test */
    public function question_must_have_at_least_one_answer_cell_across_all_tables()
    {
        $qtiJson = $this->getQtiJson();
        // Make every answer cell in every table a display cell
        foreach ($qtiJson['tables'] as &$table) {
            foreach ($table['rows'] as &$row) {
                if ($row['rowType'] === 'data') {
                    foreach ($row['cells'] as &$cell) {
                        $cell['mode'] = 'display';
                        $cell['value'] = 'text';
                    }
                }
            }
        }
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertStringContainsString('at least one answer cell', strtolower($errorData['general']));
    }

    /** @test */
    public function instruction_row_text_is_required()
    {
        $qtiJson = $this->getQtiJson();
        array_unshift($qtiJson['tables'][0]['rows'], [
            'identifier'      => 'row-inst',
            'rowType'         => 'instruction',
            'instructionText' => '',
        ]);
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Instruction text is required.', $errorData['specific'][0][0]['instructionText']);
    }

    /** @test */
    public function display_cell_value_is_required()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][0]['rows'][0]['cells'][0]['value'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Display cell must have a value.', $errorData['specific'][0][0][0]['value']);
    }

    // =========================================================================
    // VALIDATION — DOLLAR
    // =========================================================================

    /** @test */
    public function dollar_answer_value_is_required()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][0]['rows'][0]['cells'][1]['value'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('A correct answer is required.', $errorData['specific'][0][0][1]['value']);
    }

    /** @test */
    public function dollar_answer_must_be_numeric()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][0]['rows'][0]['cells'][1]['value'] = 'abc';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Dollar amount must be numeric.', $errorData['specific'][0][0][1]['value']);
    }

    /** @test */
    public function dollar_rounding_must_be_valid()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][0]['rows'][0]['cells'][1]['dollarRounding'] = 'invalid';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Rounding must be set to nearest dollar or cent.', $errorData['specific'][0][0][1]['dollarRounding']);
    }

    // =========================================================================
    // VALIDATION — PERCENTAGE
    // =========================================================================

    /** @test */
    public function percentage_answer_is_required()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][0]['rows'][1]['cells'][1]['value'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('A correct answer is required.', $errorData['specific'][0][1][1]['value']);
    }

    /** @test */
    public function percentage_answer_must_be_numeric()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][0]['rows'][1]['cells'][1]['value'] = 'abc';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Percentage value must be numeric.', $errorData['specific'][0][1][1]['value']);
    }

    /** @test */
    public function percentage_decimal_places_must_be_valid()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][0]['rows'][1]['cells'][1]['decimalPlaces'] = 99;
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Decimal places must be between 0 and 6.', $errorData['specific'][0][1][1]['decimalPlaces']);
    }

    // =========================================================================
    // VALIDATION — RATIO
    // =========================================================================

    /** @test */
    public function ratio_answer_is_required()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][0]['cells'][1]['value'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('A correct answer is required.', $errorData['specific'][1][0][1]['value']);
    }

    /** @test */
    public function ratio_answer_must_be_numeric()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][0]['cells'][1]['value'] = 'abc';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Ratio value must be numeric.', $errorData['specific'][1][0][1]['value']);
    }

    // =========================================================================
    // VALIDATION — CUSTOM
    // =========================================================================

    /** @test */
    public function custom_unit_label_is_required()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][1]['cells'][1]['customUnit'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('A unit label is required for custom type.', $errorData['specific'][1][1][1]['customUnit']);
    }

    /** @test */
    public function custom_answer_must_be_numeric()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][1]['cells'][1]['value'] = 'abc';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Value must be numeric.', $errorData['specific'][1][1][1]['value']);
    }

    // =========================================================================
    // VALIDATION — GENERAL
    // =========================================================================

    /** @test */
    public function general_answer_is_required()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][2]['cells'][1]['value'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('A correct answer is required.', $errorData['specific'][1][2][1]['value']);
    }

    /** @test */
    public function general_answer_must_be_numeric()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][2]['cells'][1]['value'] = 'abc';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Value must be numeric.', $errorData['specific'][1][2][1]['value']);
    }

    // =========================================================================
    // VALIDATION — DROPDOWN
    // =========================================================================

    /** @test */
    public function dropdown_must_have_at_least_two_options()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][3]['cells'][1]['dropdownOptions'] = ['Asset'];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('At least 2 options are required.', $errorData['specific'][1][3][1]['dropdownOptions']);
    }

    /** @test */
    public function dropdown_options_must_be_unique()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][3]['cells'][1]['dropdownOptions'] = ['Asset', 'Asset', 'Liability'];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Options must be unique.', $errorData['specific'][1][3][1]['dropdownOptions']);
    }

    /** @test */
    public function dropdown_options_cannot_be_empty_strings()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][3]['cells'][1]['dropdownOptions'] = ['Asset', '', 'Liability'];
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('All options must have text.', $errorData['specific'][1][3][1]['dropdownOptions']);
    }

    /** @test */
    public function dropdown_correct_answer_must_be_set()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][3]['cells'][1]['value'] = '';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('Please select a correct answer.', $errorData['specific'][1][3][1]['value']);
    }

    /** @test */
    public function dropdown_correct_answer_must_match_an_option()
    {
        $qtiJson = $this->getQtiJson();
        $qtiJson['tables'][1]['rows'][3]['cells'][1]['value'] = 'NotAnOption';
        $this->setQtiJson($qtiJson);

        $errorData = $this->submitAndGetErrors();
        $this->assertEquals('The correct answer must match one of the options.', $errorData['specific'][1][3][1]['value']);
    }

    // =========================================================================
    // SCORING
    // =========================================================================

    /** @test */
    public function scores_all_correct_submission_as_100_percent()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $result = $submission->computeScoreForAccountingMultiPartComputation(
            $qtiArray, $this->correctStudentResponse()
        );

        $this->assertEquals(1.0, $result['proportionCorrect']);
        foreach ($result['results'] as $ti => $table) {
            foreach ($table as $ri => $row) {
                foreach ($row as $ci => $cell) {
                    $this->assertTrue($cell['isCorrect'], "Cell [$ti][$ri][$ci] should be correct");
                }
            }
        }
    }

    /** @test */
    public function scores_all_incorrect_as_zero()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $studentResponse = [
            '0' => ['0' => ['1' => '99999'],  '1' => ['1' => '99']],
            '1' => ['0' => ['1' => '9.99'],   '1' => ['1' => '9999'], '2' => ['1' => '99'], '3' => ['1' => 'Equity']],
        ];

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertEquals(0, $result['proportionCorrect']);
    }

    /** @test */
    public function scores_partially_correct_submission_proportionally()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // Only dollar (50000) correct — 1 out of 6 total answer cells
        $studentResponse = [
            '0' => ['0' => ['1' => '50000'], '1' => ['1' => '99']],
            '1' => ['0' => ['1' => '9.99'],  '1' => ['1' => '9999'], '2' => ['1' => '99'], '3' => ['1' => 'Equity']],
        ];

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertEquals(round(1 / 6, 4), $result['proportionCorrect']);
    }

    /** @test */
    public function scores_dollar_answer_with_dollar_signs_and_commas_stripped()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $studentResponse = $this->correctStudentResponse();
        $studentResponse['0']['0']['1'] = '$50,000';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertTrue($result['results']['0']['0']['1']['isCorrect']);
    }

    /** @test */
    public function scores_dollar_answer_within_nearest_dollar_tolerance()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // dollarRounding is 'dollar' so tolerance is 0.50 — 50000.49 should match 50000
        $studentResponse = $this->correctStudentResponse();
        $studentResponse['0']['0']['1'] = '50000.49';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertTrue($result['results']['0']['0']['1']['isCorrect']);
    }

    /** @test */
    public function scores_dollar_answer_outside_nearest_dollar_tolerance_as_incorrect()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // 50000.51 is more than 0.50 away from 50000
        $studentResponse = $this->correctStudentResponse();
        $studentResponse['0']['0']['1'] = '50000.51';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertFalse($result['results']['0']['0']['1']['isCorrect']);
    }

    /** @test */
    public function scores_dollar_answer_within_nearest_cent_tolerance()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // Switch rounding to cent
        $qtiArray['tables'][0]['rows'][0]['cells'][1]['dollarRounding'] = 'cent';
        $qtiArray['tables'][0]['rows'][0]['cells'][1]['value'] = '50000.12';

        $studentResponse = $this->correctStudentResponse();
        $studentResponse['0']['0']['1'] = '50000.124'; // within 0.005

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertTrue($result['results']['0']['0']['1']['isCorrect']);
    }

    /** @test */
    public function scores_percentage_with_correct_decimal_tolerance()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // 2 decimal places → tolerance 0.005; 25.004 should match 25.00
        $studentResponse = $this->correctStudentResponse();
        $studentResponse['0']['1']['1'] = '25.004';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertTrue($result['results']['0']['1']['1']['isCorrect']);
    }

    /** @test */
    public function scores_percentage_outside_tolerance_as_incorrect()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // 25.01 is more than 0.005 away from 25.00
        $studentResponse = $this->correctStudentResponse();
        $studentResponse['0']['1']['1'] = '25.01';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertFalse($result['results']['0']['1']['1']['isCorrect']);
    }

    /** @test */
    public function scores_ratio_with_correct_decimal_tolerance()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // 2 decimal places → tolerance 0.005; 2.504 should match 2.50
        $studentResponse = $this->correctStudentResponse();
        $studentResponse['1']['0']['1'] = '2.504';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertTrue($result['results']['1']['0']['1']['isCorrect']);
    }

    /** @test */
    public function scores_custom_units_correctly()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // 0 decimal places → tolerance 0.5; 1500 exact match
        $studentResponse = $this->correctStudentResponse();
        $studentResponse['1']['1']['1'] = '1500';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertTrue($result['results']['1']['1']['1']['isCorrect']);
    }

    /** @test */
    public function scores_general_answer_correctly()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $studentResponse = $this->correctStudentResponse();
        $studentResponse['1']['2']['1'] = '42';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertTrue($result['results']['1']['2']['1']['isCorrect']);
    }

    /** @test */
    public function scores_general_answer_outside_tolerance_as_incorrect()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // 0 decimal places → tolerance 0.5; 42.6 is outside
        $studentResponse = $this->correctStudentResponse();
        $studentResponse['1']['2']['1'] = '42.6';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertFalse($result['results']['1']['2']['1']['isCorrect']);
    }

    /** @test */
    public function scores_dropdown_correct_answer()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $studentResponse = $this->correctStudentResponse();
        $studentResponse['1']['3']['1'] = 'Asset';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertTrue($result['results']['1']['3']['1']['isCorrect']);
    }

    /** @test */
    public function scores_dropdown_case_insensitive()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $studentResponse = $this->correctStudentResponse();
        $studentResponse['1']['3']['1'] = 'asset';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertTrue($result['results']['1']['3']['1']['isCorrect']);
    }

    /** @test */
    public function scores_dropdown_incorrect_answer_as_wrong()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $studentResponse = $this->correctStudentResponse();
        $studentResponse['1']['3']['1'] = 'Liability';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertFalse($result['results']['1']['3']['1']['isCorrect']);
    }

    /** @test */
    public function blank_answer_scores_as_incorrect()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $studentResponse = $this->correctStudentResponse();
        $studentResponse['0']['0']['1'] = '';

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        $this->assertFalse($result['results']['0']['0']['1']['isCorrect']);
    }

    /** @test */
    public function scores_across_multiple_tables_independently()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        // Table 0 all correct, table 1 all wrong
        $studentResponse = [
            '0' => ['0' => ['1' => '50000'], '1' => ['1' => '25']],
            '1' => ['0' => ['1' => '9.99'],  '1' => ['1' => '9999'], '2' => ['1' => '99'], '3' => ['1' => 'Equity']],
        ];

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, $studentResponse);

        // 2 correct out of 6 total
        $this->assertEquals(round(2 / 6, 4), $result['proportionCorrect']);

        $this->assertTrue($result['results']['0']['0']['1']['isCorrect']);
        $this->assertTrue($result['results']['0']['1']['1']['isCorrect']);
        $this->assertFalse($result['results']['1']['0']['1']['isCorrect']);
        $this->assertFalse($result['results']['1']['3']['1']['isCorrect']);
    }

    /** @test */
    public function results_contain_required_fields()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $result = $submission->computeScoreForAccountingMultiPartComputation(
            $qtiArray, $this->correctStudentResponse()
        );

        $this->assertArrayHasKey('proportionCorrect', $result);
        $this->assertArrayHasKey('results', $result);

        $cell = $result['results']['0']['0']['1'];
        $this->assertArrayHasKey('studentValue', $cell);
        $this->assertArrayHasKey('expectedValue', $cell);
        $this->assertArrayHasKey('isCorrect', $cell);
    }

    /** @test */
    public function proportion_correct_is_zero_when_no_answer_cells_submitted()
    {
        [$submission, $qtiArray] = $this->makeSubmission();

        $result = $submission->computeScoreForAccountingMultiPartComputation($qtiArray, []);

        $this->assertEquals(0, $result['proportionCorrect']);
    }
}
