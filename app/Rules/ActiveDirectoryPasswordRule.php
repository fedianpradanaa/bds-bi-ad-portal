<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveDirectoryPasswordRule implements ValidationRule
{
    /**
     * @param  string|Closure|null  $username  Username or closure returning username
     * @param  string|Closure|null  $name  Full/Display name or closure returning name
     * @param  bool  $checkComplexity  Whether to also validate AD complexity (8+ chars, upper, lower, digits, symbols)
     */
    public function __construct(
        protected mixed $username = null,
        protected mixed $name = null,
        protected bool $checkComplexity = true
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $password = (string) $value;

        // 1. Minimum Length Validation
        if (strlen($password) < 8) {
            $fail('Active Directory requires a minimum password length of 8 characters.');

            return;
        }

        // 2. Complexity Rules: uppercase, lowercase, digit, special character
        if ($this->checkComplexity) {
            if (! preg_match('/[A-Z]/', $password)) {
                $fail('Password must contain at least one uppercase letter (A-Z).');

                return;
            }
            if (! preg_match('/[a-z]/', $password)) {
                $fail('Password must contain at least one lowercase letter (a-z).');

                return;
            }
            if (! preg_match('/[0-9]/', $password)) {
                $fail('Password must contain at least one number (0-9).');

                return;
            }
            if (! preg_match('/[\W_]/', $password)) {
                $fail('Password must contain at least one special character (!@#$%^&* etc.).');

                return;
            }
        }

        // 3. Name and Username Restriction (Active Directory Policy)
        $resolvedUsername = is_callable($this->username) ? ($this->username)() : $this->username;
        $resolvedName = is_callable($this->name) ? ($this->name)() : $this->name;

        $tokens = $this->extractTokens((string) ($resolvedUsername ?? ''), (string) ($resolvedName ?? ''));
        $lowerPassword = strtolower($password);

        foreach ($tokens as $token) {
            if (str_contains($lowerPassword, $token)) {
                $fail("Password cannot contain user's name or username ('{$token}') as required by Active Directory policy.");

                return;
            }
        }
    }

    /**
     * Extract unique tokens of 3 or more characters from username and name.
     * Common system/domain abbreviations (bds, cmc, mhe, local) are excluded
     * unless they represent the entire username.
     *
     * @return array<string>
     */
    public function extractTokens(string $username, string $name): array
    {
        $candidates = [];
        $ignoredSubstrings = ['bds', 'cmc', 'mhe', 'local'];

        // Process Username
        $cleanUsername = trim($username);
        if (strlen($cleanUsername) >= 3) {
            $lowerUser = strtolower($cleanUsername);
            $candidates[] = $lowerUser;

            // Strip common prefix like 'bds-' or 'bds_'
            $withoutPrefix = trim(preg_replace('/^(bds|cmc|mhe)[-_\s]*/i', '', $cleanUsername));
            if (strlen($withoutPrefix) >= 3) {
                $candidates[] = strtolower($withoutPrefix);
            }

            // Split username by separators
            foreach (preg_split('/[-_.\s]+/', $cleanUsername) as $part) {
                $part = strtolower(trim($part));
                if (strlen($part) >= 3 && ! in_array($part, $ignoredSubstrings, true)) {
                    $candidates[] = $part;
                }
            }
        }

        // Process Name
        $cleanName = trim($name);
        if (strlen($cleanName) >= 3) {
            $lowerName = strtolower($cleanName);
            $candidates[] = $lowerName;

            // Strip common prefix like 'BDS '
            $withoutPrefix = trim(preg_replace('/^(bds|cmc|mhe)[-_\s]*/i', '', $cleanName));
            if (strlen($withoutPrefix) >= 3) {
                $candidates[] = strtolower($withoutPrefix);
            }

            // Split name into individual words
            foreach (preg_split('/[\s\-_\.,]+/', $cleanName) as $word) {
                $word = strtolower(trim($word));
                if (strlen($word) >= 3 && ! in_array($word, $ignoredSubstrings, true)) {
                    $candidates[] = $word;
                }
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }
}
