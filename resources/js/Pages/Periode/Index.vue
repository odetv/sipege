<script setup>
import { ref, computed } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
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

// ─── Nomor periode berikutnya ─────────────────────────────────────────────────
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

function openEdit(p) {
    editTarget.value  = p;
    formEdit.tanggal_mulai   = p.tanggal_mulai;
    formEdit.tanggal_selesai = p.tanggal_selesai;
}

function submitEdit() {
    formEdit.put(route("periode.update", editTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { editTarget.value = null; },
    });
}

// ─── Hapus ────────────────────────────────────────────────────────────────────
const deleteTarget = ref(null);

function doDelete() {
    router.delete(route("periode.destroy", deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
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
                @click="showTambah = true"
                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/30 hover:bg-primary/90 transition-colors shrink-0"
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
            <div v-if="showTambah" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showTambah = false" />
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                <Plus class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Tambah Periode {{ nextNomor }}</h3>
                                <p class="text-xs text-slate-500">Nomor periode terisi otomatis</p>
                            </div>
                        </div>
                        <button @click="showTambah = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Nomor Periode (read-only display) -->
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                            <div class="h-8 w-8 rounded-lg bg-primary text-white text-sm font-extrabold flex items-center justify-center shrink-0">
                                {{ nextNomor }}
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Nomor Periode</p>
                                <p class="text-sm font-bold text-slate-900">Periode {{ nextNomor }}</p>
                            </div>
                            <span class="ml-auto text-[10px] font-semibold text-slate-400 bg-slate-200 rounded px-1.5 py-0.5">Otomatis</span>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitTambah" class="px-6 py-5 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Tanggal Mulai <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="formTambah.tanggal_mulai"
                                    type="date"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition"
                                />
                                <p v-if="formTambah.errors.tanggal_mulai" class="mt-1 text-xs text-rose-500">{{ formTambah.errors.tanggal_mulai }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Tanggal Selesai <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="formTambah.tanggal_selesai"
                                    type="date"
                                    :min="formTambah.tanggal_mulai"
                                    :disabled="!formTambah.tanggal_mulai"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition disabled:opacity-50 disabled:cursor-not-allowed"
                                />
                                <p v-if="formTambah.errors.tanggal_selesai" class="mt-1 text-xs text-rose-500">{{ formTambah.errors.tanggal_selesai }}</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="showTambah = false"
                                class="flex-1 rounded-xl border border-slate-200 bg-white py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="formTambah.processing"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-60 transition">
                                <Save class="h-4 w-4" />
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════ MODAL EDIT ═══════════════════ -->
        <Teleport to="body">
            <div v-if="editTarget" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="editTarget = null" />
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                <Pencil class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Edit Periode {{ editTarget?.nomor_periode }}</h3>
                                <p class="text-xs text-slate-500">Ubah rentang tanggal periode</p>
                            </div>
                        </div>
                        <button @click="editTarget = null" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Nomor Periode (read-only) -->
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                            <div class="h-8 w-8 rounded-lg bg-blue-600 text-white text-sm font-extrabold flex items-center justify-center shrink-0">
                                {{ editTarget?.nomor_periode }}
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Nomor Periode</p>
                                <p class="text-sm font-bold text-slate-900">Periode {{ editTarget?.nomor_periode }}</p>
                            </div>
                            <span class="ml-auto text-[10px] font-semibold text-slate-400 bg-slate-200 rounded px-1.5 py-0.5">Terkunci</span>
                        </div>
                    </div>

                    <form @submit.prevent="submitEdit" class="px-6 py-5 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Tanggal Mulai <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="formEdit.tanggal_mulai"
                                    type="date"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition"
                                />
                                <p v-if="formEdit.errors.tanggal_mulai" class="mt-1 text-xs text-rose-500">{{ formEdit.errors.tanggal_mulai }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Tanggal Selesai <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="formEdit.tanggal_selesai"
                                    type="date"
                                    :min="formEdit.tanggal_mulai"
                                    :disabled="!formEdit.tanggal_mulai"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition disabled:opacity-50 disabled:cursor-not-allowed"
                                />
                                <p v-if="formEdit.errors.tanggal_selesai" class="mt-1 text-xs text-rose-500">{{ formEdit.errors.tanggal_selesai }}</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="editTarget = null"
                                class="flex-1 rounded-xl border border-slate-200 bg-white py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="formEdit.processing"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60 transition">
                                <Save class="h-4 w-4" />
                                Perbarui
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════ MODAL HAPUS ═══════════════════ -->
        <Teleport to="body">
            <div v-if="deleteTarget" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="h-12 w-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4">
                            <AlertTriangle class="h-6 w-6" />
                        </div>
                        <h3 class="font-bold text-slate-900 text-base mb-1">
                            Hapus Periode {{ deleteTarget?.nomor_periode }}?
                        </h3>
                        <p class="text-sm text-slate-500 mb-6">Tindakan ini tidak dapat dibatalkan.</p>
                        <div class="flex gap-3">
                            <button @click="deleteTarget = null"
                                class="flex-1 rounded-xl border border-slate-200 bg-white py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button @click="doDelete"
                                class="flex-1 rounded-xl bg-rose-600 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 transition">
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
