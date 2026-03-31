<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\BulkDestroyContacts;
use App\Services\Contact\Contact\BulkUpdateContactsGender;
use App\Services\Contact\Contact\BulkAssignTags;
use App\Http\Resources\Contact\Contact as ContactResource;

class ApiBulkContactController extends ApiController
{
    /**
     * Bulk delete contacts.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $result = app(BulkDestroyContacts::class)->handle([
                'account_id' => auth()->user()->account_id,
                'contact_ids' => $request->input('contact_ids'),
                'force_delete' => $request->input('force_delete', false),
            ]);

            return response()->json([
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (\Exception $e) {
            return $this->respondInvalidQuery();
        }
    }

    /**
     * Bulk update contacts gender.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function bulkUpdateGender(Request $request): JsonResponse
    {
        try {
            $result = app(BulkUpdateContactsGender::class)->handle([
                'account_id' => auth()->user()->account_id,
                'contact_ids' => $request->input('contact_ids'),
                'gender_id' => $request->input('gender_id'),
            ]);

            return response()->json([
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (\Exception $e) {
            return $this->respondInvalidQuery();
        }
    }

    /**
     * Bulk assign tags to contacts.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function bulkAssignTags(Request $request): JsonResponse
    {
        try {
            $result = app(BulkAssignTags::class)->handle([
                'account_id' => auth()->user()->account_id,
                'contact_ids' => $request->input('contact_ids'),
                'tags' => $request->input('tags'),
            ]);

            return response()->json([
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (\Exception $e) {
            return $this->respondInvalidQuery();
        }
    }
}
