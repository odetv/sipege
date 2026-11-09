<?php

namespace App\Http\Controllers;

use App\Models\KeuanganSaldoAwal;
use App\Models\RekeningBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KasBankController extends Controller
{
    /**
     * Sub-menu 1: Rekening Bank Unit SPPG.
     */
    public function rekeningBank(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $rekeningList = [];
        if ($unitSppg) {
            $rekeningList = RekeningBank::where('unit_sppg_id', $unitSppg->id)
                ->orderBy('is_utama', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return Inertia::render('KasBank/RekeningBank', [
            'unitSppg' => $unitSppg,
            'rekeningList' => $rekeningList,
        ]);
    }

    /**
     * Simpan Rekening Bank Baru.
     */
    public function storeRekening(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'jenis_bank' => ['required', 'string', 'max:50'],
            'nama_bank_lainnya' => ['nullable', 'string', 'max:100'],
            'nama_rekening' => ['required', 'string', 'max:255'],
            'nomor_rekening' => ['required', 'string', 'max:50'],
            'nomor_virtual_account' => ['nullable', 'string', 'max:50'],
            'kantor_cabang' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'is_utama' => ['nullable', 'boolean'],
        ]);

        $hasExisting = RekeningBank::where('unit_sppg_id', $unitSppg->id)->exists();
        $isUtama = !empty($validated['is_utama']) || !$hasExisting;

        if ($isUtama) {
            RekeningBank::where('unit_sppg_id', $unitSppg->id)->update(['is_utama' => false]);
        }

        RekeningBank::create([
            'unit_sppg_id' => $unitSppg->id,
            'user_id' => $user->id,
            'jenis_bank' => $validated['jenis_bank'],
            'nama_bank_lainnya' => $validated['nama_bank_lainnya'] ?? null,
            'nama_rekening' => $validated['nama_rekening'],
            'nomor_rekening' => $validated['nomor_rekening'],
            'nomor_virtual_account' => $validated['nomor_virtual_account'] ?? null,
            'kantor_cabang' => $validated['kantor_cabang'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'is_utama' => $isUtama,
            'status_aktif' => true,
        ]);

        return back()->with('success', 'Rekening bank berhasil disimpan.');
    }

    /**
     * Update Rekening Bank.
     */
    public function updateRekening(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $rekening = RekeningBank::where('unit_sppg_id', $unitSppg->id)->findOrFail($id);

        $validated = $request->validate([
            'jenis_bank' => ['required', 'string', 'max:50'],
            'nama_bank_lainnya' => ['nullable', 'string', 'max:100'],
            'nama_rekening' => ['required', 'string', 'max:255'],
            'nomor_rekening' => ['required', 'string', 'max:50'],
            'nomor_virtual_account' => ['nullable', 'string', 'max:50'],
            'kantor_cabang' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'is_utama' => ['nullable', 'boolean'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['is_utama']) && !$rekening->is_utama) {
            RekeningBank::where('unit_sppg_id', $unitSppg->id)->update(['is_utama' => false]);
        }

        $rekening->update([
            'jenis_bank' => $validated['jenis_bank'],
            'nama_bank_lainnya' => $validated['nama_bank_lainnya'] ?? null,
            'nama_rekening' => $validated['nama_rekening'],
            'nomor_rekening' => $validated['nomor_rekening'],
            'nomor_virtual_account' => $validated['nomor_virtual_account'] ?? null,
            'kantor_cabang' => $validated['kantor_cabang'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'is_utama' => !empty($validated['is_utama']),
            'status_aktif' => $validated['status_aktif'] ?? $rekening->status_aktif,
        ]);

        return back()->with('success', 'Rekening bank berhasil diperbarui.');
    }

    /**
     * Hapus Rekening Bank.
     */
    public function destroyRekening(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $rekening = RekeningBank::where('unit_sppg_id', $unitSppg->id)->findOrFail($id);
        $wasUtama = $rekening->is_utama;
        $rekening->delete();

        if ($wasUtama) {
            $next = RekeningBank::where('unit_sppg_id', $unitSppg->id)->first();
            if ($next) {
                $next->update(['is_utama' => true]);
            }
        }

        return back()->with('success', 'Rekening bank berhasil dihapus.');
    }

    /**
     * Tetapkan Rekening Utama.
     */
    public function setUtama(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        RekeningBank::where('unit_sppg_id', $unitSppg->id)->update(['is_utama' => false]);
        $rekening = RekeningBank::where('unit_sppg_id', $unitSppg->id)->findOrFail($id);
        $rekening->update(['is_utama' => true]);

        return back()->with('success', 'Rekening utama berhasil diperbarui.');
    }

    /**
     * Sub-menu 2: Saldo Awal Kas / Bank.
     */
    public function saldoAwal(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $saldoAwal = null;
        if ($unitSppg) {
            $saldoAwal = KeuanganSaldoAwal::where('unit_sppg_id', $unitSppg->id)->first();
        }

        return Inertia::render('KasBank/SaldoAwal', [
            'unitSppg' => $unitSppg,
            'saldoAwal' => $saldoAwal ? [
                'id' => $saldoAwal->id,
                'tanggal' => $saldoAwal->tanggal ? $saldoAwal->tanggal->format('Y-m-d') : null,
                'nominal' => $saldoAwal->nominal,
                'keterangan' => $saldoAwal->keterangan,
                'is_locked' => $saldoAwal->is_locked,
                'user_name' => $saldoAwal->user ? $saldoAwal->user->name : null,
                'updated_at' => $saldoAwal->updated_at ? $saldoAwal->updated_at->toISOString() : null,
            ] : null,
        ]);
    }

    /**
     * Sub-menu 3: Saldo Harian (Fitur Segera Hadir).
     */
    public function saldoHarian(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        return Inertia::render('KasBank/SaldoHarian', [
            'unitSppg' => $unitSppg,
        ]);
    }

    /**
     * Sub-menu 4: Kas Kecil / Petty Cash (Fitur Segera Hadir).
     */
    public function kasKecil(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        return Inertia::render('KasBank/KasKecil', [
            'unitSppg' => $unitSppg,
        ]);
    }
}

