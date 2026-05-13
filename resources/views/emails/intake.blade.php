<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: monospace; background: #f0f0f0; color: #333; margin: 0; padding: 20px; }
  .wrap { max-width: 520px; margin: 0 auto; background: #fff; padding: 24px; border: 1px solid #ccc; }
  h2 { margin: 0 0 16px; border-bottom: 1px solid #333; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  td { padding: 4px 0; vertical-align: top; }
  td:first-child { width: 100px; opacity: 0.6; }
  .note { background: #f0f0f0; padding: 10px; font-size: 0.9em; margin-bottom: 16px; }
  .meta { font-size: 0.8em; opacity: 0.6; margin-bottom: 16px; }
  a { color: #0000EE; }
</style>
</head>
<body>
<div class="wrap">
  <h2>new contact: {{ $submission->name }}</h2>
  <table>
    <tr><td>name</td><td>{{ $submission->name }}</td></tr>
    <tr><td>phone</td><td>{{ $submission->phone_raw }}</td></tr>
    @if ($submission->email)
    <tr><td>email</td><td>{{ $submission->email }}</td></tr>
    @endif
    @if ($submission->company)
    <tr><td>company</td><td>{{ $submission->company }}</td></tr>
    @endif
  </table>
  @if ($submission->note)
  <div class="note">{{ $submission->note }}</div>
  @endif
  <div class="meta">
    submitted {{ $submission->created_at->diffForHumans() }} from {{ $submission->ip_address }}
  </div>
  <a href="{{ url('/settings/intake') }}">→ review in monica</a>
</div>
</body>
</html>
