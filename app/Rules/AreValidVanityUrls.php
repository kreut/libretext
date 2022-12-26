<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class AreValidVanityUrls implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->message = '';
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
        $passes = true;
        if ($value) {
            $vanity_urls = explode(',', $value);
            foreach ($vanity_urls as $vanity_url) {
                $vanity_url = trim($vanity_url);
                if (strpos($vanity_url, 'https://') === false) {
                    $passes = false;
                    $this->message = "$vanity_url should start with https://.";
                } else
                    if (!filter_var($vanity_url, FILTER_VALIDATE_URL)) {
                        $passes = false;
                        $this->message = "$vanity_url is not a valid vanity url.";
                    }
            }
        }
        return $passes;
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
