<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Modules\Payments\Actions\ProcessPaymobWebhookAction;
use App\Modules\Payments\Services\PaymobHmacVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymobWebhookController
{
    public function __invoke(
        Request $request,
        PaymobHmacVerifier $verifier,
        ProcessPaymobWebhookAction $action,
    ): JsonResponse {
        $payload = $request->all();
        $signature = $request->query('hmac') ?? $request->input('hmac');

        if (! $verifier->verify($payload, is_string($signature) ? $signature : null)) {
            return response()->json(['message' => 'Invalid callback signature.'], 401);
        }

        $result = $action->execute($payload, $request->getContent());

        return response()->json(['received' => true, 'status' => $result['status']], 200);
    }
}
