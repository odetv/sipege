<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from "axios";
import {
    KeyRound,
    Sparkles,
    CheckCircle2,
    AlertCircle,
    Loader2,
    ExternalLink,
    Eye,
    EyeOff,
    Bot,
    Cpu,
    Zap,
    ShieldCheck,
    RefreshCw,
    HelpCircle,
    Layers,
    Check,
    Clock,
    Activity,
    Server,
    Save,
    Sliders,
} from "lucide-vue-next";

const props = defineProps({
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
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// Provider yang sedang dipilih di tab
const selectedProviderId = ref(props.defaultActiveProvider || "gemini");

// Data info provider saat ini
const currentProviderInfo = computed(() => {
    return (
        props.supportedProviders.find((p) => p.id === selectedProviderId.value) ||
        props.supportedProviders[0] ||
        {}
    );
});

// Form state untuk provider yang sedang diedit
const form = useForm({
    provider: selectedProviderId.value,
    api_key: "",
    model: "auto",
    is_active: true,
    status_koneksi: "belum_dites",
    pesan_koneksi: "",
});

// Mode pemilihan model: 'auto' | 'custom'
const modelMode = ref("auto");

// State UI lainnya
const showApiKey = ref(false);
const isTesting = ref(false);
const testResult = ref(null);
const liveModels = ref([]);
const isDebouncing = ref(false);
let debounceTimeout = null;

// Ringkasan untuk 4 kartu metrik KPI
const activeConfigSummary = computed(() => {
    const activeProv =
        props.supportedProviders.find(
            (p) => props.savedConfigs[p.id]?.is_active
        ) || currentProviderInfo.value;
    const cfg = props.savedConfigs[activeProv.id] || {};
    return {
        platformName: activeProv.name,
        company: activeProv.company,
        isConnected: cfg.status_koneksi === "terhubung",
        activeModel: cfg.model || "Otomatis (Rekomendasi)",
        lastTested: cfg.last_tested_at || "Belum pernah",
    };
});

// Sinkronkan form saat berganti platform
function syncFormWithProvider(providerId) {
    selectedProviderId.value = providerId;
    form.provider = providerId;
    testResult.value = null;
    liveModels.value = [];

    const existing = props.savedConfigs[providerId];
    if (existing) {
        form.api_key = existing.api_key || "";
        form.model = existing.model || "auto";
        form.is_active = Boolean(existing.is_active);
        form.status_koneksi = existing.status_koneksi || "belum_dites";
        form.pesan_koneksi = existing.pesan_koneksi || "";

        modelMode.value = form.model === "auto" ? "auto" : "custom";

        if (existing.status_koneksi === "terhubung") {
            testResult.value = {
                success: true,
                status: "terhubung",
                message: existing.pesan_koneksi || "Tersimpan & Terverifikasi.",
            };
        }
    } else {
        const info = props.supportedProviders.find((p) => p.id === providerId);
        form.api_key = "";
        form.model = "auto";
        modelMode.value = "auto";
        form.is_active = providerId === props.defaultActiveProvider;
        form.status_koneksi = "belum_dites";
        form.pesan_koneksi = "";
    }

    // Jika sudah ada API key tersimpan, ambil daftar model secara realtime
    if (form.api_key && form.api_key.trim().length > 10) {
        runTestConnection(false);
    }
}

// Watch perubahan modelMode (auto vs custom)
watch(modelMode, (newMode) => {
    if (newMode === "auto") {
        form.model = "auto";
    } else if (newMode === "custom" && form.model === "auto") {
        if (liveModels.value.length > 0) {
            form.model = liveModels.value[0].id;
        } else {
            form.model = currentProviderInfo.value.default_model || "";
        }
    }
});

// Watch pergantian provider tab
watch(selectedProviderId, (newId) => {
    if (form.provider !== newId) {
        syncFormWithProvider(newId);
    }
});

// Watch input API key untuk realtime auto-test koneksi (debounce 800ms)
watch(
    () => form.api_key,
    (newVal) => {
        if (debounceTimeout) clearTimeout(debounceTimeout);
        if (!newVal || newVal.trim().length < 12) {
            testResult.value = null;
            return;
        }

        isDebouncing.value = true;
        debounceTimeout = setTimeout(() => {
            isDebouncing.value = false;
            runTestConnection(false);
        }, 800);
    }
);

onMounted(() => {
    syncFormWithProvider(selectedProviderId.value);
});

// Jalankan Tes Koneksi & Ambil Model Realtime
async function runTestConnection(isManual = true) {
    if (!form.api_key || form.api_key.trim().length < 6) {
        if (isManual) {
            alert("Harap masukkan API Key terlebih dahulu untuk diuji.");
        }
        return;
    }

    isTesting.value = true;
    if (isManual) {
        testResult.value = null;
    }

    try {
        const response = await axios.post(
            route("pengaturan.api-key.test-koneksi"),
            {
                provider: form.provider,
                api_key: form.api_key.trim(),
                model: form.model,
            }
        );

        if (response.data.success) {
            testResult.value = {
                success: true,
                status: "terhubung",
                message: response.data.message,
            };
            liveModels.value = response.data.models || [];
            form.status_koneksi = "terhubung";
            form.pesan_koneksi = response.data.message;

            if (modelMode.value === "custom" && (!form.model || form.model === "auto")) {
                if (liveModels.value.length > 0) {
                    form.model = liveModels.value[0].id;
                }
            }
        } else {
            testResult.value = {
                success: false,
                status: "gagal",
                message:
                    response.data.message || "Gagal menghubungkan ke platform AI.",
            };
            form.status_koneksi = "gagal";
            form.pesan_koneksi = response.data.message;
        }
    } catch (err) {
        console.error("Test Connection Error:", err);
        const errMsg =
            err.response?.data?.message ||
            err.message ||
            "Terjadi kesalahan saat menghubungi API server platform.";

        testResult.value = {
            success: false,
            status: "gagal",
            message: errMsg,
        };
        form.status_koneksi = "gagal";
        form.pesan_koneksi = errMsg;
    } finally {
        isTesting.value = false;
    }
}

// Simpan Pengaturan
function submitForm() {
    form.post(route("pengaturan.api-key.update"), {
        preserveScroll: true,
        onSuccess: () => {
            // refresh data
        },
    });
}
</script>

<template>
    <Head title="API Key - SIPEGE" />

    <AppLayout
        title="API Key"
        subtitle="Pengaturan Integrasi AI"
        :unit-sppg="unitSppg"
    >
        <div class="space-y-6 pb-16">
            <!-- ─── HEADER UTAMA STANDAR SIPEGE ─── -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-indigo-600 text-white shadow-xs">
                        <KeyRound class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                Pengaturan API Key
                            </h1>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                Multi-Platform AI
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            Konfigurasi API key dan model AI untuk pencatatan cerdas residu dan otomatisasi SPPG.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 px-4 py-2 sm:py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs sm:text-sm font-semibold shadow-2xs transition-colors cursor-pointer"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        <span>Simpan Pengaturan</span>
                    </button>
                </div>
            </div>

            <!-- Flash Message Banner -->
            <div
                v-if="flash.success"
                class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-2xs animate-in fade-in"
            >
                <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" />
                <div class="text-xs sm:text-sm">
                    <span class="font-bold">Berhasil:</span> {{ flash.success }}
                </div>
            </div>

            <div
                v-if="flash.error"
                class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3 shadow-2xs animate-in fade-in"
            >
                <AlertCircle class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
                <div class="text-xs sm:text-sm">
                    <span class="font-bold">Gagal:</span> {{ flash.error }}
                </div>
            </div>

            <!-- ─── 4 KARTU KPI RINGKASAN METRIK (PERSIS GAYA SIPEGE) ─── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Card 1: Platform Aktif -->
                <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3.5">
                    <div class="p-3 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 shrink-0">
                        <Cpu class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-0.5">
                            Platform Aktif
                        </p>
                        <p class="text-sm sm:text-base font-bold text-slate-900 leading-normal truncate">
                            {{ activeConfigSummary.platformName }}
                        </p>
                        <p class="text-xs text-slate-500 truncate mt-0.5">
                            Penyedia: {{ activeConfigSummary.company }}
                        </p>
                    </div>
                </div>

                <!-- Card 2: Status Sambungan -->
                <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3.5">
                    <div
                        class="p-3 rounded-lg border shrink-0"
                        :class="
                            activeConfigSummary.isConnected
                                ? 'bg-emerald-50 text-emerald-600 border-emerald-100'
                                : 'bg-amber-50 text-amber-600 border-amber-100'
                        "
                    >
                        <Activity class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-0.5">
                            Status Sambungan
                        </p>
                        <p
                            class="text-sm sm:text-base font-bold leading-normal"
                            :class="activeConfigSummary.isConnected ? 'text-emerald-700' : 'text-amber-700'"
                        >
                            {{ activeConfigSummary.isConnected ? 'Terhubung Live' : 'Belum Dikonfigurasi' }}
                        </p>
                        <p class="text-xs text-slate-500 truncate mt-0.5">
                            Diuji: {{ activeConfigSummary.lastTested }}
                        </p>
                    </div>
                </div>

                <!-- Card 3: Model AI Digunakan -->
                <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3.5">
                    <div class="p-3 rounded-lg bg-sky-50 text-sky-600 border border-sky-100 shrink-0">
                        <Layers class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-0.5">
                            Model AI Aktif
                        </p>
                        <p class="text-sm sm:text-base font-bold text-slate-900 leading-normal truncate font-mono">
                            {{ activeConfigSummary.activeModel }}
                        </p>
                        <p class="text-xs text-slate-500 truncate mt-0.5">
                            Catat Pintar Residu
                        </p>
                    </div>
                </div>

                <!-- Card 4: Platform Tersedia -->
                <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3.5">
                    <div class="p-3 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 shrink-0">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-0.5">
                            Dukungan AI
                        </p>
                        <p class="text-sm sm:text-base font-bold text-slate-900 leading-normal">
                            7 Platform
                        </p>
                        <p class="text-xs text-slate-500 truncate mt-0.5">
                            Gemini, OpenAI, Groq, dll.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── PILIHAN PLATFORM AI (KARTU PILIHAN BERSIH & TIDAK BERHIMPITAN) ─── -->
            <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-2xs space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">
                            Pilih Platform AI
                        </h2>
                        <p class="text-xs text-slate-500">
                            Pilih platform yang ingin dikonfigurasi API key dan modelnya.
                        </p>
                    </div>
                    <span class="text-xs font-medium text-slate-400">
                        {{ supportedProviders.length }} Platform Didukung
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
                    <button
                        v-for="prov in supportedProviders"
                        :key="prov.id"
                        type="button"
                        @click="syncFormWithProvider(prov.id)"
                        :class="[
                            'flex flex-col items-center text-center p-3 rounded-xl border transition-all cursor-pointer relative',
                            selectedProviderId === prov.id
                                ? 'bg-indigo-50/80 border-indigo-600 ring-2 ring-indigo-500/20 text-indigo-950 font-bold shadow-2xs'
                                : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700'
                        ]"
                    >
                        <Bot
                            class="h-5 w-5 mb-1.5 shrink-0"
                            :class="selectedProviderId === prov.id ? 'text-indigo-600' : 'text-slate-400'"
                        />
                        <span class="text-xs leading-snug line-clamp-1 w-full">{{ prov.name }}</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">{{ prov.company }}</span>

                        <!-- Dot indikator jika platform aktif -->
                        <span
                            v-if="savedConfigs[prov.id]?.is_active"
                            class="absolute top-2 right-2 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"
                            title="Platform Aktif Utama"
                        ></span>
                    </button>
                </div>
            </div>

            <!-- ─── KARTU FORM KONFIGURASI PLATFORM TERPILIH (LIGHT THEME BERSIH) ─── -->
            <form @submit.prevent="submitForm">
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <!-- Header Kartu -->
                    <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 shrink-0">
                                <KeyRound class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-slate-900">
                                        Konfigurasi {{ currentProviderInfo.name }}
                                    </h3>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200/80 text-slate-700">
                                        {{ currentProviderInfo.company }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ currentProviderInfo.help_text }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 self-start sm:self-auto">
                            <!-- Toggle Jadikan Platform Utama -->
                            <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors shadow-2xs">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                                />
                                <span class="text-xs font-semibold text-slate-800 select-none">
                                    Aktifkan Platform Ini
                                </span>
                            </label>

                            <a
                                v-if="currentProviderInfo.help_url"
                                :href="currentProviderInfo.help_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline px-2 py-1 rounded"
                            >
                                <span>Dapatkan Key</span>
                                <ExternalLink class="h-3.5 w-3.5" />
                            </a>
                        </div>
                    </div>

                    <!-- Body Kartu -->
                    <div class="p-5 sm:p-6 space-y-6">
                        <!-- Input API Key -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <span>API Key {{ currentProviderInfo.name }}</span>
                                    <span class="text-rose-500">*</span>
                                </label>
                                <span v-if="isDebouncing" class="text-xs font-medium text-indigo-600 flex items-center gap-1">
                                    <Loader2 class="h-3 w-3 animate-spin" />
                                    <span>Mengetes koneksi otomatis...</span>
                                </span>
                            </div>

                            <div class="flex flex-col sm:flex-row items-stretch gap-2.5">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <KeyRound class="h-4 w-4" />
                                    </div>
                                    <input
                                        v-model="form.api_key"
                                        :type="showApiKey ? 'text' : 'password'"
                                        :placeholder="'Masukkan API Key (Contoh: ' + (currentProviderInfo.placeholder || '...') + ')'"
                                        class="w-full pl-10 pr-10 py-2.5 rounded-lg border text-xs sm:text-sm font-mono transition-colors focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none"
                                        :class="
                                            testResult?.success
                                                ? 'border-emerald-300 bg-emerald-50/20'
                                                : (testResult?.success === false ? 'border-rose-300 bg-rose-50/20' : 'border-slate-300 bg-white')
                                        "
                                    />
                                    <button
                                        type="button"
                                        @click="showApiKey = !showApiKey"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                                        :title="showApiKey ? 'Sembunyikan' : 'Tampilkan'"
                                    >
                                        <EyeOff v-if="showApiKey" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    @click="runTestConnection(true)"
                                    :disabled="isTesting || !form.api_key"
                                    class="px-4 py-2.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 disabled:opacity-50 text-indigo-700 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer shrink-0"
                                >
                                    <Loader2 v-if="isTesting" class="h-4 w-4 animate-spin" />
                                    <RefreshCw v-else class="h-4 w-4" />
                                    <span>{{ isTesting ? 'Menguji...' : 'Uji Koneksi & Ambil Model' }}</span>
                                </button>
                            </div>

                            <p class="text-xs text-slate-500 mt-1">
                                API Key disimpan aman di database unit SPPG. Saat token diinput, sistem langsung memverifikasi dan memuat model secara realtime.
                            </p>
                        </div>

                        <!-- Banner Feedback Hasil Tes Realtime -->
                        <div
                            v-if="testResult"
                            class="p-4 rounded-xl border text-xs sm:text-sm animate-in fade-in"
                            :class="
                                testResult.success
                                    ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
                                    : 'bg-rose-50 border-rose-200 text-rose-900'
                            "
                        >
                            <div class="flex items-start gap-3">
                                <CheckCircle2 v-if="testResult.success" class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" />
                                <AlertCircle v-else class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="font-bold">
                                            {{ testResult.success ? 'Koneksi Berhasil Terhubung' : 'Koneksi Gagal / API Key Tidak Valid' }}
                                        </p>
                                        <span
                                            v-if="testResult.success && liveModels.length > 0"
                                            class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-200 text-emerald-800"
                                        >
                                            {{ liveModels.length }} Model Aktif
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                        {{ testResult.message }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ─── PILIHAN MODEL AI (BERSIH & TIDAK BERANTAKAN) ─── -->
                        <div class="space-y-4 pt-4 border-t border-slate-100">
                            <div>
                                <label class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <Layers class="h-4 w-4 text-indigo-600" />
                                    <span>Pemilihan Model AI</span>
                                </label>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Pilih mode otomatis untuk kemudahan, atau pilih model spesifik yang Anda inginkan.
                                </p>
                            </div>

                            <div class="space-y-3">
                                <!-- Pilihan 1: Otomatis (Rekomendasi) -->
                                <label
                                    class="p-4 rounded-xl border transition-all flex items-start gap-3.5 cursor-pointer"
                                    :class="
                                        modelMode === 'auto'
                                            ? 'bg-indigo-50/70 border-indigo-600 ring-2 ring-indigo-500/20'
                                            : 'bg-white border-slate-200 hover:border-slate-300'
                                    "
                                >
                                    <input
                                        type="radio"
                                        name="model_mode"
                                        value="auto"
                                        v-model="modelMode"
                                        class="mt-1 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs sm:text-sm font-bold text-slate-900">
                                                Otomatis (Rekomendasi Flash Terbaik)
                                            </span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">
                                                Rekomendasi
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Sistem otomatis memilih model tercepat, paling stabil, dan paling hemat dari {{ currentProviderInfo.name }} untuk tugas ekstraksi residu.
                                        </p>
                                    </div>
                                </label>

                                <!-- Pilihan 2: Pilih Model Manual / Spesifik -->
                                <label
                                    class="p-4 rounded-xl border transition-all flex items-start gap-3.5 cursor-pointer"
                                    :class="
                                        modelMode === 'custom'
                                            ? 'bg-indigo-50/70 border-indigo-600 ring-2 ring-indigo-500/20'
                                            : 'bg-white border-slate-200 hover:border-slate-300'
                                    "
                                >
                                    <input
                                        type="radio"
                                        name="model_mode"
                                        value="custom"
                                        v-model="modelMode"
                                        class="mt-1 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs sm:text-sm font-bold text-slate-900">
                                                Pilih Model Spesifik
                                            </span>
                                            <span v-if="liveModels.length > 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                {{ liveModels.length }} Model Terdeteksi
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Pilih model tertentu secara manual dari daftar model yang didapat dari akun Anda.
                                        </p>

                                        <!-- Dropdown Select Model jika Custom Mode Aktif -->
                                        <div v-if="modelMode === 'custom'" class="mt-3 space-y-2">
                                            <select
                                                v-if="liveModels.length > 0"
                                                v-model="form.model"
                                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-xs sm:text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                            >
                                                <option
                                                    v-for="m in liveModels"
                                                    :key="m.id"
                                                    :value="m.id"
                                                >
                                                    {{ m.name }} ({{ m.id }}) {{ m.badge ? '— ' + m.badge : '' }}
                                                </option>
                                            </select>

                                            <!-- Fallback jika belum klik tes -->
                                            <div v-else class="space-y-1.5">
                                                <input
                                                    v-model="form.model"
                                                    type="text"
                                                    placeholder="Contoh: gemini-2.0-flash atau gpt-4o-mini"
                                                    class="w-full px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-xs sm:text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                                />
                                                <p class="text-[11px] text-slate-500">
                                                    *Klik tombol <strong>Uji Koneksi & Ambil Model</strong> di atas agar sistem memuat daftar model secara otomatis ke dalam pilihan dropdown.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Kartu -->
                    <div class="px-5 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs text-slate-500 flex items-center gap-1.5">
                            <Clock class="h-4 w-4 text-slate-400" />
                            <span v-if="savedConfigs[form.provider]?.last_tested_at">
                                Terakhir diuji: {{ savedConfigs[form.provider].last_tested_at }}
                            </span>
                            <span v-else>
                                Belum pernah diuji
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <button
                                type="button"
                                @click="runTestConnection(true)"
                                :disabled="isTesting || !form.api_key"
                                class="px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-50 text-slate-700 font-semibold text-xs flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': isTesting }" />
                                <span>Tes Sambungan</span>
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-xs flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer"
                            >
                                <Loader2 v-if="form.processing" class="h-3.5 w-3.5 animate-spin" />
                                <Check v-else class="h-3.5 w-3.5" />
                                <span>Simpan Pengaturan {{ currentProviderInfo.name }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- ─── 3 KARTU INFORMASI PANDUAN STANDAR SIPEGE ─── -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-2">
                    <div class="p-2.5 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 w-fit">
                        <Cpu class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Multi-Platform Terbuka</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Mendukung Google Gemini, OpenAI, Groq, DeepSeek, Claude, OpenRouter, dan Mistral AI dalam satu sistem terpadu.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-2">
                    <div class="p-2.5 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 w-fit">
                        <Zap class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Realtime Model Discovery</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Saat API key dimasukkan, sistem otomatis mengambil model yang tersedia dari akun Anda sehingga tidak perlu input manual.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-2">
                    <div class="p-2.5 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 w-fit">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Aman & Smart Heuristic</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        API key tidak ditampilkan di modul pencatatan. Jika kuota API habis, sistem otomatis beralih ke Smart Local Parser.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
