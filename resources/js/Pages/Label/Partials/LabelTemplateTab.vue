<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from "vue";
import {
    bgnStandardElements,
    getDefaultTemplatePresets,
    availableLogos,
} from "../labelPresets.js";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import {
    Layout,
    Plus,
    Copy,
    Trash2,
    CheckCircle2,
    RotateCcw,
    Undo2,
    Redo2,
    Sliders,
    Eye,
    EyeOff,
    Palette,
    Type,
    Sparkles,
    Check,
    Save,
    Maximize2,
    ShieldAlert,
    Flame,
    Utensils,
    Calendar,
    Clock,
    Tag,
    Move,
    Layers,
    Lock,
    Unlock,
    AlignLeft,
    AlignCenter,
    AlignRight,
    ArrowUp,
    ArrowDown,
    ZoomIn,
    ZoomOut,
    CornerDownLeft,
    Square,
    MapPin,
    Hourglass,
    AlertTriangle,
} from "lucide-vue-next";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: null,
    },
    templates: {
        type: Array,
        required: true,
    },
    activeTemplateId: {
        type: String,
        required: true,
    },
});

const emit = defineEmits([
    "update:templates",
    "update:activeTemplateId",
    "save-templates",
]);

// Currently selected template for editing in the Canva-like editor
const selectedTemplateId = ref(
    props.activeTemplateId || props.templates[0]?.id || "bgn_standard_4_3",
);

const currentTemplate = computed(() => {
    return (
        props.templates.find((t) => t.id === selectedTemplateId.value) ||
        props.templates[0] ||
        {}
    );
});

// Left Sidebar Tab: 'tambah' | 'layer' | 'kanvas' | 'template'
const activeSidebarTab = ref("tambah");

// Selected element on canvas
const selectedElementId = ref(null);

const selectedElement = computed(() => {
    if (!selectedElementId.value || !currentTemplate.value.elements)
        return null;
    return (
        currentTemplate.value.elements.find(
            (el) => el.id === selectedElementId.value,
        ) || null
    );
});

// Canvas Container Reference for calculating relative % on drag/resize
const canvasRef = ref(null);

// Drag & Resize state
const isDragging = ref(false);
const isResizing = ref(false);
const resizeHandle = ref(null); // 'se', 'sw', 'ne', 'nw', 'e', 'w', 's', 'n'
const startMouseX = ref(0);
const startMouseY = ref(0);
const startElementX = ref(0);
const startElementY = ref(0);
const startElementW = ref(0);
const startElementH = ref(0);

// Notification message
const saveSuccessMessage = ref("");

function showNotification(msg) {
    saveSuccessMessage.value = msg;
    setTimeout(() => {
        saveSuccessMessage.value = "";
    }, 3500);
}

// History Stack for Undo / Redo
const historyStack = ref([]);
const historyIndex = ref(-1);
const isHistoryNavigating = ref(false);

function recordHistoryState() {
    if (isHistoryNavigating.value || !currentTemplate.value) return;
    const currentState = JSON.stringify(currentTemplate.value.elements || []);

    if (
        historyIndex.value >= 0 &&
        historyStack.value[historyIndex.value] === currentState
    ) {
        return;
    }

    // Truncate future branches if user performed action after undo
    if (historyIndex.value < historyStack.value.length - 1) {
        historyStack.value = historyStack.value.slice(0, historyIndex.value + 1);
    }

    historyStack.value.push(currentState);
    if (historyStack.value.length > 50) {
        historyStack.value.shift();
    }
    historyIndex.value = historyStack.value.length - 1;
}

const canUndo = computed(() => historyIndex.value > 0);
const canRedo = computed(
    () => historyIndex.value < historyStack.value.length - 1,
);

function undo() {
    if (!canUndo.value) return;
    historyIndex.value--;
    isHistoryNavigating.value = true;
    try {
        currentTemplate.value.elements = JSON.parse(
            historyStack.value[historyIndex.value],
        );
        selectedElementId.value = null;
        emit("save-templates");
        showNotification("↩️ Undo: Perubahan dikembalikan");
    } finally {
        setTimeout(() => {
            isHistoryNavigating.value = false;
        }, 50);
    }
}

function redo() {
    if (!canRedo.value) return;
    historyIndex.value++;
    isHistoryNavigating.value = true;
    try {
        currentTemplate.value.elements = JSON.parse(
            historyStack.value[historyIndex.value],
        );
        selectedElementId.value = null;
        emit("save-templates");
        showNotification("↪️ Redo: Perubahan diterapkan ulang");
    } finally {
        setTimeout(() => {
            isHistoryNavigating.value = false;
        }, 50);
    }
}

function manualSaveTemplate() {
    recordHistoryState();
    emit("save-templates");
    showNotification(`💾 Template "${currentTemplate.value.name}" berhasil disimpan!`);
}

function handleGlobalKeydown(e) {
    if (["INPUT", "TEXTAREA", "SELECT"].includes(e.target?.tagName)) return;
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "z") {
        e.preventDefault();
        if (e.shiftKey) {
            redo();
        } else {
            undo();
        }
    } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "y") {
        e.preventDefault();
        redo();
    } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") {
        e.preventDefault();
        manualSaveTemplate();
    }
}

// Formatters for preview
function formatDateSlash(dateStr) {
    if (!dateStr) return "-";
    try {
        const parts = String(dateStr).split("-");
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        const d = new Date(dateStr);
        const day = String(d.getDate()).padStart(2, "0");
        const month = String(d.getMonth() + 1).padStart(2, "0");
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    } catch {
        return dateStr;
    }
}

function formatJam(val) {
    if (!val) return "-";
    return String(val).replace(":", ".");
}

const dummyTanggal = new Date().toISOString().substring(0, 10);
const dummyJam = "07:00";
const dummyBatas = "09:00";
const dummyMenu =
    "Nasi Putih - Dori Finger with Yellow Mayonaise - Steam Tahu - Buncis & Jagung Manis - Buah Pepaya";
const dummyKelompokNama = "RA Baitul Mutaallim";
const dummyGizi = {
    energi_pk: "386.3",
    energi_pb: "547.3",
    karbo_pk: "50.9",
    karbo_pb: "80",
    protein_pk: "18.3",
    protein_pb: "21.6",
    lemak_pk: "13",
    lemak_pb: "17.2",
    serat_pk: "3.6",
    serat_pb: "6.4",
};

// Selection Handling
function selectElement(id, event) {
    if (event) event.stopPropagation();
    selectedElementId.value = id;
}

function deselectElement(event) {
    if (event && event.target === canvasRef.value) {
        selectedElementId.value = null;
    }
}

// Dragging Element
function startDragElement(el, event) {
    if (el.isLocked) return;
    event.stopPropagation();
    selectedElementId.value = el.id;
    isDragging.value = true;
    isResizing.value = false;

    startMouseX.value = event.clientX;
    startMouseY.value = event.clientY;
    startElementX.value = el.x;
    startElementY.value = el.y;

    window.addEventListener("mousemove", onMouseMove);
    window.addEventListener("mouseup", onMouseUp);
}

// Resizing Element
function startResize(handle, el, event) {
    if (el.isLocked) return;
    event.stopPropagation();
    selectedElementId.value = el.id;
    isResizing.value = true;
    isDragging.value = false;
    resizeHandle.value = handle;

    startMouseX.value = event.clientX;
    startMouseY.value = event.clientY;
    startElementX.value = el.x;
    startElementY.value = el.y;
    startElementW.value = el.width;
    startElementH.value = el.height;

    window.addEventListener("mousemove", onMouseMove);
    window.addEventListener("mouseup", onMouseUp);
}

function onMouseMove(event) {
    if (!canvasRef.value || !selectedElement.value) return;

    const rect = canvasRef.value.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) return;

    const deltaX = ((event.clientX - startMouseX.value) / rect.width) * 100;
    const deltaY = ((event.clientY - startMouseY.value) / rect.height) * 100;

    const el = selectedElement.value;

    if (isDragging.value) {
        let newX = Math.round((startElementX.value + deltaX) * 10) / 10;
        let newY = Math.round((startElementY.value + deltaY) * 10) / 10;

        // Clamping to canvas bounds
        newX = Math.max(0, Math.min(100 - el.width, newX));
        newY = Math.max(0, Math.min(100 - el.height, newY));

        el.x = newX;
        el.y = newY;
    } else if (isResizing.value) {
        const handle = resizeHandle.value;

        if (handle.includes("e")) {
            let newW = Math.round((startElementW.value + deltaX) * 10) / 10;
            newW = Math.max(4, Math.min(100 - el.x, newW));
            el.width = newW;
        }
        if (handle.includes("s")) {
            let newH = Math.round((startElementH.value + deltaY) * 10) / 10;
            newH = Math.max(2, Math.min(100 - el.y, newH));
            el.height = newH;
        }
        if (handle.includes("w")) {
            let newW = Math.round((startElementW.value - deltaX) * 10) / 10;
            let newX = Math.round((startElementX.value + deltaX) * 10) / 10;
            if (newW >= 4 && newX >= 0) {
                el.width = newW;
                el.x = newX;
            }
        }
        if (handle.includes("n")) {
            let newH = Math.round((startElementH.value - deltaY) * 10) / 10;
            let newY = Math.round((startElementY.value + deltaY) * 10) / 10;
            if (newH >= 2 && newY >= 0) {
                el.height = newH;
                el.y = newY;
            }
        }
    }
}

function onMouseUp() {
    isDragging.value = false;
    isResizing.value = false;
    resizeHandle.value = null;
    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
    recordHistoryState();
    emit("save-templates");
}

onMounted(() => {
    window.addEventListener("keydown", handleGlobalKeydown);
    recordHistoryState();
});

onUnmounted(() => {
    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
    window.removeEventListener("keydown", handleGlobalKeydown);
});

watch(
    () => selectedTemplateId.value,
    () => {
        historyStack.value = [];
        historyIndex.value = -1;
        recordHistoryState();
    },
);

// Element Actions
function addElement(type) {
    if (!currentTemplate.value.elements) {
        currentTemplate.value.elements = [];
    }

    const id = `el_${type}_${Date.now()}`;
    let newEl = {
        id,
        type,
        label: "Elemen Baru",
        x: 10,
        y: 10,
        width: 30,
        height: 10,
        zIndex: currentTemplate.value.elements.length + 1,
        visible: true,
        isLocked: false,
    };

    switch (type) {
        case "text":
            newEl = {
                ...newEl,
                label: "Teks Bebas",
                text: "Ketik teks di sini...",
                fontSize: 12,
                fontWeight: "bold",
                color: "#1E293B",
                textAlign: "left",
            };
            break;
        case "badge":
            newEl = {
                ...newEl,
                label: "Badge Judul",
                text: "LABEL MAKANAN BERGIZI GRATIS",
                backgroundColor: "#4E88C7",
                color: "#FFFFFF",
                fontSize: 11,
                fontWeight: "black",
                borderRadius: 8,
                width: 44,
                height: 7,
            };
            break;
        case "tanggal":
            newEl = {
                ...newEl,
                label: "Tanggal Produksi",
                width: 44,
                height: 14,
            };
            break;
        case "jam":
            newEl = {
                ...newEl,
                label: "Jam Produksi",
                width: 21,
                height: 14,
            };
            break;
        case "batas":
            newEl = {
                ...newEl,
                label: "Batas Konsumsi",
                width: 21,
                height: 14,
            };
            break;
        case "tujuan":
            newEl = {
                ...newEl,
                label: "Tujuan Pengantaran",
                width: 44,
                height: 16,
            };
            break;
        case "menu":
            newEl = {
                ...newEl,
                label: "Kotak Menu",
                backgroundColor: "#4E88C7",
                width: 44,
                height: 14,
                borderRadius: 12,
            };
            break;
        case "nutrition_table":
            newEl = {
                ...newEl,
                label: "Tabel Kandungan Gizi",
                width: 44,
                height: 41,
            };
            break;
        case "warning":
            newEl = {
                ...newEl,
                label: "Banner Peringatan",
                width: 92,
                height: 18,
                text: "MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT.",
                subtitle: "DILARANG MEMBAWA PULANG!",
            };
            break;
        case "logo":
            newEl = {
                ...newEl,
                label: "Logo BGN",
                width: 40,
                height: 11,
            };
            break;
        case "sppg_header":
            newEl = {
                ...newEl,
                label: "Header Unit SPPG",
                width: 48,
                height: 11,
                textAlign: "right",
            };
            break;
        case "divider":
            newEl = {
                ...newEl,
                label: "Garis Pembatas",
                backgroundColor: "#C5921D",
                width: 92,
                height: 1,
            };
            break;
    }

    currentTemplate.value.elements.push(newEl);
    selectedElementId.value = id;
    recordHistoryState();
    emit("save-templates");
    showNotification(`Elemen "${newEl.label}" berhasil ditambahkan ke kanvas.`);
}

function setLogoImage(logoUrl) {
    if (!currentTemplate.value.elements) {
        currentTemplate.value.elements = [];
    }
    let logoEl = currentTemplate.value.elements.find((e) => e.type === "logo");
    if (!logoEl) {
        addElement("logo");
        logoEl = currentTemplate.value.elements.find((e) => e.type === "logo");
    }
    if (logoEl) {
        logoEl.imageUrl = logoUrl;
        selectedElementId.value = logoEl.id;
        recordHistoryState();
        emit("save-templates");
        showNotification("Logo BGN berhasil diterapkan ke kanvas!");
    }
}

function deleteSelectedElement() {
    if (!selectedElement.value) return;
    const idx = currentTemplate.value.elements.findIndex(
        (e) => e.id === selectedElement.value.id,
    );
    if (idx > -1) {
        currentTemplate.value.elements.splice(idx, 1);
        selectedElementId.value = null;
        recordHistoryState();
        emit("save-templates");
        showNotification("Elemen berhasil dihapus dari kanvas.");
    }
}

function duplicateSelectedElement() {
    if (!selectedElement.value) return;
    const newId = `el_copy_${Date.now()}`;
    const copy = {
        ...JSON.parse(JSON.stringify(selectedElement.value)),
        id: newId,
        label: `${selectedElement.value.label} (Copy)`,
        x: Math.min(80, selectedElement.value.x + 3),
        y: Math.min(80, selectedElement.value.y + 3),
        zIndex: currentTemplate.value.elements.length + 1,
    };
    currentTemplate.value.elements.push(copy);
    selectedElementId.value = newId;
    recordHistoryState();
    emit("save-templates");
    showNotification("Elemen berhasil diduplikasi.");
}

function bringForward() {
    if (!selectedElement.value) return;
    selectedElement.value.zIndex = (selectedElement.value.zIndex || 1) + 1;
    recordHistoryState();
    emit("save-templates");
}

function sendBackward() {
    if (!selectedElement.value) return;
    selectedElement.value.zIndex = Math.max(
        1,
        (selectedElement.value.zIndex || 1) - 1,
    );
    recordHistoryState();
    emit("save-templates");
}

function handleImageUpload(e) {
    const file = e.target.files?.[0];
    if (file && selectedElement.value) {
        const reader = new FileReader();
        reader.onload = (event) => {
            selectedElement.value.imageUrl = event.target.result;
            recordHistoryState();
            emit("save-templates");
            showNotification("Gambar logo berhasil dimuat ke kanvas!");
        };
        reader.readAsDataURL(file);
    }
}

// Template Actions
function markAsActiveTemplate(templateId) {
    emit("update:activeTemplateId", templateId);
    showNotification(
        `Template "${currentTemplate.value.name}" aktif digunakan untuk cetak label!`,
    );
}

function createNewTemplate() {
    const newId = `custom_template_${Date.now()}`;
    const newTemplate = {
        id: newId,
        name: `Desain Kustom #${props.templates.length + 1}`,
        description: "Template kanvas kustom kosong buatan pengguna",
        is_default: false,
        aspect_ratio: "4/3",
        border_width: "3px",
        border_color: "#1E4B8B",
        border_radius: "1rem",
        canvas_bg: "#FFFFFF",
        elements: [], // Kanvas kosong untuk kustomisasi dari awal
    };

    const updated = [...props.templates, newTemplate];
    emit("update:templates", updated);
    selectedTemplateId.value = newId;
    selectedElementId.value = null;
    activeSidebarTab.value = "tambah";
    emit("save-templates");
    recordHistoryState();
    showNotification("✨ Template kanvas baru (kosong) siap didesain!");
}

function duplicateCurrentTemplate() {
    const newId = `template_copy_${Date.now()}`;
    const copy = {
        ...JSON.parse(JSON.stringify(currentTemplate.value)),
        id: newId,
        name: `${currentTemplate.value.name} (Salinan)`,
        is_default: false,
    };

    const updated = [...props.templates, copy];
    emit("update:templates", updated);
    selectedTemplateId.value = newId;
    emit("save-templates");
    recordHistoryState();
    showNotification("Template berhasil diduplikasi!");
}

function deleteCurrentTemplate() {
    if (currentTemplate.value.is_default) {
        alert("Template standar bawaan sistem tidak dapat dihapus.");
        return;
    }

    if (confirm(`Hapus template "${currentTemplate.value.name}"?`)) {
        const updated = props.templates.filter(
            (t) => t.id !== selectedTemplateId.value,
        );
        emit("update:templates", updated);
        if (props.activeTemplateId === selectedTemplateId.value) {
            emit(
                "update:activeTemplateId",
                updated[0]?.id || "bgn_standard_4_3",
            );
        }
        selectedTemplateId.value = updated[0]?.id || "bgn_standard_4_3";
        emit("save-templates");
        recordHistoryState();
        showNotification("Template berhasil dihapus.");
    }
}

function resetToDefaultBgn() {
    if (
        confirm(
            "Kembalikan tata letak template ini ke format Standar Resmi BGN (4:3)?",
        )
    ) {
        const pristinePresets = getDefaultTemplatePresets();
        const pristineMatching = pristinePresets.find(
            (p) => p.id === currentTemplate.value.id,
        );

        if (pristineMatching) {
            currentTemplate.value.aspect_ratio = pristineMatching.aspect_ratio;
            currentTemplate.value.border_width = pristineMatching.border_width;
            currentTemplate.value.border_color = pristineMatching.border_color;
            currentTemplate.value.border_radius =
                pristineMatching.border_radius;
            currentTemplate.value.canvas_bg = pristineMatching.canvas_bg;
            currentTemplate.value.elements = JSON.parse(
                JSON.stringify(pristineMatching.elements),
            );
        } else {
            currentTemplate.value.aspect_ratio = "4/3";
            currentTemplate.value.border_width = "3px";
            currentTemplate.value.border_color = "#1E4B8B";
            currentTemplate.value.border_radius = "1rem";
            currentTemplate.value.canvas_bg = "#FFFFFF";
            currentTemplate.value.elements = JSON.parse(
                JSON.stringify(bgnStandardElements),
            );
        }

        selectedElementId.value = null;
        recordHistoryState();
        emit("save-templates");
        showNotification(
            "Tata letak kanvas berhasil direset ke standar resmi BGN.",
        );
    }
}

function restoreAllDefaultPresets() {
    if (
        confirm(
            "Pulihkan semua template preset bawaan ke format standar awal? (Template kustom Anda tetap aman)",
        )
    ) {
        const pristinePresets = getDefaultTemplatePresets();
        // Keep any non-default custom templates
        const customTemplates = props.templates.filter((t) => !t.is_default);
        const restored = [...pristinePresets, ...customTemplates];
        emit("update:templates", restored);
        selectedElementId.value = null;
        emit("save-templates");
        showNotification("Semua preset standar BGN berhasil dipulihkan.");
    }
}
</script>

<template>
    <div class="space-y-4">
        <!-- Toast Notifikasi Sukses -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 -translate-y-2"
            enter-to-class="transform opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="transform opacity-100 translate-y-0"
            leave-to-class="transform opacity-0 -translate-y-2"
        >
            <div
                v-if="saveSuccessMessage"
                class="fixed top-20 right-6 z-50 bg-emerald-700 text-white px-4 py-2.5 rounded-xl shadow-xl flex items-center gap-2 text-xs font-bold"
            >
                <CheckCircle2 class="h-4 w-4 text-emerald-200" />
                <span>{{ saveSuccessMessage }}</span>
            </div>
        </transition>

        <!-- Canva Main App Header Bar -->
        <div
            class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3"
        >
            <div class="flex items-center gap-3">
                <div
                    class="h-9 w-9 rounded-xl bg-primary text-white flex items-center justify-center shadow-xs shrink-0 font-black"
                >
                    🎨
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input
                            type="text"
                            v-model="currentTemplate.name"
                            @input="emit('save-templates')"
                            class="font-black text-slate-900 text-sm sm:text-base border-b border-transparent hover:border-slate-300 focus:border-primary focus:ring-0 bg-transparent px-1 py-0.5 rounded cursor-text"
                            title="Klik untuk mengubah nama template"
                        />
                        <span
                            v-if="currentTemplate.id === activeTemplateId"
                            class="bg-emerald-100 text-emerald-800 text-[10.5px] font-black px-2.5 py-0.5 rounded-full border border-emerald-300 flex items-center gap-1"
                        >
                            <Check class="h-3 w-3" />
                            <span>Sedang Digunakan Untuk Cetak</span>
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Canva Interactive Visual Editor: Geser, ubah ukuran, dan
                        tata elemen stiker secara bebas.
                    </p>
                </div>
            </div>

            <!-- Action Controls -->
            <div
                class="flex items-center gap-2 flex-wrap self-end md:self-auto"
            >
                <Button
                    type="button"
                    @click="resetToDefaultBgn"
                    className="h-8 px-2.5 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 font-bold text-xs flex items-center gap-1.5 shadow-2xs cursor-pointer"
                    title="Reset ke layout standar BGN"
                >
                    <RotateCcw class="h-3.5 w-3.5" />
                    <span>Reset Standar</span>
                </Button>

                <Button
                    type="button"
                    @click="markAsActiveTemplate(currentTemplate.id)"
                    :disabled="currentTemplate.id === activeTemplateId"
                    :className="[
                        'h-8 px-3 text-xs font-bold flex items-center gap-1.5 shadow-2xs cursor-pointer',
                        currentTemplate.id === activeTemplateId
                            ? 'bg-emerald-600 text-white cursor-default opacity-90'
                            : 'bg-primary hover:bg-primary/90 text-white',
                    ]"
                >
                    <Check class="h-3.5 w-3.5" />
                    <span>{{
                        currentTemplate.id === activeTemplateId
                            ? "Template Digunakan"
                            : "Gunakan Template Ini"
                    }}</span>
                </Button>
            </div>
        </div>

        <!-- Canva Workspace Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
            <!-- 1. Left Canva Tools Sidebar (4 Kolom) -->
            <div class="lg:col-span-4 space-y-3">
                <div
                    class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden"
                >
                    <!-- Sidebar Tab Switcher (Canva Style) -->
                    <div
                        class="grid grid-cols-4 p-1.5 bg-slate-100/90 border-b border-slate-200 text-xs font-bold gap-1"
                    >
                        <button
                            type="button"
                            @click="activeSidebarTab = 'tambah'"
                            :class="[
                                'py-2 rounded-xl transition-all cursor-pointer flex flex-col items-center gap-1 text-[11px]',
                                activeSidebarTab === 'tambah'
                                    ? 'bg-white text-primary shadow-2xs font-extrabold'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            <Plus class="h-4 w-4" />
                            <span>Elemen</span>
                        </button>
                        <button
                            type="button"
                            @click="activeSidebarTab = 'layer'"
                            :class="[
                                'py-2 rounded-xl transition-all cursor-pointer flex flex-col items-center gap-1 text-[11px]',
                                activeSidebarTab === 'layer'
                                    ? 'bg-white text-primary shadow-2xs font-extrabold'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            <Layers class="h-4 w-4" />
                            <span
                                >Layer ({{
                                    currentTemplate.elements?.length || 0
                                }})</span
                            >
                        </button>
                        <button
                            type="button"
                            @click="activeSidebarTab = 'kanvas'"
                            :class="[
                                'py-2 rounded-xl transition-all cursor-pointer flex flex-col items-center gap-1 text-[11px]',
                                activeSidebarTab === 'kanvas'
                                    ? 'bg-white text-primary shadow-2xs font-extrabold'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            <Sliders class="h-4 w-4" />
                            <span>Kanvas</span>
                        </button>
                        <button
                            type="button"
                            @click="activeSidebarTab = 'template'"
                            :class="[
                                'py-2 rounded-xl transition-all cursor-pointer flex flex-col items-center gap-1 text-[11px]',
                                activeSidebarTab === 'template'
                                    ? 'bg-white text-primary shadow-2xs font-extrabold'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            <Layout class="h-4 w-4" />
                            <span>Template</span>
                        </button>
                    </div>

                    <!-- TAB 1: TAMBAH ELEMEN (Canva Elements Drawer) -->
                    <div
                        v-if="activeSidebarTab === 'tambah'"
                        class="p-4 space-y-3 max-h-[580px] overflow-y-auto"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700"
                                >Klik komponen untuk menambahkan:</span
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button
                                type="button"
                                @click="addElement('text')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <Type
                                    class="h-4 w-4 text-primary group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Teks Bebas</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('badge')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <Tag
                                    class="h-4 w-4 text-blue-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Badge Judul</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('logo')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <Sparkles
                                    class="h-4 w-4 text-amber-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Logo BGN</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('sppg_header')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <Layout
                                    class="h-4 w-4 text-indigo-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Header SPPG</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('tanggal')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <Calendar
                                    class="h-4 w-4 text-emerald-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Tgl Produksi</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('jam')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <Clock
                                    class="h-4 w-4 text-blue-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Jam Produksi</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('batas')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <Hourglass
                                    class="h-4 w-4 text-amber-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Batas Waktu</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('tujuan')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <MapPin
                                    class="h-4 w-4 text-rose-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Nama Sasaran</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('menu')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <Utensils
                                    class="h-4 w-4 text-teal-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Kotak Menu</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('nutrition_table')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group"
                            >
                                <Flame
                                    class="h-4 w-4 text-orange-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Tabel Gizi</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('warning')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group col-span-2"
                            >
                                <AlertTriangle
                                    class="h-4 w-4 text-red-600 group-hover:scale-110 transition-transform"
                                />
                                <span class="font-bold text-slate-800"
                                    >Banner Peringatan BGN</span
                                >
                            </button>

                            <button
                                type="button"
                                @click="addElement('divider')"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-primary/40 hover:bg-primary/5 transition-all text-left flex items-center gap-2 cursor-pointer group col-span-2"
                            >
                                <div class="h-1 w-6 bg-amber-500 rounded"></div>
                                <span class="font-bold text-slate-800"
                                    >Garis Pembatas (Divider)</span
                                >
                            </button>
                        </div>

                        <!-- Galeri Logo Resmi BGN -->
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <Sparkles class="h-3.5 w-3.5 text-amber-600" />
                                    <span>Pilih Varian Logo Resmi BGN:</span>
                                </span>
                            </div>
                            <p class="text-[10.5px] text-slate-500">
                                Klik logo di bawah untuk langsung menerapkannya pada kanvas:
                            </p>

                            <div class="grid grid-cols-1 gap-2">
                                <div
                                    v-for="l in availableLogos"
                                    :key="l.id"
                                    @click="setLogoImage(l.url)"
                                    class="p-2 rounded-xl border border-slate-200 hover:border-primary/50 hover:bg-primary/5 flex items-center gap-2.5 transition-all cursor-pointer group"
                                >
                                    <div class="h-8 w-12 bg-slate-100 rounded-lg flex items-center justify-center p-1 shrink-0 border border-slate-200 overflow-hidden">
                                        <img
                                            :src="l.url"
                                            :alt="l.name"
                                            class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform"
                                        />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-extrabold text-[11px] text-slate-900 truncate">
                                            {{ l.name }}
                                        </p>
                                        <p class="text-[9.5px] text-slate-500 truncate">
                                            {{ l.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: DAFTAR LAYER (Layers Management) -->
                    <div
                        v-else-if="activeSidebarTab === 'layer'"
                        class="p-4 space-y-2 max-h-[580px] overflow-y-auto"
                    >
                        <div
                            class="flex items-center justify-between text-xs font-bold text-slate-600 mb-2"
                        >
                            <span>Daftar Objek Kanvas:</span>
                            <span
                                >{{
                                    currentTemplate.elements?.length || 0
                                }}
                                Layer</span
                            >
                        </div>

                        <div
                            v-for="el in currentTemplate.elements || []"
                            :key="el.id"
                            @click="selectElement(el.id)"
                            :class="[
                                'p-2 rounded-xl border flex items-center justify-between text-xs transition-all cursor-pointer',
                                selectedElementId === el.id
                                    ? 'bg-primary/10 border-primary font-extrabold text-primary shadow-2xs'
                                    : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold',
                            ]"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <span
                                    class="text-[10px] font-mono font-bold bg-white px-1.5 py-0.5 rounded border border-slate-200 shrink-0"
                                >
                                    {{ el.type }}
                                </span>
                                <span class="truncate">{{
                                    el.label || el.text || el.id
                                }}</span>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    @click.stop="
                                        el.visible =
                                            el.visible === false ? true : false;
                                        emit('save-templates');
                                    "
                                    class="p-1 hover:bg-slate-200 rounded text-slate-500"
                                    :title="
                                        el.visible !== false
                                            ? 'Sembunyikan'
                                            : 'Tampilkan'
                                    "
                                >
                                    <Eye
                                        v-if="el.visible !== false"
                                        class="h-3.5 w-3.5"
                                    />
                                    <EyeOff
                                        v-else
                                        class="h-3.5 w-3.5 text-slate-400"
                                    />
                                </button>
                                <button
                                    type="button"
                                    @click.stop="
                                        el.isLocked = !el.isLocked;
                                        emit('save-templates');
                                    "
                                    class="p-1 hover:bg-slate-200 rounded text-slate-500"
                                    :title="
                                        el.isLocked
                                            ? 'Buka Kunci'
                                            : 'Kunci Posisi'
                                    "
                                >
                                    <Lock
                                        v-if="el.isLocked"
                                        class="h-3.5 w-3.5 text-amber-600"
                                    />
                                    <Unlock
                                        v-else
                                        class="h-3.5 w-3.5 text-slate-400"
                                    />
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="
                                !currentTemplate.elements ||
                                currentTemplate.elements.length === 0
                            "
                            class="text-center py-6 text-slate-400 text-xs font-semibold"
                        >
                            Belum ada elemen di kanvas.
                        </div>
                    </div>

                    <!-- TAB 3: PENGATURAN KANVAS -->
                    <div
                        v-else-if="activeSidebarTab === 'kanvas'"
                        class="p-4 space-y-4 max-h-[580px] overflow-y-auto"
                    >
                        <div>
                            <label
                                class="text-[11px] font-bold text-slate-700 block mb-1"
                            >
                                Rasio Kanvas Stiker:
                            </label>
                            <select
                                v-model="currentTemplate.aspect_ratio"
                                @change="emit('save-templates')"
                                class="w-full text-xs font-semibold bg-white border border-slate-300 rounded-lg p-2 focus:ring-primary focus:border-primary"
                            >
                                <option value="4/3">
                                    4:3 (Standar Resmi Badan Gizi Nasional)
                                </option>
                                <option value="16/9">16:9 (Widescreen)</option>
                                <option value="1/1">
                                    1:1 (Kotak Simetris)
                                </option>
                                <option value="3/2">
                                    3:2 (Format Stiker Kompak)
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="text-[11px] font-bold text-slate-700 block mb-1"
                            >
                                Margin Luar / White Space Kanvas:
                            </label>
                            <select
                                v-model="currentTemplate.canvas_padding"
                                @change="recordHistoryState(); emit('save-templates')"
                                class="w-full text-xs font-semibold bg-white border border-slate-300 rounded-lg p-2 focus:ring-primary focus:border-primary"
                            >
                                <option value="0px">Tanpa Margin (0px - Penuh ke Tepi)</option>
                                <option value="4px">4px (Sempit)</option>
                                <option value="6px">6px (Sedikit Jarak - Standar)</option>
                                <option value="8px">8px (Standar BGN)</option>
                                <option value="12px">12px (Sedang)</option>
                                <option value="16px">16px (Lebar / Ekstra Ruang)</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="text-[11px] font-bold text-slate-700 block mb-1"
                                >
                                    Border Kanvas:
                                </label>
                                <select
                                    v-model="currentTemplate.border_width"
                                    @change="emit('save-templates')"
                                    class="w-full text-xs font-semibold bg-white border border-slate-300 rounded-lg p-2 focus:ring-primary focus:border-primary"
                                >
                                    <option value="1px">1px (Tipis)</option>
                                    <option value="2px">2px (Sedang)</option>
                                    <option value="3px">
                                        3px (Standar BGN)
                                    </option>
                                    <option value="4px">4px (Tebal)</option>
                                    <option value="0px">
                                        Tanpa Border (0px)
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="text-[11px] font-bold text-slate-700 block mb-1"
                                >
                                    Sudut Melengkung:
                                </label>
                                <select
                                    v-model="currentTemplate.border_radius"
                                    @change="emit('save-templates')"
                                    class="w-full text-xs font-semibold bg-white border border-slate-300 rounded-lg p-2 focus:ring-primary focus:border-primary"
                                >
                                    <option value="0px">Kotak (0px)</option>
                                    <option value="0.5rem">
                                        8px (Sedikit)
                                    </option>
                                    <option value="1rem">16px (Standar)</option>
                                    <option value="1.5rem">
                                        24px (Melengkung)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="text-[11px] font-bold text-slate-700 block mb-1"
                                >
                                    Warna Border:
                                </label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="color"
                                        v-model="currentTemplate.border_color"
                                        @change="emit('save-templates')"
                                        class="h-8 w-10 p-0 rounded border border-slate-300 cursor-pointer"
                                    />
                                    <input
                                        type="text"
                                        v-model="currentTemplate.border_color"
                                        @input="emit('save-templates')"
                                        class="w-full text-xs font-mono p-1.5 border rounded"
                                    />
                                </div>
                            </div>
                            <div>
                                <label
                                    class="text-[11px] font-bold text-slate-700 block mb-1"
                                >
                                    Warna Background:
                                </label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="color"
                                        v-model="currentTemplate.canvas_bg"
                                        @change="emit('save-templates')"
                                        class="h-8 w-10 p-0 rounded border border-slate-300 cursor-pointer"
                                    />
                                    <input
                                        type="text"
                                        v-model="currentTemplate.canvas_bg"
                                        @input="emit('save-templates')"
                                        class="w-full text-xs font-mono p-1.5 border rounded"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: KOLEKSI TEMPLATE -->
                    <div
                        v-else-if="activeSidebarTab === 'template'"
                        class="p-4 space-y-3 max-h-[580px] overflow-y-auto"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700"
                                >Pilih Desain Template:</span
                            >
                            <button
                                type="button"
                                @click="createNewTemplate"
                                class="text-xs font-bold text-primary hover:underline flex items-center gap-1"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                <span>Baru</span>
                            </button>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="tmpl in templates"
                                :key="tmpl.id"
                                @click="
                                    selectedTemplateId = tmpl.id;
                                    selectedElementId = null;
                                "
                                :class="[
                                    'p-3 rounded-xl border-2 transition-all cursor-pointer flex flex-col justify-between',
                                    selectedTemplateId === tmpl.id
                                        ? 'border-primary bg-primary/5 shadow-2xs'
                                        : 'border-slate-200 bg-white hover:bg-slate-50',
                                ]"
                            >
                                <div
                                    class="flex items-center justify-between gap-2 mb-1"
                                >
                                    <span
                                        class="font-extrabold text-xs text-slate-900 truncate"
                                    >
                                        {{ tmpl.name }}
                                    </span>
                                    <span
                                        v-if="tmpl.id === activeTemplateId"
                                        class="text-[9.5px] font-black bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full shrink-0"
                                    >
                                        ✓ Digunakan
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between text-[10.5px] text-slate-500 pt-1 border-t border-slate-100"
                                >
                                    <span
                                        >Rasio
                                        {{ tmpl.aspect_ratio || "4:3" }}</span
                                    >
                                    <button
                                        v-if="tmpl.id !== activeTemplateId"
                                        type="button"
                                        @click.stop="
                                            markAsActiveTemplate(tmpl.id)
                                        "
                                        class="font-bold text-primary hover:underline cursor-pointer"
                                    >
                                        Gunakan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="restoreAllDefaultPresets"
                                class="w-full py-2 px-3 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                <span>Pulihkan Semua Preset Standar BGN</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Center/Right Interactive Canva Canvas (8 Kolom) -->
            <div class="lg:col-span-8 space-y-3">
                <!-- Canva Top Action Header (Undo, Redo, Simpan, & Info) -->
                <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-black shrink-0">
                            <Layout class="h-4 w-4" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <input
                                    type="text"
                                    v-model="currentTemplate.name"
                                    @change="recordHistoryState(); emit('save-templates')"
                                    class="font-extrabold text-sm text-slate-900 bg-transparent hover:bg-slate-50 focus:bg-white border-b border-transparent hover:border-slate-300 focus:border-primary px-1 py-0.5 rounded transition-all max-w-[220px] truncate"
                                    title="Klik untuk mengubah nama template"
                                />
                                <span
                                    v-if="currentTemplate.id === activeTemplateId"
                                    class="text-[10px] font-black bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full shrink-0 border border-emerald-200"
                                >
                                    ✓ Digunakan
                                </span>
                            </div>
                            <p class="text-[10.5px] text-slate-500 truncate">
                                Rasio {{ currentTemplate.aspect_ratio || '4:3' }} • {{ currentTemplate.elements?.length || 0 }} Elemen
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons: Undo, Redo, Simpan, Gunakan -->
                    <div class="flex items-center gap-2">
                        <!-- Undo Button -->
                        <button
                            type="button"
                            @click="undo"
                            :disabled="!canUndo"
                            :class="[
                                'px-2.5 py-1.5 rounded-lg border text-xs font-bold flex items-center gap-1.5 transition-all',
                                canUndo
                                    ? 'border-slate-300 bg-white hover:bg-slate-50 text-slate-800 shadow-2xs cursor-pointer'
                                    : 'border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed opacity-60'
                            ]"
                            title="Undo / Batalkan perubahan terakhir (Ctrl+Z)"
                        >
                            <Undo2 class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Undo</span>
                            <kbd class="text-[9px] bg-slate-100 px-1 py-0.2 rounded border border-slate-200 text-slate-500 font-mono hidden md:inline">Ctrl+Z</kbd>
                        </button>

                        <!-- Redo Button -->
                        <button
                            type="button"
                            @click="redo"
                            :disabled="!canRedo"
                            :class="[
                                'px-2.5 py-1.5 rounded-lg border text-xs font-bold flex items-center gap-1.5 transition-all',
                                canRedo
                                    ? 'border-slate-300 bg-white hover:bg-slate-50 text-slate-800 shadow-2xs cursor-pointer'
                                    : 'border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed opacity-60'
                            ]"
                            title="Redo / Terapkan ulang perubahan (Ctrl+Y)"
                        >
                            <Redo2 class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Redo</span>
                            <kbd class="text-[9px] bg-slate-100 px-1 py-0.2 rounded border border-slate-200 text-slate-500 font-mono hidden md:inline">Ctrl+Y</kbd>
                        </button>

                        <div class="h-5 w-[1px] bg-slate-200 mx-0.5"></div>

                        <!-- Simpan Button -->
                        <button
                            type="button"
                            @click="manualSaveTemplate"
                            class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold text-xs flex items-center gap-1.5 shadow-sm transition-all cursor-pointer"
                            title="Simpan perubahan template ke penyimpanan (Ctrl+S)"
                        >
                            <Save class="h-3.5 w-3.5" />
                            <span>Simpan</span>
                            <kbd class="text-[9px] bg-emerald-800/60 px-1 py-0.2 rounded text-emerald-100 font-mono hidden md:inline">Ctrl+S</kbd>
                        </button>

                        <!-- Gunakan Template Ini Button -->
                        <button
                            v-if="currentTemplate.id !== activeTemplateId"
                            type="button"
                            @click="markAsActiveTemplate(currentTemplate.id)"
                            class="px-3 py-1.5 rounded-lg border border-primary/40 bg-primary/10 hover:bg-primary/20 text-primary font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer"
                            title="Jadikan template ini sebagai template aktif cetak label"
                        >
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            <span>Gunakan</span>
                        </button>
                    </div>
                </div>

                <!-- Floating Quick Inspector for Selected Element (Canva Style) -->
                <div
                    v-if="selectedElement"
                    class="bg-white px-4 py-2 rounded-xl border border-slate-300 shadow-sm flex items-center justify-between gap-2 flex-wrap text-xs animate-in fade-in"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="font-extrabold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 text-[11px]"
                        >
                            {{ selectedElement.label || selectedElement.type }}
                        </span>
                        <div class="h-4 w-[1px] bg-slate-200"></div>

                        <!-- Text edit if text or badge or warning -->
                        <input
                            v-if="
                                ['text', 'badge', 'warning'].includes(
                                    selectedElement.type,
                                )
                            "
                            type="text"
                            v-model="selectedElement.text"
                            @input="emit('save-templates')"
                            placeholder="Ubah teks..."
                            class="text-xs p-1 border rounded w-44 bg-slate-50 focus:bg-white"
                        />

                        <!-- Logo / Image URL & Gallery Dropdown Input -->
                        <div
                            v-if="
                                ['logo', 'image'].includes(
                                    selectedElement.type,
                                )
                            "
                            class="flex items-center gap-1.5"
                        >
                            <span class="text-[11px] font-bold text-slate-600">Pilih Logo:</span>
                            <select
                                v-model="selectedElement.imageUrl"
                                @change="emit('save-templates')"
                                class="text-xs font-bold bg-white border border-slate-300 rounded-lg p-1 text-slate-800 focus:ring-primary focus:border-primary max-w-[210px]"
                            >
                                <option value="">(Lambang Standar Vektor)</option>
                                <option
                                    v-for="l in availableLogos"
                                    :key="l.id"
                                    :value="l.url"
                                >
                                    {{ l.name }}
                                </option>
                            </select>

                            <button
                                v-if="selectedElement.imageUrl"
                                type="button"
                                @click="selectedElement.imageUrl = ''; emit('save-templates')"
                                class="text-slate-400 hover:text-rose-600 text-xs font-bold px-1"
                                title="Hapus gambar & kembali ke lambang default"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Background Color picker -->
                        <div
                            class="flex items-center gap-1"
                            title="Warna Latar Belakang"
                        >
                            <Palette class="h-3.5 w-3.5 text-slate-500" />
                            <input
                                type="color"
                                v-model="selectedElement.backgroundColor"
                                @change="emit('save-templates')"
                                class="h-6 w-7 p-0 rounded border border-slate-300 cursor-pointer"
                            />
                        </div>

                        <!-- Text Color picker -->
                        <div
                            v-if="
                                ['text', 'badge', 'warning'].includes(
                                    selectedElement.type,
                                )
                            "
                            class="flex items-center gap-1"
                            title="Warna Teks"
                        >
                            <Type class="h-3.5 w-3.5 text-slate-500" />
                            <input
                                type="color"
                                v-model="selectedElement.color"
                                @change="emit('save-templates')"
                                class="h-6 w-7 p-0 rounded border border-slate-300 cursor-pointer"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <!-- Layer Order: Bring Forward / Send Backward -->
                        <button
                            type="button"
                            @click="bringForward"
                            class="p-1 hover:bg-slate-100 rounded text-slate-600"
                            title="Naikkan Layer (Ke Depan)"
                        >
                            <ArrowUp class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="sendBackward"
                            class="p-1 hover:bg-slate-100 rounded text-slate-600"
                            title="Turunkan Layer (Ke Belakang)"
                        >
                            <ArrowDown class="h-3.5 w-3.5" />
                        </button>

                        <!-- Lock / Unlock -->
                        <button
                            type="button"
                            @click="
                                selectedElement.isLocked =
                                    !selectedElement.isLocked;
                                emit('save-templates');
                            "
                            class="p-1 hover:bg-slate-100 rounded text-slate-600"
                            :title="
                                selectedElement.isLocked
                                    ? 'Buka Kunci'
                                    : 'Kunci Posisi'
                            "
                        >
                            <Lock
                                v-if="selectedElement.isLocked"
                                class="h-3.5 w-3.5 text-amber-600"
                            />
                            <Unlock v-else class="h-3.5 w-3.5 text-slate-400" />
                        </button>

                        <!-- Duplicate -->
                        <button
                            type="button"
                            @click="duplicateSelectedElement"
                            class="p-1 hover:bg-slate-100 rounded text-slate-600"
                            title="Duplikat Elemen"
                        >
                            <Copy class="h-3.5 w-3.5" />
                        </button>

                        <!-- Delete -->
                        <button
                            type="button"
                            @click="deleteSelectedElement"
                            class="p-1 hover:bg-rose-50 rounded text-rose-600"
                            title="Hapus Elemen"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div
                    v-else
                    class="bg-white px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-500 flex items-center justify-between"
                >
                    <span
                        >💡 Klik salah satu elemen pada kanvas di bawah untuk
                        menggeser, mengubah ukuran, atau mengedit teks &
                        warnanya.</span
                    >
                    <span class="text-[11px] font-mono text-slate-400"
                        >Rasio {{ currentTemplate.aspect_ratio || "4:3" }}</span
                    >
                </div>

                <!-- Canva Interactive Work Surface -->
                <div
                    class="p-6 sm:p-8 bg-slate-200/90 rounded-2xl border border-slate-300 flex items-center justify-center min-h-[460px] shadow-inner relative overflow-hidden select-none"
                    @click="selectedElementId = null"
                >
                    <!-- Background Canvas Grid Dots -->
                    <div
                        class="absolute inset-0 bg-[radial-gradient(#94a3b8_1px,transparent_1px)] [background-size:16px_16px] opacity-70 pointer-events-none"
                    ></div>

                    <!-- THE CANVA BOARD -->
                    <div
                        ref="canvasRef"
                        :class="[
                            'w-full max-w-[620px] mx-auto bg-white shadow-xl relative overflow-hidden transition-all',
                            currentTemplate.aspect_ratio === '16/9'
                                ? 'aspect-[16/9]'
                                : currentTemplate.aspect_ratio === '1/1'
                                  ? 'aspect-square'
                                  : currentTemplate.aspect_ratio === '3/2'
                                    ? 'aspect-[3/2]'
                                    : 'aspect-[4/3]',
                        ]"
                        :style="{
                            borderWidth: currentTemplate.border_width || '3px',
                            borderColor:
                                currentTemplate.border_color || '#1E4B8B',
                            borderRadius:
                                currentTemplate.border_radius || '1rem',
                            borderStyle: 'solid',
                            backgroundColor:
                                currentTemplate.canvas_bg || '#FFFFFF',
                            padding: currentTemplate.canvas_padding || '6px',
                        }"
                        @click.stop="deselectElement"
                    >
                        <!-- Canva Elements Layer -->
                        <div
                            v-for="el in currentTemplate.elements || []"
                            :key="el.id"
                            v-show="el.visible !== false"
                            @mousedown="startDragElement(el, $event)"
                            :class="[
                                'absolute cursor-move transition-shadow flex flex-col group',
                                selectedElementId === el.id
                                    ? 'ring-2 ring-blue-500 ring-offset-1 z-50 shadow-md'
                                    : 'hover:ring-1 hover:ring-blue-300',
                            ]"
                            :style="{
                                left: `${el.x}%`,
                                top: `${el.y}%`,
                                width: `${el.width}%`,
                                height: `${el.height}%`,
                                zIndex:
                                    selectedElementId === el.id
                                        ? 999
                                        : el.zIndex || 1,
                                backgroundColor:
                                    el.backgroundColor || 'transparent',
                                borderColor: el.borderColor || 'transparent',
                                borderWidth: el.borderWidth
                                    ? `${el.borderWidth}px`
                                    : '0px',
                                borderRadius: el.borderRadius
                                    ? `${el.borderRadius}px`
                                    : '0px',
                                borderStyle: el.borderWidth ? 'solid' : 'none',
                                color: el.color || 'inherit',
                                fontSize: el.fontSize
                                    ? `${el.fontSize}px`
                                    : 'inherit',
                                fontWeight: el.fontWeight || 'normal',
                                textAlign: el.textAlign || 'left',
                                padding: el.padding ? `${el.padding}px` : '0px',
                            }"
                        >
                            <!-- Selection Handle Dots on Corners & Edges -->
                            <template
                                v-if="
                                    selectedElementId === el.id && !el.isLocked
                                "
                            >
                                <div
                                    @mousedown.stop="
                                        startResize('nw', el, $event)
                                    "
                                    class="absolute -left-1.5 -top-1.5 h-3 w-3 rounded-full bg-blue-600 border-2 border-white cursor-nwse-resize z-50 shadow-xs"
                                ></div>
                                <div
                                    @mousedown.stop="
                                        startResize('ne', el, $event)
                                    "
                                    class="absolute -right-1.5 -top-1.5 h-3 w-3 rounded-full bg-blue-600 border-2 border-white cursor-nesw-resize z-50 shadow-xs"
                                ></div>
                                <div
                                    @mousedown.stop="
                                        startResize('se', el, $event)
                                    "
                                    class="absolute -right-1.5 -bottom-1.5 h-3 w-3 rounded-full bg-blue-600 border-2 border-white cursor-nwse-resize z-50 shadow-xs"
                                ></div>
                                <div
                                    @mousedown.stop="
                                        startResize('sw', el, $event)
                                    "
                                    class="absolute -left-1.5 -bottom-1.5 h-3 w-3 rounded-full bg-blue-600 border-2 border-white cursor-nesw-resize z-50 shadow-xs"
                                ></div>
                                <div
                                    @mousedown.stop="
                                        startResize('e', el, $event)
                                    "
                                    class="absolute -right-1.5 top-1/2 -translate-y-1/2 h-3.5 w-2 rounded bg-blue-600 border border-white cursor-ew-resize z-50 shadow-xs"
                                ></div>
                                <div
                                    @mousedown.stop="
                                        startResize('s', el, $event)
                                    "
                                    class="absolute left-1/2 -translate-x-1/2 -bottom-1.5 h-2 w-3.5 rounded bg-blue-600 border border-white cursor-ns-resize z-50 shadow-xs"
                                ></div>
                            </template>

                            <!-- Element Content Renderers -->
                            <!-- Type: LOGO -->
                            <div
                                v-if="el.type === 'logo'"
                                class="w-full h-full flex items-center justify-start pointer-events-none select-none overflow-hidden"
                            >
                                <img
                                    v-if="el.imageUrl"
                                    :src="el.imageUrl"
                                    alt="Logo BGN"
                                    class="h-full w-full object-contain object-left"
                                />
                                <div
                                    v-else
                                    class="h-full max-h-12 aspect-square rounded-full bg-[#1E3A8A] border-2 border-[#D4A017] p-0.5 flex items-center justify-center shrink-0 shadow-xs relative"
                                >
                                    <div
                                        class="h-full w-full rounded-full bg-[#0D6538] border border-[#D4A017] flex items-center justify-center text-center"
                                    >
                                        <span
                                            class="text-[#FBBF24] text-xs font-black"
                                            >★</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Type: IMAGE / GAMBAR KUSTOM -->
                            <div
                                v-else-if="el.type === 'image'"
                                class="w-full h-full flex items-center justify-center overflow-hidden pointer-events-none select-none"
                            >
                                <img
                                    v-if="el.imageUrl"
                                    :src="el.imageUrl"
                                    alt="Gambar"
                                    class="w-full h-full object-contain"
                                />
                                <div
                                    v-else
                                    class="w-full h-full border-2 border-dashed border-slate-300 rounded flex items-center justify-center text-slate-400 text-[10px]"
                                >
                                    Pilih Gambar Logo
                                </div>
                            </div>

                            <!-- Type: SPPG HEADER TEXT -->
                            <div
                                v-else-if="el.type === 'sppg_header'"
                                class="w-full h-full flex flex-col justify-center pointer-events-none select-none"
                                :style="{ textAlign: el.textAlign || 'right' }"
                            >
                                <span
                                    class="block text-[8.5px] sm:text-[9.5px] font-bold text-slate-500 uppercase tracking-wider"
                                >
                                    SATUAN PELAYANAN PEMENUHAN GIZI
                                </span>
                                <h2
                                    class="font-black text-[#1E3A8A] text-xs sm:text-sm md:text-[14px] leading-tight uppercase mt-0.5 truncate"
                                >
                                    {{
                                        unitSppg?.nama
                                            ? unitSppg.nama.startsWith("SPPG")
                                                ? unitSppg.nama
                                                : "SPPG " + unitSppg.nama
                                            : "SPPG BULELENG SUKASADA TEGALLINGGAH"
                                    }}
                                </h2>
                            </div>

                            <!-- Type: DIVIDER -->
                            <div
                                v-else-if="el.type === 'divider'"
                                class="w-full h-full rounded-full pointer-events-none"
                                :style="{
                                    backgroundColor:
                                        el.backgroundColor || '#C5921D',
                                }"
                            ></div>

                            <!-- Type: BADGE -->
                            <div
                                v-else-if="el.type === 'badge'"
                                class="w-full h-full flex items-center justify-center text-center font-black uppercase tracking-wide rounded-lg shadow-2xs text-white pointer-events-none select-none"
                                :style="{
                                    backgroundColor:
                                        el.backgroundColor || '#4E88C7',
                                    fontSize: el.fontSize
                                        ? `${el.fontSize}px`
                                        : '10px',
                                }"
                            >
                                {{ el.text || "LABEL MAKANAN BERGIZI GRATIS" }}
                            </div>

                            <!-- Type: TANGGAL -->
                            <div
                                v-else-if="el.type === 'tanggal'"
                                class="w-full h-full flex flex-col justify-between pointer-events-none select-none"
                            >
                                <label
                                    class="text-[9.5px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5"
                                >
                                    <Calendar
                                        class="h-2.5 w-2.5 text-slate-800"
                                    />
                                    <span>Tanggal Produksi</span>
                                </label>
                                <div
                                    class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1 px-2 text-center text-xs sm:text-sm font-black text-slate-900 shadow-2xs"
                                >
                                    {{ formatDateSlash(dummyTanggal) }}
                                </div>
                            </div>

                            <!-- Type: JAM -->
                            <div
                                v-else-if="el.type === 'jam'"
                                class="w-full h-full flex flex-col justify-between pointer-events-none select-none"
                            >
                                <label
                                    class="text-[9px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5 truncate"
                                >
                                    <Clock
                                        class="h-2.5 w-2.5 text-slate-800 shrink-0"
                                    />
                                    <span>Jam Produksi</span>
                                </label>
                                <div
                                    class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1 px-1.5 text-center text-[10.5px] sm:text-xs font-black text-slate-900 shadow-2xs"
                                >
                                    {{ formatJam(dummyJam) }}
                                </div>
                            </div>

                            <!-- Type: BATAS -->
                            <div
                                v-else-if="el.type === 'batas'"
                                class="w-full h-full flex flex-col justify-between pointer-events-none select-none"
                            >
                                <label
                                    class="text-[9px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5 truncate"
                                >
                                    <Hourglass
                                        class="h-2.5 w-2.5 text-slate-800 shrink-0"
                                    />
                                    <span>Batas Konsumsi</span>
                                </label>
                                <div
                                    class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1 px-1.5 text-center text-[10.5px] sm:text-xs font-black text-slate-900 shadow-2xs"
                                >
                                    {{ formatJam(dummyBatas) }}
                                </div>
                            </div>

                            <!-- Type: TUJUAN -->
                            <div
                                v-else-if="el.type === 'tujuan'"
                                class="w-full h-full flex flex-col justify-between pointer-events-none select-none"
                            >
                                <label
                                    class="text-[9.5px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5"
                                >
                                    <MapPin
                                        class="h-2.5 w-2.5 text-slate-800"
                                    />
                                    <span>Tujuan Pengantaran</span>
                                </label>
                                <div
                                    class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1.5 px-2 text-left text-[10.5px] sm:text-xs font-black text-[#1E3A8A] shadow-2xs leading-tight truncate"
                                >
                                    {{ dummyKelompokNama }}
                                </div>
                            </div>

                            <!-- Type: MENU -->
                            <div
                                v-else-if="el.type === 'menu'"
                                class="w-full h-full rounded-xl p-1 flex items-stretch gap-1.5 text-white shadow-2xs pointer-events-none select-none"
                                :style="{
                                    backgroundColor:
                                        el.backgroundColor || '#4E88C7',
                                }"
                            >
                                <div
                                    class="bg-black/20 rounded-lg px-2 flex items-center justify-center font-black text-[9.5px] uppercase tracking-wider shrink-0"
                                >
                                    MENU
                                </div>
                                <div
                                    class="flex items-center text-[9px] sm:text-[9.5px] font-bold leading-tight py-0.5 pr-1 text-white/95 line-clamp-3"
                                >
                                    {{ dummyMenu }}
                                </div>
                            </div>

                            <!-- Type: NUTRITION TABLE -->
                            <div
                                v-else-if="el.type === 'nutrition_table'"
                                class="w-full h-full flex flex-col justify-between pointer-events-none select-none"
                            >
                                <div>
                                    <div
                                        class="flex items-center gap-1 text-[9.5px] font-extrabold text-slate-900 mb-0.5"
                                    >
                                        <Flame
                                            class="h-2.5 w-2.5 text-slate-800"
                                        />
                                        <span>Kandungan Gizi</span>
                                    </div>
                                    <div
                                        class="border-b border-dashed border-slate-300 mb-1"
                                    ></div>
                                </div>

                                <div
                                    class="space-y-0.5 text-xs flex-1 flex flex-col justify-between"
                                >
                                    <div
                                        class="flex items-center justify-between gap-1"
                                    >
                                        <span
                                            class="text-[9.5px] font-bold text-slate-800"
                                        >
                                            Energi
                                            <span
                                                class="text-[8px] text-slate-500 font-normal"
                                                >(Kkal)</span
                                            >
                                        </span>
                                        <div
                                            class="flex items-center gap-1 shrink-0"
                                        >
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.energi_pk }}
                                            </div>
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.energi_pb }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center justify-between gap-1"
                                    >
                                        <span
                                            class="text-[9.5px] font-bold text-slate-800"
                                        >
                                            Karbohidrat
                                            <span
                                                class="text-[8px] text-slate-500 font-normal"
                                                >(g)</span
                                            >
                                        </span>
                                        <div
                                            class="flex items-center gap-1 shrink-0"
                                        >
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.karbo_pk }}
                                            </div>
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.karbo_pb }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center justify-between gap-1"
                                    >
                                        <span
                                            class="text-[9.5px] font-bold text-slate-800"
                                        >
                                            Protein
                                            <span
                                                class="text-[8px] text-slate-500 font-normal"
                                                >(g)</span
                                            >
                                        </span>
                                        <div
                                            class="flex items-center gap-1 shrink-0"
                                        >
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.protein_pk }}
                                            </div>
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.protein_pb }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center justify-between gap-1"
                                    >
                                        <span
                                            class="text-[9.5px] font-bold text-slate-800"
                                        >
                                            Lemak
                                            <span
                                                class="text-[8px] text-slate-500 font-normal"
                                                >(g)</span
                                            >
                                        </span>
                                        <div
                                            class="flex items-center gap-1 shrink-0"
                                        >
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.lemak_pk }}
                                            </div>
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.lemak_pb }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center justify-between gap-1"
                                    >
                                        <span
                                            class="text-[9.5px] font-bold text-slate-800"
                                        >
                                            Serat
                                            <span
                                                class="text-[8px] text-slate-500 font-normal"
                                                >(g)</span
                                            >
                                        </span>
                                        <div
                                            class="flex items-center gap-1 shrink-0"
                                        >
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.serat_pk }}
                                            </div>
                                            <div
                                                class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900"
                                            >
                                                {{ dummyGizi.serat_pb }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center justify-end gap-1 pt-0.5"
                                    >
                                        <div
                                            class="w-10 sm:w-11 bg-[#5A92CF] text-white rounded py-0.5 text-center text-[8px] font-extrabold leading-tight"
                                        >
                                            Porsi<br />Kecil
                                        </div>
                                        <div
                                            class="w-10 sm:w-11 bg-[#5A92CF] text-white rounded py-0.5 text-center text-[8px] font-extrabold leading-tight"
                                        >
                                            Porsi<br />Besar
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Type: WARNING -->
                            <div
                                v-else-if="el.type === 'warning'"
                                class="w-full h-full bg-[#FFF5F5] border border-[#FCA5A5] rounded-xl p-1.5 sm:p-2 flex items-center gap-2 relative overflow-hidden pointer-events-none select-none"
                            >
                                <div
                                    class="absolute left-0 top-0 bottom-0 w-1.5 sm:w-2 bg-[#DC2626]"
                                ></div>
                                <div class="pl-1.5 shrink-0">
                                    <div
                                        class="h-6 w-6 rounded-lg flex items-center justify-center text-[#DC2626]"
                                    >
                                        <AlertTriangle
                                            class="h-5 w-5"
                                            stroke-width="2.5"
                                        />
                                    </div>
                                </div>
                                <div class="leading-tight">
                                    <p
                                        class="text-[#DC2626] font-extrabold text-[9px] sm:text-[10px] tracking-tight uppercase"
                                    >
                                        {{
                                            el.text ||
                                            "MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT."
                                        }}
                                    </p>
                                    <p
                                        class="text-[#DC2626] font-black text-[11px] sm:text-xs tracking-wide uppercase mt-0.5"
                                    >
                                        {{
                                            el.subtitle ||
                                            "DILARANG MEMBAWA PULANG!"
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Type: TEXT / CUSTOM -->
                            <div
                                v-else
                                class="w-full h-full flex items-center justify-center break-words overflow-hidden pointer-events-none select-none"
                            >
                                {{ el.text || "" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
