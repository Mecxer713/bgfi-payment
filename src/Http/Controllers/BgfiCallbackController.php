<?php

namespace Mecxer713\BgfiPayment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mecxer713\BgfiPayment\Events\BgfiCallbackReceived;

class BgfiCallbackController
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        event(new BgfiCallbackReceived($payload));

        return response()->json([
            'status'      => 'ok',
            'received_at' => now()->toIso8601String(),
        ]);
    }
}
