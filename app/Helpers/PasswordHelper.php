<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Validation\Rules\Password;

class PasswordHelper
{
    /**
     * Get password validation rules based on settings
     *
     * @param int|null $workspaceId
     * @return Password
     */
    public static function getPasswordRule($workspaceId = null)
    {
        $requirements = Setting::getPasswordRequirements($workspaceId);
        
        $rule = Password::min($requirements['min_length']);

        if ($requirements['require_uppercase']) {
            $rule->mixedCase();
        } elseif ($requirements['require_lowercase']) {
            $rule->letters();
        }

        if ($requirements['require_numbers']) {
            $rule->numbers();
        }

        if ($requirements['require_symbols']) {
            $rule->symbols();
        }

        return $rule;
    }

    /**
     * Get password requirements description
     *
     * @param int|null $workspaceId
     * @return string
     */
    public static function getPasswordDescription($workspaceId = null)
    {
        $requirements = Setting::getPasswordRequirements($workspaceId);
        
        $parts = [];
        $parts[] = "Minimal {$requirements['min_length']} karakter";
        
        if ($requirements['require_uppercase'] && $requirements['require_lowercase']) {
            $parts[] = "huruf besar dan kecil";
        } elseif ($requirements['require_uppercase']) {
            $parts[] = "huruf besar";
        } elseif ($requirements['require_lowercase']) {
            $parts[] = "huruf kecil";
        }
        
        if ($requirements['require_numbers']) {
            $parts[] = "angka";
        }
        
        if ($requirements['require_symbols']) {
            $parts[] = "simbol";
        }

        return "Password harus mengandung: " . implode(', ', $parts);
    }

    /**
     * Generate a random password that meets requirements
     *
     * @param int|null $workspaceId
     * @return string
     */
    public static function generatePassword($workspaceId = null)
    {
        $requirements = Setting::getPasswordRequirements($workspaceId);
        $minLength = $requirements['min_length'];
        
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        $characters = '';
        
        // Ensure at least one of each required type
        if ($requirements['require_uppercase']) {
            $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
            $characters .= $uppercase;
        }
        if ($requirements['require_lowercase']) {
            $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
            $characters .= $lowercase;
        }
        if ($requirements['require_numbers']) {
            $password .= $numbers[random_int(0, strlen($numbers) - 1)];
            $characters .= $numbers;
        }
        if ($requirements['require_symbols']) {
            $password .= $symbols[random_int(0, strlen($symbols) - 1)];
            $characters .= $symbols;
        }
        
        // If no specific requirements, use all characters
        if (empty($characters)) {
            $characters = $uppercase . $lowercase . $numbers . $symbols;
            // Add at least one character from each set
            $password = $uppercase[random_int(0, strlen($uppercase) - 1)] .
                       $lowercase[random_int(0, strlen($lowercase) - 1)] .
                       $numbers[random_int(0, strlen($numbers) - 1)] .
                       $symbols[random_int(0, strlen($symbols) - 1)];
            $remaining = $minLength - 4;
        } else {
            $remaining = $minLength - strlen($password);
        }
        
        // Fill the rest randomly
        for ($i = 0; $i < $remaining; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        // Shuffle to randomize position
        return str_shuffle($password);
    }
}

