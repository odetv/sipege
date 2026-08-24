<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GiziController extends Controller
{
    /**
     * Tampilkan halaman perencanaan & analisis gizi SPPG.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $kelompokList = [];
        $totalPenerima = 0;
        $totalPorsiKecil = 0;
        $totalPorsiBesar = 0;
        $kategoriCount = [];

        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)
                ->with('rincian')
                ->get();

            $totalPenerima = $kelompokList->sum('total_penerima');
            $totalPorsiKecil = $kelompokList->sum('total_porsi_kecil');
            $totalPorsiBesar = $kelompokList->sum('total_porsi_besar');

            $kategoriCount = $kelompokList->groupBy('kategori')->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total_penerima' => $items->sum('total_penerima'),
                    'total_porsi_kecil' => $items->sum('total_porsi_kecil'),
                    'total_porsi_besar' => $items->sum('total_porsi_besar'),
                ];
            });
        }

        return Inertia::render('Gizi/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'tkpiList' => $this->getTkpiData(),
            'stats' => [
                'total_kelompok' => count($kelompokList),
                'total_penerima' => $totalPenerima,
                'total_porsi_kecil' => $totalPorsiKecil,
                'total_porsi_besar' => $totalPorsiBesar,
                'kategori_breakdown' => $kategoriCount,
            ],
        ]);
    }

    /**
     * Membaca dan mem-parsing data resmi TKPI 2020 dari file database/data/tkpi2020.csv.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTkpiData(): array
    {
        $csvPath = database_path('data/tkpi2020.csv');
        if (!file_exists($csvPath)) {
            return [];
        }

        $items = [];
        if (($handle = fopen($csvPath, 'r')) !== false) {
            // Read header row
            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 28) {
                    continue;
                }

                $catRaw = $row[3] ?? 'Lainnya';
                // Bersihkan kode angka seperti "4.1. SEREALIA DAN HASIL OLAHANNYA"
                $catClean = preg_replace('/^\d+\.\d+\.\s*/', '', $catRaw);
                $catClean = ucwords(strtolower(trim($catClean)));

                $code = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');
                $energy = (float) ($row[7] ?? 0);
                $protein = (float) ($row[8] ?? 0);
                $fat = (float) ($row[9] ?? 0);
                $carb = (float) ($row[10] ?? 0);
                $fiber = (float) ($row[11] ?? 0);
                $bdd = (float) ($row[27] ?? 100);

                // Estimasi harga master awal per kg berdasarkan jenis bahan
                $hargaMaster = 15000;
                $nameLower = strtolower($name);
                if (str_contains($nameLower, 'daging') || str_contains($nameLower, 'sapi')) {
                    $hargaMaster = 120000;
                } elseif (str_contains($nameLower, 'ayam') || str_contains($nameLower, 'unggas')) {
                    $hargaMaster = 38000;
                } elseif (str_contains($nameLower, 'ikan') || str_contains($nameLower, 'udang') || str_contains($nameLower, 'cumi')) {
                    $hargaMaster = 45000;
                } elseif (str_contains($nameLower, 'telur')) {
                    $hargaMaster = 29000;
                } elseif (str_contains($nameLower, 'tempe') || str_contains($nameLower, 'tahu')) {
                    $hargaMaster = 15000;
                } elseif (str_contains($nameLower, 'beras')) {
                    $hargaMaster = 16000;
                } elseif (str_contains($nameLower, 'sayur') || str_contains($nameLower, 'bayam') || str_contains($nameLower, 'wortel') || str_contains($nameLower, 'kangkung') || str_contains($nameLower, 'buncis')) {
                    $hargaMaster = 14000;
                } elseif (str_contains($nameLower, 'pisang') || str_contains($nameLower, 'buah') || str_contains($nameLower, 'jeruk') || str_contains($nameLower, 'pepaya') || str_contains($nameLower, 'semangka')) {
                    $hargaMaster = 18000;
                } elseif (str_contains($nameLower, 'minyak')) {
                    $hargaMaster = 17500;
                } elseif (str_contains($nameLower, 'susu')) {
                    $hargaMaster = 28000;
                }

                // Deteksi alergen
                $alergen = null;
                if (str_contains($nameLower, 'telur')) {
                    $alergen = 'Telur';
                } elseif (str_contains($nameLower, 'ikan') || str_contains($nameLower, 'udang') || str_contains($nameLower, 'cumi') || str_contains($nameLower, 'kepiting') || str_contains($nameLower, 'kerang')) {
                    $alergen = 'Seafood/Ikan';
                } elseif (str_contains($nameLower, 'kedelai') || str_contains($nameLower, 'tempe') || str_contains($nameLower, 'tahu')) {
                    $alergen = 'Kedelai';
                } elseif (str_contains($nameLower, 'kacang')) {
                    $alergen = 'Kacang';
                } elseif (str_contains($nameLower, 'susu') || str_contains($nameLower, 'keju')) {
                    $alergen = 'Laktosa / Susu';
                } elseif (str_contains($nameLower, 'gandum') || str_contains($nameLower, 'terigu') || str_contains($nameLower, 'roti') || str_contains($nameLower, 'biskuit')) {
                    $alergen = 'Gluten';
                }

                $items[] = [
                    'id' => $code,
                    'code' => $code,
                    'nama' => $name,
                    'kategori' => $catClean ?: 'Lainnya',
                    'kategori_raw' => $catRaw,
                    'energi' => $energy,
                    'protein' => $protein,
                    'lemak' => $fat,
                    'karbohidrat' => $carb,
                    'serat' => $fiber,
                    'bdd' => $bdd > 0 ? $bdd : 100,
                    'fmm' => 100,
                    'buffer' => 4,
                    'harga_master' => $hargaMaster,
                    'alergen' => $alergen,
                ];
            }
            fclose($handle);
        }

        return $items;
    }
}
