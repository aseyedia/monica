<?php

namespace App\Models\Contact;

use App\Models\User\User;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $account_id
 * @property int $contact_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $planned_date
 * @property int $days_overdue
 */
class StayInTouchOverdueOutbox extends Model
{
    protected $table = 'stay_in_touch_overdue_outbox';

    protected $guarded = ['id'];

    protected $dates = ['planned_date'];

    public static array $intervals = [3, 7, 14, 30];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
