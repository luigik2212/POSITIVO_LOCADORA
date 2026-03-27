<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rental;
use App\Models\WhatsAppNotification;

class RentalAlertService
{
    private const ALERT_TYPE = 'due_in_7_days';

    private WhatsAppService $whatsAppService;
    private WhatsAppNotification $notificationModel;

    public function __construct()
    {
        $this->whatsAppService = new WhatsAppService();
        $this->notificationModel = new WhatsAppNotification();
    }

    public function processDueInSevenDays(): array
    {
        $rentals = (new Rental())->listDueInDaysWithoutNotification(7);
        $stats = [
            'checked' => count($rentals),
            'sent' => 0,
            'errors' => 0,
        ];

        foreach ($rentals as $rental) {
            $result = $this->sendForRental($rental, false);
            if ($result['success']) {
                $stats['sent']++;
            } else {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    public function sendManual(int $rentalId): array
    {
        $rental = (new Rental())->find($rentalId);
        if (!$rental) {
            return ['success' => false, 'error' => 'Locação não encontrada.'];
        }

        return $this->sendForRental($rental, true);
    }

    public function sendForRental(array $rental, bool $force): array
    {
        if (!$this->whatsAppService->isConfigured()) {
            $this->whatsAppService->log('error', 'Token, phone_number_id, template ou idioma não configurados.');
            return ['success' => false, 'error' => 'Integração WhatsApp não configurada.'];
        }

        $rentalId = (int)$rental['id'];
        if (!$force && $this->notificationModel->hasSentForAlert($rentalId, self::ALERT_TYPE)) {
            return ['success' => false, 'error' => 'Lembrete já enviado para esta locação.'];
        }

        $normalizedPhone = $this->whatsAppService->normalizePhone((string)($rental['cliente_telefone'] ?? ''));
        if ($normalizedPhone === null) {
            $this->whatsAppService->log('error', 'Telefone inválido para envio.', [
                'rental_id' => $rentalId,
                'telefone' => $rental['cliente_telefone'] ?? null,
            ]);
            return ['success' => false, 'error' => 'Telefone do cliente ausente ou inválido.'];
        }

        $response = $this->whatsAppService->sendDueTemplate($normalizedPhone, [
            'cliente_nome' => $rental['cliente_nome'] ?? '',
            'veiculo_nome' => $rental['veiculo_nome'] ?? '',
            'placa' => $rental['placa'] ?? '',
            'data_vencimento' => date('d/m/Y', strtotime((string)$rental['data_prevista_termino'])),
        ]);

        $status = $response['success'] ? 'queued' : 'failed';

        $this->notificationModel->create([
            'rental_id' => $rentalId,
            'client_id' => (int)$rental['client_id'],
            'alert_type' => self::ALERT_TYPE,
            'phone' => $normalizedPhone,
            'template_name' => $this->whatsAppService->getTemplateName(),
            'template_language' => $this->whatsAppService->getTemplateLanguage(),
            'sent_at' => date('Y-m-d H:i:s'),
            'message_id' => $response['message_id'],
            'delivery_status' => $status,
            'error_message' => $response['error'],
            'payload_summary' => json_encode([
                'cliente' => $rental['cliente_nome'] ?? '',
                'veiculo' => $rental['veiculo_nome'] ?? '',
                'placa' => $rental['placa'] ?? '',
                'vencimento' => $rental['data_prevista_termino'] ?? '',
            ], JSON_UNESCAPED_UNICODE),
            'response_body' => json_encode($response['response'] ?? null, JSON_UNESCAPED_UNICODE),
        ]);

        return [
            'success' => $response['success'],
            'error' => $response['error'] ?? null,
            'status' => $status,
        ];
    }
}
