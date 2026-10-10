<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import {
    FileSpreadsheet,
    Plus,
    Trash2,
    Save,
    Send,
    AlertCircle,
    AlertTriangle,
    CheckCircle2,
    Sparkles,
    ShieldAlert,
    ChefHat,
    Layers,
    Activity,
    ArrowLeft,
    Calendar,
    Users,
    Clock,
    FileText,
    Info,
    HelpCircle,
    Utensils,
    HeartPulse,
    Lightbulb,
    ClipboardPaste,
} from "lucide-vue-next";
import { ALERGI_OPTIONS, REKOMENDASI_SUBSTITUSI } from "@/Services/penerimaManfaatConfig";
import GiziPasteModal from "@/Components/GiziPasteModal.vue";
import {
    handleNutritionPasteEvent,
    quickPasteNutritionFromClipboard,
    applyNutritionValues,
} from "@/Services/giziPasteHelper";

const props = defineProps({
    workOrder: {
        type: Object,
        required: true,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    isEmbedded: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["saved", "back"]);

// Form State
const namaMenu = ref("");
const subMenus = ref([]);
const subMenuAlergi = ref({});
const akgPb = ref({
    energi: 0,
    protein: 0,
    lemak: 0,
    karbohidrat: 0,
    serat: 0,
});
const akgPk = ref({
    energi: 0,
    protein: 0,
    lemak: 0,
    karbohidrat: 0,
    serat: 0,
});
const akgAlergi = ref({});
const catatan = ref("");
const isSubmitting = ref(false);
const activeTabGizi = ref("normal"); // 'normal' | 'alergi'
const selectedAlergiTab = ref("");
const showSuccessNotice = ref(false);
const successMessage = ref("");

// Helper pembersih jenis alergi
function extractCleanJenis(val) {
    if (!val) return "Telur";
    if (typeof val === "object" && val !== null) {
        return String(val.value || val.label || "Telur");
    }
    const str = String(val).trim();
    if (str.startsWith("{") && str.endsWith("}")) {
        try {
            const parsed = JSON.parse(str);
            return String(parsed.value || parsed.label || "Telur");
        } catch (e) {
            // ignore
        }
    }
    return str;
}

// Opsi Alergen yang Dinormalisasi (Murni string value dan label)
const formattedAllergyOptions = computed(() => {
    return (ALERGI_OPTIONS || []).map((opt) => {
        if (typeof opt === "object" && opt !== null) {
            return {
                value: String(opt.value || opt.label || ""),
                label: String(opt.label || opt.value || ""),
            };
        }
        return {
            value: String(opt),
            label: String(opt),
        };
    }).filter((opt) => opt.value);
});

// Inisialisasi saat workOrder berubah
watch(
    () => props.workOrder,
    (wo) => {
        if (!wo) return;
        namaMenu.value = wo.nama_menu || "";

        // Rangkai Sub Menus (Array Dinamis)
        if (Array.isArray(wo.sub_menus) && wo.sub_menus.length > 0) {
            subMenus.value = wo.sub_menus.map((s) => (typeof s === "string" ? s : s?.nama || ""));
        } else {
            subMenus.value = [
                wo.sub_menu_1 || "",
                wo.sub_menu_2 || "",
                wo.sub_menu_3 || "",
                wo.sub_menu_4 || "",
                wo.sub_menu_5 || "",
            ];
        }
        // Minimal 5 slot default
        while (subMenus.value.length < 5) {
            subMenus.value.push("");
        }

        // Sub Menu Alergi (Pastikan jenis_alergi bersih sebagai string murni)
        const rawAlergi = wo.sub_menu_alergi || {};
        const parsedAlergi = {};
        subMenus.value.forEach((_, idx) => {
            const key = `sub_menu_${idx + 1}`;
            const rawList = Array.isArray(rawAlergi[key]) ? rawAlergi[key] : [];
            parsedAlergi[key] = rawList.map((item) => ({
                jenis_alergi: extractCleanJenis(item?.jenis_alergi),
                menu_pengganti: typeof item?.menu_pengganti === "string" ? item.menu_pengganti : (item?.menu_pengganti?.nama || ""),
            }));
        });
        subMenuAlergi.value = parsedAlergi;

        // AKG PB
        akgPb.value = {
            energi: Number(wo.akg_pb?.energi) || 0,
            protein: Number(wo.akg_pb?.protein) || 0,
            lemak: Number(wo.akg_pb?.lemak) || 0,
            karbohidrat: Number(wo.akg_pb?.karbohidrat) || 0,
            serat: Number(wo.akg_pb?.serat) || 0,
        };

        // AKG PK
        akgPk.value = {
            energi: Number(wo.akg_pk?.energi) || 0,
            protein: Number(wo.akg_pk?.protein) || 0,
            lemak: Number(wo.akg_pk?.lemak) || 0,
            karbohidrat: Number(wo.akg_pk?.karbohidrat) || 0,
            serat: Number(wo.akg_pk?.serat) || 0,
        };

        // AKG Alergi
        akgAlergi.value = wo.akg_alergi && typeof wo.akg_alergi === "object" ? { ...wo.akg_alergi } : {};

        catatan.value = typeof wo.catatan === "string" ? wo.catatan : (wo.catatan?.catatan_resep || "");

        // Set active alergi tab jika ada
        const allAlergiKeys = Object.values(parsedAlergi)
            .flat()
            .map((a) => extractCleanJenis(a?.jenis_alergi))
            .filter(Boolean);
        selectedAlergiTab.value = allAlergiKeys[0] || "";
    },
    { immediate: true }
);

// Tambah Sub Menu Baru (6, 7, 8, dst)
function addSubMenu() {
    subMenus.value.push("");
    const newKey = `sub_menu_${subMenus.value.length}`;
    if (!subMenuAlergi.value[newKey]) {
        subMenuAlergi.value[newKey] = [];
    }
}

// Hapus Sub Menu (Minimal 1)
function removeSubMenu(index) {
    if (subMenus.value.length <= 1) return;
    const removedKey = `sub_menu_${index + 1}`;
    subMenus.value.splice(index, 1);
    delete subMenuAlergi.value[removedKey];

    // Reindex subMenuAlergi keys
    const reindexed = {};
    subMenus.value.forEach((_, idx) => {
        const oldKey = `sub_menu_${idx + 1}`;
        reindexed[oldKey] = subMenuAlergi.value[oldKey] || [];
    });
    subMenuAlergi.value = reindexed;
}

// State & Method Tempel (Paste) List Sub Menu
const showModalPasteSubMenu = ref(false);
const pasteModalInputText = ref("");

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
            str = str.replace(/^([0-9]+[\.\)\-]\s*|[\-\*\•\–\—]\s*)/, "").trim();
            return str;
        })
        .filter((item) => item.length > 0);
}

function applySubMenuItems(items, startIdx = 0) {
    if (!items || items.length === 0) return 0;

    items.forEach((val, i) => {
        const targetIdx = startIdx + i;
        while (targetIdx >= subMenus.value.length) {
            addSubMenu();
        }
        subMenus.value[targetIdx] = val;
    });
}

function handlePasteSubMenu(event, startIdx) {
    const clipboardData = event.clipboardData || window.clipboardData;
    if (!clipboardData) return;

    const pastedText = clipboardData.getData("text");
    if (!pastedText) return;

    const parsedItems = parsePastedSubMenuText(pastedText);

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
        console.warn("Clipboard read blocked, opening modal fallback:", err);
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
            // Berhasil diterapkan langsung
        },
        (clipText) => {
            openGiziPasteModal(targetObj, title, clipText);
        },
    );
}

function onPasteGiziInput(event, targetObj, key) {
    handleNutritionPasteEvent(event, targetObj, key);
}

function handleApplyGiziFromModal(parsedValues) {
    if (giziPasteTargetObj.value) {
        applyNutritionValues(giziPasteTargetObj.value, parsedValues);
    }
    showGiziPasteModal.value = false;
}

// Tambah Varian Alergi Pengganti pada Sub Menu Tertentu
function addAlergiPengganti(subKey) {
    if (!subMenuAlergi.value[subKey]) {
        subMenuAlergi.value[subKey] = [];
    }
    subMenuAlergi.value[subKey].push({
        jenis_alergi: "Telur",
        menu_pengganti: "",
    });
}

function removeAlergiPengganti(subKey, idx) {
    if (subMenuAlergi.value[subKey]) {
        subMenuAlergi.value[subKey].splice(idx, 1);
    }
}

// Seluruh Daftar Jenis Alergi yang Dikonfigurasi (Array of clean strings)
const configuredAllergies = computed(() => {
    const set = new Set();
    Object.values(subMenuAlergi.value || {}).forEach((arr) => {
        if (Array.isArray(arr)) {
            arr.forEach((it) => {
                const j = extractCleanJenis(it?.jenis_alergi);
                if (j) set.add(j);
            });
        }
    });
    return Array.from(set);
});

// Pastikan slot AKG Alergi ada
function ensureAkgAlergi(jenis) {
    const cleanJ = extractCleanJenis(jenis);
    if (!akgAlergi.value[cleanJ]) {
        akgAlergi.value[cleanJ] = {
            akg_pb: { ...akgPb.value },
            akg_pk: { ...akgPk.value },
        };
    }
    return akgAlergi.value[cleanJ];
}

// Helper rekomendasi substitusi yang aman
function getRekomendasiPengganti(jenisAlergi) {
    const clean = extractCleanJenis(jenisAlergi);
    const rec = REKOMENDASI_SUBSTITUSI ? REKOMENDASI_SUBSTITUSI[clean] : null;
    if (rec && typeof rec === "string") {
        return rec.split(",")[0].trim();
    }
    return "Menu Alternatif Aman";
}

// Simpan Data Manual
function handleSave(statusOverride = null) {
    if (!props.workOrder) return;
    isSubmitting.value = true;

    // Bersihkan sub menus kosong di bagian ujung
    const cleanSubMenus = subMenus.value.map((s) => s.trim()).filter((s, idx) => s || idx < 5);

    // Sanitasi subMenuAlergi agar jenis_alergi dijamin string murni
    const sanitizedAlergi = {};
    Object.entries(subMenuAlergi.value || {}).forEach(([k, items]) => {
        if (Array.isArray(items)) {
            sanitizedAlergi[k] = items.map((it) => ({
                jenis_alergi: extractCleanJenis(it?.jenis_alergi),
                menu_pengganti: typeof it?.menu_pengganti === "string" ? it.menu_pengganti.trim() : "",
            }));
        } else {
            sanitizedAlergi[k] = [];
        }
    });

    const payload = {
        nama_menu: namaMenu.value.trim() || props.workOrder.nama_menu,
        sub_menus: cleanSubMenus,
        sub_menu_alergi: sanitizedAlergi,
        akg_pb: akgPb.value,
        akg_pk: akgPk.value,
        akg_alergi: akgAlergi.value,
        catatan: catatan.value,
        status: props.workOrder.status || "Draft",
    };

    router.put(route("work-order.update-manual", props.workOrder.id), payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            successMessage.value = "Data perencanaan dan kandungan gizi manual berhasil disimpan.";
            showSuccessNotice.value = true;
            setTimeout(() => {
                showSuccessNotice.value = false;
            }, 5000);
            nextTick(() => {
                initialSnapshot.value = takeSnapshot();
            });
            emit("saved");
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

// ─── DIRTY STATE & KONFIRMASI TINGGALKAN EDITOR ─────────────────────────────
const initialSnapshot = ref(null);

function takeSnapshot() {
    return JSON.stringify({
        namaMenu: (namaMenu.value || "").trim(),
        subMenus: (subMenus.value || []).map((s) => (s?.nama || "").trim()),
        subMenuAlergi: subMenuAlergi.value || {},
        akgPb: akgPb.value || {},
        akgPk: akgPk.value || {},
        akgAlergi: akgAlergi.value || {},
        catatan: (catatan.value || "").trim(),
    });
}

const isFormDirty = computed(() => {
    if (!initialSnapshot.value) return false;
    return initialSnapshot.value !== takeSnapshot();
});

const showLeaveConfirmModal = ref(false);
const pendingLeaveAction = ref(null);
const pendingNavigationUrl = ref(null);
const isNavigationConfirmed = ref(false);

function handleRequestClose() {
    if (isFormDirty.value) {
        pendingLeaveAction.value = "close";
        showLeaveConfirmModal.value = true;
    } else {
        emit("back");
    }
}

function handleConfirmLeave() {
    showLeaveConfirmModal.value = false;
    isNavigationConfirmed.value = true;
    const action = pendingLeaveAction.value;
    pendingLeaveAction.value = null;

    if (action === "reload") {
        window.location.reload();
        return;
    }
    if (action === "navigation" && pendingNavigationUrl.value) {
        router.visit(pendingNavigationUrl.value);
        return;
    }
    emit("back");
}

function handleCancelLeave() {
    showLeaveConfirmModal.value = false;
    pendingLeaveAction.value = null;
    pendingNavigationUrl.value = null;
}

function handleBeforeUnload(e) {
    if (isFormDirty.value && !isSubmitting.value && !isNavigationConfirmed.value) {
        e.preventDefault();
        e.returnValue = "";
        return "";
    }
}

function handleKeyDown(e) {
    if (isSubmitting.value || isNavigationConfirmed.value || !isFormDirty.value) {
        return;
    }
    const isReloadKey =
        e.key === "F5" ||
        ((e.ctrlKey || e.metaKey) && (e.key === "r" || e.key === "R"));
    if (isReloadKey) {
        e.preventDefault();
        e.stopPropagation();
        pendingLeaveAction.value = "reload";
        showLeaveConfirmModal.value = true;
    }
}

let removeRouterHook = null;

onMounted(() => {
    window.addEventListener("beforeunload", handleBeforeUnload);
    window.addEventListener("keydown", handleKeyDown);

    removeRouterHook = router.on("before", (event) => {
        if (isSubmitting.value || isNavigationConfirmed.value) return;
        if (isFormDirty.value) {
            event.preventDefault();
            pendingLeaveAction.value = "navigation";
            pendingNavigationUrl.value = event.detail?.visit?.url;
            showLeaveConfirmModal.value = true;
        }
    });

    nextTick(() => {
        initialSnapshot.value = takeSnapshot();
    });
});

onUnmounted(() => {
    window.removeEventListener("beforeunload", handleBeforeUnload);
    window.removeEventListener("keydown", handleKeyDown);
    if (typeof removeRouterHook === "function") {
        removeRouterHook();
    }
});

defineExpose({
    handleRequestClose,
    isFormDirty,
});
</script>

<template>
    <div class="space-y-6">
        <!-- Banner Sukses -->
        <div
            v-if="showSuccessNotice"
            class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between animate-in fade-in"
        >
            <div class="flex items-center gap-2.5">
                <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0" />
                <span class="text-xs sm:text-sm font-bold">{{ successMessage }}</span>
            </div>
            <button
                type="button"
                @click="showSuccessNotice = false"
                class="text-emerald-600 hover:text-emerald-800 p-1 text-xs font-bold cursor-pointer"
            >
                ✕
            </button>
        </div>

        <!-- Mode Standalone (isEmbedded: true) vs Mode Modal (isEmbedded: false) -->
        <div
            :class="[
                isEmbedded
                    ? 'bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden'
                    : 'space-y-6'
            ]"
        >
            <!-- Top Gradient Bar HANYA TAMPIL di mode Standalone -->
            <div
                v-if="isEmbedded"
                class="px-6 py-5 bg-gradient-to-r from-amber-600 via-amber-700 to-orange-700 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3.5">
                    <div class="p-3 rounded-2xl bg-white/15 border border-white/20 shadow-xs">
                        <FileSpreadsheet class="h-7 w-7 text-white" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-lg bg-white/20 text-[11px] font-black tracking-wider uppercase">
                                ✍️ Work Order Mode Manual
                            </span>
                            <span class="text-xs text-amber-200 font-extrabold tracking-wide">
                                {{ workOrder.nomor_wo }}
                            </span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-black text-white leading-tight mt-0.5">
                            Rancang Komponen Menu & Kandungan Gizi (Manual)
                        </h2>
                    </div>
                </div>

                <!-- Action Button di Header Bar Standalone -->
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="emit('back')"
                        class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer border border-white/20"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        <span>Kembali</span>
                    </button>
                    <button
                        type="button"
                        @click="handleSave()"
                        :disabled="isSubmitting"
                        class="px-4 py-2 rounded-xl bg-white text-amber-900 hover:bg-amber-50 text-xs font-black transition-all flex items-center gap-1.5 cursor-pointer shadow-xs disabled:opacity-50"
                    >
                        <Save class="h-3.5 w-3.5 text-amber-700" />
                        <span>Simpan Data</span>
                    </button>
                </div>
            </div>

            <!-- Ringkasan Info Perencanaan Produksi -->
            <div
                :class="[
                    'grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs',
                    isEmbedded
                        ? 'p-6 bg-amber-50/40 border-b border-amber-100'
                        : 'p-4 bg-amber-50/50 rounded-2xl border border-amber-200/80 shadow-2xs'
                ]"
            >
                <div class="bg-white p-3 rounded-2xl border border-amber-200/80 shadow-2xs">
                    <span class="text-slate-500 font-semibold block flex items-center gap-1.5">
                        <Calendar class="h-3.5 w-3.5 text-amber-600" />
                        <span>Tanggal Distribusi</span>
                    </span>
                    <strong class="text-slate-900 font-black text-sm block mt-1">
                        {{ workOrder.tanggal_distribusi }}
                    </strong>
                </div>

                <div class="bg-white p-3 rounded-2xl border border-amber-200/80 shadow-2xs">
                    <span class="text-slate-500 font-semibold block flex items-center gap-1.5">
                        <Users class="h-3.5 w-3.5 text-amber-600" />
                        <span>Total Sasaran PM</span>
                    </span>
                    <strong class="text-slate-900 font-black text-sm block mt-1">
                        {{ (workOrder.total_pm || 0).toLocaleString('id-ID') }} Porsi
                        <span class="text-[11px] font-bold text-slate-500 block sm:inline">
                            ({{ workOrder.total_pk || 0 }} PK, {{ workOrder.total_pb || 0 }} PB)
                        </span>
                    </strong>
                </div>
            </div>

            <!-- Form Body -->
            <div :class="[isEmbedded ? 'p-6 space-y-8' : 'space-y-6']">
                <!-- 1. NAMA MENU UTAMA -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <ChefHat class="h-4 w-4 text-amber-600" />
                        <span>Nama Menu Utama MBG</span>
                    </label>
                    <input
                        v-model="namaMenu"
                        type="text"
                        placeholder="Contoh: Nasi Putih + Telur Balado + Tahu Bacem + Sayur Bayam Labu + Semangka"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-800 text-sm focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all shadow-2xs"
                    />
                </div>

                <!-- 2. SUB-SUB KOMPONEN MENU DINAMIS -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <Layers class="h-4 w-4 text-amber-600" />
                                <span>Sub-Sub Komponen Menu (Total: {{ subMenus.length }} Sub Menu)</span>
                            </label>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Default 5 komponen menu MBG. Anda dapat menambahkan sub-menu ke-6, ke-7, ke-8 dst secara dinamis.
                            </p>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="handleQuickPasteButtonClick"
                                class="flex-1 sm:flex-initial px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs whitespace-nowrap shrink-0"
                                title="Tempel list dari Excel, Word, Notepad, dll"
                            >
                                <ClipboardPaste class="h-3.5 w-3.5 text-amber-700 shrink-0" />
                                <span>Tempel List</span>
                            </button>
                            <button
                                type="button"
                                @click="addSubMenu"
                                class="flex-1 sm:flex-initial px-3.5 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs whitespace-nowrap shrink-0"
                            >
                                <Plus class="h-3.5 w-3.5 shrink-0" />
                                <span>Tambah Sub Menu</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="(sub, sIdx) in subMenus"
                            :key="sIdx"
                            class="p-4 bg-slate-50/90 rounded-2xl border border-slate-200/90 space-y-3 transition-all hover:border-amber-300 hover:shadow-2xs"
                        >
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1.5 bg-amber-600 text-white rounded-xl text-xs font-black shrink-0 shadow-2xs">
                                    Sub {{ sIdx + 1 }}
                                </span>
                                <input
                                    v-model="subMenus[sIdx]"
                                    type="text"
                                    @paste="handlePasteSubMenu($event, sIdx)"
                                    :placeholder="`Nama Sub Menu ${sIdx + 1} (contoh: Nasi, Lauk Hewani, Lauk Nabati, Sayur, Buah)`"
                                    class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all shadow-2xs"
                                />
                                <button
                                    v-if="subMenus.length > 1"
                                    type="button"
                                    @click="removeSubMenu(sIdx)"
                                    title="Hapus Sub Menu Ini"
                                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Opsi Menu Alergi Pengganti pada Sub Menu Ini -->
                            <div class="pl-3 border-l-2 border-amber-300 space-y-2.5 pt-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-amber-900 flex items-center gap-1.5">
                                        <ShieldAlert class="h-3.5 w-3.5 text-amber-600" />
                                        <span>Menu Pengganti Alergi untuk Sub-{{ sIdx + 1 }}:</span>
                                    </span>
                                    <button
                                        type="button"
                                        @click="addAlergiPengganti(`sub_menu_${sIdx + 1}`)"
                                        class="px-2 py-1 rounded-lg bg-amber-100/80 hover:bg-amber-200 text-amber-900 text-[11px] font-black transition-colors cursor-pointer flex items-center gap-1"
                                    >
                                        <Plus class="h-3 w-3" />
                                        <span>Tambah Varian Alergi</span>
                                    </button>
                                </div>

                                <div
                                    v-if="subMenuAlergi[`sub_menu_${sIdx + 1}`]?.length > 0"
                                    class="space-y-2"
                                >
                                    <div
                                        v-for="(alItem, aIdx) in subMenuAlergi[`sub_menu_${sIdx + 1}`]"
                                        :key="aIdx"
                                        class="flex items-center gap-2 bg-white p-2.5 rounded-xl border border-amber-200 text-xs shadow-2xs"
                                    >
                                        <!-- Select Dropdown dengan Label Bersih (Bukan Object JSON) -->
                                        <select
                                            v-model="alItem.jenis_alergi"
                                            class="px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 outline-none focus:ring-1 focus:ring-amber-500 max-w-[150px] sm:max-w-[180px] shrink-0"
                                        >
                                            <option
                                                v-for="opt in formattedAllergyOptions"
                                                :key="opt.value"
                                                :value="opt.value"
                                            >
                                                Alergi: {{ opt.label }}
                                            </option>
                                        </select>

                                        <!-- Input Menu Pengganti -->
                                        <input
                                            v-model="alItem.menu_pengganti"
                                            type="text"
                                            :placeholder="`Pengganti (cth: ${getRekomendasiPengganti(alItem.jenis_alergi)})`"
                                            class="flex-1 px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-800 outline-none focus:ring-1 focus:ring-amber-500"
                                        />

                                        <button
                                            type="button"
                                            @click="removeAlergiPengganti(`sub_menu_${sIdx + 1}`, aIdx)"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg cursor-pointer transition-colors"
                                            title="Hapus varian alergi ini"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                                <p v-else class="text-[10.5px] text-slate-400 italic">
                                    Tidak ada varian alergi pengganti pada sub menu ini.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. INPUT NILAI KANDUNGAN GIZI (AKG) MANUAL -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between flex-wrap gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <HeartPulse class="h-4 w-4 text-emerald-600" />
                                <span>Input Kandungan Gizi (AKG) Langsung</span>
                            </label>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Masukkan total nilai gizi makanan per porsi untuk Porsi Kecil (PK) dan Porsi Besar (PB).
                            </p>
                        </div>

                        <!-- Tab Selector: Gizi Porsi Normal vs Varian Alergi -->
                        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold">
                            <button
                                type="button"
                                @click="activeTabGizi = 'normal'"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5',
                                    activeTabGizi === 'normal'
                                        ? 'bg-white text-emerald-800 shadow-2xs font-black'
                                        : 'text-slate-600 hover:text-slate-900',
                                ]"
                            >
                                <Sparkles class="h-3.5 w-3.5 text-emerald-600" />
                                <span>Porsi Normal (Standar)</span>
                            </button>
                            <button
                                v-if="configuredAllergies.length > 0"
                                type="button"
                                @click="activeTabGizi = 'alergi'"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5',
                                    activeTabGizi === 'alergi'
                                        ? 'bg-amber-600 text-white shadow-2xs font-black'
                                        : 'text-amber-800 hover:text-amber-950',
                                ]"
                            >
                                <ShieldAlert class="h-3.5 w-3.5" />
                                <span>Varian Alergi ({{ configuredAllergies.length }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 1: GIZI PORSI NORMAL (PK & PB) -->
                    <div v-if="activeTabGizi === 'normal'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- 1. Porsi Kecil (PK) - First on the left -->
                        <div class="p-4 sm:p-5 bg-blue-50/70 rounded-2xl border border-blue-200/90 space-y-3.5 shadow-2xs">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h4 class="text-sm font-black text-blue-950 flex items-center gap-1.5">
                                        <Sparkles class="h-4 w-4 text-blue-600" />
                                        <span>Porsi Kecil (PK) - Normal</span>
                                    </h4>
                                    <span class="text-[11px] text-slate-500 font-medium block mt-0.5">
                                        Sasaran: PAUD, TK, Kelas 1 - 3 SD (Standar: 500 - 600 kkal)
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button
                                        type="button"
                                        @click="handleQuickPasteGizi(akgPk, 'Porsi Kecil (PK) Normal')"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white hover:bg-blue-100/80 text-blue-700 text-[11px] font-extrabold border border-blue-200/90 transition-all cursor-pointer shadow-2xs"
                                        title="Tempel data gizi dari Excel atau teks"
                                    >
                                        <ClipboardPaste class="h-3.5 w-3.5 shrink-0" />
                                        <span>Tempel Gizi</span>
                                    </button>
                                    <span class="text-xs font-black text-blue-800 bg-blue-100 px-2.5 py-1 rounded-full border border-blue-300 shadow-2xs shrink-0">
                                        {{ workOrder.total_pk || 0 }} Sasaran PK
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-2 pt-1">
                                <!-- Energi -->
                                <div class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🔥</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Energi</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 500 - 600 kkal</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPk.energi"
                                            @paste="onPasteGiziInput($event, akgPk, 'energi')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-10 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-blue-900 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">kkal</span>
                                    </div>
                                </div>

                                <!-- Protein -->
                                <div class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🥩</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Protein</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 8.0 - 10.0 g</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPk.protein"
                                            @paste="onPasteGiziInput($event, akgPk, 'protein')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-blue-900 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">g</span>
                                    </div>
                                </div>

                                <!-- Lemak -->
                                <div class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🥑</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Lemak</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 11.0 - 14.0 g</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPk.lemak"
                                            @paste="onPasteGiziInput($event, akgPk, 'lemak')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-blue-900 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">g</span>
                                    </div>
                                </div>

                                <!-- Karbohidrat -->
                                <div class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🍚</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Karbohidrat</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 80.0 - 95.0 g</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPk.karbohidrat"
                                            @paste="onPasteGiziInput($event, akgPk, 'karbohidrat')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-blue-900 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">g</span>
                                    </div>
                                </div>

                                <!-- Serat -->
                                <div class="p-2.5 bg-white rounded-xl border border-blue-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🥗</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Serat</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 3.0 - 5.0 g</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPk.serat"
                                            @paste="onPasteGiziInput($event, akgPk, 'serat')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-blue-900 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">g</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Porsi Besar (PB) - Second on the right -->
                        <div class="p-4 sm:p-5 bg-emerald-50/70 rounded-2xl border border-emerald-200/90 space-y-3.5 shadow-2xs">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h4 class="text-sm font-black text-emerald-950 flex items-center gap-1.5">
                                        <Sparkles class="h-4 w-4 text-emerald-600" />
                                        <span>Porsi Besar (PB) - Normal</span>
                                    </h4>
                                    <span class="text-[11px] text-slate-500 font-medium block mt-0.5">
                                        Sasaran: Kelas 4 - 6 SD, SMP, SMA (Standar: 650 - 750 kkal)
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button
                                        type="button"
                                        @click="handleQuickPasteGizi(akgPb, 'Porsi Besar (PB) Normal')"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-100/80 text-emerald-700 text-[11px] font-extrabold border border-emerald-200/90 transition-all cursor-pointer shadow-2xs"
                                        title="Tempel data gizi dari Excel atau teks"
                                    >
                                        <ClipboardPaste class="h-3.5 w-3.5 shrink-0" />
                                        <span>Tempel Gizi</span>
                                    </button>
                                    <span class="text-xs font-black text-emerald-800 bg-emerald-100 px-2.5 py-1 rounded-full border border-emerald-300 shadow-2xs shrink-0">
                                        {{ workOrder.total_pb || 0 }} Sasaran PB
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-2 pt-1">
                                <!-- Energi -->
                                <div class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🔥</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Energi</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 650 - 750 kkal</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPb.energi"
                                            @paste="onPasteGiziInput($event, akgPb, 'energi')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-10 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-emerald-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">kkal</span>
                                    </div>
                                </div>

                                <!-- Protein -->
                                <div class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🥩</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Protein</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 15.0 - 25.0 g</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPb.protein"
                                            @paste="onPasteGiziInput($event, akgPb, 'protein')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-emerald-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">g</span>
                                    </div>
                                </div>

                                <!-- Lemak -->
                                <div class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🥑</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Lemak</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 18.0 - 28.0 g</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPb.lemak"
                                            @paste="onPasteGiziInput($event, akgPb, 'lemak')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-emerald-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">g</span>
                                    </div>
                                </div>

                                <!-- Karbohidrat -->
                                <div class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🍚</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Karbohidrat</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 100.0 - 130.0 g</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPb.karbohidrat"
                                            @paste="onPasteGiziInput($event, akgPb, 'karbohidrat')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-emerald-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">g</span>
                                    </div>
                                </div>

                                <!-- Serat -->
                                <div class="p-2.5 bg-white rounded-xl border border-emerald-200/90 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition-all">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base shrink-0">🥗</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block truncate">Serat</span>
                                            <span class="text-[10px] text-slate-400 block truncate">Standar: 5.0 - 8.0 g</span>
                                        </div>
                                    </div>
                                    <div class="relative w-32 shrink-0">
                                        <input
                                            v-model.number="akgPb.serat"
                                            @paste="onPasteGiziInput($event, akgPb, 'serat')"
                                            type="number"
                                            step="0.1"
                                            placeholder="0"
                                            class="w-full pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-black text-sm text-emerald-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">g</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: GIZI VARIAN ALERGI -->
                    <div v-else-if="activeTabGizi === 'alergi'" class="space-y-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <button
                                v-for="al in configuredAllergies"
                                :key="al"
                                type="button"
                                @click="selectedAlergiTab = al"
                                :class="[
                                    'px-4 py-2 rounded-xl text-xs font-black transition-all cursor-pointer border flex items-center gap-1.5',
                                    selectedAlergiTab === al
                                        ? 'bg-amber-600 text-white border-amber-600 shadow-2xs'
                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50',
                                ]"
                            >
                                <ShieldAlert class="h-3.5 w-3.5" />
                                <span>{{ al }}</span>
                            </button>
                        </div>

                        <div
                            v-if="selectedAlergiTab"
                            class="p-4 sm:p-5 bg-amber-50/70 rounded-2xl border border-amber-200/90 space-y-4 shadow-2xs"
                        >
                            <span class="text-xs font-black text-amber-950 block">
                                Nilai AKG Khusus Varian Alergi: {{ selectedAlergiTab }}
                            </span>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- 1. PK Alergi (First on the left) -->
                                <div class="bg-white p-4 rounded-2xl border border-amber-200 shadow-2xs space-y-3">
                                    <div class="flex items-center justify-between pb-1 border-b border-amber-100 gap-2">
                                        <span class="text-xs font-black text-amber-900 block flex items-center gap-1.5">
                                            <ShieldAlert class="h-4 w-4 text-amber-600" />
                                            <span>Porsi Kecil (PK) Alergi &bull; {{ selectedAlergiTab }}</span>
                                        </span>
                                        <button
                                            type="button"
                                            @click="handleQuickPasteGizi(ensureAkgAlergi(selectedAlergiTab).akg_pk, `PK Alergi (${selectedAlergiTab})`)"
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-900 text-[10.5px] font-extrabold border border-amber-300 transition-all cursor-pointer shadow-2xs shrink-0"
                                            title="Tempel data gizi dari Excel atau teks"
                                        >
                                            <ClipboardPaste class="h-3 w-3 shrink-0" />
                                            <span>Tempel Gizi</span>
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🔥 Energi</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pk.energi" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pk, 'energi')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-9 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">kkal</span>
                                            </div>
                                        </div>
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🥩 Protein</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pk.protein" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pk, 'protein')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-6 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">g</span>
                                            </div>
                                        </div>
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🥑 Lemak</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pk.lemak" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pk, 'lemak')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-6 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">g</span>
                                            </div>
                                        </div>
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🍚 Karbohidrat</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pk.karbohidrat" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pk, 'karbohidrat')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-6 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">g</span>
                                            </div>
                                        </div>
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🥗 Serat</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pk.serat" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pk, 'serat')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-6 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">g</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. PB Alergi (Second on the right) -->
                                <div class="bg-white p-4 rounded-2xl border border-amber-200 shadow-2xs space-y-3">
                                    <div class="flex items-center justify-between pb-1 border-b border-amber-100 gap-2">
                                        <span class="text-xs font-black text-amber-900 block flex items-center gap-1.5">
                                            <ShieldAlert class="h-4 w-4 text-amber-600" />
                                            <span>Porsi Besar (PB) Alergi &bull; {{ selectedAlergiTab }}</span>
                                        </span>
                                        <button
                                            type="button"
                                            @click="handleQuickPasteGizi(ensureAkgAlergi(selectedAlergiTab).akg_pb, `PB Alergi (${selectedAlergiTab})`)"
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-900 text-[10.5px] font-extrabold border border-amber-300 transition-all cursor-pointer shadow-2xs shrink-0"
                                            title="Tempel data gizi dari Excel atau teks"
                                        >
                                            <ClipboardPaste class="h-3 w-3 shrink-0" />
                                            <span>Tempel Gizi</span>
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🔥 Energi</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pb.energi" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pb, 'energi')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-9 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">kkal</span>
                                            </div>
                                        </div>
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🥩 Protein</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pb.protein" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pb, 'protein')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-6 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">g</span>
                                            </div>
                                        </div>
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🥑 Lemak</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pb.lemak" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pb, 'lemak')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-6 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">g</span>
                                            </div>
                                        </div>
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🍚 Karbohidrat</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pb.karbohidrat" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pb, 'karbohidrat')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-6 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">g</span>
                                            </div>
                                        </div>
                                        <div class="p-2 bg-slate-50/80 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                            <span class="text-xs font-bold text-slate-700">🥗 Serat</span>
                                            <div class="relative w-28 shrink-0">
                                                <input v-model.number="ensureAkgAlergi(selectedAlergiTab).akg_pb.serat" @paste="onPasteGiziInput($event, ensureAkgAlergi(selectedAlergiTab).akg_pb, 'serat')" type="number" step="0.1" placeholder="0" class="w-full pl-2 pr-6 py-1 bg-white border border-slate-300 rounded-lg text-right text-xs font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">g</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. CATATAN KERJA / RESEP -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <FileText class="h-4 w-4 text-slate-500" />
                        <span>Catatan Resep / Instruksi Juru Masak (Opsional)</span>
                    </label>
                    <textarea
                        v-model="catatan"
                        rows="3"
                        placeholder="Tambahkan catatan khusus pengolahan bahan atau instruksi untuk tim juru masak..."
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-800 outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 transition-all shadow-2xs"
                    ></textarea>
                </div>
            </div>

            <!-- Action Bar Footer (Hadir di kedua mode, sticky atau clean) -->
            <div
                :class="[
                    'flex items-center justify-between gap-4 flex-wrap',
                    isEmbedded
                        ? 'px-6 py-4 bg-slate-50 border-t border-slate-200'
                        : 'pt-4 border-t border-slate-200'
                ]"
            >
                <div class="text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5 text-amber-800 font-semibold">
                        <Info class="h-3.5 w-3.5 text-amber-600" />
                        <span>Mode Manual: Data gizi dan alergi disimpan langsung tanpa formulasi bahan di Rancang Menu.</span>
                    </span>
                </div>

                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                    <button
                        type="button"
                        @click="handleRequestClose"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer border border-slate-200"
                    >
                        Tutup
                    </button>

                    <button
                        type="button"
                        @click="handleSave()"
                        :disabled="isSubmitting"
                        class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black transition-all flex items-center gap-2 cursor-pointer shadow-xs disabled:opacity-50"
                    >
                        <Save class="h-4 w-4" />
                        <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Data Manual' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL KONFIRMASI TINGGALKAN EDITOR (PERUBAHAN BELUM DISIMPAN)             -->
        <!-- ========================================================================= -->
        <Modal :show="showLeaveConfirmModal" @close="handleCancelLeave" max-width="md">
            <div class="p-5 sm:p-6 space-y-4">
                <div class="flex items-start gap-3.5">
                    <div class="h-11 w-11 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200 shadow-2xs">
                        <AlertTriangle class="h-6 w-6 stroke-[2.2]" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-black text-slate-900 leading-snug">
                            Perubahan Belum Disimpan
                        </h3>
                        <p class="text-xs font-semibold text-slate-500">
                            Konfirmasi Menutup Editor Work Order Manual
                        </p>
                    </div>
                </div>

                <div class="bg-amber-50/80 border border-amber-200/90 rounded-2xl p-4 space-y-2 text-xs text-amber-950 leading-relaxed shadow-2xs">
                    <p class="font-bold">
                        Anda telah melakukan perubahan pada menu atau nilai gizi manual ini yang belum disimpan.
                    </p>
                    <p class="text-slate-600 text-[11.5px]">
                        Jika Anda menutup editor atau memuat ulang halaman sekarang, seluruh data perubahan yang belum disimpan akan <strong>hilang secara permanen</strong>.
                    </p>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-[11px] text-slate-600 flex items-center gap-2">
                    <Lightbulb class="h-4 w-4 text-amber-500 shrink-0" />
                    <span>Silakan klik <strong>Simpan Data Manual</strong> jika ingin menyimpan perubahan.</span>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-3 border-t border-slate-100">
                    <button
                        type="button"
                        @click="handleCancelLeave"
                        class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 rounded-xl transition cursor-pointer text-center"
                    >
                        Tetap di Editor
                    </button>
                    <button
                        type="button"
                        @click="handleConfirmLeave"
                        class="w-full sm:w-auto px-4 py-2.5 text-xs font-black text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition cursor-pointer shadow-xs text-center"
                    >
                        Ya, Tinggalkan Tanpa Menyimpan
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal Tempel (Paste) List Sub Menu -->
        <Modal
            :show="showModalPasteSubMenu"
            @close="showModalPasteSubMenu = false"
            max-width="md"
        >
            <div class="p-5 sm:p-6 space-y-4">
                <div class="flex items-start gap-3.5">
                    <div
                        class="h-11 w-11 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200 shadow-2xs"
                    >
                        <ClipboardPaste class="h-6 w-6 stroke-[2.2]" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-black text-slate-900 leading-snug">
                            Tempel List Sub Menu
                        </h3>
                        <p class="text-xs font-semibold text-slate-500">
                            Salin list dari Excel, Word, Notepad, atau WA dan tempel di sini.
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
                        class="w-full text-xs font-medium rounded-xl border border-slate-300 p-3 text-slate-800 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all resize-none shadow-2xs font-mono"
                    ></textarea>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-[11px] text-slate-600 space-y-1">
                        <p class="font-bold text-slate-800 flex items-center gap-1.5">
                            <Lightbulb class="h-3.5 w-3.5 text-amber-500 shrink-0" />
                            <span>Cara Kerja Cerdas:</span>
                        </p>
                        <ul class="list-disc list-inside space-y-0.5 text-slate-600 pl-1 text-[10.5px]">
                            <li>Mendukung salin dari sel Excel (vertikal & horizontal).</li>
                            <li>Nomor otomatis (1., 2.), strip (-), dan simbol bullet (•) akan dibersihkan.</li>
                            <li>Sub menu tambahan otomatis bertambah jika list lebih dari slot yang ada.</li>
                            <li>Anda juga bisa langsung <span class="font-bold text-slate-800">Ctrl + V</span> di input Sub Menu mana pun!</li>
                        </ul>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
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
                        class="px-4 py-2.5 rounded-xl text-xs font-black bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs transition-all cursor-pointer flex items-center gap-1.5"
                    >
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
    </div>
</template>
