<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UltraMsgService;

class UltraMsgController extends Controller
{
    /**
     * Send a WhatsApp text message via UltraMsg
     * Expected request: { to: "+249991961111", body: "message text" }
     */
    public function sendText(Request $request, UltraMsgService $ultra)
    {
        $validated = $request->validate([
            'to' => ['required','string'],
            'body' => ['required','string'],
        ]);

        if (!$ultra->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'UltraMsg not configured. Set ULTRAMSG_TOKEN and ULTRAMSG_INSTANCE in .env',
            ], 500);
        }
        $response = $ultra->sendText($validated['to'], $validated['body']);

        if ($response->failed()) {
            return response()->json([
                'status' => false,
                'message' => 'UltraMsg request failed',
                'error' => $response->json(),
            ], $response->status() ?: 500);
        }

        return response()->json($response->json());
    }

    /**
     * Send a WhatsApp document via UltraMsg
     * Expected request: { to, filename, document (url), caption? }
     */
    public function sendDocument(Request $request, UltraMsgService $ultra)
    {
        $validated = $request->validate([
            'to' => ['required','string'],
            'filename' => ['required','string'],
            'document' => ['required','url'],
            'caption' => ['nullable','string'],
        ]);

        if (!$ultra->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'UltraMsg not configured. Set ULTRAMSG_TOKEN and ULTRAMSG_INSTANCE in .env',
            ], 500);
        }
        $response = $ultra->sendDocument(
            $validated['to'],
            $validated['filename'],
            $validated['document'],
            $validated['caption'] ?? null
        );

        if ($response->failed()) {
            return response()->json([
                'status' => false,
                'message' => 'UltraMsg request failed',
                'error' => $response->json(),
            ], $response->status() ?: 500);
        }

        return response()->json($response->json());
    }

    /**
     * Temporary test endpoint to send a message to a fixed number.
     */
    public function testSend(UltraMsgService $ultra)
    {
        if (!$ultra->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'UltraMsg not configured. Set ULTRAMSG_TOKEN and ULTRAMSG_INSTANCE in .env',
            ], 500);
        }
        $to = '+249991961111';
        $body = 'Test message from Del Pasta';
        $response = $ultra->sendText($to, $body);
        return response()->json([
            'status' => $response->successful(),
            'ultramsg' => $response->json(),
        ], $response->status() ?: 200);
    }
}


