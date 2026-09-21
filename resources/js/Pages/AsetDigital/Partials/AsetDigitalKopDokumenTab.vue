<script setup>
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import {
    FileText,
    Settings2,
    Eye,
    Download,
    Printer,
    Save,
    RotateCcw,
    Upload,
    CheckCircle2,
    Shield,
    Image as ImageIcon,
    Building2,
    MapPin,
    Phone,
    Mail,
    Globe,
    Layers,
    Sliders,
    Sparkles,
    FileSpreadsheet,
    FileCheck,
    LayoutTemplate,
} from "lucide-vue-next";
import {
    saveKopConfigToLocal,
    getKopBorderStyle,
    getDefaultKopConfig,
} from "@/Services/kopDokumenHelper";

const props = defineProps({
    kopConfig: {
        type: Object,
        default: null,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
});

// Setup Form State
const defaultConfig = getDefaultKopConfig(props.unitSppg);
const activeConfig = props.kopConfig || defaultConfig;

const form = useForm({
    nama_instansi_1: activeConfig.nama_instansi_1 || "SPPG BULELENG SUKASADA TEGALLINGGAH",
    nama_instansi_2: activeConfig.nama_instansi_2 || "YAYASAN PESANTREN MIFTAHUL ULUM",
    nama_unit: activeConfig.nama_unit || props.unitSppg?.nama || "SPPG Buleleng Sukasada Tegallinggah",
    kode_unit: activeConfig.kode_unit || props.unitSppg?.kode_sppg || "51.08.05.2013.03",
    id_sppg: activeConfig.id_sppg || props.unitSppg?.id_sppg || "QQCV0LUG",
    alamat_lengkap: activeConfig.alamat_lengkap || props.unitSppg?.alamat_lengkap || "Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali",
    desa_kelurahan: activeConfig.desa_kelurahan || props.unitSppg?.desa_kelurahan || "Tegallinggah",
    kecamatan: activeConfig.kecamatan || props.unitSppg?.kecamatan || "Sukasada",
    kabupaten: activeConfig.kabupaten || props.unitSppg?.kabupaten || "Buleleng",
    provinsi: activeConfig.provinsi || props.unitSppg?.provinsi || "Bali",
    kode_pos: activeConfig.kode_pos || props.unitSppg?.kode_pos || "81161",
    telepon: activeConfig.telepon || "",
    whatsapp: activeConfig.whatsapp || "",
    email: activeConfig.email || "sppgsukasadategallinggah@gmail.com",
    website: activeConfig.website || "",
    logo_kiri_url: activeConfig.logo_kiri_url || "/images/logo/BGN_LOGO_MAIN.png",
    logo_kanan_url: activeConfig.logo_kanan_url || "/images/logo/Logo_Yayasan.png",
    layout_logo: activeConfig.layout_logo || "dual",
    gaya_garis: activeConfig.gaya_garis || "ganda_kedinasan",
    template_style: activeConfig.template_style || "klasik_formal",
    is_aktif: activeConfig.is_aktif ?? true,
});

// UI States
const previewOrientation = ref("portrait"); // 'portrait' | 'landscape'
const previewDocumentType = ref("surat"); // 'surat' | 'work_order' | 'blank'
const zoomLevel = ref("85"); // '75' | '85' | '100'
const activeFormTab = ref("instansi"); // 'instansi' | 'kontak' | 'logo' | 'all'
const saveSuccess = ref(false);
const isUploadingLogo = ref(false);
const uploadError = ref("");

// Logo Presets
const bgnLogoPresets = [
    { label: "Logo Bulat BGN Berwarna (Resmi)", url: "/images/logo/BGN_LOGO_MAIN.png" },
    { label: "Logo Type BGN Warna", url: "/images/logo/BGN_LOGOTYPE_MAIN.png" },
    { label: "Logo Bulat BGN Monokrom (Hitam Putih)", url: "/images/logo/BGN_LOGO_BW_B.png" },
    { label: "Logo Type BGN Monokrom", url: "/images/logo/BGN_LOGOTYPE_BW_B.png" },
];

const yayasanLogoPresets = [
    { label: "Logo Yayasan Pesantren Miftahul Ulum (Resmi)", url: "/images/logo/Logo_Yayasan.png" },
    { label: "Logo Bulat BGN Berwarna", url: "/images/logo/BGN_LOGO_MAIN.png" },
    { label: "Logo Type BGN Warna", url: "/images/logo/BGN_LOGOTYPE_MAIN.png" },
    { label: "Logo Monokrom Hitam Putih", url: "/images/logo/BGN_LOGO_BW_B.png" },
    { label: "Tanpa Logo Kanan (Kosong)", url: "" },
];

const showUnitLine = computed(() => {
    const inst1 = (form.nama_instansi_1 || "").trim().toUpperCase();
    const inst2 = (form.nama_instansi_2 || "").trim().toUpperCase();
    const unit = (form.nama_unit || "").trim().toUpperCase();
    return Boolean(unit && unit !== inst1 && unit !== inst2 && !inst1.includes(unit));
});

// Auto-save to localStorage for instant synchronization with other tabs
watch(
    () => form.data(),
    (newVal) => {
        saveKopConfigToLocal(newVal);
    },
    { deep: true, immediate: true }
);

function submitForm() {
    form.post(route("aset-digital.kop-dokumen.save"), {
        preserveScroll: true,
        onSuccess: () => {
            saveSuccess.value = true;
            saveKopConfigToLocal(form.data());
            setTimeout(() => {
                saveSuccess.value = false;
            }, 3500);
        },
    });
}

function resetToUnitDefaults() {
    if (confirm("Reset konfigurasi kop ke data bawaan unit SPPG aktif?")) {
        const def = getDefaultKopConfig(props.unitSppg);
        Object.keys(def).forEach((k) => {
            if (k in form) {
                form[k] = def[k];
            }
        });
        saveKopConfigToLocal(form.data());
    }
}

function handleLogoUpload(event, position = "kiri") {
    const file = event.target.files?.[0];
    if (!file) return;

    isUploadingLogo.value = true;
    uploadError.value = "";

    const formData = new FormData();
    formData.append("logo", file);
    formData.append("position", position);

    fetch(route("aset-digital.kop-dokumen.upload-logo"), {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "",
        },
        body: formData,
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success && data.url) {
                if (position === "kiri") {
                    form.logo_kiri_url = data.url;
                } else {
                    form.logo_kanan_url = data.url;
                }
                saveKopConfigToLocal(form.data());
            } else {
                uploadError.value = data.message || "Gagal mengunggah logo.";
            }
        })
        .catch((err) => {
            uploadError.value = "Terjadi kesalahan jaringan saat mengunggah logo.";
            console.error(err);
        })
        .finally(() => {
            isUploadingLogo.value = false;
        });
}

function printKopPreview() {
    window.open(route("aset-digital.kop-dokumen.download-template", { format: "print", orientation: previewOrientation.value }), "_blank");
}

function downloadDocxTemplate() {
    window.location.href = route("aset-digital.kop-dokumen.download-template", { format: "docx", orientation: previewOrientation.value });
}

// Contacts formatted line
const contactString = computed(() => {
    const list = [];
    if (form.telepon) list.push(`Telp: ${form.telepon}`);
    if (form.whatsapp) list.push(`WA: ${form.whatsapp}`);
    if (form.email) list.push(`E-mail: ${form.email}`);
    if (form.website) list.push(`Web: ${form.website}`);
    return list.length > 0 ? list.join(" | ") : (form.email ? `E-mail: ${form.email}` : "");
});

// Font family styling
const fontClass = computed(() => {
    if (form.template_style === "klasik_formal") return "font-serif";
    if (form.template_style === "modern_sppg") return "font-sans";
    return "font-sans";
});
</script>

<template>
    <div class="space-y-4">
        <!-- Top Notification Banner Success -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="saveSuccess"
                class="p-4 rounded-2xl bg-emerald-600 text-white shadow-md flex items-center justify-between gap-3"
            >
                <div class="flex items-center gap-3">
                    <CheckCircle2 class="w-5 h-5 shrink-0" />
                    <div>
                        <div class="font-bold text-sm">Konfigurasi Kop Dokumen Berhasil Disimpan!</div>
                        <div class="text-xs text-emerald-100 mt-0.5">
                            Kop ini otomatis menjadi standar kop resmi untuk seluruh unduhan berkas (Work Order, PO, Laporan) di menu-menu lainnya.
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    @click="saveSuccess = false"
                    class="text-emerald-100 hover:text-white text-xs font-bold px-2 py-1 rounded-lg bg-emerald-700/60"
                >
                    Tutup
                </button>
            </div>
        </transition>

        <!-- Compact Header Card -->
        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-teal-950 text-white shadow-sm border border-slate-700/50">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="space-y-1 min-w-0">
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-teal-500/20 text-teal-300 text-[11px] font-bold border border-teal-400/30">
                        <Shield class="w-3 h-3" />
                        <span>Kop Dokumen Resmi SPPG Terintegrasi</span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-black tracking-tight text-white">
                        Pengaturan & Pembuat Kop Dokumen Resmi
                    </h2>
                    <p class="text-xs text-slate-300">
                        Pusat pengaturan kop kedinasan Badan Gizi Nasional (BGN) dan SPPG untuk seluruh template unduhan naskah dinas.
                    </p>
                </div>

                <!-- Action Toolbar -->
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="resetToUnitDefaults"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-600/60 shadow-xs flex items-center gap-1.5 transition cursor-pointer"
                        title="Kembalikan ke data bawaan unit"
                    >
                        <RotateCcw class="w-3.5 h-3.5 text-slate-400" />
                        <span class="hidden sm:inline">Reset</span>
                    </button>

                    <button
                        type="button"
                        @click="printKopPreview"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-600/60 shadow-xs flex items-center gap-1.5 transition cursor-pointer"
                        title="Buka lembar cetak"
                    >
                        <Printer class="w-3.5 h-3.5 text-teal-400" />
                        <span>Cetak</span>
                    </button>

                    <button
                        type="button"
                        @click="downloadDocxTemplate"
                        class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs flex items-center gap-1.5 transition cursor-pointer"
                        title="Unduh template Word (.docx) dengan kop header"
                    >
                        <Download class="w-3.5 h-3.5" />
                        <span>Word (.docx)</span>
                    </button>

                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="form.processing"
                        class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-sm flex items-center gap-1.5 transition cursor-pointer disabled:opacity-50"
                    >
                        <Save class="w-3.5 h-3.5" />
                        <span>{{ form.processing ? "Menyimpan..." : "Simpan Kop" }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2-Column Responsive Workspace (Side-by-side on Laptop & Desktop >= 1024px) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <!-- LEFT COLUMN: Compact Tabbed Settings Panel (5 Columns) -->
            <div class="lg:col-span-5 space-y-3">
                <!-- Section Navigation Pills -->
                <div class="flex items-center gap-1 p-1 bg-slate-200/70 rounded-xl border border-slate-300/80 text-xs font-bold">
                    <button
                        type="button"
                        @click="activeFormTab = 'instansi'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-center transition cursor-pointer flex items-center justify-center gap-1.5"
                        :class="activeFormTab === 'instansi' ? 'bg-white text-teal-900 shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900'"
                    >
                        <Building2 class="w-3.5 h-3.5" />
                        <span class="truncate">Instansi</span>
                    </button>

                    <button
                        type="button"
                        @click="activeFormTab = 'kontak'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-center transition cursor-pointer flex items-center justify-center gap-1.5"
                        :class="activeFormTab === 'kontak' ? 'bg-white text-teal-900 shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900'"
                    >
                        <MapPin class="w-3.5 h-3.5" />
                        <span class="truncate">Kontak</span>
                    </button>

                    <button
                        type="button"
                        @click="activeFormTab = 'logo'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-center transition cursor-pointer flex items-center justify-center gap-1.5"
                        :class="activeFormTab === 'logo' ? 'bg-white text-teal-900 shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900'"
                    >
                        <ImageIcon class="w-3.5 h-3.5" />
                        <span class="truncate">Logo & Garis</span>
                    </button>

                    <button
                        type="button"
                        @click="activeFormTab = 'all'"
                        class="py-1.5 px-2.5 rounded-lg text-center transition cursor-pointer text-[11px]"
                        :class="activeFormTab === 'all' ? 'bg-white text-teal-900 shadow-xs font-extrabold' : 'text-slate-500 hover:text-slate-800'"
                        title="Tampilkan semua bidang sekaligus"
                    >
                        Semua
                    </button>
                </div>

                <!-- Form Card Content -->
                <div class="space-y-3">
                    <!-- SECTION 1: Identitas Instansi -->
                    <div
                        v-show="activeFormTab === 'instansi' || activeFormTab === 'all'"
                        class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3.5"
                    >
                        <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                            <div class="p-1.5 rounded-lg bg-teal-50 text-teal-700 border border-teal-100">
                                <Building2 class="w-4 h-4" />
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900">Identitas Instansi & Satuan Pelayanan</h3>
                                <p class="text-[11px] text-slate-500">Teks judul lembaga pada baris kop surat</p>
                            </div>
                        </div>

                        <div class="space-y-2.5 text-xs">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">
                                    Nama Satuan / Instansi Tingkat 1 (Kop Baris 1)
                                </label>
                                <input
                                    v-model="form.nama_instansi_1"
                                    type="text"
                                    class="w-full text-xs font-bold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition"
                                    placeholder="SPPG BULELENG SUKASADA TEGALLINGGAH"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">
                                    Nama Yayasan / Afiliasi (Kop Baris 2)
                                </label>
                                <input
                                    v-model="form.nama_instansi_2"
                                    type="text"
                                    class="w-full text-xs font-bold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition"
                                    placeholder="YAYASAN PESANTREN MIFTAHUL ULUM"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Nama Unit SPPG</label>
                                    <input
                                        v-model="form.nama_unit"
                                        type="text"
                                        class="w-full text-xs font-bold text-teal-900 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition"
                                        placeholder="SPPG Buleleng Sukasada Tegallinggah"
                                    />
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Kode / ID SPPG</label>
                                    <input
                                        v-model="form.kode_unit"
                                        type="text"
                                        class="w-full text-xs font-medium text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition"
                                        placeholder="51.08.05.2013.03"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Alamat Lengkap & Kontak Resmi -->
                    <div
                        v-show="activeFormTab === 'kontak' || activeFormTab === 'all'"
                        class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3.5"
                    >
                        <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                            <div class="p-1.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-100">
                                <MapPin class="w-4 h-4" />
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900">Alamat Lengkap & Kontak Resmi</h3>
                                <p class="text-[11px] text-slate-500">Rincian lokasi dan saluran komunikasi dinas</p>
                            </div>
                        </div>

                        <div class="space-y-2.5 text-xs">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap Unit</label>
                                <textarea
                                    v-model="form.alamat_lengkap"
                                    rows="2"
                                    class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition"
                                    placeholder="Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Telepon / HP</label>
                                    <input
                                        v-model="form.telepon"
                                        type="text"
                                        class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 outline-none"
                                        placeholder="0812-3456-7890"
                                    />
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">WhatsApp Resmi</label>
                                    <input
                                        v-model="form.whatsapp"
                                        type="text"
                                        class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 outline-none"
                                        placeholder="0812-3456-7890"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Email Kedinasan</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 outline-none"
                                        placeholder="sppg.sukasada@bgn.go.id"
                                    />
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Website Resmi</label>
                                    <input
                                        v-model="form.website"
                                        type="text"
                                        class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 outline-none"
                                        placeholder="bgn.go.id"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3 & 4: Logo & Format Garis -->
                    <div
                        v-show="activeFormTab === 'logo' || activeFormTab === 'all'"
                        class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4"
                    >
                        <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                            <div class="p-1.5 rounded-lg bg-purple-50 text-purple-700 border border-purple-100">
                                <ImageIcon class="w-4 h-4" />
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900">Logo & Estetika Garis Pembatas</h3>
                                <p class="text-[11px] text-slate-500">Pilihan logo resmi, posisi tata letak, dan pembatas kop</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-xs">
                            <!-- Tata Letak Logo -->
                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5">Tata Letak Logo</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <label
                                        class="p-2.5 rounded-xl border flex flex-col items-center justify-center gap-1 cursor-pointer text-center transition"
                                        :class="form.layout_logo === 'dual' ? 'border-teal-500 bg-teal-50/60 text-teal-950 font-bold' : 'border-slate-200 bg-slate-50 text-slate-600'"
                                    >
                                        <input type="radio" v-model="form.layout_logo" value="dual" class="sr-only" />
                                        <div class="flex items-center gap-1">
                                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                                            <span class="w-5 h-1 rounded-full bg-slate-300"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                                        </div>
                                        <span class="text-[10px]">Dual Logo</span>
                                    </label>

                                    <label
                                        class="p-2.5 rounded-xl border flex flex-col items-center justify-center gap-1 cursor-pointer text-center transition"
                                        :class="form.layout_logo === 'kiri' ? 'border-teal-500 bg-teal-50/60 text-teal-950 font-bold' : 'border-slate-200 bg-slate-50 text-slate-600'"
                                    >
                                        <input type="radio" v-model="form.layout_logo" value="kiri" class="sr-only" />
                                        <div class="flex items-center gap-1">
                                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                                            <span class="w-6 h-1 rounded-full bg-slate-300"></span>
                                        </div>
                                        <span class="text-[10px]">Single (Kiri)</span>
                                    </label>

                                    <label
                                        class="p-2.5 rounded-xl border flex flex-col items-center justify-center gap-1 cursor-pointer text-center transition"
                                        :class="form.layout_logo === 'tengah' ? 'border-teal-500 bg-teal-50/60 text-teal-950 font-bold' : 'border-slate-200 bg-slate-50 text-slate-600'"
                                    >
                                        <input type="radio" v-model="form.layout_logo" value="tengah" class="sr-only" />
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                                            <span class="w-6 h-1 rounded-full bg-slate-300"></span>
                                        </div>
                                        <span class="text-[10px]">Center</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Logo Kiri -->
                            <div class="pt-2 border-t border-slate-100">
                                <label class="block font-bold text-slate-700 mb-1">Logo Kiri (Logo Resmi BGN)</label>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-12 h-12 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center p-1 shrink-0 overflow-hidden">
                                        <img
                                            :src="form.logo_kiri_url"
                                            alt="Logo Kiri"
                                            class="max-w-full max-h-full object-contain"
                                            @error="$event.target.src = '/images/logo/BGN_LOGO_MAIN.png'"
                                        />
                                    </div>
                                    <div class="flex-1 space-y-1">
                                        <select
                                            v-model="form.logo_kiri_url"
                                            class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 outline-none"
                                        >
                                            <option v-for="lp in bgnLogoPresets" :key="lp.url" :value="lp.url">
                                                {{ lp.label }}
                                            </option>
                                        </select>
                                        <label class="cursor-pointer text-[11px] font-bold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1">
                                            <Upload class="w-3 h-3" />
                                            <span>Unggah Logo Kustom</span>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="sr-only"
                                                @change="handleLogoUpload($event, 'kiri')"
                                            />
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Logo Kanan -->
                            <div v-if="form.layout_logo === 'dual'" class="pt-2 border-t border-slate-100">
                                <label class="block font-bold text-slate-700 mb-1">Logo Kanan (Yayasan / Mitra)</label>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-12 h-12 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center p-1 shrink-0 overflow-hidden text-slate-400">
                                        <img
                                            v-if="form.logo_kanan_url"
                                            :src="form.logo_kanan_url"
                                            alt="Logo Kanan"
                                            class="max-w-full max-h-full object-contain"
                                        />
                                        <span v-else class="text-[9px] text-center text-slate-400">Kosong</span>
                                    </div>
                                    <div class="flex-1 space-y-1">
                                        <select
                                            v-model="form.logo_kanan_url"
                                            class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 outline-none"
                                        >
                                            <option v-for="lp in yayasanLogoPresets" :key="lp.url" :value="lp.url">
                                                {{ lp.label }}
                                            </option>
                                        </select>
                                        <div class="flex items-center gap-2">
                                            <label class="cursor-pointer text-[11px] font-bold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1">
                                                <Upload class="w-3 h-3" />
                                                <span>Unggah Logo Kanan</span>
                                                <input
                                                    type="file"
                                                    accept="image/*"
                                                    class="sr-only"
                                                    @change="handleLogoUpload($event, 'kanan')"
                                                />
                                            </label>
                                            <button
                                                v-if="form.logo_kanan_url"
                                                type="button"
                                                @click="form.logo_kanan_url = ''"
                                                class="text-[11px] text-rose-600 hover:underline cursor-pointer"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Garis & Font -->
                            <div class="pt-2 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Gaya Garis Pembatas</label>
                                    <select
                                        v-model="form.gaya_garis"
                                        class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 outline-none"
                                    >
                                        <option value="ganda_kedinasan">Garis Ganda Kedinasan (Standar)</option>
                                        <option value="tunggal_tebal">Garis Tunggal Tebal</option>
                                        <option value="modern_aksen">Modern Aksen Hijau</option>
                                        <option value="minimalis">Minimalis Tipis</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Tipografi / Font</label>
                                    <select
                                        v-model="form.template_style"
                                        class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 outline-none"
                                    >
                                        <option value="klasik_formal">Klasik Formal (Times New Roman)</option>
                                        <option value="kedinasan_resmi">Kedinasan Resmi (Arial)</option>
                                        <option value="modern_sppg">Modern SPPG (Segoe UI)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Real-Time Live Preview Kertas A4 (7 Columns, Sticky) -->
            <div class="lg:col-span-7 space-y-3 lg:sticky lg:top-4 self-start">
                <!-- Preview Toolbar Controls -->
                <div class="p-3 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2">
                        <Eye class="w-4 h-4 text-teal-600" />
                        <span class="text-xs font-black uppercase tracking-wider text-slate-800">Pratinjau Kertas A4 Nyata</span>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Zoom Toggle -->
                        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 text-[10px] font-bold">
                            <button
                                type="button"
                                @click="zoomLevel = '75'"
                                class="px-2 py-1 rounded-md transition cursor-pointer"
                                :class="zoomLevel === '75' ? 'bg-white shadow-2xs text-teal-900 font-black' : 'text-slate-500'"
                            >
                                75%
                            </button>
                            <button
                                type="button"
                                @click="zoomLevel = '85'"
                                class="px-2 py-1 rounded-md transition cursor-pointer"
                                :class="zoomLevel === '85' ? 'bg-white shadow-2xs text-teal-900 font-black' : 'text-slate-500'"
                            >
                                85%
                            </button>
                            <button
                                type="button"
                                @click="zoomLevel = '100'"
                                class="px-2 py-1 rounded-md transition cursor-pointer"
                                :class="zoomLevel === '100' ? 'bg-white shadow-2xs text-teal-900 font-black' : 'text-slate-500'"
                            >
                                100%
                            </button>
                        </div>

                        <!-- Orientation Toggle -->
                        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 text-[11px] font-bold">
                            <button
                                type="button"
                                @click="previewOrientation = 'portrait'"
                                class="px-2.5 py-1 rounded-md transition cursor-pointer"
                                :class="previewOrientation === 'portrait' ? 'bg-white shadow-2xs text-teal-900 font-black' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Portrait
                            </button>
                            <button
                                type="button"
                                @click="previewOrientation = 'landscape'"
                                class="px-2.5 py-1 rounded-md transition cursor-pointer"
                                :class="previewOrientation === 'landscape' ? 'bg-white shadow-2xs text-teal-900 font-black' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Landscape
                            </button>
                        </div>

                        <!-- Sample Document Dropdown -->
                        <select
                            v-model="previewDocumentType"
                            class="text-[11px] font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 outline-none cursor-pointer"
                        >
                            <option value="surat">Contoh: Surat Resmi</option>
                            <option value="work_order">Contoh: Work Order</option>
                            <option value="blank">Contoh: Lembar Kosong</option>
                        </select>
                    </div>
                </div>

                <!-- Simulation Desk of Physical A4 Paper -->
                <div class="p-3 sm:p-4 rounded-2xl bg-slate-200/60 border border-slate-300/70 overflow-x-auto flex justify-center shadow-inner">
                    <div
                        class="bg-white rounded-lg shadow-md transition-all duration-200 p-6 sm:px-9 sm:py-7 text-black border border-slate-200 shrink-0"
                        :class="[
                            previewOrientation === 'portrait' ? 'w-[680px] min-h-[820px]' : 'w-[880px] min-h-[580px]',
                            fontClass
                        ]"
                        :style="{
                            zoom: zoomLevel === '100' ? '1' : (zoomLevel === '85' ? '0.85' : '0.75'),
                        }"
                    >
                        <!-- ============================================== -->
                        <!-- KOP SURAT HEADER (RESMI FORMAT KEDINASAN BGN)  -->
                        <!-- ============================================== -->
                        <div class="select-none">
                            <table class="w-full border-collapse border-none">
                                <tr>
                                    <!-- Logo Kiri: BGN Logo Bulat Resmi -->
                                    <td class="w-20 text-center align-middle p-0 border-none shrink-0">
                                        <img
                                            :src="form.logo_kiri_url"
                                            alt="Logo Kiri BGN"
                                            class="h-16 w-16 object-contain mx-auto"
                                            @error="$event.target.src = '/images/logo/BGN_LOGO_MAIN.png'"
                                        />
                                    </td>

                                    <!-- Teks Kop Sesuai Format Kedinasan SPPG (Strictly Single Lines) -->
                                    <td class="text-center align-middle px-3 border-none whitespace-nowrap">
                                        <div class="text-[13.5px] sm:text-[14.5px] font-serif font-bold tracking-wide uppercase text-black leading-tight whitespace-nowrap">
                                            {{ form.nama_instansi_1 || "SPPG BULELENG SUKASADA TEGALLINGGAH" }}
                                        </div>
                                        <div class="text-[12px] sm:text-[13px] font-serif font-bold tracking-wide uppercase text-black leading-tight mt-1 whitespace-nowrap">
                                            {{ form.nama_instansi_2 || "YAYASAN PESANTREN MIFTAHUL ULUM" }}
                                        </div>
                                        <div v-if="showUnitLine" class="text-[11px] sm:text-[12px] font-serif font-bold uppercase text-black leading-tight mt-1 whitespace-nowrap">
                                            {{ form.nama_unit }}
                                        </div>
                                        <div v-if="form.alamat_lengkap" class="text-[9px] sm:text-[10px] font-serif text-black leading-tight mt-1.5 whitespace-nowrap">
                                            {{ form.alamat_lengkap }}
                                        </div>
                                        <div v-if="contactString" class="text-[9px] sm:text-[10px] font-serif text-black leading-tight mt-0.5 whitespace-nowrap">
                                            {{ contactString }}
                                        </div>
                                    </td>

                                    <!-- Logo Kanan: Yayasan Pesantren Miftahul Ulum -->
                                    <td class="w-20 text-center align-middle p-0 border-none shrink-0">
                                        <img
                                            v-if="form.layout_logo === 'dual' && form.logo_kanan_url"
                                            :src="form.logo_kanan_url"
                                            alt="Logo Kanan"
                                            class="h-16 w-16 object-contain mx-auto"
                                            @error="$event.target.src = '/images/logo/Logo_Yayasan.png'"
                                        />
                                        <div v-else class="w-16"></div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Garis Ganda Kedinasan (Top 3px Thick, 2px Gap, Bottom 1px Thin) -->
                            <div class="w-full mt-2 mb-2 select-none">
                                <div class="border-b-[3px] border-black w-full"></div>
                                <div class="border-b border-black w-full mt-[2px]"></div>
                            </div>

                            <!-- Indikator Header Format Microsoft Word -->
                            <div class="relative w-full border-b border-dashed border-slate-300 mb-5 select-none">
                                <span class="absolute -bottom-2.5 left-0 px-1.5 py-0.5 text-[8.5px] text-slate-400 bg-white border border-slate-200 rounded font-sans shadow-2xs">
                                    Header
                                </span>
                            </div>
                        </div>

                        <!-- ============================================== -->
                        <!-- PREVIEW DOCUMENT BODY (SIMULASI ISI DOKUMEN) -->
                        <!-- ============================================== -->
                        <!-- Simulasi 1: Surat / Keputusan Kedinasan -->
                        <div v-if="previewDocumentType === 'surat'" class="space-y-4 pt-2 text-[10px] leading-relaxed text-slate-800">
                            <div class="text-center space-y-0.5">
                                <div class="font-bold underline uppercase tracking-wider text-[11px] text-black">SURAT PERNYATAAN / DOKUMEN RESMI</div>
                                <div class="text-[9.5px] text-slate-600">Nomor: B-048/SPPG.51.08/MBG/IX/2026</div>
                            </div>

                            <div class="space-y-1 text-[9.5px]">
                                <p>Yang bertanda tangan di bawah ini:</p>
                                <table class="w-full ml-2 border-none">
                                    <tr><td class="w-24 border-none py-0.5 text-slate-500">Nama</td><td class="border-none py-0.5 font-bold">: I Putu Hendra, S.Gz</td></tr>
                                    <tr><td class="border-none py-0.5 text-slate-500">Jabatan</td><td class="border-none py-0.5 font-bold">: Kepala Unit SPPG</td></tr>
                                    <tr><td class="border-none py-0.5 text-slate-500">Unit Kerja</td><td class="border-none py-0.5">: {{ form.nama_unit }}</td></tr>
                                </table>
                            </div>

                            <p class="text-justify text-[9px] text-slate-700">
                                Menyatakan dengan sesungguhnya bahwa seluruh proses perencanaan, pengadaan bahan makanan, dan formulasi gizi dalam rangka Program Makan Bergizi Gratis (MBG) telah dilaksanakan sesuai dengan Petunjuk Teknis dan Standar Operasional Prosedur Badan Gizi Nasional (BGN).
                            </p>

                            <div class="p-3 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 text-center text-slate-400 text-[9px]">
                                [ Area Teks Dokumen / Rincian Rekapitulasi MBG ]
                            </div>

                            <div class="flex justify-end pt-4">
                                <div class="text-center w-48 text-[9px] space-y-0.5">
                                    <div>{{ form.kabupaten || 'Buleleng' }}, 21 September 2026</div>
                                    <div class="font-bold">Kepala {{ form.nama_unit }}</div>
                                    <div class="h-10"></div>
                                    <div class="font-bold underline text-[9.5px]">I Putu Hendra, S.Gz</div>
                                    <div class="text-slate-500 text-[8.5px]">NIP. 19880412 201402 1 003</div>
                                </div>
                            </div>
                        </div>

                        <!-- Simulasi 2: Work Order Menu MBG -->
                        <div v-else-if="previewDocumentType === 'work_order'" class="space-y-3 pt-2 text-[9px] text-slate-800">
                            <div class="text-center space-y-0.5">
                                <div class="font-black text-[11px] uppercase tracking-wider text-black">LEMBAR PERENCANAAN PRODUKSI & WORK ORDER (WO)</div>
                                <div class="text-[9px] text-slate-600">Nomor WO: WO-20260921-001 • Menu: Nasi Ayam Suwir Bumbu Kuning</div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 bg-slate-50 p-2 rounded border border-slate-200 text-[8px]">
                                <div><strong>Target Distribusi:</strong> Senin, 21 September 2026</div>
                                <div><strong>Sasaran PM:</strong> 1.500 Porsi (600 PK / 900 PB)</div>
                            </div>

                            <table class="w-full border-collapse text-[8px]">
                                <thead>
                                    <tr class="bg-slate-100 font-bold border-b border-slate-300">
                                        <th class="p-1 text-center border border-slate-200 w-5">No</th>
                                        <th class="p-1 text-left border border-slate-200">Sub Menu</th>
                                        <th class="p-1 text-left border border-slate-200">Bahan Baku</th>
                                        <th class="p-1 text-right border border-slate-200">Kebutuhan</th>
                                        <th class="p-1 text-right border border-slate-200">Food Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="p-1 text-center border border-slate-200">1</td>
                                        <td class="p-1 border border-slate-200">Makanan Pokok</td>
                                        <td class="p-1 border border-slate-200">Beras Putih Premium</td>
                                        <td class="p-1 text-right border border-slate-200">150.00 kg</td>
                                        <td class="p-1 text-right border border-slate-200">Rp 2.250.000</td>
                                    </tr>
                                    <tr>
                                        <td class="p-1 text-center border border-slate-200">2</td>
                                        <td class="p-1 border border-slate-200">Lauk Hewani</td>
                                        <td class="p-1 border border-slate-200">Daging Dada Ayam Fillet</td>
                                        <td class="p-1 text-right border border-slate-200">85.00 kg</td>
                                        <td class="p-1 text-right border border-slate-200">Rp 3.825.000</td>
                                    </tr>
                                    <tr>
                                        <td class="p-1 text-center border border-slate-200">3</td>
                                        <td class="p-1 border border-slate-200">Lauk Nabati</td>
                                        <td class="p-1 border border-slate-200">Tempe Kedelai Murni</td>
                                        <td class="p-1 text-right border border-slate-200">45.00 kg</td>
                                        <td class="p-1 text-right border border-slate-200">Rp 900.000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Simulasi 3: Lembar Kosong Ber-Kop -->
                        <div v-else class="py-16 text-center text-slate-400 text-xs italic">
                            [ Halaman Dokumen Kosong Ber-Kop Siap Digunakan Untuk Template Berkas / Formulir Kedinasan ]
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
