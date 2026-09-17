<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SopController extends Controller
{
    /**
     * Daftar SOP beserta metadata dan kategori.
     */
    public function index(Request $request): Response
    {
        $sopDir = database_path('data/sop');
        $sops = [];

        if (File::exists($sopDir)) {
            $files = File::files($sopDir);

            foreach ($files as $file) {
                if (strtolower($file->getExtension()) !== 'pdf') {
                    continue;
                }

                $filename = $file->getFilename();
                $sopData = $this->parseSopMetadata($filename, $file->getSize());
                $sops[] = $sopData;
            }

            // Urutkan berdasarkan nomor urut SOP
            usort($sops, fn($a, $b) => $a['number'] <=> $b['number']);
        }

        // Hitung statistik kategori
        $categories = [
            'Semua' => count($sops),
            'Perencanaan & Pengadaan' => 0,
            'Penerimaan & Penyimpanan Bahan' => 0,
            'Persiapan & Pengolahan Makanan' => 0,
            'Pemorsian & Distribusi' => 0,
            'Pengendalian Mutu & Keamanan Pangan' => 0,
            'Sanitasi, Higiene & Pemeliharaan' => 0,
        ];

        foreach ($sops as $sop) {
            $cat = $sop['category'];
            if (isset($categories[$cat])) {
                $categories[$cat]++;
            } else {
                $categories[$cat] = 1;
            }
        }

        $summary = [
            'total_sop' => count($sops),
            'total_kategori' => count($categories) - 1,
            'standar' => 'BGN & Kemenkes RI',
            'status' => 'Terverifikasi & Berlaku',
        ];

        return Inertia::render('Sop/Index', [
            'sops' => $sops,
            'categories' => $categories,
            'summary' => $summary,
            'selectedId' => $request->query('id') ? (int) $request->query('id') : null,
        ]);
    }

    /**
     * Stream / Preview dokumen PDF SOP secara inline di browser.
     */
    public function stream(string $filename): BinaryFileResponse
    {
        // Sanitasi nama file untuk mencegah directory traversal
        $cleanFilename = basename($filename);
        $path = database_path('data/sop/' . $cleanFilename);

        if (!File::exists($path)) {
            abort(404, 'Dokumen SOP tidak ditemukan.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $cleanFilename . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Download dokumen PDF SOP.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $cleanFilename = basename($filename);
        $path = database_path('data/sop/' . $cleanFilename);

        if (!File::exists($path)) {
            abort(404, 'Dokumen SOP tidak ditemukan.');
        }

        return response()->download($path, $cleanFilename);
    }

    /**
     * Parse metadata file SOP (Nomor, Judul, Kategori, Deskripsi, Badge Color).
     */
    private function parseSopMetadata(string $filename, int $sizeBytes): array
    {
        $pattern = '/^(\d+)\.\s*(.+)\.pdf$/i';
        $number = 999;
        $title = $filename;

        if (preg_match($pattern, trim($filename), $matches)) {
            $number = (int) $matches[1];
            $title = trim($matches[2]);
        }

        // Hapus duplikasi kata 'SOP' di awal judul jika ada
        $cleanTitle = preg_replace('/^SOP\s+/i', '', $title);

        // Pengelompokan Kategori Berdasarkan Standar Operasional SPPG
        if ($number >= 1 && $number <= 2) {
            $category = 'Perencanaan & Pengadaan';
            $badgeColor = 'indigo';
            $icon = 'ClipboardCheck';
            $desc = 'Pedoman teknis penyusunan rencana menu bergizi seimbang dan tata cara survei harga pasar komoditas.';
        } elseif ($number >= 3 && $number <= 8) {
            $category = 'Penerimaan & Penyimpanan Bahan';
            $badgeColor = 'amber';
            $icon = 'PackageCheck';
            $desc = 'Standar penerimaan kualitas, sortasi, penimbangan, dan tata cara penyimpanan bahan baku segar & kering.';
        } elseif ($number >= 9 && $number <= 17) {
            $category = 'Persiapan & Pengolahan Makanan';
            $badgeColor = 'emerald';
            $icon = 'Utensils';
            $desc = 'Instruksi kerja persiapan bahan, pencucian, pemotongan, dan pengolahan pangan sesuai target sasaran.';
        } elseif ($number >= 18 && $number <= 20) {
            $category = 'Pemorsian & Distribusi';
            $badgeColor = 'blue';
            $icon = 'Truck';
            $desc = 'Prosedur pemorsian ompreng, penempelan label nutrisi/alergen, serta logistik pengantaran ke titik bagi.';
        } elseif ($number >= 21 && $number <= 28) {
            $category = 'Pengendalian Mutu & Keamanan Pangan';
            $badgeColor = 'rose';
            $icon = 'ShieldCheck';
            $desc = 'Protokol uji organoleptik, kalibrasi alat ukur, penyimpanan sampel pangan, dan mitigasi kedaruratan.';
        } else {
            $category = 'Sanitasi, Higiene & Pemeliharaan';
            $badgeColor = 'purple';
            $icon = 'Sparkles';
            $desc = 'Standar kebersihan personel, sanitasi ruangan/alat, pest control, dan pengelolaan limbah (Grease Trap/IPAL).';
        }

        $sizeKb = round($sizeBytes / 1024, 1);
        $formattedSize = $sizeKb >= 1024
            ? round($sizeKb / 1024, 2) . ' MB'
            : round($sizeKb) . ' KB';

        return [
            'id' => $number,
            'number' => $number,
            'code' => sprintf('SOP-%02d', $number),
            'filename' => $filename,
            'title' => 'SOP ' . $cleanTitle,
            'short_title' => $cleanTitle,
            'category' => $category,
            'badge_color' => $badgeColor,
            'icon' => $icon,
            'description' => $desc,
            'size_bytes' => $sizeBytes,
            'size_formatted' => $formattedSize,
            'stream_url' => route('sop.stream', ['filename' => $filename]),
            'download_url' => route('sop.download', ['filename' => $filename]),
        ];
    }
}
