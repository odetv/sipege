<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PetunjukController extends Controller
{
    /**
     * Halaman Utama Menu Petunjuk (SOP, Juknis, Pedoman).
     */
    public function index(Request $request): Response
    {
        $activeType = $request->query('type', 'all');
        return $this->renderPetunjukView($activeType, $request);
    }

    /**
     * Sub-menu SOP.
     */
    public function sop(Request $request): Response
    {
        return $this->renderPetunjukView('sop', $request);
    }

    /**
     * Sub-menu Juknis.
     */
    public function juknis(Request $request): Response
    {
        return $this->renderPetunjukView('juknis', $request);
    }

    /**
     * Sub-menu Pedoman.
     */
    public function pedoman(Request $request): Response
    {
        return $this->renderPetunjukView('pedoman', $request);
    }

    /**
     * Stream / Preview dokumen PDF secara inline di browser.
     */
    public function stream(string $type, string $filename): BinaryFileResponse
    {
        $cleanType = strtolower($type);
        if (!in_array($cleanType, ['sop', 'juknis', 'pedoman'])) {
            abort(404, 'Kategori petunjuk tidak valid.');
        }

        $cleanFilename = basename($filename);
        $path = database_path("data/{$cleanType}/" . $cleanFilename);

        if (!File::exists($path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $cleanFilename . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Download dokumen PDF.
     */
    public function download(string $type, string $filename): BinaryFileResponse
    {
        $cleanType = strtolower($type);
        if (!in_array($cleanType, ['sop', 'juknis', 'pedoman'])) {
            abort(404, 'Kategori petunjuk tidak valid.');
        }

        $cleanFilename = basename($filename);
        $path = database_path("data/{$cleanType}/" . $cleanFilename);

        if (!File::exists($path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        return response()->download($path, $cleanFilename);
    }

    /**
     * Helper render view ke Inertia dengan data lengkap.
     */
    private function renderPetunjukView(string $activeType, Request $request): Response
    {
        $allDocuments = [];

        // 1. Scan SOP Documents
        $sopDir = database_path('data/sop');
        if (File::exists($sopDir)) {
            $files = File::files($sopDir);
            foreach ($files as $file) {
                if (strtolower($file->getExtension()) !== 'pdf') continue;
                $filename = $file->getFilename();
                $allDocuments[] = $this->parseSopMetadata($filename, $file->getSize());
            }
        }

        // 2. Scan Juknis Documents
        $juknisDir = database_path('data/juknis');
        if (File::exists($juknisDir)) {
            $files = File::files($juknisDir);
            $juknisCounter = 1;
            foreach ($files as $file) {
                if (strtolower($file->getExtension()) !== 'pdf') continue;
                $filename = $file->getFilename();
                $allDocuments[] = $this->parseJuknisMetadata($filename, $file->getSize(), $juknisCounter++);
            }
        }

        // 3. Scan Pedoman Documents
        $pedomanDir = database_path('data/pedoman');
        if (File::exists($pedomanDir)) {
            $files = File::files($pedomanDir);
            $pedomanCounter = 1;
            foreach ($files as $file) {
                if (strtolower($file->getExtension()) !== 'pdf') continue;
                $filename = $file->getFilename();
                $allDocuments[] = $this->parsePedomanMetadata($filename, $file->getSize(), $pedomanCounter++);
            }
        }

        // Urutkan dokumen: SOP urut nomor, Juknis & Pedoman sesuai urutan
        usort($allDocuments, function ($a, $b) {
            $typePriority = ['sop' => 1, 'juknis' => 2, 'pedoman' => 3];
            $pA = $typePriority[$a['type']] ?? 99;
            $pB = $typePriority[$b['type']] ?? 99;
            if ($pA !== $pB) {
                return $pA <=> $pB;
            }
            return ($a['number'] ?? 0) <=> ($b['number'] ?? 0);
        });

        // Hitung statistik
        $sopCount = count(array_filter($allDocuments, fn($d) => $d['type'] === 'sop'));
        $juknisCount = count(array_filter($allDocuments, fn($d) => $d['type'] === 'juknis'));
        $pedomanCount = count(array_filter($allDocuments, fn($d) => $d['type'] === 'pedoman'));

        // Hitung kategori untuk filter
        $categories = [
            'Semua' => count($allDocuments),
        ];

        foreach ($allDocuments as $doc) {
            $cat = $doc['category'];
            if (isset($categories[$cat])) {
                $categories[$cat]++;
            } else {
                $categories[$cat] = 1;
            }
        }

        $summary = [
            'total_petunjuk' => count($allDocuments),
            'total_sop' => $sopCount,
            'total_juknis' => $juknisCount,
            'total_pedoman' => $pedomanCount,
            'standar' => 'BGN & Kemenkes RI',
            'status' => 'Terverifikasi & Berlaku',
        ];

        return Inertia::render('Petunjuk/Index', [
            'documents' => $allDocuments,
            'categories' => $categories,
            'summary' => $summary,
            'activeType' => $activeType,
            'selectedId' => $request->query('id') ? (string) $request->query('id') : null,
        ]);
    }

    /**
     * Parse SOP Metadata
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

        $cleanTitle = preg_replace('/^SOP\s+/i', '', $title);

        if ($number >= 1 && $number <= 2) {
            $category = 'Perencanaan & Pengadaan';
            $desc = 'Pedoman teknis penyusunan rencana menu bergizi seimbang dan tata cara survei harga pasar komoditas.';
        } elseif ($number >= 3 && $number <= 8) {
            $category = 'Penerimaan & Penyimpanan Bahan';
            $desc = 'Standar penerimaan kualitas, sortasi, penimbangan, dan tata cara penyimpanan bahan baku segar & kering.';
        } elseif ($number >= 9 && $number <= 17) {
            $category = 'Persiapan & Pengolahan Makanan';
            $desc = 'Instruksi kerja persiapan bahan, pencucian, pemotongan, dan pengolahan pangan sesuai target sasaran.';
        } elseif ($number >= 18 && $number <= 20) {
            $category = 'Pemorsian & Distribusi';
            $desc = 'Prosedur pemorsian ompreng, penempelan label nutrisi/alergen, serta logistik pengantaran ke titik bagi.';
        } elseif ($number >= 21 && $number <= 28) {
            $category = 'Pengendalian Mutu & Keamanan Pangan';
            $desc = 'Protokol uji organoleptik, kalibrasi alat ukur, penyimpanan sampel pangan, dan mitigasi kedaruratan.';
        } else {
            $category = 'Sanitasi, Higiene & Pemeliharaan';
            $desc = 'Standar kebersihan personel, sanitasi ruangan/alat, pest control, dan pengelolaan limbah (Grease Trap/IPAL).';
        }

        return [
            'id' => 'sop-' . $number,
            'type' => 'sop',
            'type_label' => 'SOP',
            'number' => $number,
            'code' => sprintf('SOP-%02d', $number),
            'filename' => $filename,
            'title' => 'SOP ' . $cleanTitle,
            'short_title' => $cleanTitle,
            'category' => $category,
            'description' => $desc,
            'size_bytes' => $sizeBytes,
            'size_formatted' => $this->formatFileSize($sizeBytes),
            'stream_url' => route('petunjuk.stream', ['type' => 'sop', 'filename' => $filename]),
            'download_url' => route('petunjuk.download', ['type' => 'sop', 'filename' => $filename]),
        ];
    }

    /**
     * Parse Juknis Metadata
     */
    private function parseJuknisMetadata(string $filename, int $sizeBytes, int $counter): array
    {
        $cleanName = preg_replace('/\.pdf$/i', '', $filename);
        $cleanName = preg_replace('/^\d+_\s*/', '', $cleanName);

        return [
            'id' => 'juknis-' . $counter,
            'type' => 'juknis',
            'type_label' => 'Petunjuk Teknis (Juknis)',
            'number' => $counter,
            'code' => sprintf('JUKNIS-%02d', $counter),
            'filename' => $filename,
            'title' => $cleanName,
            'short_title' => $cleanName,
            'category' => 'Petunjuk Teknis (Juknis)',
            'description' => 'Petunjuk teknis resmi tata kelola, mekanisme pembiayaan, dan akuntabilitas penyelenggaraan program Makan Bergizi Gratis (MBG).',
            'size_bytes' => $sizeBytes,
            'size_formatted' => $this->formatFileSize($sizeBytes),
            'stream_url' => route('petunjuk.stream', ['type' => 'juknis', 'filename' => $filename]),
            'download_url' => route('petunjuk.download', ['type' => 'juknis', 'filename' => $filename]),
        ];
    }

    /**
     * Parse Pedoman Metadata
     */
    private function parsePedomanMetadata(string $filename, int $sizeBytes, int $counter): array
    {
        $cleanName = preg_replace('/\.pdf$/i', '', $filename);

        return [
            'id' => 'pedoman-' . $counter,
            'type' => 'pedoman',
            'type_label' => 'Pedoman Operasional',
            'number' => $counter,
            'code' => sprintf('PEDOMAN-%02d', $counter),
            'filename' => $filename,
            'title' => $cleanName,
            'short_title' => $cleanName,
            'category' => 'Pedoman Operasional',
            'description' => 'Buku pedoman acuan penyelenggaraan operasional, alur kerja, standar fasilitas, dan manajemen Unit SPPG.',
            'size_bytes' => $sizeBytes,
            'size_formatted' => $this->formatFileSize($sizeBytes),
            'stream_url' => route('petunjuk.stream', ['type' => 'pedoman', 'filename' => $filename]),
            'download_url' => route('petunjuk.download', ['type' => 'pedoman', 'filename' => $filename]),
        ];
    }

    private function formatFileSize(int $sizeBytes): string
    {
        $sizeKb = round($sizeBytes / 1024, 1);
        if ($sizeKb >= 1024) {
            return round($sizeKb / 1024, 2) . ' MB';
        }
        return round($sizeKb) . ' KB';
    }
}
