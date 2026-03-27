<?php

declare(strict_types=1);

namespace App\Services;

class WhatsAppService
{
    private string $apiVersion = 'v20.0';

    public function isConfigured(): bool
    {
        return $this->getAccessToken() !== ''
            && $this->getPhoneNumberId() !== ''
            && $this->getTemplateName() !== ''
            && $this->getTemplateLanguage() !== '';
    }

    public function getTemplateName(): string
    {
        return trim((string)($_ENV['WHATSAPP_TEMPLATE_NAME'] ?? ''));
    }

    public function getTemplateLanguage(): string
    {
        return trim((string)($_ENV['WHATSAPP_TEMPLATE_LANGUAGE'] ?? 'pt_BR'));
    }

    public function getWebhookVerifyToken(): string
    {
        return trim((string)($_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? ''));
    }

    public function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (!str_starts_with($digits, '55') && strlen($digits) >= 10 && strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        if (!preg_match('/^55\d{10,11}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    public function sendDueTemplate(string $toPhone, array $variables): array
    {
        $token = $this->getAccessToken();
        $phoneNumberId = $this->getPhoneNumberId();
        $templateName = $this->getTemplateName();
        $templateLanguage = $this->getTemplateLanguage();

        if ($token === '' || $phoneNumberId === '' || $templateName === '' || $templateLanguage === '') {
            $message = 'Configuração incompleta da WhatsApp Cloud API.';
            $this->log('error', $message, ['phone' => $toPhone]);
            return [
                'success' => false,
                'http_code' => 0,
                'message_id' => null,
                'error' => $message,
                'response' => null,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $templateLanguage],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => (string)($variables['cliente_nome'] ?? '')],
                        ['type' => 'text', 'text' => (string)($variables['veiculo_nome'] ?? '')],
                        ['type' => 'text', 'text' => (string)($variables['placa'] ?? '')],
                        ['type' => 'text', 'text' => (string)($variables['data_vencimento'] ?? '')],
                    ],
                ]],
            ],
        ];

        $url = sprintf('https://graph.facebook.com/%s/%s/messages', $this->apiVersion, $phoneNumberId);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 20,
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = is_string($rawResponse) && $rawResponse !== '' ? json_decode($rawResponse, true) : null;

        if ($curlError !== '') {
            $this->log('error', 'Falha cURL ao enviar WhatsApp.', [
                'phone' => $toPhone,
                'curl_error' => $curlError,
                'payload' => $this->compactPayload($payload),
            ]);

            return [
                'success' => false,
                'http_code' => $httpCode,
                'message_id' => null,
                'error' => $curlError,
                'response' => $decoded,
            ];
        }

        $messageId = $decoded['messages'][0]['id'] ?? null;
        $success = $httpCode >= 200 && $httpCode < 300 && $messageId !== null;

        $this->log($success ? 'info' : 'error', 'Envio WhatsApp Cloud API.', [
            'phone' => $toPhone,
            'http_code' => $httpCode,
            'message_id' => $messageId,
            'payload' => $this->compactPayload($payload),
            'response' => $decoded,
        ]);

        return [
            'success' => $success,
            'http_code' => $httpCode,
            'message_id' => $messageId,
            'error' => $success ? null : ($decoded['error']['message'] ?? 'Erro desconhecido na API'),
            'response' => $decoded,
        ];
    }

    private function compactPayload(array $payload): array
    {
        return [
            'to' => $payload['to'] ?? null,
            'template' => $payload['template']['name'] ?? null,
            'language' => $payload['template']['language']['code'] ?? null,
        ];
    }

    private function getAccessToken(): string
    {
        return trim((string)($_ENV['WHATSAPP_ACCESS_TOKEN'] ?? ''));
    }

    private function getPhoneNumberId(): string
    {
        return trim((string)($_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? ''));
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $logDir = APP_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $line = sprintf("[%s] %s %s %s\n", strtoupper($level), date('Y-m-d H:i:s'), $message, $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : '');
        error_log($line, 3, $logDir . '/whatsapp.log');
    }
}
