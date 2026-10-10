<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Modal from "@/Components/Modal.vue";
import {
    FileSpreadsheet,
    Calendar,
    Sparkles,
    ShieldAlert,
    AlertTriangle,
    AlertCircle,
    CheckCircle2,
    Check,
    Clock,
    Users,
    UserCheck,
    UserX,
    Edit3,
    RotateCcw,
    Plus,
    Trash2,
    Save,
    ArrowRight,
    FileText,
    School,
    X,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    ChefHat,
    Eye,
    Lightbulb,
    Lock,
    ClipboardPaste,
    Package,
    HeartPulse,
    FlaskConical,
    UtensilsCrossed,
    ShieldCheck,
    Layers,
    Info,
    PlusCircle,
} from "lucide-vue-next";
import {
    ALERGI_OPTIONS,
    checkTextMatchesAllergen,
    getSubKategoriByKategori,
    getJenisPorsiBySubKategori,
    sortRincianByKategori,
} from "@/Services/penerimaManfaatConfig";
import WorkOrderManualEditModal from "./Partials/WorkOrderManualEditModal.vue";
import GiziPasteModal from "@/Components/GiziPasteModal.vue";
import {
    handleNutritionPasteEvent,
    quickPasteNutritionFromClipboard,
    applyNutritionValues,
} from "@/Services/giziPasteHelper";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    workOrdersList: {
        type: Array,
        default: () => [],
    },
    activeTab: {
        type: String,
        default: "perencanaan", // 'perencanaan' | 'daftar'
    },
    editWorkOrder: {
        type: Object,
        default: null,
    },
});

const currentTab = ref(props.activeTab || "perencanaan");

// ─── STATE PERENCANAAN PRODUKSI (STEP 1) ────────────────────────────────────
const isEditMode = ref(false);
const editingWoId = ref(null);
const metodeWo = ref("sistem"); // 'sistem' | 'manual'

// Tab Khusus Mode Manual: 'perencanaan' | 'formula'
const activeManualTab = ref(
    typeof window !== "undefined" &&
        new URLSearchParams(window.location.search).get("tab") === "formula"
        ? "formula"
        : "perencanaan",
);

// State Kandungan Gizi (AKG) Mode Manual
const akgPk = ref({
    energi: 0,
    protein: 0,
    lemak: 0,
    karbohidrat: 0,
    serat: 0,
});
const akgPb = ref({
    energi: 0,
    protein: 0,
    lemak: 0,
    karbohidrat: 0,
    serat: 0,
});
const akgAlergi = ref({});
const catatanResep = ref("");
const activeTabGiziFormula = ref("normal"); // 'normal' | 'alergi'
const selectedAlergiFormulaTab = ref("");

const isSubmitting = ref(false);
const showSubmitSuccessAlert = ref(false);
const submitAlertMessage = ref("");
const showSubmitErrorAlert = ref(false);
const submitErrorMessage = ref("");
let submitErrorTimer = null;
let submitSuccessTimer = null;

function triggerSubmitSuccess(msg) {
    submitAlertMessage.value = msg;
    showSubmitSuccessAlert.value = true;
    showSubmitErrorAlert.value = false;
    if (submitSuccessTimer) clearTimeout(submitSuccessTimer);
    submitSuccessTimer = setTimeout(() => {
        showSubmitSuccessAlert.value = false;
    }, 4500);
}

function triggerSubmitError(msg, shouldScroll = false) {
    submitErrorMessage.value = msg;
    showSubmitErrorAlert.value = true;
    showSubmitSuccessAlert.value = false;
    if (submitErrorTimer) clearTimeout(submitErrorTimer);
    submitErrorTimer = setTimeout(() => {
        showSubmitErrorAlert.value = false;
    }, 6000);
    if (shouldScroll) {
        scrollToFirstError();
    }
}

// Map seluruh Work Order yang sudah ada per tanggal (termasuk status Draft).
// Aturan mutlak: 1 tanggal HANYA boleh ada 1 Work Order.
const existingWoDatesMap = computed(() => {
    const map = {};
    const currentWoId = editingWoId.value;

    (props.workOrdersList || []).forEach((w) => {
        if (!w || !w.tanggal_distribusi) return;
        const tgl =
            typeof w.tanggal_distribusi === "string"
                ? w.tanggal_distribusi.substring(0, 10)
                : new Date(w.tanggal_distribusi).toISOString().substring(0, 10);

        if (currentWoId && w.id === currentWoId) return;

        map[tgl] = {
            id: w.id,
            nomor_wo: w.nomor_wo,
            nama_menu: w.nama_menu || "Menu MBG",
            status: w.status || "Draft",
            tanggal: tgl,
        };
    });
    return map;
});

function isDateTaken(dateStr) {
    if (!dateStr) return false;
    return !!existingWoDatesMap.value[dateStr];
}

function getTakenWoInfo(dateStr) {
    if (!dateStr) return null;
    return existingWoDatesMap.value[dateStr] || null;
}

function getInitialAvailableDate() {
    const today = new Date();
    for (let offset = 0; offset <= 60; offset++) {
        const target = new Date();
        target.setDate(today.getDate() + offset);
        const y = target.getFullYear();
        const m = String(target.getMonth() + 1).padStart(2, "0");
        const d = String(target.getDate()).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        if (!isDateTaken(dateStr)) {
            return dateStr;
        }
    }
    return today.toISOString().split("T")[0];
}

const tanggalRencana = ref(getInitialAvailableDate());
const currentDateConflict = computed(() =>
    getTakenWoInfo(tanggalRencana.value),
);

const todayStr = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
});

const tomorrowStr = computed(() => {
    const tom = new Date();
    tom.setDate(tom.getDate() + 1);
    const y = tom.getFullYear();
    const m = String(tom.getMonth() + 1).padStart(2, "0");
    const d = String(tom.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
});

const isTodayTaken = computed(() => isDateTaken(todayStr.value));
const isTomorrowTaken = computed(() => isDateTaken(tomorrowStr.value));

// Popover Kalender Picker Interaktif dengan Indikator WO
const showDatePickerPopover = ref(false);
const datePickerContainerRef = ref(null);
const pickerCalendarYear = ref(new Date().getFullYear());
const pickerCalendarMonth = ref(new Date().getMonth());

const NAMA_BULAN_PICKER = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember",
];

const pickerMonthLabel = computed(() => {
    return `${NAMA_BULAN_PICKER[pickerCalendarMonth.value]} ${pickerCalendarYear.value}`;
});

function syncPickerMonthWithSelected() {
    if (tanggalRencana.value) {
        const parts = tanggalRencana.value.split("-").map(Number);
        if (parts[0] && parts[1]) {
            pickerCalendarYear.value = parts[0];
            pickerCalendarMonth.value = parts[1] - 1;
        }
    }
}

function toggleDatePickerPopover() {
    showDatePickerPopover.value = !showDatePickerPopover.value;
    if (showDatePickerPopover.value) {
        syncPickerMonthWithSelected();
    }
}

function prevPickerMonth() {
    if (pickerCalendarMonth.value === 0) {
        pickerCalendarMonth.value = 11;
        pickerCalendarYear.value -= 1;
    } else {
        pickerCalendarMonth.value -= 1;
    }
}

function nextPickerMonth() {
    if (pickerCalendarMonth.value === 11) {
        pickerCalendarMonth.value = 0;
        pickerCalendarYear.value += 1;
    } else {
        pickerCalendarMonth.value += 1;
    }
}

const pickerCalendarDays = computed(() => {
    const year = pickerCalendarYear.value;
    const month = pickerCalendarMonth.value;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    let startDayOfWeek = firstDay.getDay() - 1;
    if (startDayOfWeek === -1) startDayOfWeek = 6;

    const days = [];
    const today = todayStr.value;

    // Previous month padding
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const dayNum = prevMonthLastDay - i;
        const prevDate = new Date(year, month - 1, dayNum);
        const y = prevDate.getFullYear();
        const m = String(prevDate.getMonth() + 1).padStart(2, "0");
        const d = String(dayNum).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const taken = getTakenWoInfo(dateStr);

        days.push({
            dateStr,
            dayNumber: dayNum,
            isCurrentMonth: false,
            isToday: dateStr === today,
            isSelected: dateStr === tanggalRencana.value,
            isTaken: !!taken,
            takenInfo: taken,
        });
    }

    // Current month days
    const totalDays = lastDay.getDate();
    for (let dayNum = 1; dayNum <= totalDays; dayNum++) {
        const y = year;
        const m = String(month + 1).padStart(2, "0");
        const d = String(dayNum).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const taken = getTakenWoInfo(dateStr);

        days.push({
            dateStr,
            dayNumber: dayNum,
            isCurrentMonth: true,
            isToday: dateStr === today,
            isSelected: dateStr === tanggalRencana.value,
            isTaken: !!taken,
            takenInfo: taken,
        });
    }

    // Next month padding to complete grid
    const remaining = (7 - (days.length % 7)) % 7;
    for (let i = 1; i <= remaining; i++) {
        const nextDate = new Date(year, month + 1, i);
        const y = nextDate.getFullYear();
        const m = String(nextDate.getMonth() + 1).padStart(2, "0");
        const d = String(i).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const taken = getTakenWoInfo(dateStr);

        days.push({
            dateStr,
            dayNumber: i,
            isCurrentMonth: false,
            isToday: dateStr === today,
            isSelected: dateStr === tanggalRencana.value,
            isTaken: !!taken,
            takenInfo: taken,
        });
    }

    return days;
});

function handleSelectPickerDate(day) {
    if (day.isTaken) {
        triggerSubmitError(
            `Tanggal ${formatTanggalIndo(day.dateStr)} sudah memiliki Work Order: "${day.takenInfo.nama_menu}" (${day.takenInfo.nomor_wo} • Status: ${day.takenInfo.status}). 1 tanggal hanya diperbolehkan 1 Work Order.`,
        );
        return;
    }
    tanggalRencana.value = day.dateStr;
    clearError("tanggalRencana");
    showDatePickerPopover.value = false;
}

function onNativeDateChange() {
    clearError("tanggalRencana");
    if (isDateTaken(tanggalRencana.value)) {
        const info = getTakenWoInfo(tanggalRencana.value);
        validationErrors.value.tanggalRencana = `Tanggal ${formatTanggalIndo(tanggalRencana.value)} sudah memiliki Work Order ("${info.nama_menu}"). 1 tanggal hanya boleh 1 Work Order.`;
    }
}

const woNo = computed(() => {
    return (
        "WO-MBG-" +
        (tanggalRencana.value
            ? tanggalRencana.value.replace(/-/g, "")
            : new Date().toISOString().slice(0, 10).replace(/-/g, ""))
    );
});

// Default kosong semua, tapi wajib diisi sebelum lanjut
const namaMenuAktif = ref("");
const subMenuKeys = ref([
    "sub_menu_1",
    "sub_menu_2",
    "sub_menu_3",
    "sub_menu_4",
    "sub_menu_5",
]);
const subMenuKomponen = ref({
    sub_menu_1: "",
    sub_menu_2: "",
    sub_menu_3: "",
    sub_menu_4: "",
    sub_menu_5: "",
});

// Opsi Menu Pengganti Alergi per masing-masing Sub Menu (Opsional & Dinamis)
const subMenuAlergi = ref({
    sub_menu_1: [],
    sub_menu_2: [],
    sub_menu_3: [],
    sub_menu_4: [],
    sub_menu_5: [],
});

function getSubMenuDefaultPlaceholder(index) {
    const placeholders = [
        "Karbohidrat",
        "Protein Hewani",
        "Protein Nabati",
        "Sayur",
        "Buah",
    ];
    return placeholders[index] || `Tambahan Sub Menu ${index + 1}`;
}

function addSubMenu() {
    const nextIdx = subMenuKeys.value.length + 1;
    const newKey = `sub_menu_${nextIdx}`;
    subMenuKeys.value.push(newKey);
    subMenuKomponen.value[newKey] = "";
    if (!subMenuAlergi.value[newKey]) {
        subMenuAlergi.value[newKey] = [];
    }
}

function removeSubMenu(index) {
    if (subMenuKeys.value.length <= 5) return;

    const values = subMenuKeys.value.map((k) => subMenuKomponen.value[k] || "");
    const allergies = subMenuKeys.value.map(
        (k) => subMenuAlergi.value[k] || [],
    );

    values.splice(index, 1);
    allergies.splice(index, 1);

    const newKeys = [];
    const newKomponen = {};
    const newAlergi = {};

    values.forEach((v, idx) => {
        const k = `sub_menu_${idx + 1}`;
        newKeys.push(k);
        newKomponen[k] = v;
        newAlergi[k] = allergies[idx] || [];
    });

    Object.keys(validationErrors.value).forEach((k) => {
        if (k.startsWith("sub_menu_") || k.startsWith("alergi_sub_menu_")) {
            delete validationErrors.value[k];
        }
    });

    subMenuKeys.value = newKeys;
    subMenuKomponen.value = newKomponen;
    subMenuAlergi.value = newAlergi;
}

// State & Method Tempel (Paste) List Sub Menu
const showModalPasteSubMenu = ref(false);
const pasteModalInputText = ref("");
const pasteToastMessage = ref("");
let pasteToastTimer = null;

function triggerPasteToast(msg) {
    pasteToastMessage.value = msg;
    if (pasteToastTimer) clearTimeout(pasteToastTimer);
    pasteToastTimer = setTimeout(() => {
        pasteToastMessage.value = "";
    }, 3500);
}

function parsePastedSubMenuText(text) {
    if (!text || typeof text !== "string") return [];

    let rawItems = [];
    if (text.includes("\n") || text.includes("\r")) {
        const lines = text.split(/\r?\n/);
        lines.forEach((line) => {
            if (line.includes("\t")) {
                line.split("\t").forEach((cell) => rawItems.push(cell));
            } else {
                rawItems.push(line);
            }
        });
    } else if (text.includes("\t")) {
        rawItems = text.split("\t");
    } else {
        rawItems = [text];
    }

    return rawItems
        .map((item) => {
            let str = item.trim();
            // Bersihkan format numbering seperti "1.", "1)", "1 -", atau bullets "- ", "* ", "• "
            str = str
                .replace(/^([0-9]+[\.\)\-]\s*|[\-\*\•\–\—]\s*)/, "")
                .trim();
            return str;
        })
        .filter((item) => item.length > 0);
}

function applySubMenuItems(items, startIdx = 0) {
    if (!items || items.length === 0) return 0;

    let appliedCount = 0;
    items.forEach((val, i) => {
        const targetIdx = startIdx + i;
        while (targetIdx >= subMenuKeys.value.length) {
            addSubMenu();
        }
        const key = subMenuKeys.value[targetIdx];
        if (key) {
            subMenuKomponen.value[key] = val;
            clearError(key);
            appliedCount++;
        }
    });

    triggerPasteToast(`Berhasil menempel ${appliedCount} Sub Menu!`);
    return appliedCount;
}

function handlePasteSubMenu(event, startIdx) {
    const clipboardData = event.clipboardData || window.clipboardData;
    if (!clipboardData) return;

    const pastedText = clipboardData.getData("text");
    if (!pastedText) return;

    const parsedItems = parsePastedSubMenuText(pastedText);

    // Jika lebih dari 1 item, intercept paste default browser dan isi berurutan
    if (parsedItems.length > 1) {
        event.preventDefault();
        applySubMenuItems(parsedItems, startIdx);
    }
}

async function handleQuickPasteButtonClick() {
    try {
        if (navigator.clipboard && navigator.clipboard.readText) {
            const clipText = await navigator.clipboard.readText();
            if (clipText && clipText.trim()) {
                const items = parsePastedSubMenuText(clipText);
                if (items.length > 1) {
                    applySubMenuItems(items, 0);
                    return;
                } else if (items.length === 1) {
                    pasteModalInputText.value = clipText;
                    showModalPasteSubMenu.value = true;
                    return;
                }
            }
        }
    } catch (err) {
        console.warn(
            "Clipboard read blocked or not permitted, opening modal fallback:",
            err,
        );
    }

    pasteModalInputText.value = "";
    showModalPasteSubMenu.value = true;
}

function handleApplyPasteFromModal() {
    if (!pasteModalInputText.value.trim()) {
        showModalPasteSubMenu.value = false;
        return;
    }
    const items = parsePastedSubMenuText(pasteModalInputText.value);
    if (items.length > 0) {
        applySubMenuItems(items, 0);
    }
    pasteModalInputText.value = "";
    showModalPasteSubMenu.value = false;
}

// ─── STATE & METHOD TEMPEL (PASTE) GIZI (AKG) REUSABLE ───────────────────────
const showGiziPasteModal = ref(false);
const giziPasteTargetObj = ref(null);
const giziPasteTargetTitle = ref("");
const giziPasteInitialText = ref("");

function openGiziPasteModal(targetObj, title, initialText = "") {
    giziPasteTargetObj.value = targetObj;
    giziPasteTargetTitle.value = title;
    giziPasteInitialText.value = initialText;
    showGiziPasteModal.value = true;
}

function handleQuickPasteGizi(targetObj, title) {
    quickPasteNutritionFromClipboard(
        targetObj,
        (count) => {
            triggerPasteToast(
                `Berhasil menempel ${count} nilai zat gizi pada ${title}!`,
            );
        },
        (clipText) => {
            openGiziPasteModal(targetObj, title, clipText);
        },
    );
}

function onPasteGiziInput(event, targetObj, key, title) {
    handleNutritionPasteEvent(event, targetObj, key, (count) => {
        triggerPasteToast(
            `Berhasil menempel ${count} nilai zat gizi pada ${title}!`,
        );
    });
}

function handleApplyGiziFromModal(parsedValues) {
    if (giziPasteTargetObj.value) {
        const applied = applyNutritionValues(
            giziPasteTargetObj.value,
            parsedValues,
        );
        triggerPasteToast(
            `Berhasil menerapkan ${applied} nilai zat gizi pada ${giziPasteTargetTitle.value}!`,
        );
    }
    showGiziPasteModal.value = false;
}

// Sumber kelompok sasaran yang aktif & konsisten
const activeKelompoksSource = computed(() => {
    if (
        Array.isArray(woKelompokList.value) &&
        woKelompokList.value.length > 0
    ) {
        return woKelompokList.value;
    }
    return props.kelompokList || [];
});

function extractCleanAlergi(val) {
    if (!val) return "";
    if (typeof val === "string") return val.trim();
    if (typeof val === "number") return String(val).trim();
    if (Array.isArray(val)) {
        return val.map(extractCleanAlergi).filter(Boolean).join(", ");
    }
    if (typeof val === "object") {
        const candidate =
            val.jenis_alergi ||
            val.alergen ||
            val.nama ||
            val.label ||
            val.nama_alergi ||
            val.name;
        if (typeof candidate === "string") return candidate.trim();
        if (candidate && typeof candidate === "object") {
            return extractCleanAlergi(candidate);
        }
        return "";
    }
    return String(val).trim();
}

// Opsi Alergi khusus yang benar-benar ada/tercatat pada data Penerima Manfaat (PM)
const availableAlergiOptions = computed(() => {
    const set = new Set();
    activeKelompoksSource.value.forEach((k) => {
        if (Array.isArray(k.keterangan_alergi)) {
            k.keterangan_alergi.forEach((item) => {
                const j = extractCleanAlergi(item);
                const pk = Number(item?.porsi_kecil) || 0;
                const pb = Number(item?.porsi_besar) || 0;
                const total = typeof item === "string" ? 1 : pk + pb;
                if (j && total > 0) {
                    set.add(j);
                }
            });
        }
    });
    return Array.from(set);
});

// Master opsi alergen untuk dropdown variasi alergi sub menu (prioritaskan alergi yang terdata di PM, lalu semua master ALERGI_OPTIONS)
const masterAlergenOptions = computed(() => {
    const list = [];
    const seen = new Set();

    // 1. Prioritaskan alergi yang terdata pada PM kelompok aktif (jika ada)
    if (
        availableAlergiOptions.value &&
        availableAlergiOptions.value.length > 0
    ) {
        availableAlergiOptions.value.forEach((j) => {
            if (j && !seen.has(j)) {
                seen.add(j);
                list.push({
                    value: j,
                    label: `⚠️ ${j} (Terdata di PM)`,
                    isFromPm: true,
                });
            }
        });
    }

    // 2. Tambahkan seluruh master baku dari ALERGI_OPTIONS
    if (Array.isArray(ALERGI_OPTIONS)) {
        ALERGI_OPTIONS.forEach((opt) => {
            if (opt && opt.value && !seen.has(opt.value)) {
                seen.add(opt.value);
                list.push({
                    value: opt.value,
                    label: opt.label,
                    isFromPm: false,
                });
            }
        });
    }

    return list;
});

// Rekapitulasi porsi & jumlah PM terdampak per jenis alergi
const pmAlergiStats = computed(() => {
    const map = {};
    activeKelompoksSource.value.forEach((k) => {
        if (Array.isArray(k.keterangan_alergi)) {
            k.keterangan_alergi.forEach((item) => {
                const cleanJenis = extractCleanAlergi(item);
                if (!cleanJenis) return;
                const pk = Number(item?.porsi_kecil) || 0;
                const pb = Number(item?.porsi_besar) || 0;
                const totalPorsi = typeof item === "string" ? 1 : pk + pb;

                if (!map[cleanJenis]) {
                    map[cleanJenis] = {
                        jenis: cleanJenis,
                        total_pm: 0,
                        pk: 0,
                        pb: 0,
                        kelompokNames: [],
                    };
                }
                map[cleanJenis].total_pm += totalPorsi;
                map[cleanJenis].pk += pk;
                map[cleanJenis].pb += pb;
                if (
                    k.nama_kelompok &&
                    !map[cleanJenis].kelompokNames.includes(k.nama_kelompok)
                ) {
                    map[cleanJenis].kelompokNames.push(k.nama_kelompok);
                }
            });
        }
    });
    return map;
});

function checkTextContainsAllergen(text, allergenName) {
    return checkTextMatchesAllergen(text, allergenName);
}

function detectAllergensInText(text) {
    if (!text || !text.trim()) return [];
    const detected = [];
    const stats = pmAlergiStats.value;
    for (const [jenis, data] of Object.entries(stats)) {
        if (checkTextContainsAllergen(text, jenis)) {
            detected.push(data);
        }
    }
    return detected;
}

const detectedAllergensMenuUtama = computed(() => {
    return detectAllergensInText(namaMenuAktif.value);
});

const detectedAllergensPerSubMenu = computed(() => {
    const res = {};
    subMenuKeys.value.forEach((k) => {
        res[k] = detectAllergensInText(subMenuKomponen.value[k]);
    });
    return res;
});

function addPenggantiAlergiWithPreset(subKey, jenisAlergiDefault = "") {
    if (!subMenuAlergi.value[subKey]) {
        subMenuAlergi.value[subKey] = [];
    }
    const exists = subMenuAlergi.value[subKey].some(
        (p) => p.jenis_alergi === jenisAlergiDefault,
    );
    if (!exists) {
        subMenuAlergi.value[subKey].push({
            jenis_alergi: jenisAlergiDefault,
            menu_pengganti: "",
        });
    }
}

function formatAllergenDisplay(allergen) {
    if (!allergen) return "Alergi";
    const str = String(allergen).trim();
    if (str.toLowerCase().startsWith("alergi")) {
        return str;
    }
    return `Alergi ${str}`;
}

const realTimeAllergyAlerts = computed(() => {
    const alerts = [];
    subMenuKeys.value.forEach((k, idx) => {
        const smVal =
            typeof subMenuKomponen.value[k] === "string"
                ? subMenuKomponen.value[k].trim()
                : "";
        if (smVal) {
            const detected = detectAllergensInText(smVal);
            detected.forEach((al) => {
                const alJenis = extractCleanAlergi(al?.jenis);
                const existing = (subMenuAlergi.value[k] || []).find((p) => {
                    const pJenis = extractCleanAlergi(p?.jenis_alergi);
                    if (!pJenis || !alJenis) return false;
                    return (
                        pJenis === alJenis ||
                        alJenis.toLowerCase().includes(pJenis.toLowerCase()) ||
                        pJenis.toLowerCase().includes(alJenis.toLowerCase())
                    );
                });
                alerts.push({
                    subKey: k,
                    subLabel: `Sub Menu ${idx + 1}`,
                    menuName: smVal,
                    allergen: al.jenis,
                    totalPm: al.total_pm,
                    pk: al.pk,
                    pb: al.pb,
                    hasReplacement: !!(
                        existing &&
                        typeof existing.menu_pengganti === "string" &&
                        existing.menu_pengganti.trim()
                    ),
                    replacementName: existing?.menu_pengganti || "",
                });
            });
        }
    });
    return alerts;
});

function addPenggantiAlergi(subKey) {
    if (!subMenuAlergi.value[subKey]) {
        subMenuAlergi.value[subKey] = [];
    }
    subMenuAlergi.value[subKey].push({
        jenis_alergi: "",
        menu_pengganti: "",
    });
}

function removePenggantiAlergi(subKey, index) {
    if (subMenuAlergi.value[subKey]) {
        subMenuAlergi.value[subKey].splice(index, 1);
        clearError(`alergi_${subKey}_${index}_jenis`);
        clearError(`alergi_${subKey}_${index}_menu`);
    }
}

// ─── WAKTU KEGIATAN OPERASIONAL SPPG ────────────────────────────────────────
const DEFAULT_JADWAL_OPERASIONAL = {
    persiapan: {
        nama: "Persiapan",
        mulai: "19:00",
        selesai: "03:00",
        deskripsi:
            "Pukul 19.00 s.d 03.00 • Persiapan bahan baku, sortasi & bumbu",
    },
    pengolahan: {
        nama: "Pengolahan",
        mulai: "02:00",
        selesai: "10:00",
        deskripsi:
            "Pukul 02.00 s.d 10.00 • Pengolahan & pemasakan seluruh menu",
    },
    pemorsian: {
        nama: "Pemorsian",
        mulai: "04:00",
        selesai: "12:00",
        deskripsi: "Pukul 04.00 s.d 12.00 • Pengepakan & pemorsian ke ompreng",
    },
    uji_organolaptik: {
        nama: "Uji Organolaptik",
        mulai: "05:00",
        selesai: "06:00",
        deskripsi:
            "Pukul 05.00 s.d 06.00 • Uji sensori rasa, aroma, tekstur & suhu",
    },
    distribusi: {
        nama: "Distribusi",
        mulai: "06:00",
        selesai: "14:00",
        deskripsi:
            "Pukul 06.00 s.d 14.00 • Pengantaran makanan ke kelompok sasaran",
    },
    pencucian_ompreng: {
        nama: "Pencucian Ompreng",
        mulai: "12:00",
        selesai: "20:00",
        deskripsi:
            "Pukul 12.00 s.d 20.00 • Penerimaan kembali & sanitasi ompreng",
    },
};

const jadwalOperasionalList = {
    persiapan: {
        no: 1,
        nama: "Persiapan",
        defaultMulai: "19:00",
        defaultSelesai: "03:00",
        deskripsi:
            "Pukul 19.00 s.d 03.00 • Persiapan bahan baku, sortasi & bumbu",
    },
    pengolahan: {
        no: 2,
        nama: "Pengolahan",
        defaultMulai: "01:00",
        defaultSelesai: "09:00",
        deskripsi:
            "Pukul 01.00 s.d 09.00 • Pengolahan & pemasakan seluruh menu",
    },
    pemorsian: {
        no: 3,
        nama: "Pemorsian",
        defaultMulai: "04:00",
        defaultSelesai: "12:00",
        deskripsi: "Pukul 04.00 s.d 12.00 • Pengepakan & pemorsian ke ompreng",
    },
    uji_organolaptik: {
        no: 4,
        nama: "Uji Organolaptik",
        defaultMulai: "05:00",
        defaultSelesai: "06:00",
        deskripsi:
            "Pukul 05.00 s.d 06.00 • Uji sensori rasa, aroma, tekstur & suhu",
    },
    distribusi: {
        no: 5,
        nama: "Distribusi",
        defaultMulai: "06:00",
        defaultSelesai: "14:00",
        deskripsi:
            "Pukul 06.00 s.d 14.00 • Pengantaran makanan ke kelompok sasaran",
    },
    pencucian_ompreng: {
        no: 6,
        nama: "Pencucian Ompreng",
        defaultMulai: "12:00",
        defaultSelesai: "20:00",
        deskripsi:
            "Pukul 12.00 s.d 20.00 • Penerimaan kembali & sanitasi ompreng",
    },
};

const EMPTY_JADWAL_OPERASIONAL = {
    persiapan: { mulai: "", selesai: "" },
    pengolahan: { mulai: "", selesai: "" },
    pemorsian: { mulai: "", selesai: "" },
    uji_organolaptik: { mulai: "", selesai: "" },
    distribusi: { mulai: "", selesai: "" },
    pencucian_ompreng: { mulai: "", selesai: "" },
};

// Default kosong seluruhnya
const jadwalOperasional = ref(
    JSON.parse(JSON.stringify(EMPTY_JADWAL_OPERASIONAL)),
);

const totalJadwalTerisi = computed(() => {
    let count = 0;
    Object.keys(jadwalOperasionalList).forEach((k) => {
        const item = jadwalOperasional.value?.[k];
        if (item?.mulai && item?.selesai) {
            count++;
        }
    });
    return count;
});

function resetJadwalOperasionalToEmpty() {
    jadwalOperasional.value = JSON.parse(
        JSON.stringify(EMPTY_JADWAL_OPERASIONAL),
    );
}

function resetJadwalOperasionalToDefault() {
    jadwalOperasional.value = JSON.parse(
        JSON.stringify(DEFAULT_JADWAL_OPERASIONAL),
    );
    clearError("jadwal_operasional");
    Object.keys(jadwalOperasionalList).forEach((k) => {
        clearError("jadwal_" + k);
        clearError("jadwal_" + k + "_mulai");
        clearError("jadwal_" + k + "_selesai");
    });
}

// ─── DATA KELOMPOK PENERIMA MANFAAT (PM) ───────────────────────────────────
function normalizeAlergiItems(data, pkAlergi = 0, pbAlergi = 0) {
    if (Array.isArray(data) && data.length > 0) {
        return data.map((item) => {
            if (typeof item === "string") {
                return {
                    jenis_alergi: item,
                    porsi_kecil: Number(pkAlergi) || 0,
                    porsi_besar: Number(pbAlergi) || 0,
                };
            }
            return {
                jenis_alergi: item.jenis_alergi || "Lainnya",
                porsi_kecil: Number(item.porsi_kecil) || 0,
                porsi_besar: Number(item.porsi_besar) || 0,
            };
        });
    }
    if (Number(pkAlergi) > 0 || Number(pbAlergi) > 0) {
        return [
            {
                jenis_alergi: "Alergi Khusus",
                porsi_kecil: Number(pkAlergi) || 0,
                porsi_besar: Number(pbAlergi) || 0,
            },
        ];
    }
    return [];
}

function normalizeKelompokForWo(k) {
    const subCats = getSubKategoriByKategori(k.kategori);
    let rincianArr = [];
    if (Array.isArray(k.rincian) && k.rincian.length > 0) {
        rincianArr = k.rincian.map((r) => ({
            id: r.id,
            sub_kategori: r.sub_kategori,
            jenis_porsi:
                r.jenis_porsi ||
                getJenisPorsiBySubKategori(r.sub_kategori, k.kategori),
            jumlah_laki_laki: Number(r.jumlah_laki_laki) || 0,
            jumlah_perempuan: Number(r.jumlah_perempuan) || 0,
            total:
                (Number(r.jumlah_laki_laki) || 0) +
                (Number(r.jumlah_perempuan) || 0),
        }));
    } else {
        rincianArr = subCats.map((sub) => {
            const jp = getJenisPorsiBySubKategori(sub, k.kategori);
            return {
                id: null,
                sub_kategori: sub,
                jenis_porsi: jp,
                jumlah_laki_laki: 0,
                jumlah_perempuan: 0,
                total: 0,
            };
        });
    }

    const calcPK = rincianArr
        .filter((r) => r.jenis_porsi === "Porsi Kecil")
        .reduce(
            (sum, r) => sum + (r.jumlah_laki_laki + r.jumlah_perempuan || 0),
            0,
        );
    const calcPB = rincianArr
        .filter((r) => r.jenis_porsi === "Porsi Besar")
        .reduce(
            (sum, r) => sum + (r.jumlah_laki_laki + r.jumlah_perempuan || 0),
            0,
        );

    const pk = calcPK > 0 ? calcPK : Number(k.total_porsi_kecil) || 0;
    const pb = calcPB > 0 ? calcPB : Number(k.total_porsi_besar) || 0;

    const normAlergi = normalizeAlergiItems(
        k.keterangan_alergi,
        k.alergi_porsi_kecil,
        k.alergi_porsi_besar,
    );
    const sumAlergiPk = normAlergi.reduce(
        (s, a) => s + (Number(a.porsi_kecil) || 0),
        0,
    );
    const sumAlergiPb = normAlergi.reduce(
        (s, a) => s + (Number(a.porsi_besar) || 0),
        0,
    );

    return {
        id: k.id,
        nama_kelompok: k.nama_kelompok,
        kategori: k.kategori,
        desa_kelurahan: k.desa_kelurahan,
        kecamatan: k.kecamatan,
        status_menerima:
            k.status_menerima !== undefined
                ? k.status_menerima
                : k.is_menerima !== undefined
                  ? k.is_menerima
                  : true,
        rincian: sortRincianByKategori(rincianArr, k.kategori),
        total_porsi_kecil: pk,
        total_porsi_besar: pb,
        total_penerima: pk + pb,
        alergi_porsi_kecil:
            sumAlergiPk > 0 ? sumAlergiPk : Number(k.alergi_porsi_kecil) || 0,
        alergi_porsi_besar:
            sumAlergiPb > 0 ? sumAlergiPb : Number(k.alergi_porsi_besar) || 0,
        keterangan_alergi: normAlergi,
    };
}

const woKelompokList = ref(props.kelompokList.map(normalizeKelompokForWo));

function handleResetWoKelompokList() {
    woKelompokList.value = props.kelompokList.map(normalizeKelompokForWo);
}

function handleToggleStatusMenerima(k, status) {
    if (status !== undefined) {
        k.status_menerima = status;
    } else {
        k.status_menerima = !k.status_menerima;
    }
}

// State & Method Modal Edit Detail PM per Sub-Sub Kategori
const showModalEditPm = ref(false);
const editingKelompok = ref(null);
const editFormRincian = ref([]);
const editFormKeteranganAlergi = ref([]);
const modalPmError = ref("");

function handleOpenModalEditPm(kelompok) {
    editingKelompok.value = kelompok;
    modalPmError.value = "";
    editFormRincian.value = JSON.parse(JSON.stringify(kelompok.rincian || []));
    editFormKeteranganAlergi.value = JSON.parse(
        JSON.stringify(kelompok.keterangan_alergi || []),
    );
    showModalEditPm.value = true;
}

const modalTotalPk = computed(() => {
    return editFormRincian.value
        .filter((r) => r.jenis_porsi === "Porsi Kecil")
        .reduce(
            (sum, r) =>
                sum +
                ((Number(r.jumlah_laki_laki) || 0) +
                    (Number(r.jumlah_perempuan) || 0)),
            0,
        );
});

const modalTotalPb = computed(() => {
    return editFormRincian.value
        .filter((r) => r.jenis_porsi === "Porsi Besar")
        .reduce(
            (sum, r) =>
                sum +
                ((Number(r.jumlah_laki_laki) || 0) +
                    (Number(r.jumlah_perempuan) || 0)),
            0,
        );
});

const modalTotalPm = computed(() => {
    return modalTotalPk.value + modalTotalPb.value;
});

const modalTotalAlergiPk = computed(() => {
    return editFormKeteranganAlergi.value.reduce(
        (sum, item) => sum + (Math.max(0, Number(item.porsi_kecil)) || 0),
        0,
    );
});

const modalTotalAlergiPb = computed(() => {
    return editFormKeteranganAlergi.value.reduce(
        (sum, item) => sum + (Math.max(0, Number(item.porsi_besar)) || 0),
        0,
    );
});

const modalGrandTotalAlergi = computed(() => {
    return modalTotalAlergiPk.value + modalTotalAlergiPb.value;
});

function handleSimpanEditDetailPm() {
    if (!editingKelompok.value) return;

    if (
        modalTotalPm.value === 0 &&
        editingKelompok.value.status_menerima !== false
    ) {
        modalPmError.value = `Total porsi untuk "${editingKelompok.value.nama_kelompok}" minimal 1 porsi. Jika tidak menerima distribusi, silakan tandai status kelompok menjadi 'Tidak Menerima'.`;
        return;
    }

    modalPmError.value = "";

    editingKelompok.value.rincian = editFormRincian.value.map((r) => ({
        ...r,
        jumlah_laki_laki: Math.max(0, Number(r.jumlah_laki_laki) || 0),
        jumlah_perempuan: Math.max(0, Number(r.jumlah_perempuan) || 0),
        total:
            Math.max(0, Number(r.jumlah_laki_laki) || 0) +
            Math.max(0, Number(r.jumlah_perempuan) || 0),
    }));

    editingKelompok.value.keterangan_alergi =
        editFormKeteranganAlergi.value.map((a) => ({
            jenis_alergi: a.jenis_alergi || "Lainnya",
            porsi_kecil: Math.max(0, Number(a.porsi_kecil) || 0),
            porsi_besar: Math.max(0, Number(a.porsi_besar) || 0),
        }));

    editingKelompok.value.total_porsi_kecil = modalTotalPk.value;
    editingKelompok.value.total_porsi_besar = modalTotalPb.value;
    editingKelompok.value.total_penerima = modalTotalPm.value;
    editingKelompok.value.alergi_porsi_kecil = modalTotalAlergiPk.value;
    editingKelompok.value.alergi_porsi_besar = modalTotalAlergiPb.value;

    showModalEditPm.value = false;
    editingKelompok.value = null;
}

// Rekapitulasi MBG
const kelompokMenerimaAktif = computed(() => {
    return woKelompokList.value.filter((k) => k.status_menerima !== false);
});

const totalPK = computed(() => {
    return kelompokMenerimaAktif.value.reduce(
        (acc, k) => acc + (Number(k.total_porsi_kecil) || 0),
        0,
    );
});

const totalPB = computed(() => {
    return kelompokMenerimaAktif.value.reduce(
        (acc, k) => acc + (Number(k.total_porsi_besar) || 0),
        0,
    );
});

const totalPM = computed(() => {
    return totalPK.value + totalPB.value;
});

const grandTotalSemuaPM = computed(() => {
    return woKelompokList.value.reduce(
        (acc, k) =>
            acc +
            (Number(k.total_porsi_kecil) || 0) +
            (Number(k.total_porsi_besar) || 0),
        0,
    );
});

const persentasePmMenerima = computed(() => {
    if (grandTotalSemuaPM.value === 0) return 0;
    const pct = (totalPM.value / grandTotalSemuaPM.value) * 100;
    return Number.isInteger(pct) ? pct : parseFloat(pct.toFixed(1));
});

const totalPKAlergi = computed(() => {
    return kelompokMenerimaAktif.value.reduce(
        (acc, k) => acc + (Number(k.alergi_porsi_kecil) || 0),
        0,
    );
});

const totalPBAlergi = computed(() => {
    return kelompokMenerimaAktif.value.reduce(
        (acc, k) => acc + (Number(k.alergi_porsi_besar) || 0),
        0,
    );
});

// ─── PORSI TAMBAHAN PRODUKSI (UJI ORGANOLEPTIK, SAMPEL, BUFFER) ────────────
const porsiTambahan = ref({
    organoleptik: { pk: "", pb: "" },
    sampel: { pk: "", pb: "" },
    buffer: { pk: "", pb: "" },
});
const organoleptikIsManuallyEdited = ref(false);

// Auto-sync organoleptik default saat jumlah KPM aktif berubah (jika belum diedit manual)
watch(
    () => kelompokMenerimaAktif.value.length,
    (count) => {
        if (!organoleptikIsManuallyEdited.value && !isEditMode.value) {
            porsiTambahan.value.organoleptik.pb = count > 0 ? count * 1 : "";
            porsiTambahan.value.organoleptik.pk = "";
        }
    },
    { immediate: true },
);

function onOrganoleptikChange() {
    organoleptikIsManuallyEdited.value = true;
    clearPorsiTambahanFieldError("porsi_organoleptik_pk");
    clearPorsiTambahanFieldError("porsi_organoleptik_pb");
}

function clearPorsiTambahanFieldError(key) {
    if (validationErrors.value[key]) {
        delete validationErrors.value[key];
    }
    const hasRemainingPtError = [
        "porsi_organoleptik_pk",
        "porsi_organoleptik_pb",
        "porsi_sampel_pk",
        "porsi_sampel_pb",
        "porsi_buffer_pk",
        "porsi_buffer_pb",
    ].some((k) => !!validationErrors.value[k]);
    if (!hasRemainingPtError && validationErrors.value.porsiTambahan) {
        delete validationErrors.value.porsiTambahan;
    }
}

const isPtFieldEmpty = (val) =>
    val === "" || val === null || val === undefined || isNaN(Number(val));

const porsiTambahanFieldErrors = computed(() => {
    return {
        organoleptik_pk: isPtFieldEmpty(porsiTambahan.value.organoleptik.pk),
        organoleptik_pb: isPtFieldEmpty(porsiTambahan.value.organoleptik.pb),
        sampel_pk: isPtFieldEmpty(porsiTambahan.value.sampel.pk),
        sampel_pb: isPtFieldEmpty(porsiTambahan.value.sampel.pb),
        buffer_pk: isPtFieldEmpty(porsiTambahan.value.buffer.pk),
        buffer_pb: isPtFieldEmpty(porsiTambahan.value.buffer.pb),
    };
});

const hasEmptyPorsiTambahan = computed(() => {
    return Object.values(porsiTambahanFieldErrors.value).some(Boolean);
});

const totalOrganoleptik = computed(() => {
    return (
        (Number(porsiTambahan.value.organoleptik.pk) || 0) +
        (Number(porsiTambahan.value.organoleptik.pb) || 0)
    );
});

const totalSampel = computed(() => {
    return (
        (Number(porsiTambahan.value.sampel.pk) || 0) +
        (Number(porsiTambahan.value.sampel.pb) || 0)
    );
});

const totalBuffer = computed(() => {
    return (
        (Number(porsiTambahan.value.buffer.pk) || 0) +
        (Number(porsiTambahan.value.buffer.pb) || 0)
    );
});

const totalPorsiTambahanPK = computed(() => {
    return (
        (Number(porsiTambahan.value.organoleptik.pk) || 0) +
        (Number(porsiTambahan.value.sampel.pk) || 0) +
        (Number(porsiTambahan.value.buffer.pk) || 0)
    );
});

const totalPorsiTambahanPB = computed(() => {
    return (
        (Number(porsiTambahan.value.organoleptik.pb) || 0) +
        (Number(porsiTambahan.value.sampel.pb) || 0) +
        (Number(porsiTambahan.value.buffer.pb) || 0)
    );
});

const totalPorsiTambahan = computed(() => {
    return totalPorsiTambahanPK.value + totalPorsiTambahanPB.value;
});

const grandTotalProduksiPK = computed(() => {
    return totalPK.value + totalPorsiTambahanPK.value;
});

const grandTotalProduksiPB = computed(() => {
    return totalPB.value + totalPorsiTambahanPB.value;
});

const grandTotalProduksiSemua = computed(() => {
    return totalPM.value + totalPorsiTambahan.value;
});

function applyContohPorsiTambahan() {
    organoleptikIsManuallyEdited.value = true;
    porsiTambahan.value = {
        organoleptik: { pk: 0, pb: kelompokMenerimaAktif.value.length * 1 },
        sampel: { pk: 2, pb: 2 },
        buffer: { pk: 10, pb: 10 },
    };
    [
        "porsiTambahan",
        "porsi_organoleptik_pk",
        "porsi_organoleptik_pb",
        "porsi_sampel_pk",
        "porsi_sampel_pb",
        "porsi_buffer_pk",
        "porsi_buffer_pb",
    ].forEach((k) => delete validationErrors.value[k]);
    triggerPasteToast("Contoh Porsi Tambahan berhasil diterapkan!");
}

function resetPorsiTambahan() {
    organoleptikIsManuallyEdited.value = false;
    porsiTambahan.value = {
        organoleptik: { pk: "", pb: kelompokMenerimaAktif.value.length * 1 },
        sampel: { pk: "", pb: "" },
        buffer: { pk: "", pb: "" },
    };
    [
        "porsiTambahan",
        "porsi_organoleptik_pk",
        "porsi_organoleptik_pb",
        "porsi_sampel_pk",
        "porsi_sampel_pb",
        "porsi_buffer_pk",
        "porsi_buffer_pb",
    ].forEach((k) => delete validationErrors.value[k]);
    triggerPasteToast("Porsi Tambahan dikosongkan ke default.");
}

const porsiTambahanPayload = computed(() => ({
    organoleptik: {
        pk: Math.max(0, Number(porsiTambahan.value.organoleptik.pk) || 0),
        pb: Math.max(0, Number(porsiTambahan.value.organoleptik.pb) || 0),
        total: totalOrganoleptik.value,
    },
    sampel: {
        pk: Math.max(0, Number(porsiTambahan.value.sampel.pk) || 0),
        pb: Math.max(0, Number(porsiTambahan.value.sampel.pb) || 0),
        total: totalSampel.value,
    },
    buffer: {
        pk: Math.max(0, Number(porsiTambahan.value.buffer.pk) || 0),
        pb: Math.max(0, Number(porsiTambahan.value.buffer.pb) || 0),
        total: totalBuffer.value,
    },
    total_pk: totalPorsiTambahanPK.value,
    total_pb: totalPorsiTambahanPB.value,
    total: totalPorsiTambahan.value,
}));

// ─── HELPER TANGGAL & TEMPLATE CONTOH ─────────────────────────────────────
function formatTanggalIndo(dateStr) {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr + "T00:00:00");
        return d.toLocaleDateString("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    } catch (e) {
        return dateStr;
    }
}

function setTanggalHariIni() {
    if (isTodayTaken.value) {
        const info = getTakenWoInfo(todayStr.value);
        triggerSubmitError(
            `Tanggal hari ini (${formatTanggalIndo(todayStr.value)}) sudah memiliki Work Order: "${info.nama_menu}" (${info.nomor_wo} • Status: ${info.status}). 1 tanggal hanya diperbolehkan 1 Work Order.`,
        );
        return;
    }
    tanggalRencana.value = todayStr.value;
    clearError("tanggalRencana");
}

function setTanggalBesok() {
    if (isTomorrowTaken.value) {
        const info = getTakenWoInfo(tomorrowStr.value);
        triggerSubmitError(
            `Tanggal besok (${formatTanggalIndo(tomorrowStr.value)}) sudah memiliki Work Order: "${info.nama_menu}" (${info.nomor_wo} • Status: ${info.status}). 1 tanggal hanya diperbolehkan 1 Work Order.`,
        );
        return;
    }
    tanggalRencana.value = tomorrowStr.value;
    clearError("tanggalRencana");
}

// Isi otomatis template contoh menu MBG (Sesuai Screenshot)
function handleGunakanContoh() {
    namaMenuAktif.value = "Ayam Guling Khas Bali";
    subMenuKeys.value = [
        "sub_menu_1",
        "sub_menu_2",
        "sub_menu_3",
        "sub_menu_4",
        "sub_menu_5",
    ];
    subMenuKomponen.value = {
        sub_menu_1: "Nasi Putih",
        sub_menu_2: "Ayam Guling",
        sub_menu_3: "Tempe Goreng",
        sub_menu_4: "Sayur Bening Bayam",
        sub_menu_5: "Buah Jeruk",
    };

    // 2 Contoh Varian Diet Alergi untuk Sub Menu Hewani & Nabati
    subMenuAlergi.value = {
        sub_menu_1: [],
        sub_menu_2: [
            {
                jenis_alergi: "Alergi Ayam",
                menu_pengganti: "Ikan Bakar Bumbu Bali",
            },
        ],
        sub_menu_3: [
            {
                jenis_alergi: "Alergi Kedelai",
                menu_pengganti: "Telur Rebus Balado",
            },
        ],
        sub_menu_4: [],
        sub_menu_5: [],
    };

    if (woKelompokList.value && woKelompokList.value.length > 0) {
        woKelompokList.value[0].keterangan_alergi = [
            { jenis_alergi: "Alergi Ayam", porsi_kecil: 8, porsi_besar: 10 },
            { jenis_alergi: "Alergi Kedelai", porsi_kecil: 5, porsi_besar: 6 },
        ];
        woKelompokList.value[0].alergi_porsi_kecil = 13;
        woKelompokList.value[0].alergi_porsi_besar = 16;
    }

    clearError("namaMenuAktif");
    subMenuKeys.value.forEach((k) => clearError(k));
}

// ─── VALIDASI & SCROLL KE ERROR ───────────────────────────────────────────
const validationErrors = ref({});

function clearError(field) {
    if (validationErrors.value[field]) {
        delete validationErrors.value[field];
    }
}

function scrollToFirstError() {
    nextTick(() => {
        // Cek apakah ada error pada bagian Porsi Tambahan
        const ptErrorKeys = [
            "porsi_organoleptik_pk",
            "porsi_organoleptik_pb",
            "porsi_sampel_pk",
            "porsi_sampel_pb",
            "porsi_buffer_pk",
            "porsi_buffer_pb",
            "porsiTambahan",
        ];
        const hasPtError = ptErrorKeys.some((k) => !!validationErrors.value[k]);

        // Cek apakah ada error di field-field atas (sebelum porsi tambahan)
        const upperErrorKeys = Object.keys(validationErrors.value).filter(
            (k) => !ptErrorKeys.includes(k),
        );

        // Jika hanya error di porsi tambahan (atau tidak ada error di field atas)
        if (hasPtError && upperErrorKeys.length === 0) {
            const ptContainer = document.getElementById("card-porsi-tambahan");
            const firstPtInput = ptContainer?.querySelector(
                "input.border-rose-400, input.border-rose-500, input[data-error-target]",
            );
            const targetEl = firstPtInput || ptContainer;
            if (targetEl) {
                targetEl.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });
                if (firstPtInput && typeof firstPtInput.focus === "function") {
                    firstPtInput.focus();
                }
                return;
            }
        }

        // Jika ada error di field atas, cari input/kontrol form error pertama
        const firstErrorEl = document.querySelector(
            "input.border-rose-400, input.border-rose-500, select.border-rose-400, select.border-rose-500, textarea.border-rose-400, textarea.border-rose-500, [data-error-target], #error-kelompok-target",
        );
        if (firstErrorEl) {
            firstErrorEl.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
            if (
                typeof firstErrorEl.focus === "function" &&
                (firstErrorEl.tagName === "INPUT" ||
                    firstErrorEl.tagName === "SELECT" ||
                    firstErrorEl.tagName === "TEXTAREA")
            ) {
                firstErrorEl.focus();
            } else {
                const innerInput = firstErrorEl.querySelector(
                    "input, select, textarea",
                );
                if (innerInput && typeof innerInput.focus === "function") {
                    innerInput.focus();
                }
            }
        } else if (hasPtError) {
            const ptContainer = document.getElementById("card-porsi-tambahan");
            if (ptContainer) {
                ptContainer.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });
            }
        }
    });
}

function validateStep1() {
    const errs = {};
    if (!tanggalRencana.value) {
        errs.tanggalRencana = "Tanggal rencana masak & distribusi wajib diisi.";
    } else {
        const duplicateDateWo = getTakenWoInfo(tanggalRencana.value);
        if (duplicateDateWo) {
            errs.tanggalRencana = `Tanggal ${formatTanggalIndo(tanggalRencana.value)} sudah memiliki Work Order: "${duplicateDateWo.nama_menu}" (${duplicateDateWo.nomor_wo} • Status: ${duplicateDateWo.status}). Hanya diperbolehkan 1 Work Order per tanggal (termasuk status Draft).`;
            triggerSubmitError(errs.tanggalRencana);
        }
    }
    if (!namaMenuAktif.value || !namaMenuAktif.value.trim()) {
        errs.namaMenuAktif = "Nama menu wajib diisi.";
    }

    // Validasi setiap Sub Menu yang aktif
    subMenuKeys.value.forEach((subKey, idx) => {
        const val = subMenuKomponen.value[subKey];
        if (!val || !val.trim()) {
            errs[subKey] = `Sub Menu ${idx + 1} wajib diisi.`;
        }
    });

    // Validasi menu pengganti alergi
    subMenuKeys.value.forEach((subKey, idx) => {
        const list = subMenuAlergi.value[subKey];
        if (Array.isArray(list) && list.length > 0) {
            list.forEach((item, itemIdx) => {
                const subLabel = `Sub Menu ${idx + 1}`;
                const jAlergi = extractCleanAlergi(item?.jenis_alergi);
                const mPengganti =
                    typeof item?.menu_pengganti === "string"
                        ? item.menu_pengganti.trim()
                        : "";
                if (!jAlergi) {
                    errs[`alergi_${subKey}_${itemIdx}_jenis`] =
                        `Pilih jenis alergi untuk ${subLabel}.`;
                }
                if (!mPengganti) {
                    errs[`alergi_${subKey}_${itemIdx}_menu`] =
                        `Menu pengganti ${subLabel} wajib diisi.`;
                }
            });
        }
    });

    if (kelompokMenerimaAktif.value.length === 0) {
        errs.kelompok =
            "Minimal 1 kelompok sasaran penerima manfaat harus berstatus Menerima.";
    } else {
        const zeroReceiving = woKelompokList.value.find(
            (k) =>
                k.status_menerima !== false &&
                (Number(k.total_porsi_kecil) || 0) +
                    (Number(k.total_porsi_besar) || 0) <=
                    0,
        );
        if (zeroReceiving) {
            errs.kelompok = `Kelompok "${zeroReceiving.nama_kelompok}" berstatus Menerima tetapi memiliki 0 porsi. Wajib minimal 1 porsi atau tandai 'Tidak Menerima'.`;
        }
    }

    // Validasi Waktu Kegiatan Operasional (Wajib diisi seluruhnya)
    let emptyJadwalFound = false;
    Object.keys(jadwalOperasionalList).forEach((kKey) => {
        const item = jadwalOperasional.value?.[kKey];
        const isMulaiEmpty = !item || !item.mulai || !String(item.mulai).trim();
        const isSelesaiEmpty =
            !item || !item.selesai || !String(item.selesai).trim();
        if (isMulaiEmpty) {
            errs["jadwal_" + kKey + "_mulai"] = "Jam mulai wajib diisi.";
            emptyJadwalFound = true;
        }
        if (isSelesaiEmpty) {
            errs["jadwal_" + kKey + "_selesai"] = "Jam selesai wajib diisi.";
            emptyJadwalFound = true;
        }
        if (isMulaiEmpty || isSelesaiEmpty) {
            errs["jadwal_" + kKey] =
                `Waktu ${jadwalOperasionalList[kKey].nama} belum lengkap. Jam mulai & jam selesai wajib diisi.`;
        }
    });
    // Validasi Porsi Tambahan (Wajib diisi seluruhnya, tidak boleh kosong, minimal 0)
    let porsiTambahanEmptyFound = false;
    const ptFields = [
        {
            key: "porsi_organoleptik_pk",
            val: porsiTambahan.value.organoleptik.pk,
            label: "PK Uji Organoleptik",
        },
        {
            key: "porsi_organoleptik_pb",
            val: porsiTambahan.value.organoleptik.pb,
            label: "PB Uji Organoleptik",
        },
        {
            key: "porsi_sampel_pk",
            val: porsiTambahan.value.sampel.pk,
            label: "PK Sampel Makanan",
        },
        {
            key: "porsi_sampel_pb",
            val: porsiTambahan.value.sampel.pb,
            label: "PB Sampel Makanan",
        },
        {
            key: "porsi_buffer_pk",
            val: porsiTambahan.value.buffer.pk,
            label: "PK Buffer Produksi",
        },
        {
            key: "porsi_buffer_pb",
            val: porsiTambahan.value.buffer.pb,
            label: "PB Buffer Produksi",
        },
    ];

    ptFields.forEach((f) => {
        if (
            f.val === "" ||
            f.val === null ||
            f.val === undefined ||
            isNaN(Number(f.val)) ||
            Number(f.val) < 0
        ) {
            errs[f.key] =
                `${f.label} wajib diisi (isi angka 0 jika tidak ada alokasi).`;
            porsiTambahanEmptyFound = true;
        }
    });

    if (porsiTambahanEmptyFound) {
        errs.porsiTambahan =
            "Porsi Tambahan (Uji Organoleptik, Sampel, dan Buffer Produksi) wajib diisi seluruhnya. Tidak boleh ada yang kosong (isi angka 0 bila tidak ada porsi).";
        triggerSubmitError(errs.porsiTambahan, false);
    }

    validationErrors.value = errs;
    const isValid = Object.keys(errs).length === 0;
    if (!isValid) {
        scrollToFirstError();
    }
    return isValid;
}

// ─── SIMPAN / DRAFT PERENCANAAN PRODUKSI ──────────────────────────────────
function handleSavePerencanaan(lanjutKeRancangMenu = false) {
    if (!validateStep1()) {
        return;
    }

    isSubmitting.value = true;

    const cleanSubMenus = subMenuKeys.value.map((k) =>
        (subMenuKomponen.value[k] || "").trim(),
    );

    const payload = {
        id: isEditMode.value ? editingWoId.value : null,
        nomor_wo: woNo.value,
        tanggal_distribusi: tanggalRencana.value,
        siklus_ke: 1,
        metode_wo: metodeWo.value,
        database_pangan: "tkpi2020",
        nama_menu: namaMenuAktif.value.trim(),
        sub_menus: cleanSubMenus,
        sub_menu_1: cleanSubMenus[0] || null,
        sub_menu_2: cleanSubMenus[1] || null,
        sub_menu_3: cleanSubMenus[2] || null,
        sub_menu_4: cleanSubMenus[3] || null,
        sub_menu_5: cleanSubMenus[4] || null,
        sub_menu_alergi: subMenuAlergi.value,
        total_pm: totalPM.value,
        total_pk: totalPK.value,
        total_pb: totalPB.value,
        total_alergi: totalPKAlergi.value + totalPBAlergi.value,
        total_kelompok: kelompokMenerimaAktif.value.length,
        jadwal_operasional: jadwalOperasional.value,
        lanjut_ke_rancang_menu:
            metodeWo.value === "manual" ? false : lanjutKeRancangMenu,
        step: lanjutKeRancangMenu ? "formula" : null,
        tetap_di_halaman: false,
        tab_tujuan: activeManualTab.value,
        akg_pk: akgPk.value,
        akg_pb: akgPb.value,
        akg_alergi: akgAlergi.value,
        catatan: catatanResep.value
            ? {
                  catatan_resep: catatanResep.value,
                  jadwal_operasional: jadwalOperasional.value,
              }
            : {
                  jadwal_operasional: jadwalOperasional.value,
              },
        porsi_tambahan: porsiTambahanPayload.value,
        kelompoks: woKelompokList.value.map((k) => ({
            kelompok_id: k.id,
            nama_kelompok: k.nama_kelompok,
            kategori: k.kategori,
            desa_kelurahan: k.desa_kelurahan,
            kecamatan: k.kecamatan,
            is_menerima: k.status_menerima !== false,
            total_penerima: k.total_penerima,
            total_porsi_kecil: k.total_porsi_kecil,
            total_porsi_besar: k.total_porsi_besar,
            porsi_kecil: k.total_porsi_kecil,
            porsi_besar: k.total_porsi_besar,
            rincian: k.rincian || [],
            detail_alergi: k.keterangan_alergi || [],
            keterangan_alergi: k.keterangan_alergi || [],
        })),
        status: isEditMode.value
            ? props.editWorkOrder?.status || "Draft"
            : "Draft",
    };

    router.post(route("work-order.store"), payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            isNavigationConfirmed.value = true;
            nextTick(() => {
                initialFormSnapshot.value = takeFormSnapshot();
            });
            triggerSubmitSuccess(
                metodeWo.value === "manual"
                    ? "Perencanaan Produksi WO Manual berhasil disimpan!"
                    : "Perencanaan Produksi Work Order berhasil disimpan!",
            );
            if (metodeWo.value !== "manual" && !lanjutKeRancangMenu) {
                currentTab.value = "daftar";
            }
        },
        onError: (err) => {
            isSubmitting.value = false;
            const msg = Object.values(err)[0] || "Gagal menyimpan Work Order.";
            triggerSubmitError(msg);
        },
    });
}

// ─── HANDLER TAB FORMULA MAKANAN (MODE MANUAL) ───────────────────────────
const configuredAllergiesList = computed(() => {
    const list = [];
    if (!subMenuAlergi.value || typeof subMenuAlergi.value !== "object")
        return list;

    Object.entries(subMenuAlergi.value).forEach(([subKey, items]) => {
        if (!Array.isArray(items)) return;
        const sIdx = subMenuKeys.value.indexOf(subKey);
        const subMenuName =
            subMenuKomponen.value[subKey] ||
            `Sub Menu ${sIdx >= 0 ? sIdx + 1 : ""}`;

        items.forEach((item, itemIdx) => {
            const jenis =
                typeof item?.jenis_alergi === "string"
                    ? item.jenis_alergi
                    : item?.jenis_alergi?.value ||
                      item?.jenis_alergi?.label ||
                      "";
            const pengganti =
                typeof item?.menu_pengganti === "string"
                    ? item.menu_pengganti
                    : item?.menu_pengganti?.nama || "";
            if (jenis) {
                list.push({
                    subKey,
                    subLabel: `Sub Menu ${sIdx >= 0 ? sIdx + 1 : itemIdx + 1}`,
                    subMenuName,
                    jenis,
                    pengganti: pengganti || "-",
                    uniqueKey: `${subKey}_${jenis}_${itemIdx}`,
                });
            }
        });
    });
    return list;
});

watch(
    configuredAllergiesList,
    (newList) => {
        if (newList.length > 0 && !selectedAlergiFormulaTab.value) {
            selectedAlergiFormulaTab.value = newList[0].jenis;
        }
        newList.forEach((item) => {
            const j = item.jenis;
            if (!akgAlergi.value[j]) {
                akgAlergi.value[j] = {
                    pk: {
                        energi: 0,
                        protein: 0,
                        lemak: 0,
                        karbohidrat: 0,
                        serat: 0,
                    },
                    pb: {
                        energi: 0,
                        protein: 0,
                        lemak: 0,
                        karbohidrat: 0,
                        serat: 0,
                    },
                };
            }
        });
    },
    { immediate: true, deep: true },
);

function handleSwitchToFormulaTab() {
    if (!validateStep1()) {
        activeManualTab.value = "perencanaan";
        return;
    }
    activeManualTab.value = "formula";
    window.scrollTo({ top: 0, behavior: "smooth" });
}

function handleSavePerencanaanManualAndOpenFormula() {
    if (!validateStep1()) {
        activeManualTab.value = "perencanaan";
        return;
    }
    activeManualTab.value = "formula";
    window.scrollTo({ top: 0, behavior: "smooth" });
    triggerPasteToast(
        "Beralih ke Formula Makanan. Silakan lengkapi kandungan gizi.",
    );
}

function handleSavePerencanaanManualComplete() {
    if (!validateStep1()) {
        activeManualTab.value = "perencanaan";
        return;
    }

    isSubmitting.value = true;

    const cleanSubMenus = subMenuKeys.value.map((k) =>
        (subMenuKomponen.value[k] || "").trim(),
    );

    const payload = {
        id: isEditMode.value ? editingWoId.value : null,
        nomor_wo: woNo.value,
        tanggal_distribusi: tanggalRencana.value,
        siklus_ke: 1,
        metode_wo: "manual",
        database_pangan: "tkpi2020",
        nama_menu: namaMenuAktif.value.trim(),
        sub_menus: cleanSubMenus,
        sub_menu_1: cleanSubMenus[0] || null,
        sub_menu_2: cleanSubMenus[1] || null,
        sub_menu_3: cleanSubMenus[2] || null,
        sub_menu_4: cleanSubMenus[3] || null,
        sub_menu_5: cleanSubMenus[4] || null,
        sub_menu_alergi: subMenuAlergi.value,
        total_pm: totalPM.value,
        total_pk: totalPK.value,
        total_pb: totalPB.value,
        total_alergi: totalPKAlergi.value + totalPBAlergi.value,
        total_kelompok: kelompokMenerimaAktif.value.length,
        jadwal_operasional: jadwalOperasional.value,
        lanjut_ke_rancang_menu: false,
        tetap_di_halaman: false,
        tab_tujuan: "formula",
        akg_pk: akgPk.value,
        akg_pb: akgPb.value,
        akg_alergi: akgAlergi.value,
        catatan: catatanResep.value
            ? {
                  catatan_resep: catatanResep.value,
                  jadwal_operasional: jadwalOperasional.value,
              }
            : {
                  jadwal_operasional: jadwalOperasional.value,
              },
        porsi_tambahan: porsiTambahanPayload.value,
        kelompoks: woKelompokList.value.map((k) => ({
            kelompok_id: k.id,
            nama_kelompok: k.nama_kelompok,
            kategori: k.kategori,
            desa_kelurahan: k.desa_kelurahan,
            kecamatan: k.kecamatan,
            is_menerima: k.status_menerima !== false,
            total_penerima: k.total_penerima,
            total_porsi_kecil: k.total_porsi_kecil,
            total_porsi_besar: k.total_porsi_besar,
            porsi_kecil: k.total_porsi_kecil,
            porsi_besar: k.total_porsi_besar,
            rincian: k.rincian || [],
            detail_alergi: k.keterangan_alergi || [],
            keterangan_alergi: k.keterangan_alergi || [],
        })),
        status: isEditMode.value
            ? props.editWorkOrder?.status || "Draft"
            : "Draft",
    };

    router.post(route("work-order.store"), payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            isNavigationConfirmed.value = true;
            nextTick(() => {
                initialFormSnapshot.value = takeFormSnapshot();
            });
            triggerSubmitSuccess("Work Order berhasil disimpan!");
        },
        onError: (err) => {
            isSubmitting.value = false;
            const msg = Object.values(err)[0] || "Gagal menyimpan Work Order.";
            triggerSubmitError(msg);
        },
    });
}

// ─── INITIALIZATION / EDIT MODE ───────────────────────────────────────────
function initForm(targetWo = null) {
    if (targetWo) {
        isEditMode.value = true;
        editingWoId.value = targetWo.id;
        metodeWo.value = targetWo.metode_wo || "sistem";
        tanggalRencana.value = targetWo.tanggal_distribusi;
        namaMenuAktif.value = targetWo.nama_menu || "";

        let rawList = [];
        if (
            Array.isArray(targetWo.sub_menus) &&
            targetWo.sub_menus.length > 0
        ) {
            rawList = targetWo.sub_menus.map((s) =>
                typeof s === "string" ? s : s?.nama || "",
            );
        } else {
            rawList = [
                targetWo.sub_menu_1 || "",
                targetWo.sub_menu_2 || "",
                targetWo.sub_menu_3 || "",
                targetWo.sub_menu_4 || "",
                targetWo.sub_menu_5 || "",
            ];
        }
        while (rawList.length < 5) {
            rawList.push("");
        }

        const newKeys = [];
        const newKomponen = {};
        rawList.forEach((val, idx) => {
            const k = `sub_menu_${idx + 1}`;
            newKeys.push(k);
            newKomponen[k] = val;
        });
        subMenuKeys.value = newKeys;
        subMenuKomponen.value = newKomponen;

        const rawAlergi =
            targetWo.sub_menu_alergi &&
            typeof targetWo.sub_menu_alergi === "object"
                ? targetWo.sub_menu_alergi
                : {};
        const parsedAlergi = {};
        newKeys.forEach((k) => {
            parsedAlergi[k] = Array.isArray(rawAlergi[k]) ? rawAlergi[k] : [];
        });
        subMenuAlergi.value = parsedAlergi;

        let savedJadwal = null;
        if (
            targetWo.jadwal_operasional &&
            typeof targetWo.jadwal_operasional === "object"
        ) {
            savedJadwal = targetWo.jadwal_operasional;
        } else if (targetWo.catatan && typeof targetWo.catatan === "object") {
            savedJadwal = targetWo.catatan.jadwal_operasional;
        } else if (typeof targetWo.catatan === "string") {
            try {
                const parsed = JSON.parse(targetWo.catatan);
                savedJadwal = parsed?.jadwal_operasional;
            } catch (e) {}
        }
        if (savedJadwal) {
            jadwalOperasional.value = JSON.parse(JSON.stringify(savedJadwal));
        } else {
            resetJadwalOperasionalToEmpty();
        }

        if (
            Array.isArray(targetWo.kelompoks) &&
            targetWo.kelompoks.length > 0
        ) {
            woKelompokList.value = props.kelompokList.map((master) => {
                const found = targetWo.kelompoks.find(
                    (k) =>
                        k.kelompok_id === master.id ||
                        k.nama_kelompok === master.nama_kelompok,
                );
                const norm = normalizeKelompokForWo(master);
                if (found) {
                    norm.status_menerima = found.is_menerima !== false;
                    if (found.porsi_kecil !== undefined)
                        norm.total_porsi_kecil = Number(found.porsi_kecil);
                    if (found.porsi_besar !== undefined)
                        norm.total_porsi_besar = Number(found.porsi_besar);
                    if (found.total_penerima !== undefined)
                        norm.total_penerima = Number(found.total_penerima);
                    if (
                        Array.isArray(found.rincian) &&
                        found.rincian.length > 0
                    )
                        norm.rincian = found.rincian;
                    if (
                        Array.isArray(found.detail_alergi) &&
                        found.detail_alergi.length > 0
                    )
                        norm.keterangan_alergi = found.detail_alergi;
                } else {
                    norm.status_menerima = false;
                }
                return norm;
            });
        } else {
            woKelompokList.value = props.kelompokList.map(
                normalizeKelompokForWo,
            );
        }

        // Load data AKG dan catatan resep untuk WO ini
        akgPk.value = {
            energi: Number(targetWo.akg_pk?.energi) || 0,
            protein: Number(targetWo.akg_pk?.protein) || 0,
            lemak: Number(targetWo.akg_pk?.lemak) || 0,
            karbohidrat: Number(targetWo.akg_pk?.karbohidrat) || 0,
            serat: Number(targetWo.akg_pk?.serat) || 0,
        };
        akgPb.value = {
            energi: Number(targetWo.akg_pb?.energi) || 0,
            protein: Number(targetWo.akg_pb?.protein) || 0,
            lemak: Number(targetWo.akg_pb?.lemak) || 0,
            karbohidrat: Number(targetWo.akg_pb?.karbohidrat) || 0,
            serat: Number(targetWo.akg_pb?.serat) || 0,
        };
        akgAlergi.value =
            targetWo.akg_alergi && typeof targetWo.akg_alergi === "object"
                ? JSON.parse(JSON.stringify(targetWo.akg_alergi))
                : {};
        catatanResep.value =
            typeof targetWo.catatan === "string"
                ? targetWo.catatan
                : targetWo.catatan?.catatan_resep || "";

        if (
            targetWo.porsi_tambahan &&
            typeof targetWo.porsi_tambahan === "object"
        ) {
            const pt = targetWo.porsi_tambahan;
            porsiTambahan.value = {
                organoleptik: {
                    pk:
                        pt.organoleptik?.pk !== undefined &&
                        pt.organoleptik?.pk !== null &&
                        pt.organoleptik?.pk !== ""
                            ? pt.organoleptik.pk
                            : "",
                    pb:
                        pt.organoleptik?.pb !== undefined &&
                        pt.organoleptik?.pb !== null &&
                        pt.organoleptik?.pb !== ""
                            ? pt.organoleptik.pb
                            : "",
                },
                sampel: {
                    pk:
                        pt.sampel?.pk !== undefined &&
                        pt.sampel?.pk !== null &&
                        pt.sampel?.pk !== ""
                            ? pt.sampel.pk
                            : "",
                    pb:
                        pt.sampel?.pb !== undefined &&
                        pt.sampel?.pb !== null &&
                        pt.sampel?.pb !== ""
                            ? pt.sampel.pb
                            : "",
                },
                buffer: {
                    pk:
                        pt.buffer?.pk !== undefined &&
                        pt.buffer?.pk !== null &&
                        pt.buffer?.pk !== ""
                            ? pt.buffer.pk
                            : "",
                    pb:
                        pt.buffer?.pb !== undefined &&
                        pt.buffer?.pb !== null &&
                        pt.buffer?.pb !== ""
                            ? pt.buffer.pb
                            : "",
                },
            };
            organoleptikIsManuallyEdited.value = true;
        } else {
            organoleptikIsManuallyEdited.value = false;
            porsiTambahan.value = {
                organoleptik: {
                    pk: "",
                    pb:
                        kelompokMenerimaAktif.value.length > 0
                            ? kelompokMenerimaAktif.value.length * 1
                            : "",
                },
                sampel: { pk: "", pb: "" },
                buffer: { pk: "", pb: "" },
            };
        }

        // Cek kelengkapan porsi tambahan jika sedang edit WO
        const isValEmpty = (v) =>
            v === "" || v === null || v === undefined || isNaN(Number(v));
        const isPtIncomplete =
            isValEmpty(porsiTambahan.value.organoleptik.pk) ||
            isValEmpty(porsiTambahan.value.organoleptik.pb) ||
            isValEmpty(porsiTambahan.value.sampel.pk) ||
            isValEmpty(porsiTambahan.value.sampel.pb) ||
            isValEmpty(porsiTambahan.value.buffer.pk) ||
            isValEmpty(porsiTambahan.value.buffer.pb);

        if (isPtIncomplete) {
            if (isValEmpty(porsiTambahan.value.organoleptik.pk))
                validationErrors.value.porsi_organoleptik_pk = "Wajib diisi";
            if (isValEmpty(porsiTambahan.value.organoleptik.pb))
                validationErrors.value.porsi_organoleptik_pb = "Wajib diisi";
            if (isValEmpty(porsiTambahan.value.sampel.pk))
                validationErrors.value.porsi_sampel_pk = "Wajib diisi";
            if (isValEmpty(porsiTambahan.value.sampel.pb))
                validationErrors.value.porsi_sampel_pb = "Wajib diisi";
            if (isValEmpty(porsiTambahan.value.buffer.pk))
                validationErrors.value.porsi_buffer_pk = "Wajib diisi";
            if (isValEmpty(porsiTambahan.value.buffer.pb))
                validationErrors.value.porsi_buffer_pb = "Wajib diisi";
            validationErrors.value.porsiTambahan =
                "Porsi Tambahan Produksi pada Work Order ini belum diisi lengkap. Harap lengkapi seluruh field sebelum melanjutkan.";
            activeManualTab.value = "perencanaan";
            nextTick(() => {
                setTimeout(() => {
                    const ptEl = document.getElementById("card-porsi-tambahan");
                    if (ptEl) {
                        ptEl.scrollIntoView({
                            behavior: "smooth",
                            block: "center",
                        });
                        const targetInput =
                            ptEl.querySelector("input.border-rose-400") ||
                            ptEl.querySelector("input[value='']") ||
                            ptEl.querySelector("input");
                        if (
                            targetInput &&
                            typeof targetInput.focus === "function"
                        ) {
                            targetInput.focus();
                        }
                    }
                }, 400);
            });
        } else if (targetWo.metode_wo === "manual") {
            const urlTab =
                typeof window !== "undefined"
                    ? new URLSearchParams(window.location.search).get("tab")
                    : null;
            if (urlTab === "formula") {
                activeManualTab.value = "formula";
            }
        }
    } else {
        isEditMode.value = false;
        editingWoId.value = null;
        metodeWo.value = "sistem";
        activeManualTab.value = "perencanaan";
        tanggalRencana.value = getInitialAvailableDate();
        namaMenuAktif.value = "";
        subMenuKeys.value = [
            "sub_menu_1",
            "sub_menu_2",
            "sub_menu_3",
            "sub_menu_4",
            "sub_menu_5",
        ];
        subMenuKomponen.value = {
            sub_menu_1: "",
            sub_menu_2: "",
            sub_menu_3: "",
            sub_menu_4: "",
            sub_menu_5: "",
        };
        subMenuAlergi.value = {
            sub_menu_1: [],
            sub_menu_2: [],
            sub_menu_3: [],
            sub_menu_4: [],
            sub_menu_5: [],
        };
        akgPk.value = {
            energi: 0,
            protein: 0,
            lemak: 0,
            karbohidrat: 0,
            serat: 0,
        };
        akgPb.value = {
            energi: 0,
            protein: 0,
            lemak: 0,
            karbohidrat: 0,
            serat: 0,
        };
        akgAlergi.value = {};
        catatanResep.value = "";
        resetJadwalOperasionalToEmpty();
        woKelompokList.value = props.kelompokList.map(normalizeKelompokForWo);
        organoleptikIsManuallyEdited.value = false;
        porsiTambahan.value = {
            organoleptik: {
                pk: "",
                pb:
                    kelompokMenerimaAktif.value.length > 0
                        ? kelompokMenerimaAktif.value.length * 1
                        : "",
            },
            sampel: { pk: "", pb: "" },
            buffer: { pk: "", pb: "" },
        };
        validationErrors.value = {};
    }
    nextTick(() => {
        initialFormSnapshot.value = takeFormSnapshot();
    });
}

// ─── TAB DAFTAR WORK ORDER & MODAL MANUAL EDIT DENGAN SINKRONISASI URL UUID ─
const showManualModal = ref(false);
const selectedManualWo = ref(null);

function updateUrlId(id) {
    try {
        const url = new URL(window.location.href);
        if (id) {
            url.searchParams.set("id", id);
        } else {
            url.searchParams.delete("id");
            url.searchParams.delete("uuid");
            url.searchParams.delete("edit_id");
        }
        window.history.replaceState({}, "", url.toString());
    } catch (e) {}
}

function openManualEditor(wo) {
    selectedManualWo.value = wo;
    showManualModal.value = true;
    updateUrlId(wo.uuid || wo.id);
}

function closeManualEditor() {
    showManualModal.value = false;
    selectedManualWo.value = null;
    updateUrlId(null);
}

function handleManualSaved() {
    router.reload({
        only: ["workOrdersList"],
        onSuccess: (page) => {
            if (selectedManualWo.value) {
                const updated = (page.props.workOrdersList || []).find(
                    (w) =>
                        w.id === selectedManualWo.value.id ||
                        w.uuid === selectedManualWo.value.uuid,
                );
                if (updated) {
                    selectedManualWo.value = updated;
                }
            }
        },
    });
}

function openRancangMenuSistem(wo) {
    const targetUuid = wo.uuid || wo.id;
    router.visit(
        route("gizi.rancang-menu", { id: targetUuid, step: "formula" }),
    );
}

function handleEditPerencanaan(wo) {
    initForm(wo);
    currentTab.value = "perencanaan";
    updateUrlId(wo.uuid || wo.id);
}

function handleDeleteWo(wo) {
    if (!confirm(`Hapus Work Order ${wo.nomor_wo} (${wo.nama_menu})?`)) return;
    router.delete(route("work-order.destroy", wo.id), {
        preserveScroll: true,
    });
}

function onGlobalWindowClick(e) {
    if (
        showDatePickerPopover.value &&
        datePickerContainerRef.value &&
        !datePickerContainerRef.value.contains(e.target)
    ) {
        showDatePickerPopover.value = false;
    }
}

watch(
    () => props.editWorkOrder,
    (val) => {
        if (val) {
            initForm(val);
            currentTab.value = "perencanaan";
            updateUrlId(val.uuid || val.id);
        }
    },
    { immediate: true },
);

// ─── DIRTY STATE & KONFIRMASI TINGGALKAN HALAMAN (LEAVE CONFIRM) ─────────────
const showLeaveConfirmModal = ref(false);
const pendingNavigation = ref(null);
const isNavigationConfirmed = ref(false);
const initialFormSnapshot = ref(null);

function takeFormSnapshot() {
    return JSON.stringify({
        tanggal: tanggalRencana.value || "",
        metode: metodeWo.value || "sistem",
        namaMenu: (namaMenuAktif.value || "").trim(),
        subMenus: subMenuKeys.value.map((k) =>
            (subMenuKomponen.value[k] || "").trim(),
        ),
        subAlergi: subMenuAlergi.value || {},
        kelompoks: (woKelompokList.value || []).map((k) => ({
            id: k.id,
            menerima: k.status_menerima !== false,
        })),
        jadwal: jadwalOperasional.value || {},
        akgPk: akgPk.value,
        akgPb: akgPb.value,
        akgAlergi: akgAlergi.value,
        catatanResep: catatanResep.value,
        porsiTambahan: porsiTambahan.value,
    });
}

const isFormDirty = computed(() => {
    if (!initialFormSnapshot.value) return false;
    return initialFormSnapshot.value !== takeFormSnapshot();
});

function handleBeforeUnload(e) {
    if (
        isFormDirty.value &&
        !isSubmitting.value &&
        !isNavigationConfirmed.value
    ) {
        e.preventDefault();
        e.returnValue = "";
        return "";
    }
}

function handleKeyDown(e) {
    if (
        isSubmitting.value ||
        isNavigationConfirmed.value ||
        !isFormDirty.value
    ) {
        return;
    }
    const isReloadKey =
        e.key === "F5" ||
        ((e.ctrlKey || e.metaKey) && (e.key === "r" || e.key === "R"));

    if (isReloadKey) {
        e.preventDefault();
        e.stopPropagation();
        pendingNavigation.value = { type: "reload" };
        showLeaveConfirmModal.value = true;
        return;
    }

    const isBackKey = e.altKey && e.key === "ArrowLeft";
    if (isBackKey) {
        e.preventDefault();
        e.stopPropagation();
        pendingNavigation.value = { type: "history_back" };
        showLeaveConfirmModal.value = true;
    }
}

function handlePopState() {
    if (isSubmitting.value || isNavigationConfirmed.value) {
        return;
    }
    if (isFormDirty.value) {
        window.history.pushState(null, "", window.location.href);
        pendingNavigation.value = { type: "history_back" };
        showLeaveConfirmModal.value = true;
    }
}

function handleConfirmLeave() {
    showLeaveConfirmModal.value = false;
    isNavigationConfirmed.value = true;

    if (pendingNavigation.value) {
        const nav = pendingNavigation.value;
        pendingNavigation.value = null;

        if (nav.type === "reload") {
            window.location.reload();
            return;
        }

        if (nav.type === "history_back") {
            router.visit(route("work-order.daftar"));
            return;
        }

        if (nav.url) {
            router.visit(nav.url, {
                method: nav.method || "get",
                data: nav.data || {},
                replace: nav.replace || false,
                preserveScroll: nav.preserveScroll || false,
                preserveState: nav.preserveState || false,
            });
            return;
        }
    }

    router.visit(route("work-order.daftar"));
}

function handleCancelLeave() {
    showLeaveConfirmModal.value = false;
    pendingNavigation.value = null;
}

let removeRouterBeforeHook = null;

onMounted(() => {
    window.addEventListener("click", onGlobalWindowClick);
    window.addEventListener("beforeunload", handleBeforeUnload);
    window.addEventListener("keydown", handleKeyDown);
    window.addEventListener("popstate", handlePopState, true);

    removeRouterBeforeHook = router.on("before", (event) => {
        if (isSubmitting.value || isNavigationConfirmed.value) {
            return;
        }
        if (isFormDirty.value) {
            event.preventDefault();
            const visit = event.detail?.visit ?? {};
            pendingNavigation.value = {
                url: visit.url,
                method: visit.method,
                data: visit.data,
                replace: visit.replace,
                preserveScroll: visit.preserveScroll,
                preserveState: visit.preserveState,
            };
            showLeaveConfirmModal.value = true;
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const paramId =
        urlParams.get("id") ||
        urlParams.get("uuid") ||
        urlParams.get("edit_id");
    if (paramId && props.workOrdersList?.length > 0) {
        const targetWo = props.workOrdersList.find(
            (w) =>
                String(w.id) === String(paramId) ||
                w.uuid === paramId ||
                w.nomor_wo === paramId,
        );
        if (targetWo) {
            handleEditPerencanaan(targetWo);
            return;
        }
    }

    if (!props.editWorkOrder) {
        initForm();
    }
});

onUnmounted(() => {
    window.removeEventListener("click", onGlobalWindowClick);
    window.removeEventListener("beforeunload", handleBeforeUnload);
    window.removeEventListener("keydown", handleKeyDown);
    window.removeEventListener("popstate", handlePopState, true);
    if (typeof removeRouterBeforeHook === "function") {
        removeRouterBeforeHook();
    }
});
</script>

<template>
    <AppLayout
        title="Work Order"
        subtitle="Buat WO"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Work Order - Buat WO" />

        <div class="space-y-6">
            <!-- Header Halaman Perencanaan Produksi -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-2xs"
            >
                <div>
                    <h2
                        class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                    >
                        <ChefHat class="h-5 w-5 text-primary" />
                        <span
                            >Perencanaan Produksi Makan Bergizi Gratis
                            (MBG)</span
                        >
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Rancang sasaran penerima manfaat, menu, komposisi porsi,
                        dan jadwal operasional SPPG.
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <div v-if="isEditMode" class="flex items-center gap-2">
                        <span
                            class="text-xs font-bold text-amber-800 bg-amber-50 px-2.5 py-1.5 rounded-lg border border-amber-200 flex items-center gap-1"
                        >
                            <span>Mode Edit:</span>
                            <span class="font-mono">{{ woNo }}</span>
                        </span>
                        <button
                            type="button"
                            @click="
                                initForm();
                                router.visit(route('work-order.buat'));
                            "
                            class="px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 transition-colors flex items-center gap-1 cursor-pointer"
                        >
                            <RotateCcw class="h-3 w-3" />
                            <span>Batal Edit / Buat Baru</span>
                        </button>
                    </div>

                    <Link
                        :href="route('work-order.daftar')"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-primary bg-primary/10 hover:bg-primary/20 border border-primary/20 transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                    >
                        <FileSpreadsheet class="h-4 w-4" />
                        <span>Lihat Daftar WO</span>
                    </Link>
                </div>
            </div>

            <!-- Global Alert Notifikasi Sukses / Error -->
            <div
                v-if="showSubmitSuccessAlert"
                class="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs font-bold flex items-center justify-between gap-2 shadow-2xs animate-in fade-in"
            >
                <div class="flex items-center gap-2">
                    <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0" />
                    <span>{{ submitAlertMessage }}</span>
                </div>
                <button
                    type="button"
                    @click="showSubmitSuccessAlert = false"
                    class="text-emerald-700 hover:text-emerald-900"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div
                v-if="showSubmitErrorAlert"
                class="p-4 rounded-2xl bg-rose-50 border border-rose-300 text-rose-900 text-xs font-bold flex items-center justify-between gap-2 shadow-2xs animate-in fade-in"
            >
                <div class="flex items-center gap-2">
                    <AlertCircle class="h-5 w-5 text-rose-600 shrink-0" />
                    <span>{{ submitErrorMessage }}</span>
                </div>
                <button
                    type="button"
                    @click="showSubmitErrorAlert = false"
                    class="text-rose-700 hover:text-rose-900"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Tab Navigasi Mode Manual: Perencanaan Produksi & Formula Makanan -->
            <div
                v-if="metodeWo === 'manual'"
                class="flex items-center gap-2 p-1.5 bg-slate-100/90 rounded-2xl border border-slate-200/80 w-fit"
            >
                <button
                    type="button"
                    @click="activeManualTab = 'perencanaan'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2 cursor-pointer shadow-2xs',
                        activeManualTab === 'perencanaan'
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'bg-white text-slate-700 hover:text-slate-900 hover:bg-slate-50 border border-slate-200/70',
                    ]"
                >
                    <FileText class="h-4 w-4 shrink-0" />
                    <span>Perencanaan Produksi</span>
                </button>
                <button
                    type="button"
                    @click="handleSwitchToFormulaTab"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2 cursor-pointer shadow-2xs',
                        activeManualTab === 'formula'
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'bg-white text-slate-700 hover:text-slate-900 hover:bg-slate-50 border border-slate-200/70',
                    ]"
                >
                    <Package class="h-4 w-4 shrink-0" />
                    <span>Formula Makanan</span>
                </button>
            </div>

            <!-- FORM PERENCANAAN PRODUKSI -->
            <div
                v-show="
                    metodeWo === 'sistem' || activeManualTab === 'perencanaan'
                "
                class="space-y-6"
            >
                <Card
                    className="bg-white border-slate-200 shadow-xs overflow-hidden"
                >
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-white"
                    >
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                        >
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <CardTitle
                                        class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2"
                                    >
                                        <FileSpreadsheet
                                            class="h-5 w-5 text-primary"
                                        />
                                        <span
                                            >Menyusun Perencanaan Produksi</span
                                        >
                                    </CardTitle>
                                </div>
                                <CardDescription
                                    class="text-xs sm:text-sm mt-0.5"
                                >
                                    Penetapan jadwal distribusi menu, penamaan
                                    menu, dan menentukan Penerima Manfaat (PM).
                                </CardDescription>
                            </div>

                            <!-- Toggle Pilihan Metode: Sistem vs Manual (Terkunci saat Mode Edit) -->
                            <div
                                class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl border border-slate-200 self-start md:self-auto"
                            >
                                <button
                                    type="button"
                                    :disabled="isEditMode"
                                    @click="
                                        if (!isEditMode) {
                                            metodeWo = 'sistem';
                                            activeManualTab = 'perencanaan';
                                        }
                                    "
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5',
                                        metodeWo === 'sistem'
                                            ? 'bg-white text-primary shadow-2xs font-black'
                                            : 'text-slate-600 hover:text-slate-900',
                                        isEditMode
                                            ? 'cursor-not-allowed opacity-60 select-none'
                                            : 'cursor-pointer',
                                    ]"
                                    :title="
                                        isEditMode
                                            ? 'Metode Work Order terkunci dan tidak dapat diubah pada Mode Edit'
                                            : 'Pilih Mode Sistem'
                                    "
                                >
                                    <span>Sistem</span>
                                </button>
                                <button
                                    type="button"
                                    :disabled="isEditMode"
                                    @click="
                                        if (!isEditMode) {
                                            metodeWo = 'manual';
                                            activeManualTab = 'perencanaan';
                                        }
                                    "
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5',
                                        metodeWo === 'manual'
                                            ? 'bg-amber-600 text-white shadow-2xs font-black'
                                            : 'text-amber-900 hover:text-amber-950',
                                        isEditMode
                                            ? 'cursor-not-allowed opacity-60 select-none'
                                            : 'cursor-pointer',
                                    ]"
                                    :title="
                                        isEditMode
                                            ? 'Metode Work Order terkunci dan tidak dapat diubah pada Mode Edit'
                                            : 'Pilih Mode Manual'
                                    "
                                >
                                    <span>Manual</span>
                                </button>

                                <span
                                    v-if="isEditMode"
                                    class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 text-[10px] font-bold flex items-center gap-1 ml-0.5 border border-amber-200/80"
                                    title="Metode Work Order tidak dapat diubah saat mengedit Work Order yang sudah ada"
                                >
                                    <Lock class="h-3 w-3 text-amber-700" />
                                    <span>Terkunci</span>
                                </span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5 sm:p-6 space-y-6">
                        <!-- Form Identitas Perencanaan Produksi (1 Baris Bagi 3) -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <!-- Kolom 1: No. Perencanaan Produksi -->
                            <div class="space-y-1.5">
                                <div
                                    class="flex items-center justify-between gap-1 min-h-[22px]"
                                >
                                    <label
                                        class="text-xs font-bold text-slate-700 block truncate"
                                    >
                                        No. Perencanaan Produksi
                                    </label>
                                </div>
                                <input
                                    type="text"
                                    :value="woNo"
                                    readonly
                                    disabled
                                    class="w-full h-11 text-xs font-black text-slate-800 rounded-xl border border-slate-200 bg-slate-100/80 px-3.5 flex items-center cursor-not-allowed select-all shadow-2xs"
                                    title="Nomor Perencanaan Produksi otomatis mengacu pada tanggal distribusi kalender menu"
                                />
                            </div>

                            <!-- Kolom 2: Tanggal Distribusi Menu -->
                            <div
                                class="space-y-1.5"
                                ref="datePickerContainerRef"
                            >
                                <div
                                    class="flex items-center justify-between gap-1 min-h-[22px]"
                                >
                                    <label
                                        class="text-xs font-bold text-slate-700 block truncate"
                                    >
                                        Tanggal Distribusi Menu
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <div
                                        class="flex items-center gap-1.5 shrink-0 text-[10.5px] font-bold"
                                    >
                                        <button
                                            type="button"
                                            @click="setTanggalHariIni"
                                            :class="
                                                isTodayTaken
                                                    ? 'text-slate-400 cursor-not-allowed line-through'
                                                    : 'text-primary hover:underline cursor-pointer'
                                            "
                                            :title="
                                                isTodayTaken
                                                    ? `Hari ini sudah ada WO: ${getTakenWoInfo(todayStr.value)?.nama_menu}`
                                                    : 'Pilih Tanggal Hari Ini'
                                            "
                                        >
                                            Hari Ini
                                        </button>
                                        <span class="text-slate-300">•</span>
                                        <button
                                            type="button"
                                            @click="setTanggalBesok"
                                            :class="
                                                isTomorrowTaken
                                                    ? 'text-slate-400 cursor-not-allowed line-through'
                                                    : 'text-primary hover:underline cursor-pointer'
                                            "
                                            :title="
                                                isTomorrowTaken
                                                    ? `Besok sudah ada WO: ${getTakenWoInfo(tomorrowStr.value)?.nama_menu}`
                                                    : 'Pilih Tanggal Besok'
                                            "
                                        >
                                            Besok
                                        </button>
                                    </div>
                                </div>

                                <!-- Input Trigger & Kalender Dropdown Popover -->
                                <div class="relative">
                                    <div
                                        @click="toggleDatePickerPopover"
                                        class="w-full h-11 flex items-center justify-between gap-2 text-xs font-bold rounded-xl border px-3.5 bg-white cursor-pointer transition select-none shadow-2xs hover:border-primary/60"
                                        :class="[
                                            validationErrors.tanggalRencana ||
                                            currentDateConflict
                                                ? 'border-rose-400 ring-2 ring-rose-200 bg-rose-50/20 text-rose-950'
                                                : 'border-slate-300 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 text-slate-800',
                                        ]"
                                        title="Klik untuk membuka kalender pemilihan tanggal"
                                    >
                                        <div
                                            class="flex items-center gap-2 truncate"
                                        >
                                            <Calendar
                                                class="h-4 w-4 shrink-0"
                                                :class="
                                                    currentDateConflict
                                                        ? 'text-rose-500'
                                                        : 'text-primary'
                                                "
                                            />
                                            <span
                                                class="font-extrabold truncate"
                                            >
                                                {{
                                                    formatTanggalIndo(
                                                        tanggalRencana,
                                                    )
                                                }}
                                            </span>
                                            <span
                                                class="text-[10px] text-slate-400 font-mono hidden sm:inline"
                                            >
                                                ({{ tanggalRencana }})
                                            </span>
                                        </div>
                                        <div
                                            class="flex items-center gap-1.5 shrink-0"
                                        >
                                            <span
                                                v-if="currentDateConflict"
                                                class="text-[9.5px] font-black uppercase px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-300"
                                            >
                                                Sudah Ada WO
                                            </span>
                                            <span
                                                v-else
                                                class="text-[9.5px] font-bold uppercase px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-300"
                                            >
                                                Tersedia
                                            </span>
                                            <ChevronDown
                                                class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                                                :class="
                                                    showDatePickerPopover
                                                        ? 'rotate-180 text-primary'
                                                        : ''
                                                "
                                            />
                                        </div>
                                    </div>

                                    <!-- Native date input fallback -->
                                    <input
                                        type="date"
                                        v-model="tanggalRencana"
                                        @change="onNativeDateChange"
                                        class="sr-only"
                                        tabindex="-1"
                                        aria-hidden="true"
                                    />

                                    <!-- POPOVER KALENDER INTERAKTIF DENGAN TANDA WO -->
                                    <div
                                        v-if="showDatePickerPopover"
                                        class="absolute z-50 top-full left-0 mt-2 w-[320px] sm:w-[350px] bg-white rounded-2xl shadow-2xl border border-slate-200 p-4 space-y-3 animate-in fade-in zoom-in-95 duration-150"
                                    >
                                        <!-- Header Bulan & Navigasi -->
                                        <div
                                            class="flex items-center justify-between pb-2 border-b border-slate-100"
                                        >
                                            <button
                                                type="button"
                                                @click.stop="prevPickerMonth"
                                                class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-600 transition cursor-pointer"
                                                title="Bulan Sebelumnya"
                                            >
                                                <ChevronLeft class="h-4 w-4" />
                                            </button>
                                            <div
                                                class="text-xs font-black text-slate-800 tracking-wide"
                                            >
                                                {{ pickerMonthLabel }}
                                            </div>
                                            <button
                                                type="button"
                                                @click.stop="nextPickerMonth"
                                                class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-600 transition cursor-pointer"
                                                title="Bulan Berikutnya"
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </button>
                                        </div>

                                        <!-- Nama Hari (Sen - Min) -->
                                        <div
                                            class="grid grid-cols-7 gap-1 text-center text-[10.5px] font-bold text-slate-500 uppercase"
                                        >
                                            <span class="py-1">Sen</span>
                                            <span class="py-1">Sel</span>
                                            <span class="py-1">Rab</span>
                                            <span class="py-1">Kam</span>
                                            <span class="py-1">Jum</span>
                                            <span class="py-1 text-amber-600"
                                                >Sab</span
                                            >
                                            <span class="py-1 text-rose-600"
                                                >Min</span
                                            >
                                        </div>

                                        <!-- Grid Tanggal -->
                                        <div
                                            class="grid grid-cols-7 gap-1 text-center"
                                        >
                                            <button
                                                v-for="(
                                                    day, dIdx
                                                ) in pickerCalendarDays"
                                                :key="
                                                    'picker-day-' +
                                                    dIdx +
                                                    '-' +
                                                    day.dateStr
                                                "
                                                type="button"
                                                @click.stop="
                                                    handleSelectPickerDate(day)
                                                "
                                                :disabled="day.isTaken"
                                                :title="
                                                    day.isTaken
                                                        ? `SUDAH ADA WO: ${day.takenInfo.nama_menu} (${day.takenInfo.nomor_wo} - Status: ${day.takenInfo.status})`
                                                        : `Pilih tanggal ${day.dateStr}`
                                                "
                                                class="h-9 relative rounded-xl text-xs font-bold transition flex flex-col items-center justify-center select-none"
                                                :class="[
                                                    day.isTaken
                                                        ? 'bg-rose-50/90 border border-rose-300 text-rose-600 cursor-not-allowed opacity-80'
                                                        : day.isSelected
                                                          ? 'bg-primary text-white font-extrabold shadow-md ring-2 ring-primary/40'
                                                          : day.isToday
                                                            ? 'border border-primary text-primary hover:bg-primary/10 cursor-pointer'
                                                            : day.isCurrentMonth
                                                              ? 'text-slate-800 hover:bg-slate-100 cursor-pointer'
                                                              : 'text-slate-300 hover:bg-slate-50 cursor-pointer',
                                                ]"
                                            >
                                                <span
                                                    :class="
                                                        day.isTaken
                                                            ? 'line-through text-rose-500 text-[11px]'
                                                            : ''
                                                    "
                                                >
                                                    {{ day.dayNumber }}
                                                </span>

                                                <!-- Indikator Tag Jika Sudah Ada WO -->
                                                <span
                                                    v-if="day.isTaken"
                                                    class="text-[7.5px] font-black text-rose-700 leading-none tracking-tighter"
                                                >
                                                    WO ADA
                                                </span>
                                                <!-- Dot jika hari ini dan belum dipilih -->
                                                <span
                                                    v-else-if="
                                                        day.isToday &&
                                                        !day.isSelected
                                                    "
                                                    class="h-1 w-1 rounded-full bg-primary mt-0.5"
                                                ></span>
                                            </button>
                                        </div>

                                        <!-- Legenda & Keterangan Popover -->
                                        <div
                                            class="pt-2.5 border-t border-slate-100 space-y-2"
                                        >
                                            <div
                                                class="flex items-center justify-between text-[10px] text-slate-500 font-semibold px-0.5"
                                            >
                                                <div
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <span
                                                        class="h-2.5 w-2.5 rounded bg-rose-100 border border-rose-300 ring-1 ring-rose-200"
                                                    ></span>
                                                    <span
                                                        >Sudah Ada WO
                                                        (Terkunci)</span
                                                    >
                                                </div>
                                                <div
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <span
                                                        class="h-2.5 w-2.5 rounded bg-primary"
                                                    ></span>
                                                    <span>Terpilih</span>
                                                </div>
                                                <div
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <span
                                                        class="h-2.5 w-2.5 rounded bg-white border border-slate-300"
                                                    ></span>
                                                    <span>Tersedia</span>
                                                </div>
                                            </div>
                                            <p
                                                class="text-[9.5px] text-slate-400 leading-tight"
                                            >
                                                * 1 tanggal hanya diperbolehkan
                                                1 Work Order (termasuk status
                                                Draft).
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pesan Peringatan Jika Tanggal Sudah Dipakai (Conflict Alert) -->
                                <div
                                    v-if="currentDateConflict"
                                    class="p-2.5 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-800 space-y-1 mt-1.5 shadow-2xs"
                                >
                                    <div
                                        class="font-extrabold flex items-center gap-1.5 text-rose-900"
                                    >
                                        <AlertTriangle
                                            class="h-4 w-4 text-rose-600 shrink-0"
                                        />
                                        <span
                                            >Tanggal Ini Sudah Memiliki Work
                                            Order!</span
                                        >
                                    </div>
                                    <p
                                        class="text-[11px] leading-relaxed text-rose-700"
                                    >
                                        Tanggal
                                        <strong>{{
                                            formatTanggalIndo(tanggalRencana)
                                        }}</strong>
                                        sudah digunakan untuk menu
                                        <strong
                                            >"{{
                                                currentDateConflict.nama_menu
                                            }}"</strong
                                        >
                                        (Nomor:
                                        {{ currentDateConflict.nomor_wo }} •
                                        Status:
                                        <span class="uppercase font-bold">{{
                                            currentDateConflict.status
                                        }}</span
                                        >).
                                        <strong
                                            >Hanya boleh 1 Work Order per
                                            tanggal (termasuk status
                                            Draft).</strong
                                        >
                                        Silakan pilih tanggal lain yang masih
                                        tersedia.
                                    </p>
                                </div>

                                <!-- Error Message jika ada validation error lain -->
                                <p
                                    v-if="
                                        validationErrors.tanggalRencana &&
                                        !currentDateConflict
                                    "
                                    class="text-[11px] text-rose-600 font-semibold mt-1 flex items-center gap-1"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{
                                        validationErrors.tanggalRencana
                                    }}</span>
                                </p>
                            </div>

                            <!-- Kolom 3: Nama Menu Produksi MBG -->
                            <div class="space-y-1.5">
                                <div
                                    class="flex items-center justify-between gap-1 min-h-[22px]"
                                >
                                    <label
                                        class="text-xs font-bold text-slate-700 truncate"
                                    >
                                        Nama Menu Produksi
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="handleGunakanContoh"
                                        class="text-[10.5px] font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer shrink-0 truncate max-w-[150px]"
                                        title="Gunakan Contoh"
                                    >
                                        <Sparkles
                                            class="h-3 w-3 shrink-0 text-amber-500"
                                        />
                                        <span class="truncate"
                                            >Gunakan Contoh</span
                                        >
                                    </button>
                                </div>
                                <input
                                    type="text"
                                    v-model="namaMenuAktif"
                                    @input="clearError('namaMenuAktif')"
                                    required
                                    placeholder="Contoh: Mujair Nyat-Nyat Kintamani"
                                    :class="[
                                        'w-full h-11 text-xs font-bold text-slate-900 rounded-xl border px-3.5 bg-white shadow-2xs transition-all',
                                        validationErrors.namaMenuAktif
                                            ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                            : 'border-slate-300 focus:ring-primary focus:border-primary',
                                    ]"
                                />
                                <p
                                    v-if="validationErrors.namaMenuAktif"
                                    class="text-[11px] text-rose-600 font-semibold mt-1 flex items-center gap-1"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{
                                        validationErrors.namaMenuAktif
                                    }}</span>
                                </p>
                                <!-- Real-time Warning Alergi pada Nama Menu Utama -->
                                <div
                                    v-if="detectedAllergensMenuUtama.length > 0"
                                    class="flex flex-wrap items-center gap-1.5 pt-1"
                                >
                                    <span
                                        class="text-[10.5px] font-extrabold text-slate-700 flex items-center gap-1"
                                    >
                                        <AlertTriangle
                                            class="h-3.5 w-3.5 text-amber-600 shrink-0"
                                        />
                                        <span>Alergen PM Terdeteksi:</span>
                                    </span>
                                    <span
                                        v-for="al in detectedAllergensMenuUtama"
                                        :key="al.jenis"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-100/90 text-slate-800 border border-amber-300 text-[10.5px] font-bold shadow-2xs"
                                    >
                                        ⚠️ {{ al.jenis }} ({{ al.total_pm }} PM)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Rincian Sub Menu Komponen Gizi (Sub Menu 1 s.d. Sub Menu 5) -->
                        <div
                            class="p-4 rounded-2xl bg-white border border-slate-200 space-y-3"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 sm:pb-0"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-6 w-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0"
                                    >
                                        ✦
                                    </div>
                                    <div>
                                        <h4
                                            class="text-xs font-black text-slate-900"
                                        >
                                            Rincian Sub Menu
                                        </h4>
                                        <p class="text-[10.5px] text-slate-500">
                                            Input rincian untuk masing-masing
                                            Sub Menu (minimal 5 Sub Menu).
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-2 w-full sm:w-auto"
                                >
                                    <button
                                        type="button"
                                        @click="handleQuickPasteButtonClick"
                                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-2 sm:py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all cursor-pointer shadow-2xs border border-slate-200 hover:border-slate-300 whitespace-nowrap shrink-0"
                                        title="Tempel list dari Excel, Word, Notepad, atau teks lainnya"
                                    >
                                        <ClipboardPaste
                                            class="h-3.5 w-3.5 text-primary shrink-0"
                                        />
                                        <span>Tempel List</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="addSubMenu"
                                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-2 sm:py-1.5 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary text-xs font-black transition-all cursor-pointer shadow-2xs border border-primary/20 whitespace-nowrap shrink-0"
                                        title="Tambah Sub Menu Baru"
                                    >
                                        <Plus class="h-3.5 w-3.5 shrink-0" />
                                        <span>Tambah Sub Menu</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Banner Real-time Deteksi Alergi PM -->
                            <div
                                v-if="realTimeAllergyAlerts.length > 0"
                                class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 text-slate-800 space-y-2.5 text-xs shadow-2xs transition-all duration-300"
                            >
                                <div
                                    class="flex items-center justify-between flex-wrap gap-2"
                                >
                                    <div
                                        class="flex items-center gap-1.5 font-black text-slate-800 text-xs"
                                    >
                                        <ShieldAlert
                                            class="h-4 w-4 text-amber-600 shrink-0"
                                        />
                                        <span
                                            >Peringatan: Terdeteksi
                                            {{ realTimeAllergyAlerts.length }}
                                            Sub Menu Mengandung Bahan Alergi
                                            PM</span
                                        >
                                    </div>
                                    <span
                                        class="text-[10px] font-bold text-slate-700 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-200 shrink-0"
                                    >
                                        ⚡ Realtime Deteksi Alergi
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <div
                                        v-for="(
                                            al, alIdx
                                        ) in realTimeAllergyAlerts"
                                        :key="alIdx"
                                        class="p-2.5 sm:px-3 sm:py-2 rounded-xl border bg-white shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 transition-all"
                                        :class="
                                            al.hasReplacement
                                                ? 'border-emerald-300 bg-emerald-50/20'
                                                : 'border-rose-300 bg-rose-50/20'
                                        "
                                    >
                                        <!-- Bagian Kiri/Atas: Sub Menu & Menu & Alergi -->
                                        <div
                                            class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-[11px] min-w-0"
                                        >
                                            <span
                                                class="font-black text-slate-900 bg-slate-100 px-2 py-0.5 rounded-md text-[10px] shrink-0"
                                            >
                                                {{ al.subLabel }}
                                            </span>
                                            <span
                                                class="font-extrabold text-slate-800 text-xs truncate max-w-[150px] sm:max-w-none"
                                            >
                                                "{{ al.menuName }}"
                                            </span>
                                            <span
                                                class="text-slate-400 font-bold shrink-0"
                                                >➔</span
                                            >
                                            <span
                                                class="font-extrabold text-rose-700 bg-rose-100/70 border border-rose-200 px-2 py-0.5 rounded-md text-[10.5px] shrink-0"
                                            >
                                                {{
                                                    formatAllergenDisplay(
                                                        al.allergen,
                                                    )
                                                }}
                                                ({{ al.totalPm }} PM)
                                            </span>
                                        </div>

                                        <!-- Bagian Kanan/Bawah: Menu Pengganti -->
                                        <div
                                            class="flex items-center justify-end shrink-0 pt-1.5 sm:pt-0 border-t sm:border-t-0 border-slate-100"
                                        >
                                            <span
                                                v-if="al.hasReplacement"
                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1 text-emerald-800 font-extrabold text-[10.5px] bg-emerald-100/80 px-2.5 py-1 rounded-lg border border-emerald-300 shadow-2xs"
                                            >
                                                <Check
                                                    class="h-3 w-3 text-emerald-600 shrink-0"
                                                />
                                                <span
                                                    >Pengganti:
                                                    <strong>{{
                                                        al.replacementName
                                                    }}</strong></span
                                                >
                                            </span>
                                            <button
                                                v-else
                                                type="button"
                                                @click="
                                                    addPenggantiAlergiWithPreset(
                                                        al.subKey,
                                                        al.allergen,
                                                    )
                                                "
                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1 text-rose-700 hover:text-white font-extrabold text-[11px] bg-white hover:bg-rose-600 px-3 py-1.5 rounded-lg border border-rose-300 hover:border-rose-600 shadow-2xs cursor-pointer transition-colors"
                                                title="Tambahkan menu pengganti sekarang"
                                            >
                                                <Plus
                                                    class="h-3.5 w-3.5 shrink-0"
                                                />
                                                <span
                                                    >Tambah Menu Pengganti</span
                                                >
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 items-start">
                                <div
                                    v-for="(subKey, sIdx) in subMenuKeys"
                                    :key="subKey"
                                    class="space-y-2 bg-white p-2.5 rounded-xl border shadow-2xs flex flex-col h-fit transition-all flex-1 min-w-[200px] basis-[200px]"
                                    :class="
                                        validationErrors[subKey]
                                            ? 'border-rose-400 bg-rose-50/20'
                                            : 'border-slate-200'
                                    "
                                >
                                    <div class="space-y-1">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <label
                                                class="text-[11px] font-black text-slate-800 flex items-center gap-1.5"
                                            >
                                                <span
                                                    class="h-2 w-2 rounded-full"
                                                    :class="
                                                        sIdx < 5
                                                            ? 'bg-slate-700'
                                                            : 'bg-primary'
                                                    "
                                                ></span>
                                                <span
                                                    >Sub Menu {{ sIdx + 1 }}
                                                    <strong
                                                        class="text-rose-500"
                                                        >*</strong
                                                    ></span
                                                >
                                            </label>

                                            <!-- Tombol Hapus jika lebih dari 5 sub menu -->
                                            <button
                                                v-if="subMenuKeys.length > 5"
                                                type="button"
                                                @click="removeSubMenu(sIdx)"
                                                class="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                :title="`Hapus Sub Menu ${sIdx + 1}`"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>

                                        <input
                                            type="text"
                                            v-model="subMenuKomponen[subKey]"
                                            @input="clearError(subKey)"
                                            @paste="
                                                handlePasteSubMenu($event, sIdx)
                                            "
                                            :placeholder="
                                                getSubMenuDefaultPlaceholder(
                                                    sIdx,
                                                )
                                            "
                                            :class="[
                                                'w-full text-xs font-semibold rounded-lg border p-2',
                                                validationErrors[subKey]
                                                    ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                                    : 'border-slate-200 focus:ring-primary focus:border-primary bg-white',
                                            ]"
                                        />
                                        <p
                                            v-if="validationErrors[subKey]"
                                            class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-3 w-3 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[subKey]
                                            }}</span>
                                        </p>

                                        <!-- Warning Realtime Alergi Sub Menu -->
                                        <div
                                            v-if="
                                                detectedAllergensPerSubMenu[
                                                    subKey
                                                ] &&
                                                detectedAllergensPerSubMenu[
                                                    subKey
                                                ].length > 0
                                            "
                                            class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-800 space-y-1 text-[10px]"
                                        >
                                            <div
                                                class="flex items-center gap-1 font-extrabold text-slate-800"
                                            >
                                                <AlertTriangle
                                                    class="h-3 w-3 text-amber-600 shrink-0"
                                                />
                                                <span
                                                    >Alergi PM Terdeteksi:</span
                                                >
                                            </div>
                                            <div
                                                v-for="al in detectedAllergensPerSubMenu[
                                                    subKey
                                                ]"
                                                :key="al.jenis"
                                                class="flex items-center justify-between gap-1 text-[9.5px] leading-tight text-slate-800"
                                            >
                                                <span
                                                    >⚠️
                                                    <strong
                                                        >{{
                                                            al.total_pm
                                                        }}
                                                        PM</strong
                                                    >
                                                    alergi
                                                    <strong>{{
                                                        al.jenis
                                                    }}</strong></span
                                                >
                                                <button
                                                    type="button"
                                                    @click="
                                                        addPenggantiAlergiWithPreset(
                                                            subKey,
                                                            al.jenis,
                                                        )
                                                    "
                                                    class="text-slate-700 font-bold hover:underline cursor-pointer shrink-0"
                                                    title="Tambah opsi pengganti untuk alergi ini"
                                                >
                                                    + Tambah
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Opsi Menu Pengganti Alergi -->
                                    <div
                                        class="pt-2 border-t border-slate-100 space-y-1.5"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                class="text-[10px] font-bold text-slate-500 flex items-center gap-1"
                                            >
                                                <span>🛡️ Alergi:</span>
                                                <span
                                                    v-if="
                                                        subMenuAlergi[subKey] &&
                                                        subMenuAlergi[subKey]
                                                            .length > 0
                                                    "
                                                    class="text-rose-600 font-extrabold"
                                                    >({{
                                                        subMenuAlergi[subKey]
                                                            .length
                                                    }})</span
                                                >
                                            </span>
                                            <button
                                                type="button"
                                                @click="
                                                    addPenggantiAlergi(subKey)
                                                "
                                                class="text-[10px] font-bold text-slate-600 hover:text-slate-900 hover:underline flex items-center gap-0.5 cursor-pointer"
                                                title="Tambah menu pengganti jika ada PM alergi"
                                            >
                                                <Plus class="h-3 w-3" />
                                                <span>Pengganti</span>
                                            </button>
                                        </div>

                                        <div
                                            v-for="(
                                                alItem, alIdx
                                            ) in subMenuAlergi[subKey]"
                                            :key="alIdx"
                                            class="p-1.5 rounded-lg border space-y-1 transition-all"
                                            :class="
                                                validationErrors[
                                                    'alergi_' +
                                                        subKey +
                                                        '_' +
                                                        alIdx +
                                                        '_jenis'
                                                ] ||
                                                validationErrors[
                                                    'alergi_' +
                                                        subKey +
                                                        '_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                                    ? 'bg-rose-50 border-rose-400 ring-1 ring-rose-300'
                                                    : 'bg-white border-slate-200'
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <select
                                                    v-model="
                                                        alItem.jenis_alergi
                                                    "
                                                    @change="
                                                        clearError(
                                                            'alergi_' +
                                                                subKey +
                                                                '_' +
                                                                alIdx +
                                                                '_jenis',
                                                        )
                                                    "
                                                    :class="[
                                                        'w-full text-[10.5px] font-bold rounded py-0.5 px-1.5 focus:ring-1 bg-white',
                                                        validationErrors[
                                                            'alergi_' +
                                                                subKey +
                                                                '_' +
                                                                alIdx +
                                                                '_jenis'
                                                        ]
                                                            ? 'border border-rose-400 text-rose-900 focus:ring-rose-400'
                                                            : 'border border-slate-200 text-slate-900 focus:ring-primary focus:border-primary',
                                                    ]"
                                                >
                                                    <option value="" disabled>
                                                        -- Pilih Jenis Alergi
                                                        (Wajib) --
                                                    </option>
                                                    <option
                                                        v-for="opt in masterAlergenOptions"
                                                        :key="opt.value"
                                                        :value="opt.value"
                                                    >
                                                        {{ opt.label }}
                                                    </option>
                                                </select>
                                                <button
                                                    type="button"
                                                    @click="
                                                        removePenggantiAlergi(
                                                            subKey,
                                                            alIdx,
                                                        )
                                                    "
                                                    class="h-5 w-5 shrink-0 rounded bg-white hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center cursor-pointer"
                                                    title="Hapus opsi ini"
                                                >
                                                    <Trash2
                                                        class="h-2.5 w-2.5"
                                                    />
                                                </button>
                                            </div>
                                            <p
                                                v-if="
                                                    validationErrors[
                                                        'alergi_' +
                                                            subKey +
                                                            '_' +
                                                            alIdx +
                                                            '_jenis'
                                                    ]
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span>{{
                                                    validationErrors[
                                                        "alergi_" +
                                                            subKey +
                                                            "_" +
                                                            alIdx +
                                                            "_jenis"
                                                    ]
                                                }}</span>
                                            </p>

                                            <input
                                                type="text"
                                                v-model="alItem.menu_pengganti"
                                                @input="
                                                    clearError(
                                                        'alergi_' +
                                                            subKey +
                                                            '_' +
                                                            alIdx +
                                                            '_menu',
                                                    )
                                                "
                                                placeholder="Menu pengganti (wajib)..."
                                                :class="[
                                                    'w-full text-[10.5px] font-medium text-slate-800 bg-white rounded py-0.5 px-1.5 focus:ring-1 placeholder:text-slate-400',
                                                    validationErrors[
                                                        'alergi_' +
                                                            subKey +
                                                            '_' +
                                                            alIdx +
                                                            '_menu'
                                                    ]
                                                        ? 'border border-rose-400 focus:ring-rose-400'
                                                        : 'border border-slate-200 focus:ring-primary focus:border-primary',
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    validationErrors[
                                                        'alergi_' +
                                                            subKey +
                                                            '_' +
                                                            alIdx +
                                                            '_menu'
                                                    ]
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span>{{
                                                    validationErrors[
                                                        "alergi_" +
                                                            subKey +
                                                            "_" +
                                                            alIdx +
                                                            "_menu"
                                                    ]
                                                }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Waktu Kegiatan Operasional SPPG -->
                        <div
                            id="section-jadwal-operasional"
                            class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 space-y-4"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="h-8 w-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs"
                                    >
                                        <Clock class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4
                                                class="text-xs sm:text-sm font-black text-slate-900"
                                            >
                                                Waktu Kegiatan Operasional
                                            </h4>
                                        </div>
                                        <p
                                            class="text-[11px] text-slate-500 mt-0.5"
                                        >
                                            Tentukan jam pelaksanaan 6 tahapan
                                            operasional. Seluruh tahapan wajib
                                            diisi sebelum lanjut.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <!-- Status Badge Real-time -->
                                    <span
                                        v-if="totalJadwalTerisi === 6"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs"
                                    >
                                        <CheckCircle2
                                            class="h-3.5 w-3.5 text-emerald-600"
                                        />
                                        <span>Terisi Lengkap (6/6)</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs"
                                    >
                                        <AlertTriangle
                                            class="h-3.5 w-3.5 text-amber-600"
                                        />
                                        <span
                                            >{{ totalJadwalTerisi }}/6
                                            Terisi</span
                                        >
                                    </span>

                                    <!-- Tombol Set Jam Default -->
                                    <button
                                        type="button"
                                        @click="resetJadwalOperasionalToDefault"
                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 transition-colors shadow-2xs cursor-pointer shrink-0"
                                        title="Isi seluruh jam kegiatan dengan jadwal standar operasional secara otomatis"
                                    >
                                        <Sparkles
                                            class="h-3.5 w-3.5 text-amber-500"
                                        />
                                        <span>Gunakan Jam Default</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Grid 6 Kegiatan Operasional -->
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5"
                            >
                                <div
                                    v-for="(
                                        kegiatan, kKey
                                    ) in jadwalOperasionalList"
                                    :key="kKey"
                                    class="p-3.5 rounded-xl border transition-all duration-200"
                                    :class="[
                                        validationErrors['jadwal_' + kKey] ||
                                        validationErrors[
                                            'jadwal_' + kKey + '_mulai'
                                        ] ||
                                        validationErrors[
                                            'jadwal_' + kKey + '_selesai'
                                        ]
                                            ? 'border-rose-400 bg-rose-50/20 ring-1 ring-rose-300'
                                            : jadwalOperasional[kKey]?.mulai &&
                                                jadwalOperasional[kKey]?.selesai
                                              ? 'border-emerald-200/80 bg-white hover:bg-slate-50/60'
                                              : 'border-slate-200/90 bg-slate-50/60 hover:bg-slate-50/90',
                                    ]"
                                >
                                    <div
                                        class="flex items-center justify-between mb-1.5"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="h-5 w-5 rounded-lg text-[10.5px] font-black flex items-center justify-center shrink-0"
                                                :class="[
                                                    jadwalOperasional[kKey]
                                                        ?.mulai &&
                                                    jadwalOperasional[kKey]
                                                        ?.selesai
                                                        ? 'bg-emerald-100 text-emerald-800'
                                                        : 'bg-indigo-100/90 text-indigo-700',
                                                ]"
                                            >
                                                {{ kegiatan.no }}
                                            </span>
                                            <span
                                                class="text-xs font-black text-slate-800"
                                            >
                                                {{ kegiatan.nama }}
                                            </span>
                                        </div>
                                        <span
                                            v-if="
                                                jadwalOperasional[kKey]
                                                    ?.mulai &&
                                                jadwalOperasional[kKey]?.selesai
                                            "
                                            class="text-[10px] font-bold font-mono text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60 flex items-center gap-1"
                                        >
                                            <Check
                                                class="h-2.5 w-2.5 text-emerald-600"
                                            />
                                            {{ jadwalOperasional[kKey].mulai }}
                                            s.d
                                            {{
                                                jadwalOperasional[kKey].selesai
                                            }}
                                        </span>
                                        <span
                                            v-else-if="
                                                validationErrors[
                                                    'jadwal_' + kKey
                                                ] ||
                                                validationErrors[
                                                    'jadwal_' + kKey + '_mulai'
                                                ] ||
                                                validationErrors[
                                                    'jadwal_' +
                                                        kKey +
                                                        '_selesai'
                                                ]
                                            "
                                            class="text-[10px] font-bold font-mono text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200/70 flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 text-rose-600"
                                            />
                                            Wajib Diisi
                                        </span>
                                        <span
                                            v-else
                                            class="text-[10px] font-bold font-mono text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200"
                                        >
                                            --:-- s.d --:--
                                        </span>
                                    </div>

                                    <p
                                        class="text-[10px] text-slate-500 leading-tight mb-2.5"
                                    >
                                        {{ kegiatan.deskripsi }}
                                    </p>

                                    <div class="flex items-start gap-2">
                                        <!-- Jam Mulai -->
                                        <div class="flex-1">
                                            <div
                                                class="flex items-center justify-between mb-1"
                                            >
                                                <label
                                                    class="block text-[9.5px] font-bold text-slate-600 uppercase tracking-wider"
                                                >
                                                    Jam Mulai
                                                    <span class="text-rose-500"
                                                        >*</span
                                                    >
                                                </label>
                                                <span
                                                    v-if="
                                                        validationErrors[
                                                            'jadwal_' +
                                                                kKey +
                                                                '_mulai'
                                                        ] ||
                                                        (!jadwalOperasional[
                                                            kKey
                                                        ]?.mulai &&
                                                            validationErrors.jadwal_operasional)
                                                    "
                                                    class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1 py-0.2 rounded border border-rose-200"
                                                >
                                                    Wajib
                                                </span>
                                            </div>
                                            <input
                                                :id="
                                                    'input-jadwal-' +
                                                    kKey +
                                                    '-mulai'
                                                "
                                                type="time"
                                                v-model="
                                                    jadwalOperasional[kKey]
                                                        .mulai
                                                "
                                                @input="
                                                    clearError(
                                                        'jadwal_' +
                                                            kKey +
                                                            '_mulai',
                                                    );
                                                    clearError(
                                                        'jadwal_' + kKey,
                                                    );
                                                    clearError(
                                                        'jadwal_operasional',
                                                    );
                                                "
                                                required
                                                :class="[
                                                    'w-full text-xs font-bold rounded-lg border p-2 transition-colors shadow-2xs',
                                                    validationErrors[
                                                        'jadwal_' +
                                                            kKey +
                                                            '_mulai'
                                                    ] ||
                                                    (!jadwalOperasional[kKey]
                                                        ?.mulai &&
                                                        validationErrors.jadwal_operasional)
                                                        ? 'border-rose-400 bg-rose-50/40 text-rose-950 ring-2 ring-rose-200 focus:border-rose-500 focus:ring-rose-400'
                                                        : 'border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500',
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    validationErrors[
                                                        'jadwal_' +
                                                            kKey +
                                                            '_mulai'
                                                    ] ||
                                                    (!jadwalOperasional[kKey]
                                                        ?.mulai &&
                                                        validationErrors.jadwal_operasional)
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold mt-1 flex items-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span
                                                    >Jam mulai wajib diisi</span
                                                >
                                            </p>
                                        </div>

                                        <span
                                            class="text-xs font-bold text-slate-400 mt-6 shrink-0"
                                        >
                                            s.d
                                        </span>

                                        <!-- Jam Selesai -->
                                        <div class="flex-1">
                                            <div
                                                class="flex items-center justify-between mb-1"
                                            >
                                                <label
                                                    class="block text-[9.5px] font-bold text-slate-600 uppercase tracking-wider"
                                                >
                                                    Jam Selesai
                                                    <span class="text-rose-500"
                                                        >*</span
                                                    >
                                                </label>
                                                <span
                                                    v-if="
                                                        validationErrors[
                                                            'jadwal_' +
                                                                kKey +
                                                                '_selesai'
                                                        ] ||
                                                        (!jadwalOperasional[
                                                            kKey
                                                        ]?.selesai &&
                                                            validationErrors.jadwal_operasional)
                                                    "
                                                    class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1 py-0.2 rounded border border-rose-200"
                                                >
                                                    Wajib
                                                </span>
                                            </div>
                                            <input
                                                :id="
                                                    'input-jadwal-' +
                                                    kKey +
                                                    '-selesai'
                                                "
                                                type="time"
                                                v-model="
                                                    jadwalOperasional[kKey]
                                                        .selesai
                                                "
                                                @input="
                                                    clearError(
                                                        'jadwal_' +
                                                            kKey +
                                                            '_selesai',
                                                    );
                                                    clearError(
                                                        'jadwal_' + kKey,
                                                    );
                                                    clearError(
                                                        'jadwal_operasional',
                                                    );
                                                "
                                                required
                                                :class="[
                                                    'w-full text-xs font-bold rounded-lg border p-2 transition-colors shadow-2xs',
                                                    validationErrors[
                                                        'jadwal_' +
                                                            kKey +
                                                            '_selesai'
                                                    ] ||
                                                    (!jadwalOperasional[kKey]
                                                        ?.selesai &&
                                                        validationErrors.jadwal_operasional)
                                                        ? 'border-rose-400 bg-rose-50/40 text-rose-950 ring-2 ring-rose-200 focus:border-rose-500 focus:ring-rose-400'
                                                        : 'border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500',
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    validationErrors[
                                                        'jadwal_' +
                                                            kKey +
                                                            '_selesai'
                                                    ] ||
                                                    (!jadwalOperasional[kKey]
                                                        ?.selesai &&
                                                        validationErrors.jadwal_operasional)
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold mt-1 flex items-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span
                                                    >Jam selesai wajib
                                                    diisi</span
                                                >
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert Error Global Jadwal jika ada -->
                            <div
                                v-if="validationErrors.jadwal_operasional"
                                class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 font-semibold flex flex-col sm:flex-row sm:items-center justify-between gap-2.5"
                            >
                                <div class="flex items-center gap-2">
                                    <AlertCircle
                                        class="h-4 w-4 shrink-0 text-rose-600"
                                    />
                                    <span>{{
                                        validationErrors.jadwal_operasional
                                    }}</span>
                                </div>
                                <button
                                    type="button"
                                    @click="resetJadwalOperasionalToDefault"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-white hover:bg-rose-100 text-xs font-extrabold text-indigo-700 border border-indigo-200 rounded-lg shrink-0 cursor-pointer shadow-2xs"
                                >
                                    <Sparkles class="h-3 w-3 text-amber-500" />
                                    <span
                                        >Klik untuk Isi Jam Default
                                        Otomatis</span
                                    >
                                </button>
                            </div>
                        </div>

                        <!-- Ringkasan Kuota PM Fix Berdasarkan Tanggal Work Order -->
                        <div class="space-y-3 pt-4 border-t border-slate-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4
                                        class="text-sm font-black text-slate-900 flex items-center gap-2"
                                    >
                                        <Users class="h-4 w-4 text-primary" />
                                        <span
                                            >Data Penerima Manfaat (PM) per
                                            Tanggal Distribusi</span
                                        >
                                    </h4>
                                </div>
                            </div>

                            <!-- 4 Metric Cards Kuota PM Fix -->
                            <div
                                class="grid grid-cols-2 sm:grid-cols-4 gap-3.5"
                            >
                                <!-- Total PM -->
                                <div
                                    class="p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100/60 border border-blue-200/80 space-y-1 shadow-2xs"
                                >
                                    <p
                                        class="text-[11px] font-bold text-blue-700 uppercase tracking-wider"
                                    >
                                        Total PM
                                    </p>
                                    <h3
                                        class="text-2xl font-black text-blue-950"
                                    >
                                        {{ totalPM.toLocaleString("id-ID") }}
                                        <span
                                            class="text-xs font-semibold text-blue-700"
                                            >Porsi</span
                                        >
                                    </h3>
                                    <p
                                        class="text-[10.5px] text-blue-600 font-medium"
                                    >
                                        {{ persentasePmMenerima }}% Kuota
                                        Distribusi Harian
                                    </p>
                                </div>

                                <!-- Porsi Kecil -->
                                <div
                                    class="p-4 rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100/60 border border-amber-200/80 space-y-1 shadow-2xs"
                                >
                                    <p
                                        class="text-[11px] font-bold text-slate-700 uppercase tracking-wider"
                                    >
                                        Porsi Kecil (PK)
                                    </p>
                                    <h3
                                        class="text-2xl font-black text-amber-950"
                                    >
                                        {{ totalPK.toLocaleString("id-ID") }}
                                        <span
                                            class="text-xs font-semibold text-slate-700"
                                            >Porsi</span
                                        >
                                    </h3>
                                    <p
                                        class="text-[10.5px] text-amber-700 font-medium"
                                    >
                                        TK/RA, PAUD, SD/MI 1-3 & Balita
                                    </p>
                                </div>

                                <!-- Porsi Besar -->
                                <div
                                    class="p-4 rounded-2xl bg-gradient-to-br from-indigo-50 to-indigo-100/60 border border-indigo-200/80 space-y-1 shadow-2xs"
                                >
                                    <p
                                        class="text-[11px] font-bold text-indigo-800 uppercase tracking-wider"
                                    >
                                        Porsi Besar (PB)
                                    </p>
                                    <h3
                                        class="text-2xl font-black text-indigo-950"
                                    >
                                        {{ totalPB.toLocaleString("id-ID") }}
                                        <span
                                            class="text-xs font-semibold text-indigo-800"
                                            >Porsi</span
                                        >
                                    </h3>
                                    <p
                                        class="text-[10.5px] text-indigo-700 font-medium"
                                    >
                                        SD/MI 4-6, SMP/MTs, SMA/SMK/MA, Guru,
                                        Tendik, Bumil & Busui
                                    </p>
                                </div>

                                <!-- Varian Khusus Alergi -->
                                <div
                                    class="p-4 rounded-2xl bg-gradient-to-br from-rose-50 to-rose-100/60 border border-rose-200/80 space-y-1 shadow-2xs"
                                >
                                    <p
                                        class="text-[11px] font-bold text-rose-800 uppercase tracking-wider"
                                    >
                                        Varian Alergi Khusus
                                    </p>
                                    <h3
                                        class="text-2xl font-black text-rose-950"
                                    >
                                        {{
                                            (
                                                totalPKAlergi + totalPBAlergi
                                            ).toLocaleString("id-ID")
                                        }}
                                        <span
                                            class="text-xs font-semibold text-rose-800"
                                            >PM</span
                                        >
                                    </h3>
                                    <p
                                        class="text-[10.5px] text-rose-700 font-medium"
                                    >
                                        {{ totalPKAlergi }} PK •
                                        {{ totalPBAlergi }} PB Membutuhkan
                                        Substitusi
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Rincian Kelompok Penerima Manfaat Terjadwal -->
                        <div class="space-y-3 pt-2">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                            >
                                <div>
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <h5
                                            class="text-xs font-bold text-slate-800 uppercase tracking-wider"
                                        >
                                            Daftar Kelompok Penerima Manfaat ({{
                                                woKelompokList.length
                                            }}
                                            Kelompok)
                                        </h5>
                                        <Badge
                                            variant="outline"
                                            class="text-[11px] font-extrabold bg-emerald-50 text-emerald-800 border-emerald-300"
                                        >
                                            <UserCheck class="h-3 w-3 mr-1" />
                                            {{ kelompokMenerimaAktif.length }}
                                            Menerima
                                        </Badge>
                                        <Badge
                                            v-if="
                                                woKelompokList.length >
                                                kelompokMenerimaAktif.length
                                            "
                                            variant="outline"
                                            class="text-[11px] font-extrabold bg-rose-50 text-rose-800 border-rose-300"
                                        >
                                            <UserX class="h-3 w-3 mr-1" />
                                            {{
                                                woKelompokList.length -
                                                kelompokMenerimaAktif.length
                                            }}
                                            Tidak Menerima
                                        </Badge>
                                    </div>
                                    <p
                                        class="text-[11px] text-slate-500 mt-0.5"
                                    >
                                        Kelompok yang dinyatakan
                                        <strong>"Tidak Menerima"</strong>
                                        kuotanya otomatis dinolkan dari
                                        perhitungan Work Order ini.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        @click="handleResetWoKelompokList"
                                        className="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3 h-8 flex items-center gap-1 cursor-pointer shadow-none border border-slate-200"
                                        title="Kembalikan semua kelompok default dari database"
                                    >
                                        <RotateCcw class="h-3.5 w-3.5" />
                                        <span>Kembalikan</span>
                                    </Button>
                                </div>
                            </div>
                            <div
                                class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white"
                            >
                                <div class="overflow-x-auto">
                                    <table
                                        class="w-full min-w-[650px] text-left text-xs border-collapse"
                                    >
                                        <thead>
                                            <tr
                                                class="bg-white border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                                            >
                                                <th
                                                    class="py-3.5 px-3 text-center w-10"
                                                >
                                                    No
                                                </th>
                                                <th
                                                    class="py-3.5 px-3 text-center"
                                                >
                                                    Status
                                                </th>
                                                <th class="py-3.5 px-3">
                                                    Nama Kelompok Penerima
                                                    Manfaat
                                                </th>
                                                <th class="py-3.5 px-3">
                                                    Kategori
                                                </th>
                                                <th
                                                    class="py-3.5 px-3 text-center"
                                                >
                                                    Porsi Kecil (PK)
                                                </th>
                                                <th
                                                    class="py-3.5 px-3 text-center"
                                                >
                                                    Porsi Besar (PB)
                                                </th>
                                                <th
                                                    class="py-3.5 px-3 text-center"
                                                >
                                                    Total PM
                                                </th>
                                                <th class="py-3.5 px-3">
                                                    Status Alergi
                                                </th>
                                                <th
                                                    class="py-3.5 px-3 text-center w-28"
                                                >
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-slate-100 text-slate-800"
                                        >
                                            <tr
                                                v-for="(
                                                    k, idx
                                                ) in woKelompokList"
                                                :key="k.id"
                                                :class="[
                                                    'transition-colors',
                                                    k.status_menerima === false
                                                        ? 'bg-white text-slate-400 opacity-60 select-none'
                                                        : 'hover:bg-white',
                                                ]"
                                            >
                                                <!-- Kolom Nomor -->
                                                <td
                                                    class="p-3 text-center font-bold text-xs align-middle"
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-300'
                                                            : 'text-slate-500'
                                                    "
                                                >
                                                    {{ idx + 1 }}
                                                </td>

                                                <!-- Status Badge -->
                                                <td
                                                    class="p-3 text-center align-middle"
                                                >
                                                    <span
                                                        v-if="
                                                            k.status_menerima !==
                                                            false
                                                        "
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"
                                                    >
                                                        <UserCheck
                                                            class="h-3 w-3 mr-1"
                                                        />
                                                        Menerima
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-80 select-none"
                                                    >
                                                        <UserX
                                                            class="h-3 w-3 mr-1 text-slate-400"
                                                        />
                                                        Tidak Menerima
                                                    </span>
                                                </td>

                                                <!-- Nama Kelompok -->
                                                <td
                                                    class="p-3 font-bold align-middle"
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-400 line-through opacity-70'
                                                            : 'text-slate-900'
                                                    "
                                                >
                                                    {{ k.nama_kelompok }}
                                                    <span
                                                        class="block text-[10px] text-slate-400 font-normal no-underline"
                                                    >
                                                        {{ k.desa_kelurahan }},
                                                        {{ k.kecamatan }}
                                                    </span>
                                                </td>

                                                <!-- Kategori -->
                                                <td class="p-3 align-middle">
                                                    <Badge
                                                        variant="outline"
                                                        :class="[
                                                            'font-bold text-[11px]',
                                                            k.status_menerima ===
                                                            false
                                                                ? 'bg-slate-100 text-slate-400 border-slate-200 opacity-60'
                                                                : 'bg-slate-50 text-slate-700',
                                                        ]"
                                                    >
                                                        {{ k.kategori }}
                                                    </Badge>
                                                </td>

                                                <!-- Porsi Kecil -->
                                                <td
                                                    class="p-3 text-center align-middle font-bold"
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-300 line-through font-normal'
                                                            : 'text-slate-800 bg-white'
                                                    "
                                                >
                                                    {{ k.total_porsi_kecil }}
                                                </td>

                                                <!-- Porsi Besar -->
                                                <td
                                                    class="p-3 text-center align-middle font-bold"
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-300 line-through font-normal'
                                                            : 'text-indigo-900 bg-indigo-50/20'
                                                    "
                                                >
                                                    {{ k.total_porsi_besar }}
                                                </td>

                                                <!-- Total PM -->
                                                <td
                                                    class="p-3 text-center font-black text-sm align-middle"
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-300 line-through font-normal'
                                                            : 'text-slate-900'
                                                    "
                                                >
                                                    {{ k.total_penerima }}
                                                </td>

                                                <!-- Alergi (Detail Breakdown per Jenis) -->
                                                <td class="p-3 align-middle">
                                                    <div
                                                        v-if="
                                                            k.keterangan_alergi &&
                                                            k.keterangan_alergi
                                                                .length > 0
                                                        "
                                                        class="space-y-1"
                                                    >
                                                        <div
                                                            v-for="(
                                                                al, alIdx
                                                            ) in k.keterangan_alergi"
                                                            :key="alIdx"
                                                            class="text-[11px] font-bold"
                                                            :class="
                                                                k.status_menerima ===
                                                                false
                                                                    ? 'text-slate-300 line-through font-normal'
                                                                    : 'text-rose-700'
                                                            "
                                                        >
                                                            ⚠️
                                                            {{
                                                                al.jenis_alergi
                                                            }}:
                                                            <span
                                                                class="font-black text-rose-900 ml-0.5"
                                                                :class="
                                                                    k.status_menerima ===
                                                                    false
                                                                        ? 'text-slate-300 line-through'
                                                                        : ''
                                                                "
                                                            >
                                                                {{
                                                                    (Number(
                                                                        al.porsi_kecil,
                                                                    ) || 0) +
                                                                    (Number(
                                                                        al.porsi_besar,
                                                                    ) || 0)
                                                                }}
                                                            </span>
                                                            <span
                                                                class="text-[10px] text-slate-500 font-normal ml-1"
                                                            >
                                                                (PK:
                                                                {{
                                                                    al.porsi_kecil ||
                                                                    0
                                                                }}, PB:
                                                                {{
                                                                    al.porsi_besar ||
                                                                    0
                                                                }})
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        v-else-if="
                                                            (k.alergi_porsi_kecil ||
                                                                0) +
                                                                (k.alergi_porsi_besar ||
                                                                    0) >
                                                            0
                                                        "
                                                        class="text-[11px] font-bold"
                                                        :class="
                                                            k.status_menerima ===
                                                            false
                                                                ? 'text-slate-300 line-through font-normal'
                                                                : 'text-rose-700'
                                                        "
                                                    >
                                                        ⚠️
                                                        {{
                                                            (k.alergi_porsi_kecil ||
                                                                0) +
                                                            (k.alergi_porsi_besar ||
                                                                0)
                                                        }}
                                                        Alergi
                                                        <span
                                                            class="block text-[10px] text-slate-500 font-normal"
                                                        >
                                                            PK:
                                                            {{
                                                                k.alergi_porsi_kecil ||
                                                                0
                                                            }}
                                                            • PB:
                                                            {{
                                                                k.alergi_porsi_besar ||
                                                                0
                                                            }}
                                                        </span>
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="text-[11px] font-medium"
                                                        :class="
                                                            k.status_menerima ===
                                                            false
                                                                ? 'text-slate-300 line-through'
                                                                : 'text-emerald-700'
                                                        "
                                                    >
                                                        ✓ Normal
                                                    </div>
                                                </td>

                                                <!-- Aksi -->
                                                <td
                                                    class="py-3 px-3 text-center align-middle"
                                                >
                                                    <div
                                                        class="flex items-center justify-center gap-1.5"
                                                    >
                                                        <button
                                                            type="button"
                                                            :disabled="
                                                                k.status_menerima ===
                                                                false
                                                            "
                                                            @click="
                                                                handleOpenModalEditPm(
                                                                    k,
                                                                )
                                                            "
                                                            :class="[
                                                                k.status_menerima ===
                                                                false
                                                                    ? 'opacity-30 cursor-not-allowed bg-slate-100 text-slate-300 border-slate-200 pointer-events-none'
                                                                    : 'bg-amber-50 hover:bg-amber-100 text-amber-600 border-amber-200/80 cursor-pointer shadow-2xs',
                                                                'h-8 w-8 rounded-lg border flex items-center justify-center transition-colors',
                                                            ]"
                                                            :title="
                                                                k.status_menerima ===
                                                                false
                                                                    ? 'Kelompok Tidak Menerima (Non-Aktif)'
                                                                    : 'Edit Detail PM per Sub-Sub Kategori'
                                                            "
                                                        >
                                                            <Edit3
                                                                class="h-4 w-4"
                                                            />
                                                        </button>

                                                        <button
                                                            v-if="
                                                                k.status_menerima !==
                                                                false
                                                            "
                                                            type="button"
                                                            @click="
                                                                handleToggleStatusMenerima(
                                                                    k,
                                                                    false,
                                                                )
                                                            "
                                                            class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                            title="Tandai TIDAK MENERIMA Menu Hari Ini"
                                                        >
                                                            <UserX
                                                                class="h-4 w-4"
                                                            />
                                                        </button>
                                                        <button
                                                            v-else
                                                            type="button"
                                                            @click="
                                                                handleToggleStatusMenerima(
                                                                    k,
                                                                    true,
                                                                )
                                                            "
                                                            class="h-8 w-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                            title="Aktifkan Kembali Penerimaan"
                                                        >
                                                            <RotateCcw
                                                                class="h-4 w-4"
                                                            />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div
                                v-if="validationErrors.kelompok"
                                class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 font-bold flex items-center gap-2 mt-3"
                            >
                                <AlertCircle
                                    class="h-4 w-4 shrink-0 text-rose-600"
                                />
                                <span>{{ validationErrors.kelompok }}</span>
                            </div>
                        </div>

                        <!-- Card Porsi Tambahan Produksi (PK & PB) -->
                        <div
                            id="card-porsi-tambahan"
                            class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-4"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100"
                            >
                                <div class="space-y-0.5">
                                    <h4
                                        class="text-sm font-black text-slate-900 flex items-center gap-2"
                                    >
                                        <UtensilsCrossed
                                            class="h-4 w-4 text-primary"
                                        />
                                        <span
                                            >Porsi Tambahan Produksi (PK &
                                            PB)</span
                                        >
                                    </h4>
                                    <p class="text-xs text-slate-500">
                                        Porsi tambahan untuk Uji Organoleptik,
                                        Sampel Makanan, dan Buffer Produksi.
                                        <span class="text-amber-700 font-bold">
                                            (Tidak dihitung dalam pagu anggaran
                                            sasaran, namun otomatis
                                            diperhitungkan dalam kebutuhan
                                            belanja &amp; bahan baku).
                                        </span>
                                    </p>
                                </div>
                                <div
                                    class="flex items-center gap-2 shrink-0 flex-wrap"
                                >
                                    <button
                                        type="button"
                                        @click="applyContohPorsiTambahan"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 transition-colors shadow-2xs cursor-pointer"
                                        title="Terapkan contoh isian standar (Organoleptik PB = KPM aktif * 1, Sampel PK 2 PB 2, Buffer PK 10 PB 10)"
                                    >
                                        <Sparkles class="h-3.5 w-3.5" />
                                        <span>Gunakan Contoh</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="resetPorsiTambahan"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-colors cursor-pointer"
                                        title="Reset ke default awal"
                                    >
                                        <RotateCcw class="h-3.5 w-3.5" />
                                        <span>Reset</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Banner Peringatan Wajib Diisi jika ada field yang masih kosong -->
                            <div
                                v-if="
                                    hasEmptyPorsiTambahan ||
                                    validationErrors.porsiTambahan
                                "
                                class="p-3 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-900 font-bold flex items-start gap-2.5 shadow-2xs animate-in fade-in"
                            >
                                <AlertCircle
                                    class="h-4 w-4 text-rose-600 shrink-0 mt-0.5"
                                />
                                <div class="space-y-0.5">
                                    <p class="font-black text-rose-950">
                                        Peringatan: Seluruh Kolom Porsi Tambahan
                                        Wajib Diisi!
                                    </p>
                                    <p
                                        class="text-[11px] text-rose-700 font-medium"
                                    >
                                        Terdapat kolom Porsi Kecil (PK) atau
                                        Porsi Besar (PB) yang masih kosong
                                        (bertanda merah). Silakan isi angka
                                        (ketik <strong>0</strong> jika tidak ada
                                        alokasi porsi), atau klik tombol
                                        <strong>"Gunakan Contoh"</strong> /
                                        <strong>"Reset"</strong> di atas.
                                    </p>
                                </div>
                            </div>

                            <!-- Grid 3 Kategori Porsi Tambahan -->
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-3.5"
                            >
                                <!-- 1. Uji Organoleptik -->
                                <div
                                    class="p-3.5 rounded-xl border transition-all space-y-3"
                                    :class="[
                                        porsiTambahanFieldErrors.organoleptik_pk ||
                                        porsiTambahanFieldErrors.organoleptik_pb
                                            ? 'border-rose-300 bg-rose-50/30'
                                            : 'border-slate-200 bg-slate-50/40 hover:bg-slate-50/70',
                                    ]"
                                >
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="h-7 w-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 font-black text-xs"
                                            >
                                                <FlaskConical class="h-4 w-4" />
                                            </div>
                                            <div>
                                                <h5
                                                    class="text-xs font-black text-slate-900"
                                                >
                                                    Uji Organoleptik
                                                </h5>
                                                <p
                                                    class="text-[10px] text-slate-500"
                                                >
                                                    Uji sensori &amp; rasa menu
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0"
                                        >
                                            {{ totalOrganoleptik }} Porsi
                                        </span>
                                    </div>

                                    <div
                                        class="p-2 rounded-lg bg-emerald-50/50 border border-emerald-100 text-[10.5px] text-emerald-900 flex items-start gap-1.5"
                                    >
                                        <Info
                                            class="h-3.5 w-3.5 text-emerald-700 shrink-0 mt-0.5"
                                        />
                                        <span>
                                            Default:
                                            <strong
                                                >PB =
                                                {{
                                                    kelompokMenerimaAktif.length
                                                }}
                                                porsi</strong
                                            >
                                            (1 per KPM aktif). Dapat diubah
                                            manual.
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <div
                                                class="flex items-center justify-between mb-1"
                                            >
                                                <label
                                                    class="block text-[10px] font-bold text-slate-600"
                                                >
                                                    Porsi Kecil (PK)
                                                    <span
                                                        class="text-rose-500 font-black"
                                                        >*</span
                                                    >
                                                </label>
                                                <span
                                                    v-if="
                                                        porsiTambahanFieldErrors.organoleptik_pk
                                                    "
                                                    class="text-[9px] font-black text-rose-600 bg-rose-50 px-1 py-0.2 rounded border border-rose-200"
                                                >
                                                    Wajib diisi
                                                </span>
                                            </div>
                                            <input
                                                type="number"
                                                min="0"
                                                v-model.number="
                                                    porsiTambahan.organoleptik
                                                        .pk
                                                "
                                                @input="
                                                    organoleptikIsManuallyEdited = true;
                                                    clearPorsiTambahanFieldError(
                                                        'porsi_organoleptik_pk',
                                                    );
                                                "
                                                placeholder="0"
                                                :class="[
                                                    'w-full text-xs font-extrabold rounded-lg p-2 text-center transition-all shadow-2xs',
                                                    porsiTambahanFieldErrors.organoleptik_pk
                                                        ? 'border-2 border-rose-400 bg-rose-50/50 text-rose-900 focus:ring-2 focus:ring-rose-400 focus:border-rose-500 placeholder-rose-300'
                                                        : 'border border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary',
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    porsiTambahanFieldErrors.organoleptik_pk
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold mt-1 text-center flex items-center justify-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span
                                                    >Wajib diisi (min. 0)</span
                                                >
                                            </p>
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center justify-between mb-1"
                                            >
                                                <label
                                                    class="block text-[10px] font-bold text-slate-600"
                                                >
                                                    Porsi Besar (PB)
                                                    <span
                                                        class="text-rose-500 font-black"
                                                        >*</span
                                                    >
                                                </label>
                                                <span
                                                    v-if="
                                                        porsiTambahanFieldErrors.organoleptik_pb
                                                    "
                                                    class="text-[9px] font-black text-rose-600 bg-rose-50 px-1 py-0.2 rounded border border-rose-200"
                                                >
                                                    Wajib diisi
                                                </span>
                                            </div>
                                            <input
                                                type="number"
                                                min="0"
                                                v-model.number="
                                                    porsiTambahan.organoleptik
                                                        .pb
                                                "
                                                @input="
                                                    organoleptikIsManuallyEdited = true;
                                                    clearPorsiTambahanFieldError(
                                                        'porsi_organoleptik_pb',
                                                    );
                                                "
                                                placeholder="0"
                                                :class="[
                                                    'w-full text-xs font-extrabold rounded-lg p-2 text-center transition-all shadow-2xs',
                                                    porsiTambahanFieldErrors.organoleptik_pb
                                                        ? 'border-2 border-rose-400 bg-rose-50/50 text-rose-900 focus:ring-2 focus:ring-rose-400 focus:border-rose-500 placeholder-rose-300'
                                                        : 'border border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary',
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    porsiTambahanFieldErrors.organoleptik_pb
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold mt-1 text-center flex items-center justify-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span
                                                    >Wajib diisi (min. 0)</span
                                                >
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Sampel Makanan -->
                                <div
                                    class="p-3.5 rounded-xl border transition-all space-y-3"
                                    :class="[
                                        porsiTambahanFieldErrors.sampel_pk ||
                                        porsiTambahanFieldErrors.sampel_pb
                                            ? 'border-rose-300 bg-rose-50/30'
                                            : 'border-slate-200 bg-slate-50/40 hover:bg-slate-50/70',
                                    ]"
                                >
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="h-7 w-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 font-black text-xs"
                                            >
                                                <ShieldCheck class="h-4 w-4" />
                                            </div>
                                            <div>
                                                <h5
                                                    class="text-xs font-black text-slate-900"
                                                >
                                                    Sampel Makanan
                                                </h5>
                                                <p
                                                    class="text-[10px] text-slate-500"
                                                >
                                                    Arsip uji lab &amp; keamanan
                                                    pangan
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200 shrink-0"
                                        >
                                            {{ totalSampel }} Porsi
                                        </span>
                                    </div>

                                    <div
                                        class="p-2 rounded-lg bg-amber-50/50 border border-amber-100 text-[10.5px] text-amber-900 flex items-start gap-1.5"
                                    >
                                        <Info
                                            class="h-3.5 w-3.5 text-amber-700 shrink-0 mt-0.5"
                                        />
                                        <span>
                                            Default: <strong>0 porsi</strong>.
                                            Contoh: 4 porsi (PK: 2, PB: 2) untuk
                                            arsip dingin.
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <div
                                                class="flex items-center justify-between mb-1"
                                            >
                                                <label
                                                    class="block text-[10px] font-bold text-slate-600"
                                                >
                                                    Porsi Kecil (PK)
                                                    <span
                                                        class="text-rose-500 font-black"
                                                        >*</span
                                                    >
                                                </label>
                                                <span
                                                    v-if="
                                                        porsiTambahanFieldErrors.sampel_pk
                                                    "
                                                    class="text-[9px] font-black text-rose-600 bg-rose-50 px-1 py-0.2 rounded border border-rose-200"
                                                >
                                                    Wajib diisi
                                                </span>
                                            </div>
                                            <input
                                                type="number"
                                                min="0"
                                                v-model.number="
                                                    porsiTambahan.sampel.pk
                                                "
                                                @input="
                                                    clearPorsiTambahanFieldError(
                                                        'porsi_sampel_pk',
                                                    )
                                                "
                                                placeholder="0"
                                                :class="[
                                                    'w-full text-xs font-extrabold rounded-lg p-2 text-center transition-all shadow-2xs',
                                                    porsiTambahanFieldErrors.sampel_pk
                                                        ? 'border-2 border-rose-400 bg-rose-50/50 text-rose-900 focus:ring-2 focus:ring-rose-400 focus:border-rose-500 placeholder-rose-300'
                                                        : 'border border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary',
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    porsiTambahanFieldErrors.sampel_pk
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold mt-1 text-center flex items-center justify-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span
                                                    >Wajib diisi (min. 0)</span
                                                >
                                            </p>
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center justify-between mb-1"
                                            >
                                                <label
                                                    class="block text-[10px] font-bold text-slate-600"
                                                >
                                                    Porsi Besar (PB)
                                                    <span
                                                        class="text-rose-500 font-black"
                                                        >*</span
                                                    >
                                                </label>
                                                <span
                                                    v-if="
                                                        porsiTambahanFieldErrors.sampel_pb
                                                    "
                                                    class="text-[9px] font-black text-rose-600 bg-rose-50 px-1 py-0.2 rounded border border-rose-200"
                                                >
                                                    Wajib diisi
                                                </span>
                                            </div>
                                            <input
                                                type="number"
                                                min="0"
                                                v-model.number="
                                                    porsiTambahan.sampel.pb
                                                "
                                                @input="
                                                    clearPorsiTambahanFieldError(
                                                        'porsi_sampel_pb',
                                                    )
                                                "
                                                placeholder="0"
                                                :class="[
                                                    'w-full text-xs font-extrabold rounded-lg p-2 text-center transition-all shadow-2xs',
                                                    porsiTambahanFieldErrors.sampel_pb
                                                        ? 'border-2 border-rose-400 bg-rose-50/50 text-rose-900 focus:ring-2 focus:ring-rose-400 focus:border-rose-500 placeholder-rose-300'
                                                        : 'border border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary',
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    porsiTambahanFieldErrors.sampel_pb
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold mt-1 text-center flex items-center justify-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span
                                                    >Wajib diisi (min. 0)</span
                                                >
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Buffer Produksi -->
                                <div
                                    class="p-3.5 rounded-xl border transition-all space-y-3"
                                    :class="[
                                        porsiTambahanFieldErrors.buffer_pk ||
                                        porsiTambahanFieldErrors.buffer_pb
                                            ? 'border-rose-300 bg-rose-50/30'
                                            : 'border-slate-200 bg-slate-50/40 hover:bg-slate-50/70',
                                    ]"
                                >
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="h-7 w-7 rounded-lg bg-indigo-100 text-indigo-800 flex items-center justify-center shrink-0 font-black text-xs"
                                            >
                                                <Layers class="h-4 w-4" />
                                            </div>
                                            <div>
                                                <h5
                                                    class="text-xs font-black text-slate-900"
                                                >
                                                    Buffer Produksi
                                                </h5>
                                                <p
                                                    class="text-[10px] text-slate-500"
                                                >
                                                    Cadangan tumpah / penyusutan
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-100 text-indigo-800 border border-indigo-200 shrink-0"
                                        >
                                            {{ totalBuffer }} Porsi
                                        </span>
                                    </div>

                                    <div
                                        class="p-2 rounded-lg bg-indigo-50/50 border border-indigo-100 text-[10.5px] text-indigo-900 flex items-start gap-1.5"
                                    >
                                        <Info
                                            class="h-3.5 w-3.5 text-indigo-700 shrink-0 mt-0.5"
                                        />
                                        <span>
                                            Default: <strong>0 porsi</strong>.
                                            Contoh: 20 porsi (PK: 10, PB: 10)
                                            cadangan pemorsian.
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <div
                                                class="flex items-center justify-between mb-1"
                                            >
                                                <label
                                                    class="block text-[10px] font-bold text-slate-600"
                                                >
                                                    Porsi Kecil (PK)
                                                    <span
                                                        class="text-rose-500 font-black"
                                                        >*</span
                                                    >
                                                </label>
                                                <span
                                                    v-if="
                                                        porsiTambahanFieldErrors.buffer_pk
                                                    "
                                                    class="text-[9px] font-black text-rose-600 bg-rose-50 px-1 py-0.2 rounded border border-rose-200"
                                                >
                                                    Wajib diisi
                                                </span>
                                            </div>
                                            <input
                                                type="number"
                                                min="0"
                                                v-model.number="
                                                    porsiTambahan.buffer.pk
                                                "
                                                @input="
                                                    clearPorsiTambahanFieldError(
                                                        'porsi_buffer_pk',
                                                    )
                                                "
                                                placeholder="0"
                                                :class="[
                                                    'w-full text-xs font-extrabold rounded-lg p-2 text-center transition-all shadow-2xs',
                                                    porsiTambahanFieldErrors.buffer_pk
                                                        ? 'border-2 border-rose-400 bg-rose-50/50 text-rose-900 focus:ring-2 focus:ring-rose-400 focus:border-rose-500 placeholder-rose-300'
                                                        : 'border border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary',
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    porsiTambahanFieldErrors.buffer_pk
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold mt-1 text-center flex items-center justify-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span
                                                    >Wajib diisi (min. 0)</span
                                                >
                                            </p>
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center justify-between mb-1"
                                            >
                                                <label
                                                    class="block text-[10px] font-bold text-slate-600"
                                                >
                                                    Porsi Besar (PB)
                                                    <span
                                                        class="text-rose-500 font-black"
                                                        >*</span
                                                    >
                                                </label>
                                                <span
                                                    v-if="
                                                        porsiTambahanFieldErrors.buffer_pb
                                                    "
                                                    class="text-[9px] font-black text-rose-600 bg-rose-50 px-1 py-0.2 rounded border border-rose-200"
                                                >
                                                    Wajib diisi
                                                </span>
                                            </div>
                                            <input
                                                type="number"
                                                min="0"
                                                v-model.number="
                                                    porsiTambahan.buffer.pb
                                                "
                                                @input="
                                                    clearPorsiTambahanFieldError(
                                                        'porsi_buffer_pb',
                                                    )
                                                "
                                                placeholder="0"
                                                :class="[
                                                    'w-full text-xs font-extrabold rounded-lg p-2 text-center transition-all shadow-2xs',
                                                    porsiTambahanFieldErrors.buffer_pb
                                                        ? 'border-2 border-rose-400 bg-rose-50/50 text-rose-900 focus:ring-2 focus:ring-rose-400 focus:border-rose-500 placeholder-rose-300'
                                                        : 'border border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary',
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    porsiTambahanFieldErrors.buffer_pb
                                                "
                                                class="text-[9.5px] text-rose-600 font-bold mt-1 text-center flex items-center justify-center gap-0.5"
                                            >
                                                <AlertCircle
                                                    class="h-2.5 w-2.5 shrink-0"
                                                />
                                                <span
                                                    >Wajib diisi (min. 0)</span
                                                >
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rekapitulasi Baris Total Porsi Tambahan vs Sasaran PM -->
                            <div
                                class="p-3 sm:p-4 rounded-xl bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                            >
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-extrabold text-amber-300"
                                        >
                                            ⚡ Total Porsi Tambahan:
                                        </span>
                                        <span
                                            class="font-mono font-black text-amber-200"
                                        >
                                            {{ totalPorsiTambahan }} Porsi
                                        </span>
                                        <span
                                            class="text-[10px] text-slate-300"
                                        >
                                            (PK: {{ totalPorsiTambahanPK }}, PB:
                                            {{ totalPorsiTambahanPB }})
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">
                                        Sasaran PM Reguler (Pagu Anggaran):
                                        <strong class="text-slate-200"
                                            >{{ totalPM }} Porsi</strong
                                        >
                                        (PK: {{ totalPK }}, PB: {{ totalPB }})
                                    </p>
                                </div>

                                <div
                                    class="flex items-center gap-3 sm:border-l sm:border-slate-800 sm:pl-4"
                                >
                                    <div>
                                        <p
                                            class="text-[9.5px] uppercase tracking-wider text-slate-400 font-bold"
                                        >
                                            Total Fisik Dimasak (Bahan Baku)
                                        </p>
                                        <div
                                            class="text-base sm:text-lg font-black text-emerald-400"
                                        >
                                            {{ grandTotalProduksiSemua }} Porsi
                                            <span
                                                class="text-xs font-normal text-slate-300"
                                            >
                                                (PK: {{ grandTotalProduksiPK }},
                                                PB: {{ grandTotalProduksiPB }})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Action Button -->
                        <div
                            class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3.5"
                        >
                            <div class="text-xs text-slate-500">
                                <span
                                    v-if="metodeWo === 'manual'"
                                    class="text-amber-800 font-semibold"
                                >
                                    ✍️ Mode Manual: Klik
                                    <strong>Simpan Draft</strong>
                                    untuk simpan saja, atau
                                    <strong>Lanjut ke Formula Makanan</strong>
                                    untuk melengkapi data gizi.
                                </span>
                                <span v-else>
                                    Pastikan tanggal, nama menu, dan status
                                    penerima sasaran sudah sesuai sebelum
                                    melanjutkan.
                                </span>
                            </div>
                            <div
                                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full sm:w-auto"
                            >
                                <Button
                                    type="button"
                                    @click="handleSavePerencanaan(false)"
                                    :disabled="isSubmitting"
                                    className="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold px-4 h-11 rounded-xl cursor-pointer w-full sm:w-auto flex items-center justify-center gap-1.5 shadow-2xs"
                                >
                                    <FileText class="h-4 w-4" />
                                    <span>Simpan Draft</span>
                                </Button>

                                <Button
                                    v-if="metodeWo === 'sistem'"
                                    type="button"
                                    @click="handleSavePerencanaan(true)"
                                    :disabled="isSubmitting"
                                    className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-6 h-11 flex items-center justify-center gap-2 rounded-xl shadow-xs cursor-pointer w-full sm:w-auto text-center"
                                >
                                    <span>Simpan & Lanjut ke Rancang Menu</span>
                                    <ArrowRight class="h-4 w-4 shrink-0" />
                                </Button>

                                <Button
                                    v-else
                                    type="button"
                                    @click="
                                        handleSavePerencanaanManualAndOpenFormula
                                    "
                                    :disabled="isSubmitting"
                                    className="bg-blue-600 hover:bg-blue-700 text-white text-xs font-black px-6 h-11 flex items-center justify-center gap-2 rounded-xl shadow-xs cursor-pointer w-full sm:w-auto text-center"
                                >
                                    <Package class="h-4 w-4 shrink-0" />
                                    <span>Lanjut ke Formula Makanan</span>
                                    <ArrowRight class="h-4 w-4 shrink-0" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Modal Edit Detail Penerima Manfaat per Sub-Sub Kategori (Persis Rancang Menu) -->
                <Modal
                    :show="showModalEditPm"
                    @close="showModalEditPm = false"
                    maxWidth="3xl"
                >
                    <div class="p-5 sm:p-6 space-y-5">
                        <!-- Modal Header -->
                        <div
                            class="flex items-start justify-between border-b border-slate-100 pb-3"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0"
                                >
                                    <School class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3
                                        class="text-base font-black text-slate-900"
                                    >
                                        Edit Rincian PM:
                                        {{ editingKelompok?.nama_kelompok }}
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Kategori:
                                        <strong class="text-slate-800">{{
                                            editingKelompok?.kategori
                                        }}</strong>
                                        • Wilayah:
                                        {{ editingKelompok?.desa_kelurahan }},
                                        {{ editingKelompok?.kecamatan }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="showModalEditPm = false"
                                class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Modal Body: Tabel Sub-Sub Kategori -->
                        <div class="space-y-4">
                            <div
                                v-if="modalPmError"
                                class="p-3 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-800 font-bold flex items-center gap-2"
                            >
                                <AlertCircle
                                    class="h-4 w-4 shrink-0 text-rose-600"
                                />
                                <span>{{ modalPmError }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <h4
                                    class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >
                                    Rincian Kuota Porsi / Penerima per Jenjang:
                                </h4>
                                <span class="text-xs text-slate-500">
                                    Format input: Laki-laki (L) + Perempuan (P)
                                </span>
                            </div>

                            <div
                                class="rounded-xl border border-slate-200 overflow-x-auto max-h-60 overflow-y-auto"
                            >
                                <table
                                    class="w-full min-w-[500px] text-left text-xs border-collapse"
                                >
                                    <thead
                                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px] sticky top-0 z-10 shadow-2xs"
                                    >
                                        <tr>
                                            <th class="p-3">
                                                Sub-Kategori / Jenjang
                                            </th>
                                            <th class="p-3">
                                                Peruntukan Porsi
                                            </th>
                                            <th
                                                class="p-3 text-center min-w-[100px]"
                                            >
                                                Laki-laki (L)
                                            </th>
                                            <th
                                                class="p-3 text-center min-w-[100px]"
                                            >
                                                Perempuan (P)
                                            </th>
                                            <th class="p-3 text-right">
                                                Subtotal
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-slate-100 text-slate-800"
                                    >
                                        <tr
                                            v-for="(r, rIdx) in editFormRincian"
                                            :key="r.sub_kategori || rIdx"
                                            class="hover:bg-white"
                                        >
                                            <td
                                                class="p-3 font-bold text-slate-900"
                                            >
                                                {{ r.sub_kategori }}
                                            </td>
                                            <td class="p-3">
                                                <Badge
                                                    variant="outline"
                                                    :class="[
                                                        'font-extrabold text-[10px]',
                                                        r.jenis_porsi ===
                                                        'Porsi Kecil'
                                                            ? 'bg-amber-50 text-slate-700 border-amber-300'
                                                            : 'bg-indigo-50 text-indigo-800 border-indigo-300',
                                                    ]"
                                                >
                                                    {{ r.jenis_porsi }}
                                                </Badge>
                                            </td>
                                            <td class="p-2 text-center">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    v-model.number="
                                                        r.jumlah_laki_laki
                                                    "
                                                    class="w-20 text-center text-xs font-bold rounded-lg border-slate-300 p-1.5 focus:ring-primary focus:border-primary"
                                                />
                                            </td>
                                            <td class="p-2 text-center">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    v-model.number="
                                                        r.jumlah_perempuan
                                                    "
                                                    class="w-20 text-center text-xs font-bold rounded-lg border-slate-300 p-1.5 focus:ring-primary focus:border-primary"
                                                />
                                            </td>
                                            <td
                                                class="p-3 text-right font-black text-slate-900"
                                            >
                                                {{
                                                    (Number(
                                                        r.jumlah_laki_laki,
                                                    ) || 0) +
                                                    (Number(
                                                        r.jumlah_perempuan,
                                                    ) || 0)
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Input Khusus Kuota Porsi Alergi -->
                            <div
                                class="p-4 rounded-xl bg-rose-50/60 border border-rose-200/80 space-y-3"
                            >
                                <div>
                                    <h5
                                        class="text-xs font-bold text-rose-900 flex items-center gap-1.5"
                                    >
                                        <AlertCircle
                                            class="h-4 w-4 text-rose-600"
                                        />
                                        <span
                                            >Penyesuaian Porsi Khusus Alergi
                                            (Membutuhkan Menu Substitusi)</span
                                        >
                                    </h5>
                                    <p class="text-[11px] text-rose-700 mt-0.5">
                                        Daftar jenis alergen bersumber dari
                                        master data
                                        <strong>Penerima Manfaat</strong>. Anda
                                        dapat menyesuaikan jumlah kuota porsi
                                        (PK / PB) untuk Work Order ini jika ada
                                        perubahan kehadiran.
                                    </p>
                                </div>

                                <!-- Tabel Daftar Alergi Terdaftar -->
                                <div
                                    v-if="editFormKeteranganAlergi.length > 0"
                                    class="rounded-lg border border-rose-200 bg-white overflow-x-auto"
                                >
                                    <table
                                        class="w-full min-w-[500px] text-left text-xs border-collapse"
                                    >
                                        <thead
                                            class="bg-rose-100/60 text-rose-900 font-bold border-b border-rose-200 uppercase text-[10px]"
                                        >
                                            <tr>
                                                <th class="p-3">
                                                    Jenis Alergen (Master PM)
                                                </th>
                                                <th
                                                    class="p-3 text-center min-w-[110px]"
                                                >
                                                    Porsi Kecil (PK)
                                                </th>
                                                <th
                                                    class="p-3 text-center min-w-[110px]"
                                                >
                                                    Porsi Besar (PB)
                                                </th>
                                                <th class="p-3 text-right">
                                                    Subtotal Alergi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-rose-100 text-slate-800"
                                        >
                                            <tr
                                                v-for="(
                                                    alItem, alIdx
                                                ) in editFormKeteranganAlergi"
                                                :key="alIdx"
                                                class="hover:bg-white"
                                            >
                                                <td
                                                    class="p-3 font-bold text-slate-900 align-middle"
                                                >
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <span
                                                            class="h-2 w-2 rounded-full bg-rose-500 shrink-0"
                                                        ></span>
                                                        <span
                                                            class="text-xs font-black text-rose-950"
                                                            >{{
                                                                alItem.jenis_alergi
                                                            }}</span
                                                        >
                                                    </div>
                                                </td>
                                                <td
                                                    class="p-2 text-center align-middle"
                                                >
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        :max="modalTotalPk"
                                                        v-model.number="
                                                            alItem.porsi_kecil
                                                        "
                                                        class="w-20 text-center text-xs font-bold rounded-lg border-rose-300 bg-white p-1.5 focus:ring-rose-400 focus:border-rose-400"
                                                    />
                                                </td>
                                                <td
                                                    class="p-2 text-center align-middle"
                                                >
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        :max="modalTotalPb"
                                                        v-model.number="
                                                            alItem.porsi_besar
                                                        "
                                                        class="w-20 text-center text-xs font-bold rounded-lg border-rose-300 bg-white p-1.5 focus:ring-rose-400 focus:border-rose-400"
                                                    />
                                                </td>
                                                <td
                                                    class="p-3 text-right font-black text-rose-900 text-xs align-middle"
                                                >
                                                    {{
                                                        (Number(
                                                            alItem.porsi_kecil,
                                                        ) || 0) +
                                                        (Number(
                                                            alItem.porsi_besar,
                                                        ) || 0)
                                                    }}
                                                    PM
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div
                                    v-else
                                    class="p-4 text-center text-slate-500 text-xs bg-white rounded-xl border border-dashed border-rose-200 space-y-1"
                                >
                                    <p class="font-bold text-slate-700">
                                        Tidak ada riwayat alergi yang terdaftar
                                        untuk kelompok sasaran ini.
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Penambahan atau pengelolaan jenis
                                        alergen dilakukan melalui master data
                                        <strong class="text-slate-800"
                                            >Penerima Manfaat</strong
                                        >.
                                    </p>
                                </div>
                            </div>

                            <!-- Live Summary Bar -->
                            <div
                                class="p-3.5 rounded-xl bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 text-xs"
                            >
                                <div>
                                    <span class="text-slate-400"
                                        >Hasil Rekapitulasi:
                                    </span>
                                    <strong class="text-white ml-1"
                                        >Total {{ modalTotalPm }} PM</strong
                                    >
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-amber-300 font-bold"
                                        >PK: {{ modalTotalPk }} Porsi</span
                                    >
                                    <span class="text-indigo-300 font-bold"
                                        >PB: {{ modalTotalPb }} Porsi</span
                                    >
                                    <span class="text-rose-300 font-bold"
                                        >Alergi:
                                        {{ modalGrandTotalAlergi }} Porsi (PK:
                                        {{ modalTotalAlergiPk }}, PB:
                                        {{ modalTotalAlergiPb }})</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div
                            class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                @click="showModalEditPm = false"
                                className="text-xs font-bold cursor-pointer"
                            >
                                Batal
                            </Button>
                            <Button
                                type="button"
                                @click="handleSimpanEditDetailPm"
                                className="bg-primary hover:bg-primary/90 text-white text-xs font-bold flex items-center gap-1.5 cursor-pointer shadow-xs"
                            >
                                <Check class="h-4 w-4" />
                                <span>Simpan Perubahan</span>
                            </Button>
                        </div>
                    </div>
                </Modal>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 2: FORMULA MAKANAN (HANYA MUNCUL DI MODE MANUAL TAB FORMULA)          -->
            <!-- ========================================================================= -->
            <div
                v-if="metodeWo === 'manual' && activeManualTab === 'formula'"
                class="space-y-6"
            >
                <Card
                    className="bg-white border-slate-200 shadow-xs overflow-hidden"
                >
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-white"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                        >
                            <div>
                                <CardTitle
                                    class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2"
                                >
                                    <Package class="h-5 w-5 text-blue-600" />
                                    <span
                                        >Formula Makanan & Kandungan Gizi
                                        (AKG)</span
                                    >
                                </CardTitle>
                                <CardDescription
                                    class="text-xs sm:text-sm mt-0.5 text-slate-500"
                                >
                                    Input total kandungan gizi per porsi untuk
                                    porsi standar (PK & PB) serta varian menu
                                    pengganti alergi <strong>{{ woNo }}</strong
                                    >.
                                </CardDescription>
                            </div>

                            <!-- Sub-tab Selector: Gizi Normal vs Alergi -->
                            <div
                                class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold self-start sm:self-auto"
                            >
                                <button
                                    type="button"
                                    @click="activeTabGiziFormula = 'normal'"
                                    :class="[
                                        'px-4 py-1.5 rounded-lg transition-all cursor-pointer text-center',
                                        activeTabGiziFormula === 'normal'
                                            ? 'bg-white text-blue-700 shadow-2xs font-black'
                                            : 'text-slate-600 hover:text-slate-900',
                                    ]"
                                >
                                    <span>Normal</span>
                                </button>
                                <button
                                    v-if="configuredAllergiesList.length > 0"
                                    type="button"
                                    @click="activeTabGiziFormula = 'alergi'"
                                    :class="[
                                        'px-4 py-1.5 rounded-lg transition-all cursor-pointer text-center',
                                        activeTabGiziFormula === 'alergi'
                                            ? 'bg-amber-600 text-white shadow-2xs font-black'
                                            : 'text-amber-800 hover:text-amber-950',
                                    ]"
                                >
                                    <span>Alergi</span>
                                </button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="p-4 sm:p-6 space-y-6">
                        <!-- 1. TAB GIZI PORSI NORMAL -->
                        <div
                            v-if="activeTabGiziFormula === 'normal'"
                            class="grid grid-cols-1 md:grid-cols-2 gap-4"
                        >
                            <!-- Porsi Kecil (PK) Normal -->
                            <div
                                class="p-4 sm:p-5 bg-blue-50/70 rounded-2xl border border-blue-200/90 space-y-3.5 shadow-2xs"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <div>
                                        <h4
                                            class="text-sm font-black text-blue-950 flex items-center gap-1.5"
                                        >
                                            <Sparkles
                                                class="h-4 w-4 text-blue-600"
                                            />
                                            <span
                                                >Porsi Kecil (PK) - Normal</span
                                            >
                                        </h4>
                                        <span
                                            class="text-[11px] text-slate-500 font-medium block mt-0.5"
                                        >
                                            Sasaran: PAUD, TK, SD Kelas 1-3
                                            (Standar: 500 - 600 kkal)
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center gap-2 shrink-0"
                                    >
                                        <button
                                            type="button"
                                            @click="
                                                handleQuickPasteGizi(
                                                    akgPk,
                                                    'Porsi Kecil (PK) Normal',
                                                )
                                            "
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white hover:bg-blue-100/80 text-blue-700 text-[11px] font-extrabold border border-blue-200/90 transition-all cursor-pointer shadow-2xs"
                                            title="Tempel data gizi dari Excel atau teks"
                                        >
                                            <ClipboardPaste
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span>Tempel Gizi</span>
                                        </button>
                                        <span
                                            class="text-xs font-black text-blue-800 bg-blue-100 px-2.5 py-1 rounded-full border border-blue-300 shadow-2xs shrink-0"
                                        >
                                            {{ totalPK }} Porsi PK
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-2 pt-1">
                                    <!-- Energi -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🔥</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Energi</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 500 - 600
                                                    kkal</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="akgPk.energi"
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPk,
                                                        'energi',
                                                        'Porsi Kecil (PK) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-10 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >kkal</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Protein -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🥩</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Protein</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 15 - 20 g</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="akgPk.protein"
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPk,
                                                        'protein',
                                                        'Porsi Kecil (PK) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >g</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Lemak -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🥑</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Lemak</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 15 - 20 g</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="akgPk.lemak"
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPk,
                                                        'lemak',
                                                        'Porsi Kecil (PK) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >g</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Karbohidrat -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🍚</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Karbohidrat</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 65 - 85 g</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="
                                                    akgPk.karbohidrat
                                                "
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPk,
                                                        'karbohidrat',
                                                        'Porsi Kecil (PK) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >g</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Serat -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🥦</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Serat</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 5 - 8 g</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="akgPk.serat"
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPk,
                                                        'serat',
                                                        'Porsi Kecil (PK) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >g</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Porsi Besar (PB) Normal -->
                            <div
                                class="p-4 sm:p-5 bg-emerald-50/70 rounded-2xl border border-emerald-200/90 space-y-3.5 shadow-2xs"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <div>
                                        <h4
                                            class="text-sm font-black text-emerald-950 flex items-center gap-1.5"
                                        >
                                            <Sparkles
                                                class="h-4 w-4 text-emerald-600"
                                            />
                                            <span
                                                >Porsi Besar (PB) - Normal</span
                                            >
                                        </h4>
                                        <span
                                            class="text-[11px] text-slate-500 font-medium block mt-0.5"
                                        >
                                            Sasaran: SD Kelas 4-6, SMP, SMA,
                                            Bumil/Busui (Standar: 700 - 800
                                            kkal)
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center gap-2 shrink-0"
                                    >
                                        <button
                                            type="button"
                                            @click="
                                                handleQuickPasteGizi(
                                                    akgPb,
                                                    'Porsi Besar (PB) Normal',
                                                )
                                            "
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-100/80 text-emerald-700 text-[11px] font-extrabold border border-emerald-200/90 transition-all cursor-pointer shadow-2xs"
                                            title="Tempel data gizi dari Excel atau teks"
                                        >
                                            <ClipboardPaste
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span>Tempel Gizi</span>
                                        </button>
                                        <span
                                            class="text-xs font-black text-emerald-800 bg-emerald-100 px-2.5 py-1 rounded-full border border-emerald-300 shadow-2xs shrink-0"
                                        >
                                            {{ totalPB }} Porsi PB
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-2 pt-1">
                                    <!-- Energi -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🔥</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Energi</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 700 - 800
                                                    kkal</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="akgPb.energi"
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPb,
                                                        'energi',
                                                        'Porsi Besar (PB) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-10 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >kkal</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Protein -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🥩</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Protein</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 20 - 25 g</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="akgPb.protein"
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPb,
                                                        'protein',
                                                        'Porsi Besar (PB) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >g</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Lemak -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🥑</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Lemak</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 20 - 25 g</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="akgPb.lemak"
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPb,
                                                        'lemak',
                                                        'Porsi Besar (PB) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >g</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Karbohidrat -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🍚</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Karbohidrat</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 90 - 110 g</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="
                                                    akgPb.karbohidrat
                                                "
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPb,
                                                        'karbohidrat',
                                                        'Porsi Besar (PB) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >g</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Serat -->
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0"
                                        >
                                            <span class="text-base shrink-0"
                                                >🥦</span
                                            >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs font-bold text-slate-800 block truncate"
                                                    >Serat</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 block truncate"
                                                    >Standar: 7 - 10 g</span
                                                >
                                            </div>
                                        </div>
                                        <div class="relative w-32 shrink-0">
                                            <input
                                                v-model.number="akgPb.serat"
                                                @paste="
                                                    onPasteGiziInput(
                                                        $event,
                                                        akgPb,
                                                        'serat',
                                                        'Porsi Besar (PB) Normal',
                                                    )
                                                "
                                                type="number"
                                                step="0.1"
                                                placeholder="0"
                                                class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                                            />
                                            <span
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none"
                                                >g</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. TAB GIZI VARIAN ALERGI -->
                        <div
                            v-else-if="activeTabGiziFormula === 'alergi'"
                            class="space-y-4"
                        >
                            <!-- Navigasi Jenis Alergi -->
                            <div
                                class="flex items-center gap-2 overflow-x-auto pb-1"
                            >
                                <button
                                    v-for="al in configuredAllergiesList"
                                    :key="al.uniqueKey"
                                    type="button"
                                    @click="selectedAlergiFormulaTab = al.jenis"
                                    :class="[
                                        'px-3.5 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap shrink-0 border',
                                        selectedAlergiFormulaTab === al.jenis
                                            ? 'bg-amber-600 text-white border-amber-600 shadow-2xs'
                                            : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-200',
                                    ]"
                                >
                                    <ShieldAlert class="h-3.5 w-3.5" />
                                    <span>Alergi: {{ al.jenis }}</span>
                                    <span
                                        class="text-[10px] opacity-80 font-normal"
                                        >({{ al.pengganti }})</span
                                    >
                                </button>
                            </div>

                            <!-- Input Gizi untuk Alergi yang Dipilih -->
                            <div
                                v-if="
                                    selectedAlergiFormulaTab &&
                                    akgAlergi[selectedAlergiFormulaTab]
                                "
                                class="grid grid-cols-1 md:grid-cols-2 gap-4"
                            >
                                <!-- PK Alergi -->
                                <div
                                    class="p-4 sm:p-5 bg-white rounded-2xl border border-amber-200 shadow-2xs space-y-3.5"
                                >
                                    <div
                                        class="flex items-center justify-between pb-2 border-b border-amber-100 gap-2"
                                    >
                                        <div>
                                            <h5
                                                class="text-xs font-black text-amber-950 flex items-center gap-1.5"
                                            >
                                                <Sparkles
                                                    class="h-3.5 w-3.5 text-amber-600"
                                                />
                                                <span
                                                    >Porsi Kecil (PK) Alergi:
                                                    {{
                                                        selectedAlergiFormulaTab
                                                    }}</span
                                                >
                                            </h5>
                                            <span
                                                class="text-[10.5px] text-slate-500 mt-0.5 block"
                                                >Menu Pengganti Khusus PM
                                                Alergi</span
                                            >
                                        </div>
                                        <button
                                            type="button"
                                            @click="
                                                handleQuickPasteGizi(
                                                    akgAlergi[
                                                        selectedAlergiFormulaTab
                                                    ].pk,
                                                    `PK Alergi (${selectedAlergiFormulaTab})`,
                                                )
                                            "
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100/90 text-amber-900 text-[11px] font-extrabold border border-amber-300 transition-all cursor-pointer shadow-2xs shrink-0"
                                            title="Tempel data gizi dari Excel atau teks"
                                        >
                                            <ClipboardPaste
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span>Tempel Gizi</span>
                                        </button>
                                    </div>

                                    <div class="space-y-2">
                                        <div
                                            v-for="nutrisi in [
                                                'energi',
                                                'protein',
                                                'lemak',
                                                'karbohidrat',
                                                'serat',
                                            ]"
                                            :key="nutrisi"
                                            class="p-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-xs font-bold text-slate-700 capitalize"
                                                >{{ nutrisi }}</span
                                            >
                                            <div class="relative w-28 shrink-0">
                                                <input
                                                    v-model.number="
                                                        akgAlergi[
                                                            selectedAlergiFormulaTab
                                                        ].pk[nutrisi]
                                                    "
                                                    @paste="
                                                        onPasteGiziInput(
                                                            $event,
                                                            akgAlergi[
                                                                selectedAlergiFormulaTab
                                                            ].pk,
                                                            nutrisi,
                                                            `PK Alergi (${selectedAlergiFormulaTab})`,
                                                        )
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    placeholder="0"
                                                    class="w-full pl-2 pr-7 py-1 bg-white border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                                <span
                                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-400 pointer-events-none"
                                                    >{{
                                                        nutrisi === "energi"
                                                            ? "kkal"
                                                            : "g"
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PB Alergi -->
                                <div
                                    class="p-4 sm:p-5 bg-white rounded-2xl border border-amber-200 shadow-2xs space-y-3.5"
                                >
                                    <div
                                        class="flex items-center justify-between pb-2 border-b border-amber-100 gap-2"
                                    >
                                        <div>
                                            <h5
                                                class="text-xs font-black text-amber-950 flex items-center gap-1.5"
                                            >
                                                <Sparkles
                                                    class="h-3.5 w-3.5 text-amber-600"
                                                />
                                                <span
                                                    >Porsi Besar (PB) Alergi:
                                                    {{
                                                        selectedAlergiFormulaTab
                                                    }}</span
                                                >
                                            </h5>
                                            <span
                                                class="text-[10.5px] text-slate-500 mt-0.5 block"
                                                >Menu Pengganti Khusus PM
                                                Alergi</span
                                            >
                                        </div>
                                        <button
                                            type="button"
                                            @click="
                                                handleQuickPasteGizi(
                                                    akgAlergi[
                                                        selectedAlergiFormulaTab
                                                    ].pb,
                                                    `PB Alergi (${selectedAlergiFormulaTab})`,
                                                )
                                            "
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100/90 text-amber-900 text-[11px] font-extrabold border border-amber-300 transition-all cursor-pointer shadow-2xs shrink-0"
                                            title="Tempel data gizi dari Excel atau teks"
                                        >
                                            <ClipboardPaste
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span>Tempel Gizi</span>
                                        </button>
                                    </div>

                                    <div class="space-y-2">
                                        <div
                                            v-for="nutrisi in [
                                                'energi',
                                                'protein',
                                                'lemak',
                                                'karbohidrat',
                                                'serat',
                                            ]"
                                            :key="nutrisi"
                                            class="p-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-xs font-bold text-slate-700 capitalize"
                                                >{{ nutrisi }}</span
                                            >
                                            <div class="relative w-28 shrink-0">
                                                <input
                                                    v-model.number="
                                                        akgAlergi[
                                                            selectedAlergiFormulaTab
                                                        ].pb[nutrisi]
                                                    "
                                                    @paste="
                                                        onPasteGiziInput(
                                                            $event,
                                                            akgAlergi[
                                                                selectedAlergiFormulaTab
                                                            ].pb,
                                                            nutrisi,
                                                            `PB Alergi (${selectedAlergiFormulaTab})`,
                                                        )
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    placeholder="0"
                                                    class="w-full pl-2 pr-7 py-1 bg-white border border-slate-300 rounded-lg text-xs font-black text-right text-slate-900 focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                                <span
                                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-400 pointer-events-none"
                                                    >{{
                                                        nutrisi === "energi"
                                                            ? "kkal"
                                                            : "g"
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Aksi Formula Makanan -->
                        <div
                            class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-slate-100"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                @click="activeManualTab = 'perencanaan'"
                                className="border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-bold px-5 h-11 flex items-center justify-center gap-2 rounded-xl cursor-pointer w-full sm:w-auto text-center"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                <span>Kembali ke Perencanaan Produksi</span>
                            </Button>

                            <Button
                                type="button"
                                @click="handleSavePerencanaanManualComplete"
                                :disabled="isSubmitting"
                                className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-6 h-11 flex items-center justify-center gap-2 rounded-xl shadow-xs cursor-pointer w-full sm:w-auto text-center"
                            >
                                <Save class="h-4 w-4" />
                                <span>Simpan Work Order</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL KONFIRMASI TINGGALKAN HALAMAN (PERUBAHAN BELUM DISIMPAN)            -->
        <!-- ========================================================================= -->
        <Modal
            :show="showLeaveConfirmModal"
            @close="handleCancelLeave"
            max-width="md"
        >
            <div class="p-5 sm:p-6 space-y-4">
                <!-- Icon & Header -->
                <div class="flex items-start gap-3.5">
                    <div
                        class="h-11 w-11 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200 shadow-2xs"
                    >
                        <AlertTriangle class="h-6 w-6 stroke-[2.2]" />
                    </div>
                    <div class="space-y-1">
                        <h3
                            class="text-base font-black text-slate-900 leading-snug"
                        >
                            Perubahan Belum Disimpan
                        </h3>
                        <p class="text-xs font-semibold text-slate-500">
                            Konfirmasi Meninggalkan Halaman Perencanaan Work
                            Order
                        </p>
                    </div>
                </div>

                <!-- Konten Penjelasan -->
                <div
                    class="bg-amber-50/80 border border-amber-200/90 rounded-2xl p-4 space-y-2 text-xs text-amber-950 leading-relaxed shadow-2xs"
                >
                    <p class="font-bold">
                        Anda telah melakukan perubahan pada rancangan Work Order
                        ini yang belum disimpan.
                    </p>
                    <p
                        v-if="pendingNavigation?.type === 'reload'"
                        class="text-slate-600 text-[11.5px]"
                    >
                        Anda akan
                        <strong>memuat ulang (refresh)</strong> halaman ini.
                        Seluruh data perubahan yang belum disimpan akan
                        <strong>hilang secara permanen</strong>.
                    </p>
                    <p
                        v-else-if="pendingNavigation?.type === 'history_back'"
                        class="text-slate-600 text-[11.5px]"
                    >
                        Anda menekan tombol <strong>kembali (back)</strong> pada
                        browser. Seluruh data perubahan yang belum disimpan akan
                        <strong>hilang secara permanen</strong>.
                    </p>
                    <p v-else class="text-slate-600 text-[11.5px]">
                        Jika Anda beralih ke halaman lain sekarang, seluruh data
                        perencanaan produksi yang belum disimpan akan
                        <strong>hilang secara permanen</strong>.
                    </p>
                </div>

                <div
                    class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-[11px] text-slate-600 flex items-center gap-2"
                >
                    <Lightbulb class="h-4 w-4 text-amber-500 shrink-0" />
                    <span
                        >Silakan simpan terlebih dahulu rancangan Work Order ini
                        sebelum berpindah halaman.</span
                    >
                </div>

                <!-- Footer Aksi -->
                <div
                    class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-3 border-t border-slate-100"
                >
                    <button
                        type="button"
                        @click="handleCancelLeave"
                        class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 rounded-xl transition cursor-pointer text-center"
                    >
                        Tetap di Halaman Ini
                    </button>
                    <button
                        type="button"
                        @click="handleConfirmLeave"
                        class="w-full sm:w-auto px-4 py-2.5 text-xs font-black text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition cursor-pointer shadow-xs text-center"
                    >
                        Ya, Tinggalkan Halaman
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal Manual Edit (Gizi & Alergi) -->
        <WorkOrderManualEditModal
            :show="showManualModal"
            :work-order="selectedManualWo"
            :kelompok-list="kelompokList"
            @close="closeManualEditor"
            @saved="handleManualSaved"
        />

        <!-- ========================================================================= -->
        <!-- MODAL TEMPEL (PASTE) LIST SUB MENU                                       -->
        <!-- ========================================================================= -->
        <Modal
            :show="showModalPasteSubMenu"
            @close="showModalPasteSubMenu = false"
            max-width="md"
        >
            <div class="p-5 sm:p-6 space-y-4">
                <div class="flex items-start gap-3.5">
                    <div
                        class="h-11 w-11 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0 border border-primary/20 shadow-2xs"
                    >
                        <ClipboardPaste class="h-6 w-6 stroke-[2.2]" />
                    </div>
                    <div class="space-y-1">
                        <h3
                            class="text-base font-black text-slate-900 leading-snug"
                        >
                            Tempel List Sub Menu
                        </h3>
                        <p class="text-xs font-semibold text-slate-500">
                            Salin list dari Excel, Word, Notepad, atau WA dan
                            tempel di sini.
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700">
                        Teks Daftar Menu (1 baris per sub menu):
                    </label>
                    <textarea
                        v-model="pasteModalInputText"
                        rows="6"
                        placeholder="Contoh:&#10;1. Nasi Putih&#10;2. Ayam Goreng Lengkuas&#10;3. Tempe Bacem&#10;4. Sayur Sop Bening&#10;5. Buah Pisang Ambon&#10;6. Susu UHT"
                        class="w-full text-xs font-medium rounded-xl border border-slate-300 p-3 text-slate-800 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none shadow-2xs font-mono"
                    ></textarea>

                    <div
                        class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-[11px] text-slate-600 space-y-1"
                    >
                        <p
                            class="font-bold text-slate-800 flex items-center gap-1.5"
                        >
                            <Lightbulb
                                class="h-3.5 w-3.5 text-amber-500 shrink-0"
                            />
                            <span>Cara Kerja Cerdas:</span>
                        </p>
                        <ul
                            class="list-disc list-inside space-y-0.5 text-slate-600 pl-1 text-[10.5px]"
                        >
                            <li>
                                Mendukung salin dari sel Excel (vertikal &
                                horizontal).
                            </li>
                            <li>
                                Nomor otomatis (1., 2.), strip (-), dan simbol
                                bullet (•) akan dibersihkan.
                            </li>
                            <li>
                                Sub menu tambahan otomatis bertambah jika list
                                lebih dari 5 item.
                            </li>
                            <li>
                                Anda juga bisa langsung
                                <span class="font-bold text-slate-800"
                                    >Ctrl + V</span
                                >
                                di input Sub Menu mana pun!
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100"
                >
                    <button
                        type="button"
                        @click="showModalPasteSubMenu = false"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleApplyPasteFromModal"
                        :disabled="!pasteModalInputText.trim()"
                        class="px-4 py-2.5 rounded-xl text-xs font-black bg-primary text-white hover:bg-primary-hover disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs transition-all cursor-pointer flex items-center gap-1.5"
                    >
                        <Check class="h-3.5 w-3.5" />
                        <span>Terapkan ke Sub Menu</span>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- ========================================================================= -->
        <!-- MODAL TEMPEL (PASTE) NILAI GIZI (AKG) REUSABLE                            -->
        <!-- ========================================================================= -->
        <GiziPasteModal
            :show="showGiziPasteModal"
            :target-title="giziPasteTargetTitle"
            :initial-text="giziPasteInitialText"
            @close="showGiziPasteModal = false"
            @apply="handleApplyGiziFromModal"
        />

        <!-- Toast Floating Notifikasi Sukses Paste -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-3 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-3 opacity-0"
        >
            <div
                v-if="pasteToastMessage"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-slate-900/95 backdrop-blur text-white shadow-2xl border border-slate-700/80 text-xs font-black pointer-events-auto"
            >
                <div
                    class="h-6 w-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0"
                >
                    <CheckCircle2 class="h-4 w-4" />
                </div>
                <span>{{ pasteToastMessage }}</span>
            </div>
        </transition>
    </AppLayout>
</template>
