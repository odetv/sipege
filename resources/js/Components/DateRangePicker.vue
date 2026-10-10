<script setup>
import { ref, computed, watch } from "vue";
import { ChevronLeft, ChevronRight, Check, X } from "lucide-vue-next";

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ start: "", end: "" }),
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "apply", "close"]);

const monthNames = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
];

const dayHeaders = ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"];
const years = [2024, 2025, 2026, 2027, 2028, 2029, 2030];

// Selection State
const tempStart = ref(props.modelValue?.start || "");
const tempEnd = ref(props.modelValue?.end || "");
const hoverDate = ref("");

function formatDateStr(year, month, day) {
    const m = String(month + 1).padStart(2, "0");
    const d = String(day).padStart(2, "0");
    return `${year}-${m}-${d}`;
}

// Center initial calendar on start date
const initialDate = props.modelValue?.start ? new Date(props.modelValue.start + "T00:00:00") : new Date();
const currentMonth1 = ref(initialDate.getMonth());
const currentYear1 = ref(initialDate.getFullYear());

// Month 2 is always Month 1 + 1
const currentMonth2 = computed(() => {
    return (currentMonth1.value + 1) % 12;
});

const currentYear2 = computed(() => {
    return currentMonth1.value === 11 ? currentYear1.value + 1 : currentYear1.value;
});

// Navigation chevrons
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

// Build calendar grid for 1 month
function buildMonthDays(monthIdx, yearVal) {
    const firstDay = new Date(yearVal, monthIdx, 1);
    // Monday = 0, ..., Sunday = 6
    const startDayOffset = (firstDay.getDay() + 6) % 7;
    const daysInMonth = new Date(yearVal, monthIdx + 1, 0).getDate();
    const prevMonthDays = new Date(yearVal, monthIdx, 0).getDate();

    const days = [];

    // Leading days from previous month
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
        });
    }

    // Trailing days from next month to complete the 35 or 42 grid
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
        });
    }

    return days;
}

const month1Days = computed(() => buildMonthDays(currentMonth1.value, currentYear1.value));
const month2Days = computed(() => buildMonthDays(currentMonth2.value, currentYear2.value));

// Range calculation (with hover preview)
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

function selectDate(dateStr) {
    if (!tempStart.value || (tempStart.value && tempEnd.value)) {
        tempStart.value = dateStr;
        tempEnd.value = "";
    } else if (tempStart.value && !tempEnd.value) {
        if (dateStr < tempStart.value) {
            tempEnd.value = tempStart.value;
            tempStart.value = dateStr;
        } else {
            tempEnd.value = dateStr;
        }
    }
}


function handleApply() {
    if (!tempStart.value) return;
    const finalEnd = tempEnd.value || tempStart.value;
    const finalStart = tempStart.value <= finalEnd ? tempStart.value : finalEnd;
    const finalEndSorted = tempStart.value <= finalEnd ? finalEnd : tempStart.value;

    emit("update:modelValue", { start: finalStart, end: finalEndSorted });
    emit("apply", { start: finalStart, end: finalEndSorted });
    emit("close");
}

function handleClose() {
    tempStart.value = props.modelValue?.start || "";
    tempEnd.value = props.modelValue?.end || "";
    emit("close");
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
    if (!tempStart.value) return 0;
    const end = tempEnd.value || tempStart.value;
    const d1 = new Date(tempStart.value + "T00:00:00");
    const d2 = new Date(end + "T00:00:00");
    const diffTime = Math.abs(d2 - d1);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
});
</script>

<template>
    <div
        class="bg-white rounded-2xl border border-slate-200/90 shadow-2xl p-3.5 sm:p-5 select-none w-full max-w-[340px] sm:max-w-none sm:w-[650px] md:w-[680px] text-slate-800 animate-in fade-in zoom-in-95 duration-150"
    >
        <!-- ─── TWO MONTHS GRID CONTAINER (1 Col di HP, 2 Col di Desktop) ──── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-7">
            <!-- ─── BULAN 1 (KIRI) ───────────────────────────────────────── -->
            <div class="w-full">
                <!-- Header Bulan 1: Dropdown Bulan, Tahun & Prev Chevron -->
                <div class="flex items-center justify-between gap-1 pb-3 border-b border-slate-100">
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

                    <!-- Nav Chevrons (Di HP ada Prev & Next, di Desktop hanya Prev) -->
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="prevMonth"
                            class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
                            title="Bulan Sebelumnya"
                        >
                            <ChevronLeft class="h-3.5 w-3.5" />
                        </button>
                        <!-- Next chevron di HP agar bisa navigasi saat 1 kolom -->
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

                <!-- Day Headers: Sen, Sel, Rab, Kam, Jum, Sab, Min -->
                <div class="grid grid-cols-7 text-center pt-2.5 pb-1">
                    <div
                        v-for="day in dayHeaders"
                        :key="day"
                        class="h-7 flex items-center justify-center text-[11px] font-semibold text-slate-500"
                    >
                        {{ day }}
                    </div>
                </div>

                <!-- Days Grid Bulan 1 -->
                <div class="grid grid-cols-7 text-center text-xs">
                    <div
                        v-for="(item, idx) in month1Days"
                        :key="idx"
                        class="relative h-9 flex items-center justify-center cursor-pointer group"
                        @mouseenter="hoverDate = item.dateStr"
                        @mouseleave="hoverDate = ''"
                        @click="selectDate(item.dateStr)"
                    >
                        <!-- Continuous Soft Shaded Band -->
                        <div
                            v-if="isInRange(item.dateStr)"
                            :class="[
                                'absolute inset-y-1 bg-slate-100 transition-colors',
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

                        <!-- Date Pill Endpoint / Number -->
                        <div
                            :class="[
                                'relative z-10 w-8 h-8 flex items-center justify-center text-xs transition-all select-none',
                                isStart(item.dateStr) || isEnd(item.dateStr)
                                    ? 'bg-[#0e1f38] text-white font-black rounded-lg shadow-xs scale-105'
                                    : isStrictMiddle(item.dateStr)
                                    ? 'text-slate-900 font-bold'
                                    : item.isCurrentMonth
                                    ? 'text-slate-800 hover:bg-slate-200/80 rounded-lg font-medium'
                                    : 'text-slate-400/80 font-normal hover:bg-slate-100 rounded-lg',
                            ]"
                        >
                            {{ item.dayNum }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── BULAN 2 (KANAN - Hanya Tampil di Tablet & Desktop) ──────── -->
            <div class="hidden sm:block w-full">
                <!-- Header Bulan 2: Dropdown Bulan, Tahun & Next Chevron -->
                <div class="flex items-center justify-between gap-1 pb-3 border-b border-slate-100">
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

                    <!-- Next Button -->
                    <button
                        type="button"
                        @click="nextMonth"
                        class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
                        title="Bulan Berikutnya"
                    >
                        <ChevronRight class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Day Headers: Sen, Sel, Rab, Kam, Jum, Sab, Min -->
                <div class="grid grid-cols-7 text-center pt-2.5 pb-1">
                    <div
                        v-for="day in dayHeaders"
                        :key="day"
                        class="h-7 flex items-center justify-center text-[11px] font-semibold text-slate-500"
                    >
                        {{ day }}
                    </div>
                </div>

                <!-- Days Grid Bulan 2 -->
                <div class="grid grid-cols-7 text-center text-xs">
                    <div
                        v-for="(item, idx) in month2Days"
                        :key="idx"
                        class="relative h-9 flex items-center justify-center cursor-pointer group"
                        @mouseenter="hoverDate = item.dateStr"
                        @mouseleave="hoverDate = ''"
                        @click="selectDate(item.dateStr)"
                    >
                        <!-- Continuous Soft Shaded Band -->
                        <div
                            v-if="isInRange(item.dateStr)"
                            :class="[
                                'absolute inset-y-1 bg-slate-100 transition-colors',
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

                        <!-- Date Pill Endpoint / Number -->
                        <div
                            :class="[
                                'relative z-10 w-8 h-8 flex items-center justify-center text-xs transition-all select-none',
                                isStart(item.dateStr) || isEnd(item.dateStr)
                                    ? 'bg-[#0e1f38] text-white font-black rounded-lg shadow-xs scale-105'
                                    : isStrictMiddle(item.dateStr)
                                    ? 'text-slate-900 font-bold'
                                    : item.isCurrentMonth
                                    ? 'text-slate-800 hover:bg-slate-200/80 rounded-lg font-medium'
                                    : 'text-slate-400/80 font-normal hover:bg-slate-100 rounded-lg',
                            ]"
                        >
                            {{ item.dayNum }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── FOOTER BAR (AKSI) ────────────────────────────── -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <!-- Status Info -->
            <div class="flex items-center justify-between sm:justify-start gap-2 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500 font-medium">Rentang:</span>
                    <span
                        v-if="selectedDaysCount > 0"
                        class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold"
                    >
                        {{ selectedDaysCount }} Hari Terpilih
                    </span>
                    <span v-else class="text-slate-400 italic">Pilih tanggal awal & akhir</span>
                </div>
            </div>

                <!-- Action Buttons (Icon Saja) -->
                <div class="flex items-center gap-1.5 w-auto">
                    <button
                        type="button"
                        @click="handleClose"
                        class="p-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors cursor-pointer flex items-center justify-center shadow-2xs"
                        title="Batal"
                    >
                        <X class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        @click="handleApply"
                        :disabled="!tempStart"
                        class="p-2 rounded-xl bg-[#0e1f38] hover:bg-slate-800 text-white shadow-2xs transition-colors cursor-pointer disabled:opacity-50 flex items-center justify-center"
                        title="Terapkan Rentang"
                    >
                        <Check class="h-4 w-4 text-emerald-400" />
                    </button>
                </div>
            </div>
        </div>
</template>
