<?php

namespace App\Http\Requests;

use App\Rules\IsADateLaterThan;
use App\Rules\IsValidPeriodOfTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateForgeSettings extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $rules = [];

        // Draft validation rules
        $this->drafts = $this->drafts ?: [];
        $totalDrafts = count($this->drafts);

        foreach ($this->drafts as $draft_index => $draft) {
            $draftName = $draft['title'] ?: ($draft['isFinal'] ? 'Final Submission' : 'Draft ' . ($draft_index + 1));
            $assign_tos = $draft['assign_tos'] ?? [];

            // Validate draft UUID and isFinal flag
            $rules["drafts.{$draft_index}.uuid"] = 'required|uuid';
            $rules["drafts.{$draft_index}.isFinal"] = 'required|boolean';
            $rules["drafts.{$draft_index}.question_id"] = 'nullable|integer';

            // Late policy validation
            $rules["drafts.{$draft_index}.late_policy"] = ['required', Rule::in(['not accepted', 'marked late', 'deduction'])];

            $latePolicy = $draft['late_policy'] ?? '';

            // Late deduction fields (only required if late_policy is 'deduction')
            if ($latePolicy === 'deduction') {
                $rules["drafts.{$draft_index}.late_deduction_percent"] = 'required|integer|min:1|max:99';
                $rules["drafts.{$draft_index}.late_deduction_applied_once"] = 'required|boolean';

                // late_deduction_application_period required if not applied once
                $lateDeductionAppliedOnce = $draft['late_deduction_applied_once'] ?? true;
                if (!$lateDeductionAppliedOnce) {
                    $rules["drafts.{$draft_index}.late_deduction_application_period"] = ['required', new IsValidPeriodOfTime()];
                }
            }

            foreach ($assign_tos as $assign_to_index => $assign_to) {
                $groupName = $assign_to['groups'][0]['text'] ?? 'Group ' . ($assign_to_index + 1);
                $prefix = $draftName . ' (' . $groupName . ')';

                // Build the available_from datetime for comparison
                $availableFrom = $this->input("drafts.{$draft_index}.assign_tos.{$assign_to_index}.available_from_date")
                    . ' '
                    . $this->input("drafts.{$draft_index}.assign_tos.{$assign_to_index}.available_from_time");

                // Build the due datetime for comparison
                $due = $this->input("drafts.{$draft_index}.assign_tos.{$assign_to_index}.due_date")
                    . ' '
                    . $this->input("drafts.{$draft_index}.assign_tos.{$assign_to_index}.due_time");

                $rules["drafts.{$draft_index}.assign_tos.{$assign_to_index}.available_from_date"] = 'required|date';
                $rules["drafts.{$draft_index}.assign_tos.{$assign_to_index}.available_from_time"] = 'required|date_format:g:i A';
                $rules["drafts.{$draft_index}.assign_tos.{$assign_to_index}.due_date"] = 'required|date';
                $rules["drafts.{$draft_index}.assign_tos.{$assign_to_index}.due_time"] = 'required|date_format:g:i A';

                // Due must be after available_from
                $rules["drafts.{$draft_index}.assign_tos.{$assign_to_index}.due"] = new IsADateLaterThan(
                    $availableFrom,
                    'available on',
                    'due',
                    $prefix
                );

                // Final submission deadline validation (only if late_policy is not 'not accepted')
                if (in_array($latePolicy, ['marked late', 'deduction'])) {
                    $rules["drafts.{$draft_index}.assign_tos.{$assign_to_index}.final_submission_deadline_date"] = 'required|date';
                    $rules["drafts.{$draft_index}.assign_tos.{$assign_to_index}.final_submission_deadline_time"] = 'required|date_format:g:i A';

                    // Final submission deadline must be after due date
                    $rules["drafts.{$draft_index}.assign_tos.{$assign_to_index}.final_submission_deadline"] = new IsADateLaterThan(
                        $due,
                        'due',
                        'final submission deadline',
                        $prefix
                    );
                }
            }

            // Extension validation (at draft level)
            $extensions = $draft['extensions'] ?? [];
            foreach ($extensions as $ext_index => $extension) {
                $studentName = !empty($extension['student_name']) ? $extension['student_name'] : 'Extension ' . ($ext_index + 1);
                $extPrefix = "{$draftName} - {$studentName}";

                $rules["drafts.{$draft_index}.extensions.{$ext_index}.user_id"] = 'required|integer';
                $rules["drafts.{$draft_index}.extensions.{$ext_index}.due_date"] = 'required|date';
                $rules["drafts.{$draft_index}.extensions.{$ext_index}.due_time"] = 'required|date_format:g:i A';

                // Use the first assign_to's available_from for comparison
                $firstAvailableFrom = '';
                if (!empty($assign_tos)) {
                    $firstAvailableFrom = $this->input("drafts.{$draft_index}.assign_tos.0.available_from_date")
                        . ' '
                        . $this->input("drafts.{$draft_index}.assign_tos.0.available_from_time");
                }

                // Extension due must be after available_from
                $extDue = ($extension['due_date'] ?? '') . ' ' . ($extension['due_time'] ?? '');
                $rules["drafts.{$draft_index}.extensions.{$ext_index}.due"] = new IsADateLaterThan(
                    $firstAvailableFrom,
                    'available on',
                    'extension due',
                    $extPrefix
                );

                // Final submission deadline validation for extensions (only if late_policy is not 'not accepted')
                if (in_array($latePolicy, ['marked late', 'deduction'])) {
                    $rules["drafts.{$draft_index}.extensions.{$ext_index}.final_submission_deadline_date"] = 'required|date';
                    $rules["drafts.{$draft_index}.extensions.{$ext_index}.final_submission_deadline_time"] = 'required|date_format:g:i A';

                    // Extension final submission deadline must be after extension due date
                    $rules["drafts.{$draft_index}.extensions.{$ext_index}.final_submission_deadline"] = new IsADateLaterThan(
                        $extDue,
                        'extension due',
                        'extension final submission deadline',
                        $extPrefix
                    );
                }
            }
        }

        // Validate that exactly one draft has isFinal = true
        $rules['drafts'] = ['required', 'array', 'min:1', function ($attribute, $value, $fail) {
            $finalCount = collect($value)->where('isFinal', true)->count();
            if ($finalCount === 0) {
                $fail('A Final Submission is required.');
            } elseif ($finalCount > 1) {
                $fail('Only one Final Submission is allowed.');
            }
        }];

        // Auto-submission validation with late policy check
        $rules['settings.autoSubmission'] = ['required', 'boolean', function ($attribute, $value, $fail) {
            if ($value) {
                $drafts = $this->input('drafts', []);
                foreach ($drafts as $draft) {
                    $latePolicy = $draft['late_policy'] ?? '';
                    if (in_array($latePolicy, ['marked late', 'deduction'])) {
                        $draftName = $draft['title'] ?: ($draft['isFinal'] ?? false ? 'Final Submission' : 'Draft');
                        $fail("Auto-submit at deadline cannot be enabled when \"{$draftName}\" accepts late submissions. Please set all drafts to \"Do not accept late\" or disable auto-submit.");
                        return;
                    }
                }
            }
        }];

        $rules['settings.preventAfterDueDate'] = 'required|boolean';
        $rules['settings.autoAccept'] = 'required|boolean';
        $rules['settings.showAnalytics'] = ['required', Rule::in(['never', 'after_grade', 'always'])];
        $rules['settings.mainFileType'] = ['required', Rule::in(['document', 'spreadsheet', 'presentation', 'draw', 'image'])];
        $rules['settings.allowImport'] = 'required|boolean';
        $rules['settings.additionalFiles'] = 'present|array';
        $rules['settings.additionalFiles.*'] = Rule::in(['presentation', 'spreadsheet', 'document', 'draw']);
        $rules['settings.uploadFile'] = 'required|boolean';

        return $rules;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $drafts = $this->drafts ?: [];

        // Build combined datetime fields for validation
        foreach ($drafts as $draft_index => $draft) {
            // Cast isFinal to boolean
            if (isset($drafts[$draft_index]['isFinal'])) {
                $drafts[$draft_index]['isFinal'] = filter_var($drafts[$draft_index]['isFinal'], FILTER_VALIDATE_BOOLEAN);
            }

            // Cast late_deduction_applied_once to boolean
            if (isset($drafts[$draft_index]['late_deduction_applied_once'])) {
                $drafts[$draft_index]['late_deduction_applied_once'] = filter_var($drafts[$draft_index]['late_deduction_applied_once'], FILTER_VALIDATE_BOOLEAN);
            }

            $assign_tos = $draft['assign_tos'] ?? [];

            foreach ($assign_tos as $assign_to_index => $assign_to) {
                $availableFromDate = $assign_to['available_from_date'] ?? '';
                $availableFromTime = $assign_to['available_from_time'] ?? '';
                $dueDate = $assign_to['due_date'] ?? '';
                $dueTime = $assign_to['due_time'] ?? '';
                $finalDeadlineDate = $assign_to['final_submission_deadline_date'] ?? '';
                $finalDeadlineTime = $assign_to['final_submission_deadline_time'] ?? '';

                // Set combined datetime fields
                $drafts[$draft_index]['assign_tos'][$assign_to_index]['available_from'] = "{$availableFromDate} {$availableFromTime}";
                $drafts[$draft_index]['assign_tos'][$assign_to_index]['due'] = "{$dueDate} {$dueTime}";
                $drafts[$draft_index]['assign_tos'][$assign_to_index]['final_submission_deadline'] = "{$finalDeadlineDate} {$finalDeadlineTime}";
            }

            // Prepare extension datetime fields (at draft level)
            $extensions = $draft['extensions'] ?? [];
            foreach ($extensions as $ext_index => $extension) {
                $extDueDate = $extension['due_date'] ?? '';
                $extDueTime = $extension['due_time'] ?? '';
                $extFinalDate = $extension['final_submission_deadline_date'] ?? '';
                $extFinalTime = $extension['final_submission_deadline_time'] ?? '';

                $drafts[$draft_index]['extensions'][$ext_index]['due'] = "{$extDueDate} {$extDueTime}";
                $drafts[$draft_index]['extensions'][$ext_index]['final_submission_deadline'] = "{$extFinalDate} {$extFinalTime}";
            }
        }

        $this->merge(['drafts' => $drafts]);

        // Cast boolean fields
        if ($this->has('settings')) {
            $settings = $this->settings;
            $booleanFields = ['autoSubmission', 'preventAfterDueDate', 'autoAccept', 'allowImport', 'uploadFile'];

            foreach ($booleanFields as $field) {
                if (isset($settings[$field])) {
                    $settings[$field] = filter_var($settings[$field], FILTER_VALIDATE_BOOLEAN);
                }
            }

            $this->merge(['settings' => $settings]);
        }
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        $messages = [];

        // Draft messages
        $this->drafts = $this->drafts ?: [];

        foreach ($this->drafts as $draft_index => $draft) {
            $draftName = $draft['title'] ?: ($draft['isFinal'] ?? false ? 'Final Submission' : 'Draft ' . ($draft_index + 1));
            $assign_tos = $draft['assign_tos'] ?? [];

            // UUID and isFinal messages
            $messages["drafts.{$draft_index}.uuid.required"] = "{$draftName}: UUID is required.";
            $messages["drafts.{$draft_index}.uuid.uuid"] = "{$draftName}: UUID must be a valid UUID.";
            $messages["drafts.{$draft_index}.isFinal.required"] = "{$draftName}: Final submission flag is required.";
            $messages["drafts.{$draft_index}.isFinal.boolean"] = "{$draftName}: Final submission flag must be true or false.";

            // Late policy messages
            $messages["drafts.{$draft_index}.late_policy.required"] = "{$draftName}: Late policy is required.";
            $messages["drafts.{$draft_index}.late_policy.in"] = "{$draftName}: Late policy must be 'not accepted', 'marked late', or 'deduction'.";

            // Late deduction messages
            $messages["drafts.{$draft_index}.late_deduction_percent.required"] = "{$draftName}: Late deduction percent is required when using deduction policy.";
            $messages["drafts.{$draft_index}.late_deduction_percent.integer"] = "{$draftName}: Late deduction percent must be a whole number.";
            $messages["drafts.{$draft_index}.late_deduction_percent.min"] = "{$draftName}: Late deduction percent must be at least 1.";
            $messages["drafts.{$draft_index}.late_deduction_percent.max"] = "{$draftName}: Late deduction percent cannot exceed 99.";
            $messages["drafts.{$draft_index}.late_deduction_applied_once.required"] = "{$draftName}: Late deduction applied setting is required.";
            $messages["drafts.{$draft_index}.late_deduction_applied_once.boolean"] = "{$draftName}: Late deduction applied must be true or false.";
            $messages["drafts.{$draft_index}.late_deduction_application_period.required"] = "{$draftName}: Late deduction application period is required when not applied just once.";

            foreach ($assign_tos as $assign_to_index => $assign_to) {
                $groupName = $assign_to['groups'][0]['text'] ?? 'Group ' . ($assign_to_index + 1);
                $prefix = $draftName . ' (' . $groupName . ')';

                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.available_from_date.required"] = "{$prefix}: Available from date is required.";
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.available_from_date.date"] = "{$prefix}: Available from date must be a valid date.";
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.available_from_time.required"] = "{$prefix}: " . $this->getTimeFormatErrorMessage('available on');
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.available_from_time.date_format"] = "{$prefix}: " . $this->getTimeFormatErrorMessage('available on');
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.due_date.required"] = "{$prefix}: Due date is required.";
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.due_date.date"] = "{$prefix}: Due date must be a valid date.";
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.due_time.required"] = "{$prefix}: " . $this->getTimeFormatErrorMessage('due time');
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.due_time.date_format"] = "{$prefix}: " . $this->getTimeFormatErrorMessage('due time');

                // Final submission deadline messages
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.final_submission_deadline_date.required"] = "{$prefix}: Final submission deadline date is required.";
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.final_submission_deadline_date.date"] = "{$prefix}: Final submission deadline date must be a valid date.";
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.final_submission_deadline_time.required"] = "{$prefix}: " . $this->getTimeFormatErrorMessage('final submission deadline');
                $messages["drafts.{$draft_index}.assign_tos.{$assign_to_index}.final_submission_deadline_time.date_format"] = "{$prefix}: " . $this->getTimeFormatErrorMessage('final submission deadline');
            }

            // Extension messages (at draft level)
            $extensions = $draft['extensions'] ?? [];
            foreach ($extensions as $ext_index => $extension) {
                $studentName = !empty($extension['student_name']) ? $extension['student_name'] : 'Extension ' . ($ext_index + 1);
                $extPrefix = "{$draftName} - {$studentName}";

                $messages["drafts.{$draft_index}.extensions.{$ext_index}.user_id.required"] = "{$extPrefix}: Student is required.";
                $messages["drafts.{$draft_index}.extensions.{$ext_index}.user_id.integer"] = "{$extPrefix}: Student must be valid.";
                $messages["drafts.{$draft_index}.extensions.{$ext_index}.due_date.required"] = "{$extPrefix}: Due date is required.";
                $messages["drafts.{$draft_index}.extensions.{$ext_index}.due_date.date"] = "{$extPrefix}: Due date must be a valid date.";
                $messages["drafts.{$draft_index}.extensions.{$ext_index}.due_time.required"] = "{$extPrefix}: " . $this->getTimeFormatErrorMessage('due');
                $messages["drafts.{$draft_index}.extensions.{$ext_index}.due_time.date_format"] = "{$extPrefix}: " . $this->getTimeFormatErrorMessage('due');
                $messages["drafts.{$draft_index}.extensions.{$ext_index}.final_submission_deadline_date.required"] = "{$extPrefix}: Final submission deadline date is required.";
                $messages["drafts.{$draft_index}.extensions.{$ext_index}.final_submission_deadline_date.date"] = "{$extPrefix}: Final submission deadline date must be a valid date.";
                $messages["drafts.{$draft_index}.extensions.{$ext_index}.final_submission_deadline_time.required"] = "{$extPrefix}: " . $this->getTimeFormatErrorMessage('final submission deadline');
                $messages["drafts.{$draft_index}.extensions.{$ext_index}.final_submission_deadline_time.date_format"] = "{$extPrefix}: " . $this->getTimeFormatErrorMessage('final submission deadline');
            }
        }

        // Settings messages
        $messages['settings.autoSubmission.required'] = 'Auto-submission setting is required.';
        $messages['settings.autoSubmission.boolean'] = 'Auto-submission must be true or false.';
        $messages['settings.preventAfterDueDate.required'] = 'Prevent after due date setting is required.';
        $messages['settings.preventAfterDueDate.boolean'] = 'Prevent after due date must be true or false.';
        $messages['settings.autoAccept.required'] = 'Auto-accept setting is required.';
        $messages['settings.autoAccept.boolean'] = 'Auto-accept must be true or false.';
        $messages['settings.showAnalytics.required'] = 'Show analytics setting is required.';
        $messages['settings.showAnalytics.in'] = 'Show analytics must be never, after_grade, or always.';
        $messages['settings.mainFileType.required'] = 'Main file type is required.';
        $messages['settings.mainFileType.in'] = 'Main file type must be document, spreadsheet, presentation, draw, or image.';
        $messages['settings.allowImport.required'] = 'Allow import setting is required.';
        $messages['settings.allowImport.boolean'] = 'Allow import must be true or false.';
        $messages['settings.additionalFiles.present'] = 'Additional files field must be present.';
        $messages['settings.additionalFiles.array'] = 'Additional files must be an array.';
        $messages['settings.additionalFiles.*.in'] = 'Additional file type must be presentation, spreadsheet, document, or draw.';
        $messages['settings.uploadFile.required'] = 'Upload file setting is required.';
        $messages['settings.uploadFile.boolean'] = 'Upload file must be true or false.';

        return $messages;
    }

    /**
     * @param string $field
     * @return string
     */
    public function getTimeFormatErrorMessage(string $field): string
    {
        return "Time for {$field} needs a valid time such as 9:00 AM.";
    }
}
