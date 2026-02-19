<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SupportController extends BaseController
{
    /**
     * Get WhatsApp support configuration for mobile app
     * GET /api/v1/support/whatsapp
     */
    public function whatsapp(): JsonResponse
    {
        $enabled = (bool) Setting::get('whatsapp_enabled', false);
        
        if (!$enabled) {
            return $this->success([
                'enabled' => false,
            ], 'WhatsApp support is disabled');
        }

        return $this->success([
            'enabled' => true,
            'phone_number' => Setting::get('whatsapp_phone_number'),
            'agent_name' => Setting::get('whatsapp_agent_name', 'Support'),
            'agent_title' => Setting::get('whatsapp_agent_title', 'Typically replies instantly'),
            'greeting_message' => Setting::get('whatsapp_greeting_message', 'Hello! How can we help you?'),
            'default_message' => Setting::get('whatsapp_default_message', ''),
            'whatsapp_url' => $this->buildWhatsAppUrl(),
        ]);
    }

    /**
     * Build WhatsApp click-to-chat URL
     */
    protected function buildWhatsAppUrl(): ?string
    {
        $phone = Setting::get('whatsapp_phone_number');
        $message = Setting::get('whatsapp_default_message', '');

        if (!$phone) {
            return null;
        }

        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $url = "https://wa.me/{$phone}";
        
        if ($message) {
            $url .= "?text=" . urlencode($message);
        }

        return $url;
    }
}
