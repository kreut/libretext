<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class HasValidAccountingJournalEntries implements Rule
{
    protected $errors = [];

    // Valid account titles list
    protected $validAccountTitles = [
        'Accounts Payable',
        'Accounts Receivable',
        'Accrued Pension Liability',
        'Accumulated Depreciation-Buildings',
        'Accumulated Depreciation-Equipment',
        'Accumulated Depreciation- Plant Assets',
        'Accumulated Other Comprehensive Income',
        'Accumulated Other Comprehensive Loss',
        'Additional Paid-in Capital, Common Stock',
        'Additional Paid-in Capital, Preferred Stock',
        'Administrative Expenses',
        'Advertising Expense',
        'Allowance for Doubtful Accounts',
        'Amortization Expense',
        'Bad Debt Expense',
        'Bank Charges Expense',
        'Bonds Payable',
        'Buildings',
        'Cash',
        'Common Stock',
        'Common Stock Dividends Distributable',
        'Copyrights',
        'Cost of Goods Sold',
        'Current Portion of Long-Term Debt',
        'Deferred Revenue',
        'Delivery Expense',
        'Depreciation Expense',
        'Discount on Bonds Payable',
        'Dividends',
        'Dividends Payable',
        'Entertainment Expense',
        'Equipment',
        'Federal Income Taxes Payable',
        'Federal Unemployment Taxes Payable',
        'FICA Taxes Payable',
        'Franchise',
        'Freight-In',
        'Freight-Out',
        'Gain on Bond Redemption',
        'Gain on Disposal of Plant Assets',
        'Gain on Sale of Investments',
        'Goodwill',
        'Impairment Loss',
        'Income Summary',
        'Income Tax Expense',
        'Income Taxes Payable',
        'Insurance Expense',
        'Intangible Assets',
        'Interest Expense',
        'Interest Income',
        'Interest Payable',
        'Interest Receivable',
        'Interest Revenue',
        'Inventory',
        'Land',
        'Land Improvements',
        'Loss on Disposal of Plant Assets',
        'Loss on Sale of Equipment',
        'Maintenance and Repairs Expense',
        'Miscellaneous Expense',
        'Mortgage Payable',
        'No Entry',
        'Notes Payable',
        'Notes Receivable',
        'Operating Expenses',
        'Other Operating Expenses',
        'Other Receivables',
        'Patents',
        'Payroll Tax Expense',
        'Petty Cash',
        'Plant Assets',
        'Postage Expense',
        'Preferred Stock',
        'Premium on Bonds Payable',
        'Prepaid Advertising',
        'Prepaid Expenses',
        'Prepaid Insurance',
        'Prepaid Rent',
        'Property Tax Expense',
        'Property Taxes Payable',
        'Purchase Discounts',
        'Purchase Returns and Allowances',
        'Purchases',
        'Rent Expense',
        'Rent Revenue',
        'Repairs Expense',
        'Research and Development Expense',
        'Retained Earnings',
        'Salaries and Wages Expense',
        'Salaries and Wages Payable',
        'Sales Discounts',
        'Sales Returns and Allowances',
        'Sales Revenue',
        'Sales Taxes Payable',
        'Selling Expense',
        'Service Charge Expense',
        'Service Revenue',
        'State Income Taxes Payable',
        'State Unemployment Taxes Payable',
        'Stock Dividends',
        'Supplies',
        'Supplies Expense',
        'Travel Expense',
        'Treasury Stock',
        'Unearned Rent Revenue',
        'Unearned Sales Revenue',
        'Unearned Service Revenue',
        'Union Dues Payable',
        'Utilities Expense',
        'Warranty Liability'
    ];

    /**
     * Determine if the validation passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->errors = [
            'specific' => [],
            'general' => null
        ];

        // If value is a JSON string, decode it
        if (is_string($value)) {
            $value = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errors['general'] = 'Invalid JSON format.';
                return false;
            }
        }

        // Check if entries exist and is an array
        if (!isset($value['entries']) || !is_array($value['entries'])) {
            $this->errors['general'] = 'No journal entries provided.';
            return false;
        }

        $entries = $value['entries'];

        // Require at least one entry
        if (count($entries) === 0) {
            $this->errors['general'] = 'At least one journal entry is required.';
            return false;
        }

        $hasErrors = false;

        foreach ($entries as $entryIndex => $entry) {
            $entryErrors = [];

            // Validate entry text
            if (empty($entry['entryText']) || trim($entry['entryText']) === '') {
                $entryErrors['entryText'] = 'Entry text is required.';
                $hasErrors = true;
            }

            // Validate entry description
            if (empty($entry['entryDescription']) || trim($entry['entryDescription']) === '') {
                $entryErrors['entryDescription'] = 'Entry description is required.';
                $hasErrors = true;
            }

            // Validate solution rows
            if (!isset($entry['solutionRows']) || !is_array($entry['solutionRows'])) {
                $entryErrors['solutionRows'] = ['general' => 'Solution rows are required.'];
                $hasErrors = true;
            } else {
                $solutionRows = $entry['solutionRows'];
                $rowErrors = [];

                // Require at least 2 rows
                if (count($solutionRows) < 2) {
                    $rowErrors['general'] = 'At least 2 solution rows are required.';
                    $hasErrors = true;
                } elseif (count($solutionRows) > 5) {
                    $rowErrors['general'] = 'Maximum of 5 solution rows allowed.';
                    $hasErrors = true;
                }

                $totalDebits = 0;
                $totalCredits = 0;

                foreach ($solutionRows as $rowIndex => $row) {
                    $rowFieldErrors = [];

                    // Validate account title
                    if (empty($row['accountTitle']) || trim($row['accountTitle']) === '') {
                        $rowFieldErrors['accountTitle'] = 'Account title is required.';
                        $hasErrors = true;
                    } elseif (!in_array($row['accountTitle'], $this->validAccountTitles)) {
                        $rowFieldErrors['accountTitle'] = 'Account title must be from the valid list of accounts.';
                        $hasErrors = true;
                    }

                    // Validate type
                    if (empty($row['type']) || !in_array($row['type'], ['debit', 'credit'])) {
                        $rowFieldErrors['type'] = 'Type must be either debit or credit.';
                        $hasErrors = true;
                    }

                    // Validate amount
                    if (!isset($row['amount']) || $row['amount'] === '' || $row['amount'] === null) {
                        $rowFieldErrors['amount'] = 'Amount is required.';
                        $hasErrors = true;
                    } elseif (!is_numeric($row['amount']) || floatval($row['amount']) <= 0) {
                        $rowFieldErrors['amount'] = 'Amount must be greater than 0.';
                        $hasErrors = true;
                    } else {
                        // Calculate totals
                        $amount = floatval($row['amount']);
                        if (isset($row['type'])) {
                            if ($row['type'] === 'debit') {
                                $totalDebits += $amount;
                            } elseif ($row['type'] === 'credit') {
                                $totalCredits += $amount;
                            }
                        }
                    }

                    if (!empty($rowFieldErrors)) {
                        $rowErrors[$rowIndex] = $rowFieldErrors;
                    }
                }

                // Check if debits and credits balance (within 0.01 tolerance for floating point)
                if (abs($totalDebits - $totalCredits) > 0.01 && $totalDebits > 0 && $totalCredits > 0) {
                    $rowErrors['general'] = sprintf(
                        'Entry does not balance. Debits: $%.2f, Credits: $%.2f',
                        $totalDebits,
                        $totalCredits
                    );
                    $hasErrors = true;
                }

                if (!empty($rowErrors)) {
                    $entryErrors['solutionRows'] = $rowErrors;
                }
            }

            if (!empty($entryErrors)) {
                $this->errors['specific'][$entryIndex] = $entryErrors;
            }
        }

        // Clean up empty specific errors
        if (empty($this->errors['specific'])) {
            unset($this->errors['specific']);
        }

        return !$hasErrors;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return json_encode($this->errors);
    }
}
