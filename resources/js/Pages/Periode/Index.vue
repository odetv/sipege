<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PeriodeCalendarPicker from "@/Components/PeriodeCalendarPicker.vue";
import {
    CalendarRange,
    Plus,
    Pencil,
    Trash2,
    CheckCircle2,
    Clock,
    CalendarClock,
    X,
    Save,
    Calendar,
    AlertTriangle,
    ChevronRight,
    History,
    CalendarDays,
    Hourglass,
    TrendingUp,
    SlidersHorizontal,
    Eye,
    EyeOff,
    Check,
    HelpCircle,
    Info,
    Sparkles,
} from "lucide-vue-next";

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    periodes: { type: Array,  default: () => [] },
    summary:  { type: Object, default: () => ({}) },
    unitSppg: { type: Object, default: null },
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const bulanId = [
    "", "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
];

function formatTanggal(dateStr) {
    if (!dateStr) return "—";
    const [y, m, d] = dateStr.split("-");
    return `${parseInt(d)} ${bulanId[parseInt(m)]} ${y}`;
}

function formatRange(mulai, selesai) {
    if (!mulai || !selesai) return "—";
    const [ym, mm, dm] = mulai.split("-");
    const [ys, ms, ds] = selesai.split("-");
    if (mm === ms && ym === ys) {
        return `${parseInt(dm)}–${parseInt(ds)} ${bulanId[parseInt(mm)]} ${ym}`;
    }
    return `${parseInt(dm)} ${bulanId[parseInt(mm)]} – ${parseInt(ds)} ${bulanId[parseInt(ms)]} ${ys}`;
}

const statusConfig = {
    aktif:       { label: "Aktif",        bg: "bg-emerald-100", text: "text-emerald-700", dot: "bg-emerald-500" },
    selesai:     { label: "Selesai",      bg: "bg-slate-100",   text: "text-slate-500",   dot: "bg-slate-400"   },
    akan_datang: { label: "Akan Datang",  bg: "bg-blue-100",    text: "text-blue-600",    dot: "bg-blue-400"    },
};

// Format jumlah hari menjadi "X tahun Y bulan Z hari" / "Y bulan Z hari" / "Z hari"
function formatDurasi(totalHari) {
    if (!totalHari || totalHari <= 0) return "(baru dimulai)";
    const tahun  = Math.floor(totalHari / 365);
    const sisa1  = totalHari % 365;
    const bulan  = Math.floor(sisa1 / 30);
    const hari   = sisa1 % 30;

    const bagian = [];
    if (tahun > 0)  bagian.push(`${tahun} tahun`);
    if (bulan > 0)  bagian.push(`${bulan} bulan`);
    if (hari  > 0)  bagian.push(`${hari} hari`);

    return `(${bagian.join(" ") || "0 hari"})`;
}

// ─── Flash ────────────────────────────────────────────────────────────────────
const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// ─── Periode Terakhir & Nomor Berikutnya ───────────────────────────────────────
const latestPeriode = computed(() => {
    if (!props.periodes || !props.periodes.length) return null;
    return [...props.periodes].sort((a, b) => (b.tanggal_selesai || "").localeCompare(a.tanggal_selesai || ""))[0];
});

const nextNomor = computed(() =>
    props.periodes.length
        ? Math.max(...props.periodes.map(p => p.nomor_periode)) + 1
        : 1
);

// ─── Filter Tabs Tabel ────────────────────────────────────────────────────────
const activeTab = ref("semua"); // 'semua' | 'aktif_berjalan' | 'penyesuaian' | 'tampil_list' | 'sembunyi_list'

const filteredPeriodes = computed(() => {
    if (activeTab.value === "aktif_berjalan") {
        return props.periodes.filter(p => !p.is_penyesuaian);
    }
    if (activeTab.value === "penyesuaian") {
        return props.periodes.filter(p => p.is_penyesuaian);
    }
    if (activeTab.value === "tampil_list") {
        return props.periodes.filter(p => p.tampilkan_di_list);
    }
    if (activeTab.value === "sembunyi_list") {
        return props.periodes.filter(p => !p.tampilkan_di_list);
    }
    return props.periodes;
});

// ─── Modal Penyesuaian Periode Awal / Lampau ─────────────────────────────────
const showPenyesuaian = ref(false);
const formPenyesuaian = useForm({
    nomor_periode: 1,
    tanggal_mulai: "",
    tanggal_selesai: "",
    tampilkan_di_list: true,
    keterangan: "",
});

function openPenyesuaian() {
    formPenyesuaian.clearErrors();
    const existingPenyesuaian = props.periodes.find(p => p.is_penyesuaian);
    if (existingPenyesuaian) {
        formPenyesuaian.nomor_periode = existingPenyesuaian.nomor_periode;
        formPenyesuaian.tanggal_mulai = existingPenyesuaian.tanggal_mulai;
        formPenyesuaian.tanggal_selesai = existingPenyesuaian.tanggal_selesai;
        formPenyesuaian.tampilkan_di_list = Boolean(existingPenyesuaian.tampilkan_di_list);
        formPenyesuaian.keterangan = existingPenyesuaian.keterangan || "";
    } else {
        const defaultStart = props.summary.tgl_awal_program || props.unitSppg?.tanggal_operasional || "2026-05-04";
        formPenyesuaian.nomor_periode = 1;
        formPenyesuaian.tanggal_mulai = defaultStart;
        try {
            const d = new Date(defaultStart);
            d.setDate(d.getDate() + 13);
            formPenyesuaian.tanggal_selesai = d.toISOString().split("T")[0];
        } catch {
            formPenyesuaian.tanggal_selesai = defaultStart;
        }
        formPenyesuaian.tampilkan_di_list = true;
        formPenyesuaian.keterangan = "Penyesuaian periode awal sebelum penggunaan aplikasi";
    }
    showPenyesuaian.value = true;
}

function onTglMulaiChange() {
    if (formPenyesuaian.tanggal_mulai && (!formPenyesuaian.tanggal_selesai || formPenyesuaian.tanggal_selesai < formPenyesuaian.tanggal_mulai)) {
        try {
            const d = new Date(formPenyesuaian.tanggal_mulai);
            d.setDate(d.getDate() + 13);
            formPenyesuaian.tanggal_selesai = d.toISOString().split("T")[0];
        } catch {}
    }
}

function setDurasiPenyesuaian(days) {
    if (!formPenyesuaian.tanggal_mulai) return;
    try {
        const d = new Date(formPenyesuaian.tanggal_mulai);
        d.setDate(d.getDate() + (days - 1));
        formPenyesuaian.tanggal_selesai = d.toISOString().split("T")[0];
    } catch {}
}

const durasiPenyesuaianHari = computed(() => {
    if (!formPenyesuaian.tanggal_mulai || !formPenyesuaian.tanggal_selesai) return 0;
    try {
        const start = new Date(formPenyesuaian.tanggal_mulai);
        const end = new Date(formPenyesuaian.tanggal_selesai);
        const diff = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
        return diff > 0 ? diff : 0;
    } catch {
        return 0;
    }
});

function submitPenyesuaian() {
    formPenyesuaian.post(route("periode.penyesuaian"), {
        preserveScroll: true,
        onSuccess: () => {
            showPenyesuaian.value = false;
        },
    });
}

// ─── Toggle Visibilitas Periode di List ───────────────────────────────────────
function toggleVisibility(p) {
    router.patch(route("periode.toggle-visibility", p.id), {}, {
        preserveScroll: true,
    });
}

// ─── Modal Tambah ─────────────────────────────────────────────────────────────
const showTambah = ref(false);
const formTambah = useForm({
    tanggal_mulai:   "",
    tanggal_selesai: "",
    is_penyesuaian:  false,
    tampilkan_di_list: true,
    keterangan:      "",
});
const calendarRangeTambah = ref({ start: "", end: "" });

function openTambah() {
    formTambah.reset();
    formTambah.clearErrors();
    formTambah.is_penyesuaian = false;
    formTambah.tampilkan_di_list = true;
    calendarRangeTambah.value = { start: "", end: "" };
    showTambah.value = true;
}

onMounted(() => {
    if (typeof window !== "undefined") {
        const params = new URLSearchParams(window.location.search);
        if (params.get("action") === "tambah") {
            openTambah();
        } else if (params.get("action") === "penyesuaian") {
            openPenyesuaian();
        }
    }
});

function onRangeTambahChange(val) {
    formTambah.tanggal_mulai = val.start;
    formTambah.tanggal_selesai = val.end;
}

function submitTambah() {
    formTambah.post(route("periode.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showTambah.value = false;
            formTambah.reset();
        },
    });
}

// ─── Modal Edit ───────────────────────────────────────────────────────────────
const editTarget = ref(null);
const formEdit = useForm({
    tanggal_mulai:   "",
    tanggal_selesai: "",
    is_penyesuaian:  false,
    tampilkan_di_list: true,
    keterangan:      "",
});
const calendarRangeEdit = ref({ start: "", end: "" });

function openEdit(p) {
    editTarget.value = p;
    formEdit.clearErrors();
    formEdit.tanggal_mulai = p.tanggal_mulai;
    formEdit.tanggal_selesai = p.tanggal_selesai;
    formEdit.is_penyesuaian = Boolean(p.is_penyesuaian);
    formEdit.tampilkan_di_list = Boolean(p.tampilkan_di_list);
    formEdit.keterangan = p.keterangan || "";
    calendarRangeEdit.value = {
        start: p.tanggal_mulai,
        end: p.tanggal_selesai,
    };
}

function onRangeEditChange(val) {
    formEdit.tanggal_mulai = val.start;
    formEdit.tanggal_selesai = val.end;
}

function submitEdit() {
    formEdit.put(route("periode.update", editTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editTarget.value = null;
        },
    });
}

// ─── Hapus ────────────────────────────────────────────────────────────────────
const deleteTarget = ref(null);
const isDeleting = ref(false);

function confirmDelete(p) {
    deleteTarget.value = p;
}

function doDelete() {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(route("periode.destroy", deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <AppLayout title="Periode" subtitle="Kelola Periode Operasional SPPG">
        <Head title="Periode - SIPEGE" />

        <!-- Flash -->
        <div
            v-if="flash.success"
            class="mb-5 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 animate-in fade-in duration-150"
        >
            <CheckCircle2 class="h-4 w-4 shrink-0" />
            <span>{{ flash.success }}</span>
        </div>

        <!-- ═══════════════════ SUMMARY CARDS ═══════════════════ -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Card 1: Periode Berlalu -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xs p-4 sm:p-5 flex items-start gap-3 sm:gap-4 transition-colors">
                <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                    <History class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Periode Berlalu</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-none">
                        {{ summary.periode_berlalu ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">periode sudah selesai</p>
                </div>
            </div>

            <!-- Card 2: Tgl Awal Program -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xs p-4 sm:p-5 flex items-start gap-3 sm:gap-4 transition-colors">
                <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <CalendarDays class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Awal Program</p>
                    <p class="text-sm sm:text-base font-extrabold text-slate-900 leading-snug">
                        {{ summary.tgl_awal_program ? formatTanggal(summary.tgl_awal_program) : '—' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">mulai operasional SPPG</p>
                </div>
            </div>

            <!-- Card 3: Periode Aktif -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xs p-4 sm:p-5 flex items-start gap-3 sm:gap-4 transition-colors">
                <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <CalendarRange class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Periode Aktif</p>
                    <p class="text-sm sm:text-base font-extrabold text-slate-900 leading-snug">
                        {{ summary.periode_aktif ?? '—' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">sedang berjalan</p>
                </div>
            </div>

            <!-- Card 4: Umur Operasional -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xs p-4 sm:p-5 flex items-start gap-3 sm:gap-4 transition-colors">
                <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center shrink-0">
                    <Hourglass class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Hari Berjalan</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-none">
                        {{ summary.hari_sejak_awal ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ formatDurasi(summary.hari_sejak_awal) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- ═══════════════════ BANNER STATUS PENYESUAIAN ═══════════════════ -->
        <div
            v-if="summary.total_penyesuaian > 0"
            class="mb-6 p-4 rounded-2xl bg-gradient-to-r from-amber-50/90 via-amber-50/50 to-white border border-amber-200 text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs"
        >
            <div class="flex items-start gap-3">
                <div class="h-8 w-8 rounded-xl bg-amber-500/15 border border-amber-300 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                    <History class="h-4 w-4" />
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-amber-950 text-sm">
                            Fitur Penyesuaian Periode Awal Aktif ({{ summary.total_penyesuaian }} Periode)
                        </span>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10.5px] font-black border"
                            :class="summary.lampau_tampil_count > 0 ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-slate-100 text-slate-700 border-slate-300'"
                        >
                            {{ summary.lampau_tampil_count > 0 ? '✓ Dimunculkan di List Dropdown' : '✗ Disembunyikan dari Dropdown' }}
                        </span>
                    </div>
                    <p class="text-slate-600 mt-1 text-[11.5px] leading-relaxed">
                        Terdapat <strong>{{ summary.total_penyesuaian }} periode penyesuaian</strong> yang tercatat di sistem agar urutan periode dan hari operasional tetap selaras dengan pembukuan fisik SPPG. Periode yang dibuat normal tetap berjalan terpisah secara normal.
                    </p>
                </div>
            </div>
            <button
                type="button"
                @click="openPenyesuaian"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-amber-100/60 active:bg-amber-200 text-amber-900 border border-amber-300 font-bold text-xs shadow-2xs transition-colors self-start sm:self-auto cursor-pointer shrink-0"
            >
                <SlidersHorizontal class="h-3.5 w-3.5 text-amber-700" />
                <span>Ubah Pengaturan</span>
            </button>
        </div>

        <!-- ═══════════════════ HEADER TABEL & AKSI ═══════════════════ -->
        <div class="mb-5 flex flex-col items-start sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <CalendarRange class="h-5 w-5 text-primary" />
                    Daftar Periode
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Status otomatis berdasarkan tanggal hari ini. Nomor periode terisi berurutan secara otomatis.
                </p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Tombol Penyesuaian Periode Lampau -->
                <button
                    type="button"
                    @click="openPenyesuaian"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-50 hover:bg-amber-100 active:bg-amber-200 text-amber-800 border border-amber-300 px-3.5 py-2.5 text-xs sm:text-sm font-bold shadow-2xs transition-colors cursor-pointer"
                    title="Atur penyesuaian periode yang sudah berjalan sebelum aplikasi digunakan"
                >
                    <SlidersHorizontal class="h-4 w-4 text-amber-600" />
                    <span>Penyesuaian Periode</span>
                </button>

                <!-- Tombol Tambah Periode -->
                <button
                    type="button"
                    @click="openTambah"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-primary/30 hover:bg-primary/90 transition-colors shrink-0 cursor-pointer"
                >
                    <Plus class="h-4 w-4" />
                    Tambah Periode {{ nextNomor }}
                </button>
            </div>
        </div>

        <!-- Filter Tab Kategori Periode -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-2 mb-3 text-xs font-semibold">
            <button
                type="button"
                @click="activeTab = 'semua'"
                class="px-3 py-1.5 rounded-xl transition-colors cursor-pointer flex items-center gap-1.5 shrink-0"
                :class="activeTab === 'semua' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
                <span>Semua Periode</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold" :class="activeTab === 'semua' ? 'bg-white/20' : 'bg-slate-200'">{{ periodes.length }}</span>
            </button>
            <button
                type="button"
                @click="activeTab = 'aktif_berjalan'"
                class="px-3 py-1.5 rounded-xl transition-colors cursor-pointer flex items-center gap-1.5 shrink-0"
                :class="activeTab === 'aktif_berjalan' ? 'bg-primary text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
                <span>Periode Aplikasi</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold" :class="activeTab === 'aktif_berjalan' ? 'bg-white/20' : 'bg-slate-200'">{{ periodes.filter(p => !p.is_penyesuaian).length }}</span>
            </button>
            <button
                v-if="summary.total_penyesuaian > 0"
                type="button"
                @click="activeTab = 'penyesuaian'"
                class="px-3 py-1.5 rounded-xl transition-colors cursor-pointer flex items-center gap-1.5 shrink-0"
                :class="activeTab === 'penyesuaian' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
                <span>Penyesuaian Lampau</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold" :class="activeTab === 'penyesuaian' ? 'bg-white/20' : 'bg-slate-200'">{{ summary.total_penyesuaian }}</span>
            </button>
            <button
                type="button"
                @click="activeTab = 'tampil_list'"
                class="px-3 py-1.5 rounded-xl transition-colors cursor-pointer flex items-center gap-1.5 shrink-0"
                :class="activeTab === 'tampil_list' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
                <Eye class="h-3.5 w-3.5" />
                <span>Muncul di List</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold" :class="activeTab === 'tampil_list' ? 'bg-white/20' : 'bg-slate-200'">{{ summary.total_tampil_list }}</span>
            </button>
            <button
                v-if="periodes.length > summary.total_tampil_list"
                type="button"
                @click="activeTab = 'sembunyi_list'"
                class="px-3 py-1.5 rounded-xl transition-colors cursor-pointer flex items-center gap-1.5 shrink-0"
                :class="activeTab === 'sembunyi_list' ? 'bg-slate-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
                <EyeOff class="h-3.5 w-3.5" />
                <span>Disembunyikan</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold" :class="activeTab === 'sembunyi_list' ? 'bg-white/20' : 'bg-slate-200'">{{ periodes.length - summary.total_tampil_list }}</span>
            </button>
        </div>

        <!-- ═══════════════════ TABEL ═══════════════════ -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 w-10">No</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Periode</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal Mulai</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal Selesai</th>
                        <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">List Dropdown</th>
                        <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!filteredPeriodes.length">
                        <td colspan="7" class="py-16 text-center text-slate-400">
                            <Calendar class="h-10 w-10 mx-auto mb-2 opacity-30" />
                            Belum ada data periode yang sesuai filter.
                        </td>
                    </tr>
                    <tr
                        v-for="(p, idx) in filteredPeriodes"
                        :key="p.id"
                        :class="[
                            'transition-colors hover:bg-slate-50/60',
                            p.status === 'aktif' ? 'bg-emerald-50/30' : '',
                            p.is_penyesuaian ? 'bg-amber-50/15' : '',
                        ]"
                    >
                        <td class="px-5 py-4 text-xs text-slate-400">{{ idx + 1 }}</td>

                        <!-- Periode + Rentang + Badge Penyesuaian -->
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900">Periode {{ p.nomor_periode }}</span>
                                <span
                                    v-if="p.is_penyesuaian"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                    title="Periode lampau sebelum aplikasi digunakan"
                                >
                                    <History class="h-3 w-3 text-amber-600" />
                                    Penyesuaian Lampau
                                </span>
                            </div>
                            <div class="flex items-center gap-1 mt-0.5 text-xs text-slate-500">
                                <ChevronRight class="h-3 w-3 shrink-0" />
                                {{ formatRange(p.tanggal_mulai, p.tanggal_selesai) }}
                            </div>
                            <div v-if="p.keterangan" class="text-[10.5px] text-slate-400 italic mt-0.5 truncate max-w-xs">
                                {{ p.keterangan }}
                            </div>
                        </td>

                        <!-- Tanggal Mulai -->
                        <td class="px-5 py-4 text-slate-700">{{ formatTanggal(p.tanggal_mulai) }}</td>

                        <!-- Tanggal Selesai -->
                        <td class="px-5 py-4 text-slate-700">{{ formatTanggal(p.tanggal_selesai) }}</td>

                        <!-- Status (read-only, otomatis) -->
                        <td class="px-5 py-4 text-center">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                                :class="[statusConfig[p.status]?.bg, statusConfig[p.status]?.text]"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full shrink-0"
                                    :class="[
                                        statusConfig[p.status]?.dot,
                                        p.status === 'aktif' ? 'animate-pulse' : '',
                                    ]"
                                />
                                {{ statusConfig[p.status]?.label }}
                            </span>
                        </td>

                        <!-- Status Visibilitas di List / Dropdown Aplikasi (Klik untuk Toggle Cepat) -->
                        <td class="px-5 py-4 text-center">
                            <button
                                type="button"
                                @click="toggleVisibility(p)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition-all cursor-pointer shadow-2xs border"
                                :class="p.tampilkan_di_list ? 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border-emerald-300' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 border-slate-300'"
                                :title="p.tampilkan_di_list ? 'Klik untuk sembunyikan dari dropdown list periode' : 'Klik untuk tampilkan di dropdown list periode'"
                            >
                                <Eye v-if="p.tampilkan_di_list" class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                <EyeOff v-else class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                <span>{{ p.tampilkan_di_list ? 'Tampil di List' : 'Disembunyikan' }}</span>
                            </button>
                        </td>

                        <!-- Aksi -->
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-1.5">
                                <button
                                    @click="openEdit(p)"
                                    class="h-8 w-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                    title="Edit Tanggal & Status Periode"
                                >
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button
                                    @click="confirmDelete(p)"
                                    class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                    title="Hapus Periode"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

        <!-- ═══════════════════ MODAL PENYESUAIAN PERIODE LAMPAU ═══════════════════ -->
        <Teleport to="body">
            <div v-if="showPenyesuaian" class="fixed inset-0 z-[99999] flex items-center justify-center p-3 sm:p-4">
                <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="showPenyesuaian = false" />
                <div class="relative w-full max-w-2xl lg:max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 sm:px-7 py-5 border-b border-slate-100 bg-gradient-to-r from-amber-50/90 via-slate-50 to-white shrink-0 gap-4">
                        <div class="flex items-center gap-3.5 min-w-0 flex-1 pr-3">
                            <div class="h-11 w-11 rounded-2xl bg-amber-500/15 border border-amber-300 text-amber-700 flex items-center justify-center shadow-2xs shrink-0">
                                <SlidersHorizontal class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-extrabold text-slate-900 text-base sm:text-lg leading-tight">Penyesuaian Periode Awal</h3>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    Tentukan urutan periode dan rentang tanggal untuk periode awal operasional SPPG
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showPenyesuaian = false"
                            class="h-9 w-9 rounded-xl bg-slate-100/80 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer shrink-0"
                            title="Tutup"
                        >
                            <X class="h-4.5 w-4.5" />
                        </button>
                    </div>

                    <!-- Body Modal -->
                    <div class="overflow-y-auto px-6 sm:px-7 py-6 space-y-5 text-slate-800 text-xs sm:text-sm">
                        <!-- Panduan Singkat -->
                        <div class="p-3.5 rounded-2xl bg-blue-50/80 border border-blue-200 text-blue-900 flex items-start gap-3">
                            <Info class="h-5 w-5 text-blue-600 shrink-0 mt-0.5" />
                            <div class="space-y-1 text-xs sm:text-[12.5px] leading-relaxed">
                                <p>
                                    Fitur ini digunakan jika SPPG Anda telah aktif berjalan beberapa periode sebelum memakai SIPEGE. Anda dapat menentukan urutan periode ke berapa untuk diawal serta rentang tanggalnya.
                                </p>
                                <p class="text-[11.5px] text-blue-700 font-medium">
                                    Periode yang dibuat melalui tombol biasa tetap berstatus normal dan tidak terpengaruh oleh penyesuaian ini.
                                </p>
                            </div>
                        </div>

                        <!-- 1. Urutan Periode ke Berapa untuk di Awal -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-800 text-xs sm:text-sm">
                                Urutan Periode Awal (Periode Ke-) <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center gap-2.5 max-w-xs">
                                <span class="text-xs sm:text-sm font-bold text-slate-600 shrink-0">Periode</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="999"
                                    v-model="formPenyesuaian.nomor_periode"
                                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-black text-slate-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 shadow-2xs"
                                    placeholder="Contoh: 1"
                                    required
                                />
                            </div>
                            <p class="text-[11px] text-slate-400">
                                Tentukan nomor urutan periode awal yang disesuaikan.
                            </p>
                        </div>

                        <!-- 2. Rentang Tanggal dari Tanggal Berapa - Berapa untuk di Awal -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <label class="block font-bold text-slate-800 text-xs sm:text-sm">
                                    Rentang Tanggal Periode Awal <span class="text-rose-500">*</span>
                                </label>
                                <!-- Quick Presets -->
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="setDurasiPenyesuaian(14)"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-100/80 hover:bg-amber-200 text-amber-800 border border-amber-300 transition-colors cursor-pointer"
                                        title="Set rentang 14 hari (standar 2 minggu)"
                                    >
                                        + 14 Hari (2 Minggu)
                                    </button>
                                    <button
                                        type="button"
                                        @click="setDurasiPenyesuaian(7)"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition-colors cursor-pointer"
                                        title="Set rentang 7 hari (1 minggu)"
                                    >
                                        + 7 Hari
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- Tanggal Mulai -->
                                <div class="space-y-1">
                                    <span class="block text-xs font-semibold text-slate-600">
                                        Tanggal Mulai <span class="text-rose-500">*</span>
                                    </span>
                                    <input
                                        type="date"
                                        v-model="formPenyesuaian.tanggal_mulai"
                                        @change="onTglMulaiChange"
                                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 shadow-2xs"
                                        required
                                    />
                                </div>

                                <!-- Tanggal Selesai -->
                                <div class="space-y-1">
                                    <span class="block text-xs font-semibold text-slate-600">
                                        Tanggal Selesai <span class="text-rose-500">*</span>
                                    </span>
                                    <input
                                        type="date"
                                        v-model="formPenyesuaian.tanggal_selesai"
                                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 shadow-2xs"
                                        required
                                    />
                                </div>
                            </div>

                            <!-- Info Durasi Terpilih -->
                            <div v-if="durasiPenyesuaianHari > 0" class="flex items-center gap-1.5 text-xs text-amber-800 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200/80 w-fit">
                                <CalendarDays class="h-3.5 w-3.5 text-amber-600" />
                                <span>Durasi: <strong>{{ durasiPenyesuaianHari }} hari</strong> operasional ({{ formatRange(formPenyesuaian.tanggal_mulai, formPenyesuaian.tanggal_selesai) }})</span>
                            </div>
                        </div>

                        <!-- 3. Pilihan Tampilan Periode Ini di List / Dropdown -->
                        <div class="space-y-2 pt-1">
                            <label class="block font-bold text-slate-800 text-xs sm:text-sm">
                                Pilihan Tampilan Periode Ini di List / Dropdown Pilihan: <span class="text-rose-500">*</span>
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Opsi 1: Tampilkan di List -->
                                <label
                                    class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all"
                                    :class="formPenyesuaian.tampilkan_di_list ? 'bg-emerald-50/80 border-emerald-400 ring-2 ring-emerald-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'"
                                >
                                    <input
                                        type="radio"
                                        :value="true"
                                        v-model="formPenyesuaian.tampilkan_di_list"
                                        class="mt-1 text-emerald-600 focus:ring-emerald-500 shrink-0"
                                    />
                                    <div>
                                        <p class="font-bold text-slate-900 text-xs sm:text-sm flex items-center gap-1.5">
                                            <Eye class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                            <span>Tampilkan di List Periode</span>
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                            Periode penyesuaian ini akan muncul di daftar pilihan (dropdown) seluruh aplikasi (Work Order, Rekap Distribusi, Kas & Bank, Laporan, dll.).
                                        </p>
                                    </div>
                                </label>

                                <!-- Opsi 2: Sembunyikan dari Dropdown -->
                                <label
                                    class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all"
                                    :class="!formPenyesuaian.tampilkan_di_list ? 'bg-amber-50/80 border-amber-400 ring-2 ring-amber-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'"
                                >
                                    <input
                                        type="radio"
                                        :value="false"
                                        v-model="formPenyesuaian.tampilkan_di_list"
                                        class="mt-1 text-amber-600 focus:ring-amber-500 shrink-0"
                                    />
                                    <div>
                                        <p class="font-bold text-slate-900 text-xs sm:text-sm flex items-center gap-1.5">
                                            <EyeOff class="h-3.5 w-3.5 text-amber-600 shrink-0" />
                                            <span>Sembunyikan dari Dropdown</span>
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                            Periode penyesuaian ini disembunyikan dari pilihan dropdown harian agar tetap ringkas, namun nomor urut & riwayatnya tetap tercatat.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 4. Keterangan / Catatan Penyesuaian (Opsional) -->
                        <div class="space-y-1.5 pt-1">
                            <label class="block font-bold text-slate-800 text-xs sm:text-sm">
                                Keterangan / Catatan Penyesuaian <span class="text-slate-400 font-normal">(Opsional)</span>
                            </label>
                            <input
                                type="text"
                                v-model="formPenyesuaian.keterangan"
                                placeholder="Contoh: Penyesuaian periode awal sebelum penggunaan aplikasi"
                                class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 shadow-2xs"
                            />
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="border-t border-slate-100 bg-slate-50/80 px-6 py-4 flex items-center justify-between gap-3 shrink-0">
                        <button
                            type="button"
                            @click="showPenyesuaian = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 transition shadow-2xs cursor-pointer"
                            :disabled="formPenyesuaian.processing"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitPenyesuaian"
                            :disabled="formPenyesuaian.processing || !formPenyesuaian.nomor_periode || !formPenyesuaian.tanggal_mulai || !formPenyesuaian.tanggal_selesai"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 hover:bg-amber-700 active:bg-amber-800 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm shadow-amber-600/30 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
                        >
                            <Save class="h-4 w-4" />
                            <span>{{ formPenyesuaian.processing ? 'Menyimpan...' : 'Simpan & Terapkan Penyesuaian' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════ MODAL TAMBAH ═══════════════════ -->
        <Teleport to="body">
            <div v-if="showTambah" class="fixed inset-0 z-[99999] flex items-center justify-center p-3 sm:p-4">
                <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="showTambah = false" />
                <div class="relative w-full max-w-2xl lg:max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                <Plus class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Tambah Periode {{ nextNomor }}</h3>
                                <p class="text-xs text-slate-500">Pilih rentang tanggal atau gunakan opsi cepat per 2 minggu</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showTambah = false"
                            class="rounded-lg p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="overflow-y-auto px-6 py-5 space-y-4">
                        <!-- Top Info Badge -->
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 border border-slate-200/80 px-4 py-2.5">
                            <div class="flex items-center gap-3">
                                <div class="h-7 w-7 rounded-lg bg-primary text-white text-xs font-black flex items-center justify-center shrink-0">
                                    {{ nextNomor }}
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-400 font-medium">Nomor Periode: </span>
                                    <span class="text-xs font-bold text-slate-900">Periode {{ nextNomor }}</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-500 bg-slate-200/80 rounded px-2 py-0.5">Otomatis Terurut</span>
                        </div>

                        <!-- Backend Error Alert if any -->
                        <div
                            v-if="formTambah.errors.tanggal_mulai || formTambah.errors.tanggal_selesai"
                            class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700"
                        >
                            <AlertTriangle class="h-4 w-4 text-rose-500 shrink-0 mt-0.5" />
                            <div>
                                <p class="font-semibold">Penyimpanan Gagal</p>
                                <p>{{ formTambah.errors.tanggal_mulai || formTambah.errors.tanggal_selesai }}</p>
                            </div>
                        </div>

                        <!-- Interactive Calendar Picker -->
                        <PeriodeCalendarPicker
                            v-model="calendarRangeTambah"
                            :existing-periodes="periodes"
                            :latest-periode="latestPeriode"
                            @change="onRangeTambahChange"
                        />

                        <!-- Opsi Visibilitas di List -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                            <div>
                                <p class="font-bold text-xs text-slate-800">Munculkan di List Pilihan Periode</p>
                                <p class="text-[11px] text-slate-500">Tampilkan periode ini di dropdown Work Order, Distribusi, dan fitur lainnya.</p>
                            </div>
                            <input
                                type="checkbox"
                                v-model="formTambah.tampilkan_di_list"
                                class="rounded text-primary focus:ring-primary h-4 w-4"
                            />
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="border-t border-slate-100 bg-slate-50/80 px-6 py-4 flex items-center justify-between gap-3 shrink-0">
                        <button
                            type="button"
                            @click="showTambah = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 transition shadow-2xs cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitTambah"
                            :disabled="formTambah.processing || !formTambah.tanggal_mulai || !formTambah.tanggal_selesai"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-primary/30 hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
                        >
                            <Save class="h-4 w-4" />
                            Simpan Periode Baru
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════ MODAL EDIT ═══════════════════ -->
        <Teleport to="body">
            <div v-if="editTarget" class="fixed inset-0 z-[99999] flex items-center justify-center p-3 sm:p-4">
                <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="editTarget = null" />
                <div class="relative w-full max-w-2xl lg:max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <Pencil class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Edit Periode {{ editTarget?.nomor_periode }}</h3>
                                <p class="text-xs text-slate-500">Ubah rentang tanggal & status visibilitas periode operasional</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="editTarget = null"
                            class="rounded-lg p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="overflow-y-auto px-6 py-5 space-y-4">
                        <!-- Top Info Badge -->
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 border border-slate-200/80 px-4 py-2.5">
                            <div class="flex items-center gap-3">
                                <div class="h-7 w-7 rounded-lg bg-amber-500 text-white text-xs font-black flex items-center justify-center shrink-0">
                                    {{ editTarget?.nomor_periode }}
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-400 font-medium">Nomor Periode: </span>
                                    <span class="text-xs font-bold text-slate-900">Periode {{ editTarget?.nomor_periode }}</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-500 bg-slate-200/80 rounded px-2 py-0.5">Terkunci</span>
                        </div>

                        <!-- Backend Error Alert if any -->
                        <div
                            v-if="formEdit.errors.tanggal_mulai || formEdit.errors.tanggal_selesai"
                            class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700"
                        >
                            <AlertTriangle class="h-4 w-4 text-rose-500 shrink-0 mt-0.5" />
                            <div>
                                <p class="font-semibold">Perubahan Gagal</p>
                                <p>{{ formEdit.errors.tanggal_mulai || formEdit.errors.tanggal_selesai }}</p>
                            </div>
                        </div>

                        <!-- Interactive Calendar Picker -->
                        <PeriodeCalendarPicker
                            v-model="calendarRangeEdit"
                            :existing-periodes="periodes"
                            :current-periode-id="editTarget?.id"
                            :latest-periode="latestPeriode"
                            @change="onRangeEditChange"
                        />

                        <!-- Opsi Visibilitas di List -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                            <div>
                                <p class="font-bold text-xs text-slate-800">Munculkan di List Pilihan Periode</p>
                                <p class="text-[11px] text-slate-500">Tampilkan periode ini di dropdown Work Order, Distribusi, dan fitur lainnya.</p>
                            </div>
                            <input
                                type="checkbox"
                                v-model="formEdit.tampilkan_di_list"
                                class="rounded text-primary focus:ring-primary h-4 w-4"
                            />
                        </div>

                        <!-- Opsi Tandai Sebagai Periode Lampau -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                            <div>
                                <p class="font-bold text-xs text-slate-800">Tandai Sebagai Penyesuaian Lampau</p>
                                <p class="text-[11px] text-slate-500">Centang jika periode ini adalah riwayat sebelum aplikasi digunakan.</p>
                            </div>
                            <input
                                type="checkbox"
                                v-model="formEdit.is_penyesuaian"
                                class="rounded text-amber-600 focus:ring-amber-500 h-4 w-4"
                            />
                        </div>

                        <!-- Catatan / Keterangan Tambahan -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Catatan / Keterangan Periode (Opsional)</label>
                            <input
                                type="text"
                                v-model="formEdit.keterangan"
                                placeholder="Contoh: Periode operasional offline sebelum sistem"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-200"
                            />
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="border-t border-slate-100 bg-slate-50/80 px-6 py-4 flex items-center justify-between gap-3 shrink-0">
                        <button
                            type="button"
                            @click="editTarget = null"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 transition shadow-2xs cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitEdit"
                            :disabled="formEdit.processing || !formEdit.tanggal_mulai || !formEdit.tanggal_selesai"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 px-5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-amber-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
                        >
                            <Save class="h-4 w-4" />
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════ MODAL HAPUS ═══════════════════ -->
        <Teleport to="body">
            <div v-if="deleteTarget" class="fixed inset-0 z-[99999] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="deleteTarget = null" />
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="p-6 text-center">
                        <div class="h-12 w-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-100">
                            <AlertTriangle class="h-6 w-6" />
                        </div>
                        <h3 class="font-bold text-slate-900 text-base mb-1">
                            Hapus Periode {{ deleteTarget?.nomor_periode }}?
                        </h3>
                        <p class="text-xs text-slate-500 mb-2">
                            Rentang: {{ formatRange(deleteTarget?.tanggal_mulai, deleteTarget?.tanggal_selesai) }}
                        </p>
                        <div class="bg-rose-50 border border-rose-100 rounded-xl p-2.5 mb-5 text-[11px] text-rose-600 font-medium leading-relaxed">
                            Data periode ini akan dihapus secara permanen dari sistem.
                        </div>
                        <div class="flex gap-2.5">
                            <button
                                type="button"
                                @click="deleteTarget = null"
                                :disabled="isDeleting"
                                class="flex-1 rounded-xl border border-slate-200 bg-white py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition shadow-2xs cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="doDelete"
                                :disabled="isDeleting"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-600 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 shadow-sm shadow-rose-600/30 disabled:opacity-60 transition cursor-pointer"
                            >
                                <Trash2 v-if="!isDeleting" class="h-4 w-4" />
                                <span v-if="isDeleting">Menghapus...</span>
                                <span v-else>Ya, Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
