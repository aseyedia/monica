<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NtfyChannel
{
    public function send($notifiable, Notification $notification): void
    {
        $url   = config('services.ntfy.url');
        $topic = config('services.ntfy.topic');
        $token = config('services.ntfy.token');

        if (! $url || ! $topic) {
            return;
        }

        ['title' => $title, 'body' => $body] = $notification->toNtfy($notifiable);

        $request = Http::withHeaders(['Title' => $title, 'Priority' => 'high']);
        if ($token) {
            $request = $request->withToken($token);
        }

        $response = $request->post("{$url}/{$topic}", $body);

        if (! $response->successful()) {
            Log::error('ntfy notification failed', ['status' => $response->status(), 'body' => $response->body()]);
        }
    }
}
