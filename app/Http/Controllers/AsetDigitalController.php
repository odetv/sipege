<?php

namespace App\Http\Controllers;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Shared\Converter;

use App\Models\UnitSppg;
use App\Models\SettingKopDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AsetDigitalController extends Controller
{
    /**
     * Folder mapping kategori ke direktori public/images/
     */
    private array $categoryFolderMap = [
        'logo' => 'logo',
        'denah' => 'denah',
        'struktur' => 'struktur',
        'plang-ruangan' => 'plang-ruangan',
        'plang' => 'plang-ruangan',
        'poster' => 'poster',
        'kop-dokumen' => 'kop',
        'kop' => 'kop',
    ];

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
        $folder = $this->categoryFolderMap[$cleanCategory] ?? $cleanCategory;

        $path = public_path("images/{$folder}/" . $cleanFilename);

        if (!File::exists($path)) {
            $fallbackPath = database_path("data/aset/{$folder}/" . $cleanFilename);
            if (File::exists($fallbackPath)) {
                $path = $fallbackPath;
            } else {
                abort(404, 'Berkas aset tidak ditemukan.');
            }
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
        $folder = $this->categoryFolderMap[$cleanCategory] ?? $cleanCategory;

        $path = public_path("images/{$folder}/" . $cleanFilename);

        if (!File::exists($path)) {
            $fallbackPath = database_path("data/aset/{$folder}/" . $cleanFilename);
            if (File::exists($fallbackPath)) {
                $path = $fallbackPath;
            } else {
                abort(404, 'Berkas aset tidak ditemukan.');
            }
        }

        return response()->download($path, $cleanFilename);
    }

    /**
     * Helper format ukuran berkas.
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1024, 1) . ' KB';
    }

    /**
     * Helper render view ke Inertia dengan pemindaian aset dari public/images/.
     */
    private function renderAsetView(string $activeTab, Request $request): Response
    {
        $unitSppg = UnitSppg::first();

        $logoItems = [];
        $denahItems = [];
        $strukturItems = [];
        $plangItems = [];
        $posterItems = [];
        $kopItems = [];
        $colorPalettes = [];
        $customAssets = [];

        $subfolders = [
            'logo' => 'logo',
            'denah' => 'denah',
            'struktur' => 'struktur',
            'plang-ruangan' => 'plang-ruangan',
            'poster' => 'poster',
            'kop-dokumen' => 'kop',
        ];

        foreach ($subfolders as $tabKey => $folderName) {
            $path = public_path("images/{$folderName}");
            if (File::exists($path)) {
                $files = File::files($path);
                foreach ($files as $file) {
                    $ext = strtolower($file->getExtension());
                    $filename = $file->getFilename();
                    $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg']);
                    $isPdf = ($ext === 'pdf');

                    $item = [
                        'id' => $filename,
                        'title' => pathinfo($filename, PATHINFO_FILENAME),
                        'filename' => $filename,
                        'size' => $this->formatFileSize($file->getSize()),
                        'mime' => File::mimeType($file->getPathname()),
                        'extension' => strtoupper($ext),
                        'url' => asset("images/{$folderName}/" . $filename),
                        'stream_url' => route('aset-digital.stream', ['category' => $tabKey, 'filename' => $filename]),
                        'download_url' => route('aset-digital.download', ['category' => $tabKey, 'filename' => $filename]),
                        'is_image' => $isImage,
                        'is_pdf' => $isPdf,
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
            'kopConfig' => SettingKopDokumen::getActiveConfig($unitSppg?->id),
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

    /**
     * Simpan / Perbarui konfigurasi Kop Dokumen resmi.
     */
    public function saveKopDokumen(Request $request)
    {
        $validated = $request->validate([
            'nama_instansi_1' => 'required|string|max:255',
            'nama_instansi_2' => 'required|string|max:255',
            'nama_unit' => 'required|string|max:255',
            'kode_unit' => 'nullable|string|max:100',
            'id_sppg' => 'nullable|string|max:100',
            'alamat_lengkap' => 'required|string',
            'desa_kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:20',
            'telepon' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:100',
            'logo_kiri_url' => 'nullable|string',
            'logo_kanan_url' => 'nullable|string',
            'layout_logo' => 'required|string|in:dual,kiri,tengah',
            'gaya_garis' => 'required|string|in:ganda_kedinasan,tunggal_tebal,modern_aksen,minimalis',
            'template_style' => 'required|string|in:kedinasan_resmi,modern_sppg,klasik_formal',
            'is_aktif' => 'nullable|boolean',
            'extra_attributes' => 'nullable|array',
        ]);

        $unit = UnitSppg::first();
        $config = SettingKopDokumen::where('is_aktif', true)->first();

        if ($config) {
            $config->update($validated);
        } else {
            $validated['unit_sppg_id'] = $unit?->id;
            $config = SettingKopDokumen::create($validated);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi Kop Dokumen berhasil disimpan.',
                'data' => $config,
            ]);
        }

        return redirect()->back()->with('success', 'Konfigurasi Kop Dokumen resmi berhasil disimpan.');
    }

    /**
     * Upload logo untuk Kop Dokumen.
     */
    public function uploadKopLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:4096',
            'position' => 'nullable|string|in:kiri,kanan',
        ]);

        $file = $request->file('logo');
        $extension = $file->getClientOriginalExtension();
        $position = $request->input('position', 'kiri');
        $filename = 'kop_logo_' . $position . '_' . time() . '.' . $extension;

        $destinationPath = public_path('images/kop');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $file->move($destinationPath, $filename);
        $url = '/images/kop/' . $filename;

        return response()->json([
            'success' => true,
            'url' => $url,
            'filename' => $filename,
            'message' => 'Logo kop berhasil diunggah.',
        ]);
    }

    /**
     * Unduh template dokumen kosong ber-kop resmi (Word DOCX / HTML / Print).
     */
    public function downloadKopTemplate(Request $request, string $format)
    {
        $unit = UnitSppg::first();
        $config = SettingKopDokumen::getActiveConfig($unit?->id);
        $orientation = $request->query('orientation', 'portrait');

        if ($format === 'docx' || $format === 'word' || $format === 'doc') {
            return $this->generateNativeDocxResponse($config, $orientation);
        }

        $html = $this->generateKopPrintHtml($config, $orientation);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Menghasilkan berkas .docx native resmi Microsoft Word (OpenXML) dengan Header asli.
     */
    private function generateNativeDocxResponse(SettingKopDokumen $config, string $orientation = 'portrait')
    {
        $phpWord = new PhpWord();

        $isLandscape = ($orientation === 'landscape');
        $sectionWidth = $isLandscape ? 29.7 : 21.0;
        $marginSide = 2.0;
        $printableWidthCm = $sectionWidth - ($marginSide * 2);

        $sectionStyle = [
            'orientation' => $isLandscape ? 'landscape' : 'portrait',
            'marginTop' => Converter::cmToTwip(1.8),
            'marginBottom' => Converter::cmToTwip(2.0),
            'marginLeft' => Converter::cmToTwip($marginSide),
            'marginRight' => Converter::cmToTwip($marginSide),
            'headerHeight' => Converter::cmToTwip(1.0),
        ];

        $section = $phpWord->addSection($sectionStyle);

        // ==========================================
        // 1. ADD OFFICIAL NATIVE WORD HEADER
        // ==========================================
        $header = $section->addHeader();

        $logoCellWidthCm = 2.4;
        $textCellWidthCm = $printableWidthCm - ($logoCellWidthCm * 2);

        $table = $header->addTable([
            'alignment' => JcTable::CENTER,
            'cellSpacing' => 0,
        ]);
        $table->addRow();

        // Logo Kiri BGN
        $logoKiriPath = public_path(ltrim($config->logo_kiri_url ?: '/images/logo/BGN_LOGO_MAIN.png', '/'));
        $cellKiri = $table->addCell(Converter::cmToTwip($logoCellWidthCm), ['valign' => 'center']);
        if (file_exists($logoKiriPath)) {
            $cellKiri->addImage($logoKiriPath, [
                'width' => 55,
                'height' => 55,
                'alignment' => Jc::CENTER,
            ]);
        }

        // Teks Tengah Kop
        $cellTengah = $table->addCell(Converter::cmToTwip($textCellWidthCm), ['valign' => 'center']);

        $instansi1 = strtoupper($config->nama_instansi_1 ?? 'SPPG BULELENG SUKASADA TEGALLINGGAH');
        $instansi2 = strtoupper($config->nama_instansi_2 ?? 'YAYASAN PESANTREN MIFTAHUL ULUM');
        $namaUnit = strtoupper($config->nama_unit ?? '');
        $alamat = $config->alamat_lengkap ?? 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali';

        $showUnitLine = (!empty($namaUnit) && $namaUnit !== $instansi1 && $namaUnit !== $instansi2 && !str_contains($instansi1, $namaUnit));

        $contacts = [];
        if ($config->telepon) $contacts[] = 'Telp: ' . $config->telepon;
        if ($config->whatsapp) $contacts[] = 'WA: ' . $config->whatsapp;
        if ($config->email) $contacts[] = 'E-mail: ' . $config->email;
        if ($config->website) $contacts[] = 'Web: ' . $config->website;

        $kontakStr = !empty($contacts) ? implode(' | ', $contacts) : ($config->email ? 'E-mail: ' . $config->email : '');

        $cellTengah->addText($instansi1, [
            'name' => 'Times New Roman',
            'size' => 12.5,
            'bold' => true,
            'color' => '000000',
        ], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 20,
            'spaceBefore' => 0,
        ]);

        $cellTengah->addText($instansi2, [
            'name' => 'Times New Roman',
            'size' => 11.5,
            'bold' => true,
            'color' => '000000',
        ], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 20,
            'spaceBefore' => 0,
        ]);

        if ($showUnitLine) {
            $cellTengah->addText($namaUnit, [
                'name' => 'Times New Roman',
                'size' => 11,
                'bold' => true,
                'color' => '000000',
            ], [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 20,
                'spaceBefore' => 0,
            ]);
        }

        $cellTengah->addText($alamat, [
            'name' => 'Times New Roman',
            'size' => 9.5,
            'color' => '000000',
        ], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 15,
            'spaceBefore' => 0,
        ]);

        $cellTengah->addText($kontakStr, [
            'name' => 'Times New Roman',
            'size' => 9.5,
            'color' => '000000',
        ], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 30,
            'spaceBefore' => 0,
        ]);

        // Logo Kanan Yayasan
        $logoKananPath = public_path(ltrim($config->logo_kanan_url ?: '/images/logo/Logo_Yayasan.png', '/'));
        $cellKanan = $table->addCell(Converter::cmToTwip($logoCellWidthCm), ['valign' => 'center']);
        if (($config->layout_logo === 'dual') && file_exists($logoKananPath)) {
            $cellKanan->addImage($logoKananPath, [
                'width' => 55,
                'height' => 55,
                'alignment' => Jc::CENTER,
            ]);
        }

        // Garis Pembatas Ganda Kedinasan
        $header->addText('', [], [
            'borderBottomSize' => 24,
            'borderBottomStyle' => 'thinThickMediumGap',
            'borderBottomColor' => '000000',
            'spaceBefore' => 60,
            'spaceAfter' => 0,
        ]);

        // ==========================================
        // 2. DOCUMENT BODY CONTENT
        // ==========================================
        $section->addText('SURAT PERNYATAAN / DOKUMEN RESMI', [
            'name' => 'Times New Roman',
            'size' => 12,
            'bold' => true,
            'underline' => 'single',
        ], [
            'alignment' => Jc::CENTER,
            'spaceBefore' => 250,
            'spaceAfter' => 40,
        ]);

        $section->addText('Nomor: B-...../SPPG/...../2026', [
            'name' => 'Times New Roman',
            'size' => 10.5,
        ], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 250,
        ]);

        $section->addText('Yang bertanda tangan di bawah ini:', [
            'name' => 'Times New Roman',
            'size' => 10.5,
        ], ['spaceAfter' => 100]);

        $tableBody = $section->addTable(['cellSpacing' => 0]);
        $r1 = $tableBody->addRow();
        $r1->addCell(Converter::cmToTwip(3.0))->addText('Nama', ['name' => 'Times New Roman', 'size' => 10.5]);
        $r1->addCell(Converter::cmToTwip(13.0))->addText(': ..............................................................', ['name' => 'Times New Roman', 'size' => 10.5]);

        $r2 = $tableBody->addRow();
        $r2->addCell(Converter::cmToTwip(3.0))->addText('Jabatan', ['name' => 'Times New Roman', 'size' => 10.5]);
        $r2->addCell(Converter::cmToTwip(13.0))->addText(': Kepala Satuan Pelayanan Program Gizi', ['name' => 'Times New Roman', 'size' => 10.5]);

        $r3 = $tableBody->addRow();
        $r3->addCell(Converter::cmToTwip(3.0))->addText('Unit Kerja', ['name' => 'Times New Roman', 'size' => 10.5]);
        $r3->addCell(Converter::cmToTwip(13.0))->addText(': ' . ($config->nama_unit ?? 'SPPG Buleleng Sukasada Tegallinggah'), ['name' => 'Times New Roman', 'size' => 10.5]);

        $section->addTextBreak(1);

        $section->addText('[ AREA FORMAT ISI DOKUMEN / SURAT / FORMULIR RESMI ]', [
            'name' => 'Times New Roman',
            'size' => 10,
            'bold' => true,
            'color' => '475569',
        ], [
            'alignment' => Jc::CENTER,
            'spaceBefore' => 150,
            'spaceAfter' => 150,
        ]);

        $section->addText('Dokumen ini telah dilengkapi dengan Kop Dokumen Resmi SPPG pada bagian Header Microsoft Word. Anda dapat langsung menyunting teks isi dokumen ini tanpa merusak tata letak kop.', [
            'name' => 'Times New Roman',
            'size' => 9.5,
            'italic' => true,
            'color' => '64748B',
        ], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 300,
        ]);

        // Kolom Tanda Tangan
        $ttdTable = $section->addTable(['cellSpacing' => 0]);
        $trTtd = $ttdTable->addRow();
        $trTtd->addCell(Converter::cmToTwip($isLandscape ? 17.0 : 9.5)); // Spacer
        $cTtd = $trTtd->addCell(Converter::cmToTwip(7.5), ['valign' => 'top']);
        $cTtd->addText('Ditetapkan di: ' . ($config->kabupaten ?? 'Buleleng'), ['name' => 'Times New Roman', 'size' => 10.5], ['alignment' => Jc::CENTER]);
        $cTtd->addText('Pada tanggal: ' . date('d F Y'), ['name' => 'Times New Roman', 'size' => 10.5], ['alignment' => Jc::CENTER]);
        $cTtd->addText('Kepala ' . ($config->nama_unit ?? 'SPPG Buleleng'), ['name' => 'Times New Roman', 'size' => 10.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 800]);
        $cTtd->addText('( .................................................... )', ['name' => 'Times New Roman', 'size' => 10.5, 'bold' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
        $cTtd->addText('NIP. ....................................................', ['name' => 'Times New Roman', 'size' => 10.5], ['alignment' => Jc::CENTER]);

        $cleanUnit = preg_replace('/[^a-zA-Z0-9_-]/', '_', $config->nama_unit ?? 'SPPG');
        $filename = 'Template_Kop_Resmi_' . $cleanUnit . '_' . ucfirst($orientation) . '.docx';

        $tempFile = tempnam(sys_get_temp_dir(), 'sppg_docx_') . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Helper membuat markup dokumen template ber-kop untuk cetak print/PDF browser.
     */
    private function generateKopPrintHtml(SettingKopDokumen $config, string $orientation = 'portrait'): string
    {
        $logoKiriSrc = asset(ltrim($config->logo_kiri_url ?: '/images/logo/BGN_LOGO_MAIN.png', '/'));
        $logoKananSrc = asset(ltrim($config->logo_kanan_url ?: '/images/logo/Logo_Yayasan.png', '/'));

        $instansi1 = strtoupper($config->nama_instansi_1 ?? 'SPPG BULELENG SUKASADA TEGALLINGGAH');
        $instansi2 = strtoupper($config->nama_instansi_2 ?? 'YAYASAN PESANTREN MIFTAHUL ULUM');
        $namaUnit = strtoupper($config->nama_unit ?? '');
        $alamat = $config->alamat_lengkap ?? 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali';

        $showUnitLine = (!empty($namaUnit) && $namaUnit !== $instansi1 && $namaUnit !== $instansi2 && !str_contains($instansi1, $namaUnit));

        $contacts = [];
        if ($config->telepon) $contacts[] = 'Telp: ' . $config->telepon;
        if ($config->whatsapp) $contacts[] = 'WA: ' . $config->whatsapp;
        if ($config->email) $contacts[] = 'E-mail: ' . $config->email;
        if ($config->website) $contacts[] = 'Web: ' . $config->website;

        $kontakStr = !empty($contacts) ? implode(' | ', $contacts) : ($config->email ? 'E-mail: ' . $config->email : '');

        $showLogoKanan = ($config->layout_logo === 'dual') && !empty($logoKananSrc);
        $dateNow = date('d F Y');

        $isLandscape = ($orientation === 'landscape');
        $pageSizeCss = $isLandscape ? 'A4 landscape' : 'A4 portrait';
        $pageWidthCss = $isLandscape ? '297mm' : '210mm';
        $unitLinePrint = $showUnitLine ? '<div class="unit-text">' . $namaUnit . '</div>' : '';

        $logoKananHtml = $showLogoKanan ? '<img src="' . $logoKananSrc . '" class="kop-logo" alt="Logo Kanan">' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Dokumen Resmi - {$config->nama_unit}</title>
    <style>
        @page {
            size: {$pageSizeCss};
            margin: 1.5cm 2.0cm 2.0cm 2.0cm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 4px;
        }
        .kop-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .kop-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .instansi-1 {
            font-size: 13.5pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .instansi-2 {
            font-size: 12.5pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 0.5px;
            margin: 2px 0 0 0;
        }
        .unit-text {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin: 2px 0 0 0;
        }
        .alamat-text {
            font-size: 9.5pt;
            text-align: center;
            margin: 4px 0 0 0;
        }
        .kontak-text {
            font-size: 9.5pt;
            text-align: center;
            margin: 2px 0 0 0;
        }
        .border-ganda {
            width: 100%;
            margin-top: 6px;
            margin-bottom: 25px;
        }
        .border-thick {
            border-bottom: 3px solid #000;
            width: 100%;
        }
        .border-thin {
            border-bottom: 1px solid #000;
            width: 100%;
            margin-top: 2px;
        }
        .content {
            font-size: 10.5pt;
            line-height: 1.5;
        }
        .doc-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 2px;
        }
        .doc-number {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 20px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #0f172a; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-family: sans-serif; font-size: 12px; font-weight: bold;">Pratinjau Cetak / Print Lembar Naskah Ber-Kop Resmi</span>
        <button onclick="window.print()" style="background: #0d9488; color: white; border: none; padding: 6px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">Cetak Sekarang</button>
    </div>

    <div style="padding: 10px 0;">
        <table class="kop-table">
            <tr>
                <td style="width: 80px; text-align: center;">
                    <img src="{$logoKiriSrc}" class="kop-logo" alt="Logo Kiri">
                </td>
                <td style="text-align: center; padding: 0 10px;">
                    <div class="instansi-1">{$instansi1}</div>
                    <div class="instansi-2">{$instansi2}</div>
                    {$unitLinePrint}
                    <div class="alamat-text">{$alamat}</div>
                    <div class="kontak-text">{$kontakStr}</div>
                </td>
                <td style="width: 80px; text-align: center;">
                    {$logoKananHtml}
                </td>
            </tr>
        </table>
        <div class="border-ganda">
            <div class="border-thick"></div>
            <div class="border-thin"></div>
        </div>
        <div class="content">
            <div class="doc-title">SURAT PERNYATAAN / DOKUMEN RESMI</div>
            <div class="doc-number">Nomor: B-...../SPPG/...../2026</div>
            <p>Yang bertanda tangan di bawah ini:</p>
            <table style="width: 100%; border-collapse: collapse; margin-left: 15px; margin-bottom: 15px;">
                <tr><td style="width: 120px; padding: 3px 0;">Nama</td><td style="padding: 3px 0;">: ..............................................................</td></tr>
                <tr><td style="padding: 3px 0;">Jabatan</td><td style="padding: 3px 0;">: Kepala Satuan Pelayanan Program Gizi</td></tr>
                <tr><td style="padding: 3px 0;">Unit Kerja</td><td style="padding: 3px 0;">: {$config->nama_unit}</td></tr>
            </table>
            <p style="text-align: justify;">Menyatakan dengan sesungguhnya bahwa naskah dinas ini dibuat sesuai dengan ketentuan tata naskah dinas dan petunjuk teknis Badan Gizi Nasional (BGN).</p>
            <div style="margin-top: 50px; float: right; width: 250px; text-align: center;">
                <div>{$config->kabupaten}, {$dateNow}</div>
                <div style="font-weight: bold; margin-bottom: 60px;">Kepala {$config->nama_unit}</div>
                <div style="font-weight: bold; text-decoration: underline;">( .................................................... )</div>
                <div>NIP. ....................................................</div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
