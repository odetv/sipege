<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Modal from "@/Components/Modal.vue";
import {
    ClipboardList,
    Plus,
    Trash2,
    Save,
    Sparkles,
    Building2,
    Store,
    Phone,
    MapPin,
    Calendar,
    FileText,
    History,
    CheckCircle2,
    Search,
    Filter,
    Layers,
    Wand2,
    Eye,
    RotateCcw,
    ChevronDown,
    Download,
    ArrowRight,
    ArrowLeft,
    Edit3,
    Clock,
    X,
    Check,
    FileCheck,
    CloudUpload,
    CheckCheck,
    UserCheck,
    User,
    AlertTriangle,
    AlertCircle,
    HelpCircle,
} from "lucide-vue-next";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    surveiHargaList: {
        type: Array,
        default: () => [],
    },
    defaultSurveiItems: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        default: (val) =>
            val ? `Rp ${Number(val).toLocaleString("id-ID")}` : "Rp 0",
    },
    formatTanggalIndo: {
        type: Function,
        default: (val) => val || "-",
    },
});

const page = usePage();

// Compute Unit SPPG Name from Database
const unitSppgNama = computed(() => {
    return (
        props.unitSppg?.nama ||
        props.unitSppg?.nama_unit ||
        props.unitSppg?.nama_sppg ||
        props.user?.unit_sppg?.nama ||
        props.user?.unitSppg?.nama ||
        page.props.unitSppg?.nama ||
        page.props.auth?.user?.unit_sppg?.nama ||
        page.props.auth?.user?.unitSppg?.nama ||
        ""
    );
});

// Dynamic font size for Unit SPPG name to ensure text never truncates or cuts off with dots
const sppgTitleFontSizeClass = computed(() => {
    const len = (unitSppgNama.value || "").length;
    if (len > 45) return "text-[9.5px] sm:text-[10.5px] md:text-[11px]";
    if (len > 32) return "text-[10.5px] sm:text-[11.5px] md:text-xs";
    if (len > 22) return "text-[11px] sm:text-xs md:text-[12.5px]";
    return "text-xs sm:text-[13px] md:text-sm";
});

// Compute User Full Name (Kepala SPPG) from Database
const userFullNameFromDb = computed(() => {
    const u =
        props.user && Object.keys(props.user).length > 0
            ? props.user
            : page.props.auth?.user || {};
    return (
        u.nama_lengkap ||
        u.nama ||
        u.name ||
        page.props.auth?.user?.nama_lengkap ||
        page.props.auth?.user?.nama ||
        page.props.auth?.user?.name ||
        ""
    );
});

// Standard abbreviation options for Satuan
const satuanOptions = [
    { value: "Kg", label: "Kg (Kilogram)" },
    { value: "g", label: "g (Gram)" },
    { value: "L", label: "L (Liter)" },
    { value: "ml", label: "ml (Mililiter)" },
    { value: "pcs", label: "pcs (Pcs / Biji)" },
    { value: "btl", label: "btl (Botol)" },
    { value: "bks", label: "bks (Bungkus)" },
    { value: "pack", label: "pack (Pak)" },
    { value: "ikat", label: "ikat (Ikat)" },
    { value: "sisir", label: "sisir (Sisir)" },
    { value: "butir", label: "butir (Butir)" },
    { value: "lonjor", label: "lonjor (Lonjor)" },
    { value: "ember", label: "ember (Ember)" },
    { value: "jirigen", label: "jirigen (Jirigen / Jerigen)" },
    { value: "bal", label: "bal (Bal)" },
    { value: "karung", label: "karung (Karung / Sak)" },
    { value: "dus", label: "dus (Kardus / Karton)" },
    { value: "buah", label: "buah (Buah)" },
    { value: "tray", label: "tray (Tray Telur)" },
    { value: "kaleng", label: "kaleng (Kaleng)" },
    { value: "sachet", label: "sachet (Sachet)" },
];

// Main View: 'list' (Daftar Riwayat - Tampilan Awal) | 'form' (Editor & Pratinjau)
const currentView = ref("list");

// Sub-view in 'form' view: 'form' | 'preview'
const formSubView = ref("form");

// Current Working UID (for URL query & database)
const currentUid = ref("");

// Local Draft state
const localDraftItem = ref(null);

function generateRandomUid() {
    if (typeof crypto !== "undefined" && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return (
        "shp-" +
        Math.random().toString(36).substring(2, 10) +
        "-" +
        Date.now().toString(36)
    );
}

// Helper sync URL with survei UID
function updateUrl(surveiUid = null) {
    if (typeof window === "undefined") return;
    const url = new URL(window.location.href);
    if (surveiUid) {
        url.searchParams.set("tab", "survei-harga");
        url.searchParams.set("survei_id", surveiUid);
    } else {
        url.searchParams.set("tab", "survei-harga");
        url.searchParams.delete("survei_id");
    }
    window.history.replaceState({}, "", url.toString());
}

// Search & Filter
const searchQuery = ref("");
const selectedCategory = ref("all");

// Helper generate No Dokumen Format: NO/SPPG/KEU/SHP/BULAN_ROMAWI/TAHUN (Contoh: 001/SPPG/KEU/SHP/IX/2026)
const romanMonths = [
    "I",
    "II",
    "III",
    "IV",
    "V",
    "VI",
    "VII",
    "VIII",
    "IX",
    "X",
    "XI",
    "XII",
];
function generateNoDokumen(customIndex = null) {
    const now = new Date();
    const romanMonth = romanMonths[now.getMonth()] || "I";
    const year = now.getFullYear();
    const count =
        customIndex !== null ? customIndex : props.surveiHargaList.length + 1;
    const paddedNo = String(count).padStart(3, "0");
    return `${paddedNo}/SPPG/KEU/SHP/${romanMonth}/${year}`;
}

// Form Data State
const formId = ref(null); // null if new, number if editing
const noDokumen = ref(generateNoDokumen());

// Revisi: can be empty date / string. If empty, preview shows "-"
const revisi = ref("");

const tanggalBerlaku = ref(new Date().toISOString().split("T")[0]);
const tanggalSurvei = ref(new Date().toISOString().split("T")[0]);

// Lokasi Survei Pasar starts empty
const lokasiSurvei = ref("");

// Petugas Survei 1: Kepala SPPG (auto-filled from database user)
const petugasSurvei1 = ref("");

// Synchronize Petugas 1 with database user
watch(
    userFullNameFromDb,
    (name) => {
        if (
            name &&
            (!petugasSurvei1.value || petugasSurvei1.value === "Kepala SPPG")
        ) {
            petugasSurvei1.value = name;
        }
    },
    { immediate: true },
);

// Petugas Survei 2: Pengawas Keuangan (manual input)
const petugasSurvei2 = ref("");

// Mengetahui: Kepala Pasar (starts empty)
const mengetahuiKepalaPasar = ref("");

const catatan = ref("");
const statusSurvei = ref("Draft");

// List of survey items
const items = ref([]);

// Auto-save State
const autoSaveStatus = ref("idle"); // 'idle' | 'saving' | 'saved' | 'error'
const lastSavedTime = ref("");
let autoSaveTimer = null;
const isInitialLoading = ref(false);

// Modals
const showSmartFillModal = ref(false);
const showSuccessNotice = ref(false);
const isSaving = ref(false);
const showNewSurveyBackModal = ref(false);
const showDeleteModal = ref(false);
const surveyToDelete = ref(null);
const isDeleting = ref(false);
const showClearDraftModal = ref(false);

// Smart Fill form state
const smartVendorSupplierId = ref("");
const smartVendorTargetCategory = ref("all");

// Days of week mapping in Indonesian
const indonesianDays = [
    "Minggu",
    "Senin",
    "Selasa",
    "Rabu",
    "Kamis",
    "Jumat",
    "Sabtu",
];

// Automatically get Indonesian Day & Date string directly from date
function getFormattedHariTanggal(dateStr) {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const hari = indonesianDays[d.getDay()];
        const formattedDate = d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric",
        });
        return `${hari}, ${formattedDate}`;
    } catch {
        return dateStr;
    }
}

// Category Definitions (without 'dst.')
const categories = [
    {
        key: "karbohidrat",
        label: "KARBOHIDRAT",
        badgeColor: "bg-amber-100 text-amber-800 border-amber-200",
    },
    {
        key: "hewani",
        label: "HEWANI",
        badgeColor: "bg-red-100 text-red-800 border-red-200",
    },
    {
        key: "nabati",
        label: "NABATI",
        badgeColor: "bg-emerald-100 text-emerald-800 border-emerald-200",
    },
    {
        key: "sayuran",
        label: "SAYURAN",
        badgeColor: "bg-green-100 text-green-800 border-green-200",
    },
    {
        key: "buah",
        label: "BUAH",
        badgeColor: "bg-purple-100 text-purple-800 border-purple-200",
    },
    {
        key: "lain_lain",
        label: "Lain-lain",
        badgeColor: "bg-slate-100 text-slate-800 border-slate-200",
    },
];

// Baseline standard market reference prices for Auto-Fill
const standardReferencePrices = {
    Beras: 14500,
    Kentang: 18000,
    "Ayam Potong": 36000,
    "Ayam Fillet": 48000,
    "Telur Ukuran Besar": 28500,
    "Telur Ukuran Sedang": 27000,
    "Telur Ukuran Kecil": 25500,
    "Daging Sapi": 135000,
    "Telur Puyuh": 38000,
    "Susu Full Cream": 17500,
    Tahu: 10000,
    Tempe: 12000,
    Bayam: 6000,
    Buncis: 15000,
    "Jagung Pipil": 14000,
    "Kol Putih": 9000,
    "Wortel Lokal": 12000,
    "Wortel Berastagi": 16000,
    Brokoli: 28000,
    "Kembang Kol": 25000,
    Tomat: 14000,
    "Daun Pandan": 8000,
    "Daun Seledri": 20000,
    "Daun Salam": 8000,
    "Cabe Teropong Merah": 45000,
    "Cabe Keriting": 50000,
    "Jeruk Nipis": 18000,
    "Kunyit Mentah": 12000,
    Lengkuas: 12000,
    Pala: 85000,
    "Bawang Merah Kupas": 38000,
    "Bawang Putih Kupas": 40000,
    "Asam Jawa": 24000,
    "Bawang Bombay": 32000,
    "Gula Merah": 22000,
    Sereh: 10000,
    "Bawang Merah Utuh": 32000,
    "Bawang Putih Utuh": 35000,
    "Kemiri Pecah": 42000,
    "Buah Naga": 22000,
    "Jeruk Manis": 20000,
    "Jeruk Santang": 30000,
    Kelengkeng: 38000,
    Anggur: 55000,
    "Apel Fuji": 35000,
    Melon: 16000,
    "Semangka Merah": 10000,
    "Semangka Kuning": 12000,
    "Pisang Mas": 18000,
    "Pisang Ambon": 20000,
    Salak: 15000,
    "Sabun Cuci Piring": 14000,
    Karbol: 16000,
};

// Initialize Items with empty inputs (no 'dst.')
function initDefaultItems() {
    if (props.defaultSurveiItems && props.defaultSurveiItems.length > 0) {
        items.value = props.defaultSurveiItems
            .filter((i) => i.nama_bahan !== "dst.")
            .map((i) => ({
                id: i.id,
                kategori: i.kategori,
                kategori_label: i.kategori_label,
                nomor: i.nomor,
                nama_bahan: i.nama_bahan,
                satuan: i.satuan || "Kg",
                harga: null,
                nama_toko: "",
                kontak: "",
                keterangan: "",
            }));
    } else {
        items.value = [];
    }
}

// LocalStorage Draft Key
const draftStorageKey = computed(() => {
    const unitId = props.unitSppg?.id || "default";
    return `sipege_survei_harga_draft_${unitId}`;
});

// Auto-Save: Save to DB if editing existing survey, or save to LocalStorage if new survey draft
function triggerAutoSave() {
    if (isInitialLoading.value) return;
    if (currentView.value !== "form") return;

    autoSaveStatus.value = "saving";
    if (autoSaveTimer) clearTimeout(autoSaveTimer);

    autoSaveTimer = setTimeout(() => {
        if (formId.value) {
            // Live auto-save directly to Database for existing survey
            const payload = {
                uid: currentUid.value || generateRandomUid(),
                no_dokumen: noDokumen.value,
                revisi: revisi.value || null,
                tanggal_berlaku: tanggalBerlaku.value,
                tanggal_survei: tanggalSurvei.value,
                hari_survei: getFormattedHariTanggal(tanggalSurvei.value).split(
                    ",",
                )[0],
                lokasi_survei: lokasiSurvei.value || null,
                petugas_survei:
                    petugasSurvei1.value || userFullNameFromDb.value || null,
                petugas_survei_2: petugasSurvei2.value || null,
                mengetahui_nama: mengetahuiKepalaPasar.value || null,
                status: statusSurvei.value || "Selesai",
                catatan: catatan.value,
                items: items.value,
            };

            router.put(
                route("keuangan.survei-harga.update", formId.value),
                payload,
                {
                    preserveScroll: true,
                    preserveState: true,
                    only: ["surveiHargaList"],
                    onSuccess: () => {
                        autoSaveStatus.value = "saved";
                        lastSavedTime.value = new Date().toLocaleTimeString(
                            "id-ID",
                            {
                                hour: "2-digit",
                                minute: "2-digit",
                                second: "2-digit",
                            },
                        );
                    },
                    onError: () => {
                        autoSaveStatus.value = "error";
                    },
                },
            );
        } else {
            // Auto-save new survey as LocalStorage Draft
            try {
                const draftData = {
                    uid: currentUid.value || generateRandomUid(),
                    formId: null,
                    noDokumen: noDokumen.value,
                    revisi: revisi.value,
                    tanggalBerlaku: tanggalBerlaku.value,
                    tanggalSurvei: tanggalSurvei.value,
                    lokasiSurvei: lokasiSurvei.value,
                    petugasSurvei1:
                        petugasSurvei1.value || userFullNameFromDb.value,
                    petugasSurvei2: petugasSurvei2.value,
                    mengetahuiKepalaPasar: mengetahuiKepalaPasar.value,
                    catatan: catatan.value,
                    statusSurvei: statusSurvei.value,
                    items: items.value,
                    savedAt: new Date().toLocaleTimeString("id-ID", {
                        hour: "2-digit",
                        minute: "2-digit",
                        second: "2-digit",
                    }),
                };
                localStorage.setItem(
                    draftStorageKey.value,
                    JSON.stringify(draftData),
                );
                autoSaveStatus.value = "saved";
                lastSavedTime.value = draftData.savedAt;
            } catch (e) {
                console.error("Gagal auto-save draft:", e);
                autoSaveStatus.value = "idle";
            }
        }
    }, 800);
}

// Check local draft presence
function checkLocalDraft() {
    try {
        const savedDraft = localStorage.getItem(draftStorageKey.value);
        if (savedDraft) {
            const parsed = JSON.parse(savedDraft);
            if (
                parsed &&
                Array.isArray(parsed.items) &&
                parsed.items.length > 0
            ) {
                localDraftItem.value = parsed;
                return;
            }
        }
    } catch (e) {
        console.error("Gagal membaca draft lokal:", e);
    }
    localDraftItem.value = null;
}

// Resume draft from LocalStorage
function resumeLocalDraft(syncUrl = true) {
    if (!localDraftItem.value) return;
    isInitialLoading.value = true;
    const parsed = localDraftItem.value;
    formId.value = null;
    currentUid.value = parsed.uid || generateRandomUid();
    noDokumen.value = parsed.noDokumen || noDokumen.value;
    revisi.value = parsed.revisi !== undefined ? parsed.revisi : "";
    tanggalBerlaku.value = parsed.tanggalBerlaku || tanggalBerlaku.value;
    tanggalSurvei.value = parsed.tanggalSurvei || tanggalSurvei.value;
    lokasiSurvei.value = parsed.lokasiSurvei || "";
    petugasSurvei1.value = parsed.petugasSurvei1 || userFullNameFromDb.value;
    petugasSurvei2.value = parsed.petugasSurvei2 || "";
    mengetahuiKepalaPasar.value = parsed.mengetahuiKepalaPasar || "";
    catatan.value = parsed.catatan || "";
    statusSurvei.value = parsed.statusSurvei || "Draft";
    items.value = parsed.items;
    autoSaveStatus.value = "saved";
    lastSavedTime.value = parsed.savedAt || "";
    currentView.value = "form";
    formSubView.value = "form";
    if (syncUrl) {
        updateUrl(currentUid.value);
    }
    setTimeout(() => {
        isInitialLoading.value = false;
    }, 300);
}

// Open / Confirm Clear Local Draft Modal
function openClearDraftModal() {
    showClearDraftModal.value = true;
}

function confirmClearDraft() {
    localStorage.removeItem(draftStorageKey.value);
    localDraftItem.value = null;
    showClearDraftModal.value = false;
}

// Watch changes on form fields to trigger auto-save
watch(
    [
        noDokumen,
        revisi,
        tanggalBerlaku,
        tanggalSurvei,
        lokasiSurvei,
        petugasSurvei1,
        petugasSurvei2,
        mengetahuiKepalaPasar,
        catatan,
        items,
    ],
    () => {
        if (currentView.value === "form") {
            triggerAutoSave();
        }
    },
    { deep: true },
);

onMounted(() => {
    // Default Petugas I to user's name from DB
    if (!petugasSurvei1.value) {
        petugasSurvei1.value = userFullNameFromDb.value;
    }

    checkLocalDraft();

    // Check if URL has survei_id param
    if (typeof window !== "undefined") {
        const urlParams = new URLSearchParams(window.location.search);
        const surveiIdParam = urlParams.get("survei_id");
        if (surveiIdParam) {
            const found = props.surveiHargaList.find(
                (s) =>
                    String(s.uid) === String(surveiIdParam) ||
                    String(s.id) === String(surveiIdParam),
            );
            if (found) {
                loadSurveyData(found, false);
                return;
            } else if (
                localDraftItem.value &&
                (localDraftItem.value.uid === surveiIdParam ||
                    String(localDraftItem.value.formId) === surveiIdParam)
            ) {
                resumeLocalDraft(false);
                return;
            } else {
                startNewSurvey(surveiIdParam);
                return;
            }
        }
    }

    // Default view when tab is opened is strictly the List View
    currentView.value = "list";
    initDefaultItems();
});

onUnmounted(() => {
    if (autoSaveTimer) clearTimeout(autoSaveTimer);
});

// Load survey from history list
function loadSurveyData(survey, syncUrl = true) {
    if (!survey) return;
    isInitialLoading.value = true;
    formId.value = survey.id;
    currentUid.value = survey.uid || generateRandomUid();
    noDokumen.value = survey.no_dokumen || "";
    revisi.value = survey.revisi || "";
    tanggalBerlaku.value =
        survey.tanggal_berlaku || new Date().toISOString().split("T")[0];
    tanggalSurvei.value =
        survey.tanggal_survei || new Date().toISOString().split("T")[0];
    lokasiSurvei.value = survey.lokasi_survei || "";
    petugasSurvei1.value = survey.petugas_survei || userFullNameFromDb.value;
    petugasSurvei2.value = survey.petugas_survei_2 || "";
    mengetahuiKepalaPasar.value = survey.mengetahui_nama || "";
    catatan.value = survey.catatan || "";
    statusSurvei.value = survey.status || "Selesai";

    if (
        survey.items &&
        Array.isArray(survey.items) &&
        survey.items.length > 0
    ) {
        items.value = JSON.parse(JSON.stringify(survey.items)).filter(
            (i) => i.nama_bahan !== "dst.",
        );
    } else {
        initDefaultItems();
    }

    currentView.value = "form";
    formSubView.value = "form";
    if (syncUrl) {
        updateUrl(currentUid.value);
    }
    autoSaveStatus.value = "saved";
    lastSavedTime.value = new Date().toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
    setTimeout(() => {
        isInitialLoading.value = false;
    }, 300);
}

// Reset form for fresh blank new survey
function startNewSurvey(customUid = null) {
    isInitialLoading.value = true;
    formId.value = null;
    currentUid.value = customUid || generateRandomUid();
    noDokumen.value = generateNoDokumen();
    revisi.value = ""; // Blank revisi
    tanggalBerlaku.value = new Date().toISOString().split("T")[0];
    tanggalSurvei.value = new Date().toISOString().split("T")[0];
    lokasiSurvei.value = ""; // Blank lokasi
    petugasSurvei1.value = userFullNameFromDb.value; // Kepala SPPG from DB user
    petugasSurvei2.value = ""; // Pengawas Keuangan blank
    mengetahuiKepalaPasar.value = ""; // Kepala Pasar blank
    catatan.value = "";
    statusSurvei.value = "Draft";
    initDefaultItems();
    currentView.value = "form";
    formSubView.value = "form";
    updateUrl(currentUid.value);
    autoSaveStatus.value = "idle";
    lastSavedTime.value = "";
    setTimeout(() => {
        isInitialLoading.value = false;
    }, 300);
}

// Duplicate an existing survey as a new draft
function duplicateSurvey(survey) {
    loadSurveyData(survey, false);
    formId.value = null;
    currentUid.value = generateRandomUid();
    noDokumen.value = generateNoDokumen();
    tanggalSurvei.value = new Date().toISOString().split("T")[0];
    statusSurvei.value = "Draft";
    currentView.value = "form";
    formSubView.value = "form";
    updateUrl(currentUid.value);
    triggerAutoSave();
}

// Check if user has entered data in a new survey form
const hasNewSurveyModifications = computed(() => {
    if (formId.value) return false;
    const hasPrice = items.value.some((i) => i.harga && Number(i.harga) > 0);
    const hasStore = items.value.some(
        (i) => i.nama_toko && i.nama_toko.trim() !== "",
    );
    const hasNote = items.value.some(
        (i) => i.keterangan && i.keterangan.trim() !== "",
    );
    const hasLocation = lokasiSurvei.value && lokasiSurvei.value.trim() !== "";
    const hasPetugas2 =
        petugasSurvei2.value && petugasSurvei2.value.trim() !== "";
    const hasCatatan = catatan.value && catatan.value.trim() !== "";
    const hasMengetahui =
        mengetahuiKepalaPasar.value &&
        mengetahuiKepalaPasar.value.trim() !== "";
    return (
        hasPrice ||
        hasStore ||
        hasNote ||
        hasLocation ||
        hasPetugas2 ||
        hasCatatan ||
        hasMengetahui
    );
});

// Smart Back Navigation: prompts modal if new survey has unsaved changes
function handleBackNavigation() {
    if (!formId.value && hasNewSurveyModifications.value) {
        showNewSurveyBackModal.value = true;
    } else {
        goBackToList();
    }
}

// Return from form/preview back to the list view
function goBackToList() {
    currentView.value = "list";
    updateUrl(null);
    checkLocalDraft();
}

// Save new survey directly to Database and exit to list
function saveAndExitNewSurvey() {
    isSaving.value = true;
    const saveUid = currentUid.value || generateRandomUid();
    currentUid.value = saveUid;

    const payload = {
        uid: saveUid,
        no_dokumen: noDokumen.value,
        revisi: revisi.value || null,
        tanggal_berlaku: tanggalBerlaku.value,
        tanggal_survei: tanggalSurvei.value,
        hari_survei: getFormattedHariTanggal(tanggalSurvei.value).split(",")[0],
        lokasi_survei: lokasiSurvei.value || null,
        petugas_survei:
            petugasSurvei1.value || userFullNameFromDb.value || null,
        petugas_survei_2: petugasSurvei2.value || null,
        mengetahui_nama: mengetahuiKepalaPasar.value || null,
        status: statusSurvei.value || "Selesai",
        catatan: catatan.value,
        items: items.value,
    };

    router.post(route("keuangan.survei-harga.store"), payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            showNewSurveyBackModal.value = false;
            localStorage.removeItem(draftStorageKey.value);
            localDraftItem.value = null;
            showSuccessNotice.value = true;
            setTimeout(() => (showSuccessNotice.value = false), 3500);
            goBackToList();
        },
        onError: () => {
            isSaving.value = false;
        },
    });
}

// Discard draft and exit to list
function discardDraftAndExit() {
    localStorage.removeItem(draftStorageKey.value);
    localDraftItem.value = null;
    initDefaultItems();
    showNewSurveyBackModal.value = false;
    goBackToList();
}

// Open & Confirm Delete Survey Modal
function openDeleteModal(survey) {
    surveyToDelete.value = survey;
    showDeleteModal.value = true;
}

function confirmDeleteSurvey() {
    if (!surveyToDelete.value) return;
    isDeleting.value = true;
    const targetId = surveyToDelete.value.uid || surveyToDelete.value.id;

    router.delete(route("keuangan.survei-harga.destroy", targetId), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            if (formId.value === targetId || currentUid.value === targetId) {
                goBackToList();
            }
            surveyToDelete.value = null;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
}

// Summary Statistics
const surveyStats = computed(() => {
    const total = items.value.length;
    const filledPrice = items.value.filter(
        (i) => i.harga && Number(i.harga) > 0,
    ).length;
    const filledStore = items.value.filter(
        (i) => i.nama_toko && i.nama_toko.trim() !== "",
    ).length;
    const totalPrice = items.value.reduce(
        (acc, i) => acc + (Number(i.harga) || 0),
        0,
    );
    return {
        total,
        filledPrice,
        filledStore,
        totalPrice,
        percentComplete:
            total > 0 ? Math.round((filledPrice / total) * 100) : 0,
    };
});

// Format Nominal Input with Indonesian thousand separator (dots)
function formatNominalInput(val) {
    if (val === null || val === undefined || val === "") return "";
    const cleanNum = String(val).replace(/\D/g, "");
    if (!cleanNum) return "";
    return new Intl.NumberFormat("id-ID").format(Number(cleanNum));
}

// Handle Harga input with dynamic thousand dots formatting
function onHargaInput(item, event) {
    const rawVal = event.target.value;
    const cleanNum = String(rawVal).replace(/\D/g, "");
    if (!cleanNum) {
        item.harga = null;
        event.target.value = "";
    } else {
        const num = Number(cleanNum);
        item.harga = num;
        event.target.value = new Intl.NumberFormat("id-ID").format(num);
    }
}

// Handle Kontak input: strictly digits only
function onKontakInput(item, event) {
    const cleanNum = (event.target.value || "").replace(/\D/g, "");
    item.kontak = cleanNum;
    event.target.value = cleanNum;
}

// Realtime filtered items by category & search query
function getFilteredItemsByCategory(catKey) {
    const q = (searchQuery.value || "").trim().toLowerCase();
    const catItems = items.value.filter((i) => i.kategori === catKey);
    if (!q) return catItems;
    return catItems.filter((i) => {
        return (
            (i.nama_bahan || "").toLowerCase().includes(q) ||
            (i.nama_toko || "").toLowerCase().includes(q) ||
            (i.keterangan || "").toLowerCase().includes(q) ||
            (i.kontak || "").toLowerCase().includes(q) ||
            (i.satuan || "").toLowerCase().includes(q)
        );
    });
}

// Total items matching current search query across categories
const totalFilteredItemsCount = computed(() => {
    const q = (searchQuery.value || "").trim().toLowerCase();
    if (!q) {
        if (selectedCategory.value === "all") return items.value.length;
        return items.value.filter((i) => i.kategori === selectedCategory.value)
            .length;
    }
    return items.value.filter((i) => {
        if (
            selectedCategory.value !== "all" &&
            i.kategori !== selectedCategory.value
        )
            return false;
        return (
            (i.nama_bahan || "").toLowerCase().includes(q) ||
            (i.nama_toko || "").toLowerCase().includes(q) ||
            (i.keterangan || "").toLowerCase().includes(q) ||
            (i.kontak || "").toLowerCase().includes(q) ||
            (i.satuan || "").toLowerCase().includes(q)
        );
    }).length;
});

// Search query for Tab 2: Daftar Riwayat
const historySearchQuery = ref("");
const filteredHistoryList = computed(() => {
    const q = (historySearchQuery.value || "").trim().toLowerCase();
    if (!q) return props.surveiHargaList;
    return props.surveiHargaList.filter((s) => {
        return (
            (s.no_dokumen || "").toLowerCase().includes(q) ||
            (s.lokasi_survei || "").toLowerCase().includes(q) ||
            (s.petugas_survei || "").toLowerCase().includes(q) ||
            (s.petugas_survei_2 || "").toLowerCase().includes(q) ||
            (s.tanggal_survei || "").toLowerCase().includes(q) ||
            (s.status || "").toLowerCase().includes(q)
        );
    });
});

// When user selects a vendor from supplier dropdown for an item, auto-fill contact
function onSupplierSelect(item, supplierName) {
    item.nama_toko = supplierName;
    const matched = props.suppliers.find((s) => s.nama_usaha === supplierName);
    if (matched) {
        item.kontak = (matched.no_telp || item.kontak || "").replace(/\D/g, "");
    }
}

// Add new row inside a category with completely empty fields
function addItemToCategory(catKey) {
    const catItems = items.value.filter((i) => i.kategori === catKey);
    const nextNomor = catItems.length + 1;
    const nextId = Math.max(0, ...items.value.map((i) => i.id || 0)) + 1;

    const newItem = {
        id: nextId,
        kategori: catKey,
        kategori_label:
            categories.find((c) => c.key === catKey)?.label ||
            catKey.toUpperCase(),
        nomor: nextNomor,
        nama_bahan: "", // Completely empty
        satuan: "", // Completely empty
        harga: null, // Completely empty
        nama_toko: "", // Completely empty
        kontak: "", // Completely empty
        keterangan: "", // Completely empty
    };

    items.value.push(newItem);
}

// Remove row
function removeItem(item) {
    items.value = items.value.filter((i) => i !== item);
    // Recalculate numbering per category
    categories.forEach((cat) => {
        const catItems = items.value.filter((i) => i.kategori === cat.key);
        catItems.forEach((it, idx) => {
            it.nomor = idx + 1;
        });
    });
}

// Smart Auto-Fill Action: Apply Vendor & Contact in Bulk
function applySmartVendor() {
    if (!smartVendorSupplierId.value) return;
    const supp = props.suppliers.find(
        (s) => s.id == smartVendorSupplierId.value,
    );
    if (!supp) return;

    items.value.forEach((it) => {
        if (
            smartVendorTargetCategory.value === "all" ||
            it.kategori === smartVendorTargetCategory.value
        ) {
            it.nama_toko = supp.nama_usaha;
            it.kontak = supp.no_telp || it.kontak;
        }
    });

    showSmartFillModal.value = false;
}

// Smart Auto-Fill: Load Reference Baseline Prices
function applyReferencePrices() {
    items.value.forEach((it) => {
        if (!it.harga || it.harga === 0) {
            if (standardReferencePrices[it.nama_bahan]) {
                it.harga = standardReferencePrices[it.nama_bahan];
            }
        }
    });
    showSmartFillModal.value = false;
}

// Save survey to database
function saveSurvey() {
    isSaving.value = true;
    const saveUid = currentUid.value || generateRandomUid();
    currentUid.value = saveUid;

    const payload = {
        uid: saveUid,
        no_dokumen: noDokumen.value,
        revisi: revisi.value || null,
        tanggal_berlaku: tanggalBerlaku.value,
        tanggal_survei: tanggalSurvei.value,
        hari_survei: getFormattedHariTanggal(tanggalSurvei.value).split(",")[0],
        lokasi_survei: lokasiSurvei.value || null,
        petugas_survei:
            petugasSurvei1.value || userFullNameFromDb.value || null,
        petugas_survei_2: petugasSurvei2.value || null,
        mengetahui_nama: mengetahuiKepalaPasar.value || null,
        status: statusSurvei.value || "Selesai",
        catatan: catatan.value,
        items: items.value,
    };

    if (formId.value) {
        router.put(
            route("keuangan.survei-harga.update", formId.value),
            payload,
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    isSaving.value = false;
                    showSuccessNotice.value = true;
                    updateUrl(currentUid.value);
                    setTimeout(() => (showSuccessNotice.value = false), 3500);
                },
                onError: () => {
                    isSaving.value = false;
                },
            },
        );
    } else {
        router.post(route("keuangan.survei-harga.store"), payload, {
            preserveScroll: true,
            onSuccess: (page) => {
                isSaving.value = false;
                showSuccessNotice.value = true;
                updateUrl(currentUid.value);
                setTimeout(() => (showSuccessNotice.value = false), 3500);
            },
            onError: () => {
                isSaving.value = false;
            },
        });
    }
}

// Open delete modal for survey
function deleteSurvey(idOrUid) {
    const found = props.surveiHargaList.find(
        (s) => s.uid === idOrUid || s.id === idOrUid,
    ) || {
        id: idOrUid,
        uid: idOrUid,
        no_dokumen: noDokumen.value,
        lokasi_survei: lokasiSurvei.value,
        tanggal_survei: tanggalSurvei.value,
        petugas_survei: petugasSurvei1.value,
    };
    openDeleteModal(found);
}

// Helper load logo as base64 for embedding into PDF
function getBase64ImageFromUrl(url) {
    return new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.onload = () => {
            try {
                const canvas = document.createElement("canvas");
                canvas.width = img.naturalWidth || img.width;
                canvas.height = img.naturalHeight || img.height;
                const ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0);
                resolve(canvas.toDataURL("image/png"));
            } catch (e) {
                console.warn("Gagal memproses gambar logo:", e);
                resolve(null);
            }
        };
        img.onerror = () => resolve(null);
        img.src = url;
    });
}

// Vector-based PDF Download Handler (Teks Asli, Presisi Vektor, A4 Portrait)
const isDownloadingPdf = ref(false);

async function downloadPdf() {
    isDownloadingPdf.value = true;

    try {
        const pdf = new jsPDF({
            orientation: "portrait",
            unit: "mm",
            format: "a4",
        });

        const pageWidth = 210;
        const pageHeight = 297;
        const margin = 12;
        const contentWidth = pageWidth - margin * 2; // 186mm

        // 1. HEADER BOX (X: 12, Y: 10, W: 186, H: 20)
        pdf.setDrawColor(15, 23, 42); // slate-900
        pdf.setLineWidth(0.4);
        pdf.rect(margin, 10, contentWidth, 20);

        // Box 1: Logo (X: 12 to 34, W: 22mm)
        pdf.line(34, 10, 34, 30);
        try {
            const logoData = await getBase64ImageFromUrl(
                "/images/logo/BGN_LOGO_MAIN.png",
            );
            if (logoData) {
                pdf.addImage(logoData, "PNG", margin + 3.5, 12.5, 15, 15);
            }
        } catch (e) {
            console.warn("Logo loading skipped:", e);
        }

        // Box 2: Center Titles (X: 34 to 125, W: 91mm)
        pdf.line(125, 10, 125, 30);
        const centerBoxMidX = 34 + 91 / 2; // 79.5mm
        const sppgName = (
            unitSppgNama.value || "BADAN GIZI NASIONAL"
        ).toUpperCase();

        pdf.setFont("helvetica", "bold");
        if (sppgName.length > 40) {
            pdf.setFontSize(7.5);
        } else if (sppgName.length > 25) {
            pdf.setFontSize(8.5);
        } else {
            pdf.setFontSize(9.5);
        }
        pdf.setTextColor(15, 23, 42);
        pdf.text(sppgName, centerBoxMidX, 18.5, { align: "center" });

        pdf.setFontSize(8);
        pdf.text("FORMULIR SURVEI HARGA PASAR", centerBoxMidX, 24.5, {
            align: "center",
        });

        // Box 3: Right Metadata Grid (X: 125 to 198, W: 73mm)
        pdf.line(125, 16.66, 198, 16.66);
        pdf.line(125, 23.33, 198, 23.33);

        const metaLabelX = 127;
        const metaColonX = 152;
        const metaValX = 155;

        pdf.setFontSize(7.5);

        // Row 1: No Dokumen
        pdf.setFont("helvetica", "bold");
        pdf.text("No. Dokumen", metaLabelX, 14.5);
        pdf.text(":", metaColonX, 14.5);
        pdf.setFont("helvetica", "normal");
        pdf.text(noDokumen.value || "-", metaValX, 14.5);

        // Row 2: Revisi
        pdf.setFont("helvetica", "bold");
        pdf.text("Revisi", metaLabelX, 21.2);
        pdf.text(":", metaColonX, 21.2);
        pdf.setFont("helvetica", "normal");
        pdf.text(
            revisi.value ? props.formatTanggalIndo(revisi.value) : "-",
            metaValX,
            21.2,
        );

        // Row 3: Tanggal Berlaku
        pdf.setFont("helvetica", "bold");
        pdf.text("Tanggal Berlaku", metaLabelX, 27.8);
        pdf.text(":", metaColonX, 27.8);
        pdf.setFont("helvetica", "normal");
        pdf.text(
            props.formatTanggalIndo(tanggalBerlaku.value) || "-",
            metaValX,
            27.8,
        );

        // 2. SUB-HEADER METADATA (Hari, Tanggal & Lokasi)
        pdf.setFont("helvetica", "bold");
        pdf.setFontSize(8);
        pdf.text("Hari, Tanggal", margin, 36);
        pdf.text(
            `:  ${getFormattedHariTanggal(tanggalSurvei.value)}`,
            margin + 25,
            36,
        );

        pdf.text("Lokasi survei", margin, 40.5);
        pdf.text(`:  ${lokasiSurvei.value || "-"}`, margin + 25, 40.5);

        // 3. TABLE BODY (Categorized)
        const tableRows = [];
        categories.forEach((cat) => {
            // Category Header Row
            tableRows.push([
                {
                    content: cat.label.toUpperCase(),
                    colSpan: 7,
                    styles: {
                        halign: "center",
                        fillColor: [226, 232, 240], // slate-200
                        textColor: [15, 23, 42],
                        fontStyle: "bold",
                        fontSize: 7.5,
                        cellPadding: 1.5,
                    },
                },
            ]);

            const catItems = items.value.filter((i) => i.kategori === cat.key);
            catItems.forEach((it, idx) => {
                tableRows.push([
                    {
                        content: String(it.nomor || idx + 1),
                        styles: { halign: "center" },
                    },
                    {
                        content: it.nama_bahan || "-",
                        styles: { halign: "left", fontStyle: "bold" },
                    },
                    { content: it.satuan || "-", styles: { halign: "center" } },
                    {
                        content: it.harga ? props.formatRupiah(it.harga) : "-",
                        styles: { halign: "right", fontStyle: "bold" },
                    },
                    {
                        content: it.nama_toko || "-",
                        styles: { halign: "left" },
                    },
                    { content: it.kontak || "-", styles: { halign: "left" } },
                    {
                        content: it.keterangan || "-",
                        styles: { halign: "left" },
                    },
                ]);
            });
        });

        autoTable(pdf, {
            startY: 43.5,
            margin: {
                left: margin,
                right: margin,
                top: margin,
                bottom: margin,
            },
            head: [
                [
                    { content: "No.", styles: { halign: "center" } },
                    { content: "Nama Bahan Baku", styles: { halign: "left" } },
                    { content: "Satuan", styles: { halign: "center" } },
                    { content: "Harga", styles: { halign: "right" } },
                    {
                        content: "Nama Toko/ Supplier",
                        styles: { halign: "left" },
                    },
                    { content: "Kontak", styles: { halign: "left" } },
                    { content: "Keterangan", styles: { halign: "left" } },
                ],
            ],
            body: tableRows,
            theme: "plain",
            styles: {
                font: "helvetica",
                fontSize: 7,
                cellPadding: 1.2,
                lineColor: [15, 23, 42],
                lineWidth: 0.25,
                textColor: [15, 23, 42],
                overflow: "linebreak",
            },
            headStyles: {
                fillColor: [241, 245, 249], // slate-100
                textColor: [15, 23, 42],
                fontStyle: "bold",
                fontSize: 7.5,
                cellPadding: 1.6,
                lineColor: [15, 23, 42],
                lineWidth: 0.35,
            },
            columnStyles: {
                0: { cellWidth: 9 },
                1: { cellWidth: 45 },
                2: { cellWidth: 15 },
                3: { cellWidth: 26 },
                4: { cellWidth: 34 },
                5: { cellWidth: 26 },
                6: { cellWidth: 31 },
            },
        });

        // 4. SIGNATURE SECTION (3 COLUMNS)
        let finalY = pdf.lastAutoTable ? pdf.lastAutoTable.finalY : 150;

        // Ensure enough space for signature block (needs ~38mm)
        if (finalY + 38 > pageHeight - margin) {
            pdf.addPage();
            finalY = margin + 5;
        }

        const sigY = finalY + 8;
        const col1MidX = margin + 30; // 42mm
        const col2MidX = margin + 93; // 105mm
        const col3MidX = margin + 156; // 168mm

        pdf.setFontSize(7.5);
        pdf.setTextColor(51, 65, 85); // slate-700

        // Top labels
        pdf.setFont("helvetica", "normal");
        pdf.text("Petugas Survei I,", col1MidX, sigY, { align: "center" });
        pdf.text("Petugas Survei II,", col2MidX, sigY, { align: "center" });
        pdf.text("Mengetahui,", col3MidX, sigY, { align: "center" });

        pdf.setFont("helvetica", "bold");
        pdf.text("Kepala SPPG", col1MidX, sigY + 4, { align: "center" });
        pdf.text("Pengawas Keuangan", col2MidX, sigY + 4, { align: "center" });
        pdf.text("Kepala Pasar", col3MidX, sigY + 4, { align: "center" });

        // Names (Underlined, bold)
        const nameY = sigY + 22;
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(15, 23, 42);

        const name1 =
            petugasSurvei1.value ||
            userFullNameFromDb.value ||
            "( ........................................... )";
        const name2 =
            petugasSurvei2.value ||
            "( ........................................... )";
        const name3 =
            mengetahuiKepalaPasar.value ||
            "( ........................................... )";

        pdf.text(name1, col1MidX, nameY, { align: "center" });
        // Underline 1
        const w1 = pdf.getTextWidth(name1);
        pdf.setLineWidth(0.3);
        pdf.line(
            col1MidX - w1 / 2,
            nameY + 0.8,
            col1MidX + w1 / 2,
            nameY + 0.8,
        );

        pdf.text(name2, col2MidX, nameY, { align: "center" });
        // Underline 2
        const w2 = pdf.getTextWidth(name2);
        pdf.line(
            col2MidX - w2 / 2,
            nameY + 0.8,
            col2MidX + w2 / 2,
            nameY + 0.8,
        );

        pdf.text(name3, col3MidX, nameY, { align: "center" });
        // Underline 3
        const w3 = pdf.getTextWidth(name3);
        pdf.line(
            col3MidX - w3 / 2,
            nameY + 0.8,
            col3MidX + w3 / 2,
            nameY + 0.8,
        );

        const safeDocNo = (noDokumen.value || "SURVEI-HARGA").replace(
            /[/\\?%*:|"<>]/g,
            "_",
        );
        pdf.save(`Survei_Harga_Pasar_${safeDocNo}.pdf`);
    } catch (e) {
        console.error("Gagal membuat file PDF vektor:", e);
        alert(
            "Gagal mengunduh file PDF. Anda dapat menggunakan fitur Cetak / PDF dari peramban.",
        );
    } finally {
        isDownloadingPdf.value = false;
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- SUCCESS TOAST / BANNER -->
        <div
            v-if="showSuccessNotice"
            class="flex items-center justify-between p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl shadow-xs animate-in fade-in slide-in-from-top-2"
        >
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg">
                    <CheckCircle2 class="w-5 h-5" />
                </div>
                <div>
                    <p class="font-bold text-sm">
                        Formulir Survei Harga Pasar Berhasil Disimpan ke
                        Database!
                    </p>
                    <p class="text-xs text-emerald-600">
                        Data telah diarsipkan di basis data unit SPPG dan siap
                        dicetak / dijadikan acuan pengadaan.
                    </p>
                </div>
            </div>
            <button
                @click="showSuccessNotice = false"
                class="p-1 rounded-lg text-emerald-500 hover:bg-emerald-100"
            >
                <X class="w-4 h-4" />
            </button>
        </div>

        <!-- ======================= TOP HEADER BANNER (NO TABS) ======================= -->
        <div
            class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3"
        >
            <div
                class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-xs"
            >
                <ClipboardList class="w-6 h-6" />
            </div>
            <div>
                <h2
                    class="text-xl font-extrabold text-slate-900 tracking-tight"
                >
                    Survei Harga Pasar
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Pemantauan & telaah harga bahan baku pasar lokal untuk
                    penentuan estimasi food cost SPPG.
                </p>
            </div>
        </div>

        <!-- ======================= VIEW 1: DAFTAR RIWAYAT ARSIP (TAMPILAN AWAL) ======================= -->
        <div v-show="currentView === 'list'" class="space-y-6">
            <Card class="border-slate-200/80 shadow-xs">
                <CardHeader
                    class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-9 h-9 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center"
                        >
                            <History class="w-5 h-5" />
                        </div>
                        <div>
                            <CardTitle
                                class="text-base font-bold text-slate-800"
                            >
                                Daftar Riwayat Arsip Survei Harga Pasar
                            </CardTitle>
                            <CardDescription class="text-xs text-slate-500">
                                Seluruh dokumen survei harga yang telah disimpan
                                di database unit SPPG.
                            </CardDescription>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <!-- History Search Input -->
                        <div class="relative w-full sm:w-64">
                            <Search
                                class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"
                            />
                            <input
                                type="text"
                                v-model="historySearchQuery"
                                placeholder="Cari no. dokumen / lokasi..."
                                class="w-full text-xs pl-9 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                            <button
                                v-if="historySearchQuery"
                                type="button"
                                @click="historySearchQuery = ''"
                                class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                            >
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <Button
                            variant="default"
                            size="sm"
                            @click="startNewSurvey()"
                            class="bg-primary hover:bg-primary/90 text-white font-bold gap-1.5 text-xs h-9 px-3.5 rounded-xl cursor-pointer shrink-0 shadow-xs"
                            title="Buat Formulir Survei Baru"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Buat Survei Baru</span>
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="pt-4">
                    <!-- Local Draft Alert Card (if any unsaved draft exists) -->
                    <div
                        v-if="localDraftItem"
                        class="mb-4 p-4 bg-amber-50/70 border border-amber-200 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="p-2 bg-amber-100 text-amber-700 rounded-lg"
                            >
                                <Clock class="w-4 h-4" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-bold text-xs text-amber-900"
                                    >
                                        Draft Lokal Tersimpan (Belum Final)
                                    </span>
                                    <Badge
                                        variant="outline"
                                        class="text-[10px] bg-amber-100/60 text-amber-800 border-amber-300"
                                    >
                                        Penyimpanan Lokal:
                                        {{
                                            localDraftItem.savedAt || "Terbaru"
                                        }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-amber-800 mt-0.5">
                                    Dokumen:
                                    <strong>{{
                                        localDraftItem.noDokumen || "Draft Baru"
                                    }}</strong>
                                    •
                                    {{ (localDraftItem.items || []).length }}
                                    Bahan
                                    <span v-if="localDraftItem.lokasiSurvei">
                                        • Lokasi:
                                        {{ localDraftItem.lokasiSurvei }}</span
                                    >
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <Button
                                variant="default"
                                size="sm"
                                @click="resumeLocalDraft(true)"
                                class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold gap-1.5 h-9 px-3 rounded-xl cursor-pointer shadow-xs"
                            >
                                <Edit3 class="w-4 h-4" />
                                <span>Lanjutkan Draft</span>
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openClearDraftModal"
                                class="text-slate-400 hover:text-red-600 hover:bg-red-50 h-9 w-9 p-0 flex items-center justify-center rounded-xl cursor-pointer"
                                title="Buang draft lokal"
                            >
                                <Trash2 class="w-4.5 h-4.5" />
                            </Button>
                        </div>
                    </div>

                    <!-- Empty State (No DB records and no draft) -->
                    <div
                        v-if="
                            props.surveiHargaList.length === 0 &&
                            !localDraftItem
                        "
                        class="py-16 text-center space-y-3"
                    >
                        <div
                            class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto"
                        >
                            <ClipboardList class="w-7 h-7" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-700">
                                Belum Ada Riwayat Survei Tersimpan
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Mulai buat formulir survei harga pasar pertama
                                Anda untuk unit SPPG.
                            </p>
                        </div>
                        <Button
                            variant="default"
                            size="sm"
                            @click="startNewSurvey()"
                            class="bg-primary text-white text-xs font-bold gap-1.5 h-9 px-4 rounded-xl mt-2 cursor-pointer shadow-xs"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Buat Survei Baru</span>
                        </Button>
                    </div>

                    <!-- State when draft exists but no saved surveys in DB yet -->
                    <div
                        v-else-if="props.surveiHargaList.length === 0"
                        class="py-8 text-center bg-slate-50/50 rounded-xl border border-dashed border-slate-200/90 space-y-1.5"
                    >
                        <ClipboardList class="w-5 h-5 text-slate-300 mx-auto" />
                        <p class="text-xs font-semibold text-slate-600">
                            Belum ada dokumen survei yang tersimpan di database
                        </p>
                        <p class="text-[11px] text-slate-400">
                            Lanjutkan draft aktif di atas untuk menyimpan ke
                            database atau buat survei baru.
                        </p>
                    </div>

                    <!-- Empty Filtered History State (when searching and 0 matches found) -->
                    <div
                        v-else-if="
                            historySearchQuery &&
                            filteredHistoryList.length === 0
                        "
                        class="py-12 text-center bg-slate-50/50 rounded-xl border border-slate-200 space-y-2"
                    >
                        <Search class="w-6 h-6 text-slate-400 mx-auto" />
                        <p class="text-xs font-bold text-slate-700">
                            Tidak ditemukan arsip survei dengan kata kunci "{{
                                historySearchQuery
                            }}"
                        </p>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="historySearchQuery = ''"
                            class="text-xs font-bold mt-1 cursor-pointer"
                        >
                            Hapus Pencarian
                        </Button>
                    </div>

                    <!-- History List Cards -->
                    <div v-else class="space-y-3">
                        <div
                            v-for="s in filteredHistoryList"
                            :key="s.uid || s.id"
                            class="p-4 bg-white hover:bg-slate-50/80 border border-slate-200/90 rounded-xl transition-all shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                        >
                            <div class="space-y-1.5 min-w-0">
                                <div
                                    class="flex items-center gap-2.5 flex-wrap"
                                >
                                    <span
                                        class="font-extrabold text-sm text-slate-900"
                                    >
                                        {{ s.no_dokumen || "SURVEI-HARGA" }}
                                    </span>
                                    <Badge
                                        variant="outline"
                                        class="text-xs font-bold bg-blue-50 text-blue-700 border-blue-200"
                                    >
                                        {{
                                            getFormattedHariTanggal(
                                                s.tanggal_survei,
                                            )
                                        }}
                                    </Badge>
                                    <Badge
                                        :class="[
                                            'text-xs font-bold',
                                            s.status === 'Selesai'
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                : 'bg-amber-50 text-amber-700 border-amber-200',
                                        ]"
                                    >
                                        {{ s.status || "Draft" }}
                                    </Badge>
                                </div>

                                <div
                                    class="flex items-center gap-3 text-xs text-slate-600 flex-wrap"
                                >
                                    <span
                                        class="flex items-center gap-1 font-medium"
                                    >
                                        <MapPin
                                            class="w-3.5 h-3.5 text-slate-400"
                                        />
                                        {{ s.lokasi_survei || "Lokasi Pasar" }}
                                    </span>
                                    <span class="text-slate-300">•</span>
                                    <span
                                        >{{ s.total_items }} Item
                                        Komoditas</span
                                    >
                                    <span class="text-slate-300">•</span>
                                    <span
                                        >Kepala SPPG:
                                        <strong>{{
                                            s.petugas_survei || "-"
                                        }}</strong></span
                                    >
                                    <span
                                        v-if="s.petugas_survei_2"
                                        class="text-slate-300"
                                        >•</span
                                    >
                                    <span v-if="s.petugas_survei_2"
                                        >Pengawas:
                                        <strong>{{
                                            s.petugas_survei_2
                                        }}</strong></span
                                    >
                                </div>
                            </div>

                            <!-- Actions (Uniform Icons) -->
                            <div class="flex items-center gap-2 shrink-0">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="loadSurveyData(s)"
                                    class="h-9 w-9 p-0 flex items-center justify-center text-primary bg-primary/5 hover:bg-primary/15 border-primary/25 rounded-xl cursor-pointer shadow-2xs"
                                    title="Buka / Edit Formulir Survei"
                                >
                                    <Edit3 class="w-4.5 h-4.5" />
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="duplicateSurvey(s)"
                                    class="h-9 w-9 p-0 flex items-center justify-center text-amber-700 bg-amber-50/70 hover:bg-amber-100 border-amber-200 rounded-xl cursor-pointer shadow-2xs"
                                    title="Duplikat sebagai survei baru"
                                >
                                    <Sparkles class="w-4.5 h-4.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openDeleteModal(s)"
                                    class="h-9 w-9 p-0 flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl cursor-pointer"
                                    title="Hapus survei dari database"
                                >
                                    <Trash2 class="w-4.5 h-4.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ======================= VIEW 2: FORMULIR & PRATINJAU SURVEI ======================= -->
        <div v-show="currentView === 'form'" class="space-y-6">
            <!-- SUB-VIEW CONTROLS & ACTION BUTTONS -->
            <div
                class="bg-white p-3 sm:p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3"
            >
                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Kembali ke Riwayat (Uniform Icon) -->
                    <Button
                        variant="outline"
                        size="sm"
                        @click="handleBackNavigation"
                        class="border-slate-200 text-slate-700 hover:bg-slate-100 h-9 w-9 p-0 flex items-center justify-center rounded-xl cursor-pointer shrink-0 shadow-2xs"
                        title="Kembali ke Riwayat Arsip"
                    >
                        <ArrowLeft class="w-4.5 h-4.5 text-slate-700" />
                    </Button>

                    <!-- Auto-Save Status Badge -->
                    <span
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all',
                            autoSaveStatus === 'saved'
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                : autoSaveStatus === 'saving'
                                  ? 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse'
                                  : autoSaveStatus === 'error'
                                    ? 'bg-red-50 text-red-700 border-red-200'
                                    : 'bg-slate-50 text-slate-600 border-slate-200',
                        ]"
                    >
                        <CheckCheck
                            v-if="autoSaveStatus === 'saved'"
                            class="w-3.5 h-3.5 text-emerald-600"
                        />
                        <AlertCircle
                            v-else-if="autoSaveStatus === 'error'"
                            class="w-3.5 h-3.5 text-red-600"
                        />
                        <Clock v-else class="w-3.5 h-3.5 text-amber-600" />
                        <span>
                            {{
                                formId
                                    ? autoSaveStatus === "saved"
                                        ? `Tersimpan di Database (${lastSavedTime || "Baru"})`
                                        : autoSaveStatus === "saving"
                                          ? "Menyimpan ke Database..."
                                          : autoSaveStatus === "error"
                                            ? "Gagal Menyimpan ke Database"
                                            : "Tersimpan di Database"
                                    : autoSaveStatus === "saved"
                                      ? `Draft Tersimpan Lokal (${lastSavedTime || "Baru"})`
                                      : autoSaveStatus === "saving"
                                        ? "Menyimpan Draft Lokal..."
                                        : autoSaveStatus === "error"
                                          ? "Gagal Menyimpan Draft"
                                          : "Draft Baru"
                            }}
                        </span>
                    </span>

                    <!-- Sub-view toggle (Form Input vs Pratinjau Dokumen - Uniform Icons) -->
                    <div
                        class="inline-flex bg-slate-100 p-0.5 rounded-xl border border-slate-200/80 gap-0.5"
                    >
                        <button
                            type="button"
                            @click="formSubView = 'form'"
                            :class="[
                                'flex items-center justify-center h-8 w-8 rounded-lg transition-all cursor-pointer',
                                formSubView === 'form'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-500 hover:text-slate-900',
                            ]"
                            title="Formulir Input"
                        >
                            <Edit3 class="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            @click="formSubView = 'preview'"
                            :class="[
                                'flex items-center justify-center h-8 w-8 rounded-lg transition-all cursor-pointer',
                                formSubView === 'preview'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-500 hover:text-slate-900',
                            ]"
                            title="Pratinjau Dokumen Cetak"
                        >
                            <Eye class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Action Buttons in Form View (Uniform Icons) -->
                <div class="flex items-center gap-2 shrink-0">
                    <!-- Smart Auto-Fill Trigger (Icon) -->
                    <Button
                        variant="outline"
                        size="sm"
                        @click="showSmartFillModal = true"
                        class="bg-amber-50 hover:bg-amber-100 text-amber-900 border-amber-200 h-9 w-9 p-0 flex items-center justify-center rounded-xl cursor-pointer shadow-2xs"
                        title="Bantuan Pengisian Cepat (Smart Auto-Fill)"
                    >
                        <Wand2 class="w-4.5 h-4.5 text-amber-600" />
                    </Button>

                    <!-- Reset / New Button (Icon) -->
                    <Button
                        variant="outline"
                        size="sm"
                        @click="startNewSurvey()"
                        class="border-slate-200 text-slate-700 hover:bg-slate-50 h-9 w-9 p-0 flex items-center justify-center rounded-xl cursor-pointer shadow-2xs"
                        title="Reset Formulir / Buat Baru"
                    >
                        <RotateCcw class="w-4.5 h-4.5 text-slate-600" />
                    </Button>

                    <!-- Download PDF Button (Icon) -->
                    <Button
                        variant="outline"
                        size="sm"
                        @click="downloadPdf"
                        :disabled="isDownloadingPdf"
                        class="bg-red-50 hover:bg-red-100 text-red-700 border-red-200 h-9 w-9 p-0 flex items-center justify-center rounded-xl cursor-pointer disabled:opacity-50 shadow-2xs"
                        :title="
                            isDownloadingPdf
                                ? 'Mengunduh Dokumen PDF...'
                                : 'Download Dokumen PDF'
                        "
                    >
                        <Download class="w-4.5 h-4.5 text-red-600" />
                    </Button>

                    <!-- Save to Database Button (Icon) -->
                    <Button
                        variant="default"
                        size="sm"
                        @click="saveSurvey"
                        :disabled="isSaving"
                        class="bg-primary hover:bg-primary/90 text-white shadow-xs h-9 w-9 p-0 flex items-center justify-center rounded-xl cursor-pointer disabled:opacity-50"
                        :title="
                            isSaving
                                ? 'Menyimpan...'
                                : formId
                                  ? 'Perbarui Dokumen di Database'
                                  : 'Simpan ke Database'
                        "
                    >
                        <Save class="w-4.5 h-4.5" />
                    </Button>
                </div>
            </div>

            <!-- SUB-VIEW 1.A: FORM INPUT -->
            <div v-show="formSubView === 'form'" class="space-y-6">
                <!-- 1. IDENTITAS & HEADER FORMULIR CARD -->
                <Card class="border-slate-200/80 shadow-xs">
                    <CardHeader class="pb-3 border-b border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Building2 class="w-4 h-4 text-primary" />
                                <CardTitle
                                    class="text-base font-bold text-slate-800"
                                >
                                    Identitas Dokumen & Lokasi Survei
                                </CardTitle>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="formId"
                                    class="text-xs font-semibold px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-md"
                                >
                                    Edit ID: #{{ formId }}
                                </span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-4">
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
                        >
                            <!-- Nama Unit SPPG (Otomatis dari Database) -->
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-700 mb-1"
                                >
                                    Unit SPPG (Otomatis Database)
                                </label>
                                <input
                                    type="text"
                                    :value="unitSppgNama || 'Unit SPPG'"
                                    readonly
                                    class="w-full text-xs font-bold bg-slate-100/90 border border-slate-200 rounded-lg px-3 py-2 text-slate-900 focus:outline-hidden cursor-not-allowed select-all"
                                    title="Nama Unit SPPG diambil langsung dari basis data SPPG aktif"
                                />
                            </div>

                            <!-- No. Dokumen -->
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-700 mb-1"
                                >
                                    No. Dokumen
                                </label>
                                <input
                                    type="text"
                                    v-model="noDokumen"
                                    placeholder="Contoh: 001/SPPG/KEU/SHP/IX/2026"
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                            </div>

                            <!-- Revisi (Berupa tanggal, bisa dikosongkan) -->
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-700 mb-1"
                                >
                                    Revisi (Tanggal / Kosongkan)
                                </label>
                                <input
                                    type="date"
                                    v-model="revisi"
                                    placeholder="Pilih tanggal revisi atau kosongkan"
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                            </div>

                            <!-- Tanggal Berlaku -->
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-700 mb-1"
                                >
                                    Tanggal Berlaku
                                </label>
                                <input
                                    type="date"
                                    v-model="tanggalBerlaku"
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                            </div>

                            <!-- Tanggal Pelaksanaan Survei (Hari otomatis dari tanggal ini) -->
                            <div class="lg:col-span-2">
                                <div
                                    class="flex items-center justify-between mb-1"
                                >
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                    >
                                        Tanggal Survei Pasar
                                    </label>
                                    <span
                                        class="text-[11px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md"
                                    >
                                        Hari:
                                        {{
                                            getFormattedHariTanggal(
                                                tanggalSurvei,
                                            ).split(",")[0] || "-"
                                        }}
                                    </span>
                                </div>
                                <input
                                    type="date"
                                    v-model="tanggalSurvei"
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                            </div>

                            <!-- Lokasi Survei (Kosongkan) -->
                            <div class="lg:col-span-2">
                                <label
                                    class="block text-xs font-bold text-slate-700 mb-1"
                                >
                                    Lokasi Survei Pasar
                                </label>
                                <input
                                    type="text"
                                    v-model="lokasiSurvei"
                                    list="lokasiPresets"
                                    placeholder="Ketik lokasi pasar (contoh: Pasar Induk Tradisional / Pasar Pagi)..."
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                                <datalist id="lokasiPresets">
                                    <option value="Pasar Induk Tradisional" />
                                    <option value="Pasar Pagi Terdekat" />
                                    <option value="Pasar Sentral Komoditas" />
                                    <option value="Grosir Pangan Daerah" />
                                    <option
                                        value="Sentra Peternak & Tani Lokal"
                                    />
                                </datalist>
                            </div>

                            <!-- Petugas Survei 1: Kepala SPPG (Otomatis dari Database Pengguna) -->
                            <div>
                                <div
                                    class="flex items-center justify-between mb-1"
                                >
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                    >
                                        Petugas I: Kepala SPPG
                                    </label>
                                    <span
                                        class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.2 rounded"
                                    >
                                        Otomatis Database
                                    </span>
                                </div>
                                <input
                                    type="text"
                                    v-model="petugasSurvei1"
                                    :placeholder="
                                        userFullNameFromDb || 'Nama Kepala SPPG'
                                    "
                                    class="w-full text-xs font-bold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-900 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                            </div>

                            <!-- Petugas Survei 2: Pengawas Keuangan (Manual) -->
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-700 mb-1"
                                >
                                    Petugas II: Pengawas Keuangan
                                </label>
                                <input
                                    type="text"
                                    v-model="petugasSurvei2"
                                    placeholder="Nama Pengawas Keuangan..."
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                            </div>

                            <!-- Mengetahui: Kepala Pasar (Kosongkan) -->
                            <div class="lg:col-span-2">
                                <label
                                    class="block text-xs font-bold text-slate-700 mb-1"
                                >
                                    Mengetahui: Kepala Pasar
                                </label>
                                <input
                                    type="text"
                                    v-model="mengetahuiKepalaPasar"
                                    placeholder="Nama Kepala Pasar (kosongkan jika belum ada)..."
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 2. STATS & QUICK FILTER BAR -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div
                        class="p-4 bg-white border border-slate-200/80 rounded-xl shadow-2xs"
                    >
                        <span class="text-slate-400 text-xs font-semibold block"
                            >Total Komoditas</span
                        >
                        <span
                            class="text-xl font-extrabold text-slate-800 mt-1 block"
                        >
                            {{ surveyStats.total }} Item
                        </span>
                    </div>
                    <div
                        class="p-4 bg-white border border-slate-200/80 rounded-xl shadow-2xs"
                    >
                        <span class="text-slate-400 text-xs font-semibold block"
                            >Harga Terisi</span
                        >
                        <div class="flex items-center gap-2 mt-1">
                            <span
                                class="text-xl font-extrabold text-emerald-600"
                            >
                                {{ surveyStats.filledPrice }} /
                                {{ surveyStats.total }}
                            </span>
                            <span class="text-[11px] font-bold text-slate-500">
                                ({{ surveyStats.percentComplete }}%)
                            </span>
                        </div>
                    </div>
                    <div
                        class="p-4 bg-white border border-slate-200/80 rounded-xl shadow-2xs"
                    >
                        <span class="text-slate-400 text-xs font-semibold block"
                            >Toko / Vendor Terisi</span
                        >
                        <span
                            class="text-xl font-extrabold text-blue-600 mt-1 block"
                        >
                            {{ surveyStats.filledStore }} Toko
                        </span>
                    </div>
                    <div
                        class="p-4 bg-white border border-slate-200/80 rounded-xl shadow-2xs"
                    >
                        <span class="text-slate-400 text-xs font-semibold block"
                            >Total Akumulasi Survei</span
                        >
                        <span
                            class="text-xl font-extrabold text-slate-900 mt-1 block truncate"
                        >
                            {{ props.formatRupiah(surveyStats.totalPrice) }}
                        </span>
                    </div>
                </div>

                <!-- 3. FILTER & SEARCH BAR -->
                <div
                    class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs"
                >
                    <!-- Category Tabs -->
                    <div
                        class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0"
                    >
                        <button
                            type="button"
                            @click="selectedCategory = 'all'"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-colors cursor-pointer',
                                selectedCategory === 'all'
                                    ? 'bg-primary text-white shadow-xs'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                            ]"
                        >
                            Semua Kategori ({{
                                searchQuery.trim()
                                    ? totalFilteredItemsCount
                                    : items.length
                            }})
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat.key"
                            type="button"
                            @click="selectedCategory = cat.key"
                            :class="[
                                'px-2.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-colors cursor-pointer',
                                selectedCategory === cat.key
                                    ? 'bg-slate-900 text-white shadow-xs'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                            ]"
                        >
                            {{ cat.label }} ({{
                                getFilteredItemsByCategory(cat.key).length
                            }})
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72 shrink-0">
                        <Search
                            class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400"
                        />
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari realtime nama bahan, toko, kontak..."
                            class="w-full text-xs pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''"
                            class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                            title="Hapus pencarian"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- 4. TABEL FORMULIR INPUT ITEM (SESUAI GAMBAR DOKUMEN ASLI) -->
                <div class="space-y-6">
                    <div
                        v-for="cat in categories"
                        :key="cat.key"
                        v-show="
                            (selectedCategory === 'all' ||
                                selectedCategory === cat.key) &&
                            (!searchQuery.trim() ||
                                getFilteredItemsByCategory(cat.key).length > 0)
                        "
                        class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden"
                    >
                        <!-- Category Header -->
                        <div
                            class="bg-slate-50/90 px-4 py-3 border-b border-slate-200 flex items-center justify-between"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-2.5 h-2.5 rounded-full bg-primary"
                                ></span>
                                <h3
                                    class="font-extrabold text-sm uppercase tracking-wider text-slate-800"
                                >
                                    {{ cat.label }}
                                </h3>
                                <Badge
                                    :class="[
                                        'text-[11px] font-bold',
                                        cat.badgeColor,
                                    ]"
                                >
                                    {{
                                        getFilteredItemsByCategory(cat.key)
                                            .length
                                    }}
                                    Item
                                </Badge>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="addItemToCategory(cat.key)"
                                class="text-xs font-bold text-primary hover:bg-primary/10 gap-1.5 h-8 px-3 rounded-lg cursor-pointer"
                                title="Tambah Bahan Baku"
                            >
                                <Plus class="w-4 h-4" />
                                <span>Tambah Bahan</span>
                            </Button>
                        </div>

                        <!-- Items Table -->
                        <div class="overflow-x-auto">
                            <table
                                class="w-full text-left border-collapse text-xs"
                            >
                                <thead>
                                    <tr
                                        class="bg-slate-100/75 text-slate-700 font-bold border-b border-slate-200 select-none"
                                    >
                                        <th
                                            class="py-2.5 px-3 w-12 text-center"
                                        >
                                            No.
                                        </th>
                                        <th class="py-2.5 px-3 min-w-[200px]">
                                            Nama Bahan Baku
                                        </th>
                                        <th class="py-2.5 px-3 min-w-[110px]">
                                            Satuan
                                        </th>
                                        <th class="py-2.5 px-3 min-w-[140px]">
                                            Harga (Rp)
                                        </th>
                                        <th class="py-2.5 px-3 min-w-[200px]">
                                            Nama Toko / Supplier
                                        </th>
                                        <th class="py-2.5 px-3 min-w-[150px]">
                                            Kontak (Telp/WA)
                                        </th>
                                        <th class="py-2.5 px-3 min-w-[180px]">
                                            Keterangan
                                        </th>
                                        <th
                                            class="py-2.5 px-2 w-12 text-center"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="(
                                            item, idx
                                        ) in getFilteredItemsByCategory(
                                            cat.key,
                                        )"
                                        :key="item.id || idx"
                                        class="hover:bg-slate-50/80 transition-colors"
                                    >
                                        <!-- No -->
                                        <td
                                            class="py-2 px-3 text-center font-bold text-slate-500"
                                        >
                                            {{ item.nomor || idx + 1 }}
                                        </td>

                                        <!-- Nama Bahan Baku -->
                                        <td class="py-2 px-3">
                                            <input
                                                type="text"
                                                v-model="item.nama_bahan"
                                                placeholder="Ketik nama bahan..."
                                                class="w-full text-xs font-bold text-slate-800 bg-transparent border-0 border-b border-transparent hover:border-slate-300 focus:border-primary focus:bg-white focus:ring-0 px-1 py-1 rounded"
                                            />
                                        </td>

                                        <!-- Satuan (Dropdown Pilihan Singkatan) -->
                                        <td class="py-2 px-3">
                                            <select
                                                v-model="item.satuan"
                                                class="w-full text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-md px-2 py-1 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            >
                                                <option value="">
                                                    - Satuan -
                                                </option>
                                                <option
                                                    v-for="s in satuanOptions"
                                                    :key="s.value"
                                                    :value="s.value"
                                                >
                                                    {{ s.value }}
                                                </option>
                                            </select>
                                        </td>

                                        <!-- Harga (Format Titik Nominal Otomatis) -->
                                        <td class="py-2 px-3">
                                            <div class="relative">
                                                <span
                                                    class="absolute left-2 top-1.5 text-[11px] font-bold text-slate-400"
                                                    >Rp</span
                                                >
                                                <input
                                                    type="text"
                                                    inputmode="numeric"
                                                    :value="
                                                        formatNominalInput(
                                                            item.harga,
                                                        )
                                                    "
                                                    @input="
                                                        onHargaInput(
                                                            item,
                                                            $event,
                                                        )
                                                    "
                                                    placeholder="0"
                                                    class="w-full text-xs font-bold text-slate-900 bg-white border border-slate-200 rounded-md pl-8 pr-2 py-1 focus:ring-2 focus:ring-primary/20 focus:border-primary text-right"
                                                />
                                            </div>
                                        </td>

                                        <!-- Nama Toko / Supplier (with suggestions) -->
                                        <td class="py-2 px-3">
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    v-model="item.nama_toko"
                                                    list="supplierListDatalist"
                                                    @input="
                                                        onSupplierSelect(
                                                            item,
                                                            $event.target.value,
                                                        )
                                                    "
                                                    placeholder="Pilih / ketik nama toko..."
                                                    class="w-full text-xs font-medium text-slate-800 bg-white border border-slate-200 rounded-md px-2.5 py-1 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                />
                                            </div>
                                        </td>

                                        <!-- Kontak (Hanya Angka) -->
                                        <td class="py-2 px-3">
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                :value="item.kontak"
                                                @input="
                                                    onKontakInput(item, $event)
                                                "
                                                @keypress="
                                                    (e) => {
                                                        if (
                                                            !/[0-9]/.test(e.key)
                                                        )
                                                            e.preventDefault();
                                                    }
                                                "
                                                placeholder="08..."
                                                class="w-full text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-md px-2.5 py-1 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            />
                                        </td>

                                        <!-- Keterangan -->
                                        <td class="py-2 px-3">
                                            <input
                                                type="text"
                                                v-model="item.keterangan"
                                                placeholder="Keterangan / kualitas / grade..."
                                                class="w-full text-xs text-slate-600 bg-white border border-slate-200 rounded-md px-2 py-1 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            />
                                        </td>

                                        <!-- Delete Action -->
                                        <td class="py-2 px-2 text-center">
                                            <button
                                                type="button"
                                                @click="removeItem(item)"
                                                class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                                title="Hapus baris bahan"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Empty search result message -->
                    <div
                        v-if="
                            searchQuery.trim() && totalFilteredItemsCount === 0
                        "
                        class="p-10 text-center bg-white rounded-xl border border-slate-200 shadow-2xs space-y-3"
                    >
                        <div
                            class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto"
                        >
                            <Search class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800">
                                Tidak ada komoditas yang cocok dengan kata kunci
                                "{{ searchQuery }}"
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Coba cari dengan kata kunci lain atau pilih
                                kategori "Semua Kategori".
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="searchQuery = ''"
                            class="text-xs font-bold text-primary border-primary/30"
                        >
                            Reset Pencarian
                        </Button>
                    </div>

                    <!-- Datalist for registered suppliers -->
                    <datalist id="supplierListDatalist">
                        <option
                            v-for="supp in props.suppliers"
                            :key="supp.id"
                            :value="supp.nama_usaha"
                        >
                            {{ supp.jenis_supplier }} -
                            {{ supp.no_telp || "No Telp" }}
                        </option>
                    </datalist>
                </div>
            </div>

            <!-- SUB-VIEW 1.B: PRATINJAU DOKUMEN RESMI (SESUAI GAMBAR) -->
            <div v-show="formSubView === 'preview'" class="space-y-6">
                <!-- Toolbar for preview & actions (Prominent Icons) -->
                <div
                    class="bg-white p-3 sm:p-3.5 rounded-2xl border border-slate-200 flex items-center justify-between shadow-2xs print:hidden"
                >
                    <div class="flex items-center gap-2">
                        <Badge
                            variant="outline"
                            class="bg-emerald-50 text-emerald-700 border-emerald-200 font-bold text-xs px-2.5 py-1 rounded-lg"
                        >
                            Pratinjau Dokumen
                        </Badge>
                        <span
                            class="text-xs text-slate-500 hidden sm:inline font-medium"
                        >
                            Format tata letak presisi sesuai blangko survei
                            harga resmi SPPG.
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="formSubView = 'form'"
                            class="border-slate-200 text-slate-700 hover:bg-slate-100 h-9 w-9 p-0 flex items-center justify-center rounded-xl cursor-pointer shadow-2xs"
                            title="Kembali ke Formulir Input"
                        >
                            <Edit3 class="w-4.5 h-4.5" />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="downloadPdf"
                            :disabled="isDownloadingPdf"
                            class="bg-red-50 hover:bg-red-100 text-red-700 border-red-200 h-9 w-9 p-0 flex items-center justify-center rounded-xl cursor-pointer disabled:opacity-50 shadow-2xs"
                            :title="
                                isDownloadingPdf
                                    ? 'Mengunduh Dokumen PDF...'
                                    : 'Download Dokumen PDF'
                            "
                        >
                            <Download class="w-4.5 h-4.5 text-red-600" />
                        </Button>
                    </div>
                </div>

                <!-- OFFICIAL DOCUMENT PAPER SHEET -->
                <div
                    id="officialSurveiPaper"
                    class="bg-white mx-auto p-6 sm:p-10 border border-slate-300 shadow-lg rounded-sm text-slate-950 max-w-4xl font-sans print:shadow-none print:border-0 print:p-0 print:m-0 print:bg-white print:w-full print:min-h-screen"
                >
                    <!-- HEADER BOX WITH 3 PANELS (LOGO, TITLE, DOC NUMBER GRID) -->
                    <div class="border-2 border-slate-900 flex mb-3">
                        <!-- Box 1: Logo (Left, compact) -->
                        <div
                            class="border-r-2 border-slate-900 px-2.5 sm:px-3 py-1.5 flex items-center justify-center bg-white shrink-0"
                        >
                            <img
                                src="/images/logo/BGN_LOGO_MAIN.png"
                                alt="Logo BGN"
                                class="h-12 w-12 sm:h-14 sm:w-14 object-contain"
                            />
                        </div>

                        <!-- Box 2: Title & SPPG (Center, adapts dynamically without cutoff) -->
                        <div
                            class="flex-1 min-w-0 border-r-2 border-slate-900 px-2 sm:px-3 py-1.5 flex flex-col items-center justify-center text-center"
                        >
                            <span
                                class="font-extrabold uppercase tracking-normal text-slate-900 leading-tight text-center break-words max-w-full sppg-header-title"
                                :class="sppgTitleFontSizeClass"
                            >
                                {{ unitSppgNama }}
                            </span>
                            <span
                                class="font-bold text-[9.5px] sm:text-[10.5px] md:text-xs uppercase tracking-wider text-slate-800 mt-0.5 whitespace-nowrap"
                            >
                                FORMULIR SURVEI HARGA PASAR
                            </span>
                        </div>

                        <!-- Box 3: Meta details 3 rows (Right, wide enough to prevent overflow) -->
                        <div
                            class="w-[320px] sm:w-[335px] shrink-0 flex flex-col justify-between text-[11px] sm:text-xs meta-box-header"
                        >
                            <div
                                class="flex items-center border-b border-slate-900 py-1.5 px-3"
                            >
                                <span
                                    class="w-[95px] font-bold text-slate-800 shrink-0 whitespace-nowrap"
                                    >No. Dokumen</span
                                >
                                <span
                                    class="font-bold text-slate-900 mr-2 shrink-0"
                                    >:</span
                                >
                                <span
                                    class="font-semibold text-slate-950 whitespace-nowrap"
                                    >{{ noDokumen || "-" }}</span
                                >
                            </div>
                            <!-- Revisi: Show formatted date or '-' if empty -->
                            <div
                                class="flex items-center border-b border-slate-900 py-1.5 px-3"
                            >
                                <span
                                    class="w-[95px] font-bold text-slate-800 shrink-0 whitespace-nowrap"
                                    >Revisi</span
                                >
                                <span
                                    class="font-bold text-slate-900 mr-2 shrink-0"
                                    >:</span
                                >
                                <span
                                    class="font-semibold text-slate-950 whitespace-nowrap"
                                >
                                    {{
                                        revisi
                                            ? props.formatTanggalIndo(revisi)
                                            : "-"
                                    }}
                                </span>
                            </div>
                            <div class="flex items-center py-1.5 px-3">
                                <span
                                    class="w-[95px] font-bold text-slate-800 shrink-0 whitespace-nowrap"
                                    >Tanggal Berlaku</span
                                >
                                <span
                                    class="font-bold text-slate-900 mr-2 shrink-0"
                                    >:</span
                                >
                                <span
                                    class="font-semibold text-slate-950 whitespace-nowrap"
                                    >{{
                                        props.formatTanggalIndo(tanggalBerlaku)
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- SUB-HEADER (Hari, Tanggal & Lokasi Survei) -->
                    <div
                        class="mb-4 text-xs sm:text-sm font-semibold space-y-1"
                    >
                        <div class="flex items-center">
                            <span class="w-28 font-bold text-slate-900"
                                >Hari, Tanggal</span
                            >
                            <span class="text-slate-900"
                                >:
                                {{
                                    getFormattedHariTanggal(tanggalSurvei)
                                }}</span
                            >
                        </div>
                        <div class="flex items-center">
                            <span class="w-28 font-bold text-slate-900"
                                >Lokasi survei</span
                            >
                            <span class="text-slate-900"
                                >: {{ lokasiSurvei || "-" }}</span
                            >
                        </div>
                    </div>

                    <!-- OFFICIAL CATEGORIZED TABLE -->
                    <table
                        class="w-full border-2 border-slate-900 border-collapse text-[11px] sm:text-xs table-fixed"
                    >
                        <thead>
                            <tr
                                class="bg-slate-100 text-slate-900 font-bold border-b-2 border-slate-900 text-center"
                            >
                                <th
                                    class="border-r border-slate-900 py-1.5 px-2 w-[5%] text-center"
                                >
                                    No.
                                </th>
                                <th
                                    class="border-r border-slate-900 py-1.5 px-2 w-[25%] text-left"
                                >
                                    Nama Bahan Baku
                                </th>
                                <th
                                    class="border-r border-slate-900 py-1.5 px-2 w-[8%] text-center"
                                >
                                    Satuan
                                </th>
                                <th
                                    class="border-r border-slate-900 py-1.5 px-2 w-[14%] text-right"
                                >
                                    Harga
                                </th>
                                <th
                                    class="border-r border-slate-900 py-1.5 px-2 w-[18%] text-left"
                                >
                                    Nama Toko/ Supplier
                                </th>
                                <th
                                    class="border-r border-slate-900 py-1.5 px-2 w-[14%] text-left"
                                >
                                    Kontak
                                </th>
                                <th class="py-1.5 px-2 w-[16%] text-left">
                                    Keterangan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="cat in categories" :key="cat.key">
                                <!-- Category Header Row -->
                                <tr
                                    class="bg-slate-200/90 font-extrabold text-slate-900 border-y border-slate-900 uppercase"
                                >
                                    <td
                                        colspan="7"
                                        class="py-1 px-3 text-center tracking-widest text-[11px]"
                                    >
                                        {{ cat.label }}
                                    </td>
                                </tr>

                                <!-- Item Rows -->
                                <tr
                                    v-for="(item, idx) in items.filter(
                                        (i) => i.kategori === cat.key,
                                    )"
                                    :key="item.id || idx"
                                    class="border-b border-slate-900 hover:bg-slate-50/50"
                                >
                                    <td
                                        class="border-r border-slate-900 py-1 px-2 text-center font-medium"
                                    >
                                        {{ item.nomor || idx + 1 }}
                                    </td>
                                    <td
                                        class="border-r border-slate-900 py-1 px-2 font-bold break-words"
                                    >
                                        {{ item.nama_bahan || "-" }}
                                    </td>
                                    <td
                                        class="border-r border-slate-900 py-1 px-2 text-center"
                                    >
                                        {{ item.satuan || "-" }}
                                    </td>
                                    <td
                                        class="border-r border-slate-900 py-1 px-2 text-right font-bold whitespace-nowrap"
                                    >
                                        {{
                                            item.harga
                                                ? props.formatRupiah(item.harga)
                                                : "-"
                                        }}
                                    </td>
                                    <td
                                        class="border-r border-slate-900 py-1 px-2 font-medium break-words"
                                    >
                                        {{ item.nama_toko || "-" }}
                                    </td>
                                    <td
                                        class="border-r border-slate-900 py-1 px-2 font-medium break-words"
                                    >
                                        {{ item.kontak || "-" }}
                                    </td>
                                    <td
                                        class="py-1 px-2 text-slate-700 break-words"
                                    >
                                        {{ item.keterangan || "-" }}
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <!-- BOTTOM SIGNATURE AREA (3 COLUMNS: PETUGAS I, PETUGAS II, MENGETAHUI KEPALA PASAR) -->
                    <div
                        class="mt-8 pt-4 grid grid-cols-3 gap-4 text-center text-xs sm:text-sm font-semibold"
                    >
                        <!-- Col 1: Petugas Pelaksana Survei 1 (Kepala SPPG) -->
                        <div>
                            <p class="font-normal text-slate-700 mb-14">
                                Petugas Survei I,<br />
                                <strong>Kepala SPPG</strong>
                            </p>
                            <p class="font-bold text-slate-950 underline">
                                {{
                                    petugasSurvei1 ||
                                    userFullNameFromDb ||
                                    "(...........................................)"
                                }}
                            </p>
                        </div>

                        <!-- Col 2: Petugas Pelaksana Survei 2 (Pengawas Keuangan) -->
                        <div>
                            <p class="font-normal text-slate-700 mb-14">
                                Petugas Survei II,<br />
                                <strong>Pengawas Keuangan</strong>
                            </p>
                            <p class="font-bold text-slate-950 underline">
                                {{
                                    petugasSurvei2 ||
                                    "(...........................................)"
                                }}
                            </p>
                        </div>

                        <!-- Col 3: Mengetahui (Kepala Pasar) -->
                        <div>
                            <p class="font-normal text-slate-700 mb-14">
                                Mengetahui,<br />
                                <strong>Kepala Pasar</strong>
                            </p>
                            <p class="font-bold text-slate-950 underline">
                                {{
                                    mengetahuiKepalaPasar ||
                                    "(...........................................)"
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= MODAL: SMART AUTO-FILL ======================= -->
        <Modal
            :show="showSmartFillModal"
            @close="showSmartFillModal = false"
            max-width="lg"
        >
            <div class="p-6 space-y-5">
                <div
                    class="flex items-center justify-between pb-3 border-b border-slate-100"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-amber-100 text-amber-700">
                            <Wand2 class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-slate-900">
                                Bantuan Pengisian Cepat (Smart Auto-Fill)
                            </h3>
                            <p class="text-xs text-slate-500">
                                Isi otomatis toko, kontak supplier, dan
                                referensi harga komoditas
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showSmartFillModal = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Feature 1: Terapkan Supplier ke Kategori -->
                    <div
                        class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3"
                    >
                        <h4
                            class="text-xs font-bold text-slate-800 flex items-center gap-1.5"
                        >
                            <Store class="w-4 h-4 text-primary" />
                            <span
                                >1. Terapkan Supplier Terdaftar ke
                                Kategori</span
                            >
                        </h4>
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-600 mb-1"
                            >
                                Pilih Supplier / Toko
                            </label>
                            <select
                                v-model="smartVendorSupplierId"
                                class="w-full text-xs font-medium bg-white border border-slate-200 rounded-lg px-3 py-2"
                            >
                                <option value="">
                                    -- Pilih Supplier Rekanan --
                                </option>
                                <option
                                    v-for="supp in props.suppliers"
                                    :key="supp.id"
                                    :value="supp.id"
                                >
                                    {{ supp.nama_usaha }} ({{
                                        supp.jenis_supplier
                                    }}
                                    - {{ supp.no_telp || "No Telp" }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-600 mb-1"
                            >
                                Terapkan ke Kategori Bahan
                            </label>
                            <select
                                v-model="smartVendorTargetCategory"
                                class="w-full text-xs font-medium bg-white border border-slate-200 rounded-lg px-3 py-2"
                            >
                                <option value="all">Semua Kategori</option>
                                <option
                                    v-for="cat in categories"
                                    :key="cat.key"
                                    :value="cat.key"
                                >
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            @click="applySmartVendor"
                            :disabled="!smartVendorSupplierId"
                            class="w-full bg-primary text-white text-xs font-bold gap-1.5 mt-1 cursor-pointer"
                        >
                            <Check class="w-3.5 h-3.5" />
                            <span>Terapkan Vendor & Kontak</span>
                        </Button>
                    </div>

                    <!-- Feature 2: Muat Referensi Harga Acuan -->
                    <div
                        class="p-4 bg-amber-50/60 rounded-xl border border-amber-200 space-y-2.5"
                    >
                        <div class="flex items-center gap-2">
                            <Sparkles class="w-4 h-4 text-amber-600" />
                            <h4 class="text-xs font-bold text-amber-900">
                                2. Muat Referensi Harga Acuan Pasar Standar
                            </h4>
                        </div>
                        <p class="text-xs text-amber-800">
                            Mengisi otomatis harga perkiraan standar lokal pada
                            seluruh item yang masih kosong untuk mempercepat
                            pengisian survei.
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="applyReferencePrices"
                            class="w-full border-amber-300 text-amber-900 bg-white hover:bg-amber-100 text-xs font-bold gap-1.5 cursor-pointer"
                        >
                            <Sparkles class="w-3.5 h-3.5 text-amber-600" />
                            <span>Muat Harga Acuan Standar</span>
                        </Button>
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="showSmartFillModal = false"
                        class="text-xs font-bold cursor-pointer"
                    >
                        Tutup
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- MODAL KONFIRMASI KELUAR DARI SURVEI BARU -->
        <Modal
            :show="showNewSurveyBackModal"
            @close="showNewSurveyBackModal = false"
            maxWidth="md"
        >
            <div class="p-6 space-y-5">
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0"
                    >
                        <AlertCircle class="w-6 h-6 text-amber-600" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900">
                            Konfirmasi Keluar dari Formulir Baru
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Anda sedang mengisi formulir survei baru dan
                            terdapat data yang belum disimpan ke database.
                            Bagaimana Anda ingin memproses formulir ini?
                        </p>
                    </div>
                </div>

                <div class="space-y-2.5 pt-2">
                    <Button
                        type="button"
                        variant="default"
                        @click="saveAndExitNewSurvey"
                        :disabled="isSaving"
                        class="w-full bg-primary hover:bg-primary/90 text-white text-xs font-bold h-10 gap-2 cursor-pointer shadow-xs"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{
                            isSaving
                                ? "Menyimpan ke Database..."
                                : "Simpan sebagai Formulir Baru & Keluar"
                        }}</span>
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        @click="discardDraftAndExit"
                        class="w-full border-red-200 text-red-700 bg-red-50/50 hover:bg-red-100 text-xs font-bold h-10 gap-2 cursor-pointer"
                    >
                        <Trash2 class="w-4 h-4 text-red-600" />
                        <span>Buang / Hapus Draft & Keluar</span>
                    </Button>

                    <Button
                        type="button"
                        variant="ghost"
                        @click="showNewSurveyBackModal = false"
                        class="w-full text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-xs font-semibold h-9 cursor-pointer"
                    >
                        Batal (Tetap Lanjutkan Mengisi)
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- MODAL PERINGATAN HAPUS SURVEI PASAR -->
        <Modal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            maxWidth="md"
        >
            <div class="p-6 space-y-5">
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 border border-red-200 flex items-center justify-center shrink-0"
                    >
                        <AlertTriangle class="w-6 h-6 text-red-600" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900">
                            Hapus Dokumen Survei Pasar?
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Apakah Anda yakin ingin menghapus arsip survei harga
                            pasar ini dari database? Tindakan ini bersifat
                            permanen dan tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>

                <div
                    v-if="surveyToDelete"
                    class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2 text-xs text-slate-700"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Nomor Dokumen:</span>
                        <span class="font-bold text-slate-900">{{
                            surveyToDelete.no_dokumen || "-"
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Tanggal Survei:</span>
                        <span class="font-semibold text-slate-800">{{
                            getFormattedHariTanggal(
                                surveyToDelete.tanggal_survei,
                            )
                        }}</span>
                    </div>
                    <div
                        v-if="surveyToDelete.lokasi_survei"
                        class="flex items-center justify-between"
                    >
                        <span class="text-slate-500">Lokasi:</span>
                        <span class="font-semibold text-slate-800">{{
                            surveyToDelete.lokasi_survei
                        }}</span>
                    </div>
                    <div
                        v-if="surveyToDelete.petugas_survei"
                        class="flex items-center justify-between"
                    >
                        <span class="text-slate-500">Petugas SPPG:</span>
                        <span class="font-semibold text-slate-800">{{
                            surveyToDelete.petugas_survei
                        }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="showDeleteModal = false"
                        :disabled="isDeleting"
                        class="text-xs font-bold border-slate-200 text-slate-700 cursor-pointer"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        variant="default"
                        size="sm"
                        @click="confirmDeleteSurvey"
                        :disabled="isDeleting"
                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold gap-1.5 cursor-pointer shadow-xs"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                        <span>{{
                            isDeleting ? "Menghapus..." : "Ya, Hapus Survei"
                        }}</span>
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- MODAL KONFIRMASI BUANG DRAFT LOKAL -->
        <Modal
            :show="showClearDraftModal"
            @close="showClearDraftModal = false"
            maxWidth="sm"
        >
            <div class="p-6 space-y-4">
                <div class="flex items-start gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-red-50 text-red-600 border border-red-200 flex items-center justify-center shrink-0"
                    >
                        <Trash2 class="w-5 h-5 text-red-600" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">
                            Buang Draft Lokal?
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Draft formulir yang tersimpan pada penyimpanan
                            peramban lokal Anda akan dihapus secara permanen.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="showClearDraftModal = false"
                        class="text-xs font-bold cursor-pointer"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        @click="confirmClearDraft"
                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold gap-1.5 cursor-pointer shadow-xs"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                        <span>Ya, Buang Draft</span>
                    </Button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 12mm 12mm 12mm 12mm;
    }

    *,
    *::before,
    *::after {
        box-shadow: none !important;
        text-shadow: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    html,
    body,
    #app,
    div,
    main,
    section,
    header,
    footer,
    aside,
    .min-h-screen,
    .h-screen,
    .w-screen {
        background: #ffffff !important;
        background-color: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
    }

    /* Hide layout chrome, sidebar, navigation, and all surrounding UI */
    body * {
        visibility: hidden !important;
    }

    #officialSurveiPaper,
    #officialSurveiPaper * {
        visibility: visible !important;
    }

    #officialSurveiPaper {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        outline: none !important;
        border-radius: 0 !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
    }

    /* Preserve clean borders on the table and header box */
    #officialSurveiPaper .border-2 {
        border-width: 1.5px !important;
    }
    #officialSurveiPaper .border-b-2 {
        border-bottom-width: 1.5px !important;
    }
    #officialSurveiPaper .border-r-2 {
        border-right-width: 1.5px !important;
    }
    #officialSurveiPaper .border-b {
        border-bottom-width: 1px !important;
    }
    #officialSurveiPaper .border-r {
        border-right-width: 1px !important;
    }
    #officialSurveiPaper .border-slate-900 {
        border-color: #0f172a !important;
    }
    #officialSurveiPaper .bg-slate-200\/90,
    #officialSurveiPaper .bg-slate-100 {
        background-color: #f1f5f9 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    #officialSurveiPaper .bg-white {
        background-color: #ffffff !important;
    }

    #officialSurveiPaper .sppg-header-title {
        word-break: normal !important;
        overflow-wrap: break-word !important;
        white-space: normal !important;
        text-overflow: clip !important;
        overflow: visible !important;
    }

    #officialSurveiPaper .meta-box-header {
        width: 320px !important;
        min-width: 320px !important;
    }

    table {
        width: 100% !important;
        table-layout: fixed !important;
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    thead {
        display: table-header-group;
    }

    .print\:hidden {
        display: none !important;
    }
}
</style>
