<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            $user->loadMissing('unitSppg');
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'unitSppg' => $user?->unitSppg,
            'periodes' => fn () => $user ? \App\Models\Periode::orderBy('nomor_periode', 'asc')->get()->map(function ($p) {
                return [
                    'id'              => $p->id,
                    'nomor_periode'   => $p->nomor_periode,
                    'tanggal_mulai'   => $p->tanggal_mulai ? $p->tanggal_mulai->format('Y-m-d') : null,
                    'tanggal_selesai' => $p->tanggal_selesai ? $p->tanggal_selesai->format('Y-m-d') : null,
                    'status'          => $p->status,
                ];
            }) : [],
        ];
    }
}
