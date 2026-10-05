<script setup>
import { ref, computed } from "vue";
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
} from "lucide-vue-next";

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    periodes: { type: Array,  default: () => [] },
    summary:  { type: Object, default: () => ({}) },
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

// ─── Modal Tambah ─────────────────────────────────────────────────────────────
const showTambah = ref(false);
const formTambah = useForm({
    tanggal_mulai:   "",
    tanggal_selesai: "",
});
const calendarRangeTambah = ref({ start: "", end: "" });

function openTambah() {
    formTambah.reset();
    formTambah.clearErrors();
    calendarRangeTambah.value = { start: "", end: "" };
    showTambah.value = true;
}

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
});
const calendarRangeEdit = ref({ start: "", end: "" });

function openEdit(p) {
    editTarget.value = p;
    formEdit.clearErrors();
    formEdit.tanggal_mulai = p.tanggal_mulai;
    formEdit.tanggal_selesai = p.tanggal_selesai;
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
        <Head title="Periode" />

        <!-- Flash -->
        <div
            v-if="flash.success"
            class="mb-5 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            <CheckCircle2 class="h-4 w-4 shrink-0" />
            {{ flash.success }}
        </div>

        <!-- ═══════════════════ SUMMARY CARDS ═══════════════════ -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

            <!-- Card 1: Periode Berlalu -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5 flex items-start gap-4">
                <div class="h-11 w-11 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                    <History class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Periode Berlalu</p>
                    <p class="text-3xl font-extrabold text-slate-900 leading-none">
                        {{ summary.periode_berlalu ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">periode sudah selesai</p>
                </div>
            </div>

            <!-- Card 2: Tgl Awal Program -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5 flex items-start gap-4">
                <div class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <CalendarDays class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Awal Program</p>
                    <p class="text-sm font-extrabold text-slate-900 leading-snug">
                        {{ summary.tgl_awal_program ? formatTanggal(summary.tgl_awal_program) : '—' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">tanggal mulai periode 1</p>
                </div>
            </div>

            <!-- Card 3: Periode Aktif -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5 flex items-start gap-4">
                <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <CalendarRange class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Periode Aktif</p>
                    <p class="text-sm font-extrabold text-slate-900 leading-snug">
                        {{ summary.periode_aktif ?? '—' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">sedang berjalan</p>
                </div>
            </div>

            <!-- Card 4: Umur Operasional -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5 flex items-start gap-4">
                <div class="h-11 w-11 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center shrink-0">
                    <Hourglass class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Hari Berjalan</p>
                    <p class="text-3xl font-extrabold text-slate-900 leading-none">
                        {{ summary.hari_sejak_awal ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ formatDurasi(summary.hari_sejak_awal) }} sejak operasional dimulai
                    </p>
                </div>
            </div>
        </div>

        <!-- ═══════════════════ HEADER TABEL ═══════════════════ -->
        <div class="mb-5 flex flex-col items-start sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <CalendarRange class="h-4.5 w-4.5 text-primary" />
                    Daftar Periode
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Status otomatis berdasarkan tanggal hari ini. Nomor periode terisi berurutan secara otomatis.
                </p>
            </div>
            <button
                @click="openTambah"
                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/30 hover:bg-primary/90 transition-colors shrink-0 cursor-pointer"
            >
                <Plus class="h-4 w-4" />
                Tambah Periode {{ nextNomor }}
            </button>
        </div>

        <!-- ═══════════════════ TABEL ═══════════════════ -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 w-10">No</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Periode</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal Mulai</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal Selesai</th>
                        <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!periodes.length">
                        <td colspan="6" class="py-16 text-center text-slate-400">
                            <Calendar class="h-10 w-10 mx-auto mb-2 opacity-30" />
                            Belum ada data periode.
                        </td>
                    </tr>
                    <tr
                        v-for="(p, idx) in periodes"
                        :key="p.id"
                        :class="[
                            'transition-colors hover:bg-slate-50/60',
                            p.status === 'aktif' ? 'bg-emerald-50/30' : '',
                        ]"
                    >
                        <td class="px-5 py-4 text-xs text-slate-400">{{ idx + 1 }}</td>

                        <!-- Periode + Rentang -->
                        <td class="px-5 py-4">
                            <div class="font-bold text-slate-900">Periode {{ p.nomor_periode }}</div>
                            <div class="flex items-center gap-1 mt-0.5 text-xs text-slate-500">
                                <ChevronRight class="h-3 w-3 shrink-0" />
                                {{ formatRange(p.tanggal_mulai, p.tanggal_selesai) }}
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

                        <!-- Aksi -->
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-1.5">
                                <button
                                    @click="openEdit(p)"
                                    class="h-8 w-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                    title="Edit Tanggal Periode"
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

        <!-- ═══════════════════ MODAL TAMBAH ═══════════════════ -->
        <Teleport to="body">
            <div v-if="showTambah" class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="showTambah = false" />
                <div class="relative w-full max-w-2xl lg:max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-150">
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
                            class="rounded-lg p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
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
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="border-t border-slate-100 bg-slate-50/80 px-6 py-4 flex items-center justify-between gap-3 shrink-0">
                        <button
                            type="button"
                            @click="showTambah = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 transition shadow-2xs"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitTambah"
                            :disabled="formTambah.processing || !formTambah.tanggal_mulai || !formTambah.tanggal_selesai"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-primary/30 hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition"
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
            <div v-if="editTarget" class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="editTarget = null" />
                <div class="relative w-full max-w-2xl lg:max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-150">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <Pencil class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Edit Periode {{ editTarget?.nomor_periode }}</h3>
                                <p class="text-xs text-slate-500">Ubah rentang tanggal periode operasional</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="editTarget = null"
                            class="rounded-lg p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
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
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="border-t border-slate-100 bg-slate-50/80 px-6 py-4 flex items-center justify-between gap-3 shrink-0">
                        <button
                            type="button"
                            @click="editTarget = null"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 transition shadow-2xs"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitEdit"
                            :disabled="formEdit.processing || !formEdit.tanggal_mulai || !formEdit.tanggal_selesai"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 px-5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-amber-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition"
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
            <div v-if="deleteTarget" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="deleteTarget = null" />
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
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
                                class="flex-1 rounded-xl border border-slate-200 bg-white py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition shadow-2xs"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="doDelete"
                                :disabled="isDeleting"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-600 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 shadow-sm shadow-rose-600/30 disabled:opacity-60 transition"
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
