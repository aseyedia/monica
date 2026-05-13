# Plan: Public Contact Intake Form

**URL:** `people.artaseyedian.com/cnct`
**Status:** Planned — not yet implemented
**Branch:** implement off `4.x`

---

## What It Does

A public page Arta can share (via Dot NFC card, QR code, link) where someone submits their contact info and it lands directly in Monica. No login required. Rate limited. Phone number is the deduplication primary key.

---

## User Flow

```
1. [Upload contact card]   ← optional .vcf, 1 contact max, pre-fills fields below

2. Name*
   Phone*
   Email                  ← optional, visible in main section (not hidden)

   [+ More ▼]             ← expandable, collapsed by default
     Company
     Note to Arta

3. [Submit]
      ↓
   Confirmation: "Thanks, [name]. Arta will be in touch."
```

---

## Architecture

### Routes (`routes/web.php`)

```php
// Public — no auth middleware
Route::get('/cnct', [ContactIntakeController::class, 'show']);
Route::post('/cnct', [ContactIntakeController::class, 'submit'])
    ->middleware('throttle:intake');

// Auth-gated — inside existing authenticated route group
Route::prefix('settings/intake')->group(function () {
    Route::get('/', [IntakeSettingsController::class, 'index']);
    Route::post('{submission}/approve', [IntakeSettingsController::class, 'approve']);
    Route::post('{submission}/reject', [IntakeSettingsController::class, 'reject']);
});
```

### Rate Limiting (`app/Providers/RouteServiceProvider.php`)

```php
RateLimiter::for('intake', function (Request $request) {
    return Limit::perHour(5)->by($request->ip());
});
```

---

## New Files

| File | Purpose |
|------|---------|
| `app/Http/Controllers/ContactIntakeController.php` | Public form display + submit handler |
| `app/Http/Controllers/Settings/IntakeSettingsController.php` | Staging queue: list, approve, reject |
| `app/Models/ContactIntakeSubmission.php` | Eloquent model for staged submissions |
| `app/Services/ContactIntake/ParseVCard.php` | `.vcf` → field array; rejects >1 VCARD |
| `app/Services/ContactIntake/ProcessIntake.php` | Dup check, create Monica contact or stage |
| `app/Mail/NewContactIntake.php` | Mailable sent to Arta on new submission |
| `database/migrations/..._create_contact_intake_submissions_table.php` | Schema below |
| `resources/views/layouts/intake.blade.php` | Minimal shell — loads intake.css only, no Monica chrome |
| `resources/views/intake/form.blade.php` | The form |
| `resources/views/intake/thanks.blade.php` | Confirmation page |
| `resources/views/settings/intake/index.blade.php` | Staging queue UI in Monica settings |
| `resources/views/emails/intake.blade.php` | Email template |
| `public/css/intake.css` | All form styles, scoped to `.intake-form {}` |

---

## Database Schema

```sql
contact_intake_submissions
  id                  bigint unsigned PK
  name                varchar(255)
  phone_raw           varchar(50)          -- as submitted
  phone_normalized    varchar(20)          -- digits only, used for dedup
  email               varchar(255) NULL
  company             varchar(255) NULL
  note                text NULL
  raw_vcf             text NULL            -- original VCF content if uploaded
  ip_address          varchar(45)          -- IPv4 or IPv6
  status              enum(pending, approved, discarded)  DEFAULT pending
  created_at / updated_at
```

---

## Key Logic

### VCard Upload

- Accept `.vcf` only, single file
- Parse with `Sabre\VObject\Reader` (already in vendor)
- Count `VCARD` components — if >1, validation error: *"Please upload a single contact card"*
- Extract `FN`, `TEL`, `EMAIL`, `ORG` → pre-fill form fields
- User reviews and edits before submitting

### Phone Normalization

```php
// Strip everything except digits
$normalized = preg_replace('/\D/', '', $phone);
// Strip leading country code 1 for US numbers
if (strlen($normalized) === 11 && str_starts_with($normalized, '1')) {
    $normalized = substr($normalized, 1);
}
```

### Duplicate Check (on submit)

1. Normalize submitted phone
2. Query `contact_fields` where `data = $normalized` and type = phone
3. **Match found** → save submission as `discarded`, return confirmation page anyway (don't reveal to submitter)
4. **No match** → save as `pending`, send email to Arta

### Email Notification

- **To:** `arta.seyedian@gmail.com`
- **Subject:** `New contact: {name}`
- **Body:** name, phone, email, company, note, timestamp, IP address, link to `/settings/intake`
- Sent only on new `pending` submissions (not discards)

### Staging Queue (`/settings/intake`)

- Lists all `pending` submissions, newest first
- **Approve** → calls Monica's `CreateContact` + `CreateContactField` services, sets status=`approved`
- **Reject** → sets status=`discarded`
- No auto-approve; everything goes through manual review

---

## CSS Carveout

`resources/views/layouts/intake.blade.php` is a standalone layout — no Monica nav, no Monica JS bundles. Loads only:

```html
<link rel="stylesheet" href="{{ asset('css/intake.css') }}">
```

All form styles live in `public/css/intake.css` and are scoped under `.intake-form {}`. Reskin by swapping this one file. Monica internals are never touched.

---

## Out of Scope (this iteration)

- `share.*` subdomain (just use `people.*`)
- CAPTCHA (rate limiting is the spam defense)
- Email-based deduplication (phone is the primary key)
- Auto-approve on no duplicate
- One-time / expiring links
- Personal website styling (CSS carveout makes this easy to add later)

---

## Dot Card

Reprogram via Dot app → edit card destination URL → `people.artaseyedian.com/cnct`. Fully reprogrammable anytime.

---

## Implementation Order

1. Migration + model (`ContactIntakeSubmission`)
2. `ParseVCard` service
3. `ProcessIntake` service (dup check + stage)
4. `NewContactIntake` mailable
5. `ContactIntakeController` (GET + POST) + rate limiter registration
6. Views: `layouts/intake`, `intake/form`, `intake/thanks`
7. `public/css/intake.css` (basic functional styles)
8. `IntakeSettingsController` + staging queue view
9. Wire routes, test end-to-end
