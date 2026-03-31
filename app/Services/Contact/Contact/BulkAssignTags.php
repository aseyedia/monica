<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Tag;
use App\Services\Contact\Tag\AssociateTag;

class BulkAssignTags extends BaseService
{

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
            'tags' => 'required|array',
            'tags.*' => 'required|string|max:255',
        ];
    }

    /**
     * Bulk assign tags to contacts.
     *
     * @param  array  $data
     * @return array
     */
    public function handle(array $data): array
    {
        $this->validate($data);

        $updatedContactIds = [];
        $failedContactIds = [];

        foreach ($data['contact_ids'] as $contactId) {
            try {
                $contact = Contact::where('account_id', $data['account_id'])
                    ->findOrFail($contactId);

                $contact->throwInactive();

                foreach ($data['tags'] as $tagName) {
                    app(AssociateTag::class)->execute([
                        'account_id' => $data['account_id'],
                        'contact_id' => $contactId,
                        'name' => $tagName,
                    ]);
                }

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
