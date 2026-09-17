<?php

namespace App\Http\Controllers;

use App\Models\UnitSppg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AsetDigitalController extends Controller
{
    /**
     * Halaman Utama Menu Aset Digital.
     */
    public function index(Request $request): Response
    {
        $activeTab = $request->query('tab', 'logo');
        return $this->renderAsetView($activeTab, $request);
    }

    /**
     * Sub-menu 1: Logo.
     */
    public function logo(Request $request): Response
    {
        return $this->renderAsetView('logo', $request);
    }

    /**
     * Sub-menu 2: Denah.
     */
    public function denah(Request $request): Response
    {
        return $this->renderAsetView('denah', $request);
    }

    /**
     * Sub-menu 3: Struktur Organisasi SPPG.
     */
    public function struktur(Request $request): Response
    {
        return $this->renderAsetView('struktur', $request);
    }

    /**
     * Sub-menu 4: Plang Ruangan.
     */
    public function plangRuangan(Request $request): Response
    {
        return $this->renderAsetView('plang-ruangan', $request);
    }

    /**
     * Sub-menu 5: Poster.
     */
    public function poster(Request $request): Response
    {
        return $this->renderAsetView('poster', $request);
    }

    /**
     * Sub-menu 6: Kop Dokumen.
     */
    public function kopDokumen(Request $request): Response
    {
        return $this->renderAsetView('kop-dokumen', $request);
    }

    /**
     * Stream / Preview file aset digital.
     */
    public function stream(string $category, string $filename): BinaryFileResponse
    {
        $cleanCategory = strtolower($category);
        $cleanFilename = basename($filename);

        $categoryFolderMap = [
            'logo' => 'logo',
            'denah' => 'denah',
            'struktur' => 'struktur',
            'plang-ruangan' => 'plang',
            'plang' => 'plang',
            'poster' => 'poster',
            'kop-dokumen' => 'kop',
            'kop' => 'kop',
        ];

        $folder = $categoryFolderMap[$cleanCategory] ?? $cleanCategory;
        $path = database_path("data/aset/{$folder}/" . $cleanFilename);

        if (!File::exists($path)) {
            abort(404, 'Berkas aset tidak ditemukan.');
        }

        $mimeType = File::mimeType($path);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $cleanFilename . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Download file aset digital.
     */
    public function download(string $category, string $filename): BinaryFileResponse
    {
        $cleanCategory = strtolower($category);
        $cleanFilename = basename($filename);

        $categoryFolderMap = [
            'logo' => 'logo',
            'denah' => 'denah',
            'struktur' => 'struktur',
            'plang-ruangan' => 'plang',
            'plang' => 'plang',
            'poster' => 'poster',
            'kop-dokumen' => 'kop',
            'kop' => 'kop',
        ];

        $folder = $categoryFolderMap[$cleanCategory] ?? $cleanCategory;
        $path = database_path("data/aset/{$folder}/" . $cleanFilename);

        if (!File::exists($path)) {
            abort(404, 'Berkas aset tidak ditemukan.');
        }

        return response()->download($path, $cleanFilename);
    }

    /**
     * Helper render view ke Inertia dengan dataset kosong (siap diisi aset baru).
     */
    private function renderAsetView(string $activeTab, Request $request): Response
    {
        $unitSppg = UnitSppg::first();

        // Seluruh dataset awal dikosongkan sesuai permintaan
        $logoItems = [];
        $denahItems = [];
        $strukturItems = [];
        $plangItems = [];
        $posterItems = [];
        $kopItems = [];
        $colorPalettes = [];

        // Scan berkas fisik dari database/data/aset/ jika suatu saat user menambahkan file
        $customAssets = [];
        $subfolders = [
            'logo' => 'logo',
            'denah' => 'denah',
            'struktur' => 'struktur',
            'plang-ruangan' => 'plang',
            'poster' => 'poster',
            'kop-dokumen' => 'kop',
        ];

        foreach ($subfolders as $tabKey => $folderName) {
            $path = database_path("data/aset/{$folderName}");
            if (File::exists($path)) {
                $files = File::files($path);
                foreach ($files as $file) {
                    $item = [
                        'id' => $file->getFilename(),
                        'title' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                        'filename' => $file->getFilename(),
                        'size' => round($file->getSize() / 1024, 1) . ' KB',
                        'mime' => File::mimeType($file->getPathname()),
                        'extension' => strtolower($file->getExtension()),
                        'stream_url' => route('aset-digital.stream', ['category' => $tabKey, 'filename' => $file->getFilename()]),
                        'download_url' => route('aset-digital.download', ['category' => $tabKey, 'filename' => $file->getFilename()]),
                    ];

                    if ($tabKey === 'logo') $logoItems[] = $item;
                    elseif ($tabKey === 'denah') $denahItems[] = $item;
                    elseif ($tabKey === 'struktur') $strukturItems[] = $item;
                    elseif ($tabKey === 'plang-ruangan') $plangItems[] = $item;
                    elseif ($tabKey === 'poster') $posterItems[] = $item;
                    elseif ($tabKey === 'kop-dokumen') $kopItems[] = $item;

                    $customAssets[] = $item;
                }
            }
        }

        return Inertia::render('AsetDigital/Index', [
            'activeTab' => $activeTab,
            'unitSppg' => $unitSppg,
            'logos' => $logoItems,
            'denahs' => $denahItems,
            'strukturs' => $strukturItems,
            'plangs' => $plangItems,
            'posters' => $posterItems,
            'kops' => $kopItems,
            'colorPalettes' => $colorPalettes,
            'customAssets' => $customAssets,
            'stats' => [
                'total_logo' => count($logoItems),
                'total_denah' => count($denahItems),
                'total_struktur' => count($strukturItems),
                'total_plang' => count($plangItems),
                'total_poster' => count($posterItems),
                'total_kop' => count($kopItems),
                'total_all' => count($logoItems) + count($denahItems) + count($strukturItems) + count($plangItems) + count($posterItems) + count($kopItems),
            ],
        ]);
    }
}
