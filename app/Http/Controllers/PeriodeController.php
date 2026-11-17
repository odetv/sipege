<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\UnitSppg;
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
                'id'                => $p->id,
                'nomor_periode'     => $p->nomor_periode,
                'tanggal_mulai'     => $p->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai'   => $p->tanggal_selesai?->format('Y-m-d'),
                'is_penyesuaian'    => (bool) $p->is_penyesuaian,
                'tampilkan_di_list' => (bool) $p->tampilkan_di_list,
                'keterangan'        => $p->keterangan,
                'status'            => $p->status,
            ];
        });

        $today        = Carbon::today();
        $aktif        = $periodes->firstWhere('status', 'aktif');
        $periodeAwal  = $periodes->first();
        $unitSppg     = UnitSppg::first();

        // Hari sejak operasional dimulai = selisih dari tanggal_operasional unit SPPG atau tanggal_mulai Periode 1 ke hari ini
        $tglAwal = $unitSppg?->tanggal_operasional 
            ? $unitSppg->tanggal_operasional->format('Y-m-d') 
            : ($periodeAwal ? $periodeAwal['tanggal_mulai'] : null);

        $hariSejak = $tglAwal
            ? Carbon::parse($tglAwal)->diffInDays($today)
            : 0;

        // Cari periode aplikasi pertama (periode pertama yang bukan penyesuaian lampau)
        $periodeMulaiAplikasi = $periodes->firstWhere('is_penyesuaian', false) ?? $periodeAwal;

        $summary = [
            'periode_berlalu'        => $periodes->where('status', 'selesai')->count(),
            'periode_aktif'          => $aktif ? "Periode {$aktif['nomor_periode']}" : '—',
            'tgl_awal_program'       => $tglAwal,
            'hari_sejak_awal'        => (int) $hariSejak,
            'total_periode'          => $periodes->count(),
            'total_penyesuaian'      => $periodes->where('is_penyesuaian', true)->count(),
            'total_tampil_list'      => $periodes->where('tampilkan_di_list', true)->count(),
            'nomor_periode_aplikasi' => $periodeMulaiAplikasi ? $periodeMulaiAplikasi['nomor_periode'] : 1,
            'lampau_tampil_count'    => $periodes->where('is_penyesuaian', true)->where('tampilkan_di_list', true)->count(),
        ];

        return Inertia::render('Periode/Index', [
            'periodes' => $periodes,
            'summary'  => $summary,
            'unitSppg' => $unitSppg ? $unitSppg->only(['id', 'nama', 'tanggal_operasional']) : null,
        ]);
    }

    /**
     * Simpan periode baru — nomor_periode otomatis (max + 1).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_mulai'     => 'required|date',
            'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
            'is_penyesuaian'    => 'nullable|boolean',
            'tampilkan_di_list' => 'nullable|boolean',
            'keterangan'        => 'nullable|string|max:255',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        // Cek tumpang tindih dengan periode yang sudah ada
        $overlap = Periode::where(function ($query) use ($validated) {
            $query->where('tanggal_mulai', '<=', $validated['tanggal_selesai'])
                  ->where('tanggal_selesai', '>=', $validated['tanggal_mulai']);
        })->first();

        if ($overlap) {
            return back()->withErrors([
                'tanggal_mulai' => "Rentang tanggal bertabrakan dengan Periode {$overlap->nomor_periode} (" .
                    $overlap->tanggal_mulai->format('d/m/Y') . " – " . $overlap->tanggal_selesai->format('d/m/Y') . ").",
            ]);
        }

        $nextNomor = (Periode::max('nomor_periode') ?? 0) + 1;
        $validated['nomor_periode'] = $nextNomor;
        $validated['is_penyesuaian'] = $validated['is_penyesuaian'] ?? false;
        $validated['tampilkan_di_list'] = $validated['tampilkan_di_list'] ?? true;

        Periode::create($validated);

        return back()->with('success', "Periode {$nextNomor} berhasil ditambahkan.");
    }

    /**
     * Update tanggal & status visibilitas periode.
     */
    public function update(Request $request, Periode $periode): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_mulai'     => 'required|date',
            'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
            'is_penyesuaian'    => 'nullable|boolean',
            'tampilkan_di_list' => 'nullable|boolean',
            'keterangan'        => 'nullable|string|max:255',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        // Cek tumpang tindih dengan periode lain
        $overlap = Periode::where('id', '!=', $periode->id)
            ->where(function ($query) use ($validated) {
                $query->where('tanggal_mulai', '<=', $validated['tanggal_selesai'])
                      ->where('tanggal_selesai', '>=', $validated['tanggal_mulai']);
            })->first();

        if ($overlap) {
            return back()->withErrors([
                'tanggal_mulai' => "Rentang tanggal bertabrakan dengan Periode {$overlap->nomor_periode} (" .
                    $overlap->tanggal_mulai->format('d/m/Y') . " – " . $overlap->tanggal_selesai->format('d/m/Y') . ").",
            ]);
        }

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

    /**
     * Fitur Penyesuaian Periode Awal / Lampau.
     * Menyesuaikan urutan periode awal, rentang tanggal awal (mulai & selesai),
     * serta preferensi tampil/sembunyi di list/dropdown.
     * Periode normal lainnya tetap terjaga dan tidak diubah menjadi penyesuaian lampau.
     */
    public function penyesuaian(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_periode'     => 'required|integer|min:1',
            'tanggal_mulai'     => 'required|date',
            'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
            'tampilkan_di_list' => 'required|boolean',
            'keterangan'        => 'nullable|string|max:255',
        ], [
            'nomor_periode.required'         => 'Urutan nomor periode awal wajib diisi.',
            'nomor_periode.min'              => 'Urutan nomor periode minimal 1.',
            'tanggal_mulai.required'         => 'Tanggal mulai periode awal wajib diisi.',
            'tanggal_selesai.required'       => 'Tanggal selesai periode awal wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $nomorPeriode = (int) $validated['nomor_periode'];
        $tglMulai = Carbon::parse($validated['tanggal_mulai']);
        $tglSelesai = Carbon::parse($validated['tanggal_selesai']);
        $tampilkanDiList = (bool) $validated['tampilkan_di_list'];
        $keterangan = !empty(trim($validated['keterangan'] ?? ''))
            ? trim($validated['keterangan'])
            : "Penyesuaian periode awal sebelum penggunaan aplikasi";

        // Cek apakah ada periode lain yang tanggalnya bertabrakan
        $existingPeriode = Periode::where('nomor_periode', $nomorPeriode)->first();

        $overlap = Periode::when($existingPeriode, function ($q) use ($existingPeriode) {
                return $q->where('id', '!=', $existingPeriode->id);
            }, function ($q) use ($nomorPeriode) {
                return $q->where('nomor_periode', '!=', $nomorPeriode);
            })
            ->where(function ($query) use ($validated) {
                $query->where('tanggal_mulai', '<=', $validated['tanggal_selesai'])
                      ->where('tanggal_selesai', '>=', $validated['tanggal_mulai']);
            })->first();

        if ($overlap) {
            return back()->withErrors([
                'tanggal_mulai' => "Rentang tanggal bertabrakan dengan Periode {$overlap->nomor_periode} (" .
                    $overlap->tanggal_mulai->format('d/m/Y') . " – " . $overlap->tanggal_selesai->format('d/m/Y') . ").",
            ]);
        }

        // Buat atau perbarui periode penyesuaian khusus ini saja
        if ($existingPeriode) {
            $existingPeriode->update([
                'tanggal_mulai'     => $tglMulai->toDateString(),
                'tanggal_selesai'   => $tglSelesai->toDateString(),
                'is_penyesuaian'    => true,
                'tampilkan_di_list' => $tampilkanDiList,
                'keterangan'        => $keterangan,
            ]);
        } else {
            Periode::create([
                'nomor_periode'     => $nomorPeriode,
                'tanggal_mulai'     => $tglMulai->toDateString(),
                'tanggal_selesai'   => $tglSelesai->toDateString(),
                'is_penyesuaian'    => true,
                'tampilkan_di_list' => $tampilkanDiList,
                'keterangan'        => $keterangan,
            ]);
        }

        // Sinkronkan tanggal operasional unit SPPG jika periode ini adalah tanggal paling awal
        $minTanggal = Periode::min('tanggal_mulai');
        if ($minTanggal) {
            $unitSppg = UnitSppg::first();
            if ($unitSppg) {
                $unitSppg->update(['tanggal_operasional' => $minTanggal]);
            }
        }

        $statusList = $tampilkanDiList ? 'dimunculkan di daftar pilihan periode' : 'disembunyikan dari daftar pilihan periode';
        return back()->with('success', "Penyesuaian Periode {$nomorPeriode} ({$tglMulai->format('d/m/Y')} – {$tglSelesai->format('d/m/Y')}) berhasil disimpan dan {$statusList}. Periode normal lainnya tetap terjaga normal.");
    }

    /**
     * Toggle cepat visibilitas periode di list/dropdown aplikasi.
     */
    public function toggleVisibility(Periode $periode): RedirectResponse
    {
        $newStatus = !$periode->tampilkan_di_list;
        $periode->update(['tampilkan_di_list' => $newStatus]);

        $status = $newStatus ? 'dimunculkan di' : 'disembunyikan dari';
        return back()->with('success', "Periode {$periode->nomor_periode} sekarang {$status} list pilihan periode.");
    }
}
