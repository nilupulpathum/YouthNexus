<?php

class ClubMemberRegistrationValidator {

    public static function validate(array $input): array {
        $length = static function ($value) {
            return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        };
        $values = [
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'address' => trim((string) ($input['address'] ?? '')),
            'nic' => trim((string) ($input['nic'] ?? '')),
        ];
        $errors = [];

        foreach ($values as $field => $value) {
            if ($value === '') {
                $errors[$field] = 'All fields are required.';
            }
        }
        if ($errors) {
            return ['values' => $values, 'errors' => $errors];
        }

        $nameParts = preg_split('/\s+/u', $values['name'], 2);
        if (count($nameParts) < 2 || $length($nameParts[0]) > 50 || $length($nameParts[1]) > 50) {
            $errors['name'] = 'Enter a first and last name of up to 50 characters each.';
        }
        if ($length($values['email']) > 100 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address of up to 100 characters.';
        }
        if (!preg_match('/^[0-9+(). -]{7,20}$/', $values['phone'])) {
            $errors['phone'] = 'Enter a valid phone number of 7 to 20 characters.';
        }
        if ($length($values['address']) > 255) {
            $errors['address'] = 'Address must be 255 characters or fewer.';
        }
        if (!preg_match('/^[A-Za-z0-9-]{5,20}$/', $values['nic'])) {
            $errors['nic'] = 'Enter a valid NIC of 5 to 20 letters, numbers, or hyphens.';
        }

        return ['values' => $values, 'errors' => $errors];
    }
}
