<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from "axios";
import {
    Server,
    Database,
    HardDrive,
    Mail,
    Bot,
    KeyRound,
    Sparkles,
    CheckCircle2,
    AlertCircle,
    Loader2,
    ExternalLink,
    Eye,
    EyeOff,
    Cpu,
    Zap,
    ShieldCheck,
    RefreshCw,
    HelpCircle,
    Layers,
    Check,
    Clock,
    Activity,
    Save,
    Sliders,
    FolderKanban,
    FileCheck,
    Send,
    Terminal,
    Info,
    ArrowUpRight,
    Flame,
    Radio,
    CircleCheck,
    CircleAlert,
    Cloud,
    Globe,
    Lock,
    MailCheck,
    UploadCloud,
    ShieldAlert,
    RotateCcw,
    Palette,
    FileText,
} from "lucide-vue-next";

const props = defineProps({
    databaseInfo: {
        type: Object,
        default: () => ({}),
    },
    storageInfo: {
        type: Object,
        default: () => ({}),
    },
    mailInfo: {
        type: Object,
        default: () => ({}),
    },
    supportedProviders: {
        type: Array,
        default: () => [],
    },
    savedConfigs: {
        type: Object,
        default: () => ({}),
    },
    defaultActiveProvider: {
        type: String,
        default: "gemini",
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    initialTab: {
        type: String,
        default: "database",
    },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVE TAB STATE
// ═══════════════════════════════════════════════════════════════════════════
const activeTab = ref(props.initialTab || "database");

function setActiveTab(tab) {
    activeTab.value = tab;
    const url = new URL(window.location.href);
    url.searchParams.set("tab", tab);
    window.history.replaceState({}, "", url.toString());
}

// ═══════════════════════════════════════════════════════════════════════════
// TAB 1: DATABASE STATE & TESTING
// ═══════════════════════════════════════════════════════════════════════════
const isDbTesting = ref(false);
const dbTestResult = ref(null);
const liveDbLatency = ref(props.databaseInfo.latency_ms || 0);

async function runTestDatabase() {
    isDbTesting.value = true;
    dbTestResult.value = null;
    try {
        const res = await axios.post(route("admin.pengaturan.layanan.test-database"));
        dbTestResult.value = res.data;
        if (res.data.latency_ms) {
            liveDbLatency.value = res.data.latency_ms;
        }
    } catch (err) {
        dbTestResult.value = {
            success: false,
            message: err.response?.data?.message || err.message || "Gagal menghubungi database.",
        };
    } finally {
        isDbTesting.value = false;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// TAB 2: STORAGE STATE, FORM & TESTING (Lokal vs Cloudflare R2)
// ═══════════════════════════════════════════════════════════════════════════
const formStorage = useForm({
    driver: props.storageInfo.active_driver || "local", // 'local' | 'cloud'
    account_id: props.storageInfo.r2_config?.account_id || "",
    access_key_id: props.storageInfo.r2_config?.access_key_id || "",
    secret_access_key: props.storageInfo.r2_config?.secret_access_key || "",
    bucket: props.storageInfo.r2_config?.bucket || "",
    region: props.storageInfo.r2_config?.region || "auto",
    endpoint: props.storageInfo.r2_config?.endpoint || "",
    public_domain: props.storageInfo.r2_config?.public_domain || "",
    status_koneksi: props.storageInfo.status_koneksi || "belum_dites",
    pesan_koneksi: props.storageInfo.pesan_koneksi || "",
});

const showR2Secret = ref(false);
const isStorageTesting = ref(false);
const storageTestResult = ref(
    props.storageInfo.pesan_koneksi
        ? {
              success: props.storageInfo.status_koneksi === "terhubung",
              message: props.storageInfo.pesan_koneksi,
              tested_at: props.storageInfo.last_tested_at,
          }
        : null
);

async function runTestStorage() {
    isStorageTesting.value = true;
    storageTestResult.value = null;
    try {
        const payload = {
            driver: formStorage.driver,
            account_id: formStorage.account_id,
            access_key_id: formStorage.access_key_id,
            secret_access_key: formStorage.secret_access_key,
            bucket: formStorage.bucket,
            region: formStorage.region,
            endpoint: formStorage.endpoint,
            public_domain: formStorage.public_domain,
        };
        const res = await axios.post(route("admin.pengaturan.layanan.test-storage"), payload);
        storageTestResult.value = res.data;
        formStorage.status_koneksi = res.data.status;
        formStorage.pesan_koneksi = res.data.message;
    } catch (err) {
        let errMsg = err.response?.data?.message || err.message || "Gagal melakukan uji storage.";
        if (err.response?.status === 401 || errMsg === "Unauthenticated.") {
            errMsg = "Sesi login Anda telah kedaluwarsa (Unauthenticated). Silakan simpan salinan kunci rahasia Anda, lalu login kembali.";
        }
        storageTestResult.value = {
            success: false,
            message: errMsg,
        };
        formStorage.status_koneksi = "gagal";
        formStorage.pesan_koneksi = errMsg;
    } finally {
        isStorageTesting.value = false;
    }
}

function submitFormStorage() {
    formStorage.post(route("admin.pengaturan.layanan.update-storage"), {
        preserveScroll: true,
        onSuccess: () => {
            // Berhasil disimpan
        },
    });
}

// ═══════════════════════════════════════════════════════════════════════════
// TAB 3: MAIL STATE, FORM & TESTING (Google SMTP vs Local Log)
// ═══════════════════════════════════════════════════════════════════════════
const defaultEmailTemplates = {
    verification: {
        subject: "Verifikasi Alamat Email Akun SIPEGE Anda",
        heading: "Verifikasi Alamat Email",
        subheading: "Sistem Pengelolaan SPPG - Badan Gizi Nasional",
        greeting: "Halo {nama},",
        body: "Terima kasih telah mendaftar di SIPEGE. Untuk mengaktifkan akun dan mulai mengakses layanan operasional SPPG, silakan verifikasi alamat email Anda dengan menekan tombol di bawah ini:",
        button_text: "Verifikasi Email Sekarang",
        footer_note: "Tautan verifikasi ini berlaku selama {waktu_kedaluwarsa}. Jika Anda tidak merasa mendaftar di SIPEGE, abaikan email ini dengan aman.",
    },
    reset_password: {
        subject: "Permintaan Atur Ulang Kata Sandi SIPEGE",
        heading: "Atur Ulang Kata Sandi",
        subheading: "Sistem Pengelolaan SPPG - Badan Gizi Nasional",
        greeting: "Halo {nama},",
        body: "Kami menerima permintaan untuk mengatur ulang kata sandi akun SIPEGE Anda. Silakan klik tombol di bawah ini untuk membuat kata sandi baru akun Anda:",
        button_text: "Atur Ulang Kata Sandi",
        footer_note: "Tautan ini akan kedaluwarsa dalam waktu {waktu_kedaluwarsa}. Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan email ini.",
    },
};

const formMail = useForm({
    driver: props.mailInfo.active_driver || "log", // 'smtp' | 'log'
    host: props.mailInfo.smtp_config?.host || "smtp.gmail.com",
    port: props.mailInfo.smtp_config?.port || 587,
    encryption: props.mailInfo.smtp_config?.encryption || "tls",
    username: props.mailInfo.smtp_config?.username || "",
    password: props.mailInfo.smtp_config?.password || "",
    from_address: props.mailInfo.smtp_config?.from_address || "",
    from_name: props.mailInfo.smtp_config?.from_name || "SIPEGE SPPG",
    send_real_email: false,
    status_koneksi: props.mailInfo.status_koneksi || "belum_dites",
    pesan_koneksi: props.mailInfo.pesan_koneksi || "",
    templates: props.mailInfo.templates
        ? JSON.parse(JSON.stringify(props.mailInfo.templates))
        : JSON.parse(JSON.stringify(defaultEmailTemplates)),
});

const showMailPassword = ref(false);
const isMailTesting = ref(false);
const mailTestResult = ref(
    props.mailInfo.pesan_koneksi
        ? {
              success: props.mailInfo.status_koneksi === "terhubung",
              message: props.mailInfo.pesan_koneksi,
              tested_at: props.mailInfo.last_tested_at,
          }
        : null
);

// State Pengaturan Template Email Pengguna
const activeTemplateTab = ref("verification"); // 'verification' | 'reset_password'
const previewTargetEmail = ref(
    formMail.username || usePage().props.auth?.user?.email || ""
);
const isSendingTemplatePreview = ref(false);
const templatePreviewResult = ref(null);

async function runSendTemplatePreview() {
    if (!previewTargetEmail.value) {
        templatePreviewResult.value = {
            success: false,
            message: "Silakan masukkan alamat email tujuan uji pratinjau.",
        };
        return;
    }
    isSendingTemplatePreview.value = true;
    templatePreviewResult.value = null;
    try {
        const payload = {
            type: activeTemplateTab.value,
            target_email: previewTargetEmail.value,
            template: formMail.templates[activeTemplateTab.value],
        };
        const res = await axios.post(
            route("admin.pengaturan.layanan.test-mail-template"),
            payload
        );
        templatePreviewResult.value = res.data;
    } catch (err) {
        templatePreviewResult.value = {
            success: false,
            message:
                err.response?.data?.message ||
                err.message ||
                "Gagal mengirim email uji pratinjau.",
        };
    } finally {
        isSendingTemplatePreview.value = false;
    }
}

function resetTemplateToDefault(type) {
    if (defaultEmailTemplates[type]) {
        formMail.templates[type] = JSON.parse(
            JSON.stringify(defaultEmailTemplates[type])
        );
    }
}

async function runTestMail() {
    isMailTesting.value = true;
    mailTestResult.value = null;
    try {
        const payload = {
            driver: formMail.driver,
            host: formMail.host,
            port: formMail.port,
            encryption: formMail.encryption,
            username: formMail.username,
            password: formMail.password,
            from_address: formMail.from_address || formMail.username,
            from_name: formMail.from_name,
            send_real_email: formMail.send_real_email,
        };
        const res = await axios.post(route("admin.pengaturan.layanan.test-mail"), payload);
        mailTestResult.value = res.data;
        formMail.status_koneksi = res.data.status;
        formMail.pesan_koneksi = res.data.message;
    } catch (err) {
        const errMsg = err.response?.data?.message || err.message || "Gagal menguji koneksi mailer.";
        mailTestResult.value = {
            success: false,
            message: errMsg,
        };
        formMail.status_koneksi = "gagal";
        formMail.pesan_koneksi = errMsg;
    } finally {
        isMailTesting.value = false;
    }
}

function submitFormMail() {
    formMail.post(route("admin.pengaturan.layanan.update-mail"), {
        preserveScroll: true,
        onSuccess: () => {
            // Berhasil disimpan
        },
    });
}

// ═══════════════════════════════════════════════════════════════════════════
// TAB 4: AI PLATFORM & API KEY STATE & LOGIC
// ═══════════════════════════════════════════════════════════════════════════
const selectedProviderId = ref(props.defaultActiveProvider || "gemini");

const selectedProviderMeta = computed(() => {
    return (
        props.supportedProviders.find((p) => p.id === selectedProviderId.value) ||
        props.supportedProviders[0] || {
            id: "gemini",
            name: "Google Gemini",
            company: "Google",
            default_model: "gemini-2.0-flash",
            recommended_models: [],
            docs_url: "https://aistudio.google.com/app/apikey",
            key_placeholder: "AIzaSy...",
        }
    );
});

const formAi = useForm({
    provider: selectedProviderId.value,
    api_key: "",
    model: "auto",
    is_active: true,
    status_koneksi: "belum_dites",
    pesan_koneksi: "",
});

const showApiKey = ref(false);
const modelMode = ref("auto"); // 'auto' | 'custom'
const isAiTesting = ref(false);
const aiTestResult = ref(null);
const liveModels = ref([]);
const isDebouncing = ref(false);
let debounceTimeout = null;

function syncFormWithProvider(providerId) {
    selectedProviderId.value = providerId;
    const existing = props.savedConfigs[providerId];

    formAi.provider = providerId;
    formAi.api_key = existing?.api_key || "";
    formAi.is_active = existing ? existing.is_active : false;
    formAi.status_koneksi = existing?.status_koneksi || "belum_dites";
    formAi.pesan_koneksi = existing?.pesan_koneksi || "";

    const savedModel = existing?.model || "auto";
    if (savedModel === "auto" || !savedModel) {
        modelMode.value = "auto";
        formAi.model = "auto";
    } else {
        modelMode.value = "custom";
        formAi.model = savedModel;
    }

    if (existing?.status_koneksi === "terhubung") {
        aiTestResult.value = {
            success: true,
            status: "terhubung",
            message: existing.pesan_koneksi || "Koneksi token API aktif & terverifikasi.",
        };
    } else if (existing?.status_koneksi === "gagal") {
        aiTestResult.value = {
            success: false,
            status: "gagal",
            message: existing.pesan_koneksi || "Koneksi API gagal dihubungi.",
        };
    } else {
        aiTestResult.value = null;
    }

    liveModels.value = [];
}

const isAiActiveAndConnected = computed(() => {
    return Object.values(props.savedConfigs).some(
        (cfg) => cfg.is_active && cfg.api_key && cfg.status_koneksi === "terhubung"
    );
});

const isStorageActiveAndConnected = computed(() => {
    if (formStorage.driver === "cloud") {
        return formStorage.status_koneksi === "terhubung";
    }
    return Boolean(props.storageInfo.is_app_writable);
});

const isMailActiveAndConnected = computed(() => {
    if (formMail.driver === "smtp") {
        return formMail.status_koneksi === "terhubung";
    }
    return true; // Mode simulasi log selalu ON/siap
});

const activeConfigSummary = computed(() => {
    const activeEntry = Object.values(props.savedConfigs).find((c) => c.is_active && c.api_key);
    if (!activeEntry) {
        return {
            platformName: "Google Gemini (Bawaan)",
            company: "Google",
            activeModel: "gemini-2.0-flash",
            lastTested: "Belum Dites",
        };
    }

    const provMeta = props.supportedProviders.find((p) => p.id === activeEntry.provider);
    return {
        platformName: provMeta?.name || activeEntry.provider.toUpperCase(),
        company: provMeta?.company || "AI Provider",
        activeModel: activeEntry.model === "auto" ? `${provMeta?.default_model || "auto"} (Auto)` : activeEntry.model,
        lastTested: activeEntry.last_tested_at || "Belum Dites",
    };
});

watch(
    () => formAi.api_key,
    (newVal) => {
        if (debounceTimeout) {
            clearTimeout(debounceTimeout);
        }

        if (!newVal || newVal.trim().length < 12) {
            aiTestResult.value = null;
            return;
        }

        isDebouncing.value = true;
        debounceTimeout = setTimeout(() => {
            isDebouncing.value = false;
            runTestAiConnection(false);
        }, 800);
    }
);

async function runTestAiConnection(isManual = true) {
    if (!formAi.api_key || formAi.api_key.trim().length < 6) {
        if (isManual) {
            alert("Harap masukkan API Key terlebih dahulu untuk diuji.");
        }
        return;
    }

    isAiTesting.value = true;
    if (isManual) {
        aiTestResult.value = null;
    }

    try {
        const response = await axios.post(
            route("admin.pengaturan.layanan.test-koneksi-ai"),
            {
                provider: formAi.provider,
                api_key: formAi.api_key.trim(),
                model: formAi.model,
            }
        );

        if (response.data.success) {
            aiTestResult.value = {
                success: true,
                status: "terhubung",
                message: response.data.message,
            };
            liveModels.value = response.data.models || [];
            formAi.status_koneksi = "terhubung";
            formAi.pesan_koneksi = response.data.message;

            if (modelMode.value === "custom" && (!formAi.model || formAi.model === "auto")) {
                if (liveModels.value.length > 0) {
                    formAi.model = liveModels.value[0].id;
                }
            }
        } else {
            aiTestResult.value = {
                success: false,
                status: "gagal",
                message: response.data.message || "Gagal menghubungkan ke platform AI.",
            };
            formAi.status_koneksi = "gagal";
            formAi.pesan_koneksi = response.data.message;
        }
    } catch (err) {
        console.error("Test AI Error:", err);
        const errMsg =
            err.response?.data?.message ||
            err.message ||
            "Terjadi kesalahan saat menghubungi API server platform AI.";

        aiTestResult.value = {
            success: false,
            status: "gagal",
            message: errMsg,
        };
        formAi.status_koneksi = "gagal";
        formAi.pesan_koneksi = errMsg;
    } finally {
        isAiTesting.value = false;
    }
}

function submitFormAi() {
    formAi.post(route("admin.pengaturan.layanan.update-ai"), {
        preserveScroll: true,
        onSuccess: () => {
            // Berhasil disimpan
        },
    });
}

onMounted(() => {
    syncFormWithProvider(selectedProviderId.value);
});
</script>

<template>
    <Head title="Layanan - Admin SIPEGE" />

    <AppLayout title="Layanan - Admin SIPEGE">
        <!-- Kontainer Mengikuti Whitespace Penuh Sesuai Rekap Kehadiran -->
        <div class="space-y-3.5 sm:space-y-4 w-full">
            
            <!-- ─── HEADER SECTION (Bersih & Rata Kiri Kanan Tanpa Unit SPPG) ─── -->
            <div class="flex items-center justify-between p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs w-full">
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <div class="p-2 sm:p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-2xs shrink-0">
                        <Server class="h-5 w-5 sm:h-6 sm:w-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight leading-tight">
                                Layanan Sistem & Integrasi
                            </h1>
                            <span class="px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Global Database
                            </span>
                        </div>
                        <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5 truncate">
                            Kelola konfigurasi layanan terpadu: Database RDBMS, Penyimpanan (Lokal / Cloudflare R2), Mail Server (Google SMTP), dan Kecerdasan Buatan (AI)
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── FLASH MESSAGE ─────────────────────────────────────────── -->
            <div
                v-if="flash.success"
                class="flex items-center gap-2.5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs animate-in fade-in w-full"
            >
                <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                <span class="font-medium">{{ flash.success }}</span>
            </div>

            <!-- ─── TAB NAVIGASI UTAMA (Database, Storage, Mail, AI) ───────── -->
            <!-- Full Lebar Rata Kiri Kanan: 2 Kolom di HP (2x2), 4 Kolom di Tablet & Desktop (1x4) -->
            <div class="p-1 sm:p-1.5 rounded-xl bg-white border border-slate-200 shadow-2xs grid grid-cols-2 sm:grid-cols-4 gap-1.5 w-full select-none">
                <!-- Tab 1: Database -->
                <button
                    type="button"
                    @click="setActiveTab('database')"
                    :class="[
                        'w-full flex items-center justify-between px-3 py-2 sm:px-3.5 sm:py-2.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                        activeTab === 'database'
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                    ]"
                >
                    <div class="flex items-center gap-2 truncate">
                        <Database class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">Database</span>
                    </div>
                    <span
                        :class="[
                            'text-[10px] px-1.5 py-0.5 rounded font-mono font-black shrink-0 tracking-wider',
                            databaseInfo.status === 'terhubung'
                                ? (activeTab === 'database' ? 'bg-white/20 text-white border border-white/30' : 'bg-emerald-100 text-emerald-800 border border-emerald-200')
                                : (activeTab === 'database' ? 'bg-rose-500/30 text-white border border-white/30' : 'bg-rose-100 text-rose-800 border border-rose-200'),
                        ]"
                    >
                        {{ databaseInfo.status === 'terhubung' ? 'ON' : 'OFF' }}
                    </span>
                </button>

                <!-- Tab 2: Storage -->
                <button
                    type="button"
                    @click="setActiveTab('storage')"
                    :class="[
                        'w-full flex items-center justify-between px-3 py-2 sm:px-3.5 sm:py-2.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                        activeTab === 'storage'
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                    ]"
                >
                    <div class="flex items-center gap-2 truncate">
                        <HardDrive class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">Storage</span>
                    </div>
                    <span
                        :class="[
                            'text-[10px] px-1.5 py-0.5 rounded font-mono font-black shrink-0 tracking-wider',
                            isStorageActiveAndConnected
                                ? (activeTab === 'storage' ? 'bg-white/20 text-white border border-white/30' : 'bg-emerald-100 text-emerald-800 border border-emerald-200')
                                : (activeTab === 'storage' ? 'bg-rose-500/30 text-white border border-white/30' : 'bg-rose-100 text-rose-800 border border-rose-200'),
                        ]"
                    >
                        {{ isStorageActiveAndConnected ? 'ON' : 'OFF' }}
                    </span>
                </button>

                <!-- Tab 3: Mail -->
                <button
                    type="button"
                    @click="setActiveTab('mail')"
                    :class="[
                        'w-full flex items-center justify-between px-3 py-2 sm:px-3.5 sm:py-2.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                        activeTab === 'mail'
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                    ]"
                >
                    <div class="flex items-center gap-2 truncate">
                        <Mail class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">Mail</span>
                    </div>
                    <span
                        :class="[
                            'text-[10px] px-1.5 py-0.5 rounded font-mono font-black shrink-0 tracking-wider',
                            isMailActiveAndConnected
                                ? (activeTab === 'mail' ? 'bg-white/20 text-white border border-white/30' : 'bg-emerald-100 text-emerald-800 border border-emerald-200')
                                : (activeTab === 'mail' ? 'bg-rose-500/30 text-white border border-white/30' : 'bg-rose-100 text-rose-800 border border-rose-200'),
                        ]"
                    >
                        {{ isMailActiveAndConnected ? 'ON' : 'OFF' }}
                    </span>
                </button>

                <!-- Tab 4: AI -->
                <button
                    type="button"
                    @click="setActiveTab('ai')"
                    :class="[
                        'w-full flex items-center justify-between px-3 py-2 sm:px-3.5 sm:py-2.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                        activeTab === 'ai'
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                    ]"
                >
                    <div class="flex items-center gap-2 truncate">
                        <Bot class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">AI</span>
                    </div>
                    <span
                        :class="[
                            'text-[10px] px-1.5 py-0.5 rounded font-mono font-black shrink-0 tracking-wider',
                            isAiActiveAndConnected
                                ? (activeTab === 'ai' ? 'bg-white/20 text-white border border-white/30' : 'bg-emerald-100 text-emerald-800 border border-emerald-200')
                                : (activeTab === 'ai' ? 'bg-rose-500/30 text-white border border-white/30' : 'bg-slate-100 text-slate-600 border border-slate-200'),
                        ]"
                    >
                        {{ isAiActiveAndConnected ? 'ON' : 'OFF' }}
                    </span>
                </button>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- CONTENT TAB 1: DATABASE                                         -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'database'" class="space-y-3 sm:space-y-3.5 animate-in fade-in duration-150 w-full">
                <!-- KPI Status Database (Rata Penuh 2 Kolom HP & 4 Kolom Desktop) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 w-full">
                    <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Status Koneksi</p>
                            <Database class="h-3.5 w-3.5 text-emerald-600" />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-base sm:text-lg font-bold text-slate-900 capitalize">{{ databaseInfo.status }}</span>
                        </div>
                        <p class="text-[10px] text-emerald-600 font-semibold mt-0.5 truncate">
                            Koneksi RDBMS aktif & stabil
                        </p>
                    </div>

                    <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Waktu Respon (Latency)</p>
                            <Activity class="h-3.5 w-3.5 text-indigo-600" />
                        </div>
                        <p class="text-base sm:text-lg font-bold text-indigo-600 font-mono mt-1">
                            {{ liveDbLatency }} <span class="text-xs font-normal text-slate-500">ms</span>
                        </p>
                        <p class="text-[10px] text-indigo-600 font-medium mt-0.5 truncate">
                            Latency ping PDO roundtrip
                        </p>
                    </div>

                    <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Engine / Provider</p>
                            <Server class="h-3.5 w-3.5 text-amber-600" />
                        </div>
                        <p class="text-sm sm:text-base font-bold text-slate-900 mt-1 truncate">
                            {{ databaseInfo.driver_label }}
                        </p>
                        <p class="text-[10px] text-slate-500 font-mono mt-0.5 truncate">
                            Driver: {{ databaseInfo.driver }} • Port {{ databaseInfo.port }}
                        </p>
                    </div>

                    <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Tabel Terdaftar</p>
                            <Layers class="h-3.5 w-3.5 text-blue-600" />
                        </div>
                        <p class="text-base sm:text-lg font-bold text-slate-900 font-mono mt-1">
                            {{ databaseInfo.tables_count }} <span class="text-xs font-normal text-slate-500">Tabel</span>
                        </p>
                        <p class="text-[10px] text-blue-600 font-medium mt-0.5 truncate">
                            Skema public aktif
                        </p>
                    </div>
                </div>

                <!-- Kartu Parameter Database & Diagnostik Ping -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 w-full">
                    <div class="lg:col-span-2 p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <h3 class="font-bold text-xs sm:text-sm text-slate-900 flex items-center gap-2">
                                <Server class="h-4 w-4 text-emerald-600" />
                                <span>Parameter Koneksi Database</span>
                            </h3>
                            <span class="text-[10px] font-mono text-slate-400 bg-slate-100 px-2 py-0.5 rounded">
                                Read Only View
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold block">Database Host</span>
                                <span class="font-mono text-slate-900 text-xs font-semibold block truncate mt-0.5">{{ databaseInfo.host }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold block">Port Koneksi</span>
                                <span class="font-mono text-slate-900 text-xs font-semibold block mt-0.5">{{ databaseInfo.port }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold block">Nama Database</span>
                                <span class="font-mono text-slate-900 text-xs font-semibold block mt-0.5">{{ databaseInfo.database }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold block">Username Pengguna</span>
                                <span class="font-mono text-slate-900 text-xs font-semibold block mt-0.5">{{ databaseInfo.username_masked }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold block">SSL Mode</span>
                                <span class="font-mono text-emerald-700 text-xs font-bold block mt-0.5 uppercase">{{ databaseInfo.sslmode }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold block">Versi Server RDBMS</span>
                                <span class="font-mono text-slate-700 text-[11px] block truncate mt-0.5" :title="databaseInfo.version">{{ databaseInfo.version }}</span>
                            </div>
                        </div>

                        <!-- Tombol Uji Ping Live -->
                        <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                            <p class="text-[11px] text-slate-500">
                                Lakukan pengujian koneksi langsung untuk mengukur latency query server database secara real-time.
                            </p>
                            <button
                                type="button"
                                @click="runTestDatabase"
                                :disabled="isDbTesting"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-2xs transition-colors shrink-0 cursor-pointer disabled:opacity-50"
                            >
                                <Loader2 v-if="isDbTesting" class="h-3.5 w-3.5 animate-spin" />
                                <Activity v-else class="h-3.5 w-3.5" />
                                <span>{{ isDbTesting ? 'Menguji Koneksi...' : 'Uji Koneksi (Ping)' }}</span>
                            </button>
                        </div>

                        <!-- Hasil Uji Ping -->
                        <div
                            v-if="dbTestResult"
                            :class="[
                                'p-2.5 rounded-lg border text-xs animate-in fade-in',
                                dbTestResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900',
                            ]"
                        >
                            <div class="flex items-center gap-1.5 font-bold mb-0.5">
                                <CheckCircle2 v-if="dbTestResult.success" class="h-3.5 w-3.5 text-emerald-600" />
                                <AlertCircle v-else class="h-3.5 w-3.5 text-rose-600" />
                                <span>{{ dbTestResult.message }}</span>
                            </div>
                            <p v-if="dbTestResult.tested_at" class="text-[10px] text-slate-500">
                                Waktu tes: {{ dbTestResult.tested_at }} • Latency: {{ dbTestResult.latency_ms }} ms
                            </p>
                        </div>
                    </div>

                    <!-- Panel Informasi Keamanan Database -->
                    <div class="p-3.5 sm:p-4 rounded-xl bg-slate-900 text-white shadow-2xs space-y-3 flex flex-col justify-between">
                        <div class="space-y-2.5">
                            <div class="flex items-center gap-2 text-emerald-400">
                                <ShieldCheck class="h-4 w-4" />
                                <span class="font-bold text-xs uppercase tracking-wider">Keamanan & Backup</span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Database SIPEGE beroperasi menggunakan PostgreSQL di cloud Supabase dengan enkripsi SSL/TLS secara end-to-end.
                            </p>
                            <div class="space-y-1.5 text-xs text-slate-300 pt-2 border-t border-slate-800">
                                <div class="flex items-start gap-1.5">
                                    <Check class="h-3.5 w-3.5 text-emerald-400 shrink-0 mt-0.5" />
                                    <span>Pencadangan rutin otomatis via fitur Backup & Restore</span>
                                </div>
                                <div class="flex items-start gap-1.5">
                                    <Check class="h-3.5 w-3.5 text-emerald-400 shrink-0 mt-0.5" />
                                    <span>Pooler mode aktif pada port 6543</span>
                                </div>
                                <div class="flex items-start gap-1.5">
                                    <Check class="h-3.5 w-3.5 text-emerald-400 shrink-0 mt-0.5" />
                                    <span>Integritas relasi kunci asing (Foreign Key) terverifikasi</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- CONTENT TAB 2: STORAGE (Lokal vs Cloudflare R2)                  -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'storage'" class="space-y-3 sm:space-y-3.5 animate-in fade-in duration-150 w-full">
                <!-- PILIH MODE STORAGE (Lokal vs Cloudflare R2) - Rata Penuh Kiri-Kanan -->
                <div class="p-2 sm:p-2.5 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-1.5 w-full">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider px-1">
                        PILIH METODE PENYIMPANAN BERKAS (STORAGE ENGINE):
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 w-full">
                        <!-- Opsi 1: Penyimpanan Lokal -->
                        <button
                            type="button"
                            @click="formStorage.driver = 'local'"
                            :class="[
                                'p-3 rounded-lg border text-left transition-all flex items-start gap-3 relative cursor-pointer',
                                formStorage.driver === 'local'
                                    ? 'border-emerald-600 bg-emerald-50/70 text-emerald-950 font-medium ring-1 ring-emerald-600 shadow-2xs'
                                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700',
                            ]"
                        >
                            <div class="p-2 rounded-lg bg-white border border-slate-200 shadow-2xs shrink-0 text-blue-600 mt-0.5">
                                <HardDrive class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-xs font-bold text-slate-900">Penyimpanan Lokal (Server)</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold bg-slate-100 text-slate-600">Lokal</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">
                                    Menyimpan foto, kwitansi, dan berkas langsung ke dalam storage hard disk server.
                                </p>
                            </div>
                        </button>

                        <!-- Opsi 2: Cloud Storage (Cloudflare R2) -->
                        <button
                            type="button"
                            @click="formStorage.driver = 'cloud'"
                            :class="[
                                'p-3 rounded-lg border text-left transition-all flex items-start gap-3 relative cursor-pointer',
                                formStorage.driver === 'cloud'
                                    ? 'border-emerald-600 bg-emerald-50/70 text-emerald-950 font-medium ring-1 ring-emerald-600 shadow-2xs'
                                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700',
                            ]"
                        >
                            <div class="p-2 rounded-lg bg-white border border-slate-200 shadow-2xs shrink-0 text-amber-600 mt-0.5">
                                <Cloud class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-xs font-bold text-slate-900">Cloudflare R2 (Cloud Storage)</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold bg-amber-100 text-amber-800">S3-Compatible</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">
                                    Object storage cloud cepat tanpa biaya bandwidth/egress dengan protokol standar S3.
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- ── KONTEN STORAGE CLOUD: FORM VARIABEL CLOUDFLARE R2 ── -->
                <div v-if="formStorage.driver === 'cloud'" class="p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3.5 w-full">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <Cloud class="h-4 w-4 text-amber-600" />
                            <h3 class="font-bold text-xs sm:text-sm text-slate-900">
                                Konfigurasi Variabel Cloudflare R2
                            </h3>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            Global Sistem
                        </span>
                    </div>

                    <!-- Input Grid Variabel R2 -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs w-full">
                        <!-- Cloudflare Account ID -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                Cloudflare Account ID <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="formStorage.account_id"
                                type="text"
                                placeholder="Contoh: d3b84f3ab9128cd..."
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                            <p class="text-[10px] text-slate-400">
                                ID Akun Cloudflare (bisa dilihat di dashboard overview R2)
                            </p>
                        </div>

                        <!-- Bucket Name -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                Nama Bucket R2 <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="formStorage.bucket"
                                type="text"
                                placeholder="Contoh: sipege-storage"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                            <p class="text-[10px] text-slate-400">
                                Nama ember bucket R2 yang telah dibuat di Cloudflare
                            </p>
                        </div>

                        <!-- Access Key ID -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                R2 Access Key ID <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="formStorage.access_key_id"
                                type="text"
                                placeholder="Contoh: 9a8f4c..."
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                            <p class="text-[10px] text-slate-400">
                                Kunci akses API token Cloudflare R2
                            </p>
                        </div>

                        <!-- Secret Access Key -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                R2 Secret Access Key <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="formStorage.secret_access_key"
                                    :type="showR2Secret ? 'text' : 'password'"
                                    placeholder="••••••••••••••••••••••••••••••••"
                                    class="w-full pl-3 pr-9 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                                />
                                <button
                                    type="button"
                                    @click="showR2Secret = !showR2Secret"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                                >
                                    <EyeOff v-if="showR2Secret" class="h-3.5 w-3.5" />
                                    <Eye v-else class="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-400">
                                Kunci rahasia API token Cloudflare R2
                            </p>
                        </div>

                        <!-- Wilayah / Region -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                Wilayah (Region)
                            </label>
                            <input
                                v-model="formStorage.region"
                                type="text"
                                placeholder="auto"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                            <p class="text-[10px] text-slate-400">
                                Default: <code>auto</code> (Cloudflare otomatis memilih lokasi terdekat)
                            </p>
                        </div>

                        <!-- Public Domain / Custom URL -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                Public Domain / URL Akses Berkas (Opsional)
                            </label>
                            <input
                                v-model="formStorage.public_domain"
                                type="text"
                                placeholder="Contoh: https://pub-xxx.r2.dev atau https://media.sipege.id"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                            <p class="text-[10px] text-slate-400">
                                Domain publik atau R2.dev untuk URL preview dokumen langsung
                            </p>
                        </div>
                    </div>

                    <!-- Panduan Singkat Cloudflare R2 -->
                    <div class="p-3 bg-amber-50/60 rounded-lg border border-amber-200/80 text-[11px] text-amber-900 space-y-1.5">
                        <div class="flex items-center gap-1.5 font-bold text-amber-950">
                            <Info class="h-3.5 w-3.5 text-amber-700 shrink-0" />
                            <span>Cara Mendapatkan Token API Cloudflare R2:</span>
                        </div>
                        <ol class="list-decimal list-inside space-y-0.5 text-amber-900/90 pl-1 text-[11px]">
                            <li>Buka <strong>Cloudflare Dashboard</strong> &gt; menu <strong>R2</strong>.</li>
                            <li>Klik tombol <strong>"Manage R2 API Tokens"</strong> di sisi kanan &gt; <strong>Create API Token</strong>.</li>
                            <li>Pilih izin <strong>Object Read &amp; Write</strong> dan tentukan bucket Anda.</li>
                            <li>Salin <strong>Account ID</strong>, <strong>Access Key ID</strong>, dan <strong>Secret Access Key</strong> ke dalam kolom di atas.</li>
                        </ol>
                    </div>

                    <!-- Hasil Uji R2 -->
                    <div
                        v-if="storageTestResult"
                        :class="[
                            'p-2.5 rounded-lg border text-xs animate-in fade-in',
                            storageTestResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900',
                        ]"
                    >
                        <div class="flex items-center gap-1.5 font-bold mb-0.5">
                            <CheckCircle2 v-if="storageTestResult.success" class="h-3.5 w-3.5 text-emerald-600" />
                            <AlertCircle v-else class="h-3.5 w-3.5 text-rose-600" />
                            <span>{{ storageTestResult.message }}</span>
                        </div>
                        <p v-if="storageTestResult.tested_at" class="text-[10px] text-slate-500">
                            Waktu verifikasi: {{ storageTestResult.tested_at }} • Waktu eksekusi: {{ storageTestResult.duration_ms }} ms
                        </p>
                    </div>

                    <!-- Tombol Aksi Storage Cloud -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                        <button
                            type="button"
                            @click="runTestStorage"
                            :disabled="isStorageTesting"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="isStorageTesting" class="h-3.5 w-3.5 animate-spin" />
                            <FileCheck v-else class="h-3.5 w-3.5" />
                            <span>{{ isStorageTesting ? 'Menguji I/O R2 Cloud...' : 'Uji Koneksi & I/O R2' }}</span>
                        </button>

                        <button
                            type="button"
                            @click="submitFormStorage"
                            :disabled="formStorage.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="formStorage.processing" class="h-3.5 w-3.5 animate-spin" />
                            <Save v-else class="h-3.5 w-3.5" />
                            <span>Simpan Konfigurasi Storage R2</span>
                        </button>
                    </div>
                </div>

                <!-- ── KONTEN STORAGE LOKAL: STATISTIK & UJI DISK LOKAL ── -->
                <div v-else class="space-y-3 sm:space-y-3.5 w-full">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 w-full">
                        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Driver Penyimpanan</p>
                                <HardDrive class="h-3.5 w-3.5 text-blue-600" />
                            </div>
                            <p class="text-base sm:text-lg font-bold text-slate-900 uppercase font-mono mt-1">
                                {{ storageInfo.default_disk }}
                            </p>
                            <p class="text-[10px] text-blue-600 font-medium mt-0.5 truncate">
                                Disk lokal terkonfigurasi
                            </p>
                        </div>

                        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Izin Tulis (Writable)</p>
                                <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />
                            </div>
                            <p :class="['text-base sm:text-lg font-bold mt-1', storageInfo.is_app_writable ? 'text-emerald-700' : 'text-rose-700']">
                                {{ storageInfo.is_app_writable ? 'Tersedia' : 'Terkunci' }}
                            </p>
                            <p class="text-[10px] text-emerald-600 font-medium mt-0.5 truncate">
                                storage/app writable
                            </p>
                        </div>

                        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Batas Unggah</p>
                                <Sliders class="h-3.5 w-3.5 text-amber-600" />
                            </div>
                            <p class="text-base sm:text-lg font-bold text-slate-900 font-mono mt-1">
                                {{ storageInfo.upload_max_filesize }}
                            </p>
                            <p class="text-[10px] text-slate-500 font-medium mt-0.5 truncate">
                                PHP upload_max_filesize
                            </p>
                        </div>

                        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Sisa Ruang Bebas</p>
                                <HardDrive class="h-3.5 w-3.5 text-purple-600" />
                            </div>
                            <p class="text-base sm:text-lg font-bold text-purple-700 font-mono mt-1">
                                {{ storageInfo.free_space_gb ? `${storageInfo.free_space_gb} GB` : 'Tersedia' }}
                            </p>
                            <p class="text-[10px] text-purple-600 font-medium mt-0.5 truncate">
                                Kapasitas hard disk lokal
                            </p>
                        </div>
                    </div>

                    <!-- Kartu Detail Direktori & Uji I/O -->
                    <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3 w-full">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <h3 class="font-bold text-xs sm:text-sm text-slate-900 flex items-center gap-2">
                                <FolderKanban class="h-4 w-4 text-blue-600" />
                                <span>Status Kesehatan Direktori Storage Lokal</span>
                            </h3>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                Mode Lokal Aktif
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs w-full">
                            <div class="p-2.5 sm:p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-800 text-[11px]">storage/app</p>
                                    <p class="text-[10px] text-slate-500">Arsip data, cadangan, database pangan</p>
                                </div>
                                <span :class="['px-1.5 py-0.5 rounded text-[10px] font-bold', storageInfo.is_app_writable ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800']">
                                    {{ storageInfo.is_app_writable ? 'Writable' : 'Locked' }}
                                </span>
                            </div>

                            <div class="p-2.5 sm:p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-800 text-[11px]">storage/logs</p>
                                    <p class="text-[10px] text-slate-500">Log aplikasi, transaksi & AI prompt</p>
                                </div>
                                <span :class="['px-1.5 py-0.5 rounded text-[10px] font-bold', storageInfo.is_logs_writable ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800']">
                                    {{ storageInfo.is_logs_writable ? 'Writable' : 'Locked' }}
                                </span>
                            </div>

                            <div class="p-2.5 sm:p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-800 text-[11px]">storage/framework</p>
                                    <p class="text-[10px] text-slate-500">Cache sesi, view & struktur internal</p>
                                </div>
                                <span :class="['px-1.5 py-0.5 rounded text-[10px] font-bold', storageInfo.is_framework_writable ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800']">
                                    {{ storageInfo.is_framework_writable ? 'Writable' : 'Locked' }}
                                </span>
                            </div>
                        </div>

                        <!-- Hasil Uji I/O Lokal -->
                        <div
                            v-if="storageTestResult"
                            :class="[
                                'p-2.5 rounded-lg border text-xs animate-in fade-in',
                                storageTestResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900',
                            ]"
                        >
                            <div class="flex items-center gap-1.5 font-bold mb-0.5">
                                <CheckCircle2 v-if="storageTestResult.success" class="h-3.5 w-3.5 text-emerald-600" />
                                <AlertCircle v-else class="h-3.5 w-3.5 text-rose-600" />
                                <span>{{ storageTestResult.message }}</span>
                            </div>
                            <p v-if="storageTestResult.tested_at" class="text-[10px] text-slate-500">
                                Waktu uji: {{ storageTestResult.tested_at }} • Waktu eksekusi: {{ storageTestResult.duration_ms }} ms
                            </p>
                        </div>

                        <!-- Tombol Uji I/O & Simpan Lokal -->
                        <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                            <button
                                type="button"
                                @click="runTestStorage"
                                :disabled="isStorageTesting"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <Loader2 v-if="isStorageTesting" class="h-3.5 w-3.5 animate-spin" />
                                <FileCheck v-else class="h-3.5 w-3.5" />
                                <span>{{ isStorageTesting ? 'Menguji Disk Lokal...' : 'Uji Baca & Tulis Disk Lokal' }}</span>
                            </button>

                            <button
                                type="button"
                                @click="submitFormStorage"
                                :disabled="formStorage.processing"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <Loader2 v-if="formStorage.processing" class="h-3.5 w-3.5 animate-spin" />
                                <Save v-else class="h-3.5 w-3.5" />
                                <span>Simpan & Aktifkan Penyimpanan Lokal</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- CONTENT TAB 3: MAIL (Google SMTP vs Simulasi Log)                -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'mail'" class="space-y-3 sm:space-y-3.5 animate-in fade-in duration-150 w-full">
                <!-- PILIH MODE MAIL (Google SMTP vs Simulasi Log) -->
                <div class="p-2 sm:p-2.5 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-1.5 w-full">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider px-1">
                        PILIH LAYANAN PENGIRIMAN EMAIL (MAIL DRIVER):
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 w-full">
                        <!-- Opsi 1: Google Mail (SMTP Gratis) -->
                        <button
                            type="button"
                            @click="formMail.driver = 'smtp'"
                            :class="[
                                'p-3 rounded-lg border text-left transition-all flex items-start gap-3 relative cursor-pointer',
                                formMail.driver === 'smtp'
                                    ? 'border-emerald-600 bg-emerald-50/70 text-emerald-950 font-medium ring-1 ring-emerald-600 shadow-2xs'
                                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700',
                            ]"
                        >
                            <div class="p-2 rounded-lg bg-white border border-slate-200 shadow-2xs shrink-0 text-amber-600 mt-0.5">
                                <Mail class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-xs font-bold text-slate-900">Google Mail (SMTP Gratis)</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold bg-emerald-100 text-emerald-800">Gmail SMTP</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">
                                    Kirim notifikasi email otomatis ke petugas & penerima manfaat menggunakan akun Google/Gmail.
                                </p>
                            </div>
                        </button>

                        <!-- Opsi 2: Simulasi Log Lokal -->
                        <button
                            type="button"
                            @click="formMail.driver = 'log'"
                            :class="[
                                'p-3 rounded-lg border text-left transition-all flex items-start gap-3 relative cursor-pointer',
                                formMail.driver === 'log'
                                    ? 'border-emerald-600 bg-emerald-50/70 text-emerald-950 font-medium ring-1 ring-emerald-600 shadow-2xs'
                                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700',
                            ]"
                        >
                            <div class="p-2 rounded-lg bg-white border border-slate-200 shadow-2xs shrink-0 text-slate-600 mt-0.5">
                                <Terminal class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-xs font-bold text-slate-900">Simulasi Lokal (Log Driver)</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold bg-slate-100 text-slate-600">Simulasi</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">
                                    Email tidak dikirim ke internet, melainkan dicatat ke berkas log server untuk keperluan debugging aman.
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- ── KONTEN GOOGLE MAIL SMTP FORM ── -->
                <div v-if="formMail.driver === 'smtp'" class="p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3.5 w-full">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <Mail class="h-4 w-4 text-emerald-600" />
                            <h3 class="font-bold text-xs sm:text-sm text-slate-900">
                                Konfigurasi Google Mail SMTP (Gratis)
                            </h3>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Global Sistem
                        </span>
                    </div>

                    <!-- Input Grid Variabel Google SMTP -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs w-full">
                        <!-- Host SMTP -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                Host SMTP Server <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="formMail.host"
                                type="text"
                                placeholder="smtp.gmail.com"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                            <p class="text-[10px] text-slate-400">
                                Server SMTP resmi Google: <code>smtp.gmail.com</code>
                            </p>
                        </div>

                        <!-- Port & Enkripsi -->
                        <div class="grid grid-cols-2 gap-2">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700">
                                    Port SMTP <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="formMail.port"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                                >
                                    <option :value="587">587 (TLS / Rekomendasi)</option>
                                    <option :value="465">465 (SSL)</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700">
                                    Protokol Enkripsi
                                </label>
                                <select
                                    v-model="formMail.encryption"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                                >
                                    <option value="tls">TLS (STARTTLS)</option>
                                    <option value="ssl">SSL</option>
                                </select>
                            </div>
                        </div>

                        <!-- Alamat Akun Gmail -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                Alamat Email Akun Google (Gmail) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="formMail.username"
                                type="email"
                                placeholder="Contoh: admin.sppg@gmail.com"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                            <p class="text-[10px] text-slate-400">
                                Akun Gmail yang digunakan untuk autentikasi pengiriman
                            </p>
                        </div>

                        <!-- Sandi Aplikasi Google (App Password) -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                Sandi Aplikasi Google (App Password) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="formMail.password"
                                    :type="showMailPassword ? 'text' : 'password'"
                                    placeholder="Contoh: abcd efgh ijkl mnop (16 karakter)"
                                    class="w-full pl-3 pr-9 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                                />
                                <button
                                    type="button"
                                    @click="showMailPassword = !showMailPassword"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                                >
                                    <EyeOff v-if="showMailPassword" class="h-3.5 w-3.5" />
                                    <Eye v-else class="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-400">
                                Sandi 16 karakter yang dibuat di menu Keamanan Akun Google
                            </p>
                        </div>

                        <!-- Nama Pengirim (From Name) -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                Nama Pengirim (From Name)
                            </label>
                            <input
                                v-model="formMail.from_name"
                                type="text"
                                placeholder="SIPEGE SPPG"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-medium"
                            />
                            <p class="text-[10px] text-slate-400">
                                Nama instansi/aplikasi yang tertera pada email penerima
                            </p>
                        </div>

                        <!-- Alamat Email Pengirim (From Address) -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700">
                                Alamat Email Pengirim (From Address)
                            </label>
                            <input
                                v-model="formMail.from_address"
                                type="email"
                                placeholder="Sama dengan akun Gmail"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                            <p class="text-[10px] text-slate-400">
                                Kosongkan jika ingin sama dengan Alamat Akun Gmail di atas
                            </p>
                        </div>
                    </div>

                    <!-- Opsi Kirim Real Email Saat Test -->
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-2">
                        <input
                            id="check-send-real"
                            v-model="formMail.send_real_email"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                        />
                        <label for="check-send-real" class="text-xs text-slate-700 cursor-pointer select-none">
                            <strong>Kirim email uji nyata</strong> ke alamat email Google tersebut saat menekan tombol uji koneksi
                        </label>
                    </div>

                    <!-- Panduan Resmi Google App Password -->
                    <div class="p-3 bg-emerald-50/70 rounded-lg border border-emerald-200/90 text-[11px] text-emerald-950 space-y-1.5">
                        <div class="flex items-center gap-1.5 font-bold text-emerald-900">
                            <ShieldCheck class="h-3.5 w-3.5 text-emerald-700 shrink-0" />
                            <span>Panduan Membuat Sandi Aplikasi (App Password) Akun Google:</span>
                        </div>
                        <ol class="list-decimal list-inside space-y-0.5 text-emerald-900/90 pl-1 text-[11px]">
                            <li>Pastikan Akun Google Anda telah mengaktifkan <strong>Verifikasi 2 Langkah (2-Step Verification)</strong>.</li>
                            <li>Buka tautan resmi Google: <a href="https://myaccount.google.com/apppasswords" target="_blank" class="underline font-bold text-emerald-800 hover:text-emerald-950 inline-flex items-center gap-0.5">myaccount.google.com/apppasswords <ExternalLink class="h-3 w-3" /></a>.</li>
                            <li>Beri nama aplikasi, misal <strong>"SIPEGE"</strong>, lalu klik <strong>Buat (Create)</strong>.</li>
                            <li>Salin <strong>16 karakter sandi aplikasi</strong> yang muncul dan tempelkan ke kolom <em>Sandi Aplikasi Google</em> di atas.</li>
                        </ol>
                    </div>

                    <!-- Hasil Uji Mail -->
                    <div
                        v-if="mailTestResult"
                        :class="[
                            'p-2.5 rounded-lg border text-xs animate-in fade-in',
                            mailTestResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900',
                        ]"
                    >
                        <div class="flex items-center gap-1.5 font-bold mb-0.5">
                            <CheckCircle2 v-if="mailTestResult.success" class="h-3.5 w-3.5 text-emerald-600" />
                            <AlertCircle v-else class="h-3.5 w-3.5 text-rose-600" />
                            <span>{{ mailTestResult.message }}</span>
                        </div>
                        <p v-if="mailTestResult.tested_at" class="text-[10px] text-slate-500">
                            Waktu verifikasi: {{ mailTestResult.tested_at }} • Latency handshake: {{ mailTestResult.latency_ms }} ms
                        </p>
                    </div>

                    <!-- Tombol Aksi Google Mail -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                        <button
                            type="button"
                            @click="runTestMail"
                            :disabled="isMailTesting"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="isMailTesting" class="h-3.5 w-3.5 animate-spin" />
                            <Send v-else class="h-3.5 w-3.5" />
                            <span>{{ isMailTesting ? 'Menguji Google SMTP...' : 'Uji Koneksi Google SMTP' }}</span>
                        </button>

                        <button
                            type="button"
                            @click="submitFormMail"
                            :disabled="formMail.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="formMail.processing" class="h-3.5 w-3.5 animate-spin" />
                            <Save v-else class="h-3.5 w-3.5" />
                            <span>Simpan Konfigurasi Google Mail</span>
                        </button>
                    </div>
                </div>

                <!-- ── KONTEN SIMULASI LOG LOKAL ── -->
                <div v-else class="p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3.5 w-full">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <Terminal class="h-4 w-4 text-slate-700" />
                            <h3 class="font-bold text-xs sm:text-sm text-slate-900">
                                Mode Simulasi Log Lokal (Development & Testing)
                            </h3>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Driver: Log
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed">
                        Dalam mode simulasi, sistem tidak akan mengirimkan email secara riil melalui internet. Seluruh isi email notifikasi (subjek, penerima, dan pesan) akan dicatat ke dalam berkas <code>storage/logs/laravel.log</code>. Cocok digunakan saat dalam tahap pengembangan atau jika tidak memiliki akun email SMTP.
                    </p>

                    <!-- Hasil Uji Log -->
                    <div
                        v-if="mailTestResult"
                        :class="[
                            'p-2.5 rounded-lg border text-xs animate-in fade-in',
                            mailTestResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900',
                        ]"
                    >
                        <div class="flex items-center gap-1.5 font-bold mb-0.5">
                            <CheckCircle2 v-if="mailTestResult.success" class="h-3.5 w-3.5 text-emerald-600" />
                            <AlertCircle v-else class="h-3.5 w-3.5 text-rose-600" />
                            <span>{{ mailTestResult.message }}</span>
                        </div>
                        <p v-if="mailTestResult.tested_at" class="text-[10px] text-slate-500">
                            Waktu verifikasi: {{ mailTestResult.tested_at }}
                        </p>
                    </div>

                    <!-- Tombol Aksi Mode Log -->
                    <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                        <button
                            type="button"
                            @click="runTestMail"
                            :disabled="isMailTesting"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-800 text-white font-semibold text-xs shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="isMailTesting" class="h-3.5 w-3.5 animate-spin" />
                            <Terminal v-else class="h-3.5 w-3.5" />
                            <span>{{ isMailTesting ? 'Menguji Log...' : 'Uji Tulis Log Mailer' }}</span>
                        </button>

                        <button
                            type="button"
                            @click="submitFormMail"
                            :disabled="formMail.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="formMail.processing" class="h-3.5 w-3.5 animate-spin" />
                            <Save v-else class="h-3.5 w-3.5" />
                            <span>Simpan & Aktifkan Mode Simulasi Log</span>
                        </button>
                    </div>
                </div>

                <!-- ── KONTEN KUSTOMISASI TEMPLATE & PESAN EMAIL PENGGUNA ── -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-4 w-full">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="h-9 w-9 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center shrink-0">
                                <Palette class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-xs sm:text-sm text-slate-900 flex items-center gap-1.5">
                                    <span>Kustomisasi Format & Isi Pesan Email Pengguna</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-100 text-indigo-700">HTML Modern</span>
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Atur subjek, teks pembuka, tombol, dan gaya pesan email resmi yang diterima pengguna saat mendaftar atau lupa sandi.
                                </p>
                            </div>
                        </div>

                        <!-- Segmented Switch Tab Template -->
                        <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold">
                            <button
                                type="button"
                                @click="activeTemplateTab = 'verification'"
                                :class="[
                                    'px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer',
                                    activeTemplateTab === 'verification'
                                        ? 'bg-white text-indigo-700 shadow-xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900',
                                ]"
                            >
                                <MailCheck class="h-3.5 w-3.5" />
                                <span>Verifikasi Pendaftaran</span>
                            </button>
                            <button
                                type="button"
                                @click="activeTemplateTab = 'reset_password'"
                                :class="[
                                    'px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer',
                                    activeTemplateTab === 'reset_password'
                                        ? 'bg-white text-indigo-700 shadow-xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900',
                                ]"
                            >
                                <KeyRound class="h-3.5 w-3.5" />
                                <span>Atur Ulang Kata Sandi</span>
                            </button>
                        </div>
                    </div>

                    <!-- Layout 2 Kolom: Kiri Editor Form, Kanan Live Pratinjau Tampilan Email -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                        <!-- Kolom Kiri: Form Editor Pesan (7 Kolom) -->
                        <div class="lg:col-span-7 space-y-3.5">
                            <!-- Info Tag Variabel -->
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/90 text-xs space-y-1.5">
                                <span class="font-bold text-slate-700 flex items-center gap-1.5 text-[11px]">
                                    <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                                    <span>Variabel Tag Dinamis (Otomatis Diisi Sistem):</span>
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10.5px] font-mono font-bold text-indigo-600 select-all">{nama}</span>
                                    <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10.5px] font-mono font-bold text-indigo-600 select-all">{email}</span>
                                    <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10.5px] font-mono font-bold text-indigo-600 select-all">{link_aksi}</span>
                                    <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10.5px] font-mono font-bold text-indigo-600 select-all">{nama_aplikasi}</span>
                                    <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10.5px] font-mono font-bold text-indigo-600 select-all">{waktu_kedaluwarsa}</span>
                                </div>
                            </div>

                            <!-- Subjek Email -->
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700">Subjek Email (Email Subject)</label>
                                <input
                                    v-model="formMail.templates[activeTemplateTab].subject"
                                    type="text"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs font-semibold text-slate-800"
                                />
                            </div>

                            <!-- Judul Header Kartu -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700">Judul Header Email</label>
                                    <input
                                        v-model="formMail.templates[activeTemplateTab].heading"
                                        type="text"
                                        class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs font-semibold text-slate-800"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700">Sub-Judul Instansi / Banner</label>
                                    <input
                                        v-model="formMail.templates[activeTemplateTab].subheading"
                                        type="text"
                                        class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs font-medium text-slate-800"
                                    />
                                </div>
                            </div>

                            <!-- Salam Pembuka -->
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700">Salam Pembuka</label>
                                <input
                                    v-model="formMail.templates[activeTemplateTab].greeting"
                                    type="text"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs font-semibold text-slate-800"
                                />
                            </div>

                            <!-- Isi Pesan Utama -->
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700">Isi Pesan Utama</label>
                                <textarea
                                    v-model="formMail.templates[activeTemplateTab].body"
                                    rows="3"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs font-normal text-slate-800 leading-relaxed resize-y"
                                ></textarea>
                            </div>

                            <!-- Teks Tombol Aksi (CTA) -->
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700">Teks Tombol Aksi (Tombol Verifikasi / Reset)</label>
                                <input
                                    v-model="formMail.templates[activeTemplateTab].button_text"
                                    type="text"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs font-bold text-indigo-700"
                                />
                            </div>

                            <!-- Catatan Keamanan / Kadaluwarsa -->
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-700">Catatan Keamanan & Masa Berlaku Tautan</label>
                                <textarea
                                    v-model="formMail.templates[activeTemplateTab].footer_note"
                                    rows="2"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs text-amber-900 bg-amber-50/40 leading-relaxed resize-y"
                                ></textarea>
                            </div>

                            <div class="pt-2 flex items-center justify-between">
                                <button
                                    type="button"
                                    @click="resetTemplateToDefault(activeTemplateTab)"
                                    class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1 cursor-pointer"
                                >
                                    <RotateCcw class="h-3.5 w-3.5" />
                                    <span>Kembalikan Teks ke Standar</span>
                                </button>

                                <button
                                    type="button"
                                    @click="submitFormMail"
                                    :disabled="formMail.processing"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 cursor-pointer disabled:opacity-50"
                                >
                                    <Loader2 v-if="formMail.processing" class="h-3.5 w-3.5 animate-spin" />
                                    <Save v-else class="h-3.5 w-3.5" />
                                    <span>Simpan Format Template Ini</span>
                                </button>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Pratinjau Tampilan Email Nyata (5 Kolom) -->
                        <div class="lg:col-span-5 space-y-3.5 sticky top-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <Eye class="h-3.5 w-3.5 text-indigo-600" />
                                    <span>Pratinjau Tampilan Email Pengguna:</span>
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono">Live HTML Preview</span>
                            </div>

                            <!-- Simulated Real HTML Email Card -->
                            <div class="rounded-2xl border border-slate-200 shadow-md overflow-hidden bg-slate-50 text-slate-800 text-xs">
                                <!-- Email Top Info Header (Subject & Sender) -->
                                <div class="bg-slate-100 p-2.5 border-b border-slate-200 text-[10.5px] space-y-0.5 font-mono">
                                    <div class="text-slate-500 truncate"><strong>Dari:</strong> {{ formMail.from_name || 'SIPEGE SPPG' }} &lt;{{ formMail.from_address || formMail.username || 'admin@sppg.id' }}&gt;</div>
                                    <div class="text-slate-800 font-bold truncate"><strong>Subjek:</strong> {{ formMail.templates[activeTemplateTab].subject }}</div>
                                </div>

                                <!-- Email Body Container -->
                                <div class="p-3 bg-slate-100/60">
                                    <div class="rounded-xl overflow-hidden bg-white border border-slate-200/90 shadow-2xs">
                                        <!-- Header Gradient -->
                                        <div class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 p-4 text-center text-white">
                                            <div class="inline-block px-3 py-1 rounded-xl bg-white/20 border border-white/30 text-xs font-black tracking-wider uppercase mb-1">
                                                SIPEGE
                                            </div>
                                            <div class="text-[10px] text-indigo-100 font-medium">
                                                {{ formMail.templates[activeTemplateTab].subheading }}
                                            </div>
                                            <div class="text-sm font-bold mt-1 text-white">
                                                {{ formMail.templates[activeTemplateTab].heading }}
                                            </div>
                                        </div>

                                        <!-- Content Body -->
                                        <div class="p-4 space-y-3">
                                            <div class="font-bold text-slate-900 text-xs">
                                                {{ formMail.templates[activeTemplateTab].greeting.replace('{nama}', $page.props.auth?.user?.nama || 'Budi Santoso') }}
                                            </div>
                                            <p class="text-[11.5px] text-slate-600 leading-relaxed whitespace-pre-line">
                                                {{ formMail.templates[activeTemplateTab].body }}
                                            </p>

                                            <!-- CTA Button -->
                                            <div class="text-center py-2">
                                                <span class="inline-block px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-bold text-xs shadow-md shadow-indigo-600/30 cursor-pointer">
                                                    {{ formMail.templates[activeTemplateTab].button_text }}
                                                </span>
                                            </div>

                                            <!-- Fallback Box -->
                                            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-[10px] text-slate-500 break-all leading-tight">
                                                Tautan cadangan: <span class="text-indigo-600 underline cursor-pointer">https://dev.sipege.site/auth/link-sample</span>
                                            </div>

                                            <!-- Notice Box -->
                                            <div class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-[10.5px] text-amber-900 leading-tight">
                                                <strong>Catatan:</strong> {{ formMail.templates[activeTemplateTab].footer_note.replace('{waktu_kedaluwarsa}', '60 menit') }}
                                            </div>
                                        </div>

                                        <!-- Footer Note -->
                                        <div class="p-3 bg-slate-50 border-t border-slate-100 text-center text-[9.5px] text-slate-400">
                                            Email ini dikirim otomatis oleh <strong>SIPEGE</strong>.<br>
                                            &copy; {{ new Date().getFullYear() }} Badan Gizi Nasional Republik Indonesia.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Box Uji Kirim Pratinjau Email Nyata ke Inbox Admin -->
                            <div class="p-3.5 rounded-xl bg-indigo-50/60 border border-indigo-100 space-y-2">
                                <span class="text-[11px] font-bold text-indigo-950 flex items-center gap-1.5">
                                    <Send class="h-3.5 w-3.5 text-indigo-600" />
                                    <span>Uji Kirim Email Pratinjau Ini ke Inbox:</span>
                                </span>
                                <div class="flex gap-1.5">
                                    <input
                                        v-model="previewTargetEmail"
                                        type="email"
                                        placeholder="Ketik email penerima uji..."
                                        class="flex-1 px-2.5 py-1.5 rounded-lg border border-indigo-200 bg-white text-xs font-mono text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    />
                                    <button
                                        type="button"
                                        @click="runSendTemplatePreview"
                                        :disabled="isSendingTemplatePreview"
                                        class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center gap-1 shadow-xs cursor-pointer disabled:opacity-50 shrink-0"
                                    >
                                        <Loader2 v-if="isSendingTemplatePreview" class="h-3.5 w-3.5 animate-spin" />
                                        <Send v-else class="h-3.5 w-3.5" />
                                        <span>Kirim Uji</span>
                                    </button>
                                </div>

                                <div
                                    v-if="templatePreviewResult"
                                    :class="[
                                        'p-2 rounded-lg text-[11px] border',
                                        templatePreviewResult.success ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200',
                                    ]"
                                >
                                    {{ templatePreviewResult.message }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- CONTENT TAB 4: AI PLATFORM & API KEY                            -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'ai'" class="space-y-3 sm:space-y-3.5 animate-in fade-in duration-150 w-full">
                <!-- 4 KARTU METRIK KPI RINGKASAN STATUS AI (2 Kolom di HP, 4 Kolom di Desktop) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 w-full">
                    <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Platform AI Aktif</p>
                            <Bot class="h-3.5 w-3.5 text-emerald-600" />
                        </div>
                        <p class="text-base sm:text-lg font-bold text-slate-900 mt-1 truncate">
                            {{ activeConfigSummary.platformName }}
                        </p>
                        <p class="text-[10px] text-emerald-600 font-medium mt-0.5 truncate">
                            Oleh {{ activeConfigSummary.company }}
                        </p>
                    </div>

                    <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Status Koneksi API</p>
                            <Zap class="h-3.5 w-3.5 text-amber-500" />
                        </div>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span
                                :class="[
                                    'h-2 w-2 rounded-full',
                                    isAiActiveAndConnected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500',
                                ]"
                            ></span>
                            <span class="text-base sm:text-lg font-bold text-slate-900">
                                {{ isAiActiveAndConnected ? "Terhubung" : "Belum Dites" }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-500 font-medium mt-0.5 truncate">
                            Validasi token & respon endpoint
                        </p>
                    </div>

                    <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Model LLM Dipilih</p>
                            <Cpu class="h-3.5 w-3.5 text-indigo-600" />
                        </div>
                        <p class="text-xs sm:text-sm font-bold text-slate-900 truncate font-mono mt-1">
                            {{ activeConfigSummary.activeModel }}
                        </p>
                        <p class="text-[10px] text-indigo-600 font-medium mt-0.5 truncate">
                            Target eksekusi inferensi
                        </p>
                    </div>

                    <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Verifikasi Terakhir</p>
                            <Clock class="h-3.5 w-3.5 text-slate-600" />
                        </div>
                        <p class="text-xs sm:text-sm font-bold text-slate-900 mt-1 truncate">
                            {{ activeConfigSummary.lastTested }}
                        </p>
                        <p class="text-[10px] text-slate-500 font-medium mt-0.5 truncate">
                            Riwayat pengujian koneksi live
                        </p>
                    </div>
                </div>

                <!-- TAB SELECTOR PLATFORM AI (Rata Kiri Kanan Penuh, Tanpa Ada yang Berdiri Sendiri) -->
                <div class="p-2 sm:p-2.5 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-1.5 w-full">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider px-1">
                        Pilih Penyedia Layanan Kecerdasan Buatan (AI Provider):
                    </span>

                    <div class="flex flex-wrap gap-1.5 w-full">
                        <button
                            v-for="prov in supportedProviders"
                            :key="prov.id"
                            type="button"
                            @click="syncFormWithProvider(prov.id)"
                            :class="[
                                'p-2 sm:p-2.5 rounded-lg border text-center transition-all flex flex-col items-center justify-center gap-0.5 relative cursor-pointer text-xs flex-1 min-w-[calc(50%-0.375rem)] lg:min-w-0',
                                selectedProviderId === prov.id
                                    ? 'border-emerald-600 bg-emerald-50/70 text-emerald-900 font-bold ring-1 ring-emerald-600 shadow-2xs'
                                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700',
                            ]"
                        >
                            <span
                                v-if="savedConfigs[prov.id]?.is_active"
                                class="absolute top-1 right-1 h-1.5 w-1.5 rounded-full bg-emerald-500 ring-1 ring-white"
                                title="Platform Aktif"
                            ></span>

                            <span class="text-xs font-bold leading-tight truncate w-full">
                                {{ prov.name }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-normal leading-tight truncate w-full">
                                {{ prov.company }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- KARTU FORM KONFIGURASI API KEY -->
                <form @submit.prevent="submitFormAi" class="p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3.5 w-full">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100">
                                <KeyRound class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="font-bold text-xs sm:text-sm text-slate-900">
                                    Konfigurasi Token API: {{ selectedProviderMeta.name }}
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Token API disimpan di database secara global untuk semua pengguna SIPEGE.
                                </p>
                            </div>
                        </div>

                        <a
                            :href="selectedProviderMeta.docs_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline shrink-0"
                        >
                            <span>Dapatkan API Key {{ selectedProviderMeta.name }}</span>
                            <ExternalLink class="h-3.5 w-3.5" />
                        </a>
                    </div>

                    <!-- Input API Key -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">
                            Kunci API (API Key / Token)
                        </label>
                        <div class="relative">
                            <input
                                v-model="formAi.api_key"
                                :type="showApiKey ? 'text' : 'password'"
                                :placeholder="selectedProviderMeta.key_placeholder"
                                class="w-full pl-3 pr-20 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                            <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                <span v-if="isDebouncing" class="text-[10px] text-slate-400 animate-pulse flex items-center gap-1">
                                    <Loader2 class="h-3 w-3 animate-spin" />
                                </span>
                                <button
                                    type="button"
                                    @click="showApiKey = !showApiKey"
                                    class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer"
                                >
                                    <EyeOff v-if="showApiKey" class="h-3.5 w-3.5" />
                                    <Eye v-else class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pilihan Model Mode -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Mode Pemilihan Model AI
                            </label>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <button
                                    type="button"
                                    @click="modelMode = 'auto'; formAi.model = 'auto'"
                                    :class="[
                                        'px-3 py-2 rounded-lg border text-center font-semibold transition-all cursor-pointer',
                                        modelMode === 'auto'
                                            ? 'bg-emerald-50 border-emerald-500 text-emerald-900 shadow-2xs'
                                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                    ]"
                                >
                                    Otomatis (Direkomendasikan)
                                </button>
                                <button
                                    type="button"
                                    @click="modelMode = 'custom'"
                                    :class="[
                                        'px-3 py-2 rounded-lg border text-center font-semibold transition-all cursor-pointer',
                                        modelMode === 'custom'
                                            ? 'bg-emerald-50 border-emerald-500 text-emerald-900 shadow-2xs'
                                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                    ]"
                                >
                                    Pilih Manual (Kustom)
                                </button>
                            </div>
                        </div>

                        <!-- Dropdown Model Kustom -->
                        <div v-if="modelMode === 'custom'" class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Nama / Versi Model
                            </label>
                            <select
                                v-if="liveModels.length > 0"
                                v-model="formAi.model"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            >
                                <option v-for="m in liveModels" :key="m.id" :value="m.id">
                                    {{ m.name }}
                                </option>
                            </select>
                            <input
                                v-else
                                v-model="formAi.model"
                                type="text"
                                :placeholder="selectedProviderMeta.default_model"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-xs font-mono"
                            />
                        </div>
                    </div>

                    <!-- Status Test Realtime AI -->
                    <div
                        v-if="aiTestResult"
                        :class="[
                            'p-2.5 rounded-lg border text-xs animate-in fade-in',
                            aiTestResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900',
                        ]"
                    >
                        <div class="flex items-center gap-1.5 font-bold mb-0.5">
                            <CheckCircle2 v-if="aiTestResult.success" class="h-3.5 w-3.5 text-emerald-600" />
                            <AlertCircle v-else class="h-3.5 w-3.5 text-rose-600" />
                            <span>{{ aiTestResult.message }}</span>
                        </div>
                    </div>

                    <!-- Checkbox Jadikan Platform Aktif -->
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer select-none">
                            <input
                                v-model="formAi.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            />
                            <span>Gunakan platform ini sebagai engine AI operasional utama sistem</span>
                        </label>
                    </div>

                    <!-- Tombol Aksi Form AI -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="runTestAiConnection(true)"
                            :disabled="isAiTesting || !formAi.api_key"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer disabled:opacity-40"
                        >
                            <Loader2 v-if="isAiTesting" class="h-3.5 w-3.5 animate-spin" />
                            <Sparkles v-else class="h-3.5 w-3.5 text-amber-500" />
                            <span>{{ isAiTesting ? 'Menguji Token Live...' : 'Uji Koneksi Token' }}</span>
                        </button>

                        <button
                            type="submit"
                            :disabled="formAi.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="formAi.processing" class="h-3.5 w-3.5 animate-spin" />
                            <Save v-else class="h-3.5 w-3.5" />
                            <span>Simpan Konfigurasi AI</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
