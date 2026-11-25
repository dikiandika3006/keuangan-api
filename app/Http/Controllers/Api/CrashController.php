<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrashReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Menerima laporan crash dari aplikasi. Token bersifat opsional (crash bisa terjadi sebelum login). */
class CrashController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reports' => ['required', 'array', 'max:10'],
            'reports.*.message' => ['required', 'string', 'max:2000'],
            'reports.*.stack' => ['required', 'string', 'max:60000'],
            'reports.*.app_version' => ['nullable', 'string', 'max:20'],
            'reports.*.device' => ['nullable', 'string', 'max:100'],
            'reports.*.android' => ['nullable', 'string', 'max:20'],
            'reports.*.occurred_at' => ['nullable', 'integer', 'min:0'],
        ]);
        $userId = $request->user('sanctum')?->id;

        foreach ($data['reports'] as $r) {
            CrashReport::create([
                'user_id' => $userId,
                'app_version' => $r['app_version'] ?? null,
                'device' => $r['device'] ?? null,
                'android' => $r['android'] ?? null,
                'message' => mb_substr($r['message'], 0, 500),
                'stack' => $r['stack'],
                'occurred_at' => isset($r['occurred_at']) ? Carbon::createFromTimestampMs($r['occurred_at']) : null,
            ]);
        }

        return response()->json(['ok' => true, 'saved' => count($data['reports'])]);
    }
}
