<script setup>
import { ref, computed, watch } from "vue";
import { ChevronLeft, ChevronRight, Zap, Check, AlertCircle, Calendar } from "lucide-vue-next";

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ start: "", end: "" }),
    },
    existingPeriodes: {
        type: Array,
        default: () => [],
    },
    currentPeriodeId: {
        type: [Number, String],
        default: null,
    },
    latestPeriode: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["update:modelValue", "change"]);

const monthNames = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
];
const dayHeaders = ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"];
const years = [2024, 2025, 2026, 2027, 2028, 2029, 2030];

// Internal start/end
const tempStart = ref(props.modelValue?.start || "");
const tempEnd = ref(props.modelValue?.end || "");
const hoverDate = ref("");
const alertMessage = ref("");

function formatDateStr(year, month, day) {
    const m = String(month + 1).padStart(2, "0");
    const d = String(day).padStart(2, "0");
    return `${year}-${m}-${d}`;
}

// Map dates to occupied periods (excluding currentPeriodeId)
const occupiedDatesMap = computed(() => {
    const map = {};
    const periodsToConsider = (props.existingPeriodes || []).filter(
        (p) => String(p.id) !== String(props.currentPeriodeId)
    );

    periodsToConsider.forEach((p) => {
        if (!p.tanggal_mulai || !p.tanggal_selesai) return;
        let cur = new Date(p.tanggal_mulai + "T00:00:00");
        const end = new Date(p.tanggal_selesai + "T00:00:00");
        while (cur <= end) {
            const y = cur.getFullYear();
            const m = String(cur.getMonth() + 1).padStart(2, "0");
            const d = String(cur.getDate()).padStart(2, "0");
            const dStr = `${y}-${m}-${d}`;
            map[dStr] = {
                id: p.id,
                nomor_periode: p.nomor_periode,
                isStart: dStr === p.tanggal_mulai,
                isEnd: dStr === p.tanggal_selesai,
            };
            cur.setDate(cur.getDate() + 1);
        }
    });

    return map;
});

// Initial month view centered on start date or latest period or today
function resolveInitialDate() {
    if (props.modelValue?.start) {
        return new Date(props.modelValue.start + "T00:00:00");
    }
    if (props.latestPeriode?.tanggal_selesai) {
        const lastEnd = new Date(props.latestPeriode.tanggal_selesai + "T00:00:00");
        lastEnd.setDate(lastEnd.getDate() + 1);
        return lastEnd;
    }
    return new Date();
}

const currentMonth1 = ref(resolveInitialDate().getMonth());
const currentYear1 = ref(resolveInitialDate().getFullYear());

const currentMonth2 = computed(() => (currentMonth1.value + 1) % 12);
const currentYear2 = computed(() => (currentMonth1.value === 11 ? currentYear1.value + 1 : currentYear1.value));

function prevMonth() {
    if (currentMonth1.value === 0) {
        currentMonth1.value = 11;
        currentYear1.value -= 1;
    } else {
        currentMonth1.value -= 1;
    }
}

function nextMonth() {
    if (currentMonth1.value === 11) {
        currentMonth1.value = 0;
        currentYear1.value += 1;
    } else {
        currentMonth1.value += 1;
    }
}

function onMonth1Change(e) {
    currentMonth1.value = parseInt(e.target.value);
}

function onYear1Change(e) {
    currentYear1.value = parseInt(e.target.value);
}

function onMonth2Change(e) {
    const val = parseInt(e.target.value);
    if (val === 0) {
        currentMonth1.value = 11;
        currentYear1.value = currentYear2.value - 1;
    } else {
        currentMonth1.value = val - 1;
    }
}

function onYear2Change(e) {
    const val = parseInt(e.target.value);
    if (currentMonth1.value === 11) {
        currentYear1.value = val - 1;
    } else {
        currentYear1.value = val;
    }
}

// Build 35 or 42 grid for a month
function buildMonthDays(monthIdx, yearVal) {
    const firstDay = new Date(yearVal, monthIdx, 1);
    const startDayOffset = (firstDay.getDay() + 6) % 7;
    const daysInMonth = new Date(yearVal, monthIdx + 1, 0).getDate();
    const prevMonthDays = new Date(yearVal, monthIdx, 0).getDate();

    const days = [];

    // Leading days
    for (let i = startDayOffset - 1; i >= 0; i--) {
        const dNum = prevMonthDays - i;
        const pMonth = monthIdx === 0 ? 11 : monthIdx - 1;
        const pYear = monthIdx === 0 ? yearVal - 1 : yearVal;
        const dateStr = formatDateStr(pYear, pMonth, dNum);
        days.push({
            dayNum: dNum,
            dateStr,
            isCurrentMonth: false,
            dayOfWeek: (startDayOffset - 1 - i),
            occupied: occupiedDatesMap.value[dateStr] || null,
        });
    }

    // Days in current month
    for (let d = 1; d <= daysInMonth; d++) {
        const curDate = new Date(yearVal, monthIdx, d);
        const dayOfWeek = (curDate.getDay() + 6) % 7;
        const dateStr = formatDateStr(yearVal, monthIdx, d);
        days.push({
            dayNum: d,
            dateStr,
            isCurrentMonth: true,
            dayOfWeek,
            occupied: occupiedDatesMap.value[dateStr] || null,
        });
    }

    // Trailing days
    const targetTotal = days.length > 35 ? 42 : 35;
    const remaining = targetTotal - days.length;
    for (let d = 1; d <= remaining; d++) {
        const nMonth = monthIdx === 11 ? 0 : monthIdx + 1;
        const nYear = monthIdx === 11 ? yearVal + 1 : yearVal;
        const curDate = new Date(nYear, nMonth, d);
        const dayOfWeek = (curDate.getDay() + 6) % 7;
        const dateStr = formatDateStr(nYear, nMonth, d);
        days.push({
            dayNum: d,
            dateStr,
            isCurrentMonth: false,
            dayOfWeek,
            occupied: occupiedDatesMap.value[dateStr] || null,
        });
    }

    return days;
}

const month1Days = computed(() => buildMonthDays(currentMonth1.value, currentYear1.value));
const month2Days = computed(() => buildMonthDays(currentMonth2.value, currentYear2.value));

// Effective selection range
const effectiveRange = computed(() => {
    let s = tempStart.value;
    let e = tempEnd.value;

    if (s && !e && hoverDate.value) {
        if (hoverDate.value >= s) {
            e = hoverDate.value;
        } else {
            e = s;
            s = hoverDate.value;
        }
    } else if (s && e && s > e) {
        const tmp = s;
        s = e;
        e = tmp;
    }

    return { start: s, end: e };
});

function isStart(dateStr) {
    return effectiveRange.value.start === dateStr;
}

function isEnd(dateStr) {
    return effectiveRange.value.end === dateStr;
}

function isInRange(dateStr) {
    const { start, end } = effectiveRange.value;
    if (!start || !end) return false;
    return dateStr >= start && dateStr <= end;
}

function isStrictMiddle(dateStr) {
    const { start, end } = effectiveRange.value;
    if (!start || !end) return false;
    return dateStr > start && dateStr < end;
}

// Check if range [s, e] contains any occupied dates
function rangeContainsOccupied(s, e) {
    let cur = new Date(s + "T00:00:00");
    const end = new Date(e + "T00:00:00");
    while (cur <= end) {
        const y = cur.getFullYear();
        const m = String(cur.getMonth() + 1).padStart(2, "0");
        const d = String(cur.getDate()).padStart(2, "0");
        const dStr = `${y}-${m}-${d}`;
        if (occupiedDatesMap.value[dStr]) {
            return occupiedDatesMap.value[dStr];
        }
        cur.setDate(cur.getDate() + 1);
    }
    return null;
}

function selectDate(dateStr) {
    alertMessage.value = "";
    if (occupiedDatesMap.value[dateStr]) {
        alertMessage.value = `Tanggal ini sudah terdaftar di Periode ${occupiedDatesMap.value[dateStr].nomor_periode} dan tidak dapat dipilih.`;
        return;
    }

    if (!tempStart.value || (tempStart.value && tempEnd.value)) {
        tempStart.value = dateStr;
        tempEnd.value = "";
        emit("update:modelValue", { start: tempStart.value, end: "" });
    } else if (tempStart.value && !tempEnd.value) {
        let s = tempStart.value;
        let e = dateStr;
        if (e < s) {
            const tmp = s;
            s = e;
            e = tmp;
        }

        const conflict = rangeContainsOccupied(s, e);
        if (conflict) {
            alertMessage.value = `Rentang tanggal menabrak Periode ${conflict.nomor_periode}. Pilih rentang yang tidak bertabrakan.`;
            return;
        }

        tempStart.value = s;
        tempEnd.value = e;
        emit("update:modelValue", { start: s, end: e });
        emit("change", { start: s, end: e });
    }
}

// ─── Preset: Tambah per 2 Minggu (+14 Hari) sejak Periode Terakhir ────────────
const quickPresetInfo = computed(() => {
    let baseDate;
    let labelPrev = "Awal";
    if (props.latestPeriode?.tanggal_selesai) {
        baseDate = new Date(props.latestPeriode.tanggal_selesai + "T00:00:00");
        baseDate.setDate(baseDate.getDate() + 1);
        labelPrev = `Periode ${props.latestPeriode.nomor_periode}`;
    } else {
        baseDate = new Date();
    }

    const sy = baseDate.getFullYear();
    const sm = String(baseDate.getMonth() + 1).padStart(2, "0");
    const sd = String(baseDate.getDate()).padStart(2, "0");
    const startStr = `${sy}-${sm}-${sd}`;

    const endDate = new Date(baseDate);
    endDate.setDate(baseDate.getDate() + 13); // 14 hari total
    const ey = endDate.getFullYear();
    const em = String(endDate.getMonth() + 1).padStart(2, "0");
    const ed = String(endDate.getDate()).padStart(2, "0");
    const endStr = `${ey}-${em}-${ed}`;

    return {
        start: startStr,
        end: endStr,
        labelPrev,
        displayText: `${sd}/${sm} s.d. ${ed}/${em}/${ey}`,
    };
});

function applyQuick2Weeks() {
    alertMessage.value = "";
    const { start, end } = quickPresetInfo.value;

    const conflict = rangeContainsOccupied(start, end);
    if (conflict) {
        alertMessage.value = `Preset 2 minggu menabrak Periode ${conflict.nomor_periode}.`;
        return;
    }

    tempStart.value = start;
    tempEnd.value = end;

    // Geser kalender ke bulan mulai
    const sDate = new Date(start + "T00:00:00");
    currentMonth1.value = sDate.getMonth();
    currentYear1.value = sDate.getFullYear();

    emit("update:modelValue", { start, end });
    emit("change", { start, end });
}

watch(
    () => props.modelValue,
    (val) => {
        if (val) {
            tempStart.value = val.start || "";
            tempEnd.value = val.end || "";
            if (val.start) {
                const d = new Date(val.start + "T00:00:00");
                currentMonth1.value = d.getMonth();
                currentYear1.value = d.getFullYear();
            }
        }
    },
    { deep: true }
);

const selectedDaysCount = computed(() => {
    if (!tempStart.value || !tempEnd.value) return 0;
    const d1 = new Date(tempStart.value + "T00:00:00");
    const d2 = new Date(tempEnd.value + "T00:00:00");
    const diffTime = Math.abs(d2 - d1);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
});
</script>

<template>
    <div class="space-y-3.5 select-none text-slate-800">
        <!-- ─── OPSI CEPAT PRESET (+2 MINGGU) ────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 rounded-xl bg-gradient-to-r from-emerald-50 via-teal-50/70 to-emerald-50/40 border border-emerald-200/80 shadow-2xs">
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-2xs">
                    <Zap class="h-4 w-4" />
                </div>
                <div>
                    <p class="text-xs font-bold text-emerald-950 flex items-center gap-1.5">
                        <span>Opsi Cepat: +2 Minggu (14 Hari)</span>
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-emerald-200/70 text-emerald-800">
                            Sejak {{ quickPresetInfo.labelPrev }}
                        </span>
                    </p>
                    <p class="text-[11px] text-emerald-700">
                        Otomatis mengisi tanggal: <strong>{{ quickPresetInfo.displayText }}</strong>
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="applyQuick2Weeks"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer shrink-0"
                title="Pilih langsung rentang 2 minggu dari periode sebelumnya"
            >
                <Check class="h-3.5 w-3.5" />
                <span>Terapkan 2 Minggu</span>
            </button>
        </div>

        <!-- Warning Alert jika mencoba memilih tanggal yang tabrakan -->
        <div
            v-if="alertMessage"
            class="flex items-start gap-2 p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 animate-in fade-in duration-150"
        >
            <AlertCircle class="h-4 w-4 shrink-0 mt-0.5 text-rose-500" />
            <span class="font-medium">{{ alertMessage }}</span>
        </div>

        <!-- ─── KALENDER GRID CONTAINER (Responsive 1 Col di HP, 2 Col di Desktop) ──── -->
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 sm:p-4 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                <!-- ─── BULAN 1 (KIRI) ───────────────────────────────────────── -->
                <div class="w-full">
                    <!-- Header Bulan 1 -->
                    <div class="flex items-center justify-between gap-1 pb-2.5 border-b border-slate-100">
                        <div class="flex items-center gap-1.5">
                            <select
                                :value="currentMonth1"
                                @change="onMonth1Change"
                                class="pl-2.5 pr-7 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-800 bg-white hover:bg-slate-50 focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none cursor-pointer"
                            >
                                <option v-for="(mName, idx) in monthNames" :key="idx" :value="idx">
                                    {{ mName }}
                                </option>
                            </select>

                            <select
                                :value="currentYear1"
                                @change="onYear1Change"
                                class="pl-2.5 pr-7 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-800 bg-white hover:bg-slate-50 focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none cursor-pointer"
                            >
                                <option v-for="y in years" :key="y" :value="y">
                                    {{ y }}
                                </option>
                            </select>
                        </div>

                        <!-- Nav Chevrons -->
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="prevMonth"
                                class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
                                title="Bulan Sebelumnya"
                            >
                                <ChevronLeft class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                @click="nextMonth"
                                class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer sm:hidden"
                                title="Bulan Berikutnya"
                            >
                                <ChevronRight class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Day Headers -->
                    <div class="grid grid-cols-7 text-center pt-2 pb-1">
                        <div
                            v-for="day in dayHeaders"
                            :key="day"
                            class="h-6 flex items-center justify-center text-[10px] font-bold text-slate-400 uppercase"
                        >
                            {{ day }}
                        </div>
                    </div>

                    <!-- Days Grid Bulan 1 -->
                    <div class="grid grid-cols-7 text-center text-xs">
                        <div
                            v-for="(item, idx) in month1Days"
                            :key="idx"
                            class="relative h-9 flex items-center justify-center select-none"
                            :class="[
                                item.occupied ? 'cursor-not-allowed opacity-60' : 'cursor-pointer group',
                            ]"
                            @mouseenter="!item.occupied && (hoverDate = item.dateStr)"
                            @mouseleave="hoverDate = ''"
                            @click="selectDate(item.dateStr)"
                        >
                            <!-- Continuous Selection Band -->
                            <div
                                v-if="!item.occupied && isInRange(item.dateStr)"
                                :class="[
                                    'absolute inset-y-1 bg-emerald-100/80 transition-colors',
                                    isStart(item.dateStr) && isEnd(item.dateStr)
                                        ? 'inset-x-1 rounded-md'
                                        : isStart(item.dateStr)
                                        ? 'left-1 right-0 rounded-l-md'
                                        : isEnd(item.dateStr)
                                        ? 'left-0 right-1 rounded-r-md'
                                        : item.dayOfWeek === 0
                                        ? 'left-1 right-0 rounded-l-md'
                                        : item.dayOfWeek === 6
                                        ? 'left-0 right-1 rounded-r-md'
                                        : 'inset-x-0',
                                ]"
                            ></div>

                            <!-- Occupied Band Style (Sudah Ada Periode) -->
                            <div
                                v-if="item.occupied"
                                class="absolute inset-y-1 inset-x-0.5 rounded-md bg-slate-100 border border-dashed border-slate-200 flex items-center justify-center"
                                :title="`Sudah terdaftar di Periode ${item.occupied.nomor_periode}`"
                            ></div>

                            <!-- Date Number / Pill -->
                            <div
                                :class="[
                                    'relative z-10 w-8 h-8 flex flex-col items-center justify-center text-xs transition-all select-none',
                                    item.occupied
                                        ? 'text-slate-400 font-semibold'
                                        : isStart(item.dateStr) || isEnd(item.dateStr)
                                        ? 'bg-[#0e1f38] text-white font-black rounded-lg shadow-xs scale-105'
                                        : isStrictMiddle(item.dateStr)
                                        ? 'text-emerald-950 font-bold'
                                        : item.isCurrentMonth
                                        ? 'text-slate-800 hover:bg-slate-200/80 rounded-lg font-medium'
                                        : 'text-slate-300 font-normal hover:bg-slate-100 rounded-lg',
                                ]"
                            >
                                <span class="leading-none">{{ item.dayNum }}</span>
                                <span
                                    v-if="item.occupied"
                                    class="text-[7.5px] leading-none font-bold text-slate-400 mt-0.5 tracking-tighter"
                                >
                                    P{{ item.occupied.nomor_periode }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── BULAN 2 (KANAN - Tampil di Tablet & Desktop) ────────── -->
                <div class="hidden sm:block w-full">
                    <!-- Header Bulan 2 -->
                    <div class="flex items-center justify-between gap-1 pb-2.5 border-b border-slate-100">
                        <div class="flex items-center gap-1.5">
                            <select
                                :value="currentMonth2"
                                @change="onMonth2Change"
                                class="pl-2.5 pr-7 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-800 bg-white hover:bg-slate-50 focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none cursor-pointer"
                            >
                                <option v-for="(mName, idx) in monthNames" :key="idx" :value="idx">
                                    {{ mName }}
                                </option>
                            </select>

                            <select
                                :value="currentYear2"
                                @change="onYear2Change"
                                class="pl-2.5 pr-7 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-800 bg-white hover:bg-slate-50 focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none cursor-pointer"
                            >
                                <option v-for="y in years" :key="y" :value="y">
                                    {{ y }}
                                </option>
                            </select>
                        </div>

                        <!-- Next Chevron -->
                        <button
                            type="button"
                            @click="nextMonth"
                            class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
                            title="Bulan Berikutnya"
                        >
                            <ChevronRight class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- Day Headers -->
                    <div class="grid grid-cols-7 text-center pt-2 pb-1">
                        <div
                            v-for="day in dayHeaders"
                            :key="day"
                            class="h-6 flex items-center justify-center text-[10px] font-bold text-slate-400 uppercase"
                        >
                            {{ day }}
                        </div>
                    </div>

                    <!-- Days Grid Bulan 2 -->
                    <div class="grid grid-cols-7 text-center text-xs">
                        <div
                            v-for="(item, idx) in month2Days"
                            :key="idx"
                            class="relative h-9 flex items-center justify-center select-none"
                            :class="[
                                item.occupied ? 'cursor-not-allowed opacity-60' : 'cursor-pointer group',
                            ]"
                            @mouseenter="!item.occupied && (hoverDate = item.dateStr)"
                            @mouseleave="hoverDate = ''"
                            @click="selectDate(item.dateStr)"
                        >
                            <!-- Continuous Selection Band -->
                            <div
                                v-if="!item.occupied && isInRange(item.dateStr)"
                                :class="[
                                    'absolute inset-y-1 bg-emerald-100/80 transition-colors',
                                    isStart(item.dateStr) && isEnd(item.dateStr)
                                        ? 'inset-x-1 rounded-md'
                                        : isStart(item.dateStr)
                                        ? 'left-1 right-0 rounded-l-md'
                                        : isEnd(item.dateStr)
                                        ? 'left-0 right-1 rounded-r-md'
                                        : item.dayOfWeek === 0
                                        ? 'left-1 right-0 rounded-l-md'
                                        : item.dayOfWeek === 6
                                        ? 'left-0 right-1 rounded-r-md'
                                        : 'inset-x-0',
                                ]"
                            ></div>

                            <!-- Occupied Band Style (Sudah Ada Periode) -->
                            <div
                                v-if="item.occupied"
                                class="absolute inset-y-1 inset-x-0.5 rounded-md bg-slate-100 border border-dashed border-slate-200 flex items-center justify-center"
                                :title="`Sudah terdaftar di Periode ${item.occupied.nomor_periode}`"
                            ></div>

                            <!-- Date Number / Pill -->
                            <div
                                :class="[
                                    'relative z-10 w-8 h-8 flex flex-col items-center justify-center text-xs transition-all select-none',
                                    item.occupied
                                        ? 'text-slate-400 font-semibold'
                                        : isStart(item.dateStr) || isEnd(item.dateStr)
                                        ? 'bg-[#0e1f38] text-white font-black rounded-lg shadow-xs scale-105'
                                        : isStrictMiddle(item.dateStr)
                                        ? 'text-emerald-950 font-bold'
                                        : item.isCurrentMonth
                                        ? 'text-slate-800 hover:bg-slate-200/80 rounded-lg font-medium'
                                        : 'text-slate-300 font-normal hover:bg-slate-100 rounded-lg',
                                ]"
                            >
                                <span class="leading-none">{{ item.dayNum }}</span>
                                <span
                                    v-if="item.occupied"
                                    class="text-[7.5px] leading-none font-bold text-slate-400 mt-0.5 tracking-tighter"
                                >
                                    P{{ item.occupied.nomor_periode }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── FOOTER & LEGEND ──────────────────────────────────────── -->
            <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 text-xs">
                <!-- Legend -->
                <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-md bg-[#0e1f38]"></span>
                        <span>Terpilih</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-md bg-slate-100 border border-dashed border-slate-300"></span>
                        <span>Sudah Terdaftar (Terkunci)</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-md bg-white border border-slate-200"></span>
                        <span>Bebas Dipilih</span>
                    </span>
                </div>

                <!-- Durasi Info -->
                <div class="flex items-center gap-1.5 font-bold text-slate-700">
                    <span class="text-slate-400 font-normal">Durasi:</span>
                    <span
                        v-if="selectedDaysCount > 0"
                        class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs"
                    >
                        {{ selectedDaysCount }} Hari ({{ Math.ceil(selectedDaysCount / 7) }} Minggu)
                    </span>
                    <span v-else class="text-slate-400 italic font-normal text-xs">Pilih tanggal awal & akhir</span>
                </div>
            </div>
        </div>
    </div>
</template>
