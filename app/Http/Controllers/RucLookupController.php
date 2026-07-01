<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RucLookupController extends Controller
{
    // ─── Consulta RUC (apiperu.dev) ───────────────────────────────────────────

    public function lookup(Request $request)
    {
        $request->validate([
            'ruc' => ['required', 'digits:11'],
        ], [
            'ruc.required' => 'Ingrese el RUC.',
            'ruc.digits'   => 'El RUC debe tener exactamente 11 dígitos.',
        ]);

        $token = config('services.apiperu.token');

        if (!$token) {
            return response()->json([
                'message' => 'Token no configurado. Agregue APIPERU_TOKEN en .env',
            ], 500);
        }

        try {
            $response = Http::withToken($token)
                ->withoutVerifying()
                ->acceptJson()
                ->post('https://apiperu.dev/api/ruc', [
                    'ruc' => $request->ruc,
                ]);

            $json = $response->json();

            if (!($json['success'] ?? false)) {
                return response()->json([
                    'message' => $json['message'] ?? 'RUC no encontrado.',
                ], 404);
            }

            $data = $json['data'] ?? [];

            return response()->json([
                'ruc'           => $data['ruc']                   ?? $request->ruc,
                'business_name' => $data['nombre_o_razon_social'] ?? '',
                'trade_name'    => $data['nombre_comercial']       ?? '',
                'address'       => $data['direccion_completa']     ?? '',
                'phone'         => $data['telefono']               ?? '',
                'email'         => $data['email_1'] ?? $data['email'] ?? '',
                'estado'        => $data['estado']                 ?? '',
                'condicion'     => $data['condicion']              ?? '',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al conectar: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── Tipo de Cambio del día (apiperu.dev) ─────────────────────────────────
    // URL correcta: tipo_de_cambio (guión bajo)
    // Parámetro:    fecha (YYYY-MM-DD)
    // Solo retorna: USD — EUR debe ingresarse manualmente

    public function exchangeRate(Request $request)
    {
        $request->validate([
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $currency = strtoupper($request->currency);

        // PEN siempre es 1
        if ($currency === 'PEN') {
            return response()->json([
                'currency' => 'PEN',
                'rate'     => 1,
                'sale'     => 1,
                'purchase' => 1,
                'date'     => now()->toDateString(),
            ]);
        }

        // EUR no tiene endpoint en apiperu.dev → el usuario ingresa manual
        if ($currency !== 'USD') {
            return response()->json([
                'message' => "El tipo de cambio para {$currency} no está disponible automáticamente. Ingrésalo manualmente.",
            ], 404);
        }

        $token = config('services.apiperu.token');

        if (!$token) {
            return response()->json([
                'message' => 'Token no configurado.',
            ], 500);
        }

        try {
            $response = Http::withToken($token)
                ->withoutVerifying()
                ->acceptJson()
                ->post('https://apiperu.dev/api/tipo_de_cambio', [
                    'fecha' => now()->format('Y-m-d'),
                ]);

            $json = $response->json();

            if (!($json['success'] ?? false)) {
                return response()->json([
                    'message' => $json['message'] ?? 'No se pudo obtener el tipo de cambio.',
                ], 404);
            }

            $data = $json['data'] ?? [];

            return response()->json([
                'currency'      => 'USD',
                'rate'          => $data['venta']          ?? $data['sale']     ?? 1,
                'sale'          => $data['venta']          ?? $data['sale']     ?? 1,
                'purchase'      => $data['compra']         ?? $data['purchase'] ?? 1,
                'date'          => $data['fecha_busqueda'] ?? $data['date']     ?? now()->toDateString(),
                'date_sunat'    => $data['fecha_sunat']    ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al conectar: ' . $e->getMessage(),
            ], 500);
        }
    }
}