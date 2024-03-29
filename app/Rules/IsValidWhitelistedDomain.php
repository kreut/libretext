<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IsValidWhitelistedDomain implements Rule
{
    private $email;
    private $access_code;
    /**
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($email, $access_code)
    {
        $this->email = $email;
        $this->access_code = $access_code;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $section = DB::table('sections')->where('access_code', $this->access_code)->first();
        if (!$section) {
            $this->message = "That access code does not belong to any section.";
            return false;
        }
        $course_id = $section->course_id;
        $whitelisted_domains = DB::table('whitelisted_domains')
            ->where('course_id', $course_id)
            ->select('whitelisted_domain')
            ->pluck('whitelisted_domain')
            ->toArray();
        foreach ($whitelisted_domains as $whitelisted_domain) {
            if (strpos($this->email, $whitelisted_domain) !== false) {
                return true;
            }
        }
        $domain_list = count($whitelisted_domains) > 1 ? "(s):" . implode(', ', $whitelisted_domains) : ': ' . $whitelisted_domains[0];
        $this->message = "You can only enroll in this course using an email from the following domain$domain_list.  You are current trying to enroll using the email: $this->email which has a different domain. If you need to use this email, please contact your instructor so that they can add it to their list of whitelisted domains.";
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
