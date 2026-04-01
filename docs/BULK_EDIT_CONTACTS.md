# Bulk Edit Contacts Feature

This document describes the bulk edit functionality added to Monica CRM, allowing users to perform operations on multiple contacts simultaneously.

## Overview

The bulk edit feature enables users to:
- **Delete multiple contacts** at once
- **Update gender** for multiple contacts
- **Assign tags** to multiple contacts

## Architecture

### Backend Services

All bulk operations follow the existing service pattern in Monica:

#### 1. BulkDestroyContacts
**Location:** `app/Services/Contact/Contact/BulkDestroyContacts.php`

Deletes multiple contacts using the existing `DestroyContact` service for each contact.

**Parameters:**
- `account_id` (required): The account ID
- `contact_ids` (required, array): Array of contact IDs to delete
- `force_delete` (optional, boolean): Whether to force delete (permanently remove)

**Returns:**
```php
[
    'deleted' => [1, 2, 3], // IDs of successfully deleted contacts
    'failed' => [4]          // IDs of contacts that failed to delete
]
```

#### 2. BulkUpdateContactsGender
**Location:** `app/Services/Contact/Contact/BulkUpdateContactsGender.php`

Updates the gender field for multiple contacts.

**Parameters:**
- `account_id` (required): The account ID
- `contact_ids` (required, array): Array of contact IDs to update
- `gender_id` (nullable): Gender ID to set (null to clear gender)

**Returns:**
```php
[
    'updated' => [1, 2, 3], // IDs of successfully updated contacts
    'failed' => [4]          // IDs of contacts that failed to update
]
```

#### 3. BulkAssignTags
**Location:** `app/Services/Contact/Contact/BulkAssignTags.php`

Assigns one or more tags to multiple contacts. Creates tags if they don't exist.

**Parameters:**
- `account_id` (required): The account ID
- `contact_ids` (required, array): Array of contact IDs
- `tags` (required, array): Array of tag names to assign

**Returns:**
```php
[
    'updated' => [1, 2, 3], // IDs of successfully updated contacts
    'failed' => [4]          // IDs of contacts that failed to update
]
```

### API Endpoints

**Controller:** `app/Http/Controllers/Api/ApiBulkContactController.php`

All endpoints require authentication and are prefixed with `/api`.

#### DELETE Multiple Contacts
```
POST /api/contacts/bulk/destroy
```

**Request Body:**
```json
{
  "contact_ids": [1, 2, 3],
  "force_delete": false
}
```

#### UPDATE Gender
```
POST /api/contacts/bulk/gender
```

**Request Body:**
```json
{
  "contact_ids": [1, 2, 3],
  "gender_id": 5
}
```

#### ASSIGN Tags
```
POST /api/contacts/bulk/tags
```

**Request Body:**
```json
{
  "contact_ids": [1, 2, 3],
  "tags": ["friend", "family", "work"]
}
```

### Frontend Implementation

**Component:** `resources/js/components/people/ContactList.vue`

#### Features Added:

1. **Multi-Select Checkboxes**
   - Uses vue-good-table's built-in selection
   - `selectOnCheckboxOnly` prevents row click conflicts
   - Selection state tracked in `selectedContacts` array
   - **Large Clickable Area**: Entire checkbox cell is clickable, not just the tiny checkbox
   - Visual hover feedback on checkbox column
   - Prevents accidental navigation when selecting

2. **Selection Helper Bar** (Always Visible)
   - "Select All on Page" button - selects all visible contacts
   - "Deselect All" button - clears all selections
   - Helpful tip about shift-select functionality
   - Live selection count display

3. **Shift-Select Range Selection**
   - Click one checkbox, hold Shift, click another
   - Automatically selects all contacts in between
   - Works in entire checkbox cell area, not just the checkbox itself
   - Tracks last selected index for intuitive behavior

4. **Bulk Actions Toolbar** (Appears When Items Selected)
   - Shows count of selected contacts
   - "Clear Selection" button
   - "Bulk Edit" button - opens bulk edit modal
   - "Delete Selected" button - triggers bulk deletion with confirmation

5. **Bulk Edit Modal**
   - Gender selector with "No Change" option
   - Tag input field (press Enter to add tags)
   - Tag badges with remove buttons
   - Loading state during processing

6. **User Experience**
   - Confirmation dialog for bulk deletion
   - Success/error alerts after operations
   - Automatic list refresh after operations
   - Selection clears after successful operation
   - Large, forgiving click areas for selection

## Error Handling

### Partial Failures

The bulk operations handle partial failures gracefully:

- Each contact is processed individually
- If one contact fails (e.g., archived), others continue processing
- Response indicates which contacts succeeded and which failed
- Frontend can display appropriate messages

### Common Failure Scenarios

1. **Archived Contacts**: Cannot be modified, will appear in `failed` array
2. **Invalid Gender ID**: Validation error before processing
3. **Invalid Contact ID**: Contact not found, added to `failed` array
4. **Authorization**: Contact must belong to user's account

## Testing

### Unit Tests

Three comprehensive test suites were created:

1. **BulkDestroyContactsTest.php**
   - Tests successful bulk deletion
   - Tests partial failure handling
   - Tests validation errors

2. **BulkUpdateContactsGenderTest.php**
   - Tests gender updates
   - Tests setting gender to null
   - Tests partial failure handling

3. **BulkAssignTagsTest.php**
   - Tests tag assignment
   - Tests tag creation and reuse
   - Tests partial failure handling

### Manual Testing

To manually test the feature:

1. Navigate to the contacts list page
2. Select multiple contacts using checkboxes:
   - Click individual checkboxes
   - OR click anywhere in the checkbox cell (entire column is clickable!)
   - Use "Select All on Page" button for quick selection
   - Use Shift+Click to select ranges
3. Test each operation:
   - **Bulk Edit**: Update gender and/or add tags
   - **Delete**: Confirm deletion works
4. Verify:
   - Operations apply to all selected contacts
   - Clicking near checkbox doesn't navigate to contact page
   - Shift-select works in the entire checkbox cell
   - Visual hover feedback on checkbox column
   - List refreshes after operations
   - Error handling for archived contacts

## Future Enhancements

Potential extensions to this feature:

1. **Additional Bulk Operations**
   - Bulk update company/job information
   - Bulk archive/unarchive
   - Bulk star/unstar
   - Bulk move to lifecycle stage

2. **Enhanced Tag Management**
   - Remove tags in bulk
   - Replace tags

3. **Bulk Export**
   - Export selected contacts as VCF
   - Export selected contacts as CSV

4. **Performance Improvements**
   - Queue large bulk operations
   - Progress indicators for long operations
   - Batch processing for very large selections

5. **Advanced Selection**
   - Select all across pages
   - Save selection filters
   - Select by criteria (all with specific tag, etc.)

## API Usage Examples

### Using cURL

```bash
# Bulk delete contacts
curl -X POST https://your-monica-instance.com/api/contacts/bulk/destroy \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contact_ids": [1, 2, 3]
  }'

# Bulk update gender
curl -X POST https://your-monica-instance.com/api/contacts/bulk/gender \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contact_ids": [1, 2, 3],
    "gender_id": 5
  }'

# Bulk assign tags
curl -X POST https://your-monica-instance.com/api/contacts/bulk/tags \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contact_ids": [1, 2, 3],
    "tags": ["friend", "family"]
  }'
```

### Using JavaScript (Axios)

```javascript
// Bulk delete
await axios.post('/api/contacts/bulk/destroy', {
  contact_ids: [1, 2, 3]
});

// Bulk update gender
await axios.post('/api/contacts/bulk/gender', {
  contact_ids: [1, 2, 3],
  gender_id: 5
});

// Bulk assign tags
await axios.post('/api/contacts/bulk/tags', {
  contact_ids: [1, 2, 3],
  tags: ['friend', 'family']
});
```

## Security Considerations

1. **Authorization**: All operations verify that contacts belong to the authenticated user's account
2. **Validation**: Input validation prevents invalid data
3. **Soft Deletes**: Contacts are soft-deleted by default, allowing recovery
4. **Account Isolation**: Cross-account operations are prevented at service level

## Performance Considerations

1. **Processing**: Each contact processed individually within a transaction
2. **Scalability**: For very large selections (100+ contacts), consider:
   - Breaking into smaller batches
   - Using queue system for async processing
   - Adding progress indicators

3. **Database**: Operations use existing indexes on `account_id` and `id`
4. **Frontend**: Uses debounced search and pagination to handle large contact lists

## Compatibility

- **Laravel Version**: 9.x
- **PHP Version**: 8.1+
- **Vue Version**: 2.6
- **Frontend Dependencies**: vue-good-table for selection support

## Migration Notes

No database migrations required - uses existing schema:
- `contacts` table with `gender_id` column
- `tags` table
- `contact_tag` pivot table

## Support

For issues or questions:
1. Check existing GitHub issues
2. Review this documentation
3. Examine unit tests for usage examples
4. Create new issue with detailed reproduction steps
