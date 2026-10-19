<?php

namespace App\Http\Controllers;

use App\Models\KategoriKpm;
use App\Models\UnitSppg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KategoriKpmController extends Controller
{
    /**
     * Tampilkan daftar Kategori KPM beserta Pagu per Porsi.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();
        $unitId = $unitSppg?->id;

        // Jika belum ada data kategori KPM di database, inisialisasi default 11 kategori standar BGN
        $count = KategoriKpm::when($unitId, fn($q) => $q->where('unit_sppg_id', $unitId))->count();
        if ($count === 0) {
            KategoriKpm::seedDefaults($unitId);
        }

        $kategoriList = KategoriKpm::when($unitId, fn($q) => $q->where('unit_sppg_id', $unitId))
            ->orderBy('urutan', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $summary = [
            'total_kategori' => $kategoriList->count(),
            'total_pagu_8000' => $kategoriList->where('pagu_per_porsi', 8000)->count(),
            'total_pagu_10000' => $kategoriList->where('pagu_per_porsi', 10000)->count(),
            'total_aktif' => $kategoriList->where('status', 'Aktif')->count(),
            'rata_rata_pagu' => (int) round($kategoriList->avg('pagu_per_porsi') ?: 0),
        ];

        return Inertia::render('Pengaturan/KategoriKpm', [
            'kategoriList' => $kategoriList,
            'summary' => $summary,
            'unitSppg' => $unitSppg,
        ]);
    }

    /**
     * Simpan kategori KPM baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'pagu_per_porsi' => ['required', 'integer', 'min:0'],
            'jenis_porsi' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:Aktif,Nonaktif'],
        ], [
            'nama.required' => 'Nama Kategori KPM wajib diisi.',
            'pagu_per_porsi.required' => 'Pagu setiap porsi wajib diisi (angka).',
            'pagu_per_porsi.min' => 'Pagu porsi tidak boleh bernilai negatif.',
        ]);

        $maxUrutan = (int) KategoriKpm::when($unitSppg?->id, fn($q) => $q->where('unit_sppg_id', $unitSppg->id))->max('urutan');
        $validated['urutan'] = $maxUrutan + 1;
        $validated['unit_sppg_id'] = $unitSppg?->id;
        $validated['status'] = $validated['status'] ?? 'Aktif';

        KategoriKpm::create($validated);

        return back()->with('success', "Kategori KPM '{$validated['nama']}' berhasil ditambahkan.");
    }

    /**
     * Update kategori KPM.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        $kategori = KategoriKpm::when($unitSppg?->id, fn($q) => $q->where('unit_sppg_id', $unitSppg->id))->findOrFail($id);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'pagu_per_porsi' => ['required', 'integer', 'min:0'],
            'jenis_porsi' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:Aktif,Nonaktif'],
        ], [
            'nama.required' => 'Nama Kategori KPM wajib diisi.',
            'pagu_per_porsi.required' => 'Pagu setiap porsi wajib diisi (angka).',
            'pagu_per_porsi.min' => 'Pagu porsi tidak boleh bernilai negatif.',
        ]);

        $kategori->update($validated);

        return back()->with('success', "Kategori KPM '{$kategori->nama}' berhasil diperbarui.");
    }

    /**
     * Hapus kategori KPM.
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        $kategori = KategoriKpm::when($unitSppg?->id, fn($q) => $q->where('unit_sppg_id', $unitSppg->id))->findOrFail($id);
        $nama = $kategori->nama;
        $kategori->delete();

        return back()->with('success', "Kategori KPM '{$nama}' berhasil dihapus.");
    }

    /**
     * Reset kategori KPM ke 11 default standar BGN.
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();
        $unitId = $unitSppg?->id;

        KategoriKpm::when($unitId, fn($q) => $q->where('unit_sppg_id', $unitId))->delete();
        KategoriKpm::seedDefaults($unitId);

        return back()->with('success', 'Kategori KPM berhasil direset ke 11 kategori standar default BGN.');
    }
}
