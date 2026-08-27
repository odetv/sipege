<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PeriodeController extends Controller
{
    /**
     * Tampilkan daftar periode beserta summary cards.
     */
    public function index(): Response
    {
        $periodes = Periode::orderBy('nomor_periode')->get()->map(function ($p) {
            return [
                'id'              => $p->id,
                'nomor_periode'   => $p->nomor_periode,
                'tanggal_mulai'   => $p->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai' => $p->tanggal_selesai?->format('Y-m-d'),
                'status'          => $p->status,
            ];
        });

        $today        = Carbon::today();
        $aktif        = $periodes->firstWhere('status', 'aktif');
        $periodeAwal  = $periodes->first();

        // Hari sejak operasional dimulai = selisih dari tanggal_mulai Periode 1 ke hari ini
        $hariSejak = $periodeAwal
            ? Carbon::parse($periodeAwal['tanggal_mulai'])->diffInDays($today)
            : 0;

        $summary = [
            'periode_berlalu'   => $periodes->where('status', 'selesai')->count(),
            'periode_aktif'     => $aktif ? "Periode {$aktif['nomor_periode']}" : '—',
            'tgl_awal_program'  => $periodeAwal ? $periodeAwal['tanggal_mulai'] : null,
            'hari_sejak_awal'   => (int) $hariSejak,
            'total_periode'     => $periodes->count(),
        ];

        return Inertia::render('Periode/Index', [
            'periodes' => $periodes,
            'summary'  => $summary,
        ]);
    }

    /**
     * Simpan periode baru — nomor_periode otomatis (max + 1).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $nextNomor = (Periode::max('nomor_periode') ?? 0) + 1;
        $validated['nomor_periode'] = $nextNomor;

        Periode::create($validated);

        return back()->with('success', "Periode {$nextNomor} berhasil ditambahkan.");
    }

    /**
     * Update tanggal periode — nomor_periode tidak bisa diubah.
     */
    public function update(Request $request, Periode $periode): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $periode->update($validated);

        return back()->with('success', "Periode {$periode->nomor_periode} berhasil diperbarui.");
    }

    /**
     * Hapus periode.
     */
    public function destroy(Periode $periode): RedirectResponse
    {
        $nomor = $periode->nomor_periode;
        $periode->delete();

        return back()->with('success', "Periode {$nomor} berhasil dihapus.");
    }
}
