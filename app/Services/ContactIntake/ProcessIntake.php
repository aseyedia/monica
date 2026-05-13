<?php

namespace App\Services\ContactIntake;

use App\Models\ContactIntakeSubmission;
use App\Models\Contact\ContactField;

class ProcessIntake
{
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    public function isDuplicatePhone(string $normalized): bool
    {
        if ($normalized === '') {
            return false;
        }

        // Check normalized form AND raw stored values (Monica stores numbers with formatting)
        return ContactField::get()->contains(function ($field) use ($normalized) {
            return $this->normalizePhone($field->data) === $normalized;
        });
    }

    public function store(array $data, string $ip): ContactIntakeSubmission
    {
        $normalized = $this->normalizePhone($data['phone']);
        $duplicate = $this->isDuplicatePhone($normalized);

        return ContactIntakeSubmission::create([
            'name'             => $data['name'],
            'phone_raw'        => $data['phone'],
            'phone_normalized' => $normalized,
            'email'            => $data['email'] ?? null,
            'company'          => $data['company'] ?? null,
            'note'             => $data['note'] ?? null,
            'raw_vcf'          => $data['raw_vcf'] ?? null,
            'ip_address'       => $ip,
            'status'           => $duplicate ? 'discarded' : 'pending',
        ]);
    }
}
