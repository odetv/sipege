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

        $unitSppg = $user?->getCachedUnitSppg();
        $unitId = $unitSppg?->id;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'isSuperAdmin' => (bool) $user?->isSuperAdmin(),
                'isSubscriptionActive' => (bool) $user?->isSubscriptionActive(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'initialization_success' => fn () => $request->session()->get('initialization_success'),
            ],
            'unitSppg' => $unitSppg,
            'periodes' => fn () => $user ? \App\Models\Periode::getUntukDropdown($unitId)->map(function ($p) {
                return [
                    'id'                => $p->id,
                    'nomor_periode'     => $p->nomor_periode,
                    'tanggal_mulai'     => $p->tanggal_mulai ? $p->tanggal_mulai->format('Y-m-d') : null,
                    'tanggal_selesai'   => $p->tanggal_selesai ? $p->tanggal_selesai->format('Y-m-d') : null,
                    'status'            => $p->status,
                    'is_penyesuaian'    => (bool) $p->is_penyesuaian,
                    'tampilkan_di_list' => (bool) $p->tampilkan_di_list,
                ];
            }) : [],
            'periodeAlert' => fn () => $user ? $this->getPeriodeExpirationAlert($unitId) : null,
        ];
    }

    /**
     * Cek apakah masa periode saat ini sudah mau habis (H-1 / H-0 / telah lewat)
     * dan pengguna BELUM membuat periode berikutnya (akan datang).
     *
     * @return array|null
     */
    protected function getPeriodeExpirationAlert(?int $unitId = null): ?array
    {
        if (!$unitId) {
            return null;
        }

        $today = \Illuminate\Support\Carbon::today();
        $query = \App\Models\Periode::where('unit_sppg_id', $unitId)->orderBy('nomor_periode', 'asc');
        $periodes = $query->get();

        if ($periodes->isEmpty()) {
            return null;
        }

        // Cari periode aktif (hari ini dalam rentang tanggal)
        $activePeriode = $periodes->first(function ($p) use ($today) {
            return $today->between($p->tanggal_mulai, $p->tanggal_selesai);
        });

        // Jika tidak ada yang aktif, gunakan periode terakhir yang sudah selesai
        $targetPeriode = $activePeriode ?: $periodes->last();

        if (!$targetPeriode) {
            return null;
        }

        // Cek apakah SUDAH ADA periode yang akan datang:
        // Periode yang tanggal mulainya setelah tanggal_selesai periode target ATAU setelah hari ini
        $hasUpcoming = \App\Models\Periode::where(function ($query) use ($targetPeriode, $today) {
            $query->where('tanggal_mulai', '>', $targetPeriode->tanggal_selesai)
                  ->orWhere('tanggal_mulai', '>', $today);
        })->where('id', '!=', $targetPeriode->id)->exists();

        // KONDISI UTAMA: Muncul HANYA jika pengguna BELUM membuatkan periode yang akan datang
        if ($hasUpcoming) {
            return null;
        }

        // Hitung sisa hari dari hari ini ke tanggal_selesai periode target:
        // Misal selesai 2026-11-01:
        // - Hari ini 2026-10-31: diff = 1 (H-1)
        // - Hari ini 2026-11-01: diff = 0 (H-0 / hari terakhir)
        // - Hari ini 2026-11-02: diff = -1 (sudah berakhir 1 hari yang lalu)
        if ($today->lte($targetPeriode->tanggal_selesai)) {
            $daysRemaining = (int) $today->diffInDays($targetPeriode->tanggal_selesai);
        } else {
            $daysRemaining = -((int) $targetPeriode->tanggal_selesai->diffInDays($today));
        }

        // KONDISI WAKTU: Mulai saat ketika masa periode sudah mau habis (h-1)
        // Jika sisa hari > 1 (misal H-2, H-3, dst), jangan munculkan
        if ($daysRemaining > 1) {
            return null;
        }

        $nextNomor = (\App\Models\Periode::max('nomor_periode') ?? 0) + 1;

        $tglMulaiIndo = $targetPeriode->tanggal_mulai
            ? $targetPeriode->tanggal_mulai->locale('id')->translatedFormat('d F Y')
            : '-';
        $tglSelesaiIndo = $targetPeriode->tanggal_selesai
            ? $targetPeriode->tanggal_selesai->locale('id')->translatedFormat('d F Y')
            : '-';

        return [
            'should_show'           => true,
            'periode_id'            => $targetPeriode->id,
            'nomor_periode'         => $targetPeriode->nomor_periode,
            'tanggal_mulai'         => $targetPeriode->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai'       => $targetPeriode->tanggal_selesai?->format('Y-m-d'),
            'tanggal_mulai_indo'    => $tglMulaiIndo,
            'tanggal_selesai_indo'  => $tglSelesaiIndo,
            'days_remaining'        => $daysRemaining,
            'is_expired'            => $daysRemaining < 0,
            'is_last_day'           => $daysRemaining === 0,
            'is_h_minus_1'          => $daysRemaining === 1,
            'next_nomor_periode'    => $nextNomor,
        ];
    }
}
