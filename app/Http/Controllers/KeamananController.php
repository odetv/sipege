<?php

namespace App\Http\Controllers;

use App\Models\MutasiKeamanan;
use App\Models\MutasiKeamananDetail;
use App\Models\MutasiKeamananIzin;
use App\Models\MutasiKeamananSerahTerima;
use App\Models\Petugas;
use App\Models\UnitSppg;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class KeamananController extends Controller
{
    /**
     * Tampilkan Halaman Utama Mutasi & Laporan Keamanan
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();
        $unitId = $unitSppg?->id;

        // Tanggal yang sedang aktif dilihat / diinput (default hari ini)
        $tanggal = $request->input('tanggal', Carbon::today()->toDateString());
        $shift = $request->input('shift', '24 Jam');

        // Daftar Petugas dengan jabatan Keamanan (Khusus 2 Petugas Keamanan)
        $petugasKeamanan = Petugas::where('unit_sppg_id', $unitId)
            ->where(function ($q) {
                $q->whereRaw('LOWER(jabatan) LIKE ?', ['%keamanan%'])
                  ->orWhereRaw('LOWER(jabatan) LIKE ?', ['%security%'])
                  ->orWhereRaw('LOWER(jabatan) LIKE ?', ['%satpam%']);
            })
            ->where('status', 'Aktif')
            ->orderBy('urutan', 'asc')
            ->orderBy('nama', 'asc')
            ->get(['id', 'nama', 'jabatan', 'no_telp']);

        // Fallback cadangan hanya jika belum ada petugas berjabatan keamanan
        if ($petugasKeamanan->isEmpty()) {
            $petugasKeamanan = Petugas::where('unit_sppg_id', $unitId)
                ->where('status', 'Aktif')
                ->orderBy('urutan', 'asc')
                ->orderBy('nama', 'asc')
                ->take(2)
                ->get(['id', 'nama', 'jabatan', 'no_telp']);
        }

        // Ambil data Mutasi Keamanan pada tanggal yang dipilih
        $mutasiAktif = MutasiKeamanan::with(['details.petugas', 'petugas', 'izins.petugas', 'serahTerimas.petugas'])
            ->where('unit_sppg_id', $unitId)
            ->where('tanggal', $tanggal)
            ->where('shift', $shift)
            ->first();

        // Riwayat Mutasi Keamanan (30 hari terakhir atau berpaginasi)
        $riwayatQuery = MutasiKeamanan::with(['petugas', 'details', 'izins'])
            ->where('unit_sppg_id', $unitId);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $riwayatQuery->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        $riwayatList = $riwayatQuery->orderBy('tanggal', 'desc')->paginate(10)->withQueryString();

        // Statistik Ringkas Keamanan Bulan Ini
        $startOfMonth = Carbon::parse($tanggal)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($tanggal)->endOfMonth()->toDateString();

        $bulanMutasi = MutasiKeamanan::with(['details', 'izins'])
            ->where('unit_sppg_id', $unitId)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();

        $totalHariLapor = $bulanMutasi->count();
        $totalCekJam = $bulanMutasi->sum(fn($m) => $m->details->count());
        $totalAman = $bulanMutasi->sum(fn($m) => $m->details->where('kondisi', 'Aman')->count());
        $totalInsiden = $bulanMutasi->sum(fn($m) => $m->details->where('kondisi', '!=', 'Aman')->count());
        $totalIzinKeluar = $bulanMutasi->sum(fn($m) => $m->izins->count());

        // Pejabat SPPG
        $kepalaSppg = Petugas::where('unit_sppg_id', $unitId)
            ->where('jabatan', 'like', '%Kepala%')
            ->value('nama')
            ?? $unitSppg?->nama_kepala
            ?? $user?->nama_lengkap
            ?? 'Kepala SPPG';

        $korlap = Petugas::where('unit_sppg_id', $unitId)
            ->where(function ($q) {
                $q->where('jabatan', 'like', '%Koordinator Lapangan%')
                  ->orWhere('jabatan', 'like', '%Korlap%');
            })
            ->value('nama') ?? 'Koordinator Lapangan';

        // Pastikan token keamanan SPPG tersedia untuk link form mandiri dan monitoring
        if ($unitSppg) {
            $updates = [];
            if (empty($unitSppg->token_keamanan)) {
                $updates['token_keamanan'] = Str::random(24);
            }
            if (empty($unitSppg->token_monitoring)) {
                $updates['token_monitoring'] = Str::random(24);
            }
            if (!empty($updates)) {
                $unitSppg->update($updates);
                $unitSppg->refresh();
            }
        }

        $formPublicUrl = $unitSppg?->token_keamanan
            ? route('keamanan.public-form', ['token' => $unitSppg->token_keamanan])
            : null;

        $monitoringPublicUrl = $unitSppg?->token_monitoring
            ? route('keamanan.public-monitoring', ['token' => $unitSppg->token_monitoring])
            : ($unitSppg?->token_keamanan ? route('keamanan.public-monitoring', ['token' => $unitSppg->token_keamanan]) : null);

        return Inertia::render('Keamanan/Index', [
            'unitSppg' => $unitSppg,
            'selectedTanggal' => $tanggal,
            'selectedShift' => $shift,
            'mutasiAktif' => $mutasiAktif,
            'petugasKeamanan' => $petugasKeamanan,
            'serahTerimasHariIni' => $mutasiAktif?->serahTerimas ?? [],
            'formPublicUrl' => $formPublicUrl,
            'monitoringPublicUrl' => $monitoringPublicUrl,
            'tokenKeamanan' => $unitSppg?->token_keamanan,
            'tokenMonitoring' => $unitSppg?->token_monitoring,
            'riwayatList' => $riwayatList,
            'filters' => $request->only(['tanggal', 'shift', 'start_date', 'end_date']),
            'stats' => [
                'total_hari_lapor' => $totalHariLapor,
                'total_cek_jam' => $totalCekJam,
                'total_aman' => $totalAman,
                'total_insiden' => $totalInsiden,
                'total_izin_keluar' => $totalIzinKeluar,
                'bulan_nama' => Carbon::parse($tanggal)->translatedFormat('F Y'),
            ],
            'pejabatSppg' => [
                'kepala_sppg' => $kepalaSppg,
                'korlap' => $korlap,
            ],
        ]);
    }

    /**
     * Simpan / Perbarui Laporan Mutasi Keamanan Harian beserta Seluruh 24 Baris Log Jam
     */
    public function storeOrUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();
        $unitId = $unitSppg?->id;

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'shift' => ['required', 'string', 'max:50'],
            'petugas_id' => ['nullable', 'exists:petugas,id'],
            'petugas_nama' => ['nullable', 'string', 'max:255'],
            'status_keseluruhan' => ['nullable', 'string', 'max:30'],
            'catatan_serah_terima' => ['nullable', 'string'],
            'details' => ['nullable', 'array'],
            'details.*.jam' => ['required', 'string', 'max:50'],
            'details.*.waktu_cek' => ['nullable'],
            'details.*.petugas_id' => ['nullable'],
            'details.*.petugas_nama' => ['nullable', 'string', 'max:255'],
            'details.*.kondisi' => ['nullable', 'string'],
            'details.*.uraian_kejadian' => ['nullable', 'string'],
            'details.*.data_pengecekan' => ['nullable'],
            'details.*.latitude' => ['nullable', 'numeric'],
            'details.*.longitude' => ['nullable', 'numeric'],
            'details.*.lokasi_nama' => ['nullable', 'string', 'max:255'],
            'details.*.is_reported' => ['nullable', 'boolean'],
        ]);

        // Cari nama petugas jika petugas_id dipilih
        $petugasNama = $validated['petugas_nama'] ?? null;
        if (!empty($validated['petugas_id'])) {
            $p = Petugas::find($validated['petugas_id']);
            if ($p) {
                $petugasNama = $p->nama;
            }
        }

        // Tentukan status keseluruhan berdasarkan details yang terlapor
        $reportedDetails = collect($validated['details'] ?? [])->filter(fn($d) => !empty($d['is_reported']) || !empty($d['waktu_cek']) || !empty($d['data_pengecekan']));
        $hasInsiden = $reportedDetails->contains('kondisi', 'Tidak Aman');
        $statusKeseluruhan = $hasInsiden ? 'Tidak Aman' : ($validated['status_keseluruhan'] ?? 'Aman');

        // Simpan / Update Induk Mutasi
        $mutasi = MutasiKeamanan::updateOrCreate(
            [
                'unit_sppg_id' => $unitId,
                'tanggal' => $validated['tanggal'],
                'shift' => $validated['shift'],
            ],
            [
                'petugas_id' => $validated['petugas_id'] ?? null,
                'petugas_nama' => $petugasNama,
                'status_keseluruhan' => $statusKeseluruhan,
                'catatan_serah_terima' => $validated['catatan_serah_terima'] ?? null,
                'created_by' => $user?->id,
            ]
        );

        // Sinkronisasi Detail Log Baris Jam (Hanya simpan/update baris yang dilaporkan)
        $details = $validated['details'] ?? [];

        foreach ($details as $d) {
            $isReported = !empty($d['is_reported']) || !empty($d['waktu_cek']) || !empty($d['data_pengecekan']) || !empty($d['latitude']);
            if (!$isReported && empty($d['id'])) {
                continue; // Jangan simpan baris kosong yang belum dilaporkan
            }

            $detPetugasNama = $d['petugas_nama'] ?? $petugasNama;
            if (!empty($d['petugas_id'])) {
                $dp = Petugas::find($d['petugas_id']);
                if ($dp) $detPetugasNama = $dp->nama;
            }

            $detailData = [
                'waktu_cek' => !empty($d['waktu_cek']) ? $d['waktu_cek'] : Carbon::now()->format('H:i:s'),
                'petugas_id' => $d['petugas_id'] ?? $mutasi->petugas_id,
                'petugas_nama' => $detPetugasNama,
                'kondisi' => $d['kondisi'] ?: 'Aman',
                'uraian_kejadian' => $d['uraian_kejadian'] ?? 'Situasi aman terkendali, area steril dan terpantau normal.',
                'data_pengecekan' => $d['data_pengecekan'] ?? null,
                'latitude' => $d['latitude'] ?? null,
                'longitude' => $d['longitude'] ?? null,
                'lokasi_nama' => $d['lokasi_nama'] ?? 'Pos Keamanan',
            ];

            if (!empty($d['id'])) {
                MutasiKeamananDetail::where('id', $d['id'])->update($detailData);
            } else {
                MutasiKeamananDetail::create(array_merge($detailData, [
                    'mutasi_keamanan_id' => $mutasi->id,
                    'jam' => $d['jam'],
                ]));
            }
        }

        return redirect()->route('keamanan.index', [
            'tanggal' => $validated['tanggal'],
            'shift' => $validated['shift'],
        ])->with('success', "Laporan Mutasi Keamanan tanggal {$validated['tanggal']} berhasil disimpan.");
    }

    /**
     * Tambah / Simpan 1 Baris Pengecekan Jam Cepat (Quick Hourly Check)
     */
    public function quickCheck(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();
        $unitId = $unitSppg?->id;

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'shift' => ['required', 'string'],
            'jam' => ['required', 'string', 'max:50'],
            'petugas_id' => ['nullable'],
            'kondisi' => ['required', 'string', 'in:Aman,Tidak Aman'],
            'uraian_kejadian' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'lokasi_nama' => ['nullable', 'string', 'max:255'],
        ]);

        $petugas = !empty($validated['petugas_id']) ? Petugas::find($validated['petugas_id']) : null;
        $petugasNama = $petugas?->nama ?? 'Petugas Keamanan';

        $mutasi = MutasiKeamanan::firstOrCreate(
            [
                'unit_sppg_id' => $unitId,
                'tanggal' => $validated['tanggal'],
                'shift' => $validated['shift'],
            ],
            [
                'petugas_id' => $validated['petugas_id'] ?? null,
                'petugas_nama' => $petugasNama,
                'status_keseluruhan' => $validated['kondisi'],
                'created_by' => $user?->id,
            ]
        );

        // Update or create detail untuk rentang jam tersebut
        MutasiKeamananDetail::updateOrCreate(
            [
                'mutasi_keamanan_id' => $mutasi->id,
                'jam' => $validated['jam'],
            ],
            [
                'waktu_cek' => Carbon::now()->format('H:i:s'),
                'petugas_id' => $validated['petugas_id'] ?? $mutasi->petugas_id,
                'petugas_nama' => $petugasNama,
                'kondisi' => $validated['kondisi'],
                'uraian_kejadian' => $validated['uraian_kejadian'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'lokasi_nama' => $validated['lokasi_nama'] ?? 'Pos Keamanan',
            ]
        );

        return back()->with('success', "Pengecekan {$validated['jam']} berhasil dicatat.");
    }

    /**
     * Helper Parse Waktu dan Tanggal Menjadi Objek Carbon
     */
    protected function parseTimeCarbon(?string $tanggal, ?string $waktuStr): ?Carbon
    {
        if (!$tanggal || !$waktuStr) return null;
        $timeOnly = trim(preg_replace('/[^0-9:\.]/', '', $waktuStr));
        $timeOnly = str_replace('.', ':', $timeOnly);
        $parts = explode(':', $timeOnly);
        $h = str_pad($parts[0] ?? '0', 2, '0', STR_PAD_LEFT);
        $m = str_pad($parts[1] ?? '0', 2, '0', STR_PAD_LEFT);
        $s = str_pad($parts[2] ?? '0', 2, '0', STR_PAD_LEFT);
        try {
            return Carbon::parse("{$tanggal} {$h}:{$m}:{$s}");
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Simpan Catatan Izin Keluar Penjagaan (Termasuk Fase Izin, Keluar & Tujuan)
     */
    public function storeIzin(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();
        $unitId = $unitSppg?->id;

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'shift' => ['nullable', 'string'],
            'petugas_id' => ['required', 'exists:petugas,id'],
            'keperluan' => ['required', 'string'],
            'tanggal_izin' => ['nullable', 'date'],
            'waktu_izin' => ['nullable', 'string', 'max:20'],
            'lokasi_izin' => ['nullable', 'string', 'max:255'],
            'lat_izin' => ['nullable', 'numeric'],
            'lng_izin' => ['nullable', 'numeric'],
            'tanggal_keluar' => ['required', 'date'],
            'waktu_keluar' => ['required', 'string', 'max:20'],
            'lokasi_awal' => ['nullable', 'string', 'max:255'],
            'lat_awal' => ['nullable', 'numeric'],
            'lng_awal' => ['nullable', 'numeric'],
            'lokasi_tujuan' => ['required', 'string', 'max:255'],
            'lat_tujuan' => ['nullable', 'numeric'],
            'lng_tujuan' => ['nullable', 'numeric'],
            'waktu_sampai_tujuan' => ['nullable', 'string', 'max:20'],
        ]);

        $petugas = Petugas::findOrFail($validated['petugas_id']);

        // Pastikan induk mutasi keamanan ada
        $mutasi = MutasiKeamanan::firstOrCreate(
            [
                'unit_sppg_id' => $unitId,
                'tanggal' => $validated['tanggal'],
                'shift' => $validated['shift'] ?? '24 Jam',
            ],
            [
                'petugas_id' => $petugas->id,
                'petugas_nama' => $petugas->nama,
                'status_keseluruhan' => 'Aman',
                'created_by' => $user?->id,
            ]
        );

        $tglIzin = $validated['tanggal_izin'] ?? $validated['tanggal_keluar'];
        $wktIzin = $validated['waktu_izin'] ?? $validated['waktu_keluar'];
        $lokIzin = $validated['lokasi_izin'] ?? ($validated['lokasi_awal'] ?? 'Pos Penjagaan SPPG');
        $latIzin = $validated['lat_izin'] ?? ($validated['lat_awal'] ?? null);
        $lngIzin = $validated['lng_izin'] ?? ($validated['lng_awal'] ?? null);

        MutasiKeamananIzin::create([
            'mutasi_keamanan_id' => $mutasi->id,
            'petugas_id' => $petugas->id,
            'petugas_nama' => $petugas->nama,
            'keperluan' => $validated['keperluan'],
            'tanggal_izin' => $tglIzin,
            'waktu_izin' => $wktIzin,
            'lokasi_izin' => $lokIzin,
            'lat_izin' => $latIzin,
            'lng_izin' => $lngIzin,
            'tanggal_keluar' => $validated['tanggal_keluar'],
            'waktu_keluar' => $validated['waktu_keluar'],
            'lokasi_awal' => $validated['lokasi_awal'] ?? 'Pos Penjagaan SPPG',
            'lat_awal' => $validated['lat_awal'] ?? null,
            'lng_awal' => $validated['lng_awal'] ?? null,
            'lokasi_tujuan' => $validated['lokasi_tujuan'],
            'lat_tujuan' => $validated['lat_tujuan'] ?? null,
            'lng_tujuan' => $validated['lng_tujuan'] ?? null,
            'waktu_sampai_tujuan' => $validated['waktu_sampai_tujuan'] ?? null,
            'status' => 'Sedang Keluar',
        ]);

        return back()->with('success', "Izin keluar penjagaan atas nama {$petugas->nama} berhasil dicatat.");
    }

    /**
     * Konfirmasi Kembali ke Penjagaan & Pelaporan Izin (Hitung Durasi Otomatis)
     */
    public function updateIzinKembali(Request $request, $id): RedirectResponse
    {
        $izin = MutasiKeamananIzin::findOrFail($id);

        $validated = $request->validate([
            'tanggal_kembali' => ['required', 'date'],
            'waktu_kembali' => ['required', 'string', 'max:20'],
            'lokasi_kembali' => ['nullable', 'string', 'max:255'],
            'lat_kembali' => ['nullable', 'numeric'],
            'lng_kembali' => ['nullable', 'numeric'],
            'laporan_kegiatan' => ['required', 'string'],
        ]);

        // Hitung durasi waktu (Izin -> Keluar -> Kembali)
        $tglIzin = $izin->tanggal_izin?->toDateString() ?? $izin->tanggal_keluar?->toDateString();
        $wktIzin = $izin->waktu_izin ?? $izin->waktu_keluar;
        $cIzin = $this->parseTimeCarbon($tglIzin, $wktIzin);

        $tglKeluar = $izin->tanggal_keluar?->toDateString();
        $wktKeluar = $izin->waktu_keluar;
        $cKeluar = $this->parseTimeCarbon($tglKeluar, $wktKeluar);

        $cKembali = $this->parseTimeCarbon($validated['tanggal_kembali'], $validated['waktu_kembali']);

        $durasiKeluar = ($cKeluar && $cKembali) ? abs($cKembali->diffInMinutes($cKeluar)) : null;
        $durasiTotal = ($cIzin && $cKembali) ? abs($cKembali->diffInMinutes($cIzin)) : $durasiKeluar;

        $izin->update([
            'tanggal_kembali' => $validated['tanggal_kembali'],
            'waktu_kembali' => $validated['waktu_kembali'],
            'lokasi_kembali' => $validated['lokasi_kembali'] ?? 'Pos Penjagaan SPPG',
            'lat_kembali' => $validated['lat_kembali'] ?? null,
            'lng_kembali' => $validated['lng_kembali'] ?? null,
            'durasi_keluar_menit' => $durasiKeluar,
            'durasi_total_menit' => $durasiTotal,
            'status' => 'Sudah Kembali',
            'laporan_kegiatan' => $validated['laporan_kegiatan'],
        ]);

        return back()->with('success', "Petugas {$izin->petugas_nama} telah dikonfirmasi kembali ke penjagaan.");
    }

    /**
     * Update Data Izin Keluar Penjagaan
     */
    public function updateIzin(Request $request, $id): RedirectResponse
    {
        $izin = MutasiKeamananIzin::findOrFail($id);

        $validated = $request->validate([
            'petugas_id' => ['required', 'exists:petugas,id'],
            'keperluan' => ['required', 'string'],
            'lokasi_tujuan' => ['required', 'string', 'max:255'],
            'waktu_keluar' => ['required', 'string', 'max:20'],
            'lokasi_awal' => ['nullable', 'string', 'max:255'],
            'lat_awal' => ['nullable', 'numeric'],
            'lng_awal' => ['nullable', 'numeric'],
            'lat_tujuan' => ['nullable', 'numeric'],
            'lng_tujuan' => ['nullable', 'numeric'],
            'status' => ['required', 'in:Sedang Keluar,Sudah Kembali'],
            'waktu_kembali' => ['nullable', 'string', 'max:20'],
            'lokasi_kembali' => ['nullable', 'string', 'max:255'],
            'lat_kembali' => ['nullable', 'numeric'],
            'lng_kembali' => ['nullable', 'numeric'],
            'laporan_kegiatan' => ['nullable', 'string'],
        ]);

        $petugas = Petugas::findOrFail($validated['petugas_id']);

        $durasiKeluar = $izin->durasi_keluar_menit;
        if ($validated['status'] === 'Sudah Kembali' && !empty($validated['waktu_kembali'])) {
            $tglKeluar = $izin->tanggal_keluar?->toDateString() ?? date('Y-m-d');
            $cKeluar = $this->parseTimeCarbon($tglKeluar, $validated['waktu_keluar']);
            $tglKembali = $izin->tanggal_kembali?->toDateString() ?? $tglKeluar;
            $cKembali = $this->parseTimeCarbon($tglKembali, $validated['waktu_kembali']);
            if ($cKeluar && $cKembali) {
                $durasiKeluar = abs($cKembali->diffInMinutes($cKeluar));
            }
        }

        $izin->update([
            'petugas_id' => $petugas->id,
            'petugas_nama' => $petugas->nama,
            'keperluan' => $validated['keperluan'],
            'lokasi_tujuan' => $validated['lokasi_tujuan'],
            'waktu_keluar' => $validated['waktu_keluar'],
            'lokasi_awal' => $validated['lokasi_awal'] ?? 'Pos Penjagaan SPPG',
            'lat_awal' => $validated['lat_awal'] ?? null,
            'lng_awal' => $validated['lng_awal'] ?? null,
            'lat_tujuan' => $validated['lat_tujuan'] ?? null,
            'lng_tujuan' => $validated['lng_tujuan'] ?? null,
            'status' => $validated['status'],
            'waktu_kembali' => $validated['waktu_kembali'] ?? null,
            'lokasi_kembali' => $validated['lokasi_kembali'] ?? null,
            'lat_kembali' => $validated['lat_kembali'] ?? null,
            'lng_kembali' => $validated['lng_kembali'] ?? null,
            'laporan_kegiatan' => $validated['laporan_kegiatan'] ?? null,
            'durasi_keluar_menit' => $durasiKeluar,
        ]);

        return back()->with('success', "Data izin keluar {$petugas->nama} berhasil diperbarui.");
    }

    /**
     * Hapus Data Izin Keluar
     */
    public function destroyIzin($id): RedirectResponse
    {
        $izin = MutasiKeamananIzin::findOrFail($id);
        $nama = $izin->petugas_nama;
        $izin->delete();

        return back()->with('success', "Catatan izin keluar {$nama} berhasil dihapus.");
    }

    /**
     * Update Data Serah Terima Jaga Keamanan
     */
    public function updateSerahTerima(Request $request, $id): RedirectResponse
    {
        $serahTerima = MutasiKeamananSerahTerima::findOrFail($id);

        $validated = $request->validate([
            'petugas_id' => ['required', 'exists:petugas,id'],
            'jenis' => ['required', 'in:Masuk Jaga,Lepas Jaga'],
            'waktu' => ['required', 'string', 'max:20'],
            'catatan' => ['nullable', 'string'],
            'lokasi_nama' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $petugas = Petugas::findOrFail($validated['petugas_id']);

        $serahTerima->update([
            'petugas_id' => $petugas->id,
            'petugas_nama' => $petugas->nama,
            'jenis' => $validated['jenis'],
            'waktu' => $validated['waktu'],
            'catatan' => $validated['catatan'] ?? null,
            'lokasi_nama' => $validated['lokasi_nama'] ?? 'Pos Penjagaan SPPG',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return back()->with('success', "Laporan serah terima {$petugas->nama} berhasil diperbarui.");
    }

    /**
     * Hapus Data Serah Terima Jaga Keamanan
     */
    public function destroySerahTerima($id): RedirectResponse
    {
        $serahTerima = MutasiKeamananSerahTerima::findOrFail($id);
        $nama = $serahTerima->petugas_nama ?: 'Petugas';
        $serahTerima->delete();

        return back()->with('success', "Laporan serah terima {$nama} berhasil dihapus.");
    }

    /**
     * Hapus 1 Log Detail Jam
     */
    public function destroyDetail($id): RedirectResponse
    {
        $detail = MutasiKeamananDetail::findOrFail($id);
        $jam = $detail->jam;
        $detail->delete();

        return back()->with('success', "Log pengecekan {$jam} berhasil direset.");
    }

    /**
     * Hapus Seluruh Mutasi Tanggal Tersebut
     */
    public function destroy($id): RedirectResponse
    {
        $mutasi = MutasiKeamanan::findOrFail($id);
        $tgl = $mutasi->tanggal->format('d/m/Y');
        $mutasi->delete();

        return redirect()->route('keamanan.index')->with('success', "Laporan mutasi keamanan tanggal {$tgl} berhasil dihapus.");
    }

    /**
     * Generate Ulang Token Link Publik Keamanan
     */
    public function regenerateToken(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        if ($unitSppg) {
            $oldTokens = $unitSppg->old_tokens_keamanan ?? [];
            if (!empty($unitSppg->token_keamanan)) {
                $oldTokens[] = $unitSppg->token_keamanan;
            }

            $unitSppg->update([
                'token_keamanan' => Str::random(24),
                'old_tokens_keamanan' => array_values(array_unique($oldTokens)),
            ]);
        }

        return back()->with('success', "Link formulir input tim keamanan berhasil diperbarui! Link lama otomatis dinonaktifkan.");
    }

    /**
     * Generate Ulang Token Link Monitoring Keamanan Realtime
     */
    public function regenerateMonitoringToken(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        if ($unitSppg) {
            $oldTokens = $unitSppg->old_tokens_monitoring ?? [];
            if (!empty($unitSppg->token_monitoring)) {
                $oldTokens[] = $unitSppg->token_monitoring;
            }

            $unitSppg->update([
                'token_monitoring' => Str::random(24),
                'old_tokens_monitoring' => array_values(array_unique($oldTokens)),
            ]);
        }

        return back()->with('success', "Link monitoring keamanan realtime berhasil diperbarui! Link lama otomatis dinonaktifkan.");
    }

    /**
     * Tampilan Formulir Mandiri Tim Keamanan (Akses Cepat Smartphone / Google Form Style)
     */
    public function publicForm(string $token): Response
    {
        $unitSppg = UnitSppg::where('token_keamanan', $token)->first();

        // Jika token tidak ditemukan di token aktif, periksa apakah token lama atau tidak valid
        if (!$unitSppg) {
            $unitOld = UnitSppg::whereJsonContains('old_tokens_keamanan', $token)->first() ?? UnitSppg::first();
            return Inertia::render('Keamanan/LinkBerubah', [
                'unitSppg' => $unitOld?->only(['id', 'nama', 'kode_sppg', 'alamat_lengkap']),
                'isOldToken' => true,
            ]);
        }

        $unitId = $unitSppg->id;
        $nowWita = Carbon::now('Asia/Makassar');
        $tanggal = $nowWita->toDateString();

        // 2 Petugas Keamanan Resmi
        $petugasKeamanan = Petugas::where('unit_sppg_id', $unitId)
            ->where(function ($q) {
                $q->whereRaw('LOWER(jabatan) LIKE ?', ['%keamanan%'])
                  ->orWhereRaw('LOWER(jabatan) LIKE ?', ['%security%'])
                  ->orWhereRaw('LOWER(jabatan) LIKE ?', ['%satpam%']);
            })
            ->where('status', 'Aktif')
            ->orderBy('urutan', 'asc')
            ->orderBy('nama', 'asc')
            ->get(['id', 'nama', 'jabatan', 'no_telp']);

        if ($petugasKeamanan->isEmpty()) {
            $petugasKeamanan = Petugas::where('unit_sppg_id', $unitId)
                ->where('status', 'Aktif')
                ->orderBy('urutan', 'asc')
                ->orderBy('nama', 'asc')
                ->take(2)
                ->get(['id', 'nama', 'jabatan', 'no_telp']);
        }

        // Seluruh Petugas SPPG Aktif (untuk kendala/catatan personil SPPG)
        $petugasSppgAktif = Petugas::where('unit_sppg_id', $unitId)
            ->where('status', 'Aktif')
            ->orderBy('urutan', 'asc')
            ->orderBy('nama', 'asc')
            ->get(['id', 'nama', 'jabatan', 'no_telp']);

        // Cari rentang jam otomatis saat ini (WITA / UTC+8)
        $currentHour = $nowWita->hour;
        $h1 = str_pad($currentHour, 2, '0', STR_PAD_LEFT);
        $h2 = str_pad(($currentHour + 1) % 24, 2, '0', STR_PAD_LEFT);
        $defaultIntervalJam = "{$h1}.00 - {$h2}.00 WITA";

        // Mutasi aktif hari ini
        $mutasiAktif = MutasiKeamanan::with(['details', 'izins', 'serahTerimas'])
            ->where('unit_sppg_id', $unitId)
            ->where('tanggal', $tanggal)
            ->first();

        return Inertia::render('Keamanan/PublicForm', [
            'unitSppg' => $unitSppg->only(['id', 'nama', 'kode_sppg', 'alamat_lengkap', 'latitude', 'longitude']),
            'token' => $token,
            'todayDate' => $tanggal,
            'defaultIntervalJam' => $defaultIntervalJam,
            'serverTimeWita' => $nowWita->format('H:i:s'),
            'petugasKeamanan' => $petugasKeamanan,
            'petugasSppgAktif' => $petugasSppgAktif,
            'mutasiAktif' => $mutasiAktif,
        ]);
    }

    /**
     * Simpan Laporan Pengecekan Jam dari Link Mandiri Tim Keamanan
     * Jam & waktu otomatis realtime WITA dan otomatis dicocokkan ke baris jam yang berkesesuaian
     */
    public function publicStoreJam(Request $request, string $token): RedirectResponse
    {
        $unitSppg = UnitSppg::where('token_keamanan', $token)->first();
        if (!$unitSppg) {
            return redirect()->route('keamanan.public-form', ['token' => $token]);
        }
        $unitId = $unitSppg->id;

        // Validasi: Petugas, Koordinat GPS, dan Data Pengecekan
        $validated = $request->validate([
            'petugas_id' => ['required', 'exists:petugas,id'],
            'lokasi_nama' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'data_pengecekan' => ['required', 'array'],
            'data_pengecekan.catatan_tambahan' => ['required', 'string'],
        ], [
            'petugas_id.required' => 'Nama petugas yang bertugas jaga wajib dipilih.',
            'lokasi_nama.required' => 'Tempat Posisi Pengecekan Terakhir wajib diisi.',
            'latitude.required' => 'Titik koordinat lokasi GPS wajib direkam dengan mengklik tombol "Ambil Lokasi".',
            'longitude.required' => 'Titik koordinat lokasi GPS wajib direkam dengan mengklik tombol "Ambil Lokasi".',
            'data_pengecekan.required' => 'Kondisi lingkungan SPPG wajib diisi.',
            'data_pengecekan.catatan_tambahan.required' => 'Catatan Tambahan wajib diisi.',
        ]);

        $petugas = Petugas::findOrFail($validated['petugas_id']);

        $dataPengecekan = $request->input('data_pengecekan', []);
        $cuaca = $dataPengecekan['cuaca'] ?? 'Cerah';
        $pengecekanUmum = $dataPengecekan['pengecekan_umum'] ?? [];
        $lingkunganKerja = $dataPengecekan['lingkungan_kerja'] ?? [];
        $catatanTambahan = trim($dataPengecekan['catatan_tambahan'] ?? '');

        // Evaluasi apakah terdapat item Tidak Aman / Ada Kejadian
        $insidenList = [];
        foreach ($pengecekanUmum as $item) {
            $label = $item['label'] ?? '';
            $status = $item['status'] ?? '';
            $uraian = trim($item['uraian'] ?? '');
            if ($status === 'Tidak Aman') {
                $insidenList[] = "{$label} (Tidak Aman" . ($uraian ? ": {$uraian}" : "") . ")";
            } elseif ($status === 'Ada') {
                $insidenList[] = "{$label} (Ada" . ($uraian ? ": {$uraian}" : "") . ")";
            }
        }

        foreach ($lingkunganKerja as $item) {
            $label = $item['label'] ?? '';
            $status = $item['status'] ?? '';
            $uraian = trim($item['uraian'] ?? '');
            if ($status === 'Tidak Aman') {
                $insidenList[] = "{$label} (Tidak Aman" . ($uraian ? ": {$uraian}" : "") . ")";
            }
        }

        $kondisi = count($insidenList) > 0 ? 'Tidak Aman' : 'Aman';

        // Susun ringkasan uraian untuk display tabel / cetak
        $uraianParts = [];
        $uraianParts[] = "Cuaca: {$cuaca}";
        if (!empty($insidenList)) {
            $uraianParts[] = "Perhatian: " . implode(', ', $insidenList);
        } else {
            $uraianParts[] = "Kondisi Lingkungan SPPG: Seluruh item aman & kondusif";
        }
        if (!empty($catatanTambahan)) {
            $uraianParts[] = "Catatan: {$catatanTambahan}";
        }
        $ringkasanUraian = implode(' | ', $uraianParts);

        // Waktu otomatis realtime WITA (Asia/Makassar)
        $now = Carbon::now('Asia/Makassar');
        $tanggal = $now->toDateString();
        $currentHour = $now->hour;
        $h1 = str_pad($currentHour, 2, '0', STR_PAD_LEFT);
        $h2 = str_pad(($currentHour + 1) % 24, 2, '0', STR_PAD_LEFT);
        $matchingJam = "{$h1}.00 - {$h2}.00 WITA";
        $waktuCek = $now->format('H:i:s');

        // Pastikan Induk Mutasi Hari ini ada
        $mutasi = MutasiKeamanan::firstOrCreate(
            [
                'unit_sppg_id' => $unitId,
                'tanggal' => $tanggal,
                'shift' => '24 Jam',
            ],
            [
                'petugas_id' => $petugas->id,
                'petugas_nama' => $petugas->nama,
                'status_keseluruhan' => $kondisi,
            ]
        );

        // Jika kondisi 'Tidak Aman', update status induk
        if ($kondisi === 'Tidak Aman') {
            $mutasi->update(['status_keseluruhan' => 'Tidak Aman']);
        }

        // Simpan setiap laporan pengecekan sebagai baris baru (bisa > 1 laporan dalam rentang jam yang sama)
        MutasiKeamananDetail::create([
            'mutasi_keamanan_id' => $mutasi->id,
            'jam' => $matchingJam,
            'waktu_cek' => $waktuCek,
            'petugas_id' => $petugas->id,
            'petugas_nama' => $petugas->nama,
            'kondisi' => $kondisi,
            'uraian_kejadian' => $ringkasanUraian,
            'data_pengecekan' => $dataPengecekan,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'lokasi_nama' => $validated['lokasi_nama'],
        ]);

        return back()->with('success', "Laporan jam {$matchingJam} (Pukul {$waktuCek} WITA) oleh {$petugas->nama} berhasil otomatis tersimpan ke Buku Mutasi!");
    }

    /**
     * Simpan Laporan Keluar Jaga dari Link Mandiri Smartphone (Waktu Otomatis Realtime WITA)
     */
    public function publicStoreIzin(Request $request, string $token): RedirectResponse
    {
        $unitSppg = UnitSppg::where('token_keamanan', $token)->first();
        if (!$unitSppg) {
            return redirect()->route('keamanan.public-form', ['token' => $token]);
        }
        $unitId = $unitSppg->id;

        $validated = $request->validate([
            'petugas_id' => ['required', 'exists:petugas,id'],
            'lokasi_tujuan' => ['required', 'string', 'max:255'],
            'keperluan' => ['required', 'string', 'min:3'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'lat_awal' => ['nullable', 'numeric'],
            'lng_awal' => ['nullable', 'numeric'],
            'lat_tujuan' => ['nullable', 'numeric'],
            'lng_tujuan' => ['nullable', 'numeric'],
            'lat_kembali' => ['nullable', 'numeric'],
            'lng_kembali' => ['nullable', 'numeric'],
        ], [
            'petugas_id.required' => 'Nama Petugas Jaga wajib dipilih.',
            'lokasi_tujuan.required' => 'Tempat Tujuan Ijin wajib diisi.',
            'keperluan.required' => 'Keperluan Ijin Keluar wajib diisi.',
        ]);

        $lat = $validated['latitude'] ?? $validated['lat_awal'] ?? $validated['lat_tujuan'] ?? null;
        $lng = $validated['longitude'] ?? $validated['lng_awal'] ?? $validated['lng_tujuan'] ?? null;

        if ($lat === null || $lng === null) {
            return back()->withErrors(['latitude' => 'Titik Lokasi Pengecekan Terakhir (koordinat GPS) wajib diambil.']);
        }

        $petugas = Petugas::findOrFail($validated['petugas_id']);
        $now = Carbon::now('Asia/Makassar');
        $tanggal = $now->toDateString();
        $waktu = $now->format('H.i') . ' WITA';

        $mutasi = MutasiKeamanan::firstOrCreate(
            [
                'unit_sppg_id' => $unitId,
                'tanggal' => $tanggal,
                'shift' => '24 Jam',
            ],
            [
                'petugas_id' => $petugas->id,
                'petugas_nama' => $petugas->nama,
                'status_keseluruhan' => 'Aman',
            ]
        );

        $hasKembali = !empty($validated['lat_kembali']) && !empty($validated['lng_kembali']);
        $status = $hasKembali ? 'Sudah Kembali' : 'Sedang Keluar';

        MutasiKeamananIzin::create([
            'mutasi_keamanan_id' => $mutasi->id,
            'petugas_id' => $petugas->id,
            'petugas_nama' => $petugas->nama,
            'keperluan' => $validated['keperluan'],
            'tanggal_izin' => $tanggal,
            'waktu_izin' => $waktu,
            'lokasi_izin' => 'Pos Penjagaan SPPG',
            'lat_izin' => $lat,
            'lng_izin' => $lng,
            'tanggal_keluar' => $tanggal,
            'waktu_keluar' => $waktu,
            'lokasi_awal' => 'Pos Penjagaan SPPG',
            'lat_awal' => $lat,
            'lng_awal' => $lng,
            'lokasi_tujuan' => $validated['lokasi_tujuan'],
            'lat_tujuan' => $lat,
            'lng_tujuan' => $lng,
            'tanggal_kembali' => $hasKembali ? $tanggal : null,
            'waktu_kembali' => $hasKembali ? $waktu : null,
            'lokasi_kembali' => 'Pos Penjagaan SPPG',
            'lat_kembali' => $validated['lat_kembali'] ?? null,
            'lng_kembali' => $validated['lng_kembali'] ?? null,
            'status' => $status,
            'laporan_kegiatan' => $hasKembali ? 'Telah kembali ke pos penjagaan.' : "Petugas izin keluar menuju {$validated['lokasi_tujuan']} untuk keperluan: {$validated['keperluan']}.",
        ]);

        return back()->with('success', "Laporan Keluar Jaga atas nama {$petugas->nama} pada pukul {$waktu} berhasil dicatat!");
    }

    /**
     * Simpan Laporan Sedang Diluar dari Link Mandiri Smartphone (Tab Sedang Diluar)
     */
    public function publicStoreSedangDiluar(Request $request, string $token): RedirectResponse
    {
        $unitSppg = UnitSppg::where('token_keamanan', $token)->first();
        if (!$unitSppg) {
            abort(404, 'Token formulir keamanan tidak valid.');
        }
        $unitId = $unitSppg->id;

        $validated = $request->validate([
            'petugas_id' => ['required', 'exists:petugas,id'],
            'lokasi_nama' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ], [
            'petugas_id.required' => 'Nama Petugas Keamanan wajib dipilih.',
            'latitude.required' => 'Titik Lokasi Pengecekan Terakhir (koordinat GPS) wajib diambil.',
            'longitude.required' => 'Titik Lokasi Pengecekan Terakhir (koordinat GPS) wajib diambil.',
        ]);

        $petugas = Petugas::findOrFail($validated['petugas_id']);
        $petugasNama = $petugas->nama;
        $lokasiNama = !empty($validated['lokasi_nama']) ? trim($validated['lokasi_nama']) : 'Sedang di Luar Pos SPPG';

        $now = Carbon::now('Asia/Makassar');
        $today = $now->toDateString();
        $waktuStr = $now->format('H.i') . ' WITA';

        // 1. Dapatkan / buat Mutasi Keamanan aktif hari ini
        $mutasi = MutasiKeamanan::firstOrCreate(
            [
                'unit_sppg_id' => $unitId,
                'tanggal' => $today,
                'shift' => '24 Jam',
            ],
            [
                'petugas_id' => $petugas->id,
                'petugas_nama' => $petugasNama,
                'status_keseluruhan' => 'Aman',
            ]
        );

        // 2. Cek apakah ada catatan izin keluar petugas ini yang masih 'Sedang Keluar'
        $izinKeluar = MutasiKeamananIzin::where('mutasi_keamanan_id', $mutasi->id)
            ->where('petugas_id', $petugas->id)
            ->where('status', 'Sedang Keluar')
            ->latest()
            ->first();

        if ($izinKeluar) {
            $catatanBaru = ($izinKeluar->laporan_kegiatan ? $izinKeluar->laporan_kegiatan . "\n" : '') .
                "[{$waktuStr}] Checkpoint Sedang Diluar di: {$lokasiNama}.";

            $izinKeluar->update([
                'lokasi_tujuan' => $lokasiNama,
                'lat_tujuan' => $validated['latitude'],
                'lng_tujuan' => $validated['longitude'],
                'laporan_kegiatan' => $catatanBaru,
            ]);
        } else {
            MutasiKeamananIzin::create([
                'mutasi_keamanan_id' => $mutasi->id,
                'petugas_id' => $petugas->id,
                'petugas_nama' => $petugasNama,
                'keperluan' => 'Patroli / bertugas di lingkungan luar SPPG',
                'tanggal_izin' => $today,
                'waktu_izin' => $waktuStr,
                'lokasi_izin' => 'Pos Penjagaan SPPG',
                'lat_izin' => $validated['latitude'],
                'lng_izin' => $validated['longitude'],
                'tanggal_keluar' => $today,
                'waktu_keluar' => $waktuStr,
                'lokasi_awal' => 'Pos Penjagaan SPPG',
                'lat_awal' => $validated['latitude'],
                'lng_awal' => $validated['longitude'],
                'lokasi_tujuan' => $lokasiNama,
                'lat_tujuan' => $validated['latitude'],
                'lng_tujuan' => $validated['longitude'],
                'status' => 'Sedang Keluar',
                'laporan_kegiatan' => "[{$waktuStr}] Laporan posisi petugas sedang di luar: {$lokasiNama}.",
            ]);
        }

        return redirect()->route('keamanan.public-form', ['token' => $token])
            ->with('success', "Laporan Sedang Diluar atas nama {$petugasNama} berhasil dicatat!");
    }

    /**
     * Konfirmasi Kembali ke Penjagaan dari Link Mandiri Smartphone (Waktu Otomatis Realtime WITA)
     */
    public function publicKembaliIzin(Request $request, string $token, $id): RedirectResponse
    {
        $unitSppg = UnitSppg::where('token_keamanan', $token)->first();
        if (!$unitSppg) {
            return redirect()->route('keamanan.public-form', ['token' => $token]);
        }

        $izin = MutasiKeamananIzin::where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'lokasi_kembali' => ['required', 'string', 'max:255'],
            'lat_kembali' => ['required', 'numeric'],
            'lng_kembali' => ['required', 'numeric'],
            'laporan_kegiatan' => ['required', 'string', 'min:3'],
        ], [
            'lokasi_kembali.required' => 'Lokasi kembali wajib diisi.',
            'lat_kembali.required' => 'Titik koordinat kembali wajib diambil (Ambil Lokasi).',
            'lng_kembali.required' => 'Titik koordinat kembali wajib diambil (Ambil Lokasi).',
            'laporan_kegiatan.required' => 'Laporan hasil kegiatan/izin keluar wajib diisi.',
        ]);

        $now = Carbon::now('Asia/Makassar');
        $tglKembali = $now->toDateString();
        $wktKembali = $now->format('H.i') . ' WITA';

        $tglIzin = $izin->tanggal_izin?->toDateString() ?? $izin->tanggal_keluar?->toDateString();
        $wktIzin = $izin->waktu_izin ?? $izin->waktu_keluar;
        $cIzin = $this->parseTimeCarbon($tglIzin, $wktIzin);

        $tglKeluar = $izin->tanggal_keluar?->toDateString();
        $wktKeluar = $izin->waktu_keluar;
        $cKeluar = $this->parseTimeCarbon($tglKeluar, $wktKeluar);

        $cKembali = $this->parseTimeCarbon($tglKembali, $wktKembali);

        $durasiKeluar = ($cKeluar && $cKembali) ? abs($cKembali->diffInMinutes($cKeluar)) : null;
        $durasiTotal = ($cIzin && $cKembali) ? abs($cKembali->diffInMinutes($cIzin)) : $durasiKeluar;

        $izin->update([
            'tanggal_kembali' => $tglKembali,
            'waktu_kembali' => $wktKembali,
            'lokasi_kembali' => $validated['lokasi_kembali'],
            'lat_kembali' => $validated['lat_kembali'],
            'lng_kembali' => $validated['lng_kembali'],
            'durasi_keluar_menit' => $durasiKeluar,
            'durasi_total_menit' => $durasiTotal,
            'status' => 'Sudah Kembali',
            'laporan_kegiatan' => $validated['laporan_kegiatan'],
        ]);

        return back()->with('success', "Petugas {$izin->petugas_nama} telah dikonfirmasi kembali ke pos penjagaan pada pukul {$wktKembali}!");
    }

    /**
     * Simpan Laporan Kembali Jaga dari Link Mandiri Smartphone (Tab Kembali Jaga)
     */
    public function publicStoreKembaliJaga(Request $request, string $token): RedirectResponse
    {
        $unitSppg = UnitSppg::where('token_keamanan', $token)->first();
        if (!$unitSppg) {
            abort(404, 'Token formulir keamanan tidak valid.');
        }
        $unitId = $unitSppg->id;

        $validated = $request->validate([
            'petugas_id' => ['required', 'exists:petugas,id'],
            'lokasi_nama' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ], [
            'petugas_id.required' => 'Nama Petugas Jaga wajib dipilih.',
            'lokasi_nama.required' => 'Tempat Posisi Pengecekan Terakhir wajib diisi.',
            'latitude.required' => 'Titik Lokasi Pengecekan Terakhir (koordinat GPS) wajib diambil.',
            'longitude.required' => 'Titik Lokasi Pengecekan Terakhir (koordinat GPS) wajib diambil.',
        ]);

        $petugas = Petugas::findOrFail($validated['petugas_id']);
        $petugasNama = $petugas->nama;

        $now = Carbon::now('Asia/Makassar');
        $today = $now->toDateString();
        $waktuStr = $now->format('H.i') . ' WITA';

        // 1. Dapatkan / buat Mutasi Keamanan aktif hari ini
        $mutasi = MutasiKeamanan::firstOrCreate(
            [
                'unit_sppg_id' => $unitId,
                'tanggal' => $today,
                'shift' => '24 Jam',
            ],
            [
                'petugas_id' => $petugas->id,
                'petugas_nama' => $petugasNama,
                'status_keseluruhan' => 'Aman',
            ]
        );

        // 2. Cek apakah ada catatan izin keluar petugas ini yang masih 'Sedang Keluar'
        $izinKeluar = MutasiKeamananIzin::where('mutasi_keamanan_id', $mutasi->id)
            ->where('petugas_id', $petugas->id)
            ->where('status', 'Sedang Keluar')
            ->latest()
            ->first();

        if ($izinKeluar) {
            $tglKeluar = $izinKeluar->tanggal_keluar ?: $izinKeluar->tanggal_izin ?: $today;
            $wktKeluar = $izinKeluar->waktu_keluar ?: $izinKeluar->waktu_izin;
            $durasiKeluar = null;
            if ($wktKeluar) {
                try {
                    $cleanTime = preg_replace('/[^0-9:]/', '', str_replace('.', ':', $wktKeluar));
                    $dtKeluar = Carbon::createFromFormat('Y-m-d H:i', $tglKeluar . ' ' . $cleanTime, 'Asia/Makassar');
                    $durasiKeluar = (int) $dtKeluar->diffInMinutes($now);
                } catch (\Throwable $e) {}
            }

            $izinKeluar->update([
                'tanggal_kembali' => $today,
                'waktu_kembali' => $waktuStr,
                'lokasi_kembali' => $validated['lokasi_nama'],
                'lat_kembali' => $validated['latitude'],
                'lng_kembali' => $validated['longitude'],
                'durasi_keluar_menit' => $durasiKeluar,
                'durasi_total_menit' => $durasiKeluar,
                'status' => 'Sudah Kembali',
                'laporan_kegiatan' => $izinKeluar->laporan_kegiatan ?: "Petugas telah kembali jaga di {$validated['lokasi_nama']}.",
            ]);
        } else {
            // Jika belum ada catatan keluar sebelumnya, buat catatan kembali jaga / kembali ke pos
            MutasiKeamananIzin::create([
                'mutasi_keamanan_id' => $mutasi->id,
                'petugas_id' => $petugas->id,
                'petugas_nama' => $petugasNama,
                'keperluan' => 'Kembali ke Pos Penjagaan SPPG setelah berpatroli / bertugas di lingkungan',
                'tanggal_izin' => $today,
                'waktu_izin' => $waktuStr,
                'lokasi_izin' => $validated['lokasi_nama'],
                'lat_izin' => $validated['latitude'],
                'lng_izin' => $validated['longitude'],
                'tanggal_keluar' => $today,
                'waktu_keluar' => $waktuStr,
                'lokasi_awal' => $validated['lokasi_nama'],
                'lat_awal' => $validated['latitude'],
                'lng_awal' => $validated['longitude'],
                'lokasi_tujuan' => $validated['lokasi_nama'],
                'lat_tujuan' => $validated['latitude'],
                'lng_tujuan' => $validated['longitude'],
                'tanggal_kembali' => $today,
                'waktu_kembali' => $waktuStr,
                'lokasi_kembali' => $validated['lokasi_nama'],
                'lat_kembali' => $validated['latitude'],
                'lng_kembali' => $validated['longitude'],
                'status' => 'Sudah Kembali',
                'laporan_kegiatan' => "Petugas telah kembali jaga di {$validated['lokasi_nama']}.",
            ]);
        }

        return back()->with('success', "Laporan Kembali Jaga atas nama {$petugasNama} pada pukul {$waktuStr} berhasil dicatat!");
    }

    /**
     * Simpan Laporan Serah Terima (Masuk Jaga / Lepas Jaga) dari Link Mandiri Smartphone
     */
    public function publicStoreSerahTerima(Request $request, string $token): RedirectResponse
    {
        $unitSppg = UnitSppg::where('token_keamanan', $token)->first();
        if (!$unitSppg) {
            abort(404, 'Token formulir keamanan tidak valid.');
        }
        $unitId = $unitSppg->id;

        $validated = $request->validate([
            'petugas_id' => ['required', 'exists:petugas,id'],
            'jenis' => ['required', 'in:Masuk Jaga,Lepas Jaga'],
            'catatan' => ['required', 'string', 'max:1000'],
            'lokasi_nama' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ], [
            'petugas_id.required' => 'Nama Petugas Keamanan wajib dipilih.',
            'jenis.required' => 'Status Serah Terima (Masuk Jaga atau Lepas Jaga) wajib dipilih.',
            'catatan.required' => 'Catatan Serah Terima wajib diisi.',
            'lokasi_nama.required' => 'Tempat Posisi Serah Terima wajib diisi.',
            'latitude.required' => 'Titik Lokasi Serah Terima (koordinat GPS) wajib diambil.',
            'longitude.required' => 'Titik Lokasi Serah Terima (koordinat GPS) wajib diambil.',
        ]);

        $petugas = Petugas::findOrFail($validated['petugas_id']);

        $now = Carbon::now('Asia/Makassar');
        $tanggal = $now->toDateString();
        $waktu = $now->format('H.i') . ' WITA';

        $mutasi = MutasiKeamanan::firstOrCreate(
            [
                'unit_sppg_id' => $unitId,
                'tanggal' => $tanggal,
                'shift' => '24 Jam',
            ],
            [
                'petugas_id' => $petugas->id,
                'petugas_nama' => $petugas->nama,
                'status_keseluruhan' => 'Aman',
            ]
        );

        MutasiKeamananSerahTerima::create([
            'mutasi_keamanan_id' => $mutasi->id,
            'petugas_id' => $petugas->id,
            'petugas_nama' => $petugas->nama,
            'jenis' => $validated['jenis'],
            'tanggal' => $tanggal,
            'waktu' => $waktu,
            'catatan' => $validated['catatan'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'lokasi_nama' => $validated['lokasi_nama'] ?? 'Pos Penjagaan SPPG',
        ]);

        return back()->with('success', "Laporan Serah Terima ({$validated['jenis']}) untuk {$petugas->nama} pada pukul {$waktu} berhasil disimpan!");
    }

    /**
     * Halaman Khusus Monitoring Keamanan Realtime (Tampilan Data Terkini Masing-Masing Petugas)
     */
    public function publicMonitoring(Request $request, string $token): Response
    {
        $unitSppg = UnitSppg::where('token_monitoring', $token)
            ->orWhere('token_keamanan', $token)
            ->first();

        if (!$unitSppg) {
            $unitSppgOld = UnitSppg::whereJsonContains('old_tokens_monitoring', $token)
                ->orWhereJsonContains('old_tokens_keamanan', $token)
                ->orWhere('old_tokens_monitoring', 'like', "%\"{$token}\"%")
                ->orWhere('old_tokens_keamanan', 'like', "%\"{$token}\"%")
                ->first();

            if ($unitSppgOld) {
                return Inertia::render('Keamanan/LinkBerubah', [
                    'unitSppg' => $unitSppgOld->only(['id', 'nama', 'kode_sppg', 'alamat_lengkap']),
                    'title' => 'Tautan Layar Monitoring Telah Berubah',
                    'message' => 'Tautan live monitoring keamanan yang Anda buka telah diperbarui oleh administrator SPPG.',
                    'isOldToken' => true,
                ]);
            }
            abort(404, 'Link monitoring keamanan tidak valid.');
        }

        $unitId = $unitSppg->id;
        Carbon::setLocale('id');
        $nowWita = Carbon::now('Asia/Makassar');
        $today = $nowWita->toDateString();

        // 2 Petugas Keamanan Resmi SPPG
        $petugasKeamanan = Petugas::where('unit_sppg_id', $unitId)
            ->where(function ($q) {
                $q->whereRaw('LOWER(jabatan) LIKE ?', ['%keamanan%'])
                  ->orWhereRaw('LOWER(jabatan) LIKE ?', ['%security%'])
                  ->orWhereRaw('LOWER(jabatan) LIKE ?', ['%satpam%']);
            })
            ->where('status', 'Aktif')
            ->orderBy('urutan', 'asc')
            ->orderBy('nama', 'asc')
            ->get(['id', 'nama', 'jabatan', 'no_telp']);

        if ($petugasKeamanan->isEmpty()) {
            $petugasKeamanan = Petugas::where('unit_sppg_id', $unitId)
                ->where('status', 'Aktif')
                ->orderBy('urutan', 'asc')
                ->orderBy('nama', 'asc')
                ->take(2)
                ->get(['id', 'nama', 'jabatan', 'no_telp']);
        }

        // Mutasi Hari Ini
        $mutasiHariIni = MutasiKeamanan::with(['details', 'izins', 'serahTerimas'])
            ->where('unit_sppg_id', $unitId)
            ->where('tanggal', $today)
            ->first();

        // Format data monitoring masing-masing petugas keamanan
        $petugasMonitoringList = $petugasKeamanan->map(function ($pet) use ($mutasiHariIni, $unitId, $nowWita, $today) {
            // 1. Cari laporan terakhir yang diisi petugas ini hari ini
            $latestDetailHariIni = $mutasiHariIni?->details
                ->filter(fn($d) => $d->petugas_id == $pet->id && (!empty($d->waktu_cek) || !empty($d->data_pengecekan) || !empty($d->latitude)))
                ->sortByDesc('waktu_cek')
                ->first();

            $latestDetail = $latestDetailHariIni;
            if (!$latestDetail) {
                // Fallback ke detail mutasi terbaru dari petugas ini jika hari ini belum mengisi
                $latestDetail = MutasiKeamananDetail::whereHas('mutasi', fn($q) => $q->where('unit_sppg_id', $unitId))
                    ->where('petugas_id', $pet->id)
                    ->whereNotNull('waktu_cek')
                    ->latest('id')
                    ->first();
            }

            // 2. Status Izin / Keberadaan Terkini Hari Ini
            $latestIzin = $mutasiHariIni?->izins
                ->filter(fn($i) => $i->petugas_id == $pet->id)
                ->sortByDesc('id')
                ->first();

            $isSedangKeluar = $latestIzin && $latestIzin->status === 'Sedang Keluar';

            // 3. Status Serah Terima Terkini Hari Ini (Masuk Jaga / Lepas Jaga)
            $latestSerahTerima = $mutasiHariIni?->serahTerimas
                ->filter(fn($st) => $st->petugas_id == $pet->id)
                ->sortByDesc('id')
                ->first();

            // Hitung durasi jika sedang keluar
            $durasiBerjalanMenit = null;
            $durasiBerjalanText = null;
            if ($isSedangKeluar && $latestIzin->waktu_keluar) {
                try {
                    $parts = explode(':', str_replace('.', ':', $latestIzin->waktu_keluar));
                    if (count($parts) >= 2) {
                        $mKeluar = intval($parts[0]) * 60 + intval($parts[1]);
                        $mNow = $nowWita->hour * 60 + $nowWita->minute;
                        $diff = $mNow - $mKeluar;
                        if ($diff < 0) $diff += 1440;
                        $durasiBerjalanMenit = $diff;
                        $jam = floor($diff / 60);
                        $sisa = $diff % 60;
                        $durasiBerjalanText = $jam > 0 ? "{$jam} Jam {$sisa} Menit" : "{$sisa} Menit";
                    }
                } catch (\Exception $e) {}
            }

            // Total laporan yang diinput petugas ini hari ini
            $totalLaporanHariIni = $mutasiHariIni?->details
                ->filter(fn($d) => $d->petugas_id == $pet->id && (!empty($d->waktu_cek) || !empty($d->data_pengecekan)))
                ->count() ?? 0;

            // Tentukan status & posisi tempat terkini persis sesuai apa yang diinput di formulir oleh petugas
            $statusKode = 'belum_ada_laporan';
            $statusLabel = 'Belum Ada Laporan';
            $posisiTerkini = null;

            if ($isSedangKeluar) {
                $statusKode = 'sedang_keluar';
                $posisiTerkini = $latestIzin->lokasi_tujuan ?: 'Dinas Luar';
                $statusLabel = 'Dinas Luar';
            } elseif ($latestSerahTerima && $latestSerahTerima->jenis === 'Lepas Jaga' && (!$latestDetailHariIni || $latestDetailHariIni->created_at < $latestSerahTerima->created_at)) {
                $statusKode = 'lepas_jaga';
                $posisiTerkini = $latestSerahTerima->lokasi_nama ?: 'Pos Penjagaan';
                $statusLabel = 'Lepas Jaga';
            } elseif ($latestDetailHariIni && !empty($latestDetailHariIni->lokasi_nama)) {
                $statusKode = 'aktif_jaga';
                $posisiTerkini = $latestDetailHariIni->lokasi_nama;
                $statusLabel = $posisiTerkini;
            } elseif ($latestSerahTerima && $latestSerahTerima->jenis === 'Masuk Jaga' && !empty($latestSerahTerima->lokasi_nama)) {
                $statusKode = 'aktif_jaga';
                $posisiTerkini = $latestSerahTerima->lokasi_nama;
                $statusLabel = $posisiTerkini;
            } elseif ($totalLaporanHariIni > 0) {
                $statusKode = 'aktif_jaga';
                $posisiTerkini = $latestDetailHariIni?->lokasi_nama ?: 'Pos Penjagaan';
                $statusLabel = $posisiTerkini;
            } else {
                $statusKode = 'belum_ada_laporan';
                $statusLabel = 'Belum Ada Laporan';
                $posisiTerkini = null;
            }

            $detailTanggal = $latestDetail?->mutasi?->tanggal ?? ($latestDetail?->created_at ? Carbon::parse($latestDetail->created_at)->toDateString() : $today);
            $laporanTerakhirData = $latestDetail ? [
                'id' => $latestDetail->id,
                'jam' => $latestDetail->jam,
                'waktu_cek' => $latestDetail->waktu_cek,
                'kondisi' => $latestDetail->kondisi,
                'uraian_kejadian' => $latestDetail->uraian_kejadian,
                'data_pengecekan' => $latestDetail->data_pengecekan,
                'latitude' => $latestDetail->latitude ? (float) $latestDetail->latitude : null,
                'longitude' => $latestDetail->longitude ? (float) $latestDetail->longitude : null,
                'lokasi_nama' => $latestDetail->lokasi_nama ?: 'Pos Penjagaan SPPG',
                'is_today' => ($detailTanggal === $today),
                'tanggal' => $detailTanggal,
                'hari_tanggal' => Carbon::parse($detailTanggal)->locale('id')->isoFormat('dddd, D MMMM Y'),
                'relative_time' => $latestDetail->created_at?->locale('id')->diffForHumans(),
            ] : null;

            if ($isSedangKeluar && $latestIzin && ($latestIzin->lat_tujuan || $latestIzin->lat_awal)) {
                $latKeluar = (float) ($latestIzin->lat_tujuan ?: $latestIzin->lat_awal);
                $lngKeluar = (float) ($latestIzin->lng_tujuan ?: $latestIzin->lng_awal);
                $laporanTerakhirData = [
                    'id' => $latestIzin->id,
                    'jam' => $latestIzin->waktu_keluar,
                    'waktu_cek' => $latestIzin->waktu_keluar,
                    'kondisi' => 'Aman',
                    'uraian_kejadian' => $latestIzin->keperluan,
                    'data_pengecekan' => null,
                    'latitude' => $latKeluar,
                    'longitude' => $lngKeluar,
                    'lokasi_nama' => $latestIzin->lokasi_tujuan ?: 'Sedang di Luar Pos',
                    'is_today' => true,
                    'tanggal' => $today,
                    'hari_tanggal' => Carbon::parse($today)->locale('id')->isoFormat('dddd, D MMMM Y'),
                    'relative_time' => $latestIzin->created_at?->locale('id')->diffForHumans(),
                ];
            }

            return [
                'id' => $pet->id,
                'nama' => $pet->nama,
                'jabatan' => $pet->jabatan ?: 'Petugas Keamanan SPPG',
                'no_hp' => $pet->no_telp,
                'total_laporan_hari_ini' => $totalLaporanHariIni,
                'status_kode' => $statusKode,
                'status_label' => $statusLabel,
                'posisi_terkini' => $posisiTerkini,
                'status_keberadaan' => $statusLabel,
                'izin_aktif' => $latestIzin ? [
                    'id' => $latestIzin->id,
                    'status' => $latestIzin->status,
                    'lokasi_tujuan' => $latestIzin->lokasi_tujuan,
                    'keperluan' => $latestIzin->keperluan,
                    'waktu_keluar' => $latestIzin->waktu_keluar,
                    'waktu_kembali' => $latestIzin->waktu_kembali,
                    'lat_awal' => $latestIzin->lat_awal ? (float) $latestIzin->lat_awal : null,
                    'lng_awal' => $latestIzin->lng_awal ? (float) $latestIzin->lng_awal : null,
                    'lat_tujuan' => $latestIzin->lat_tujuan ? (float) $latestIzin->lat_tujuan : null,
                    'lng_tujuan' => $latestIzin->lng_tujuan ? (float) $latestIzin->lng_tujuan : null,
                    'lat_kembali' => $latestIzin->lat_kembali ? (float) $latestIzin->lat_kembali : null,
                    'lng_kembali' => $latestIzin->lng_kembali ? (float) $latestIzin->lng_kembali : null,
                    'durasi_berjalan_menit' => $durasiBerjalanMenit,
                    'durasi_berjalan_text' => $durasiBerjalanText,
                ] : null,
                'serah_terima_terkini' => $latestSerahTerima ? [
                    'id' => $latestSerahTerima->id,
                    'jenis' => $latestSerahTerima->jenis,
                    'waktu' => $latestSerahTerima->waktu,
                    'tanggal' => $latestSerahTerima->tanggal,
                    'catatan' => $latestSerahTerima->catatan,
                    'lokasi_nama' => $latestSerahTerima->lokasi_nama ?: 'Pos Penjagaan SPPG',
                    'latitude' => $latestSerahTerima->latitude ? (float) $latestSerahTerima->latitude : null,
                    'longitude' => $latestSerahTerima->longitude ? (float) $latestSerahTerima->longitude : null,
                    'relative_time' => $latestSerahTerima->created_at?->locale('id')->diffForHumans(),
                ] : null,
                'laporan_terakhir' => $laporanTerakhirData,
            ];
        });

        // Summary Keseluruhan Hari Ini
        $totalTerlapor = $mutasiHariIni?->details->filter(fn($d) => !empty($d->waktu_cek) || !empty($d->data_pengecekan))->count() ?? 0;
        $totalIntervalTerisi = $mutasiHariIni?->details->filter(fn($d) => !empty($d->waktu_cek) || !empty($d->data_pengecekan))->pluck('jam')->unique()->count() ?? 0;
        $totalInsiden = $mutasiHariIni?->details->filter(fn($d) => $d->kondisi === 'Tidak Aman')->count() ?? 0;
        $countSedangKeluar = $mutasiHariIni?->izins->filter(fn($i) => $i->status === 'Sedang Keluar')->count() ?? 0;

        // Tentukan Sesi Jam Aktif Saat Ini (WITA)
        $currentHour = $nowWita->hour;
        $curH1 = str_pad($currentHour, 2, '0', STR_PAD_LEFT);
        $curH2 = str_pad(($currentHour + 1) % 24, 2, '0', STR_PAD_LEFT);
        $currentActiveSession = "{$curH1}.00 - {$curH2}.00 WITA";

        // Susun 24 Sesi Patroli beserta Laporan Lengkap Tiap Sesi
        $sesiList = [];
        $activeSessionReportCount = 0;

        for ($i = 0; $i < 24; $i++) {
            $h1 = str_pad($i, 2, '0', STR_PAD_LEFT);
            $h2 = str_pad(($i + 1) % 24, 2, '0', STR_PAD_LEFT);
            $jamStr = "{$h1}.00 - {$h2}.00 WITA";
            $legacyStr = "{$h1}:00";

            $reportsInInterval = $mutasiHariIni?->details
                ->filter(fn($d) => ($d->jam === $jamStr || $d->jam === $legacyStr) && (!empty($d->waktu_cek) || !empty($d->data_pengecekan)))
                ->sortBy('waktu_cek')
                ->map(function ($d) use ($today) {
                    $repTanggal = $d->mutasi?->tanggal ?? ($d->created_at ? Carbon::parse($d->created_at)->toDateString() : $today);
                    return [
                        'id' => $d->id,
                        'jam' => $d->jam,
                        'waktu_cek' => $d->waktu_cek,
                        'tanggal' => $repTanggal,
                        'hari_tanggal' => Carbon::parse($repTanggal)->locale('id')->isoFormat('dddd, D MMMM Y'),
                        'petugas_id' => $d->petugas_id,
                        'petugas_nama' => $d->petugas_nama ?: ($d->petugas?->nama ?? 'Petugas Keamanan'),
                        'kondisi' => $d->kondisi ?: 'Aman',
                        'uraian_kejadian' => $d->uraian_kejadian,
                        'data_pengecekan' => $d->data_pengecekan,
                        'lokasi_nama' => $d->lokasi_nama ?: 'Pos Penjagaan SPPG',
                        'latitude' => $d->latitude ? (float) $d->latitude : null,
                        'longitude' => $d->longitude ? (float) $d->longitude : null,
                        'relative_time' => $d->created_at?->locale('id')->diffForHumans(),
                    ];
                })
                ->values()
                ->all() ?? [];

            $isCurrentActive = ($i === $currentHour);
            if ($isCurrentActive) {
                $activeSessionReportCount = count($reportsInInterval);
            }

            $hasInsiden = collect($reportsInInterval)->contains(fn($r) => $r['kondisi'] === 'Tidak Aman');
            $statusSesi = count($reportsInInterval) > 0 ? ($hasInsiden ? 'Tidak Aman' : 'Aman') : 'Belum Ada Laporan';
            $petugasNames = collect($reportsInInterval)->pluck('petugas_nama')->unique()->values()->all();

            $sesiList[] = [
                'jam' => $jamStr,
                'tanggal' => $today,
                'hari_tanggal' => Carbon::parse($today)->locale('id')->isoFormat('dddd, D MMMM Y'),
                'hour_index' => $i,
                'is_active' => $isCurrentActive,
                'report_count' => count($reportsInInterval),
                'status' => $statusSesi,
                'petugas_names' => $petugasNames,
                'reports' => $reportsInInterval,
            ];
        }

        return Inertia::render('Keamanan/PublicMonitoring', [
            'unitSppg' => $unitSppg->only(['id', 'nama', 'kode_sppg', 'alamat_lengkap', 'latitude', 'longitude']),
            'token' => $token,
            'todayDate' => $today,
            'serverTimeWita' => $nowWita->format('H:i:s'),
            'currentActiveSession' => $currentActiveSession,
            'sesiList' => $sesiList,
            'petugasMonitoringList' => $petugasMonitoringList,
            'summary' => [
                'total_terlapor' => $totalTerlapor,
                'total_interval_terisi' => $totalIntervalTerisi,
                'total_insiden' => $totalInsiden,
                'count_sedang_keluar' => $countSedangKeluar,
                'status_keseluruhan' => $totalInsiden > 0 ? 'Tidak Aman' : 'Aman',
                'current_active_session' => $currentActiveSession,
                'active_session_report_count' => $activeSessionReportCount,
            ],
        ]);
    }
}
