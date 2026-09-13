<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Menampilkan daftar seluruh supplier rekanan SPPG.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $suppliers = [];
        $summary = [
            'total_supplier' => 0,
            'total_kdmp_koperasi' => 0,
            'total_umkm' => 0,
            'total_komoditas' => 0,
        ];

        if ($unitSppg) {
            $suppliers = Supplier::where('unit_sppg_id', $unitSppg->id)
                ->withCount('purchaseOrders')
                ->orderBy('created_at', 'desc')
                ->get();

            $allKomoditas = [];
            foreach ($suppliers as $s) {
                if (is_array($s->komoditas)) {
                    foreach ($s->komoditas as $k) {
                        if (trim($k)) {
                            $allKomoditas[strtolower(trim($k))] = true;
                        }
                    }
                }
            }

            $summary['total_supplier'] = $suppliers->count();
            $summary['total_kdmp_koperasi'] = $suppliers->filter(function ($s) {
                return in_array($s->jenis_supplier, ['KDMP', 'Koperasi', 'Bumdes', 'Bumdesma']);
            })->count();
            $summary['total_umkm'] = $suppliers->filter(function ($s) {
                return $s->jenis_supplier === 'UMKM';
            })->count();
            $summary['total_komoditas'] = count($allKomoditas);
        }

        return Inertia::render('Supplier/Index', [
            'suppliers' => $suppliers,
            'summary' => $summary,
            'unitSppg' => $unitSppg,
        ]);
    }

    /**
     * Menyimpan data supplier baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'jenis_supplier' => ['required', 'string', 'in:KDMP,Koperasi,Bumdes,Bumdesma,UMKM,UD,CV,PT,Lainnya'],
            'nama_usaha' => ['required', 'string', 'max:255'],
            'nama_pemilik' => ['nullable', 'string', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'komoditas' => ['nullable', 'array'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kabupaten' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'alamat_lengkap' => ['nullable', 'string'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
        ]);

        $validated['unit_sppg_id'] = $unitSppg->id;

        Supplier::create($validated);

        return back()->with('success', 'Data Supplier Rekanan berhasil ditambahkan.');
    }

    /**
     * Memperbarui data supplier.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $supplier = Supplier::where('unit_sppg_id', $unitSppg->id)->findOrFail($id);

        $validated = $request->validate([
            'jenis_supplier' => ['required', 'string', 'in:KDMP,Koperasi,Bumdes,Bumdesma,UMKM,UD,CV,PT,Lainnya'],
            'nama_usaha' => ['required', 'string', 'max:255'],
            'nama_pemilik' => ['nullable', 'string', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'komoditas' => ['nullable', 'array'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kabupaten' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'alamat_lengkap' => ['nullable', 'string'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
        ]);

        $supplier->update($validated);

        return back()->with('success', 'Data Supplier Rekanan berhasil diperbarui.');
    }

    /**
     * Menghapus data supplier.
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $supplier = Supplier::where('unit_sppg_id', $unitSppg->id)->findOrFail($id);
        $supplier->delete();

        return back()->with('success', 'Data Supplier Rekanan berhasil dihapus.');
    }
}
