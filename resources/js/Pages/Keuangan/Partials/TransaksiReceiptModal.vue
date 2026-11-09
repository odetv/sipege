<script setup>
import { ref, computed, watch } from "vue";
import {
    X,
    FileText,
    Download,
    Check,
    Settings2,
    ChevronLeft,
    ChevronRight,
    Building2,
    DollarSign,
    RefreshCw,
    Receipt,
    Printer,
} from "lucide-vue-next";
import jsPDF from "jspdf";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";
import { getActiveKopConfig } from "@/Services/kopDokumenHelper";
import { LOGO_BGN_BASE64, LOGO_YAYASAN_BASE64 } from "@/Services/logoBase64Helper";

const props = defineProps({
    show: { type: Boolean, default: false },
    item: { type: Object, default: null },
    allRows: { type: Array, default: () => [] },
    unitSppg: { type: Object, default: null },
    defaultPengawasKeuangan: { type: String, default: "I Gusti Ayu Made Padmi Swari, S.Ak." },
});

const emit = defineEmits(["close"]);

const isModalVisible = computed(() => Boolean(props.show));

// Formatters
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

// Fungsi Terbilang Rupiah Resmi
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
    if (angka < 1000000000000) {
        const sisa = angka % 1000000000;
        return (
            angkaKeTerbilang(Math.floor(angka / 1000000000)) +
            " Miliar" +
            (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "")
        );
    }
    return (
        angkaKeTerbilang(Math.floor(angka / 1000000000000)) +
        " Triliun" +
        (angka % 1000000000000 !== 0 ? " " + angkaKeTerbilang(angka % 1000000000000) : "")
    );
}

// List data kuitansi
const receiptList = computed(() => {
    if (props.item) {
        return [props.item];
    }
    if (props.allRows && props.allRows.length > 0) {
        return props.allRows;
    }
    return [];
});

const currentIndex = ref(0);
watch(
    () => props.item,
    () => {
        currentIndex.value = 0;
    }
);
watch(
    () => props.show,
    (val) => {
        if (val) currentIndex.value = 0;
    }
);

const currentItem = computed(() => {
    if (receiptList.value.length === 0) return null;
    return receiptList.value[currentIndex.value] || receiptList.value[0];
});

// Konfigurasi & Nama Penandatangan
const kopConfig = ref(getActiveKopConfig());
const lokasiKuitansi = ref("Desa Tegallinggah");
const namaPengawasKeuangan = ref(props.defaultPengawasKeuangan || "I Gusti Ayu Made Padmi Swari, S.Ak.");
const isGeneratingPdf = ref(false);

const fullSppgName = computed(() => {
    return (
        kopConfig.value?.nama_instansi_1 ||
        props.unitSppg?.nama_sppg ||
        "SPPG Buleleng Sukasada Tegallinggah"
    );
});

function getNomorKuitansi(row, idx) {
    if (row?.nomor_transaksi) {
        return `KWT-${row.nomor_transaksi}`;
    }
    const tgl = (row?.tanggal || new Date().toISOString().slice(0, 10)).replace(/-/g, "");
    const seq = String(idx + 1).padStart(3, "0");
    return `KWT/TRX/${tgl}/${seq}`;
}

function getUraianPembayaran(row) {
    if (!row) return "-";
    const parts = [];
    if (row.nama_transaksi) {
        parts.push(`Pembelian ${row.nama_transaksi}`);
    }
    if (row.volume || row.qty) {
        parts.push(`(${row.volume || row.qty} ${row.satuan && row.satuan !== '-' ? row.satuan : 'Kg'})`);
    }
    if (row.kategori) {
        parts.push(`- Kategori ${row.kategori}`);
    }
    if (row.vendor && row.vendor !== "-") {
        parts.push(`Supplier: ${row.vendor}`);
    }
    if (row.keterangan && row.keterangan !== "-") {
        parts.push(`- ${row.keterangan}`);
    }
    return parts.join(" ");
}

// Download PDF Kuitansi Menggunakan jsPDF
async function downloadPdfKwitansi(singleOnly = false) {
    const items = singleOnly && currentItem.value ? [currentItem.value] : receiptList.value;
    if (items.length === 0) {
        alert("Tidak ada data transaksi untuk diunduh kuitansinya.");
        return;
    }

    isGeneratingPdf.value = true;

    try {
        const doc = new jsPDF({
            orientation: "portrait",
            unit: "mm",
            format: "a4",
        });

        items.forEach((row, idx) => {
            if (idx > 0) {
                doc.addPage();
            }

            const noKwt = getNomorKuitansi(row, idx);

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
            doc.text(noKwt, 105, 54, { align: "center" });

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
            const terbilangText = `# ${angkaKeTerbilang(row.nominal || 0)} Rupiah #`;
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
            doc.text(`Rp. ${formatRibuan(row.nominal || 0)},-`, 62, curY);

            // Baris 3: Untuk Pembayaran
            curY += 10.5;
            doc.setFont("times", "normal");
            doc.setFontSize(11);
            doc.text("Untuk Pembayaran", 20, curY);
            doc.text(":", 58, curY);

            const ketText = getUraianPembayaran(row);
            const ketLines = doc.splitTextToSize(ketText, 128);
            doc.text(ketLines, 62, curY);

            // 4. Tanda Tangan 2 Kolom
            const sigY = Math.max(curY + ketLines.length * 5.2 + 20, 205);
            const tglBayar = row.tanggal ? formatTanggalIndo(row.tanggal) : formatTanggalIndo(new Date());

            // Tanggal di atas kolom kanan
            doc.setFont("times", "normal");
            doc.setFontSize(11);
            doc.text(
                `${lokasiKuitansi.value}, ${tglBayar}`,
                152,
                sigY,
                { align: "center" }
            );

            // Kolom Kiri: Staf Pengawas Keuangan SPPG
            doc.text("Staf Pengawas Keuangan", 58, sigY + 6, { align: "center" });
            doc.text(fullSppgName.value, 58, sigY + 11.5, { align: "center" });

            // Tanda Tangan Kiri
            doc.setFont("times", "bold");
            const namaKiri = `( ${namaPengawasKeuangan.value} )`;
            doc.text(namaKiri, 58, sigY + 45, { align: "center" });
            const namaKiriW = doc.getTextWidth(namaKiri);
            doc.setLineWidth(0.3);
            doc.line(58 - namaKiriW / 2, sigY + 46, 58 + namaKiriW / 2, sigY + 46);

            // Kolom Kanan: Yang Menerima (Supplier / Rekanan)
            doc.setFont("times", "normal");
            doc.text("Yang Menerima,", 152, sigY + 6, { align: "center" });

            doc.setFont("times", "bold");
            const penerimaNama = row.vendor && row.vendor !== '-' ? row.vendor : (row.supplier?.nama_usaha || "Penyedia / Rekanan");
            const namaKanan = `( ${penerimaNama} )`;
            doc.text(namaKanan, 152, sigY + 45, { align: "center" });
            const namaKananW = doc.getTextWidth(namaKanan);
            doc.setLineWidth(0.3);
            doc.line(152 - namaKananW / 2, sigY + 46, 152 + namaKananW / 2, sigY + 46);
        });

        const timeStamp = new Date().toISOString().slice(0, 10);
        const fileName =
            items.length === 1
                ? `Kuitansi_Transaksi_${(items[0].nomor_transaksi || 'TRX').replace(/\s+/g, '_')}_${timeStamp}.pdf`
                : `Kuitansi_Transaksi_SPPG_${timeStamp}.pdf`;

        doc.save(fileName);
    } catch (e) {
        console.error("Gagal generate PDF kuitansi transaksi:", e);
        alert("Gagal mengunduh PDF kuitansi: " + e.message);
    } finally {
        isGeneratingPdf.value = false;
    }
}

function printReceipt() {
    window.print();
}
</script>

<template>
    <Teleport to="body">
        <div v-if="isModalVisible" class="fixed inset-0 z-[99999] overflow-y-auto">
            <!-- Full-Screen Backdrop Overlay -->
            <div
                class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity cursor-pointer"
                @click="$emit('close')"
            ></div>

            <div class="relative min-h-screen flex items-center justify-center p-3 sm:p-5">
                <div
                    class="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[92vh]"
                    @click.stop
                >
                    <!-- Header Modal -->
                    <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-200 bg-slate-50/80 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-2xl bg-emerald-100 text-emerald-800 shadow-xs">
                                <Receipt class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                                    Kuitansi Pembayaran Transaksi Keuangan
                                </h3>
                                <p class="text-xs text-slate-500 font-medium">
                                    Format resmi kuitansi tanda terima pengeluaran/pembiayaan SPPG
                                    <span v-if="receiptList.length > 1" class="font-bold text-emerald-700">
                                        ({{ receiptList.length }} Kuitansi)
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Navigasi Lembar Kuitansi jika ada banyak -->
                            <div v-if="receiptList.length > 1" class="flex items-center gap-1.5 mr-2 bg-white px-2 py-1 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">
                                <button
                                    type="button"
                                    :disabled="currentIndex === 0"
                                    @click="currentIndex--"
                                    class="p-1 rounded-lg hover:bg-slate-100 disabled:opacity-30 cursor-pointer"
                                    title="Lembar Sebelumnya"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <span>{{ currentIndex + 1 }} / {{ receiptList.length }}</span>
                                <button
                                    type="button"
                                    :disabled="currentIndex === receiptList.length - 1"
                                    @click="currentIndex++"
                                    class="p-1 rounded-lg hover:bg-slate-100 disabled:opacity-30 cursor-pointer"
                                    title="Lembar Berikutnya"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>

                            <button
                                type="button"
                                @click="$emit('close')"
                                class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Body Modal: Lembar Kuitansi Kertas A4 -->
                    <div class="p-4 sm:p-6 overflow-y-auto bg-slate-100 flex-1 space-y-4">
                        <div v-if="currentItem" class="max-w-[760px] mx-auto bg-white p-6 sm:p-10 rounded-2xl shadow-md border border-slate-200 text-slate-900 font-serif relative">
                            <!-- Kop Kuitansi -->
                            <div class="relative text-center border-b-2 border-black pb-2 mb-6">
                                <div class="absolute left-0 top-0">
                                    <img v-if="LOGO_BGN_BASE64" :src="LOGO_BGN_BASE64" alt="BGN" class="h-14 w-auto object-contain" />
                                </div>
                                <div class="absolute right-0 top-0">
                                    <img v-if="LOGO_YAYASAN_BASE64" :src="LOGO_YAYASAN_BASE64" alt="Yayasan" class="h-14 w-auto object-contain" />
                                </div>
                                <h4 class="font-bold text-sm sm:text-base uppercase tracking-wider text-black">
                                    {{ kopConfig?.nama_instansi_1 || "SPPG BULELENG SUKASADA TEGALLINGGAH" }}
                                </h4>
                                <h5 class="font-bold text-xs sm:text-sm uppercase tracking-wide text-black mt-0.5">
                                    {{ kopConfig?.nama_instansi_2 || "YAYASAN PESANTREN MIFTAHUL ULUM" }}
                                </h5>
                                <p class="text-[10px] sm:text-[11px] text-slate-700 mt-1 max-w-lg mx-auto font-sans">
                                    {{ kopConfig?.alamat_lengkap || "Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali" }}
                                    <span v-if="kopConfig?.email"> | E-mail: {{ kopConfig.email }}</span>
                                </p>
                            </div>

                            <!-- Judul & No Kuitansi -->
                            <div class="text-center mb-6">
                                <h3 class="text-lg sm:text-xl font-bold tracking-[0.2em] underline uppercase">
                                    K U I T A N S I
                                </h3>
                                <p class="text-xs font-sans text-slate-600 mt-1 font-semibold">
                                    {{ getNomorKuitansi(currentItem, currentIndex) }}
                                </p>
                            </div>

                            <!-- Baris Rincian Kuitansi -->
                            <div class="space-y-4 text-xs sm:text-sm leading-relaxed">
                                <div class="grid grid-cols-12 gap-2">
                                    <div class="col-span-3 font-semibold text-slate-700">Sudah Terima dari</div>
                                    <div class="col-span-1 text-center font-bold">:</div>
                                    <div class="col-span-8 font-medium">
                                        Staf Pengawas Keuangan {{ fullSppgName }}
                                        <div class="text-[11px] font-sans font-bold text-slate-600 mt-0.5">
                                            ID SPPG: <span class="text-slate-900">{{ unitSppg?.id_sppg || "QQCV0LUG" }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-2">
                                    <div class="col-span-3 font-semibold text-slate-700">Uang Sejumlah</div>
                                    <div class="col-span-1 text-center font-bold">:</div>
                                    <div class="col-span-8">
                                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl italic font-serif text-slate-800 font-bold">
                                            # {{ angkaKeTerbilang(currentItem.nominal || 0) }} Rupiah #
                                        </div>
                                        <div class="mt-2 text-base font-black font-sans text-emerald-800">
                                            {{ formatRupiah(currentItem.nominal) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-2">
                                    <div class="col-span-3 font-semibold text-slate-700">Untuk Pembayaran</div>
                                    <div class="col-span-1 text-center font-bold">:</div>
                                    <div class="col-span-8 font-medium text-slate-900 leading-normal">
                                        {{ getUraianPembayaran(currentItem) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Tanda Tangan Kuitansi -->
                            <div class="mt-12 pt-6 grid grid-cols-2 gap-6 text-xs sm:text-sm">
                                <div class="text-center space-y-1">
                                    <p class="font-sans font-medium text-slate-600">Staf Pengawas Keuangan</p>
                                    <p class="font-bold text-slate-900">{{ fullSppgName }}</p>
                                    <div class="h-20"></div>
                                    <p class="font-bold underline text-slate-900">
                                        ( {{ namaPengawasKeuangan }} )
                                    </p>
                                </div>

                                <div class="text-center space-y-1">
                                    <p class="font-sans font-medium text-slate-600">
                                        {{ lokasiKuitansi }}, {{ currentItem.tanggal ? formatTanggalIndo(currentItem.tanggal) : formatTanggalIndo(new Date()) }}
                                    </p>
                                    <p class="font-bold text-slate-900">Yang Menerima,</p>
                                    <div class="h-20"></div>
                                    <p class="font-bold underline text-slate-900">
                                        ( {{ currentItem.vendor && currentItem.vendor !== '-' ? currentItem.vendor : (currentItem.supplier?.nama_usaha || "Penyedia / Rekanan") }} )
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Modal Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-5 sm:px-6 py-4 border-t border-slate-200 bg-white shrink-0">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <span class="text-xs text-slate-500 font-semibold">
                                Total: {{ receiptList.length }} Kuitansi | {{ formatRupiah(receiptList.reduce((sum, r) => sum + (Number(r.nominal) || 0), 0)) }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="px-4 py-2 text-xs font-bold rounded-xl text-slate-600 hover:bg-slate-100 cursor-pointer"
                            >
                                Tutup
                            </button>
                            <button
                                v-if="receiptList.length > 1"
                                type="button"
                                @click="downloadPdfKwitansi(true)"
                                :disabled="isGeneratingPdf"
                                class="px-4 py-2 text-xs font-bold rounded-xl border border-emerald-300 text-emerald-800 hover:bg-emerald-50 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                title="Download hanya lembar yang sedang dilihat saat ini"
                            >
                                <Download class="h-4 w-4" />
                                <span>Download Lembar Ini (.pdf)</span>
                            </button>
                            <button
                                type="button"
                                @click="downloadPdfKwitansi(false)"
                                :disabled="isGeneratingPdf"
                                class="px-5 py-2.5 text-xs font-black rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs flex items-center gap-2 cursor-pointer disabled:opacity-50"
                            >
                                <RefreshCw v-if="isGeneratingPdf" class="h-4 w-4 animate-spin" />
                                <Download v-else class="h-4 w-4" />
                                <span>{{ isGeneratingPdf ? 'Menyiapkan PDF...' : (receiptList.length > 1 ? `Download Semua Kuitansi (${receiptList.length} Hal PDF)` : 'Download Kuitansi (PDF)') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

