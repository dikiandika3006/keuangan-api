<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sinkronisasi offline-first.
 * Klien mengirim perubahan lokal (push) lalu menerima perubahan server sejak cursor (pull).
 * Konflik diselesaikan last-write-wins berdasarkan updated_at dari perangkat.
 */
class SyncController extends Controller
{
    public const ENTITIES = ['account', 'category', 'txn', 'recurring', 'debt', 'bill_occ', 'goal'];

    private const PULL_LIMIT = 1000;

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cursor' => ['nullable', 'integer', 'min:0'],
            'changes' => ['present', 'array', 'max:2000'],
            'changes.*.entity' => ['required', 'string', 'in:'.implode(',', self::ENTITIES)],
            'changes.*.id' => ['required', 'string', 'max:64'],
            'changes.*.updated_at' => ['required', 'integer', 'min:0'],
            'changes.*.deleted_at' => ['nullable', 'integer', 'min:0'],
            'changes.*.data' => ['required', 'array'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $accepted = 0;

        if (! empty($data['changes'])) {
            DB::transaction(function () use ($user, $data, &$accepted) {
                $locked = User::whereKey($user->id)->lockForUpdate()->first();
                $seq = (int) $locked->sync_seq;

                foreach ($data['changes'] as $c) {
                    $existing = SyncRecord::where('user_id', $user->id)
                        ->where('entity', $c['entity'])->where('record_id', $c['id'])->first();

                    if ($existing && $existing->client_updated_at > $c['updated_at']) {
                        continue; // versi di server lebih baru
                    }

                    $seq++;
                    SyncRecord::updateOrCreate(
                        ['user_id' => $user->id, 'entity' => $c['entity'], 'record_id' => $c['id']],
                        [
                            'data' => $c['data'],
                            'client_updated_at' => $c['updated_at'],
                            'deleted_at' => $c['deleted_at'] ?? null,
                            'seq' => $seq,
                        ],
                    );
                    $accepted++;
                }

                $locked->forceFill(['sync_seq' => $seq])->save();
            });
        }

        $cursor = (int) ($data['cursor'] ?? 0);
        $rows = SyncRecord::where('user_id', $user->id)
            ->where('seq', '>', $cursor)
            ->orderBy('seq')
            ->limit(self::PULL_LIMIT)
            ->get();

        return response()->json([
            'accepted' => $accepted,
            'cursor' => $rows->isEmpty() ? $cursor : (int) $rows->last()->seq,
            'has_more' => $rows->count() === self::PULL_LIMIT,
            'changes' => $rows->map(fn (SyncRecord $r) => [
                'entity' => $r->entity,
                'id' => $r->record_id,
                'updated_at' => (int) $r->client_updated_at,
                'deleted_at' => $r->deleted_at === null ? null : (int) $r->deleted_at,
                'data' => $r->data,
            ])->values(),
            'user' => $user->fresh()->toProfile(),
        ]);
    }
}
