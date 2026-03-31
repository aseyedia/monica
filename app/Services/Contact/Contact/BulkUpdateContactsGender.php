<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Gender;
use App\Services\QueuableService;
use App\Services\DispatchableService;

class BulkUpdateContactsGender extends BaseService implements QueuableService
{
    use DispatchableService;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'required|integer|exists:contacts,id',
            'gender_id' => 'nullable|integer|exists:genders,id',
        ];
    }

    /**
     * Bulk update contacts gender.
     *
     * @param  array  $data
     * @return array
     */
    public function handle(array $data): array
    {
        $this->validate($data);

        // Verify gender belongs to the account if provided
        if (isset($data['gender_id'])) {
            Gender::where('account_id', $data['account_id'])
                ->findOrFail($data['gender_id']);
        }

        $updatedContactIds = [];
        $failedContactIds = [];

        foreach ($data['contact_ids'] as $contactId) {
            try {
                $contact = Contact::where('account_id', $data['account_id'])
                    ->findOrFail($contactId);

                $contact->throwInactive();

                $contact->update([
                    'gender_id' => $this->nullOrValue($data, 'gender_id'),
                ]);

                $updatedContactIds[] = $contactId;
            } catch (\Exception $e) {
                $failedContactIds[] = $contactId;
            }
        }

        return [
            'updated' => $updatedContactIds,
            'failed' => $failedContactIds,
        ];
    }
}
