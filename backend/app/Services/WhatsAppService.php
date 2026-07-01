<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp helpers:
 *   - click-to-chat deep links (used on the frontend / mail),
 *   - optional WhatsApp Cloud API send for inquiry alerts to agents.
 */
class WhatsAppService
{
    /** Build a wa.me click-to-chat link. */
    public function link(?string $phone, string $message = ''): string
    {
        $phone = preg_replace('/\D+/', '', $phone ?: (string) config('services.whatsapp.phone'));
        $q = $message ? '?text=' . rawurlencode($message) : '';

        return "https://wa.me/{$phone}{$q}";
    }

    /**
     * Send a text message via the WhatsApp Cloud API. No-ops (returns false)
     * if credentials aren't configured, so it's safe to call unconditionally.
     */
    public function send(string $toPhone, string $message): bool
    {
        $token = config('services.whatsapp.cloud_token');
        $phoneId = config('services.whatsapp.cloud_phone_id');

        if (! $token || ! $phoneId) {
            return false;
        }

        $version = config('services.whatsapp.graph_version', 'v20.0');
        $to = preg_replace('/\D+/', '', $toPhone);

        try {
            $res = Http::withToken($token)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['body' => $message],
                ]);

            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('WhatsApp send failed: ' . $e->getMessage());
            return false;
        }
    }
}
