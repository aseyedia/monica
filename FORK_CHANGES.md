# Fork Customizations

This is a personal fork of [Monica](https://github.com/monicahq/monica) (branch `4.x`). Changes from upstream are documented here.

Deployed at: `https://people.artaseyedian.com`  
Self-hosted on a Beelink mini PC behind a Cloudflare Tunnel → nginx reverse proxy.

---

## 1. ntfy Push Notifications

Monica's built-in notification channels are email and SMS (Twilio). This fork replaces Twilio with [ntfy](https://ntfy.sh/), a self-hosted push notification server.

**New files:**
- `app/Channels/NtfyChannel.php` — custom Laravel notification channel; POSTs to ntfy REST API with bearer token auth and `Priority: high`
- `config/services.php` — added `ntfy` block reading `NTFY_URL`, `NTFY_TOPIC`, `NTFY_TOKEN` env vars

**Modified files:**
- `app/Notifications/UserReminded.php` — replaced `TwilioChannel` with `NtfyChannel`; added `toNtfy()` method that formats the reminder as a push notification

**Required env vars:**
```
NTFY_URL=https://your-ntfy-instance.com
NTFY_TOPIC=monica-reminders
NTFY_TOKEN=your_ntfy_bearer_token
```

**How it works:** Monica's `send:reminders` scheduler fires → dispatches `NotifyUserAboutReminder` job → calls `UserReminded::via()` → sends via both `mail` and `NtfyChannel`. The ntfy server forwards to iOS via Apple's APNs infrastructure (requires `upstream-base-url: "https://ntfy.sh"` in ntfy config for iOS push to work on a self-hosted instance).

---

## 2. Reminder Reliability Fix

**Modified:** `app/Models/User/User.php` — `isTheRightTimeToBeReminded()`

The original implementation only sent reminders when `$date->isSameDay($now)`, which silently dropped overdue reminders (e.g., after a scheduler outage or server downtime). The fix sends any reminder whose date is in the past or today:

```php
public function isTheRightTimeToBeReminded($date)
{
    if (is_null($date)) return false;
    $now = now($this->timezone);
    if ($date->startOfDay()->lt($now->copy()->startOfDay())) return true;  // overdue → send now
    if ($date->isSameDay($now)) {
        return $now->isSameHour($this->account->default_time_reminder_is_sent);
    }
    return false;
}
```

---

## 3. CalDAV Reminders Export

Monica's CalDAV previously exported:
- Birthdays → `VEVENT` (Calendar app)
- Tasks → `VTODO` (Reminders app)

Reminders (the time-based notification system) were not exported at all. This fork adds a third CalDAV calendar that exports active, non-birthday reminders as `VTODO` items with `DUE` dates so they appear in the iOS Reminders app alongside tasks.

**New files:**
- `app/Services/VCalendar/ExportReminder.php` — serializes a `Reminder` model to iCalendar VTODO format; uses `calculateNextExpectedDateOnTimezone()` for the due date
- `app/Http/Controllers/DAV/Backend/CalDAV/CalDAVReminders.php` — CalDAV backend class; exports all active non-birthday reminders; read-only (iOS cannot modify Monica reminders via CalDAV)

**Modified files:**
- `app/Http/Controllers/DAV/Backend/CalDAV/CalDAVBackend.php` — added `CalDAVReminders` to `getBackends()`

**Behavior:**
- Birthday reminders are excluded (they're already in the Birthdays calendar as VEVENT)
- Inactive reminders are excluded
- Recurring reminders show the next occurrence date as the DUE date
- Read-only: `updateOrCreateCalendarObject` and `deleteCalendarObject` are no-ops

---

## 4. cache_locks Table Migration

**New file:** `database/migrations/2026_04_27_000000_create_cache_locks_table.php`

Monica's scheduler (`send:reminders`) uses Laravel's cache locking to prevent duplicate sends. The `cache_locks` table was missing from the migration history, causing the scheduler to crash silently. This migration creates it if absent:

```php
Schema::create('cache_locks', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->string('owner');
    $table->integer('expiration');
});
```

---

## 5. Dockerfile: config/ Directory

**Modified:** `Dockerfile`

The upstream Monica Dockerfile did not copy `config/` from the build context. Since `config/services.php` needed to be customized (for ntfy), the Dockerfile was updated to include:

```dockerfile
COPY --chown=www-data:www-data config/ /var/www/html/config/
```

---

## 6. Trusted Proxies Fix

**Modified:** `docker-compose.yml` (Monica service environment)

Monica uses `APP_TRUSTED_PROXIES` env var (via `config('app.trust_proxies')`) to determine whether to trust `X-Forwarded-Proto` headers from the upstream proxy. Without this, Monica generates `http://` redirect URLs even when behind HTTPS, which causes iOS CalDAV to fail with an HTTPS→HTTP downgrade error.

```yaml
environment:
  - APP_TRUSTED_PROXIES=*
```

---

## 7. Text Exchange Logging

Extends the "Phone calls" feature to also log SMS/text exchanges. Previously the calls section only modeled voice calls.

**Modified files:**
- `database/migrations/2026_05_12_000000_add_is_text_to_calls.php` — adds `is_text` boolean column (default `false`) to `calls` table
- `app/Models/Contact/Call.php` — added `is_text` to `$casts`
- `app/Services/Contact/Call/CreateCall.php` — added `is_text` to validation rules
- `app/Services/Contact/Call/UpdateCall.php` — added `is_text` to validation rules and `update()` call
- `app/Http/Controllers/Contacts/CallsController.php` — passes `is_text` from request in both `store` and `update`
- `app/Http/Resources/Call/Call.php` — added `is_text` to JSON output
- `resources/js/components/people/calls/PhoneCallList.vue` — type toggle (☎️ Phone call / 💬 Text exchange); labels update dynamically ("Who called?" ↔ "Who texted first?", date label, emotion label); list entries show ☎️ or 💬 icon with correct verb
- `resources/lang/en/people.php` — section renamed "Calls & texts"; button renamed "Log call or text"; strings updated throughout

**Behavior:**
- Existing calls all default to `is_text = false` — no data loss
- Type toggle appears at the top of the log form; selecting "Text exchange" updates all labels in the form and the list display
- The `is_text` field is exposed in the API JSON for MCP consumers

---

## 8. Mobile Refresh Button

Monica is used as a PWA (added to iOS home screen). Safari's native pull-to-refresh and browser controls are hidden in standalone mode, leaving no way to refresh.

**Modified:** `resources/views/layouts/skeleton.blade.php`

Adds a fixed purple circle button (↻) pinned to bottom-right, visible only on screens ≤768px via CSS media query. Calls `location.reload()` on tap. Hidden on desktop.

---

## Deployment

```bash
# Build and deploy
docker compose build monica
docker compose up -d monica

# After deploying, run migrations
docker exec monica php artisan migrate --force
```

The Docker image rebuilds JS assets at build time using yarn. No pre-built assets are committed to this repo.
