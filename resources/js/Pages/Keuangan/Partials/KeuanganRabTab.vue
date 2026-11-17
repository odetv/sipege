<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import {
    Calculator,
    Calendar,
    Download,
    Save,
    RotateCcw,
    FileSpreadsheet,
    CheckCircle2,
    AlertCircle,
    Info,
    ChevronDown,
    ChevronUp,
    Building2,
    FileText,
    Users,
    DollarSign,
    Layers,
    Trash2,
    RefreshCw,
    Wand2,
    Plus,
    Table,
    ArrowRight,
    ArrowDownUp,
    ArrowRightLeft,
    Check,
    CalendarDays,
    UserCheck,
    UserX,
    School,
    HeartPulse,
    X,
    Edit3,
} from "lucide-vue-next";
import {
    downloadRabBulananExcel,
    downloadRabTahunanExcel,
} from "@/Services/exportRabHelper";
import {
    getDefaultTabelBantuOperasional,
    calculateTabelBantuItem,
    getAutoValueForBantuItem,
} from "@/Services/operasionalBantuHelper";
import {
    generateDefaultJadwalPmHarian,
    calculateJadwalPmSummary,
    normalizeKelompoksForDate,
    calculateDateTotalsFromKelompoks,
    applyPresetToKelompoks,
    isPosyanduKelompok,
    calculateKelompokTotalsFromRincian,
} from "@/Services/jadwalPmHelper";

const props = defineProps({
    rabList: {
        type: Array,
        default: () => [],
    },
    selectedYear: {
        type: Number,
        default: () => new Date().getFullYear(),
    },
    pejabatSppg: {
        type: Object,
        default: () => ({
            kepala_sppg: "",
            pengawas_keuangan: "",
            pengawas_gizi: "",
        }),
    },
    rekeningUtama: {
        type: Object,
        default: null,
    },
    kopDokumen: {
        type: Object,
        default: null,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    autoOperasional: {
        type: Object,
        default: () => ({
            relawan_count: 0,
            relawan_gaji_harian: 0,
            bpjs_tk_total: 0,
            petugas_aktif_count: 0,
            satuan_pendidikan_count: 0,
            satuan_pendidikan_harian: 0,
            kelompok_3b_count: 0,
            total_pm_3b: 0,
            insentif_3b_harian: 0,
            gaji_pengawas_total: 10000000,
            gaji_pengawas_gizi: 5000000,
            gaji_pengawas_keuangan: 5000000,
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_penerima: 0,
            total_porsi_kecil: 0,
            total_porsi_besar: 0,
        }),
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        default: (val) =>
            new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                maximumFractionDigits: 0,
            }).format(val || 0),
    },
});

const page = usePage();

// Mode tampilan: 'bulanan' | 'tahunan'
const activeMode = ref("bulanan");
const selectedYear = ref(props.selectedYear || new Date().getFullYear());
const selectedMonth = ref(new Date().getMonth() + 1); // 1-12
const isSaving = ref(false);
const showOperasionalPanel = ref(true); // default terbuka agar tabel operasional langsung tampak

const monthNames = [
    "",
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
];

// Master definisi 17 Pos Operasional Bab V BGN
const masterOperasionalDefinitions = [
    {
        no: 1,
        nama: "Insentif Relawan",
        satuan: "2 Mingguan / Bulan",
        is_auto_supported: true,
        desc: "Honorarium relawan SPPG (sesuai hari pelayanan MBG; keamanan dibayarkan 7 hari seminggu).",
    },
    {
        no: 2,
        nama: "Insentif Penanggung Jawab Satuan Pendidikan",
        satuan: "Hari / Bulan",
        is_auto_supported: true,
        desc: "Besaran insentif penanggung jawab satuan pendidikan dihitung berdasarkan hari pelayanan x sasaran penerima manfaat.",
    },
    {
        no: 3,
        nama: "Insentif PJ 3B (Kader/TPK)",
        satuan: "Hari / Bulan",
        is_auto_supported: true,
        desc: "Insentif kader pendamping proporsional sesuai jumlah sasaran 3B (Ibu hamil, ibu menyusui, balita).",
    },
    {
        no: 4,
        nama: "Jaminan Sosial Ketenagakerjaan (BPJS TK)",
        satuan: "Bulan",
        is_auto_supported: true,
        desc: "Iuran BPJS Ketenagakerjaan bagi seluruh personil relawan, pengawas gizi, dan pengawas keuangan.",
    },
    {
        no: 5,
        nama: "Listrik (PLN)",
        satuan: "Bulan",
        is_auto_supported: false,
        desc: "Tagihan listrik bulanan sesuai hasil pemakaian riil dan bukti struk PLN.",
    },
    {
        no: 6,
        nama: "Air Kemasan Galon",
        satuan: "Hari / Bulan",
        is_auto_supported: false,
        desc: "Pembelian air minum/galon untuk pencucian higienis bahan baku dan memasak.",
    },
    {
        no: 7,
        nama: "Air PDAM",
        satuan: "Bulan",
        is_auto_supported: false,
        desc: "Biaya tagihan air berlangganan bulanan dari PDAM.",
    },
    {
        no: 8,
        nama: "Biaya BBM (Kendaraan & Genset)",
        satuan: "Hari / Bulan",
        is_auto_supported: false,
        desc: "BBM kendaraan operasional antar makanan, mobilitas survei pasar, koordinasi, dan genset.",
    },
    {
        no: 9,
        nama: "Internet",
        satuan: "Bulan",
        is_auto_supported: false,
        desc: "Tagihan internet bulanan untuk pelaporan sistem dan koordinasi digital SPPG.",
    },
    {
        no: 10,
        nama: "Gas LPG",
        satuan: "Hari / Bulan",
        is_auto_supported: false,
        desc: "Pembelian tabung gas LPG harian untuk proses pengolahan dan pemanasan makanan.",
    },
    {
        no: 11,
        nama: "ATK & Stiker Label",
        satuan: "Bulan",
        is_auto_supported: false,
        desc: "Kebutuhan alat tulis kantor, twizewal, perekat, dan stiker label identitas ompreng.",
    },
    {
        no: 12,
        nama: "Retribusi Sampah / Pengelolaan Limbah Cair",
        satuan: "Bulan",
        is_auto_supported: false,
        desc: "Biaya retribusi resmi pengelolaan sampah padat dan penanganan limbah sanitasi.",
    },
    {
        no: 13,
        nama: "Alat Kebersihan & Sanitasi",
        satuan: "Bulan",
        is_auto_supported: false,
        desc: "Pengadaan alat kebersihan dapur (sapu, pel, sabun cuci food-grade, disinfektan).",
    },
    {
        no: 14,
        nama: "APD (Alat Pelindung Diri)",
        satuan: "Bulan",
        is_auto_supported: false,
        desc: "Pengadaan masker, hairnet/penutup kepala, celemek, dan sarung tangan relawan.",
    },
    {
        no: 15,
        nama: "Konsumsi Rapat Koordinasi",
        satuan: "2 Mingguan / Bulan",
        is_auto_supported: false,
        desc: "Biaya snack/makan pertemuan evaluasi rutin 2 mingguan seluruh relawan dan pengawas.",
    },
    {
        no: 16,
        nama: "Pemeliharaan (AC, Sterilisasi, APAR, IPAL)",
        satuan: "Bulan / Triwulan",
        is_auto_supported: false,
        desc: "Perawatan rutin AC, sterilisasi air, pest control, refil tabung APAR, dan pembersihan IPAL.",
    },
    {
        no: 17,
        nama: "Pembayaran Gaji Non-ASN untuk Pengawas Gizi & Pengawas Keuangan",
        satuan: "Bulan",
        is_auto_supported: true,
        desc: "Honorarium bulanan non-ASN Pengawas Gizi (Rp 5.000.000) dan Pengawas Keuangan (Rp 5.000.000).",
    },
];

// Hitung nilai otomatis untuk pos tertentu berdasarkan hari efektif
function getAutoValueForOp(no, hariEfektif) {
    const hEff = Number(hariEfektif) || 20;
    const auto = props.autoOperasional || {};

    if (no === 1) {
        // Insentif Relawan
        const harian = Number(auto.relawan_gaji_harian) || 0;
        return harian > 0 ? harian * hEff : 0;
    }
    if (no === 2) {
        // Insentif Satuan Pendidikan
        const harian = Number(auto.satuan_pendidikan_harian) || 0;
        return harian > 0 ? harian * hEff : 0;
    }
    if (no === 3) {
        // Insentif 3B (Kader/TPK)
        const harian = Number(auto.insentif_3b_harian) || (Number(auto.total_pm_3b || 0) * 1000);
        return harian > 0 ? harian * hEff : 0;
    }
    if (no === 4) {
        // BPJS Ketenagakerjaan
        const bpjs = Number(auto.bpjs_tk_total) || 0;
        return bpjs > 0 ? bpjs : (Number(auto.petugas_aktif_count || 0) * 16800);
    }
    if (no === 17) {
        // Gaji Non-ASN Pengawas Gizi & Pengawas Keuangan (masing-masing 5jt)
        return Number(auto.gaji_pengawas_total) || 10000000;
    }
    return 0;
}

// Deskripsi parameter rumus otomatis
function getAutoFormulaDesc(no, hariEfektif) {
    const hEff = Number(hariEfektif) || 20;
    const auto = props.autoOperasional || {};

    if (no === 1) {
        const count = auto.relawan_count || 0;
        const harian = Number(auto.relawan_gaji_harian) || 0;
        return `${count} Relawan aktif SPPG (${props.formatRupiah(harian)}/hari × ${hEff} hari)`;
    }
    if (no === 2) {
        const count = auto.satuan_pendidikan_count || 0;
        const harian = Number(auto.satuan_pendidikan_harian) || 0;
        return `${count} Satuan Pendidikan (${props.formatRupiah(harian)}/hari × ${hEff} hari)`;
    }
    if (no === 3) {
        const pm = auto.total_pm_3b || 0;
        const harian = Number(auto.insentif_3b_harian) || (pm * 1000);
        return `Sasaran 3B (${pm} PM × Rp 1.000/hari × ${hEff} hari)`;
    }
    if (no === 4) {
        const count = auto.petugas_aktif_count || 0;
        return `${count} Petugas SPPG aktif × Iuran BPJS TK bulanan`;
    }
    if (no === 17) {
        return `Pengawas Gizi (Rp 5.000.000) + Pengawas Keuangan (Rp 5.000.000)`;
    }
    return "Sesuai kebutuhan riil SPPG";
}

// Inisialisasi daftar 17 item operasional dengan nilai otomatis yang bisa diubah
function generateDefault17Operasional(hariEfektif, existingList = null) {
    const hEff = Number(hariEfektif) || 20;
    const existingMap = {};
    if (Array.isArray(existingList)) {
        existingList.forEach((it) => {
            if (it && it.no) existingMap[it.no] = it;
        });
    }

    return masterOperasionalDefinitions.map((def) => {
        const autoVal = getAutoValueForOp(def.no, hEff);
        const autoDesc = getAutoFormulaDesc(def.no, hEff);
        const exist = existingMap[def.no];

        let finalBiaya = 0;
        let finalKet = def.desc;

        if (exist && exist.biaya !== undefined && exist.biaya !== null && exist.biaya !== "") {
            finalBiaya = Number(exist.biaya) || 0;
            finalKet = exist.keterangan || def.desc;
        } else if (def.is_auto_supported) {
            finalBiaya = autoVal;
        }

        return {
            no: def.no,
            nama: def.nama,
            satuan: def.satuan,
            is_auto_supported: def.is_auto_supported,
            rumus_dasar: autoDesc,
            auto_val: autoVal,
            biaya: finalBiaya,
            keterangan: finalKet,
        };
    });
}

// Standar 5 Komponen Utama RAB BGN
const defaultPorsiItems = () => [
    {
        no: 1,
        nama: "Bahan makanan anak balita, siswa TK/PAUD/RA dan SD/MI/SD SLB Kelas 1-3",
        jumlah_paket: Number(props.stats?.total_porsi_kecil) || 0,
        harga_satuan: 8000,
        keterangan: "Maksimal 8.000 dengan dasar perhitungan sesuai biaya riil",
    },
    {
        no: 2,
        nama: "Bahan Makanan uji organoleptik untuk kelompok anak balita, siswa TK/PAUD/RA dan SD/MI/SD SLB Kelas 1-3",
        jumlah_paket: 1,
        harga_satuan: 8000,
        keterangan: "1 paket per pengantaran untuk kelompok 3B",
    },
    {
        no: 3,
        nama: "Bahan makanan kelompok siswa SD/MI/ SD SLB Kelas 4-6, SMP/MTS/SMP SLB, SMA/MA/SMK/SMA SLB, Pendidik, Tenaga Kependidikan, ibu hamil dan ibu menyusui",
        jumlah_paket: Number(props.stats?.total_porsi_besar) || 0,
        harga_satuan: 10000,
        keterangan: "Maksimal 10.000 dengan dasar perhitungan sesuai biaya riil",
    },
    {
        no: 4,
        nama: "Bahan Makanan uji organoleptik untuk kelompok SD/MI/ SD SLB Kelas 4-6, SMP/MTS/SMP SLB, SMA/MA/SMK/SMA SLB, Pendidik, Tenaga Kependidikan, ibu hamil dan ibu menyusui",
        jumlah_paket: 1,
        harga_satuan: 10000,
        keterangan: "1 paket per pengantaran untuk kelompok 3B",
    },
    {
        no: 5,
        nama: "Operasional (listrik, gas, air, insentif relawan, insentif kader/TPK, insentif satuan pendidikan, asuransi kecelakaan kerja relawan termasuk Jurutama Masak, Biaya Bahan Bakar Minyak Kendaraan Operasional dan Genset, internet, satpam, rapat koordinasi satu kali sebulan dll)",
        jumlah_paket: Number(props.stats?.total_penerima) || 0,
        harga_satuan: 3000,
        keterangan: "Maksimal 3.000 dengan dasar perhitungan sesuai biaya riil",
    },
];

// State Form Bulanan
const form = ref({
    id: null,
    tahun: selectedYear.value,
    bulan: selectedMonth.value,
    hari_efektif: 20,
    rincian_porsi: defaultPorsiItems(),
    rincian_operasional: generateDefault17Operasional(20),
    rincian_operasional_bantu: getDefaultTabelBantuOperasional(props.autoOperasional),
    jadwal_pm_harian: generateDefaultJadwalPmHarian(
        selectedYear.value,
        selectedMonth.value,
        props.stats?.total_porsi_kecil,
        props.stats?.total_porsi_besar,
        null,
        props.kelompokList || []
    ),
    catatan: "Jumlah hari pelayanan efektif = 20 hari (5 Hari KBM X 4 Minggu). Belum memperhitungkan libur nasional.",
    nama_yayasan: props.kopDokumen?.nama_instansi_1 || props.unitSppg?.nama || "",
    nomor_rekening_yayasan: props.rekeningUtama?.nomor_rekening || "",
    npwp: props.unitSppg?.extra_attributes?.npwp || "",
    kepala_sppg_nama: props.pejabatSppg?.kepala_sppg || "",
    pengawas_keuangan_nama: props.pejabatSppg?.pengawas_keuangan || "",
    pengawas_gizi_nama: props.pejabatSppg?.pengawas_gizi || "",
});

// Map RAB yang sudah tersimpan di database
const savedRabMap = computed(() => {
    const map = {};
    (props.rabList || []).forEach((r) => {
        map[r.bulan] = r;
    });
    return map;
});

// Rekapitulasi Tahunan: hanya menghitung bulan yang sudah diset di database
const tahunanSummary = computed(() => {
    const list = props.rabList || [];
    const savedCount = list.length;
    const unsettledCount = 12 - savedCount;

    // Total akumulasi per item (5 item standar)
    const itemTotals = [0, 0, 0, 0, 0];
    let grandTotal = 0;

    list.forEach((rab) => {
        const porsi = rab.rincian_porsi || [];
        const hEff = rab.hari_efektif || 20;

        porsi.forEach((p, idx) => {
            if (idx < 5) {
                const cost = (Number(p.jumlah_paket) || 0) * (Number(p.harga_satuan) || 0) * hEff;
                itemTotals[idx] += cost;
                grandTotal += cost;
            }
        });
    });

    return {
        savedCount,
        unsettledCount,
        itemTotals,
        grandTotal,
    };
});

// Memuat data saat bulan berubah
function loadMonthData(m) {
    selectedMonth.value = m;
    const existing = savedRabMap.value[m];

    if (existing) {
        const hEff = existing.hari_efektif || 20;
        form.value = {
            id: existing.id,
            tahun: existing.tahun,
            bulan: existing.bulan,
            hari_efektif: hEff,
            rincian_porsi: existing.rincian_porsi && existing.rincian_porsi.length > 0
                ? JSON.parse(JSON.stringify(existing.rincian_porsi))
                : defaultPorsiItems(),
            rincian_operasional: generateDefault17Operasional(hEff, existing.rincian_operasional),
            rincian_operasional_bantu: existing.rincian_operasional_bantu && existing.rincian_operasional_bantu.length > 0
                ? JSON.parse(JSON.stringify(existing.rincian_operasional_bantu))
                : getDefaultTabelBantuOperasional(props.autoOperasional),
            jadwal_pm_harian: generateDefaultJadwalPmHarian(
                existing.tahun || selectedYear.value,
                m,
                props.stats?.total_porsi_kecil,
                props.stats?.total_porsi_besar,
                existing.jadwal_pm_harian || null,
                props.kelompokList || []
            ),
            catatan: existing.catatan || "Jumlah hari pelayanan efektif = 20 hari (5 Hari KBM X 4 Minggu).",
            nama_yayasan: existing.nama_yayasan || props.kopDokumen?.nama_instansi_1 || props.unitSppg?.nama || "",
            nomor_rekening_yayasan: existing.nomor_rekening_yayasan || props.rekeningUtama?.nomor_rekening || "",
            npwp: existing.npwp || props.unitSppg?.extra_attributes?.npwp || "",
            kepala_sppg_nama: existing.kepala_sppg_nama || props.pejabatSppg?.kepala_sppg || "",
            pengawas_keuangan_nama: existing.pengawas_keuangan_nama || props.pejabatSppg?.pengawas_keuangan || "",
            pengawas_gizi_nama: existing.pengawas_gizi_nama || props.pejabatSppg?.pengawas_gizi || "",
        };
    } else {
        const hEff = 20;
        form.value = {
            id: null,
            tahun: selectedYear.value,
            bulan: m,
            hari_efektif: hEff,
            rincian_porsi: defaultPorsiItems(),
            rincian_operasional: generateDefault17Operasional(hEff),
            rincian_operasional_bantu: getDefaultTabelBantuOperasional(props.autoOperasional),
            jadwal_pm_harian: generateDefaultJadwalPmHarian(
                selectedYear.value,
                m,
                props.stats?.total_porsi_kecil,
                props.stats?.total_porsi_besar,
                null,
                props.kelompokList || []
            ),
            catatan: "Jumlah hari pelayanan efektif = 20 hari (5 Hari KBM X 4 Minggu).",
            nama_yayasan: props.kopDokumen?.nama_instansi_1 || props.unitSppg?.nama || "",
            nomor_rekening_yayasan: props.rekeningUtama?.nomor_rekening || "",
            npwp: props.unitSppg?.extra_attributes?.npwp || "",
            kepala_sppg_nama: props.pejabatSppg?.kepala_sppg || "",
            pengawas_keuangan_nama: props.pejabatSppg?.pengawas_keuangan || "",
            pengawas_gizi_nama: props.pejabatSppg?.pengawas_gizi || "",
        };
    }
}

// Reset data bulan aktif ke default kelompok sekolah
function resetToDefault() {
    form.value.hari_efektif = 20;
    form.value.rincian_porsi = defaultPorsiItems();
    form.value.rincian_operasional = generateDefault17Operasional(20);
    form.value.rincian_operasional_bantu = getDefaultTabelBantuOperasional(props.autoOperasional);
    form.value.jadwal_pm_harian = generateDefaultJadwalPmHarian(
        selectedYear.value,
        selectedMonth.value,
        props.stats?.total_porsi_kecil,
        props.stats?.total_porsi_besar,
        null,
        props.kelompokList || []
    );
}

// Terapkan nilai otomatis untuk 1 baris operasional tertentu
function applyAutoForSingle(idx) {
    const it = form.value.rincian_operasional[idx];
    if (!it) return;
    const autoVal = getAutoValueForOp(it.no, form.value.hari_efektif);
    it.biaya = autoVal;
    it.rumus_dasar = getAutoFormulaDesc(it.no, form.value.hari_efektif);
}

// Terapkan semua nilai otomatis pos kunci (Relawan, Satuan Pendidikan, 3B, BPJS TK, Gaji Pengawas)
function applyAllAutoOperasional() {
    form.value.rincian_operasional.forEach((it, idx) => {
        if (it.is_auto_supported) {
            applyAutoForSingle(idx);
        }
    });
}

// Terapkan nilai otomatis untuk 1 baris Tabel Bantu tertentu (Gaji Pengawas, Relawan, PJ Sekolah, PJ 3B, BPJS TK)
function applyAutoForSingleBantu(idx) {
    const it = form.value.rincian_operasional_bantu[idx];
    if (!it) return;
    const autoVal = getAutoValueForBantuItem(it.no, props.autoOperasional);
    if (autoVal !== null && autoVal !== undefined) {
        it.harga_pokok = autoVal;
    }
}

// Terapkan seluruh nilai otomatis pos kunci pada Tabel Bantu
function applyAllAutoBantu() {
    form.value.rincian_operasional_bantu.forEach((it, idx) => {
        if (it.is_auto_supported) {
            applyAutoForSingleBantu(idx);
        }
    });
}

// Perhitungan Realtime Baris Tabel Utama RAB
const calculatedItems = computed(() => {
    const hEff = Number(form.value.hari_efektif) || 20;
    return form.value.rincian_porsi.map((it) => {
        const jml = Number(it.jumlah_paket) || 0;
        const hrg = Number(it.harga_satuan) || 0;
        const perHari = jml * hrg;
        const per2Minggu = perHari * 12;
        const perBulan = perHari * hEff;

        return {
            ...it,
            biaya_per_hari: perHari,
            biaya_2mingguan: per2Minggu,
            hari_efektif: hEff,
            biaya_per_bulan: perBulan,
        };
    });
});

// Total Keseluruhan Tabel Utama
const totalBiayaHari = computed(() => {
    return calculatedItems.value.reduce((acc, curr) => acc + curr.biaya_per_hari, 0);
});

const totalBiaya2Mingguan = computed(() => {
    return calculatedItems.value.reduce((acc, curr) => acc + curr.biaya_2mingguan, 0);
});

const totalBiayaBulan = computed(() => {
    return calculatedItems.value.reduce((acc, curr) => acc + curr.biaya_per_bulan, 0);
});

// Pagu Alokasi Operasional di RAB Utama (Baris 5)
const paguOperasionalBulan = computed(() => {
    const opItem = calculatedItems.value[4];
    return opItem ? opItem.biaya_per_bulan : 0;
});

// Total Realisasi Riil 17 Pos Operasional di Tabel Bab V
const totalRincianOperasional = computed(() => {
    return (form.value.rincian_operasional || []).reduce(
        (acc, curr) => acc + (Number(curr.biaya) || 0),
        0
    );
});

// Selisih Pagu Operasional vs Rincian Riil
const selisihOperasional = computed(() => {
    return paguOperasionalBulan.value - totalRincianOperasional.value;
});

// Mode sub-tampilan operasional: 'bantu' | 'pos17' | 'keduanya'
const operasionalSubTab = ref("bantu");
const syncNotification = ref("");

// Tabel Bantu Terhitung Per Baris (Nominal Hari, Minggu, Periode, Bulan, Rerata Harian, Estimasi Bulanan)
const tabelBantuCalculated = computed(() => {
    const hEff = Number(form.value.hari_efektif) || 20;
    return (form.value.rincian_operasional_bantu || []).map((item) => {
        return calculateTabelBantuItem(item, hEff);
    });
});

// Ringkasan Akumulasi Total Tabel Bantu (Persis Baris 48 Excel User)
const tabelBantuSummary = computed(() => {
    const items = tabelBantuCalculated.value;

    let totHp = 0;
    let totNomHari = 0;
    let totNomMinggu = 0;
    let totNomPeriode = 0;
    let totNomBulan = 0;
    let totRerataHarian = 0;
    let totPengeluaran = 0;
    let totBulanan = 0;

    items.forEach((it) => {
        totHp += it.hp;
        totNomHari += it.nomHari;
        totNomMinggu += it.nomMinggu;
        totNomPeriode += it.nomPeriode;
        totNomBulan += it.nomBulan;
        totRerataHarian += it.rerataHarian;
        totPengeluaran += it.totalPengeluaran;
        totBulanan += it.estimasiBulanan;
    });

    return {
        totHp,
        totNomHari,
        totNomMinggu,
        totNomPeriode,
        totNomBulan,
        totRerataHarian,
        totPengeluaran,
        totBulanan,
    };
});

// Selisih Pagu Operasional vs Tabel Bantu Rinci
const selisihTabelBantu = computed(() => {
    return paguOperasionalBulan.value - tabelBantuSummary.value.totBulanan;
});

// Persentase Serapan Pagu Operasional dari Tabel Bantu
const persenSerapanBantu = computed(() => {
    if (!paguOperasionalBulan.value || paguOperasionalBulan.value === 0) return 0;
    return Math.min(999, Math.round((tabelBantuSummary.value.totBulanan / paguOperasionalBulan.value) * 100));
});

// Sinkronkan hasil Tabel Bantu ke Tabel 17 Pos Bab V
function syncTabelBantuTo17Pos() {
    const posSums = {};
    for (let p = 1; p <= 17; p++) {
        posSums[p] = { total: 0, items: [] };
    }

    tabelBantuCalculated.value.forEach((it) => {
        const ref = Number(it.pos_ref) || 16;
        if (posSums[ref]) {
            posSums[ref].total += it.estimasiBulanan;
            if (it.estimasiBulanan > 0) {
                posSums[ref].items.push(it.nama);
            }
        }
    });

    form.value.rincian_operasional.forEach((op) => {
        const data = posSums[op.no];
        if (data && data.total > 0) {
            op.biaya = Math.round(data.total);
            if (data.items.length > 0) {
                op.keterangan = `Sinkron Tabel Bantu (${data.items.length} item: ${data.items.slice(0, 3).join(", ")}${data.items.length > 3 ? "..." : ""})`;
            }
        }
    });

    syncNotification.value = "Data rincian Tabel Bantu berhasil disinkronkan ke 17 Pos Bab V!";
    setTimeout(() => {
        syncNotification.value = "";
    }, 4500);
}

function tambahBarisBantu() {
    const list = form.value.rincian_operasional_bantu || [];
    list.push({
        no: list.length + 1,
        nama: "Pengeluaran Baru",
        harga_pokok: 0,
        qty_hari: 0,
        qty_minggu: 0,
        qty_periode: 0,
        qty_bulan: 0,
        pos_ref: 16,
    });
}

function hapusBarisBantu(idx) {
    if (confirm("Hapus baris penggunaan barang ini dari Tabel Bantu?")) {
        form.value.rincian_operasional_bantu.splice(idx, 1);
    }
}

function resetTabelBantu() {
    if (confirm("Kembalikan Tabel Bantu ke template 45 item default? Perubahan yang belum disimpan akan direset.")) {
        form.value.rincian_operasional_bantu = getDefaultTabelBantuOperasional(props.autoOperasional);
    }
}

function onBantuHargaInput(item, event) {
    const raw = event.target.value;
    const clean = raw.replace(/\D/g, "");
    if (!clean) {
        item.harga_pokok = 0;
        event.target.value = "";
        return;
    }
    const num = parseInt(clean, 10);
    item.harga_pokok = num;
    event.target.value = num.toLocaleString("id-ID");
}

function onBantuHargaBlur(item, event) {
    event.target.value = formatNominalDots(item.harga_pokok);
}

function onBantuQtyInput(item, field, event) {
    const val = parseFloat(event.target.value);
    item[field] = isNaN(val) ? 0 : val;
}

// State & Fungsi Pengelolaan Jadwal PM Harian per Tanggal
const showJadwalPmPanel = ref(false);

const jadwalPmSummary = computed(() => {
    return calculateJadwalPmSummary(form.value.jadwal_pm_harian || []);
});

// State & Modal Penyesuaian Kelompok Sasaran Harian
const showModalKelompokHarian = ref(false);
const activeDateItem = ref(null);
const modalKelompokDraft = ref([]);
const activeReceivingPresetModal = ref('all');
const applyToAllWeekdaysModal = ref(false);

const countMasterSekolah = computed(() => {
    return (props.kelompokList || []).filter((k) => !isPosyanduKelompok(k.kategori, k.nama_kelompok)).length;
});
const countMasterPosyandu = computed(() => {
    return (props.kelompokList || []).filter((k) => isPosyanduKelompok(k.kategori, k.nama_kelompok)).length;
});

function openModalKelompokTanggal(item) {
    activeDateItem.value = item;
    applyToAllWeekdaysModal.value = false;

    const needsNormalize = !Array.isArray(item.kelompoks) ||
        item.kelompoks.length === 0 ||
        item.kelompoks.some((k) => !Array.isArray(k.rincian) || k.rincian.length === 0);

    if (needsNormalize) {
        item.kelompoks = normalizeKelompoksForDate(props.kelompokList || [], item.kelompoks || null, item.is_aktif);
    }
    modalKelompokDraft.value = JSON.parse(JSON.stringify(item.kelompoks));
    updateModalPresetState();
    showModalKelompokHarian.value = true;
}

function updateModalPresetState() {
    const list = modalKelompokDraft.value || [];
    if (list.length === 0) {
        activeReceivingPresetModal.value = 'all';
        return;
    }
    const allMenerima = list.every((k) => k.status_menerima !== false);
    const allTolak = list.every((k) => k.status_menerima === false);
    const sekolahOnly = list.every((k) => (k.is_posyandu ? k.status_menerima === false : k.status_menerima !== false));
    const posyanduOnly = list.every((k) => (k.is_posyandu ? k.status_menerima !== false : k.status_menerima === false));

    if (allMenerima) activeReceivingPresetModal.value = 'all';
    else if (allTolak) activeReceivingPresetModal.value = 'none';
    else if (sekolahOnly) activeReceivingPresetModal.value = 'sekolah';
    else if (posyanduOnly) activeReceivingPresetModal.value = 'posyandu';
    else activeReceivingPresetModal.value = 'custom';
}

function applyPresetModal(preset) {
    activeReceivingPresetModal.value = preset;
    applyPresetToKelompoks(modalKelompokDraft.value, preset);
}

function toggleKelompokStatusModal(k, forceStatus = null) {
    k.status_menerima = forceStatus !== null ? forceStatus : !k.status_menerima;
    (k.rincian || []).forEach((r) => {
        r.is_menerima = k.status_menerima;
        r.total = r.is_menerima ? ((Math.max(0, parseInt(r.jumlah_laki_laki, 10) || 0)) + (Math.max(0, parseInt(r.jumlah_perempuan, 10) || 0))) : 0;
    });
    const totals = calculateKelompokTotalsFromRincian(k.rincian, k.status_menerima);
    k.porsi_kecil = totals.porsi_kecil;
    k.porsi_besar = totals.porsi_besar;
    k.total_pm = totals.total_pm;
    updateModalPresetState();
}

function resetSingleKelompokModal(k) {
    k.status_menerima = true;
    (k.rincian || []).forEach((r) => {
        r.is_menerima = true;
        r.jumlah_laki_laki = r.default_laki_laki ?? r.jumlah_laki_laki;
        r.jumlah_perempuan = r.default_perempuan ?? r.jumlah_perempuan;
        r.total = (parseInt(r.jumlah_laki_laki, 10) || 0) + (parseInt(r.jumlah_perempuan, 10) || 0);
    });
    const totals = calculateKelompokTotalsFromRincian(k.rincian, true);
    k.porsi_kecil = totals.porsi_kecil;
    k.porsi_besar = totals.porsi_besar;
    k.total_pm = totals.total_pm;
    updateModalPresetState();
}

// State & Method Modal Edit Detail Sub-Kategori PM per Kelompok Sasaran
const showModalEditSubKategori = ref(false);
const editingKelompokForSubKategori = ref(null);
const editFormSubKategori = ref([]);

function handleOpenModalEditSubKategori(kelompok) {
    editingKelompokForSubKategori.value = kelompok;

    if (!Array.isArray(kelompok.rincian) || kelompok.rincian.length === 0) {
        const master = (props.kelompokList || []).find((m) => m.id === kelompok.id);
        if (master && Array.isArray(master.rincian) && master.rincian.length > 0) {
            kelompok.rincian = master.rincian.map((r) => ({
                id: r.id,
                sub_kategori: r.sub_kategori || 'Sub-Kategori',
                jenis_porsi: r.jenis_porsi || (kelompok.is_posyandu ? 'Porsi Kecil' : 'Porsi Besar'),
                default_laki_laki: parseInt(r.jumlah_laki_laki, 10) || 0,
                default_perempuan: parseInt(r.jumlah_perempuan, 10) || 0,
                jumlah_laki_laki: parseInt(r.jumlah_laki_laki, 10) || 0,
                jumlah_perempuan: parseInt(r.jumlah_perempuan, 10) || 0,
                total: (parseInt(r.jumlah_laki_laki, 10) || 0) + (parseInt(r.jumlah_perempuan, 10) || 0),
                is_menerima: kelompok.status_menerima !== false,
            }));
        } else {
            const defPk = kelompok.porsi_kecil || kelompok.default_porsi_kecil || 0;
            const defPb = kelompok.porsi_besar || kelompok.default_porsi_besar || 0;
            kelompok.rincian = [];
            if (defPk > 0 || kelompok.is_posyandu) {
                kelompok.rincian.push({
                    id: null,
                    sub_kategori: 'Porsi Kecil (Jenjang Awal / Balita)',
                    jenis_porsi: 'Porsi Kecil',
                    default_laki_laki: Math.ceil(defPk / 2),
                    default_perempuan: Math.floor(defPk / 2),
                    jumlah_laki_laki: Math.ceil(defPk / 2),
                    jumlah_perempuan: Math.floor(defPk / 2),
                    total: defPk,
                    is_menerima: kelompok.status_menerima !== false,
                });
            }
            if (defPb > 0 || !kelompok.is_posyandu) {
                kelompok.rincian.push({
                    id: null,
                    sub_kategori: 'Porsi Besar (Jenjang Lanjut / Umum)',
                    jenis_porsi: 'Porsi Besar',
                    default_laki_laki: Math.ceil(defPb / 2),
                    default_perempuan: Math.floor(defPb / 2),
                    jumlah_laki_laki: Math.ceil(defPb / 2),
                    jumlah_perempuan: Math.floor(defPb / 2),
                    total: defPb,
                    is_menerima: kelompok.status_menerima !== false,
                });
            }
        }
    }

    editFormSubKategori.value = JSON.parse(JSON.stringify(kelompok.rincian || [])).map((r) => ({
        ...r,
        is_menerima: r.is_menerima !== undefined ? Boolean(r.is_menerima) : (kelompok.status_menerima !== false),
        jumlah_laki_laki: Math.max(0, parseInt(r.jumlah_laki_laki, 10) || 0),
        jumlah_perempuan: Math.max(0, parseInt(r.jumlah_perempuan, 10) || 0),
        total: (r.is_menerima !== false)
            ? ((Math.max(0, parseInt(r.jumlah_laki_laki, 10) || 0)) + (Math.max(0, parseInt(r.jumlah_perempuan, 10) || 0)))
            : 0,
    }));
    showModalEditSubKategori.value = true;
}

const modalSubKategoriTotals = computed(() => {
    let pk = 0;
    let pb = 0;
    let activeCount = 0;
    const list = editFormSubKategori.value || [];
    list.forEach((r) => {
        if (r.is_menerima !== false) {
            activeCount += 1;
            const l = Math.max(0, parseInt(r.jumlah_laki_laki, 10) || 0);
            const p = Math.max(0, parseInt(r.jumlah_perempuan, 10) || 0);
            const subtotal = l + p;
            if (r.jenis_porsi === 'Porsi Kecil') {
                pk += subtotal;
            } else {
                pb += subtotal;
            }
        }
    });
    return {
        pk,
        pb,
        total: pk + pb,
        activeCount,
        totalCount: list.length,
    };
});

function handleSetAllSubKategoriMenerima(status) {
    (editFormSubKategori.value || []).forEach((r) => {
        r.is_menerima = Boolean(status);
        r.total = r.is_menerima ? ((Math.max(0, parseInt(r.jumlah_laki_laki, 10) || 0)) + (Math.max(0, parseInt(r.jumlah_perempuan, 10) || 0))) : 0;
    });
}

function handleSaveEditSubKategori() {
    if (!editingKelompokForSubKategori.value) return;

    const totals = modalSubKategoriTotals.value;
    editingKelompokForSubKategori.value.rincian = editFormSubKategori.value.map((r) => {
        const isM = r.is_menerima !== false;
        const l = Math.max(0, parseInt(r.jumlah_laki_laki, 10) || 0);
        const p = Math.max(0, parseInt(r.jumlah_perempuan, 10) || 0);
        return {
            ...r,
            is_menerima: isM,
            jumlah_laki_laki: l,
            jumlah_perempuan: p,
            total: isM ? (l + p) : 0,
        };
    });

    editingKelompokForSubKategori.value.porsi_kecil = totals.pk;
    editingKelompokForSubKategori.value.porsi_besar = totals.pb;
    editingKelompokForSubKategori.value.total_pm = totals.total;

    if (totals.total === 0) {
        editingKelompokForSubKategori.value.status_menerima = false;
    } else {
        editingKelompokForSubKategori.value.status_menerima = true;
    }

    updateModalPresetState();
    showModalEditSubKategori.value = false;
    editingKelompokForSubKategori.value = null;
}

function getKelompokSubKategoriBadgeInfo(k) {
    if (!Array.isArray(k.rincian) || k.rincian.length === 0) {
        return null;
    }
    const totalCount = k.rincian.length;
    const activeCount = k.rincian.filter((r) => r.is_menerima !== false).length;
    return {
        totalCount,
        activeCount,
        isAllActive: activeCount === totalCount,
        isAllOff: activeCount === 0,
        isPartial: activeCount > 0 && activeCount < totalCount,
    };
}

function onModalKelompokInput(k, field, event) {
    const raw = event.target.value;
    const clean = raw.replace(/\D/g, "");
    const num = clean ? parseInt(clean, 10) : 0;
    k[field] = num;
    k.total_pm = (Number(k.porsi_kecil) || 0) + (Number(k.porsi_besar) || 0);
    event.target.value = clean ? num.toLocaleString("id-ID") : "";
}

function onModalKelompokBlur(k, field, event) {
    event.target.value = formatNominalDots(k[field] || 0);
}

const modalDraftTotals = computed(() => {
    return calculateDateTotalsFromKelompoks(modalKelompokDraft.value || []);
});

function saveModalKelompokHarian() {
    if (!activeDateItem.value) return;

    const totals = modalDraftTotals.value;
    activeDateItem.value.kelompoks = JSON.parse(JSON.stringify(modalKelompokDraft.value));
    activeDateItem.value.porsi_kecil = totals.porsi_kecil;
    activeDateItem.value.porsi_besar = totals.porsi_besar;
    activeDateItem.value.total_pm = totals.total_pm;

    if (totals.total_pm > 0) {
        activeDateItem.value.is_aktif = true;
        if (totals.count_tidak_menerima > 0) {
            activeDateItem.value.keterangan = `Pelayanan Efektif (${totals.count_menerima} titik menerima, ${totals.count_tidak_menerima} titik libur)`;
        } else {
            activeDateItem.value.keterangan = 'Pelayanan Seluruh Kelompok';
        }
    } else {
        activeDateItem.value.is_aktif = false;
        activeDateItem.value.keterangan = 'Hari Libur / Tidak Melayani';
    }

    if (applyToAllWeekdaysModal.value) {
        (form.value.jadwal_pm_harian || []).forEach((it) => {
            if (!it.is_weekend) {
                it.kelompoks = JSON.parse(JSON.stringify(modalKelompokDraft.value));
                it.porsi_kecil = totals.porsi_kecil;
                it.porsi_besar = totals.porsi_besar;
                it.total_pm = totals.total_pm;
                it.is_aktif = totals.total_pm > 0;
                it.keterangan = activeDateItem.value.keterangan;
            }
        });
    }

    showModalKelompokHarian.value = false;
}

function getKelompokStatusInfo(item) {
    const totalMaster = (props.kelompokList || []).length;
    if (!item.is_aktif) {
        return {
            status: 'libur',
            label: 'Hari Libur',
            menerima: 0,
            tidakMenerima: totalMaster,
        };
    }
    const kelompoks = item.kelompoks || [];
    if (kelompoks.length === 0) {
        return {
            status: 'semua',
            label: 'Semua Menerima',
            menerima: totalMaster,
            tidakMenerima: 0,
        };
    }
    const totals = calculateDateTotalsFromKelompoks(kelompoks);
    if (totals.count_tidak_menerima === 0) {
        return {
            status: 'semua',
            label: `${totals.count_menerima} Titik Menerima`,
            menerima: totals.count_menerima,
            tidakMenerima: 0,
        };
    }
    return {
        status: 'sebagian',
        label: `${totals.count_menerima} Menerima · ${totals.count_tidak_menerima} Libur`,
        menerima: totals.count_menerima,
        tidakMenerima: totals.count_tidak_menerima,
    };
}

function toggleTanggalAktif(item) {
    item.is_aktif = !item.is_aktif;
    if (!item.kelompoks || item.kelompoks.length === 0) {
        item.kelompoks = normalizeKelompoksForDate(props.kelompokList || [], null, item.is_aktif);
    } else {
        applyPresetToKelompoks(item.kelompoks, item.is_aktif ? 'all' : 'none');
    }
    const totals = calculateDateTotalsFromKelompoks(item.kelompoks);
    item.porsi_kecil = totals.porsi_kecil;
    item.porsi_besar = totals.porsi_besar;
    item.total_pm = totals.total_pm;

    if (!item.is_aktif) {
        item.keterangan = item.is_weekend
            ? (item.nama_hari === 'Minggu' ? 'Hari Libur (Minggu)' : 'Hari Libur (Sabtu)')
            : 'Hari Libur / Tidak Melayani';
    } else {
        item.keterangan = 'Pelayanan Seluruh Kelompok';
    }
}

function onDailyPmInput(item, field, event) {
    const raw = event.target.value;
    const clean = raw.replace(/\D/g, "");
    const num = clean ? parseInt(clean, 10) : 0;
    item[field] = num;
    item.total_pm = (Number(item.porsi_kecil) || 0) + (Number(item.porsi_besar) || 0);
    if (item.total_pm > 0 && !item.is_aktif) {
        item.is_aktif = true;
    }
    event.target.value = clean ? num.toLocaleString("id-ID") : "";
}

function onDailyPmBlur(item, field, event) {
    event.target.value = formatNominalDots(item[field] || 0);
}

function sinkronkanJadwalPmKeRab() {
    const s = jadwalPmSummary.value;
    if (s.hariEfektif > 0) {
        form.value.hari_efektif = s.hariEfektif;
        if (form.value.rincian_porsi && form.value.rincian_porsi[0]) {
            form.value.rincian_porsi[0].jumlah_paket = s.rerataPorsiKecil;
        }
        if (form.value.rincian_porsi && form.value.rincian_porsi[2]) {
            form.value.rincian_porsi[2].jumlah_paket = s.rerataPorsiBesar;
        }
        if (form.value.rincian_porsi && form.value.rincian_porsi[4]) {
            form.value.rincian_porsi[4].jumlah_paket = s.rerataTotalPm;
        }
        form.value.catatan = `Jumlah hari pelayanan efektif = ${s.hariEfektif} hari (${s.hariEfektif} hari aktif melayani dari total ${s.totalHari} hari kalender). Berdasarkan rincian penetapan PM harian per tanggal.`;
        syncNotification.value = `Berhasil menyinkronkan data PM harian! Hari Efektif: ${s.hariEfektif} hari, Kuota Porsi Kecil: ${s.rerataPorsiKecil.toLocaleString('id-ID')} paket, Porsi Besar: ${s.rerataPorsiBesar.toLocaleString('id-ID')} paket, Operasional: ${s.rerataTotalPm.toLocaleString('id-ID')} paket.`;
        setTimeout(() => {
            syncNotification.value = "";
        }, 5000);
    } else {
        alert("Peringatan: Belum ada tanggal yang diset Aktif Melayani.");
    }
}

function setSemuaHariKerja() {
    form.value.jadwal_pm_harian = generateDefaultJadwalPmHarian(
        selectedYear.value,
        selectedMonth.value,
        props.stats?.total_porsi_kecil,
        props.stats?.total_porsi_besar,
        null,
        props.kelompokList || []
    );
    sinkronkanJadwalPmKeRab();
}

function setSemuaAktif() {
    (form.value.jadwal_pm_harian || []).forEach((it) => {
        it.is_aktif = true;
        if (!it.kelompoks || it.kelompoks.length === 0) {
            it.kelompoks = normalizeKelompoksForDate(props.kelompokList || [], null, true);
        } else {
            applyPresetToKelompoks(it.kelompoks, 'all');
        }
        const totals = calculateDateTotalsFromKelompoks(it.kelompoks);
        it.porsi_kecil = totals.porsi_kecil;
        it.porsi_besar = totals.porsi_besar;
        it.total_pm = totals.total_pm;
        it.keterangan = 'Pelayanan Seluruh Kelompok';
    });
    sinkronkanJadwalPmKeRab();
}

// Simpan RAB ke Backend
function submitRab() {
    isSaving.value = true;
    router.post(
        route("keuangan.rab.store"),
        {
            tahun: selectedYear.value,
            bulan: selectedMonth.value,
            hari_efektif: form.value.hari_efektif,
            rincian_porsi: calculatedItems.value,
            rincian_operasional: form.value.rincian_operasional,
            rincian_operasional_bantu: form.value.rincian_operasional_bantu,
            jadwal_pm_harian: form.value.jadwal_pm_harian,
            total_biaya_per_hari: totalBiayaHari.value,
            total_biaya_2mingguan: totalBiaya2Mingguan.value,
            total_biaya_per_bulan: totalBiayaBulan.value,
            catatan: form.value.catatan,
            nama_yayasan: form.value.nama_yayasan,
            nomor_rekening_yayasan: form.value.nomor_rekening_yayasan,
            npwp: form.value.npwp,
            kepala_sppg_nama: form.value.kepala_sppg_nama,
            pengawas_keuangan_nama: form.value.pengawas_keuangan_nama,
            pengawas_gizi_nama: form.value.pengawas_gizi_nama,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSaving.value = false;
            },
        }
    );
}

// Download Excel RAB Bulan Aktif
async function handleDownloadBulanan() {
    await downloadRabBulananExcel({
        rabData: {
            ...form.value,
            rincian_porsi: calculatedItems.value,
            jadwal_pm_harian: form.value.jadwal_pm_harian,
        },
        month: selectedMonth.value,
        year: selectedYear.value,
        unitSppg: props.unitSppg,
        pejabatSppg: props.pejabatSppg,
        rekeningUtama: props.rekeningUtama,
        kopDokumen: props.kopDokumen,
    });
}

// Download Excel RAB Tahunan
async function handleDownloadTahunan() {
    await downloadRabTahunanExcel({
        rabList: props.rabList,
        year: selectedYear.value,
        unitSppg: props.unitSppg,
        pejabatSppg: props.pejabatSppg,
        rekeningUtama: props.rekeningUtama,
        kopDokumen: props.kopDokumen,
    });
}

// Tahun dropdown
const availableYears = [2025, 2026, 2027, 2028, 2029];
const isYearDropdownOpen = ref(false);
const yearDropdownRef = ref(null);

function toggleYearDropdown(e) {
    if (e) e.stopPropagation();
    isYearDropdownOpen.value = !isYearDropdownOpen.value;
    if (isYearDropdownOpen.value) {
        isDownloadDropdownOpen.value = false;
    }
}

function selectYear(yr) {
    isYearDropdownOpen.value = false;
    if (selectedYear.value !== yr) {
        selectedYear.value = yr;
        router.get(route("keuangan.rab"), { tahun: yr }, { preserveState: true });
    }
}

// State dropdown download
const isDownloadDropdownOpen = ref(false);
const downloadDropdownRef = ref(null);

function toggleDownloadDropdown(e) {
    if (e) e.stopPropagation();
    isDownloadDropdownOpen.value = !isDownloadDropdownOpen.value;
    if (isDownloadDropdownOpen.value) {
        isYearDropdownOpen.value = false;
    }
}

function handleClickOutside(e) {
    if (downloadDropdownRef.value && !downloadDropdownRef.value.contains(e.target)) {
        isDownloadDropdownOpen.value = false;
    }
    if (yearDropdownRef.value && !yearDropdownRef.value.contains(e.target)) {
        isYearDropdownOpen.value = false;
    }
}

async function handleSelectDownload(type) {
    isDownloadDropdownOpen.value = false;
    if (type === "bulanan") {
        await handleDownloadBulanan();
    } else if (type === "tahunan") {
        await handleDownloadTahunan();
    }
}

// Helpers formatting nominal dengan titik
function formatNominalDots(val) {
    if (val === null || val === undefined || val === "") return "0";
    const clean = String(val).replace(/\D/g, "");
    if (!clean) return "0";
    return parseInt(clean, 10).toLocaleString("id-ID");
}

function onOperasionalBiayaInput(op, event) {
    const raw = event.target.value;
    const clean = raw.replace(/\D/g, "");
    if (!clean) {
        op.biaya = 0;
        event.target.value = "";
        return;
    }
    const num = parseInt(clean, 10);
    op.biaya = num;
    event.target.value = num.toLocaleString("id-ID");
}

function onOperasionalBiayaBlur(op, event) {
    if (!op.biaya) op.biaya = 0;
    event.target.value = formatNominalDots(op.biaya);
}

function onHargaSatuanInput(item, event) {
    const raw = event.target.value;
    const clean = raw.replace(/\D/g, "");
    if (!clean) {
        item.harga_satuan = 0;
        event.target.value = "";
        return;
    }
    const num = parseInt(clean, 10);
    item.harga_satuan = num;
    event.target.value = num.toLocaleString("id-ID");
}

function onHargaSatuanBlur(item, event) {
    if (!item.harga_satuan) item.harga_satuan = 0;
    event.target.value = formatNominalDots(item.harga_satuan);
}

function onJumlahPaketInput(item, event) {
    const raw = event.target.value;
    const clean = raw.replace(/\D/g, "");
    if (!clean) {
        item.jumlah_paket = 0;
        event.target.value = "";
        return;
    }
    const num = parseInt(clean, 10);
    item.jumlah_paket = num;
    event.target.value = num.toLocaleString("id-ID");
}

function onJumlahPaketBlur(item, event) {
    if (!item.jumlah_paket) item.jumlah_paket = 0;
    event.target.value = formatNominalDots(item.jumlah_paket);
}

function onInputFocus(event) {
    event.target.select();
}

// Inisialisasi awal
onMounted(() => {
    loadMonthData(selectedMonth.value);
    document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});

watch(
    () => props.rabList,
    () => {
        loadMonthData(selectedMonth.value);
    },
    { deep: true }
);

// Jika hari efektif diubah di form dan belum disimpan, update info rumus dasar operasional
watch(
    () => form.value.hari_efektif,
    (newVal) => {
        if (!newVal || newVal < 1) return;
        form.value.rincian_operasional.forEach((it) => {
            if (it.is_auto_supported) {
                it.rumus_dasar = getAutoFormulaDesc(it.no, newVal);
            }
        });
    }
);
</script>

<template>
    <div class="space-y-5">
        <!-- HEADER KOP & JUDUL -->
        <div
            class="bg-white rounded-xl p-4 sm:p-5 border border-slate-200/80 shadow-xs relative"
        >
            <!-- Background glow dikurung agar tidak memotong dropdown popover -->
            <div class="absolute inset-0 rounded-xl overflow-hidden pointer-events-none">
                <div
                    class="absolute -right-8 -top-8 w-44 h-44 bg-emerald-500/5 rounded-full blur-2xl"
                ></div>
            </div>

            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-3.5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="h-9 w-9 rounded-lg bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-xs shrink-0"
                    >
                        <Calculator class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">
                                Rencana Anggaran Biaya (RAB)
                            </h1>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                            >
                                Juknis Resmi BGN
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Penyusunan Rencana Anggaran Biaya Bulanan & Tahunan Program Makan Bergizi Gratis (Lampiran III & Bab VI BGN)
                        </p>
                    </div>
                </div>

                <!-- Aksi Utama & Mode Switch -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Mode Tab Switcher (Bulanan / Tahunan) -->
                    <div
                        class="h-8 inline-flex items-center p-0.5 bg-slate-100 rounded-lg border border-slate-200 text-xs font-medium"
                    >
                        <button
                            type="button"
                            @click="activeMode = 'bulanan'"
                            :class="[
                                'h-7 px-3 flex items-center justify-center rounded-md transition-all cursor-pointer font-semibold text-xs',
                                activeMode === 'bulanan'
                                    ? 'bg-white text-emerald-800 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            RAB Bulanan
                        </button>
                        <button
                            type="button"
                            @click="activeMode = 'tahunan'"
                            :class="[
                                'h-7 px-3 flex items-center justify-center rounded-md transition-all cursor-pointer font-semibold text-xs',
                                activeMode === 'tahunan'
                                    ? 'bg-white text-emerald-800 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            RAB Tahunan
                        </button>
                    </div>

                    <!-- Tahun Selector Dropdown -->
                    <div class="relative" ref="yearDropdownRef">
                        <button
                            type="button"
                            @click="toggleYearDropdown"
                            class="h-8 inline-flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 px-3 rounded-lg text-xs font-semibold text-slate-800 shadow-2xs transition-colors cursor-pointer"
                            :aria-expanded="isYearDropdownOpen"
                        >
                            <Calendar class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                            <span class="text-slate-500 text-xs font-medium shrink-0">Tahun:</span>
                            <span class="font-bold text-slate-900 shrink-0">{{ selectedYear }}</span>
                            <ChevronDown
                                class="h-3.5 w-3.5 text-slate-400 shrink-0 transition-transform duration-200"
                                :class="{ 'rotate-180': isYearDropdownOpen }"
                            />
                        </button>

                        <!-- Menu Dropdown Tahun -->
                        <div
                            v-if="isYearDropdownOpen"
                            class="absolute left-0 mt-1.5 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-50 animate-in fade-in zoom-in-95 duration-100"
                        >
                            <div class="px-3.5 py-1.5 text-[10.5px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                Pilih Tahun Anggaran
                            </div>
                            <div class="space-y-1 p-1.5">
                                <button
                                    v-for="yr in availableYears"
                                    :key="yr"
                                    type="button"
                                    @click="selectYear(yr)"
                                    :class="[
                                        'w-full text-left px-3 py-2 rounded-lg text-xs font-semibold flex items-center justify-between transition-colors cursor-pointer',
                                        selectedYear === yr
                                            ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200'
                                            : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900',
                                    ]"
                                >
                                    <span>Tahun {{ yr }}</span>
                                    <CheckCircle2 v-if="selectedYear === yr" class="h-4 w-4 text-emerald-600 shrink-0" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Dropdown Download Excel -->
                    <div class="relative" ref="downloadDropdownRef">
                        <button
                            type="button"
                            @click="toggleDownloadDropdown"
                            class="h-8 inline-flex items-center gap-1.5 px-3 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition-colors cursor-pointer"
                            :aria-expanded="isDownloadDropdownOpen"
                        >
                            <Download class="h-3.5 w-3.5 shrink-0" />
                            <span>Download Excel</span>
                            <ChevronDown
                                class="h-3.5 w-3.5 shrink-0 transition-transform duration-200"
                                :class="{ 'rotate-180': isDownloadDropdownOpen }"
                            />
                        </button>

                        <!-- Menu Dropdown -->
                        <div
                            v-if="isDownloadDropdownOpen"
                            class="absolute right-0 mt-1.5 w-72 bg-white rounded-xl shadow-xl border border-slate-200/90 py-1.5 z-50 animate-in fade-in zoom-in-95 duration-100"
                        >
                            <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Pilihan Dokumen Excel
                            </div>

                            <!-- Opsi 1: Download RAB Bulan Terpilih -->
                            <button
                                type="button"
                                @click="handleSelectDownload('bulanan')"
                                class="w-full text-left px-3 py-2 text-xs flex items-center gap-2.5 text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition-colors cursor-pointer group"
                            >
                                <div class="h-7 w-7 rounded-lg bg-emerald-100/80 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                    <FileSpreadsheet class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-800 group-hover:text-emerald-900 leading-tight">
                                        RAB Bulan {{ monthNames[selectedMonth] }} {{ selectedYear }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 leading-tight mt-0.5">
                                        Lampiran III Format Juknis BGN
                                    </div>
                                </div>
                            </button>

                            <div class="my-1 border-t border-slate-100"></div>

                            <!-- Opsi 2: Download RAB Tahunan -->
                            <button
                                type="button"
                                @click="handleSelectDownload('tahunan')"
                                class="w-full text-left px-3 py-2 text-xs flex items-center gap-2.5 text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition-colors cursor-pointer group"
                            >
                                <div class="h-7 w-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 group-hover:bg-slate-800 group-hover:text-white transition-colors">
                                    <Download class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-800 group-hover:text-slate-900 leading-tight">
                                        RAB Tahunan (12 Bulan) {{ selectedYear }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 leading-tight mt-0.5">
                                        Bab VI & Matriks Rekap 12 Bulan
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABS PEMILIH BULAN (Jika mode bulanan) -->
            <div
                v-if="activeMode === 'bulanan'"
                class="mt-4 pt-3.5 border-t border-slate-100"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                        <Calendar class="h-3.5 w-3.5 text-emerald-600" />
                        Pilih Bulan Operasional:
                    </span>
                    <span class="text-[11px] text-slate-400">
                        *Klik bulan untuk menyusun atau mengedit RAB
                    </span>
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-12 gap-1.5">
                    <button
                        v-for="m in 12"
                        :key="m"
                        type="button"
                        @click="loadMonthData(m)"
                        :class="[
                            'relative px-2 py-1.5 rounded-lg text-xs font-semibold text-center border transition-all cursor-pointer',
                            selectedMonth === m
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs ring-1 ring-emerald-500/20'
                                : savedRabMap[m]
                                ? 'bg-emerald-50/80 text-emerald-800 border-emerald-200 hover:bg-emerald-100'
                                : 'bg-slate-50 text-slate-600 border-slate-200/80 hover:bg-slate-100',
                        ]"
                    >
                        <div class="truncate text-xs font-semibold leading-tight">{{ monthNames[m] }}</div>
                        <div
                            class="text-[10px] mt-0.5 truncate font-normal"
                            :class="selectedMonth === m ? 'text-emerald-100' : 'text-slate-400'"
                        >
                            {{ savedRabMap[m] ? 'Tersimpan' : 'Belum Dibuat' }}
                        </div>
                        <!-- Status Dot -->
                        <span
                            v-if="savedRabMap[m]"
                            class="absolute top-1 right-1 h-1.5 w-1.5 rounded-full"
                            :class="selectedMonth === m ? 'bg-white' : 'bg-emerald-500'"
                        ></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ======================= MODE BULANAN ======================= -->
        <div v-if="activeMode === 'bulanan'" class="space-y-5">
            <!-- RINGKASAN METRIK KARTU -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Porsi Kecil (Rp 8.000)</span>
                    <div class="flex items-baseline justify-between mt-1">
                        <span class="text-sm sm:text-base font-bold text-slate-800">
                            {{ calculatedItems[0]?.jumlah_paket || 0 }} Paket
                        </span>
                        <span class="text-xs font-semibold text-emerald-600">
                            {{ formatRupiah(calculatedItems[0]?.biaya_per_bulan) }}
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">Balita, PAUD/TK & SD Kelas 1-3</span>
                </div>

                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Porsi Besar (Rp 10.000)</span>
                    <div class="flex items-baseline justify-between mt-1">
                        <span class="text-sm sm:text-base font-bold text-slate-800">
                            {{ calculatedItems[2]?.jumlah_paket || 0 }} Paket
                        </span>
                        <span class="text-xs font-semibold text-emerald-600">
                            {{ formatRupiah(calculatedItems[2]?.biaya_per_bulan) }}
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">SD 4-6, SMP, SMA, Pendidik & 3B</span>
                </div>

                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Pagu Operasional (Rp 3.000)</span>
                    <div class="flex items-baseline justify-between mt-1">
                        <span class="text-sm sm:text-base font-bold text-slate-800">
                            {{ calculatedItems[4]?.jumlah_paket || 0 }} Paket
                        </span>
                        <span class="text-xs font-semibold text-amber-600">
                            {{ formatRupiah(calculatedItems[4]?.biaya_per_bulan) }}
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">Pagu Maksimal Operasional BGN</span>
                </div>

                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total RAB {{ monthNames[selectedMonth] }}</span>
                    <div class="text-sm sm:text-base font-bold text-slate-800 tracking-tight mt-1">
                        {{ formatRupiah(totalBiayaBulan) }}
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">
                        {{ form.hari_efektif }} Hari Pelayanan Efektif
                    </span>
                </div>
            </div>

            <!-- ======================= SECTION PENETAPAN PM HARIAN PER TANGGAL ======================= -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-3.5 sm:p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-start gap-2.5">
                        <div class="p-2 rounded-lg bg-emerald-600 text-white shadow-2xs shrink-0">
                            <CalendarDays class="h-4 w-4" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-800">
                                    Penetapan Jumlah PM Harian Per Tanggal ({{ monthNames[selectedMonth] }} {{ selectedYear }})
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    Akurasi PM & Hari Pelayanan
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Tentukan hari aktif melayani serta kuota PM porsi kecil dan porsi besar setiap tanggal. Rerata PM dan total hari efektif langsung tersinkron ke RAB.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="sinkronkanJadwalPmKeRab"
                            title="Terapkan hasil rekapitulasi PM harian ke kuota paket MBG dan hari efektif di tabel RAB utama"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-2xs cursor-pointer"
                        >
                            <Check class="h-3.5 w-3.5" />
                            <span>Terapkan ke Kuota Paket RAB</span>
                        </button>

                        <button
                            type="button"
                            @click="setSemuaHariKerja"
                            title="Reset jadwal kembali ke standar hari kerja (Senin - Jumat aktif, Sabtu - Minggu libur)"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors shadow-2xs cursor-pointer"
                        >
                            <RotateCcw class="h-3.5 w-3.5 text-slate-400" />
                            <span>Senin–Jumat</span>
                        </button>

                        <button
                            type="button"
                            @click="showJadwalPmPanel = !showJadwalPmPanel"
                            class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500 transition-colors cursor-pointer"
                            :title="showJadwalPmPanel ? 'Sembunyikan Tabel Tanggal' : 'Buka Tabel Tanggal'"
                        >
                            <ChevronUp v-if="showJadwalPmPanel" class="h-4 w-4" />
                            <ChevronDown v-else class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- 4 KARTU RINGKASAN REKAPITULASI PM HARIAN -->
                <div class="p-3.5 sm:p-4 border-b border-slate-100 bg-white">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">Hari Pelayanan Efektif</span>
                            <div class="flex items-baseline gap-1 mt-0.5">
                                <span class="text-base sm:text-lg font-bold text-slate-900">{{ jadwalPmSummary.hariEfektif }} Hari</span>
                                <span class="text-[11px] text-slate-400">/ {{ jadwalPmSummary.totalHari }} hari</span>
                            </div>
                            <span class="text-[10.5px] text-slate-500 block mt-0.5">{{ jadwalPmSummary.hariLibur }} hari libur/tidak melayani</span>
                        </div>

                        <div class="p-2.5 rounded-lg bg-emerald-50/50 border border-emerald-200/80">
                            <span class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider block">Rerata Porsi Kecil</span>
                            <div class="flex items-baseline gap-1 mt-0.5">
                                <span class="text-base sm:text-lg font-bold text-emerald-800">{{ jadwalPmSummary.rerataPorsiKecil.toLocaleString('id-ID') }}</span>
                                <span class="text-[11px] text-emerald-600 font-medium">PM / hari</span>
                            </div>
                            <span class="text-[10.5px] text-emerald-700 block mt-0.5">Balita, PAUD/TK & SD Kelas 1–3</span>
                        </div>

                        <div class="p-2.5 rounded-lg bg-emerald-50/50 border border-emerald-200/80">
                            <span class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider block">Rerata Porsi Besar</span>
                            <div class="flex items-baseline gap-1 mt-0.5">
                                <span class="text-base sm:text-lg font-bold text-emerald-800">{{ jadwalPmSummary.rerataPorsiBesar.toLocaleString('id-ID') }}</span>
                                <span class="text-[11px] text-emerald-600 font-medium">PM / hari</span>
                            </div>
                            <span class="text-[10.5px] text-emerald-700 block mt-0.5">SD 4–6, SMP, SMA, Pendidik & 3B</span>
                        </div>

                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">Total Sasaran Sebulan</span>
                            <div class="flex items-baseline gap-1 mt-0.5">
                                <span class="text-base sm:text-lg font-bold text-slate-900">{{ jadwalPmSummary.grandTotalPm.toLocaleString('id-ID') }}</span>
                                <span class="text-[11px] text-slate-500 font-medium">Paket</span>
                            </div>
                            <span class="text-[10.5px] text-slate-500 block mt-0.5">Rerata: {{ jadwalPmSummary.rerataTotalPm.toLocaleString('id-ID') }} PM / hari aktif</span>
                        </div>
                    </div>
                </div>

                <!-- TABEL PER TANGGAL (BISA DITUTUP / DIBUKA) -->
                <div v-show="showJadwalPmPanel" class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-800 text-white font-bold border-b border-slate-900 text-[10.5px] uppercase tracking-wider">
                                <th class="py-2.5 px-3 w-10 text-center">No</th>
                                <th class="py-2.5 px-3 w-36">Tanggal &amp; Hari</th>
                                <th class="py-2.5 px-3 w-28 text-center">Status Layanan</th>
                                <th class="py-2.5 px-3 min-w-[200px]">Titik PM Sasaran</th>
                                <th class="py-2.5 px-3.5 w-32 text-right">Porsi Kecil (PM)</th>
                                <th class="py-2.5 px-3.5 w-32 text-right">Porsi Besar (PM)</th>
                                <th class="py-2.5 px-3.5 w-28 text-right">Total PM</th>
                                <th class="py-2.5 px-3.5 min-w-[180px]">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="(it, idx) in form.jadwal_pm_harian"
                                :key="idx"
                                :class="[
                                    'transition-colors',
                                    !it.is_aktif ? 'bg-slate-50/70 text-slate-400' : 'hover:bg-slate-50/90'
                                ]"
                            >
                                <td class="py-2 px-3 text-center font-bold text-xs" :class="it.is_aktif ? 'text-slate-600' : 'text-slate-400'">
                                    {{ it.hari_ke }}
                                </td>
                                <td class="py-2 px-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-xs" :class="it.is_aktif ? 'text-slate-800' : 'text-slate-500'">
                                            {{ it.tanggal }}
                                        </span>
                                        <span
                                            class="px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                            :class="it.is_weekend ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-600'"
                                        >
                                            {{ it.nama_hari }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <button
                                        type="button"
                                        @click="toggleTanggalAktif(it)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold transition-all cursor-pointer shadow-2xs"
                                        :class="it.is_aktif
                                            ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border border-emerald-300'
                                            : 'bg-slate-200 text-slate-600 hover:bg-slate-300 border border-slate-300'"
                                    >
                                        <span v-if="it.is_aktif">✓ Melayani</span>
                                        <span v-else>✕ Libur</span>
                                    </button>
                                </td>
                                <td class="py-2 px-3">
                                    <button
                                        type="button"
                                        @click="openModalKelompokTanggal(it)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border transition-all cursor-pointer shadow-2xs"
                                        :class="[
                                            !it.is_aktif
                                                ? 'bg-slate-100 hover:bg-slate-200 text-slate-500 border-slate-200'
                                                : (getKelompokStatusInfo(it).tidakMenerima > 0
                                                    ? 'bg-amber-50 hover:bg-amber-100 text-amber-900 border-amber-300'
                                                    : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-300'
                                                  )
                                        ]"
                                        title="Klik untuk memilih titik sekolah/posyandu yang menerima / tidak menerima pada tanggal ini"
                                    >
                                        <Users class="h-3.5 w-3.5" :class="it.is_aktif ? 'text-emerald-700' : 'text-slate-400'" />
                                        <span>{{ getKelompokStatusInfo(it).label }}</span>
                                        <span class="text-[10px] text-slate-400 font-normal">| Atur PM ✎</span>
                                    </button>
                                </td>
                                <td class="py-2 px-3.5 text-right">
                                    <input
                                        type="text"
                                        :value="formatNominalDots(it.porsi_kecil)"
                                        @input="onDailyPmInput(it, 'porsi_kecil', $event)"
                                        @blur="onDailyPmBlur(it, 'porsi_kecil', $event)"
                                        @focus="onInputFocus($event)"
                                        :disabled="!it.is_aktif"
                                        placeholder="0"
                                        class="w-28 text-right font-semibold text-xs rounded py-1 px-2 border focus:ring-1 focus:ring-emerald-500"
                                        :class="it.is_aktif
                                            ? 'text-slate-800 bg-white border-slate-200'
                                            : 'text-slate-400 bg-slate-100 border-slate-200 cursor-not-allowed'"
                                    />
                                </td>
                                <td class="py-2 px-3.5 text-right">
                                    <input
                                        type="text"
                                        :value="formatNominalDots(it.porsi_besar)"
                                        @input="onDailyPmInput(it, 'porsi_besar', $event)"
                                        @blur="onDailyPmBlur(it, 'porsi_besar', $event)"
                                        @focus="onInputFocus($event)"
                                        :disabled="!it.is_aktif"
                                        placeholder="0"
                                        class="w-28 text-right font-semibold text-xs rounded py-1 px-2 border focus:ring-1 focus:ring-emerald-500"
                                        :class="it.is_aktif
                                            ? 'text-slate-800 bg-white border-slate-200'
                                            : 'text-slate-400 bg-slate-100 border-slate-200 cursor-not-allowed'"
                                    />
                                </td>
                                <td class="py-2 px-3.5 text-right font-bold text-xs" :class="it.is_aktif ? 'text-slate-900' : 'text-slate-400'">
                                    {{ (it.total_pm || 0).toLocaleString('id-ID') }}
                                </td>
                                <td class="py-2 px-3.5">
                                    <input
                                        type="text"
                                        v-model="it.keterangan"
                                        placeholder="Catatan hari..."
                                        class="w-full text-xs rounded py-1 px-2 border border-slate-200 focus:ring-1 focus:ring-emerald-500"
                                        :class="it.is_aktif ? 'bg-white text-slate-700' : 'bg-slate-100 text-slate-400'"
                                    />
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-amber-100/90 font-bold text-slate-900 border-t-2 border-slate-300 text-xs">
                                <td class="py-2.5 px-3 text-center">-</td>
                                <td class="py-2.5 px-3 uppercase tracking-wider font-bold">Total Akumulasi Sebulan</td>
                                <td class="py-2.5 px-3 text-center text-emerald-900 font-bold">
                                    {{ jadwalPmSummary.hariEfektif }} Hari Aktif
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 font-medium text-[11px]">
                                    {{ (props.kelompokList || []).length }} Titik Master Terdaftar
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-bold text-slate-900">
                                    {{ jadwalPmSummary.totalPorsiKecil.toLocaleString('id-ID') }}
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-bold text-slate-900">
                                    {{ jadwalPmSummary.totalPorsiBesar.toLocaleString('id-ID') }}
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-bold text-slate-950">
                                    {{ jadwalPmSummary.grandTotalPm.toLocaleString('id-ID') }}
                                </td>
                                <td class="py-2.5 px-3.5 text-slate-500 font-normal">
                                    *Rerata: {{ jadwalPmSummary.rerataPorsiKecil.toLocaleString('id-ID') }} (PK) + {{ jadwalPmSummary.rerataPorsiBesar.toLocaleString('id-ID') }} (PB) = {{ jadwalPmSummary.rerataTotalPm.toLocaleString('id-ID') }} PM / hari aktif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- TABEL PENYUSUNAN RAB BULANAN (LAMPIRAN III BGN) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-3.5 sm:p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                            <span>Formulir Rencana Anggaran Biaya (RAB) {{ monthNames[selectedMonth] }} {{ selectedYear }}</span>
                            <span v-if="form.id" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                Tersimpan ID #{{ form.id }}
                            </span>
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Sesuaikan jumlah paket MBG, harga satuan riil, dan hari efektif pelayanan
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <!-- Tombol Buka/Tutup PM Harian -->
                        <button
                            type="button"
                            @click="showJadwalPmPanel = !showJadwalPmPanel"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all cursor-pointer"
                            :class="showJadwalPmPanel ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-emerald-800 border-emerald-200 hover:bg-emerald-50 shadow-2xs'"
                            title="Buka / Tutup Tabel Penetapan PM Harian per Tanggal"
                        >
                            <CalendarDays class="h-3.5 w-3.5" />
                            <span>PM Harian ({{ jadwalPmSummary.hariEfektif }} Hari • {{ (jadwalPmSummary.rerataTotalPm || 0).toLocaleString('id-ID') }} PM)</span>
                            <ChevronUp v-if="showJadwalPmPanel" class="h-3.5 w-3.5" />
                            <ChevronDown v-else class="h-3.5 w-3.5" />
                        </button>

                        <!-- Input Hari Efektif -->
                        <div class="flex items-center gap-2 bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-2xs">
                            <span class="text-xs font-semibold text-slate-600 whitespace-nowrap">Hari Efektif:</span>
                            <input
                                type="number"
                                v-model.number="form.hari_efektif"
                                min="1"
                                max="31"
                                class="w-12 text-center font-bold text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded py-0.5 px-1 focus:ring-1 focus:ring-emerald-500"
                            />
                            <span class="text-xs text-slate-400">Hari</span>
                        </div>

                        <button
                            type="button"
                            @click="resetToDefault"
                            title="Reset kuota paket sesuai data kelompok penerima manfaat database"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 shadow-2xs transition-colors cursor-pointer"
                        >
                            <RotateCcw class="h-3.5 w-3.5 text-slate-400" />
                            <span>Reset Default</span>
                        </button>
                    </div>
                </div>

                <!-- TABEL LAMPIRAN III -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-emerald-700 text-white font-bold border-b border-emerald-800 text-[11px] uppercase tracking-wider">
                                <th class="py-2.5 px-3 w-10 text-center">No</th>
                                <th class="py-2.5 px-3.5 min-w-[270px]">Kebutuhan</th>
                                <th class="py-2.5 px-3 w-32 text-right">Jumlah Paket MBG*</th>
                                <th class="py-2.5 px-3 w-28 text-right">Harga Satuan</th>
                                <th class="py-2.5 px-3 w-36 text-right">Biaya per Hari (Rp)</th>
                                <th class="py-2.5 px-3 w-36 text-right">Biaya 2 Mingguan (Rp)</th>
                                <th class="py-2.5 px-3 w-28 text-center">Hari Efektif</th>
                                <th class="py-2.5 px-3.5 w-40 text-right">Biaya per Bulan (Rp)</th>
                                <th class="py-2.5 px-3.5 min-w-[200px]">Keterangan</th>
                            </tr>
                            <tr class="bg-emerald-50 text-emerald-900 text-[10px] italic font-semibold border-b border-emerald-200">
                                <th class="py-1 px-3 text-center">1</th>
                                <th class="py-1 px-3.5">2</th>
                                <th class="py-1 px-3 text-right">3</th>
                                <th class="py-1 px-3 text-right">4</th>
                                <th class="py-1 px-3 text-right">(5) = (3)*(4)</th>
                                <th class="py-1 px-3 text-right">(6) = (5)*12</th>
                                <th class="py-1 px-3 text-center">7</th>
                                <th class="py-1 px-3.5 text-right">(8) = (7)*(5)</th>
                                <th class="py-1 px-3.5">9</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="(item, idx) in calculatedItems"
                                :key="idx"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="py-2.5 px-3 text-center font-bold text-slate-500 align-top">
                                    {{ idx + 1 }}
                                </td>
                                <td class="py-2.5 px-3.5 font-medium text-slate-800 leading-snug align-top">
                                    {{ item.nama }}
                                </td>
                                <td class="py-2.5 px-3 text-right align-top">
                                    <input
                                        type="text"
                                        :value="formatNominalDots(form.rincian_porsi[idx].jumlah_paket)"
                                        @input="onJumlahPaketInput(form.rincian_porsi[idx], $event)"
                                        @blur="onJumlahPaketBlur(form.rincian_porsi[idx], $event)"
                                        @focus="onInputFocus($event)"
                                        placeholder="0"
                                        class="w-24 text-right font-semibold text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded py-1 px-2 focus:ring-1 focus:ring-emerald-500"
                                    />
                                </td>
                                <td class="py-2.5 px-3 text-right align-top">
                                    <input
                                        type="text"
                                        :value="formatNominalDots(form.rincian_porsi[idx].harga_satuan)"
                                        @input="onHargaSatuanInput(form.rincian_porsi[idx], $event)"
                                        @blur="onHargaSatuanBlur(form.rincian_porsi[idx], $event)"
                                        @focus="onInputFocus($event)"
                                        placeholder="0"
                                        class="w-24 text-right font-semibold text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded py-1 px-2 focus:ring-1 focus:ring-emerald-500"
                                    />
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-700 align-top">
                                    {{ formatRupiah(item.biaya_per_hari) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-600 align-top">
                                    {{ formatRupiah(item.biaya_2mingguan) }}
                                </td>
                                <td class="py-2.5 px-3 text-center text-slate-600 text-xs align-top">
                                    {{ item.hari_efektif }} hari
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-bold text-emerald-700 align-top">
                                    {{ formatRupiah(item.biaya_per_bulan) }}
                                </td>
                                <td class="py-2.5 px-3.5 align-top">
                                    <textarea
                                        v-model="form.rincian_porsi[idx].keterangan"
                                        rows="2"
                                        placeholder="Keterangan..."
                                        class="w-full text-xs text-slate-600 bg-slate-50/50 border border-slate-200 rounded-lg py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:bg-white resize-y leading-relaxed"
                                    ></textarea>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-amber-50/90 font-bold text-slate-800 border-t-2 border-slate-300">
                                <td class="py-2.5 px-3 text-center">-</td>
                                <td class="py-2.5 px-3.5 font-bold uppercase text-slate-900 tracking-wider text-xs">
                                    Total
                                </td>
                                <td class="py-2.5 px-3 text-right text-slate-500">-</td>
                                <td class="py-2.5 px-3 text-right text-slate-500">-</td>
                                <td class="py-2.5 px-3 text-right font-bold text-slate-900">
                                    {{ formatRupiah(totalBiayaHari) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-800">
                                    {{ formatRupiah(totalBiaya2Mingguan) }}
                                </td>
                                <td class="py-2.5 px-3 text-center text-slate-500">-</td>
                                <td class="py-2.5 px-3.5 text-right font-bold text-emerald-800 text-xs sm:text-sm">
                                    {{ formatRupiah(totalBiayaBulan) }}
                                </td>
                                <td class="py-2.5 px-3.5 text-slate-500">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- CATATAN RESMI LAMPIRAN III -->
                <div class="p-3.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 space-y-0.5">
                    <p class="font-bold text-slate-700">Catatan Standar Juknis BGN:</p>
                    <p class="italic">*Jumlah hari pelayanan efektif = {{ form.hari_efektif }} hari (5 Hari KBM X 4 Minggu).</p>
                    <p class="italic">Hari pelayanan efektif belum memperhitungkan hari libur nasional dan kalender akademik satuan pendidikan.</p>
                </div>
            </div>

            <!-- ======================= SECTION PENGELUARAN OPERASIONAL & TABEL BANTU ======================= -->
            <!-- CARD EVALUASI PAGU VS RIIL OPERASIONAL & ANALISIS SELISIH -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 sm:p-5 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <!-- Info Pagu Dasar -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Pagu Anggaran Operasional BGN (Rp 3.000 / PM / Hari)
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Pagu Maksimal BGN
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                {{ formatRupiah(paguOperasionalBulan) }}
                            </span>
                            <span class="text-xs text-slate-500 font-medium">
                                / {{ form.hari_efektif }} hari ({{ calculatedItems[4]?.jumlah_paket }} PM)
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Dasar perhitungan: {{ calculatedItems[4]?.jumlah_paket }} Sasaran PM × Rp 3.000/hari × {{ form.hari_efektif }} Hari Efektif
                        </p>
                    </div>

                    <!-- 3 Indikator Ringkas: Riil Tabel Bantu, Riil Bab V, Selisih Anggaran -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                        <!-- Riil Tabel Bantu -->
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Riil Tabel Bantu (45 Pos)</span>
                            <span class="text-xs sm:text-sm font-bold text-slate-800 block mt-0.5">
                                {{ formatRupiah(tabelBantuSummary.totBulanan) }}
                            </span>
                            <span class="text-[10px] text-slate-400 block">Rerata: {{ formatRupiah(tabelBantuSummary.totRerataHarian) }}/hari</span>
                        </div>

                        <!-- Riil 17 Pos Bab V -->
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Riil 17 Pos (Bab V)</span>
                            <span class="text-xs sm:text-sm font-bold text-slate-800 block mt-0.5">
                                {{ formatRupiah(totalRincianOperasional) }}
                            </span>
                            <span class="text-[10px] text-slate-400 block">Total 17 Pos Operasional</span>
                        </div>

                        <!-- Selisih Pagu vs Riil -->
                        <div class="border-t sm:border-t-0 sm:border-l border-slate-200 pt-2 sm:pt-0 sm:pl-3">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Selisih dari Pagu</span>
                            <div class="flex items-baseline gap-1 mt-0.5">
                                <span class="text-xs sm:text-sm font-extrabold" :class="selisihTabelBantu >= 0 ? 'text-emerald-700' : 'text-rose-700'">
                                    {{ selisihTabelBantu >= 0 ? '+' : '' }}{{ formatRupiah(selisihTabelBantu) }}
                                </span>
                            </div>
                            <span
                                class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9.5px] font-bold"
                                :class="selisihTabelBantu >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            >
                                {{ selisihTabelBantu >= 0 ? `Surplus ${100 - persenSerapanBantu}% (Aman)` : `Defisit ${persenSerapanBantu - 100}%` }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- NOTIFIKASI TOAST SINKRONISASI -->
                <div
                    v-if="syncNotification"
                    class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl flex items-center justify-between text-xs text-emerald-800 shadow-2xs"
                >
                    <div class="flex items-center gap-2">
                        <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                        <span class="font-semibold">{{ syncNotification }}</span>
                    </div>
                    <button type="button" @click="syncNotification = ''" class="text-emerald-600 hover:text-emerald-800 font-bold px-1.5 cursor-pointer">✕</button>
                </div>

                <!-- SUB-NAVIGASI TABEL OPERASIONAL -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100">
                    <div class="inline-flex items-center p-0.5 bg-slate-100 rounded-lg border border-slate-200 text-xs font-medium">
                        <button
                            type="button"
                            @click="operasionalSubTab = 'bantu'"
                            :class="[
                                'px-3 py-1.5 rounded-md transition-all cursor-pointer font-semibold text-xs flex items-center gap-1.5',
                                operasionalSubTab === 'bantu' ? 'bg-white text-emerald-800 shadow-2xs' : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            <Table class="h-3.5 w-3.5" />
                            <span>Tabel Bantu Rinci (45 Item)</span>
                        </button>
                        <button
                            type="button"
                            @click="operasionalSubTab = 'pos17'"
                            :class="[
                                'px-3 py-1.5 rounded-md transition-all cursor-pointer font-semibold text-xs flex items-center gap-1.5',
                                operasionalSubTab === 'pos17' ? 'bg-white text-emerald-800 shadow-2xs' : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            <Layers class="h-3.5 w-3.5" />
                            <span>Bab V. Tabel 17 Pos SPPG</span>
                        </button>
                        <button
                            type="button"
                            @click="operasionalSubTab = 'keduanya'"
                            :class="[
                                'px-3 py-1.5 rounded-md transition-all cursor-pointer font-semibold text-xs flex items-center gap-1.5',
                                operasionalSubTab === 'keduanya' ? 'bg-white text-emerald-800 shadow-2xs' : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            <span>Tampilkan Keduanya</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="syncTabelBantuTo17Pos"
                            title="Sinkronkan akumulasi bulanan dari Tabel Bantu ke 17 Pos Operasional Bab V"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-2xs cursor-pointer"
                        >
                            <ArrowRightLeft class="h-3.5 w-3.5" />
                            <span>Sinkronkan ke Tabel 17 Pos</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 1. TABEL BANTU RINCIAN PENGELUARAN OPERASIONAL (45 Item) -->
            <div
                v-show="operasionalSubTab === 'bantu' || operasionalSubTab === 'keduanya'"
                class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden"
            >
                <div class="p-3.5 sm:p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-start gap-2.5">
                        <div class="p-2 rounded-lg bg-emerald-600 text-white shadow-2xs shrink-0">
                            <Table class="h-4 w-4" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-800">
                                    Tabel Bantu Rincian Pengeluaran Operasional SPPG
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    Perhitungan Akurat
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Breakdown rinci penggunaan harian, mingguan (5 hari), periode (10 hari), dan bulanan (20 hari). Pos kunci otomatis dihitung riil dan bisa diubah.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="applyAllAutoBantu"
                            title="Terapkan hitungan otomatis untuk Relawan, PJ Sekolah, 3B, BPJS TK, dan Gaji Pengawas"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white transition-colors shadow-2xs cursor-pointer"
                        >
                            <Wand2 class="h-3.5 w-3.5" />
                            <span>Terapkan Hitungan Otomatis Pos Kunci</span>
                        </button>

                        <button
                            type="button"
                            @click="tambahBarisBantu"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors shadow-2xs cursor-pointer"
                        >
                            <Plus class="h-3.5 w-3.5 text-emerald-600" />
                            <span>Tambah Barang</span>
                        </button>

                        <button
                            type="button"
                            @click="resetTabelBantu"
                            title="Reset tabel kembali ke template 45 item default"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 transition-colors shadow-2xs cursor-pointer"
                        >
                            <RotateCcw class="h-3.5 w-3.5 text-slate-400" />
                            <span>Reset 45 Item</span>
                        </button>
                    </div>
                </div>

                <!-- TABEL 13 KOLOM PERSIS EXCEL -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-800 text-white font-bold border-b border-slate-900 text-[10.5px] uppercase tracking-wider">
                                <th rowspan="2" class="py-2 px-2 w-10 text-center border-r border-slate-700">No</th>
                                <th rowspan="2" class="py-2 px-3 min-w-[230px] border-r border-slate-700">Nama Penggunaan Barang</th>
                                <th rowspan="2" class="py-2 px-2.5 w-28 text-right border-r border-slate-700">Harga Pokok (Rp)</th>
                                <th colspan="4" class="py-1 px-2 text-center bg-slate-900 border-r border-slate-700">Jumlah Penggunaan Per</th>
                                <th colspan="4" class="py-1 px-2 text-center bg-slate-900 border-r border-slate-700">Nominal Pengeluaran Per (Rp)</th>
                                <th rowspan="2" class="py-2 px-2.5 w-32 text-right bg-emerald-950 text-emerald-200 border-r border-slate-700">Rerata Harian (Rp)</th>
                                <th rowspan="2" class="py-2 px-2.5 w-32 text-right bg-slate-900">Total Pengeluaran (Rp)</th>
                                <th rowspan="2" class="py-2 px-1 w-16 text-center">Aksi</th>
                            </tr>
                            <tr class="bg-slate-700 text-slate-200 font-semibold text-[10px] border-b border-slate-600">
                                <th class="py-1 px-1.5 w-16 text-center border-r border-slate-600">Hari (1 Hr)</th>
                                <th class="py-1 px-1.5 w-16 text-center border-r border-slate-600">Minggu (5 Hr)</th>
                                <th class="py-1 px-1.5 w-16 text-center border-r border-slate-600">Periode (10 Hr)</th>
                                <th class="py-1 px-1.5 w-16 text-center border-r border-slate-600">Bulan (20 Hr)</th>

                                <th class="py-1 px-2 w-28 text-right border-r border-slate-600">Hari (1 Hr)</th>
                                <th class="py-1 px-2 w-28 text-right border-r border-slate-600">Minggu (5 Hr)</th>
                                <th class="py-1 px-2 w-28 text-right border-r border-slate-600">Periode (10 Hr)</th>
                                <th class="py-1 px-2 w-28 text-right border-r border-slate-600">Bulan (20 Hr)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="(it, idx) in tabelBantuCalculated"
                                :key="idx"
                                :class="[
                                    'hover:bg-slate-50/90 transition-colors',
                                    form.rincian_operasional_bantu[idx].is_auto_supported ? 'bg-amber-50/20' : ''
                                ]"
                            >
                                <td class="py-1.5 px-2 text-center font-bold text-slate-500 text-xs">
                                    {{ idx + 1 }}
                                </td>
                                <td class="py-1.5 px-3 font-medium text-slate-800 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            v-model="form.rincian_operasional_bantu[idx].nama"
                                            class="w-full text-xs text-slate-800 bg-transparent hover:bg-white focus:bg-white border border-transparent hover:border-slate-300 focus:border-amber-500 rounded px-1.5 py-0.5"
                                        />
                                        <span
                                            v-if="form.rincian_operasional_bantu[idx].is_auto_supported"
                                            class="px-1.5 py-0.5 rounded text-[9.5px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 shrink-0"
                                            title="Pos ini otomatis dihitung oleh sistem dan dapat diubah"
                                        >
                                            Otomatis
                                        </span>
                                    </div>
                                </td>
                                <td class="py-1.5 px-2 text-right text-xs">
                                    <input
                                        type="text"
                                        :value="formatNominalDots(form.rincian_operasional_bantu[idx].harga_pokok)"
                                        @input="onBantuHargaInput(form.rincian_operasional_bantu[idx], $event)"
                                        @blur="onBantuHargaBlur(form.rincian_operasional_bantu[idx], $event)"
                                        @focus="onInputFocus($event)"
                                        class="w-24 text-right font-semibold text-xs text-slate-900 bg-slate-50 border border-slate-200 rounded px-1.5 py-0.5 focus:bg-white focus:ring-1 focus:ring-amber-500"
                                    />
                                </td>

                                <!-- Qty Hari -->
                                <td class="py-1.5 px-1 text-center bg-slate-50/50">
                                    <input
                                        type="number"
                                        step="any"
                                        :value="form.rincian_operasional_bantu[idx].qty_hari || ''"
                                        @input="onBantuQtyInput(form.rincian_operasional_bantu[idx], 'qty_hari', $event)"
                                        placeholder="-"
                                        class="w-14 text-center font-semibold text-xs text-slate-800 bg-white border border-slate-200 rounded px-1 py-0.5 focus:ring-1 focus:ring-amber-500"
                                    />
                                </td>

                                <!-- Qty Minggu -->
                                <td class="py-1.5 px-1 text-center bg-slate-50/50">
                                    <input
                                        type="number"
                                        step="any"
                                        :value="form.rincian_operasional_bantu[idx].qty_minggu || ''"
                                        @input="onBantuQtyInput(form.rincian_operasional_bantu[idx], 'qty_minggu', $event)"
                                        placeholder="-"
                                        class="w-14 text-center font-semibold text-xs text-slate-800 bg-white border border-slate-200 rounded px-1 py-0.5 focus:ring-1 focus:ring-amber-500"
                                    />
                                </td>

                                <!-- Qty Periode -->
                                <td class="py-1.5 px-1 text-center bg-slate-50/50">
                                    <input
                                        type="number"
                                        step="any"
                                        :value="form.rincian_operasional_bantu[idx].qty_periode || ''"
                                        @input="onBantuQtyInput(form.rincian_operasional_bantu[idx], 'qty_periode', $event)"
                                        placeholder="-"
                                        class="w-14 text-center font-semibold text-xs text-slate-800 bg-white border border-slate-200 rounded px-1 py-0.5 focus:ring-1 focus:ring-amber-500"
                                    />
                                </td>

                                <!-- Qty Bulan -->
                                <td class="py-1.5 px-1 text-center bg-slate-50/50">
                                    <input
                                        type="number"
                                        step="any"
                                        :value="form.rincian_operasional_bantu[idx].qty_bulan || ''"
                                        @input="onBantuQtyInput(form.rincian_operasional_bantu[idx], 'qty_bulan', $event)"
                                        placeholder="-"
                                        class="w-14 text-center font-semibold text-xs text-slate-800 bg-white border border-slate-200 rounded px-1 py-0.5 focus:ring-1 focus:ring-amber-500"
                                    />
                                </td>

                                <!-- Nominal Hari -->
                                <td class="py-1.5 px-2 text-right font-medium text-xs text-slate-700 bg-emerald-50/20">
                                    {{ formatRupiah(it.nomHari) }}
                                </td>

                                <!-- Nominal Minggu -->
                                <td class="py-1.5 px-2 text-right font-medium text-xs text-slate-700 bg-emerald-50/20">
                                    {{ formatRupiah(it.nomMinggu) }}
                                </td>

                                <!-- Nominal Periode -->
                                <td class="py-1.5 px-2 text-right font-medium text-xs text-slate-700 bg-emerald-50/20">
                                    {{ formatRupiah(it.nomPeriode) }}
                                </td>

                                <!-- Nominal Bulan -->
                                <td class="py-1.5 px-2 text-right font-medium text-xs text-slate-700 bg-emerald-50/20">
                                    {{ formatRupiah(it.nomBulan) }}
                                </td>

                                <!-- Rerata Harian -->
                                <td class="py-1.5 px-2.5 text-right font-bold text-xs text-emerald-800 bg-emerald-50/50">
                                    {{ formatRupiah(it.rerataHarian) }}
                                </td>

                                <!-- Total Pengeluaran -->
                                <td class="py-1.5 px-2.5 text-right font-bold text-xs text-slate-900 bg-slate-50/80">
                                    {{ formatRupiah(it.totalPengeluaran) }}
                                </td>

                                <!-- Aksi Auto & Hapus -->
                                <td class="py-1.5 px-1 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            v-if="form.rincian_operasional_bantu[idx].is_auto_supported"
                                            type="button"
                                            @click="applyAutoForSingleBantu(idx)"
                                            class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 transition-colors cursor-pointer"
                                            title="Kembalikan ke nominal otomatis sistem SPPG"
                                        >
                                            <RefreshCw class="h-3 w-3" />
                                            <span>Auto</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="hapusBarisBantu(idx)"
                                            class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Baris"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <!-- BARIS TOTAL TABEL BANTU PERSIS EXCEL -->
                            <tr class="bg-amber-100/90 font-bold text-slate-900 border-t-2 border-slate-300 text-xs">
                                <td class="py-2.5 px-2 text-center">-</td>
                                <td class="py-2.5 px-3 font-bold uppercase tracking-wider text-xs">Total Pengeluaran</td>
                                <td class="py-2.5 px-2.5 text-right font-bold text-slate-900">{{ formatRupiah(tabelBantuSummary.totHp) }}</td>
                                <td colspan="4" class="py-2.5 px-1 text-center text-slate-400 text-[10px]">-</td>
                                <td class="py-2.5 px-2 text-right font-bold text-slate-900">{{ formatRupiah(tabelBantuSummary.totNomHari) }}</td>
                                <td class="py-2.5 px-2 text-right font-bold text-slate-900">{{ formatRupiah(tabelBantuSummary.totNomMinggu) }}</td>
                                <td class="py-2.5 px-2 text-right font-bold text-slate-900">{{ formatRupiah(tabelBantuSummary.totNomPeriode) }}</td>
                                <td class="py-2.5 px-2 text-right font-bold text-slate-900">{{ formatRupiah(tabelBantuSummary.totNomBulan) }}</td>
                                <td class="py-2.5 px-2.5 text-right font-bold text-emerald-900 bg-emerald-100/60">{{ formatRupiah(tabelBantuSummary.totRerataHarian) }}</td>
                                <td class="py-2.5 px-2.5 text-right font-bold text-slate-950 bg-amber-200/60">{{ formatRupiah(tabelBantuSummary.totPengeluaran) }}</td>
                                <td class="py-2.5 px-1 text-center text-slate-400">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- FOOTER ANALISIS KELAYAKAN PAGU TABEL BANTU -->
                <div class="p-3.5 sm:p-4 bg-slate-50 border-t border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-slate-600">
                        <div>
                            <span>Rerata Pengeluaran Harian:</span>
                            <strong class="text-slate-900 ml-1">{{ formatRupiah(tabelBantuSummary.totRerataHarian) }} / hari</strong>
                        </div>
                        <span class="text-slate-300 hidden sm:inline">•</span>
                        <div>
                            <span>Estimasi Riil 1 Bulan Penuh ({{ form.hari_efektif }} Hari):</span>
                            <strong class="text-emerald-800 ml-1">{{ formatRupiah(tabelBantuSummary.totBulanan) }}</strong>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold" :class="selisihTabelBantu >= 0 ? 'text-emerald-700' : 'text-rose-700'">
                            {{ selisihTabelBantu >= 0 ? 'Sisa Pagu Operasional:' : 'Melebihi Pagu:' }}
                            {{ formatRupiah(Math.abs(selisihTabelBantu)) }}
                        </span>
                        <span
                            class="px-2 py-0.5 rounded text-[10px] font-semibold"
                            :class="selisihTabelBantu >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                        >
                            {{ selisihTabelBantu >= 0 ? 'Surplus / Aman' : 'Defisit Anggaran' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. TABEL BAB V. RINCIAN 17 POS BIAYA OPERASIONAL RIIL SPPG -->
            <div
                v-show="operasionalSubTab === 'pos17' || operasionalSubTab === 'keduanya'"
                class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden"
            >
                <div class="p-3.5 sm:p-4 bg-gradient-to-r from-amber-50/80 via-slate-50 to-white border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-start gap-2.5">
                        <div class="p-2 rounded-lg bg-amber-500 text-white shadow-2xs shrink-0">
                            <Layers class="h-4 w-4" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-800">
                                    Bab V. Tabel Rincian 17 Pos Biaya Operasional Riil SPPG
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                                    Format Tabel Resmi
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Pos Insentif Relawan, PJ Sekolah, PJ 3B, BPJS TK, dan Gaji Non-ASN Pengawas Gizi & Keuangan (masing-masing Rp 5.000.000) otomatis dihitung & dapat diubah
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="applyAllAutoOperasional"
                            title="Terapkan ulang seluruh perhitungan otomatis berdasarkan data petugas & sasaran sekolah bulan ini"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-amber-600 text-white hover:bg-amber-700 transition-colors shadow-2xs cursor-pointer"
                        >
                            <Wand2 class="h-3.5 w-3.5" />
                            <span>Terapkan Hitungan Otomatis</span>
                        </button>

                        <button
                            type="button"
                            @click="showOperasionalPanel = !showOperasionalPanel"
                            class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500 transition-colors cursor-pointer"
                            :title="showOperasionalPanel ? 'Sembunyikan Tabel' : 'Tampilkan Tabel'"
                        >
                            <ChevronUp v-if="showOperasionalPanel" class="h-4 w-4" />
                            <ChevronDown v-else class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-show="showOperasionalPanel">
                    <!-- TABEL OPERASIONAL -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-800 text-white font-bold border-b border-slate-900 text-[11px] uppercase tracking-wider">
                                    <th class="py-2.5 px-3 w-10 text-center">No</th>
                                    <th class="py-2.5 px-3.5 min-w-[240px]">Komponen Operasional</th>
                                    <th class="py-2.5 px-3 min-w-[220px]">Dasar Perhitungan Otomatis</th>
                                    <th class="py-2.5 px-3 w-28 text-center">Satuan</th>
                                    <th class="py-2.5 px-3.5 w-44 text-right">Biaya per Bulan (Rp)</th>
                                    <th class="py-2.5 px-3.5 min-w-[220px]">Keterangan / Rincian Riil</th>
                                    <th class="py-2.5 px-3 w-20 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr
                                    v-for="(op, idx) in form.rincian_operasional"
                                    :key="idx"
                                    :class="[
                                        'hover:bg-slate-50/80 transition-colors',
                                        op.is_auto_supported ? 'bg-amber-50/20' : '',
                                    ]"
                                >
                                    <td class="py-2 px-3 text-center font-bold text-slate-500 text-xs align-top">
                                        {{ op.no }}
                                    </td>
                                    <td class="py-2 px-3.5 font-semibold text-slate-800 text-xs align-top">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ op.nama }}</span>
                                            <span
                                                v-if="op.is_auto_supported"
                                                class="px-1.5 py-0.5 rounded text-[9.5px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800"
                                            >
                                                Otomatis
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-3 text-slate-600 text-[11px] align-top">
                                        <span class="font-medium text-slate-700 leading-snug">{{ op.rumus_dasar }}</span>
                                    </td>
                                    <td class="py-2 px-3 text-center text-slate-500 text-[10px] align-top">
                                        <span class="px-1.5 py-0.5 rounded-full bg-slate-100 border border-slate-200">
                                            {{ op.satuan }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3.5 text-right align-top">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <span class="text-[11px] font-semibold text-slate-400">Rp</span>
                                            <input
                                                type="text"
                                                :value="formatNominalDots(op.biaya)"
                                                @input="onOperasionalBiayaInput(op, $event)"
                                                @blur="onOperasionalBiayaBlur(op, $event)"
                                                @focus="onInputFocus($event)"
                                                placeholder="0"
                                                class="w-32 text-right font-bold text-xs text-slate-900 bg-white border border-slate-200 rounded py-1 px-2 focus:ring-1 focus:ring-amber-500 shadow-2xs"
                                            />
                                        </div>
                                    </td>
                                    <td class="py-2 px-3.5 align-top">
                                        <textarea
                                            v-model="form.rincian_operasional[idx].keterangan"
                                            rows="2"
                                            placeholder="Tuliskan keterangan riil..."
                                            class="w-full text-xs text-slate-600 bg-white border border-slate-200 rounded-lg py-1 px-2 focus:ring-1 focus:ring-amber-500 resize-y leading-relaxed"
                                        ></textarea>
                                    </td>
                                    <td class="py-2 px-3 text-center align-top">
                                        <button
                                            v-if="op.is_auto_supported"
                                            type="button"
                                            @click="applyAutoForSingle(idx)"
                                            title="Hitung ulang otomatis berdasarkan formula sistem"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10.5px] font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 transition-colors cursor-pointer"
                                        >
                                            <RefreshCw class="h-3 w-3" />
                                            <span>Auto</span>
                                        </button>
                                        <span v-else class="text-xs text-slate-400">-</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <!-- BARIS TOTAL OPERASIONAL RIIL -->
                                <tr class="bg-amber-100/80 font-bold text-slate-900 border-t-2 border-slate-300">
                                    <td class="py-2.5 px-3 text-center">-</td>
                                    <td class="py-2.5 px-3.5 font-bold uppercase tracking-wider text-xs" colspan="3">
                                        Total Estimasi 17 Pos Operasional (Bab V)
                                    </td>
                                    <td class="py-2.5 px-3.5 text-right font-bold text-amber-900 text-xs sm:text-sm">
                                        {{ formatRupiah(totalRincianOperasional) }}
                                    </td>
                                    <td class="py-2.5 px-3.5 text-[11px] font-semibold text-amber-950" colspan="2">
                                        Akumulasi rincian operasional bulanan
                                    </td>
                                </tr>

                                <!-- BARIS EVALUASI KELAYAKAN PAGU OPERASIONAL -->
                                <tr class="bg-slate-50 border-t border-slate-200 text-[11px]">
                                    <td class="py-2 px-3 text-center">
                                        <Info class="h-3.5 w-3.5 text-slate-400 mx-auto" />
                                    </td>
                                    <td class="py-2 px-3.5 font-semibold text-slate-700" colspan="3">
                                        Perbandingan Pagu Operasional RAB (Rp 3.000 × {{ calculatedItems[4]?.jumlah_paket }} Paket × {{ form.hari_efektif }} Hari)
                                    </td>
                                    <td class="py-2 px-3.5 text-right font-bold text-slate-800">
                                        {{ formatRupiah(paguOperasionalBulan) }}
                                    </td>
                                    <td class="py-2 px-3.5" colspan="2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] font-bold" :class="selisihOperasional >= 0 ? 'text-emerald-700' : 'text-rose-700'">
                                                {{ selisihOperasional >= 0 ? 'Surplus / Sisa Pagu:' : 'Defisit Anggaran:' }}
                                                {{ formatRupiah(Math.abs(selisihOperasional)) }}
                                            </span>
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-semibold"
                                                :class="selisihOperasional >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                            >
                                                {{ selisihOperasional >= 0 ? 'Efisien / Aman' : 'Melebihi Pagu Rp 3.000' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- IDENTITAS YAYASAN & PEJABAT PENANDATANGAN -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-slate-200 shadow-xs space-y-3.5">
                <h3 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                    <Building2 class="h-4 w-4 text-emerald-600" />
                    <span>Identitas Yayasan & Pejabat Pengesahan Dokumen</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Yayasan</label>
                        <input
                            type="text"
                            v-model="form.nama_yayasan"
                            placeholder="Nama Yayasan Pengelola SPPG"
                            class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-lg py-1.5 px-2.5 focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nomor Rekening Yayasan</label>
                        <input
                            type="text"
                            v-model="form.nomor_rekening_yayasan"
                            placeholder="Contoh: 5268080021123800"
                            class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-lg py-1.5 px-2.5 focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">NPWP Yayasan</label>
                        <input
                            type="text"
                            v-model="form.npwp"
                            placeholder="Contoh: 01.234.567.8-901.000"
                            class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-lg py-1.5 px-2.5 focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 pt-3 border-t border-slate-100">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Kepala SPPG</label>
                        <input
                            type="text"
                            v-model="form.kepala_sppg_nama"
                            placeholder="Nama Lengkap Kepala SPPG"
                            class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-lg py-1.5 px-2.5 focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Pengawas Keuangan</label>
                        <input
                            type="text"
                            v-model="form.pengawas_keuangan_nama"
                            placeholder="Nama Lengkap Pengawas Keuangan"
                            class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-lg py-1.5 px-2.5 focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Pengawas Gizi</label>
                        <input
                            type="text"
                            v-model="form.pengawas_gizi_nama"
                            placeholder="Nama Lengkap Pengawas Gizi"
                            class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-lg py-1.5 px-2.5 focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <!-- Catatan Tambahan -->
                <div class="pt-1.5">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Catatan Tambahan RAB</label>
                    <textarea
                        v-model="form.catatan"
                        rows="2"
                        class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-lg py-1.5 px-2.5 focus:ring-1 focus:ring-emerald-500"
                        placeholder="Tuliskan catatan atau keterangan khusus pelaksanaan..."
                    ></textarea>
                </div>

                <!-- FOOTER SIMPAN -->
                <div class="pt-2.5 flex items-center justify-end gap-2.5 border-t border-slate-100">
                    <button
                        type="button"
                        @click="handleDownloadBulanan"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-2xs cursor-pointer"
                    >
                        <FileSpreadsheet class="h-3.5 w-3.5" />
                        <span>Download Excel Bulan Ini</span>
                    </button>

                    <button
                        type="button"
                        @click="submitRab"
                        :disabled="isSaving"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition-all shadow-sm shadow-emerald-600/20 disabled:opacity-50 cursor-pointer"
                    >
                        <Save class="h-3.5 w-3.5" />
                        <span>{{ isSaving ? "Menyimpan..." : "Simpan RAB Bulan Ini" }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ======================= MODE TAHUNAN (BAB VI BGN) ======================= -->
        <div v-else class="space-y-5">
            <!-- BANNER INFORMASI STATUS RAB TAHUNAN -->
            <div
                v-if="tahunanSummary.savedCount < 12"
                class="p-3.5 bg-amber-50/80 border border-amber-200/90 rounded-xl flex items-start gap-3 text-xs text-amber-900 shadow-2xs"
            >
                <AlertCircle class="h-4 w-4 text-amber-600 mt-0.5 shrink-0" />
                <div class="leading-relaxed">
                    <p class="font-bold text-amber-950">
                        Status Penyusunan: Baru {{ tahunanSummary.savedCount }} dari 12 Bulan yang Telah Dibuat
                    </p>
                    <p class="text-[11px] text-amber-800 mt-0.5">
                        Bulan yang berstatus <span class="font-semibold underline">Belum Dibuat</span> tidak dihitung ke dalam akumulasi RAB Tahunan. Total akumulasi di bawah ini hanya menjumlahkan bulan yang telah dibuat/disimpan.
                    </p>
                </div>
            </div>

            <!-- REKAP TABEL BAB VI RAB TAHUNAN -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-3.5 sm:p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                            <span>BAB VI. RAB (Rencana Anggaran Biaya) Tahunan {{ selectedYear }}</span>
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Akumulasi Rencana Anggaran Biaya Operasional sampai dengan 31 Desember {{ selectedYear }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="handleDownloadTahunan"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-2xs cursor-pointer"
                    >
                        <Download class="h-3.5 w-3.5" />
                        <span>Download Excel Sheet Tahunan (.xlsx)</span>
                    </button>
                </div>

                <!-- TABEL BAB VI -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-emerald-700 text-white font-bold border-b border-emerald-800 text-[11px] uppercase tracking-wider">
                                <th class="py-2.5 px-3 w-10 text-center">No</th>
                                <th class="py-2.5 px-3.5 min-w-[270px]">Kebutuhan</th>
                                <th class="py-2.5 px-3 w-32 text-right">Jumlah Paket MBG*</th>
                                <th class="py-2.5 px-3 w-28 text-right">Harga Satuan</th>
                                <th class="py-2.5 px-3 w-32 text-right">Biaya per Hari (Rp)</th>
                                <th class="py-2.5 px-3 w-36 text-right">Biaya 2 Mingguan (Rp)</th>
                                <th class="py-2.5 px-3.5 w-36 text-right">Biaya per Bulan (Rp)</th>
                                <th class="py-2.5 px-3.5 w-44 text-right">Akumulasi s/d 31 Des ({{ tahunanSummary.savedCount }} Bln Dibuat)</th>
                                <th class="py-2.5 px-3.5 min-w-[200px]">Keterangan</th>
                            </tr>
                            <tr class="bg-emerald-50 text-emerald-900 text-[10px] italic font-semibold border-b border-emerald-200">
                                <th class="py-1 px-3 text-center">1</th>
                                <th class="py-1 px-3.5">2</th>
                                <th class="py-1 px-3 text-right">3</th>
                                <th class="py-1 px-3 text-right">4</th>
                                <th class="py-1 px-3 text-right">(5)=(3)*(4)</th>
                                <th class="py-1 px-3 text-right">(6)=(5)*12</th>
                                <th class="py-1 px-3.5 text-right">7</th>
                                <th class="py-1 px-3.5 text-right">(8)=Total Bulan Dibuat</th>
                                <th class="py-1 px-3.5">9</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="(item, idx) in calculatedItems"
                                :key="idx"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="py-2.5 px-3 text-center font-bold text-slate-500">
                                    {{ idx + 1 }}
                                </td>
                                <td class="py-2.5 px-3.5 font-medium text-slate-800 leading-snug">
                                    {{ item.nama }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold">
                                    {{ item.jumlah_paket.toLocaleString('id-ID') }}
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    {{ formatRupiah(item.harga_satuan) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold">
                                    {{ formatRupiah(item.biaya_per_hari) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold">
                                    {{ formatRupiah(item.biaya_2mingguan) }}
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-semibold">
                                    {{ formatRupiah(item.biaya_per_bulan) }}
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-bold text-emerald-700">
                                    <template v-if="tahunanSummary.savedCount > 0">
                                        {{ formatRupiah(tahunanSummary.itemTotals[idx]) }}
                                    </template>
                                    <template v-else>
                                        <span class="text-[11px] font-normal text-slate-400 italic">Belum Dibuat</span>
                                    </template>
                                </td>
                                <td class="py-2.5 px-3.5 text-slate-600 text-xs">
                                    {{ item.keterangan }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-amber-50/90 font-bold text-slate-800 border-t-2 border-slate-300">
                                <td class="py-2.5 px-3 text-center">-</td>
                                <td class="py-2.5 px-3.5 font-bold uppercase text-slate-900 tracking-wider text-xs">
                                    Total
                                </td>
                                <td class="py-2.5 px-3 text-right text-slate-500">-</td>
                                <td class="py-2.5 px-3 text-right text-slate-500">-</td>
                                <td class="py-2.5 px-3 text-right font-bold text-slate-900">
                                    {{ formatRupiah(totalBiayaHari) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-800">
                                    {{ formatRupiah(totalBiaya2Mingguan) }}
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-semibold text-slate-800">
                                    {{ formatRupiah(totalBiayaBulan) }}
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-bold text-emerald-800 text-xs sm:text-sm">
                                    <template v-if="tahunanSummary.savedCount > 0">
                                        {{ formatRupiah(tahunanSummary.grandTotal) }}
                                    </template>
                                    <template v-else>
                                        <span class="text-[11px] font-normal text-slate-400 italic">Belum Dibuat</span>
                                    </template>
                                </td>
                                <td class="py-2.5 px-3.5 text-slate-500">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- TABEL STATUS PENYUSUNAN RAB 12 BULAN -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 sm:p-5 space-y-3.5">
                <h3 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                    <FileText class="h-4 w-4 text-emerald-600" />
                    <span>Daftar Progres RAB Bulanan Tahun {{ selectedYear }}</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div
                        v-for="m in 12"
                        :key="m"
                        class="p-3 rounded-lg border transition-all"
                        :class="savedRabMap[m] ? 'bg-emerald-50/60 border-emerald-200' : 'bg-slate-50 border-slate-200'"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-800">{{ monthNames[m] }}</span>
                            <span
                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                :class="savedRabMap[m] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/80 text-slate-600'"
                            >
                                {{ savedRabMap[m] ? 'Tersimpan' : 'Belum Dibuat' }}
                            </span>
                        </div>

                        <div class="text-xs sm:text-sm font-bold mt-1.5">
                            <span v-if="savedRabMap[m]" class="text-slate-900">
                                {{ formatRupiah(savedRabMap[m].total_biaya_per_bulan) }}
                            </span>
                            <span v-else class="text-slate-400 font-medium italic text-[11px]">
                                Belum Dibuat (Tidak dihitung)
                            </span>
                        </div>

                        <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-200/50">
                            <button
                                type="button"
                                @click="() => { activeMode = 'bulanan'; loadMonthData(m); }"
                                class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-900 cursor-pointer"
                            >
                                Edit RAB →
                            </button>

                            <button
                                type="button"
                                v-if="savedRabMap[m]"
                                @click="async () => {
                                    await downloadRabBulananExcel({
                                        rabData: savedRabMap[m],
                                        month: m,
                                        year: selectedYear,
                                        unitSppg: props.unitSppg,
                                        pejabatSppg: props.pejabatSppg,
                                        rekeningUtama: props.rekeningUtama,
                                        kopDokumen: props.kopDokumen,
                                    });
                                }"
                                class="text-[11px] font-medium text-slate-500 hover:text-emerald-700 flex items-center gap-1 cursor-pointer"
                            >
                                <Download class="h-3 w-3" />
                                <span>Excel</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL PENETAPAN & PENYESUAIAN PM HARIAN PER TITIK SASARAN -->
        <Teleport to="body">
            <div
                v-if="showModalKelompokHarian && activeDateItem"
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/60 backdrop-blur-xs overflow-y-auto animate-in fade-in duration-150"
                style="z-index: 5000;"
            >
                <div
                    class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl overflow-hidden my-auto flex flex-col max-h-[90vh]"
                >
                    <!-- Modal Header -->
                    <div class="px-5 py-4 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-emerald-400 shrink-0 font-bold">
                                <Users class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm sm:text-base font-bold text-white">
                                        Penetapan PM: {{ activeDateItem.nama_hari }}, {{ activeDateItem.tanggal }}
                                    </h3>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10.5px] font-extrabold uppercase tracking-wider"
                                        :class="activeDateItem.is_weekend ? 'bg-rose-500/30 text-rose-200 border border-rose-400/30' : 'bg-emerald-500/30 text-emerald-200 border border-emerald-400/30'"
                                    >
                                        {{ activeDateItem.is_weekend ? 'Akhir Pekan' : 'Hari Kerja KBM' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-300 mt-0.5">
                                    Tentukan kelompok sasaran yang menerima atau tidak menerima makanan bergizi pada tanggal ini.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showModalKelompokHarian = false"
                            class="h-8 w-8 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Modal Sub-bar Preset Cepat -->
                    <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3 shrink-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Preset Button Group -->
                            <div class="inline-flex p-1 bg-white rounded-xl border border-slate-200 text-xs shadow-2xs">
                                <!-- 1. Semua Menerima -->
                                <button
                                    type="button"
                                    @click="applyPresetModal('all')"
                                    :class="[
                                        activeReceivingPresetModal === 'all'
                                            ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-300 ring-1 ring-emerald-500/20 shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900 font-medium hover:bg-slate-50',
                                        'px-3 py-1 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer text-xs'
                                    ]"
                                    title="Aktifkan seluruh titik sasaran penerima manfaat (Sekolah & Posyandu)"
                                >
                                    <Users class="h-3.5 w-3.5" :class="activeReceivingPresetModal === 'all' ? 'text-emerald-700' : 'text-slate-400'" />
                                    <span>Semua Menerima</span>
                                    <span class="ml-0.5 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        {{ modalKelompokDraft.length }}
                                    </span>
                                </button>

                                <!-- 2. Hanya Sekolah -->
                                <button
                                    type="button"
                                    @click="applyPresetModal('sekolah')"
                                    :class="[
                                        activeReceivingPresetModal === 'sekolah'
                                            ? 'bg-blue-50 text-blue-800 font-bold border border-blue-300 ring-1 ring-blue-500/20 shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900 font-medium hover:bg-slate-50',
                                        'px-3 py-1 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer text-xs'
                                    ]"
                                    title="Hanya kelompok kategori Sekolah (TK, RA, PAUD, SD, MI, SMP, MTs, SMA, SMK, MA) yang menerima"
                                >
                                    <School class="h-3.5 w-3.5" :class="activeReceivingPresetModal === 'sekolah' ? 'text-blue-700' : 'text-slate-400'" />
                                    <span>Hanya Sekolah</span>
                                    <span class="ml-0.5 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                        {{ countMasterSekolah }}
                                    </span>
                                </button>

                                <!-- 3. Hanya Posyandu -->
                                <button
                                    type="button"
                                    @click="applyPresetModal('posyandu')"
                                    :class="[
                                        activeReceivingPresetModal === 'posyandu'
                                            ? 'bg-pink-50 text-pink-800 font-bold border border-pink-300 ring-1 ring-pink-500/20 shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900 font-medium hover:bg-slate-50',
                                        'px-3 py-1 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer text-xs'
                                    ]"
                                    title="Hanya kelompok kategori Posyandu / 3B yang menerima"
                                >
                                    <HeartPulse class="h-3.5 w-3.5" :class="activeReceivingPresetModal === 'posyandu' ? 'text-pink-700' : 'text-slate-400'" />
                                    <span>Hanya Posyandu</span>
                                    <span class="ml-0.5 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-pink-100 text-pink-800">
                                        {{ countMasterPosyandu }}
                                    </span>
                                </button>

                                <!-- 4. Semua Libur -->
                                <button
                                    type="button"
                                    @click="applyPresetModal('none')"
                                    :class="[
                                        activeReceivingPresetModal === 'none'
                                            ? 'bg-rose-50 text-rose-800 font-bold border border-rose-300 ring-1 ring-rose-500/20 shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900 font-medium hover:bg-slate-50',
                                        'px-3 py-1 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer text-xs'
                                    ]"
                                    title="Tandai seluruh kelompok sasaran libur / tidak menerima"
                                >
                                    <UserX class="h-3.5 w-3.5" :class="activeReceivingPresetModal === 'none' ? 'text-rose-700' : 'text-slate-400'" />
                                    <span>Semua Libur</span>
                                </button>
                            </div>

                            <!-- Reset Master Button -->
                            <button
                                type="button"
                                @click="applyPresetModal('reset')"
                                class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold flex items-center gap-1 cursor-pointer shadow-2xs"
                                title="Kembalikan seluruh kuota dan status ke data master database"
                            >
                                <RotateCcw class="h-3.5 w-3.5 text-slate-500" />
                                <span>Reset Master</span>
                            </button>
                        </div>

                        <!-- Status Badges Counter -->
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <UserCheck class="h-3.5 w-3.5" />
                                {{ modalDraftTotals.count_menerima }} Menerima
                            </span>
                            <span
                                v-if="modalDraftTotals.count_tidak_menerima > 0"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300"
                            >
                                <UserX class="h-3.5 w-3.5" />
                                {{ modalDraftTotals.count_tidak_menerima }} Tidak Menerima
                            </span>
                        </div>
                    </div>

                    <!-- Modal Body Table -->
                    <div class="overflow-y-auto p-4 flex-1">
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                        <th class="py-2.5 px-3 w-10 text-center">No</th>
                                        <th class="py-2.5 px-3 text-center w-28">Status</th>
                                        <th class="py-2.5 px-3 min-w-[200px]">Nama Kelompok Sasaran</th>
                                        <th class="py-2.5 px-3 w-28">Kategori</th>
                                        <th class="py-2.5 px-3 text-right w-28">Porsi Kecil (PK)</th>
                                        <th class="py-2.5 px-3 text-right w-28">Porsi Besar (PB)</th>
                                        <th class="py-2.5 px-3 text-right w-24">Total PM</th>
                                        <th class="py-2.5 px-3 text-center w-28">Aksi Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    <tr
                                        v-for="(k, idx) in modalKelompokDraft"
                                        :key="k.id"
                                        :class="[
                                            'transition-colors',
                                            k.status_menerima === false ? 'bg-slate-50/80 text-slate-400 opacity-60 select-none' : 'hover:bg-slate-50/60'
                                        ]"
                                    >
                                        <td class="py-2 px-3 text-center font-bold text-xs" :class="k.status_menerima === false ? 'text-slate-300' : 'text-slate-500'">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            <span
                                                v-if="k.status_menerima !== false"
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300"
                                            >
                                                <UserCheck class="h-3 w-3 mr-1" />
                                                Menerima
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-slate-200 text-slate-500 border border-slate-300"
                                            >
                                                <UserX class="h-3 w-3 mr-1" />
                                                Tidak
                                            </span>
                                        </td>
                                        <td class="py-2 px-3">
                                            <div class="flex items-start justify-between gap-1.5">
                                                <div>
                                                    <span class="font-bold block" :class="k.status_menerima === false ? 'text-slate-400 line-through' : 'text-slate-800'">
                                                        {{ k.nama_kelompok }}
                                                    </span>
                                                    <span class="text-[10px] text-slate-400 font-normal no-underline block">
                                                        NPSN: {{ k.npsn || '-' }} • {{ k.desa_kelurahan }}, {{ k.kecamatan }}
                                                    </span>
                                                </div>
                                                <div v-if="getKelompokSubKategoriBadgeInfo(k)" class="shrink-0 mt-0.5">
                                                    <span
                                                        v-if="getKelompokSubKategoriBadgeInfo(k).isPartial"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300"
                                                        :title="`${getKelompokSubKategoriBadgeInfo(k).activeCount} dari ${getKelompokSubKategoriBadgeInfo(k).totalCount} jenjang menerima`"
                                                    >
                                                        ⚠️ {{ getKelompokSubKategoriBadgeInfo(k).activeCount }}/{{ getKelompokSubKategoriBadgeInfo(k).totalCount }} Jenjang
                                                    </span>
                                                    <span
                                                        v-else-if="getKelompokSubKategoriBadgeInfo(k).isAllActive && k.status_menerima !== false"
                                                        class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                                    >
                                                        ✓ Semua Jenjang
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2 px-3">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border"
                                                :class="k.is_posyandu
                                                    ? 'bg-pink-50 text-pink-700 border-pink-200'
                                                    : 'bg-blue-50 text-blue-700 border-blue-200'"
                                            >
                                                {{ k.kategori || (k.is_posyandu ? 'Posyandu' : 'Sekolah') }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-3 text-right">
                                            <input
                                                type="text"
                                                :value="formatNominalDots(k.porsi_kecil)"
                                                @input="onModalKelompokInput(k, 'porsi_kecil', $event)"
                                                @blur="onModalKelompokBlur(k, 'porsi_kecil', $event)"
                                                :disabled="k.status_menerima === false"
                                                placeholder="0"
                                                class="w-20 text-right font-semibold text-xs rounded py-1 px-2 border focus:ring-1 focus:ring-emerald-500"
                                                :class="k.status_menerima === false ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed' : 'bg-white text-slate-800 border-slate-200'"
                                            />
                                        </td>
                                        <td class="py-2 px-3 text-right">
                                            <input
                                                type="text"
                                                :value="formatNominalDots(k.porsi_besar)"
                                                @input="onModalKelompokInput(k, 'porsi_besar', $event)"
                                                @blur="onModalKelompokBlur(k, 'porsi_besar', $event)"
                                                :disabled="k.status_menerima === false"
                                                placeholder="0"
                                                class="w-20 text-right font-semibold text-xs rounded py-1 px-2 border focus:ring-1 focus:ring-emerald-500"
                                                :class="k.status_menerima === false ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed' : 'bg-white text-slate-800 border-slate-200'"
                                            />
                                        </td>
                                        <td class="py-2 px-3 text-right font-bold" :class="k.status_menerima === false ? 'text-slate-400 line-through' : 'text-slate-900'">
                                            {{ k.status_menerima === false ? 0 : (k.porsi_kecil + k.porsi_besar).toLocaleString('id-ID') }}
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Tombol Edit Rincian Jenjang / Sub-Kategori -->
                                                <button
                                                    type="button"
                                                    @click="handleOpenModalEditSubKategori(k)"
                                                    :class="[
                                                        k.status_menerima === false
                                                            ? 'opacity-40 bg-slate-100 text-slate-400 border-slate-200'
                                                            : 'bg-amber-50 hover:bg-amber-100 text-amber-700 border-amber-300 shadow-2xs cursor-pointer',
                                                        'h-7 px-2 rounded-lg border flex items-center gap-1 text-[10.5px] font-bold transition-all select-none'
                                                    ]"
                                                    :title="`Atur Sub-Kategori / Jenjang Penerima Manfaat untuk ${k.nama_kelompok}`"
                                                >
                                                    <Edit3 class="h-3.5 w-3.5 text-amber-600" />
                                                    <span>Jenjang</span>
                                                </button>

                                                <!-- Tombol Switch Menerima / Tidak Menerima -->
                                                <button
                                                    v-if="k.status_menerima !== false"
                                                    type="button"
                                                    @click="toggleKelompokStatusModal(k, false)"
                                                    class="h-7 w-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                    title="Tandai seluruh kelompok TIDAK MENERIMA pada hari ini"
                                                >
                                                    <UserX class="h-3.5 w-3.5" />
                                                </button>
                                                <button
                                                    v-else
                                                    type="button"
                                                    @click="toggleKelompokStatusModal(k, true)"
                                                    class="h-7 w-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                    title="Tandai MENERIMA pada hari ini"
                                                >
                                                    <UserCheck class="h-3.5 w-3.5" />
                                                </button>

                                                <!-- Tombol Reset -->
                                                <button
                                                    type="button"
                                                    @click="resetSingleKelompokModal(k)"
                                                    class="h-7 w-7 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-500 border border-slate-200 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                    title="Reset ke kuota awal master KPM"
                                                >
                                                    <RotateCcw class="h-3 w-3" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-amber-50 font-bold text-slate-900 border-t-2 border-slate-200 text-xs">
                                        <td colspan="4" class="py-2.5 px-3 text-right uppercase tracking-wider font-bold">
                                            Total Hasil Hari Ini:
                                        </td>
                                        <td class="py-2.5 px-3 text-right text-emerald-800 font-bold">
                                            {{ modalDraftTotals.porsi_kecil.toLocaleString('id-ID') }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right text-indigo-900 font-bold">
                                            {{ modalDraftTotals.porsi_besar.toLocaleString('id-ID') }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right text-slate-950 font-black">
                                            {{ modalDraftTotals.total_pm.toLocaleString('id-ID') }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center text-[10.5px] text-slate-500 font-normal">
                                            {{ modalDraftTotals.count_menerima }} Titik
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
                        <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-slate-700">
                            <input
                                type="checkbox"
                                v-model="applyToAllWeekdaysModal"
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                            />
                            <span>Terapkan juga setelan hari ini ke seluruh <strong>Hari Kerja (Senin - Jumat)</strong> sebulan</span>
                        </label>

                        <div class="flex items-center gap-2 justify-end">
                            <button
                                type="button"
                                @click="showModalKelompokHarian = false"
                                class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold cursor-pointer transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="saveModalKelompokHarian"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer shadow-xs transition-colors flex items-center gap-1.5"
                            >
                                <Check class="h-4 w-4" />
                                <span>Simpan &amp; Terapkan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- SUB-MODAL: ATUR DETAIL SUB-KATEGORI / JENJANG PER KELOMPOK -->
        <Teleport to="body">
            <div
                v-if="showModalEditSubKategori && editingKelompokForSubKategori"
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-950/75 backdrop-blur-xs overflow-y-auto animate-in fade-in duration-150"
                style="z-index: 99999;"
            >
                <div
                    class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden my-auto flex flex-col max-h-[88vh]"
                >
                    <!-- Header Modal Sub-Kategori -->
                    <div class="px-5 py-4 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-amber-500/20 border border-amber-400/40 flex items-center justify-center text-amber-400 shrink-0 font-bold">
                                <Edit3 class="h-4 w-4" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm sm:text-base font-bold text-white">
                                        Atur Jenjang / Sub-Kategori: {{ editingKelompokForSubKategori.nama_kelompok }}
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
                                        {{ activeDateItem?.nama_hari }}, {{ activeDateItem?.tanggal }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-300 mt-0.5">
                                    Tentukan jenjang/sub-kategori mana yang menerima atau sesuaikan kuota porsi harian.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showModalEditSubKategori = false"
                            class="h-8 w-8 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Sub-bar Aksi Cepat Preset Sub-Kategori -->
                    <div class="p-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3 shrink-0">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Rincian Kuota Porsi per Jenjang:
                        </span>
                        <div class="inline-flex items-center gap-1.5 bg-white p-1 rounded-lg border border-slate-200 shadow-2xs">
                            <button
                                type="button"
                                @click="handleSetAllSubKategoriMenerima(true)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 hover:bg-emerald-50 rounded-md cursor-pointer transition-colors whitespace-nowrap"
                                title="Aktifkan seluruh sub-kategori / jenjang"
                            >
                                <Check class="w-3.5 h-3.5 stroke-[2.5]" />
                                <span>Semua Menerima</span>
                            </button>
                            <span class="text-slate-300">•</span>
                            <button
                                type="button"
                                @click="handleSetAllSubKategoriMenerima(false)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-md cursor-pointer transition-colors whitespace-nowrap"
                                title="Nonaktifkan seluruh sub-kategori / jenjang"
                            >
                                <X class="w-3.5 h-3.5 stroke-[2.5]" />
                                <span>Semua Off</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Rincian Sub-Kategori -->
                    <div class="overflow-y-auto p-4 flex-1">
                        <div class="rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]">
                                    <tr>
                                        <th class="p-3">Sub-Kategori / Jenjang</th>
                                        <th class="p-3 text-center w-20">Status</th>
                                        <th class="p-3">Peruntukan Porsi</th>
                                        <th class="p-3 text-center min-w-[90px]">Laki-laki (L)</th>
                                        <th class="p-3 text-center min-w-[90px]">Perempuan (P)</th>
                                        <th class="p-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-800">
                                    <tr
                                        v-for="(r, rIdx) in editFormSubKategori"
                                        :key="r.id || rIdx"
                                        :class="[
                                            'transition-colors',
                                            r.is_menerima === false ? 'bg-slate-50/70 text-slate-400 select-none' : 'hover:bg-slate-50/50'
                                        ]"
                                    >
                                        <td class="p-3 font-bold" :class="r.is_menerima === false ? 'text-slate-400 line-through' : 'text-slate-900'">
                                            {{ r.sub_kategori }}
                                        </td>
                                        <td class="p-2 text-center whitespace-nowrap">
                                            <button
                                                type="button"
                                                @click="r.is_menerima = !r.is_menerima"
                                                :class="[
                                                    'inline-flex items-center justify-center w-7 h-7 rounded-lg border transition-all cursor-pointer shadow-2xs select-none hover:scale-105 active:scale-95',
                                                    r.is_menerima !== false
                                                        ? 'bg-emerald-50 text-emerald-600 border-emerald-300 hover:bg-emerald-100 hover:border-emerald-400'
                                                        : 'bg-rose-50 text-rose-500 border-rose-300 hover:bg-rose-100 hover:border-rose-400'
                                                ]"
                                                :title="r.is_menerima !== false ? 'Status: Menerima (Klik untuk nonaktifkan)' : 'Status: Tidak Menerima (Klik untuk aktifkan)'"
                                            >
                                                <Check v-if="r.is_menerima !== false" class="w-4 h-4 stroke-[2.5]" />
                                                <X v-else class="w-4 h-4 stroke-[2.5]" />
                                            </button>
                                        </td>
                                        <td class="p-3">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border"
                                                :class="r.jenis_porsi === 'Porsi Kecil'
                                                    ? 'bg-amber-50 text-amber-900 border-amber-300'
                                                    : 'bg-indigo-50 text-indigo-900 border-indigo-300'"
                                            >
                                                {{ r.jenis_porsi }}
                                            </span>
                                        </td>
                                        <td class="p-2 text-center">
                                            <input
                                                type="number"
                                                min="0"
                                                :disabled="r.is_menerima === false"
                                                v-model.number="r.jumlah_laki_laki"
                                                class="w-20 text-center text-xs font-bold rounded-lg border-slate-300 p-1.5 focus:ring-emerald-500 focus:border-emerald-500"
                                                :class="r.is_menerima === false ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-dashed' : 'bg-white text-slate-900'"
                                            />
                                        </td>
                                        <td class="p-2 text-center">
                                            <input
                                                type="number"
                                                min="0"
                                                :disabled="r.is_menerima === false"
                                                v-model.number="r.jumlah_perempuan"
                                                class="w-20 text-center text-xs font-bold rounded-lg border-slate-300 p-1.5 focus:ring-emerald-500 focus:border-emerald-500"
                                                :class="r.is_menerima === false ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-dashed' : 'bg-white text-slate-900'"
                                            />
                                        </td>
                                        <td class="p-3 text-right font-black" :class="r.is_menerima === false ? 'text-slate-400' : 'text-slate-900'">
                                            <span v-if="r.is_menerima === false" class="text-[11px] text-slate-400 italic">0 (Off)</span>
                                            <span v-else>
                                                {{ (Number(r.jumlah_laki_laki) || 0) + (Number(r.jumlah_perempuan) || 0) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-amber-50 font-bold text-slate-900 border-t-2 border-slate-200 text-xs">
                                        <td colspan="3" class="p-3 uppercase tracking-wider font-bold">
                                            Total Kelompok Ini:
                                        </td>
                                        <td colspan="2" class="p-3 text-center text-slate-700 text-[11px]">
                                            PK: <strong class="text-amber-900">{{ modalSubKategoriTotals.pk }}</strong> • PB: <strong class="text-indigo-900">{{ modalSubKategoriTotals.pb }}</strong>
                                        </td>
                                        <td class="p-3 text-right font-black text-slate-950 text-sm">
                                            {{ modalSubKategoriTotals.total.toLocaleString('id-ID') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Modal Sub-Kategori -->
                    <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
                        <span class="text-xs text-slate-600">
                            Aktif: <strong>{{ modalSubKategoriTotals.activeCount }}</strong> dari {{ modalSubKategoriTotals.totalCount }} jenjang
                        </span>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="showModalEditSubKategori = false"
                                class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold cursor-pointer transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="handleSaveEditSubKategori"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer shadow-xs transition-colors flex items-center gap-1.5"
                            >
                                <Check class="h-4 w-4" />
                                <span>Terapkan Perubahan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
