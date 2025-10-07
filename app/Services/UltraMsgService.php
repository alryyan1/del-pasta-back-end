<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UltraMsgService
{
    protected string $baseUrl;
    protected ?string $instanceId;
    protected ?string $token;
    protected string $defaultCountryCode;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.ultramsg.base_url', 'https://api.ultramsg.com');
        $this->instanceId = config('services.ultramsg.instance');
        $this->token = config('services.ultramsg.token');
        $this->defaultCountryCode = (string) config('services.ultramsg.default_country_code', '249');
    }

    public function isConfigured(): bool
    {
        return !empty($this->instanceId) && !empty($this->token) && !empty($this->baseUrl);
    }

    public function getInstanceId(): ?string
    {
        return $this->instanceId;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    // Backwards-compatible simple wrappers returning Response (used in existing controllers)
    public function sendText(string $to, string $body): Response
    {
        $endpoint = "$this->baseUrl/{$this->instanceId}/messages/chat";
        return Http::asForm()->post($endpoint, [
            'token' => $this->token,
            'to' => $to,
            'body' => $body,
        ]);
    }

    public function sendDocument(string $to, string $filename, string $document, ?string $caption = null): Response
    {
        $endpoint = "$this->baseUrl/{$this->instanceId}/messages/document";
        $payload = [
            'token' => $this->token,
            'to' => $to,
            'filename' => $filename,
            'document' => $document,
        ];
        if ($caption !== null && $caption !== '') {
            $payload['caption'] = $caption;
        }
        return Http::asForm()->post($endpoint, $payload);
    }

    // New API returning structured arrays
    public function sendTextMessage(string $to, string $body): array
    {
        if (!$this->isConfigured()) {
            Log::error('UltramsgService: Service not configured (Instance ID or Token missing).');
            return ['success' => false, 'error' => 'Ultramsg service not configured.', 'data' => null];
        }
        if (strlen($body) > 4096) {
            Log::error('UltramsgService: Message too long. Max length is 4096 characters.');
            return ['success' => false, 'error' => 'Message too long. Maximum 4096 characters allowed.', 'data' => null];
        }
        $endpoint = "$this->baseUrl/{$this->instanceId}/messages/chat";
        try {
            $response = Http::asForm()->post($endpoint, [
                'token' => $this->token,
                'to' => $to,
                'body' => $body,
            ]);
            return $this->handleResponse($response, 'Text message');
        } catch (\Exception $e) {
            Log::error('UltramsgService sendTextMessage Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    public function sendDocumentRich(string $to, string $filename, string $document, string $caption = ''): array
    {
        if (!$this->isConfigured()) {
            Log::error('UltramsgService: Service not configured.');
            return ['success' => false, 'error' => 'Ultramsg service not configured.', 'data' => null];
        }
        if (strlen($filename) > 255) {
            Log::error('UltramsgService: Filename too long. Max length is 255 characters.');
            return ['success' => false, 'error' => 'Filename too long. Maximum 255 characters allowed.', 'data' => null];
        }
        if (strlen($caption) > 1024) {
            Log::error('UltramsgService: Caption too long. Max length is 1024 characters.');
            return ['success' => false, 'error' => 'Caption too long. Maximum 1024 characters allowed.', 'data' => null];
        }
        $endpoint = "$this->baseUrl/{$this->instanceId}/messages/document";
        try {
            $response = Http::asForm()->post($endpoint, [
                'token' => $this->token,
                'to' => $to,
                'filename' => $filename,
                'document' => $document,
                'caption' => $caption,
            ]);
            return $this->handleResponse($response, 'Document');
        } catch (\Exception $e) {
            Log::error('UltramsgService sendDocument Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    public function sendDocumentFromFile(string $to, string $filePath, string $caption = ''): array
    {
        if (!file_exists($filePath)) {
            Log::error("UltramsgService: File not found: {$filePath}");
            return ['success' => false, 'error' => 'File not found.', 'data' => null];
        }
        $filename = basename($filePath);
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            Log::error("UltramsgService: Could not read file: {$filePath}");
            return ['success' => false, 'error' => 'Could not read file.', 'data' => null];
        }
        $base64Content = base64_encode($fileContent);
        return $this->sendDocumentRich($to, $filename, $base64Content, $caption);
    }

    public function sendDocumentFromUrl(string $to, string $documentUrl, string $filename, string $caption = ''): array
    {
        return $this->sendDocumentRich($to, $filename, $documentUrl, $caption);
    }

    protected function handleResponse(Response $response, string $actionDescription): array
    {
        $responseData = $response->json();
        if ($response->successful() && isset($responseData['sent']) && $responseData['sent'] === 'true') {
            Log::info("UltramsgService: {$actionDescription} sent successfully.", [
                'response' => $responseData,
                'message_id' => $responseData['id'] ?? null,
            ]);
            return [
                'success' => true,
                'data' => $responseData,
                'message_id' => $responseData['id'] ?? null,
            ];
        }
        $errorMessage = "Failed to send {$actionDescription}.";
        if (isset($responseData['message'])) {
            $errorMessage .= ' Error: ' . $responseData['message'];
        } elseif (!$response->successful()) {
            $errorMessage .= ' HTTP Status: ' . $response->status();
        }
        Log::error("UltramsgService: {$errorMessage}", [
            'response' => $responseData,
            'status_code' => $response->status(),
        ]);
        return ['success' => false, 'error' => $errorMessage, 'data' => $responseData];
    }

    public static function formatPhoneNumber(string $phoneNumber, string $defaultCountryCode = '968'): ?string
    {
        if (empty(trim($phoneNumber))) {
            return null;
        }
        $cleanedNumber = preg_replace('/[^\d]/', '', $phoneNumber);
        if (str_starts_with($cleanedNumber, '0')) {
            $cleanedNumber = substr($cleanedNumber, 1);
        }
        if (!str_starts_with($cleanedNumber, $defaultCountryCode)) {
            $cleanedNumber = $defaultCountryCode . $cleanedNumber;
        }
        if (strlen($cleanedNumber) < 10 || strlen($cleanedNumber) > 15) {
            Log::warning("UltramsgService: Potentially invalid phone number format: {$phoneNumber} -> {$cleanedNumber}");
        }
        return '+' . $cleanedNumber;
    }

    public function getInstanceStatus(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Ultramsg service not configured.', 'data' => null];
        }
        $endpoint = "$this->baseUrl/{$this->instanceId}/instance/status";
        try {
            $response = Http::get($endpoint, ['token' => $this->token]);
            $responseData = $response->json();
            if ($response->successful()) {
                $status = $responseData['status']['accountStatus']['status'] ?? null;
                $substatus = $responseData['status']['accountStatus']['substatus'] ?? null;
                Log::info('UltramsgService: Instance status retrieved successfully.', [
                    'status' => $status,
                    'substatus' => $substatus,
                    'response' => $responseData,
                ]);
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status' => $status,
                    'substatus' => $substatus,
                ];
            }
            $errorMessage = 'Failed to get instance status.';
            if (isset($responseData['message'])) {
                $errorMessage .= ' Error: ' . $responseData['message'];
            } else {
                $errorMessage .= ' HTTP Status: ' . $response->status();
            }
            Log::error('UltramsgService: ' . $errorMessage, [
                'response' => $responseData,
                'status_code' => $response->status(),
            ]);
            return ['success' => false, 'error' => $errorMessage, 'data' => $responseData];
        } catch (\Exception $e) {
            Log::error('UltramsgService getInstanceStatus Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    public function isInstanceConnected(): bool
    {
        $statusResult = $this->getInstanceStatus();
        if (!$statusResult['success']) {
            return false;
        }
        $status = $statusResult['status'] ?? null;
        $substatus = $statusResult['substatus'] ?? null;
        return $status === 'authenticated' && $substatus === 'connected';
    }
}



