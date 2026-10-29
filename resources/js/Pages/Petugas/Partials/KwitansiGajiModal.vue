<script setup>
import { ref, computed, watch } from "vue";
import {
    X,
    FileText,
    Download,
    Check,
    Settings2,
    ChevronDown,
    ChevronUp,
    User,
    Calendar,
    Building2,
    DollarSign,
    Sparkles,
    RefreshCw,
} from "lucide-vue-next";
import jsPDF from "jspdf";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";
import { getActiveKopConfig, generateKopHtml } from "@/Services/kopDokumenHelper";
import { LOGO_BGN_BASE64, LOGO_YAYASAN_BASE64 } from "@/Services/logoBase64Helper";

const props = defineProps({
    show: { type: Boolean, default: false },
    selectedRows: { type: Array, default: () => [] },
    allRows: { type: Array, default: () => [] },
    unitSppg: { type: Object, default: null },
    periodes: { type: Array, default: () => [] },
    selectedPeriode: { type: Object, default: null },
    startDate: { type: String, default: "" },
    endDate: { type: String, default: "" },
    dateSelectionType: { type: String, default: "range" },
    selectedDates: { type: Array, default: () => [] },
    rentangHariCount: { type: Number, default: 0 },
    defaultPengawasKeuangan: { type: String, default: "" },
    initialPetugasId: { type: [Number, String], default: null },
});

const emit = defineEmits(["close"]);

// ─── Formatters ───────────────────────────────────────────────────────────────
function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}

function formatRibuan(num) {
    if (num === null || num === undefined || isNaN(num) || num === "") return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}

// ─── Fungsi Terbilang Rupiah Resmi ───────────────────────────────────────────
function angkaKeTerbilang(nilai) {
    const angka = Math.floor(Math.abs(Number(nilai) || 0));
    if (angka === 0) return "Nol";
    const satuan = [
        "",
        "Satu",
        "Dua",
        "Tiga",
        "Empat",
        "Lima",
        "Enam",
        "Tujuh",
        "Delapan",
        "Sembilan",
        "Sepuluh",
        "Sebelas",
    ];
    if (angka < 12) return satuan[angka];
    if (angka < 20) return angkaKeTerbilang(angka - 10) + " Belas";
    if (angka < 100) {
        const sisa = angka % 10;
        return (
            angkaKeTerbilang(Math.floor(angka / 10)) +
            " Puluh" +
            (sisa !== 0 ? " " + satuan[sisa] : "")
        );
    }
    if (angka < 200) {
        const sisa = angka - 100;
        return "Seratus" + (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "");
    }
    if (angka < 1000) {
        const sisa = angka % 100;
        return (
            angkaKeTerbilang(Math.floor(angka / 100)) +
            " Ratus" +
            (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "")
        );
    }
    if (angka < 2000) {
        const sisa = angka - 1000;
        return "Seribu" + (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "");
    }
    if (angka < 1000000) {
        const sisa = angka % 1000;
        return (
            angkaKeTerbilang(Math.floor(angka / 1000)) +
            " Ribu" +
            (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "")
        );
    }
    if (angka < 1000000000) {
        const sisa = angka % 1000000;
        return (
            angkaKeTerbilang(Math.floor(angka / 1000000)) +
            " Juta" +
            (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "")
        );
    }
    const sisa = angka % 1000000000;
    return (
        angkaKeTerbilang(Math.floor(angka / 1000000000)) +
        " Miliar" +
        (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "")
    );
}

// ─── Konversi Bulan Romawi ────────────────────────────────────────────────────
const ROMAN_MONTHS = [
    "I",
    "II",
    "III",
    "IV",
    "V",
    "VI",
    "VII",
    "VIII",
    "IX",
    "X",
    "XI",
    "XII",
];

function getRomanMonth(dateStr) {
    if (!dateStr) return "X";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return "X";
        return ROMAN_MONTHS[d.getMonth()] || "X";
    } catch {
        return "X";
    }
}

function getYearFromDate(dateStr) {
    if (!dateStr) return "2026";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return "2026";
        return String(d.getFullYear());
    } catch {
        return "2026";
    }
}

// ─── Generator Singkatan Nama SPPG (e.g. SPPG.BST untuk Nomor Kuitansi) ───────
function generateSppgAbbreviation(namaSppg) {
    if (!namaSppg) return "SPPG.BST";
    const cleaned = namaSppg.replace(/^SPPG\s+/i, "").trim();
    const words = cleaned.split(/\s+/).filter(Boolean);
    const initials = words.map((w) => w.charAt(0).toUpperCase()).join("");
    return initials ? `SPPG.${initials}` : "SPPG.BST";
}

// ─── Nama Lengkap SPPG Tanpa Disingkat untuk Tanda Tangan ─────────────────────
const fullSppgName = computed(() => {
    const raw = props.unitSppg?.nama || "SPPG Buleleng Sukasada Tegallinggah";
    return raw.toUpperCase().startsWith("SPPG") ? raw : `SPPG ${raw}`;
});

// ─── State Konfigurasi & Variabel Kuitansi ────────────────────────────────────
const showSettings = ref(false);
const isGeneratingPdf = ref(false);

const startNoUrut = ref(1);
const jenisKuitansi = ref("KUI.Ops"); // Ops = Operasional (Gaji), BanPer, BB, IF
const singkatanSppg = ref("SPPG.BST");
const bulanRomawi = ref("X");
const tahunKuitansi = ref("2026");
const tanggalKuitansi = ref("");
const lokasiKuitansi = ref("Buleleng");
const namaPengawasKeuangan = ref("");

// Mode Tampilan: 'all' (semua kuitansi terpilih) | 'single' (satu per orangan)
const viewMode = ref("all");
const singlePetugasId = ref(null);

// Inisialisasi State Saat Modal Dibuka
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            const baseDate = props.endDate || props.startDate || new Date().toISOString().slice(0, 10);
            tanggalKuitansi.value = baseDate;
            bulanRomawi.value = getRomanMonth(baseDate);
            tahunKuitansi.value = getYearFromDate(baseDate);

            const sppgName = props.unitSppg?.nama || "SPPG Buleleng Sukasada Tegallinggah";
            singkatanSppg.value = generateSppgAbbreviation(sppgName);
            lokasiKuitansi.value = props.unitSppg?.kabupaten || props.unitSppg?.kecamatan || "Buleleng";

            namaPengawasKeuangan.value =
                props.defaultPengawasKeuangan ||
                props.unitSppg?.nama_pengawas_keuangan ||
                "I Gusti Ayu Made Padmi Swari";

            if (props.initialPetugasId) {
                viewMode.value = "single";
                singlePetugasId.value = props.initialPetugasId;
            } else {
                viewMode.value = "all";
                const list = props.selectedRows.length > 0 ? props.selectedRows : (props.allRows || []);
                if (list.length > 0) {
                    singlePetugasId.value = list[0].petugas.id;
                }
            }
        }
    },
    { immediate: true }
);

watch(tanggalKuitansi, (newDate) => {
    if (newDate) {
        bulanRomawi.value = getRomanMonth(newDate);
        tahunKuitansi.value = getYearFromDate(newDate);
    }
});

// Daftar Personil Terpilih untuk Kwitansi (fallback ke allRows bila belum ada centang)
const receiptList = computed(() => {
    if (props.selectedRows && props.selectedRows.length > 0) {
        return props.selectedRows;
    }
    return props.allRows || [];
});

// Personil yang Ditampilkan Berdasarkan Mode
const displayedReceipts = computed(() => {
    const list = receiptList.value.length > 0 ? receiptList.value : (props.allRows || []);
    if (viewMode.value === "single") {
        if (!singlePetugasId.value) return list.slice(0, 1);
        const found = list.find(
            (r) => String(r.petugas?.id) === String(singlePetugasId.value)
        );
        return found ? [found] : list.slice(0, 1);
    }
    return list;
});

// Hitung nomor kuitansi untuk item tertentu
function getFormattedNomorKuitansi(itemIndex) {
    const no = String(Number(startNoUrut.value || 1) + itemIndex).padStart(3, "0");
    const jenis = jenisKuitansi.value || "KUI.Ops";
    const sppg = singkatanSppg.value || "SPPG.BST";
    const bln = bulanRomawi.value || "X";
    const thn = tahunKuitansi.value || "2026";
    return `${no}/${jenis}/${sppg}/${bln}/${thn}`;
}

// Keterangan Pembayaran Otomatis
function getKeteranganPembayaran(row) {
    let periodeStr = "";
    if (props.dateSelectionType === "custom_dates") {
        periodeStr = `Pilihan Bebas (${props.selectedDates.length} Tanggal)`;
    } else if (props.selectedPeriode) {
        periodeStr = `Periode ${props.selectedPeriode.nomor_periode} (${formatTanggalIndo(props.startDate)} s/d ${formatTanggalIndo(props.endDate)})`;
    } else {
        periodeStr = `${formatTanggalIndo(props.startDate)} s/d ${formatTanggalIndo(props.endDate)}`;
    }

    const mandaysCount = row.mandaysInfo?.mandays || 0;
    const hadir = row.mandaysInfo?.hadir || 0;
    const setengah = row.mandaysInfo?.setengah || 0;
    const sakitIzin = (row.mandaysInfo?.sakit || 0) + (row.mandaysInfo?.izin || 0);

    return `Pembayaran Gaji Petugas SPPG ${periodeStr} an. ${row.petugas.nama} (${row.petugas.jabatan || 'Personil SPPG'}) sejumlah ${mandaysCount} Mandays (${hadir} Hadir, ${setengah} Setengah Hari, ${sakitIzin} Sakit/Izin).`;
}

// Kop Dokumen HTML Terpusat
const kopConfig = computed(() => {
    return getActiveKopConfig(props.unitSppg?.setting_kop_dokumen);
});

const renderedKopHtml = computed(() => {
    return generateKopHtml(kopConfig.value, {
        mode: "print",
        showBorder: true,
        logoKiriSize: 70,
        logoKananSize: 70,
        compact: false,
    });
});

// ─── Download PDF Kuitansi Langsung (jsPDF Vector Crisp, 1 Kuitansi per Hal) ───
async function downloadPdfKwitansi() {
    if (displayedReceipts.value.length === 0) return;
    isGeneratingPdf.value = true;

    try {
        const doc = new jsPDF({
            orientation: "portrait",
            unit: "mm",
            format: "a4",
        });

        const items = displayedReceipts.value;

        items.forEach((row, idx) => {
            if (idx > 0) {
                doc.addPage();
            }

            const itemIdx =
                viewMode.value === "all"
                    ? idx
                    : receiptList.value.findIndex((r) => r.petugas.id === row.petugas.id);
            const nomorKuitansi = getFormattedNomorKuitansi(itemIdx >= 0 ? itemIdx : idx);

            // 1. Kop Dokumen Resmi
            try {
                if (LOGO_BGN_BASE64) {
                    doc.addImage(LOGO_BGN_BASE64, "PNG", 18, 14, 18, 18);
                }
            } catch (e) {}

            try {
                if (LOGO_YAYASAN_BASE64) {
                    doc.addImage(LOGO_YAYASAN_BASE64, "PNG", 174, 14, 18, 18);
                }
            } catch (e) {}

            doc.setFont("times", "bold");
            doc.setFontSize(11.5);
            doc.setTextColor(0, 0, 0);
            doc.text(
                (kopConfig.value?.nama_instansi_1 || "SPPG BULELENG SUKASADA TEGALLINGGAH").toUpperCase(),
                105,
                18.5,
                { align: "center" }
            );

            doc.setFontSize(10.5);
            doc.text(
                (kopConfig.value?.nama_instansi_2 || "YAYASAN PESANTREN MIFTAHUL ULUM").toUpperCase(),
                105,
                23.5,
                { align: "center" }
            );

            doc.setFont("times", "normal");
            doc.setFontSize(8.5);
            doc.setTextColor(30, 41, 59);
            doc.text(
                kopConfig.value?.alamat_lengkap ||
                    "Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali",
                105,
                28,
                { align: "center" }
            );

            const emailStr = kopConfig.value?.email ? `E-mail: ${kopConfig.value.email}` : "";
            if (emailStr) {
                doc.text(emailStr, 105, 32, { align: "center" });
            }

            // Garis Ganda Pembatas Kop
            doc.setDrawColor(0, 0, 0);
            doc.setLineWidth(0.6);
            doc.line(18, 36, 192, 36);
            doc.setLineWidth(0.2);
            doc.line(18, 37, 192, 37);

            // 2. Judul K U I T A N S I & Nomor
            doc.setFont("times", "bold");
            doc.setFontSize(15);
            doc.setTextColor(0, 0, 0);
            doc.text("K U I T A N S I", 105, 47, { align: "center" });

            doc.setLineWidth(0.4);
            doc.line(82, 48.5, 128, 48.5);

            doc.setFont("times", "normal");
            doc.setFontSize(10.5);
            doc.setTextColor(30, 41, 59);
            doc.text(nomorKuitansi, 105, 54, { align: "center" });

            // 3. Rincian Badan Kuitansi
            let curY = 66;
            doc.setFont("times", "normal");
            doc.setFontSize(11);
            doc.setTextColor(0, 0, 0);

            // Baris 1: Sudah Terima dari
            doc.text("Sudah Terima dari", 20, curY);
            doc.text(":", 58, curY);
            doc.text(`Staf Pengawas Keuangan ${fullSppgName.value}`, 62, curY);

            curY += 5.5;
            doc.setFont("times", "normal");
            doc.text("ID SPPG : ", 62, curY);
            const idW = doc.getTextWidth("ID SPPG : ");
            doc.setFont("times", "bold");
            doc.text(props.unitSppg?.id_sppg || "QQCV0LUG", 62 + idW, curY);

            // Baris 2: Uang Sejumlah
            curY += 10.5;
            doc.setFont("times", "normal");
            doc.text("Uang Sejumlah", 20, curY);
            doc.text(":", 58, curY);

            doc.setFont("times", "italic");
            doc.setFontSize(10.5);
            const terbilangText = `# ${angkaKeTerbilang(row.amount)} Rupiah #`;
            const terbilangLines = doc.splitTextToSize(terbilangText, 122);
            const boxH = Math.max(9, terbilangLines.length * 4.8 + 4.5);

            doc.setFillColor(248, 250, 252);
            doc.setDrawColor(203, 213, 225);
            doc.setLineWidth(0.25);
            doc.roundedRect(62, curY - 5, 128, boxH, 1.5, 1.5, "FD");

            doc.setTextColor(15, 23, 42);
            doc.text(terbilangLines, 65, curY);

            // Nominal Rp
            curY += boxH + 4.5;
            doc.setFont("times", "bold");
            doc.setFontSize(13);
            doc.setTextColor(0, 0, 0);
            doc.text(`Rp. ${formatRibuan(row.amount)},-`, 62, curY);

            // Baris 3: Untuk Pembayaran
            curY += 10.5;
            doc.setFont("times", "normal");
            doc.setFontSize(11);
            doc.text("Untuk Pembayaran", 20, curY);
            doc.text(":", 58, curY);

            const ketText = getKeteranganPembayaran(row);
            const ketLines = doc.splitTextToSize(ketText, 128);
            doc.text(ketLines, 62, curY);

            // 4. Tanda Tangan 2 Kolom (Nama SPPG Lengkap & Tanpa Embel-embel Dibawah Nama)
            const sigY = Math.max(curY + ketLines.length * 5.2 + 20, 205);

            // Tanggal di atas kolom kanan
            doc.setFont("times", "normal");
            doc.setFontSize(11);
            doc.text(
                `${lokasiKuitansi.value}, ${formatTanggalIndo(tanggalKuitansi.value)}`,
                152,
                sigY,
                { align: "center" }
            );

            // Kolom Kiri: Staf Pengawas Keuangan SPPG ... (Nama SPPG TIDAK Disingkat)
            doc.text("Staf Pengawas Keuangan", 58, sigY + 6, { align: "center" });
            doc.text(fullSppgName.value, 58, sigY + 11.5, { align: "center" });

            // Tanda Tangan Kiri (Cukup Nama Saja Tanpa Embel-embel)
            doc.setFont("times", "bold");
            const namaKiri = `( ${namaPengawasKeuangan.value} )`;
            doc.text(namaKiri, 58, sigY + 45, { align: "center" });
            const namaKiriW = doc.getTextWidth(namaKiri);
            doc.setLineWidth(0.3);
            doc.line(58 - namaKiriW / 2, sigY + 46, 58 + namaKiriW / 2, sigY + 46);

            // Kolom Kanan: Yang Menerima
            doc.setFont("times", "normal");
            doc.text("Yang Menerima,", 152, sigY + 6, { align: "center" });

            // Tanda Tangan Kanan (Cukup Nama Saja Tanpa Embel-embel)
            doc.setFont("times", "bold");
            const namaKanan = `( ${row.petugas.nama} )`;
            doc.text(namaKanan, 152, sigY + 45, { align: "center" });
            const namaKananW = doc.getTextWidth(namaKanan);
            doc.setLineWidth(0.3);
            doc.line(152 - namaKananW / 2, sigY + 46, 152 + namaKananW / 2, sigY + 46);
        });

        const fileName =
            viewMode.value === "single" && displayedReceipts.value[0]
                ? `Kwitansi_Gaji_${displayedReceipts.value[0].petugas.nama.replace(/\s+/g, "_")}_${tanggalKuitansi.value || "2026"}.pdf`
                : `Kwitansi_Gaji_Petugas_SPPG_${displayedReceipts.value.length}_Personil_${tanggalKuitansi.value || "2026"}.pdf`;

        doc.save(fileName);
    } catch (err) {
        console.error("Gagal membuat PDF kuitansi:", err);
        alert("Terjadi kendala saat membuat PDF. Silakan coba kembali.");
    } finally {
        isGeneratingPdf.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[99999] overflow-y-auto"
        >
            <!-- Full-Screen Backdrop Overlay (100% Layar Penuh termasuk Sidebar & Header) -->
            <div
                class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity cursor-pointer"
                @click="$emit('close')"
            ></div>

            <!-- Centering Container -->
            <div class="fixed inset-0 z-10 flex items-center justify-center p-2 sm:p-4 pointer-events-none">
                <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col max-h-[92vh] pointer-events-auto">
            <!-- ─── Modal Header ─── -->
            <div class="px-5 sm:px-6 py-3.5 bg-gradient-to-r from-emerald-800 to-teal-900 text-white flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-white/10 text-emerald-200">
                        <FileText class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold">Download Kuitansi Gaji Petugas</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30">
                                Format PDF Resmi (1 Hal / Kuitansi)
                            </span>
                        </div>
                        <p class="text-[11px] text-emerald-100/80">
                            Format kedinasan SPPG BGN lengkap dengan kop resmi dan langsung diunduh sebagai file PDF
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="showSettings = !showSettings"
                        :class="[
                            'p-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                            showSettings ? 'bg-white text-emerald-900 shadow-sm' : 'bg-white/10 hover:bg-white/20 text-white'
                        ]"
                        title="Atur parameter nomor & variabel kuitansi"
                    >
                        <Settings2 class="h-4 w-4" />
                        <span class="hidden sm:inline">Pengaturan Kuitansi</span>
                        <ChevronUp v-if="showSettings" class="h-3 w-3 hidden sm:inline" />
                        <ChevronDown v-else class="h-3 w-3 hidden sm:inline" />
                    </button>

                    <button
                        type="button"
                        @click="$emit('close')"
                        class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- ─── Sub-Bar Filter: Mode Semua vs Per Orangan ─── -->
            <div class="px-5 sm:px-6 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 shrink-0 text-xs">
                <!-- Mode View Selector -->
                <div class="flex items-center gap-1.5 p-1 bg-white rounded-xl border border-slate-200 shadow-2xs">
                    <button
                        type="button"
                        @click="viewMode = 'all'"
                        :class="[
                            'px-3 py-1.5 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1.5',
                            viewMode === 'all'
                                ? 'bg-emerald-600 text-white shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        <span>Semua Terpilih</span>
                        <span
                            :class="[
                                'px-1.5 py-0.2 rounded-full text-[10px]',
                                viewMode === 'all' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700'
                            ]"
                        >
                            {{ receiptList.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="viewMode = 'single'"
                        :class="[
                            'px-3 py-1.5 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1.5',
                            viewMode === 'single'
                                ? 'bg-emerald-600 text-white shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        <User class="h-3.5 w-3.5" />
                        <span>Per Orangan</span>
                    </button>
                </div>

                <!-- Selector Personil Spesifik (Saat mode Per Orangan) -->
                <div v-if="viewMode === 'single'" class="flex items-center gap-2 flex-1 sm:max-w-md">
                    <label class="text-slate-500 font-semibold shrink-0">Pilih Personil:</label>
                    <select
                        v-model="singlePetugasId"
                        class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                        <option
                            v-for="(r, idx) in receiptList"
                            :key="r.petugas.id"
                            :value="r.petugas.id"
                        >
                            {{ String(idx + 1).padStart(2, '0') }}. {{ r.petugas.nama }} ({{ r.petugas.jabatan || 'Personil' }}) — {{ formatRupiah(r.amount) }}
                        </option>
                    </select>
                </div>

                <!-- Info Status -->
                <div class="flex items-center gap-2 text-slate-500 text-[11px] self-end sm:self-auto">
                    <span>Akan diunduh: <b>{{ displayedReceipts.length }} kuitansi</b> (1 kuitansi per halaman A4)</span>
                </div>
            </div>

            <!-- ─── Drawer Pengaturan Variabel Kuitansi ─── -->
            <div
                v-if="showSettings"
                class="px-5 sm:px-6 py-3.5 bg-emerald-50/70 border-b border-emerald-200 text-xs space-y-3 shrink-0 animate-in slide-in-from-top-2 duration-200"
            >
                <div class="flex items-center justify-between pb-1 border-b border-emerald-200/60">
                    <div class="flex items-center gap-1.5 font-bold text-emerald-950">
                        <Sparkles class="h-4 w-4 text-emerald-600" />
                        <span>Pengaturan Parameter & Variabel Nomor Kuitansi</span>
                    </div>
                    <span class="text-[10px] text-emerald-700 font-mono">
                        Format: [No Urut]/[Jenis]/[Singkatan SPPG]/[Bulan Romawi]/[Tahun]
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
                    <!-- No Urut Mulai -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">No Urut Awal</label>
                        <input
                            v-model.number="startNoUrut"
                            type="number"
                            min="1"
                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white font-mono font-bold text-slate-800 text-xs focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>

                    <!-- Jenis Dokumen -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Jenis Dokumen</label>
                        <select
                            v-model="jenisKuitansi"
                            class="w-full px-2 py-1.5 rounded-lg border border-slate-300 bg-white font-mono font-bold text-slate-800 text-xs focus:ring-1 focus:ring-emerald-500"
                        >
                            <option value="KUI.Ops">KUI.Ops (Operasional/Gaji)</option>
                            <option value="KUI.BanPer">KUI.BanPer (Bantuan Pemerintah)</option>
                            <option value="KUI.BB">KUI.BB (Bahan Baku)</option>
                            <option value="KUI.IF">KUI.IF (Insentif Fasilitas)</option>
                        </select>
                    </div>

                    <!-- Singkatan SPPG untuk Nomor Dokumen -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Kode SPPG (No Doc)</label>
                        <input
                            v-model="singkatanSppg"
                            type="text"
                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white font-mono font-bold text-slate-800 text-xs focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>

                    <!-- Bulan Romawi -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Bulan Romawi</label>
                        <input
                            v-model="bulanRomawi"
                            type="text"
                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white font-mono font-bold text-slate-800 text-xs focus:ring-1 focus:ring-emerald-500 text-center"
                        />
                    </div>

                    <!-- Tahun -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Tahun</label>
                        <input
                            v-model="tahunKuitansi"
                            type="text"
                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white font-mono font-bold text-slate-800 text-xs focus:ring-1 focus:ring-emerald-500 text-center"
                        />
                    </div>

                    <!-- Tanggal Kuitansi -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Tgl Kuitansi</label>
                        <input
                            v-model="tanggalKuitansi"
                            type="date"
                            class="w-full px-2 py-1.5 rounded-lg border border-slate-300 bg-white font-semibold text-slate-800 text-xs focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>

                    <!-- Lokasi TTD -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Lokasi TTD</label>
                        <input
                            v-model="lokasiKuitansi"
                            type="text"
                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white font-semibold text-slate-800 text-xs focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <!-- Penandatangan Keuangan & Format Preview -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Nama Staf Pengawas Keuangan (Kiri)</label>
                        <input
                            v-model="namaPengawasKeuangan"
                            type="text"
                            placeholder="I Gusti Ayu Made Padmi Swari"
                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white font-bold text-slate-800 text-xs focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Contoh Hasil Nomor Kuitansi</label>
                        <div class="px-3 py-1.5 rounded-lg bg-white border border-emerald-300 text-emerald-950 font-mono font-bold text-xs">
                            {{ getFormattedNomorKuitansi(0) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── AREA PREVIEW KUITANSI ─── -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-slate-100/80">
                <div class="space-y-6 max-w-3xl mx-auto">
                    <div
                        v-for="(row, idx) in displayedReceipts"
                        :key="row.petugas.id"
                        class="bg-white p-6 sm:p-10 rounded-2xl shadow-md border border-slate-200/90 text-slate-900 font-serif relative"
                    >
                        <!-- Badge Halaman Preview -->
                        <div class="absolute top-3 right-4 text-[10px] font-sans font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">
                            Kuitansi #{{ viewMode === 'all' ? (idx + 1) : (receiptList.findIndex(r => r.petugas.id === row.petugas.id) + 1) }} dari {{ receiptList.length }}
                        </div>

                        <!-- 1. KOP DOKUMEN RESMI SPPG BGN -->
                        <div v-html="renderedKopHtml" class="w-full"></div>

                        <!-- 2. JUDUL KUITANSI & NOMOR RESMI -->
                        <div class="text-center my-5">
                            <h1 class="text-xl sm:text-2xl font-bold tracking-[0.25em] underline uppercase font-serif text-slate-950">
                                K U I T A N S I
                            </h1>
                            <div class="text-xs sm:text-sm font-sans font-medium text-slate-800 mt-1.5 tracking-wide">
                                {{ getFormattedNomorKuitansi(viewMode === 'all' ? idx : receiptList.findIndex(r => r.petugas.id === row.petugas.id)) }}
                            </div>
                        </div>

                        <!-- 3. ISI RINCIAN KUITANSI SESUAI TEMPLATE USER -->
                        <div class="my-6 space-y-3.5 text-xs sm:text-sm font-serif">
                            <table class="w-full border-collapse border-none leading-relaxed">
                                <tbody>
                                    <!-- Baris 1: Sudah Terima Dari -->
                                    <tr>
                                        <td class="w-36 sm:w-44 align-top py-1.5 font-normal whitespace-nowrap text-slate-950">
                                            Sudah Terima dari
                                        </td>
                                        <td class="w-4 align-top py-1.5 text-center">:</td>
                                        <td class="align-top py-1.5 font-serif text-slate-950">
                                            <div>Staf Pengawas Keuangan {{ fullSppgName }}</div>
                                            <div class="mt-0.5 font-sans text-xs text-slate-700">
                                                ID SPPG : <span class="font-mono font-bold text-slate-950">{{ props.unitSppg?.id_sppg || 'QQCV0LUG' }}</span>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Baris 2: Uang Sejumlah & Angka -->
                                    <tr>
                                        <td class="align-top py-2 font-normal whitespace-nowrap text-slate-950">
                                            Uang Sejumlah
                                        </td>
                                        <td class="align-top py-2 text-center">:</td>
                                        <td class="align-top py-2">
                                            <div class="italic font-bold bg-slate-50 border border-dashed border-slate-300 p-2 rounded-lg text-slate-900 leading-normal inline-block text-xs sm:text-sm">
                                                # {{ angkaKeTerbilang(row.amount) }} Rupiah #
                                            </div>
                                            <div class="mt-2 text-base sm:text-lg font-black font-sans text-slate-950">
                                                {{ formatRupiah(row.amount) }}
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Baris 3: Untuk Pembayaran -->
                                    <tr>
                                        <td class="align-top py-2 font-normal whitespace-nowrap text-slate-950">
                                            Untuk Pembayaran
                                        </td>
                                        <td class="align-top py-2 text-center">:</td>
                                        <td class="align-top py-2 text-justify text-slate-900 leading-relaxed font-serif">
                                            {{ getKeteranganPembayaran(row) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- 4. TANDA TANGAN 2 KOLOM (Nama SPPG Lengkap & Tanpa Embel-embel) -->
                        <div class="mt-10 sm:mt-12 text-xs sm:text-sm font-serif">
                            <table class="w-full border-collapse border-none">
                                <tbody>
                                    <tr>
                                        <td class="w-1/2 text-center align-top"></td>
                                        <td class="w-1/2 text-center align-top font-serif text-slate-950">
                                            {{ lokasiKuitansi }}, {{ formatTanggalIndo(tanggalKuitansi) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <!-- Kolom Kiri: Staf Pengawas Keuangan SPPG ... (Nama SPPG Lengkap Tidak Disingkat) -->
                                        <td class="w-1/2 text-center align-top pt-2">
                                            <div class="font-serif">Staf Pengawas Keuangan</div>
                                            <div class="font-serif">{{ fullSppgName }}</div>
                                            <div class="h-20 sm:h-24"></div>
                                            <div class="font-bold underline text-slate-950 font-serif">
                                                ( {{ namaPengawasKeuangan }} )
                                            </div>
                                        </td>

                                        <!-- Kolom Kanan: Yang Menerima (Cukup Nama Saja Tanpa Embel-embel) -->
                                        <td class="w-1/2 text-center align-top pt-2">
                                            <div class="font-serif">Yang Menerima,</div>
                                            <div class="h-4"></div>
                                            <div class="h-20 sm:h-24"></div>
                                            <div class="font-bold underline text-slate-950 font-serif">
                                                ( {{ row.petugas.nama }} )
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── Modal Footer ─── -->
            <div class="px-5 sm:px-6 py-3 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 shrink-0 text-xs">
                <div class="flex items-center gap-2 text-slate-500">
                    <Check class="h-4 w-4 text-emerald-600" />
                    <span>
                        Siap mengunduh <b>{{ displayedReceipts.length }}</b> lembar kuitansi resmi (A4 PDF).
                    </span>
                </div>

                <div class="flex items-center gap-2 justify-end">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>

                    <!-- Tombol Download Langsung PDF (Bukan Print) -->
                    <button
                        type="button"
                        @click="downloadPdfKwitansi"
                        :disabled="isGeneratingPdf"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition-all cursor-pointer disabled:opacity-50"
                        title="Langsung unduh kuitansi resmi dalam format file PDF (1 lembar per halaman A4)"
                    >
                        <RefreshCw v-if="isGeneratingPdf" class="h-4 w-4 animate-spin" />
                        <Download v-else class="h-4 w-4" />
                        <span>{{ isGeneratingPdf ? "Menyiapkan PDF..." : "Download PDF Kuitansi" }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
    </Teleport>
</template>
