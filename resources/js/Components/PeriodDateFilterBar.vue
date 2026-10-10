<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import DateRangePicker from "@/Components/DateRangePicker.vue";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";
import {
    Clock,
    Sparkles,
    CalendarDays,
    Calendar,
    ChevronDown,
    Archive,
    RotateCcw,
} from "lucide-vue-next";

const page = usePage();

const props = defineProps({
    modelValue: {
        type: Object,
        default: null,
    },
    startDate: {
        type: String,
        default: "",
    },
    endDate: {
        type: String,
        default: "",
    },
    mode: {
        type: String,
        default: "",
    },
    periodeId: {
        type: [String, Number],
        default: "",
    },
    isAllTime: {
        type: Boolean,
        default: false,
    },
    periodes: {
        type: Array,
        default: null,
    },
    showArchive: {
        type: Boolean,
        default: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
    autoInit: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits([
    "update:modelValue",
    "update:startDate",
    "update:endDate",
    "update:mode",
    "update:periodeId",
    "update:isAllTime",
    "change",
]);

// ─── List Periode (Props atau Global Inertia Props) ───────────────────────────
const allPeriodes = computed(() => {
    if (props.periodes && Array.isArray(props.periodes)) {
        return props.periodes;
    }
    const pagePeriodes = page?.props?.periodes;
    if (Array.isArray(pagePeriodes)) {
        return pagePeriodes;
    }
    return [];
});

const sortedPeriodes = computed(() => {
    return [...allPeriodes.value].sort((a, b) => {
        const numA = Number(a.nomor_periode) || 0;
        const numB = Number(b.nomor_periode) || 0;
        if (numB !== numA) return numB - numA;
        return String(b.tanggal_mulai || "").localeCompare(String(a.tanggal_mulai || ""));
    });
});

const latestPeriode = computed(() => {
    return sortedPeriodes.value.length > 0 ? sortedPeriodes.value[0] : null;
});

// ─── Format Tanggal Hari Ini ──────────────────────────────────────────────────
const todayStr = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
});

function isPeriodeContainingDate(periode, dateStr) {
    if (!periode || !periode.tanggal_mulai || !periode.tanggal_selesai || !dateStr) return false;
    const s = String(periode.tanggal_mulai).substring(0, 10);
    const e = String(periode.tanggal_selesai).substring(0, 10);
    return dateStr >= s && dateStr <= e;
}

function findPeriodeContainingDate(dateStr) {
    if (!allPeriodes.value || allPeriodes.value.length === 0 || !dateStr) return null;
    return allPeriodes.value.find((p) => isPeriodeContainingDate(p, dateStr)) || null;
}

function findMatchingPeriode(startStr, endStr) {
    if (!allPeriodes.value || !startStr || !endStr) return null;
    const s = String(startStr).substring(0, 10);
    const e = String(endStr).substring(0, 10);
    return (
        allPeriodes.value.find((p) => {
            const pStart = p.tanggal_mulai ? String(p.tanggal_mulai).substring(0, 10) : "";
            const pEnd = p.tanggal_selesai ? String(p.tanggal_selesai).substring(0, 10) : "";
            return pStart === s && pEnd === e;
        }) || null
    );
}

function normalizeMode(mode) {
    if (!mode) return "";
    const m = String(mode).toLowerCase();
    if (m === "hari_ini" || m === "today") return "hari_ini";
    if (m === "periode" || m === "periodik" || m === "period") return "periode";
    if (m === "rentang" || m === "bulanan" || m === "range" || m === "custom") return "rentang";
    return "";
}

// ─── State Internal ───────────────────────────────────────────────────────────
function computeInitialState() {
    const today = todayStr.value;
    const activePeriodeToday = findPeriodeContainingDate(today);

    // Default resolusi: jika hari ini berada dalam rentang suatu periode yang ada di database,
    // maka gunakan Periode tersebut! Jika di luar rentang semua periode, baru gunakan Hari Ini!
    const defaultResolution = activePeriodeToday
        ? {
            mode: "periode",
            start: String(activePeriodeToday.tanggal_mulai).substring(0, 10),
            end: String(activePeriodeToday.tanggal_selesai).substring(0, 10),
            periodeId: String(activePeriodeToday.id),
            isAllTime: false,
        }
        : {
            mode: "hari_ini",
            start: today,
            end: today,
            periodeId: "all",
            isAllTime: false,
        };

    // Jika user mengoper modelValue eksplisit
    if (props.modelValue && (props.modelValue.start || props.modelValue.mode)) {
        const norm = normalizeMode(props.modelValue.mode);
        const resolvedMode = norm || defaultResolution.mode;
        let resolvedPeriodeId =
            props.modelValue.periodeId !== undefined &&
            props.modelValue.periodeId !== "" &&
            props.modelValue.periodeId !== "all"
                ? String(props.modelValue.periodeId)
                : "";

        if (resolvedMode === "periode" && !resolvedPeriodeId) {
            const activeP = activePeriodeToday || latestPeriode.value;
            if (activeP) resolvedPeriodeId = String(activeP.id);
        }

        let resolvedStart = props.modelValue.start || "";
        let resolvedEnd = props.modelValue.end || resolvedStart || "";

        if (!resolvedStart && resolvedMode === "periode" && resolvedPeriodeId) {
            const p = allPeriodes.value.find((item) => String(item.id) === String(resolvedPeriodeId));
            if (p && p.tanggal_mulai && p.tanggal_selesai) {
                resolvedStart = String(p.tanggal_mulai).substring(0, 10);
                resolvedEnd = String(p.tanggal_selesai).substring(0, 10);
            }
        }

        return {
            mode: resolvedMode,
            start: resolvedStart || defaultResolution.start,
            end: resolvedEnd || defaultResolution.end,
            periodeId: resolvedPeriodeId || defaultResolution.periodeId,
            isAllTime: !!props.modelValue.isAllTime,
        };
    }

    // Jika user mengoper props eksplisit
    if (props.startDate || props.mode) {
        const norm = normalizeMode(props.mode);
        const resolvedMode = norm || defaultResolution.mode;
        let resolvedPeriodeId =
            props.periodeId !== undefined &&
            props.periodeId !== "" &&
            props.periodeId !== "all"
                ? String(props.periodeId)
                : "";

        if (resolvedMode === "periode" && !resolvedPeriodeId) {
            const activeP = activePeriodeToday || latestPeriode.value;
            if (activeP) resolvedPeriodeId = String(activeP.id);
        }

        let resolvedStart = props.startDate || "";
        let resolvedEnd = props.endDate || props.startDate || "";

        if (!resolvedStart && resolvedMode === "periode" && resolvedPeriodeId) {
            const p = allPeriodes.value.find((item) => String(item.id) === String(resolvedPeriodeId));
            if (p && p.tanggal_mulai && p.tanggal_selesai) {
                resolvedStart = String(p.tanggal_mulai).substring(0, 10);
                resolvedEnd = String(p.tanggal_selesai).substring(0, 10);
            }
        }

        return {
            mode: resolvedMode,
            start: resolvedStart || defaultResolution.start,
            end: resolvedEnd || defaultResolution.end,
            periodeId: resolvedPeriodeId || defaultResolution.periodeId,
            isAllTime: props.isAllTime,
        };
    }

    return defaultResolution;
}

const initial = computeInitialState();
const activeFilterMode = ref(initial.mode); // 'hari_ini' | 'periode' | 'rentang'
const tanggalMulai = ref(initial.start);
const tanggalSelesai = ref(initial.end);
const selectedPeriodeId = ref(initial.periodeId);
const isFilterAllTime = ref(initial.isAllTime);

// State Kalender Popover
const isDatePickerOpen = ref(false);
const datePickerRange = ref({
    start: tanggalMulai.value,
    end: tanggalSelesai.value,
});

watch([tanggalMulai, tanggalSelesai], () => {
    datePickerRange.value = {
        start: tanggalMulai.value,
        end: tanggalSelesai.value,
    };
});

// Emit perubahan ke parent
function emitState() {
    const payload = {
        start: tanggalMulai.value,
        end: tanggalSelesai.value,
        startDate: tanggalMulai.value,
        endDate: tanggalSelesai.value,
        mode: activeFilterMode.value,
        periodeId: selectedPeriodeId.value,
        isAllTime: isFilterAllTime.value,
    };

    emit("update:modelValue", payload);
    emit("update:startDate", tanggalMulai.value);
    emit("update:endDate", tanggalSelesai.value);
    emit("update:mode", activeFilterMode.value);
    emit("update:periodeId", selectedPeriodeId.value);
    emit("update:isAllTime", isFilterAllTime.value);
    emit("change", payload);
}

// Sinkronisasi jika props dari parent berubah dari luar
watch(
    () => props.modelValue,
    (val) => {
        if (!val) return;
        if (val.start && val.start !== tanggalMulai.value) tanggalMulai.value = val.start;
        if (val.end && val.end !== tanggalSelesai.value) tanggalSelesai.value = val.end;
        if (val.mode && val.mode !== activeFilterMode.value) activeFilterMode.value = val.mode;
        if (val.periodeId !== undefined && String(val.periodeId) !== String(selectedPeriodeId.value)) {
            selectedPeriodeId.value = String(val.periodeId);
        }
        if (val.isAllTime !== undefined && val.isAllTime !== isFilterAllTime.value) {
            isFilterAllTime.value = val.isAllTime;
        }
    },
    { deep: true }
);

watch(
    () => props.startDate,
    (val) => {
        if (val && val !== tanggalMulai.value) tanggalMulai.value = val;
    }
);
watch(
    () => props.endDate,
    (val) => {
        if (val && val !== tanggalSelesai.value) tanggalSelesai.value = val;
    }
);
watch(
    () => props.mode,
    (val) => {
        const norm = normalizeMode(val);
        if (norm && norm !== activeFilterMode.value) activeFilterMode.value = norm;
    }
);
watch(
    () => props.periodeId,
    (val) => {
        if (val !== undefined && val !== "" && String(val) !== String(selectedPeriodeId.value)) {
            selectedPeriodeId.value = String(val);
        }
    }
);
watch(
    () => props.isAllTime,
    (val) => {
        if (val !== undefined && val !== isFilterAllTime.value) {
            isFilterAllTime.value = val;
        }
    }
);

// Watcher daftar periode (ketika props.periodes atau global periodes dimuat)
watch(
    allPeriodes,
    (newList) => {
        if (!newList || newList.length === 0) return;
        if (activeFilterMode.value === "periode") {
            const hasValid = newList.some((p) => String(p.id) === String(selectedPeriodeId.value));
            if (!hasValid || selectedPeriodeId.value === "all" || !selectedPeriodeId.value) {
                const activeP = findPeriodeContainingDate(todayStr.value) || latestPeriode.value;
                if (activeP) {
                    selectedPeriodeId.value = String(activeP.id);
                    tanggalMulai.value = String(activeP.tanggal_mulai).substring(0, 10);
                    tanggalSelesai.value = String(activeP.tanggal_selesai).substring(0, 10);
                    emitState();
                }
            }
        }
    },
    { immediate: true, deep: true }
);

// ─── Handler Aksi Pengguna ────────────────────────────────────────────────────
function pilihFilterMode(mode) {
    isFilterAllTime.value = false;

    if (mode === "hari_ini") {
        activeFilterMode.value = "hari_ini";
        isDatePickerOpen.value = false;
        tanggalMulai.value = todayStr.value;
        tanggalSelesai.value = todayStr.value;
    } else if (mode === "periode") {
        activeFilterMode.value = "periode";
        isDatePickerOpen.value = false;

        let targetPeriode = null;
        if (selectedPeriodeId.value && selectedPeriodeId.value !== "all") {
            targetPeriode = allPeriodes.value?.find((p) => String(p.id) === String(selectedPeriodeId.value));
        }
        if (!targetPeriode) {
            targetPeriode = findPeriodeContainingDate(todayStr.value) || latestPeriode.value;
        }
        if (targetPeriode) {
            selectedPeriodeId.value = String(targetPeriode.id);
            if (targetPeriode.tanggal_mulai && targetPeriode.tanggal_selesai) {
                tanggalMulai.value = String(targetPeriode.tanggal_mulai).substring(0, 10);
                tanggalSelesai.value = String(targetPeriode.tanggal_selesai).substring(0, 10);
            }
        }
    } else if (mode === "rentang") {
        activeFilterMode.value = "rentang";
        isDatePickerOpen.value = false;
    }

    emitState();
}

function onPeriodeSelectChange() {
    if (!selectedPeriodeId.value || selectedPeriodeId.value === "all") {
        emitState();
        return;
    }
    const p = allPeriodes.value?.find((it) => String(it.id) === String(selectedPeriodeId.value));
    if (p && p.tanggal_mulai && p.tanggal_selesai) {
        tanggalMulai.value = String(p.tanggal_mulai).substring(0, 10);
        tanggalSelesai.value = String(p.tanggal_selesai).substring(0, 10);
    }
    emitState();
}

function onApplyDateRange(newRange) {
    if (newRange && newRange.start && newRange.end) {
        isFilterAllTime.value = false;
        activeFilterMode.value = "rentang";
        tanggalMulai.value = newRange.start;
        tanggalSelesai.value = newRange.end;

        const matched = findMatchingPeriode(newRange.start, newRange.end);
        if (matched) {
            selectedPeriodeId.value = String(matched.id);
        } else {
            selectedPeriodeId.value = "all";
        }
        isDatePickerOpen.value = false;
        emitState();
    }
}

function resetFilterSemua() {
    isFilterAllTime.value = true;
    isDatePickerOpen.value = false;
    emitState();
}

function kembalikanFilterSemula() {
    isFilterAllTime.value = false;
    isDatePickerOpen.value = false;

    if (activeFilterMode.value === "periode") {
        let p = allPeriodes.value?.find((it) => String(it.id) === String(selectedPeriodeId.value));
        if (!p) {
            p = findPeriodeContainingDate(todayStr.value) || latestPeriode.value;
            if (p) selectedPeriodeId.value = String(p.id);
        }
        if (p && p.tanggal_mulai && p.tanggal_selesai) {
            tanggalMulai.value = String(p.tanggal_mulai).substring(0, 10);
            tanggalSelesai.value = String(p.tanggal_selesai).substring(0, 10);
        }
    } else if (activeFilterMode.value === "hari_ini") {
        tanggalMulai.value = todayStr.value;
        tanggalSelesai.value = todayStr.value;
    }

    emitState();
}

onMounted(() => {
    if (activeFilterMode.value === "periode") {
        const hasValid = allPeriodes.value?.some((p) => String(p.id) === String(selectedPeriodeId.value));
        if (!hasValid || selectedPeriodeId.value === "all" || !selectedPeriodeId.value) {
            const activeP = findPeriodeContainingDate(todayStr.value) || latestPeriode.value;
            if (activeP) {
                selectedPeriodeId.value = String(activeP.id);
                tanggalMulai.value = String(activeP.tanggal_mulai).substring(0, 10);
                tanggalSelesai.value = String(activeP.tanggal_selesai).substring(0, 10);
            }
        }
    }
    if (props.autoInit) {
        emitState();
    }
});
</script>

<template>
    <div
        class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-2.5 sm:p-3 rounded-2xl bg-gradient-to-r from-emerald-50/90 via-teal-50/60 to-slate-50 border border-emerald-200 shadow-2xs"
        :class="{ 'p-2 sm:p-2.5': compact }"
    >
        <!-- Sisi Kiri: Switcher 3 Opsi + Dropdown Periode / Capsule Rentang -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Slot tambahan kiri (opsional) -->
            <slot name="left" />

            <!-- Switcher Segmented 3 Opsi -->
            <div
                class="inline-flex items-center p-1 rounded-xl bg-white border border-emerald-200/90 shadow-2xs"
                :class="compact ? 'h-8 sm:h-9' : 'h-9 sm:h-10'"
            >
                <!-- 1. Hari Ini -->
                <button
                    type="button"
                    @click="pilihFilterMode('hari_ini')"
                    :class="[
                        'h-full px-3 sm:px-3.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5',
                        activeFilterMode === 'hari_ini' && !isFilterAllTime
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50',
                    ]"
                    title="Tampilkan data untuk hari ini saja"
                >
                    <Clock class="h-3.5 w-3.5" />
                    <span>Hari Ini</span>
                </button>

                <!-- 2. Periode -->
                <button
                    type="button"
                    @click="pilihFilterMode('periode')"
                    :class="[
                        'h-full px-3 sm:px-3.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5',
                        activeFilterMode === 'periode' && !isFilterAllTime
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50',
                    ]"
                    title="Filter berdasarkan siklus periode SPPG"
                >
                    <Sparkles class="h-3.5 w-3.5" />
                    <span>Periode</span>
                </button>

                <!-- 3. Rentang -->
                <button
                    type="button"
                    @click="pilihFilterMode('rentang')"
                    :class="[
                        'h-full px-3 sm:px-3.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5',
                        activeFilterMode === 'rentang' && !isFilterAllTime
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50',
                    ]"
                    title="Filter berdasarkan rentang tanggal kalender"
                >
                    <CalendarDays class="h-3.5 w-3.5" />
                    <span>Rentang</span>
                </button>
            </div>

            <!-- Dropdown Periode (Tampil saat mode Periode aktif) -->
            <div
                v-if="activeFilterMode === 'periode' && !isFilterAllTime"
                class="flex items-center gap-2 animate-in fade-in duration-200"
            >
                <div class="relative">
                    <select
                        v-model="selectedPeriodeId"
                        @change="onPeriodeSelectChange"
                        :class="[
                            'pl-3 pr-8 rounded-xl border border-emerald-300 hover:border-emerald-500 text-xs font-bold text-slate-800 bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none cursor-pointer shadow-2xs transition-all max-w-full sm:max-w-xs md:max-w-md truncate',
                            compact ? 'h-8 sm:h-9' : 'h-9 sm:h-10',
                        ]"
                    >
                        <option
                            v-for="p in sortedPeriodes"
                            :key="p.id"
                            :value="String(p.id)"
                        >
                            Periode {{ p.nomor_periode }} ({{ formatTanggalIndo(p.tanggal_mulai) }} – {{ formatTanggalIndo(p.tanggal_selesai) }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- Capsule Range Tanggal (Tampil saat mode Rentang aktif, memicu kalender muncul dari kapsul ini) -->
            <div
                v-else-if="activeFilterMode === 'rentang' && !isFilterAllTime"
                class="relative flex items-center animate-in fade-in duration-200"
            >
                <button
                    type="button"
                    @click="isDatePickerOpen = !isDatePickerOpen"
                    :class="[
                        'inline-flex items-center gap-2 px-3.5 rounded-xl bg-white border border-emerald-300 hover:border-emerald-500 shadow-2xs text-xs font-bold text-slate-800 transition-all cursor-pointer group',
                        compact ? 'h-8 sm:h-9' : 'h-9 sm:h-10',
                    ]"
                    title="Klik untuk membuka pemilih tanggal kalender"
                >
                    <span class="text-emerald-700 font-extrabold">{{ formatTanggalIndo(tanggalMulai) }}</span>
                    <template v-if="tanggalMulai !== tanggalSelesai">
                        <span class="text-emerald-500 font-black">➜</span>
                        <span class="text-emerald-700 font-extrabold">{{ formatTanggalIndo(tanggalSelesai) }}</span>
                    </template>
                    <ChevronDown
                        class="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 transition-transform"
                        :class="{ 'rotate-180': isDatePickerOpen }"
                    />
                </button>

                <!-- Backdrop Click Outside -->
                <div
                    v-if="isDatePickerOpen"
                    class="fixed inset-0 z-40 bg-black/20 sm:bg-transparent backdrop-blur-[1px] sm:backdrop-blur-none transition-opacity"
                    @click="isDatePickerOpen = false"
                ></div>

                <!-- Popover Kalender Dua Bulan Muncul Langsung di Bawah Kapsul Rentang Tanggal -->
                <div
                    v-if="isDatePickerOpen"
                    class="fixed inset-x-2 top-24 sm:absolute sm:inset-x-auto sm:left-0 sm:top-full sm:mt-2 z-50 flex justify-center sm:block"
                >
                    <DateRangePicker
                        v-model="datePickerRange"
                        :isOpen="isDatePickerOpen"
                        @apply="onApplyDateRange"
                        @close="isDatePickerOpen = false"
                    />
                </div>
            </div>

            <!-- Info Hari Ini (Saat mode Hari Ini aktif) -->
            <div
                v-else-if="activeFilterMode === 'hari_ini' && !isFilterAllTime"
                class="flex items-center gap-2 animate-in fade-in duration-200"
            >
                <div
                    :class="[
                        'inline-flex items-center gap-2 px-3.5 rounded-xl bg-white border border-emerald-300 shadow-2xs text-xs font-bold text-emerald-800 whitespace-nowrap',
                        compact ? 'h-8 sm:h-9' : 'h-9 sm:h-10',
                    ]"
                >
                    <Calendar class="h-3.5 w-3.5 text-emerald-600" />
                    <span>{{ formatTanggalIndo(todayStr) }}</span>
                </div>
            </div>

            <!-- Slot ekstra (misal tombol atau dropdown filter samping) -->
            <slot name="extra" />
        </div>

        <!-- Sisi Kanan: Icon Aksi Lihat Semua Arsip / Kembalikan ke Semula -->
        <div class="flex items-center gap-2 ml-auto sm:ml-0">
            <slot name="right" />

            <template v-if="showArchive">
                <button
                    v-if="!isFilterAllTime"
                    type="button"
                    @click="resetFilterSemua"
                    :class="[
                        'rounded-xl bg-white border border-emerald-300 hover:border-emerald-500 text-slate-700 hover:text-emerald-800 shadow-2xs flex items-center justify-center transition-all cursor-pointer group',
                        compact ? 'h-8 sm:h-9 w-8 sm:w-9' : 'h-9 sm:h-10 w-9 sm:w-10',
                    ]"
                    title="Lihat semua arsip yang ada"
                >
                    <Archive class="h-4 w-4 text-emerald-700 group-hover:scale-110 transition-transform" />
                </button>
                <button
                    v-else
                    type="button"
                    @click="kembalikanFilterSemula"
                    :class="[
                        'rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white shadow-2xs flex items-center justify-center transition-all cursor-pointer group',
                        compact ? 'h-8 sm:h-9 w-8 sm:w-9' : 'h-9 sm:h-10 w-9 sm:w-10',
                    ]"
                    title="Kembalikan ke semula"
                >
                    <RotateCcw class="h-4 w-4 group-hover:-rotate-45 transition-transform" />
                </button>
            </template>
        </div>
    </div>
</template>
