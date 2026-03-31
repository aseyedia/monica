<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Services\QueuableService;
use App\Services\DispatchableService;

class BulkDestroyContacts extends BaseService implements QueuableService
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
            'force_delete' => 'nullable|boolean',
        ];
    }

    /**
     * Bulk destroy contacts.
     *
     * @param  array  $data
     * @return array
     */
    public function handle(array $data): array
    {
        $this->validate($data);

        $deletedContactIds = [];
        $failedContactIds = [];

        foreach ($data['contact_ids'] as $contactId) {
            try {
                app(DestroyContact::class)->handle([
                    'account_id' => $data['account_id'],
                    'contact_id' => $contactId,
                    'force_delete' => $this->valueOrFalse($data, 'force_delete'),
                ]);

                $deletedContactIds[] = $contactId;
            } catch (\Exception $e) {
                $failedContactIds[] = $contactId;
            }
        }

        return [
            'deleted' => $deletedContactIds,
            'failed' => $failedContactIds,
        ];
    }
}
