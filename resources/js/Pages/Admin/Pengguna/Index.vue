<script setup>
import { ref, computed } from "vue";
import { Head, router, usePage, useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Users,
    UserCheck,
    Shield,
    ShieldCheck,
    Search,
    RotateCcw,
    CheckCircle2,
    AlertCircle,
    Eye,
    Edit,
    Trash2,
    MailCheck,
    Building2,
    CreditCard,
    X,
    Clock,
    Phone,
    Mail,
    Check,
} from "lucide-vue-next";

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    daftar_paket: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

// Current logged in user ID
const currentUserId = computed(() => page.props.auth?.user?.id);
function isCurrentUser(u) {
    return u && u.id === currentUserId.value;
}

// State Filter
const search = ref(props.filters.search || "");
const role = ref(props.filters.role || "");
const paket = ref(props.filters.paket || "");
const statusPembayaran = ref(props.filters.status_pembayaran || "");
const statusOnboarding = ref(props.filters.status_onboarding || "");
const emailVerified = ref(props.filters.email_verified || "");

// State Modals
const selectedUser = ref(null);
const showDetailModal = ref(false);
const showEditRoleModal = ref(false);
const activeDetailTab = ref("profil"); // 'profil' | 'unit' | 'langganan'

// Delete Modal State
const showDeleteModal = ref(false);
const userToDelete = ref(null);
const isDeleting = ref(false);

// Verify Email Modal State
const showVerifyModal = ref(false);
const userToVerify = ref(null);

// Form Edit Role & Status
const editForm = useForm({
    role: "member",
    paket_langganan: "basic",
    status_pembayaran: "lunas",
    status_onboarding: "selesai",
});

// Flash feedback
const flash = computed(() => page.props.flash || {});

// Debounce timer for search
let searchTimer = null;

function applyFilters() {
    router.get(
        route("admin.pengguna.index"),
        {
            search: search.value || undefined,
            role: role.value || undefined,
            paket: paket.value || undefined,
            status_pembayaran: statusPembayaran.value || undefined,
            status_onboarding: statusOnboarding.value || undefined,
            email_verified: emailVerified.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 450);
}

function resetFilters() {
    search.value = "";
    role.value = "";
    paket.value = "";
    statusPembayaran.value = "";
    statusOnboarding.value = "";
    emailVerified.value = "";
    router.get(route("admin.pengguna.index"), {}, { preserveState: false });
}

// Role Formatter: Cukup Guest, Member, atau Administrator
function formatRole(r) {
    if (!r) return "Guest";
    const s = String(r).toLowerCase().trim();
    if (s === "administrator" || s === "superadmin") return "Administrator";
    if (s === "member") return "Member";
    return "Guest";
}

// Detail Modal Actions
function openDetail(u) {
    selectedUser.value = u;
    activeDetailTab.value = "profil";
    showDetailModal.value = true;
}

function closeDetail() {
    showDetailModal.value = false;
    selectedUser.value = null;
}

// Edit Role Modal Actions
function openEditRole(u) {
    if (isCurrentUser(u)) {
        return;
    }
    selectedUser.value = u;
    editForm.role = u.role || "guest";
    editForm.paket_langganan = u.paket_langganan || "basic";
    editForm.status_pembayaran = u.status_pembayaran || "menunggu_pembayaran";
    editForm.status_onboarding = u.status_onboarding || "belum_mulai";
    showEditRoleModal.value = true;
}

function closeEditRole() {
    showEditRoleModal.value = false;
    selectedUser.value = null;
    editForm.reset();
}

function submitEditRole() {
    if (!selectedUser.value || isCurrentUser(selectedUser.value)) return;
    editForm.put(route("admin.pengguna.update-role", selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditRole();
        },
    });
}

// Manual Email Verification Modal Actions
function openVerifyModal(u) {
    if (u.is_email_verified || isCurrentUser(u)) return;
    userToVerify.value = u;
    showVerifyModal.value = true;
}

function closeVerifyModal() {
    showVerifyModal.value = false;
    userToVerify.value = null;
}

function submitVerifyEmail() {
    if (!userToVerify.value) return;
    router.post(
        route("admin.pengguna.verify-email", userToVerify.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeVerifyModal();
            },
        }
    );
}

// Delete Account Modal Actions
function openDeleteModal(u) {
    if (isCurrentUser(u)) return;
    userToDelete.value = u;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    userToDelete.value = null;
}

function submitDeleteUser() {
    if (!userToDelete.value) return;
    isDeleting.value = true;
    router.delete(route("admin.pengguna.destroy", userToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            closeDeleteModal();
        },
    });
}

// Format Helpers
function getInitials(name) {
    if (!name) return "U";
    const parts = name.trim().split(" ");
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
}

function formatRp(val) {
    if (!val && val !== 0) return "Rp 0";
    return "Rp " + Number(val).toLocaleString("id-ID");
}
</script>

<template>
    <AppLayout title="Pengguna - Admin SIPEGE">
        <Head title="Pengguna - Admin SIPEGE" />

        <div class="space-y-4 pb-12">
            <!-- ─── HEADER SECTION (Seragam Dengan Petugas, Supplier & Layanan) ─── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs gap-3">
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <div class="p-2 sm:p-2.5 rounded-xl bg-primary/10 text-primary border border-primary/20 shrink-0">
                        <ShieldCheck class="h-5 w-5 sm:h-6 sm:w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                                Monitoring & Manajemen Pengguna
                            </h1>
                            <span class="px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Admin Panel
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Kontrol seluruh akun pengguna terdaftar, status verifikasi email, paket langganan, dan unit SPPG operasional
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="applyFilters"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-2xs transition-colors cursor-pointer"
                        title="Segarkan Data"
                    >
                        <RotateCcw class="w-3.5 h-3.5 text-slate-500" />
                        <span>Segarkan</span>
                    </button>
                </div>
            </div>

            <!-- ─── Flash Alert Notification ──────────────────────────────── -->
            <div
                v-if="flash.success"
                class="flex items-center justify-between p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs shadow-2xs animate-in fade-in duration-200"
            >
                <div class="flex items-center gap-2.5">
                    <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                    <span class="font-medium">{{ flash.success }}</span>
                </div>
                <button
                    type="button"
                    @click="flash.success = null"
                    class="text-emerald-600 hover:text-emerald-800 cursor-pointer"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div
                v-if="flash.error"
                class="flex items-center justify-between p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-2xs animate-in fade-in duration-200"
            >
                <div class="flex items-center gap-2.5">
                    <AlertCircle class="h-4 w-4 text-rose-600 shrink-0" />
                    <span class="font-medium">{{ flash.error }}</span>
                </div>
                <button
                    type="button"
                    @click="flash.error = null"
                    class="text-rose-600 hover:text-rose-800 cursor-pointer"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- ─── 5 STATISTIC CARDS (Seragam Dengan Format Petugas & SPPG) ─── -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-2 sm:gap-3.5">
                <!-- Card 1: Total Pengguna -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-blue-50 text-blue-600 shrink-0">
                        <Users class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Total Akun
                        </p>
                        <p class="text-base sm:text-xl font-bold text-slate-900 leading-tight">
                            {{ stats.total_users || 0 }} <span class="text-xs font-normal text-slate-500">akun</span>
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-emerald-600 font-semibold mt-0.5 truncate">
                            {{ stats.total_verified_emails || 0 }} terverifikasi
                        </p>
                    </div>
                </div>

                <!-- Card 2: Administrator -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-indigo-50 text-indigo-600 shrink-0">
                        <Shield class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Administrator
                        </p>
                        <p class="text-base sm:text-xl font-bold text-indigo-700 leading-tight">
                            {{ stats.total_administrators || 0 }} <span class="text-xs font-normal text-slate-500">admin</span>
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-400 mt-0.5 truncate">
                            Akses sistem
                        </p>
                    </div>
                </div>

                <!-- Card 3: Member -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-emerald-50 text-emerald-600 shrink-0">
                        <UserCheck class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Member
                        </p>
                        <p class="text-base sm:text-xl font-bold text-emerald-700 leading-tight">
                            {{ stats.total_members || 0 }} <span class="text-xs font-normal text-slate-500">member</span>
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-400 mt-0.5 truncate">
                            Langganan aktif
                        </p>
                    </div>
                </div>

                <!-- Card 4: Guest -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-amber-50 text-amber-600 shrink-0">
                        <Clock class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Guest
                        </p>
                        <p class="text-base sm:text-xl font-bold text-amber-700 leading-tight">
                            {{ stats.total_guests || 0 }} <span class="text-xs font-normal text-slate-500">guest</span>
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-400 mt-0.5 truncate">
                            Belum langganan
                        </p>
                    </div>
                </div>

                <!-- Card 5: SPPG Onboarded -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5 col-span-2 lg:col-span-1">
                    <div class="p-2 sm:p-3 rounded-lg bg-cyan-50 text-cyan-600 shrink-0">
                        <Building2 class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            SPPG Onboarded
                        </p>
                        <p class="text-base sm:text-xl font-bold text-cyan-700 leading-tight">
                            {{ stats.total_onboarded || 0 }} <span class="text-xs font-normal text-slate-500">unit</span>
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-400 mt-0.5 truncate">
                            {{ stats.total_pending_onboarding || 0 }} proses
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── FILTER & SEARCH TOOLBAR ────────────────────────────────── -->
            <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="search"
                        @input="onSearchInput"
                        type="text"
                        placeholder="Cari nama, email, NIK, telepon, ID SPPG, atau nama unit..."
                        class="w-full pl-10 pr-8 py-2 text-xs bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 focus:border-indigo-500 rounded-lg outline-hidden transition-all placeholder:text-slate-400"
                    />
                    <button
                        v-if="search"
                        @click="search = ''; applyFilters();"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                    <!-- Role Filter: Hanya Guest, Member, atau Administrator -->
                    <select
                        v-model="role"
                        @change="applyFilters"
                        class="px-3 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-hidden cursor-pointer"
                    >
                        <option value="">Semua Role</option>
                        <option value="administrator">Administrator</option>
                        <option value="member">Member</option>
                        <option value="guest">Guest</option>
                    </select>

                    <!-- Paket Filter -->
                    <select
                        v-model="paket"
                        @change="applyFilters"
                        class="px-3 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-hidden cursor-pointer"
                    >
                        <option value="">Semua Paket</option>
                        <option value="free">Free (Trial)</option>
                        <option value="basic">Basic</option>
                        <option value="standart">Standart</option>
                        <option value="pro">Pro</option>
                    </select>

                    <!-- Status Pembayaran Filter -->
                    <select
                        v-model="statusPembayaran"
                        @change="applyFilters"
                        class="px-3 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-hidden cursor-pointer"
                    >
                        <option value="">Status Bayar</option>
                        <option value="lunas">Lunas</option>
                        <option value="menunggu_pembayaran">Menunggu Bayar</option>
                        <option value="belum_bayar">Belum Bayar</option>
                    </select>

                    <!-- Email Verified Filter -->
                    <select
                        v-model="emailVerified"
                        @change="applyFilters"
                        class="px-3 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-hidden cursor-pointer"
                    >
                        <option value="">Status Email</option>
                        <option value="verified">Terverifikasi</option>
                        <option value="unverified">Belum Verifikasi</option>
                    </select>

                    <!-- Reset Button -->
                    <button
                        type="button"
                        @click="resetFilters"
                        class="px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
                        title="Reset Filter"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- ─── TABLE CONTAINER (Seragam Dengan Petugas & KPM) ───────────── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                <th class="py-3 px-4">Pengguna</th>
                                <th class="py-3 px-4">Role & Email</th>
                                <th class="py-3 px-4">Paket Langganan</th>
                                <th class="py-3 px-4">Unit SPPG Operasional</th>
                                <th class="py-3 px-4">Status Onboarding</th>
                                <th class="py-3 px-4">Terdaftar</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            <tr
                                v-for="u in users.data"
                                :key="u.id"
                                :class="[
                                    'transition-colors',
                                    isCurrentUser(u) ? 'bg-indigo-50/30 hover:bg-indigo-50/50' : 'hover:bg-slate-50/80',
                                ]"
                            >
                                <!-- Pengguna Info (Nama, Email, NIK, Telepon) -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-xs text-white shrink-0 shadow-2xs"
                                            :class="
                                                u.role === 'administrator'
                                                    ? 'bg-gradient-to-tr from-indigo-600 to-purple-600'
                                                    : u.role === 'member'
                                                    ? 'bg-gradient-to-tr from-emerald-600 to-teal-600'
                                                    : 'bg-gradient-to-tr from-amber-500 to-orange-500'
                                            "
                                        >
                                            {{ getInitials(u.nama) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate flex items-center gap-1.5">
                                                <span>{{ u.nama_lengkap || u.nama }}</span>
                                                <span
                                                    v-if="isCurrentUser(u)"
                                                    class="text-[9.5px] bg-indigo-100 text-indigo-700 font-semibold px-1.5 py-0.2 rounded"
                                                >
                                                    Anda
                                                </span>
                                            </p>
                                            <p class="text-[11px] text-slate-500 truncate flex items-center gap-1 mt-0.5">
                                                <Mail class="w-3 h-3 text-slate-400 shrink-0" />
                                                <span>{{ u.email }}</span>
                                            </p>
                                            <p v-if="u.telepon" class="text-[10.5px] text-slate-400 truncate flex items-center gap-1 mt-0.5">
                                                <Phone class="w-3 h-3 text-slate-400 shrink-0" />
                                                <span>{{ u.telepon }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role & Status Email (Role Bersih: Guest, Member, atau Administrator) -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="space-y-1.5">
                                        <!-- Role Badge Murni: Guest, Member, atau Administrator -->
                                        <span
                                            v-if="formatRole(u.role) === 'Administrator'"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                        >
                                            <Shield class="w-3 h-3" />
                                            Administrator
                                        </span>
                                        <span
                                            v-else-if="formatRole(u.role) === 'Member'"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            <UserCheck class="w-3 h-3" />
                                            Member
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            <Clock class="w-3 h-3" />
                                            Guest
                                        </span>

                                        <!-- Email Verified Badge -->
                                        <div class="flex items-center gap-1 text-[10.5px]">
                                            <span
                                                v-if="u.is_email_verified"
                                                class="text-emerald-600 flex items-center gap-1 font-semibold"
                                                :title="`Terverifikasi: ${u.email_verified_at}`"
                                            >
                                                <CheckCircle2 class="w-3 h-3" />
                                                Terverifikasi
                                            </span>
                                            <span
                                                v-else
                                                class="text-rose-500 flex items-center gap-1 font-medium"
                                                title="Belum verifikasi email"
                                            >
                                                <AlertCircle class="w-3 h-3" />
                                                Belum Verifikasi
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Paket Langganan & Pembayaran -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-800 capitalize">
                                                {{ u.paket_langganan }}
                                            </span>
                                            <span
                                                class="text-[9.5px] px-1.5 py-0.2 rounded font-semibold uppercase"
                                                :class="
                                                    u.siklus_langganan === 'tahunan'
                                                        ? 'bg-purple-50 text-purple-700 border border-purple-200'
                                                        : 'bg-slate-100 text-slate-600'
                                                "
                                            >
                                                {{ u.siklus_langganan }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] font-semibold text-slate-500">
                                            {{ formatRp(u.nominal_langganan) }}
                                        </div>
                                        <div>
                                            <span
                                                v-if="u.status_pembayaran === 'lunas'"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200"
                                            >
                                                <Check class="w-2.5 h-2.5" /> Lunas
                                            </span>
                                            <span
                                                v-else-if="u.status_pembayaran === 'menunggu_pembayaran'"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200"
                                            >
                                                <Clock class="w-2.5 h-2.5" /> Menunggu
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 text-[10px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full"
                                            >
                                                Belum Bayar
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Unit SPPG Operasional -->
                                <td class="py-3 px-4">
                                    <div v-if="u.unit_sppg" class="max-w-[220px]">
                                        <p class="font-bold text-slate-900 truncate">
                                            {{ u.unit_sppg.nama }}
                                        </p>
                                        <p class="text-[10.5px] font-mono text-indigo-600 truncate">
                                            ID: {{ u.unit_sppg.id_sppg }}
                                        </p>
                                        <p class="text-[10.5px] text-slate-500 truncate mt-0.5">
                                            {{ u.unit_sppg.kabupaten || u.unit_sppg.provinsi || '-' }}
                                        </p>
                                    </div>
                                    <div v-else class="text-[11px] text-slate-400 italic">
                                        Belum didaftarkan
                                    </div>
                                </td>

                                <!-- Status Onboarding -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span
                                        v-if="u.status_onboarding === 'selesai' || u.is_onboarding_completed"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <CheckCircle2 class="w-3 h-3 text-emerald-600" />
                                        Selesai
                                    </span>
                                    <span
                                        v-else-if="u.status_onboarding === 'menunggu_data'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        <Clock class="w-3 h-3 text-amber-600" />
                                        Menunggu Data
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-medium bg-slate-100 text-slate-600"
                                    >
                                        Belum Mulai
                                    </span>
                                </td>

                                <!-- Tanggal Terdaftar -->
                                <td class="py-3 px-4 whitespace-nowrap text-[11px] text-slate-500">
                                    {{ u.created_at || '-' }}
                                </td>

                                <!-- Aksi Buttons -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <!-- Tombol Detail (Bisa Dilihat Siapa Saja) -->
                                        <button
                                            type="button"
                                            @click="openDetail(u)"
                                            class="p-1.5 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors cursor-pointer"
                                            title="Lihat Detail Profil & SPPG"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </button>

                                        <!-- Proteksi: Jika Akun Diri Sendiri -> Tidak Bisa Mengedit/Menghapus Diri Sendiri -->
                                        <template v-if="isCurrentUser(u)">
                                            <span class="inline-flex items-center px-2 py-1 text-[10px] font-semibold text-slate-400 bg-slate-100 rounded-md select-none" title="Anda tidak dapat mengubah akun Anda sendiri di panel ini">
                                                Akun Anda
                                            </span>
                                        </template>

                                        <!-- Jika Akun Pengguna Lain -->
                                        <template v-else>
                                            <!-- Tombol Edit Role/Status -->
                                            <button
                                                type="button"
                                                @click="openEditRole(u)"
                                                class="p-1.5 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                                title="Ubah Role & Status"
                                            >
                                                <Edit class="w-4 h-4" />
                                            </button>

                                            <!-- Tombol Verifikasi Email Manual -->
                                            <button
                                                v-if="!u.is_email_verified"
                                                type="button"
                                                @click="openVerifyModal(u)"
                                                class="p-1.5 text-amber-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors cursor-pointer"
                                                title="Verifikasi Email Manual"
                                            >
                                                <MailCheck class="w-4 h-4" />
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <button
                                                type="button"
                                                @click="openDeleteModal(u)"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                                title="Hapus Akun Pengguna"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Users class="w-10 h-10 text-slate-300 stroke-1" />
                                        <p class="font-medium text-sm text-slate-500">Tidak ada data pengguna yang sesuai kriteria pencarian.</p>
                                        <button
                                            type="button"
                                            @click="resetFilters"
                                            class="mt-1 text-xs text-indigo-600 font-semibold hover:underline cursor-pointer"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION BAR -->
                <div
                    v-if="users.total > 0"
                    class="p-3.5 sm:p-4 border-t border-slate-200 bg-slate-50/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500"
                >
                    <div>
                        Menampilkan
                        <span class="font-bold text-slate-800">{{ users.from || 0 }}</span>
                        sampai
                        <span class="font-bold text-slate-800">{{ users.to || 0 }}</span>
                        dari
                        <span class="font-bold text-slate-800">{{ users.total || 0 }}</span>
                        total pengguna
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, idx) in users.links" :key="idx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 rounded-lg font-medium transition-colors cursor-pointer',
                                    link.active
                                        ? 'bg-primary text-white font-bold shadow-2xs'
                                        : 'hover:bg-slate-200 text-slate-600',
                                ]"
                                v-html="link.label"
                                preserve-scroll
                            />
                            <span
                                v-else
                                class="px-2 py-1.5 text-slate-300 select-none"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- ─── MODAL DETAIL PENGGUNA & SPPG (TELEPORT KE BODY) ───────────── -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div
                v-if="showDetailModal && selectedUser"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200"
            >
                <!-- Backdrop Gelap Pekat Menyeluruh (Semua Area Kena Termasuk Sidebar & Header) -->
                <div
                    class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity cursor-pointer"
                    @click="closeDetail"
                ></div>

                <!-- Modal Dialog -->
                <div
                    class="relative z-10 bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-slate-200 my-8 animate-in zoom-in-95 duration-200"
                    @click.stop
                >
                    <!-- Modal Header -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm text-white shadow-xs"
                                :class="
                                    selectedUser.role === 'administrator'
                                        ? 'bg-gradient-to-tr from-indigo-600 to-purple-600'
                                        : 'bg-gradient-to-tr from-emerald-600 to-teal-600'
                                "
                            >
                                {{ getInitials(selectedUser.nama) }}
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-tight">
                                    {{ selectedUser.nama_lengkap || selectedUser.nama }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                    <span>{{ selectedUser.email }}</span>
                                    <span>•</span>
                                    <span class="font-semibold text-indigo-600">{{ formatRole(selectedUser.role) }}</span>
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="closeDetail"
                            class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Tab Nav -->
                    <div class="flex items-center gap-2 px-5 pt-3 border-b border-slate-100 bg-white">
                        <button
                            type="button"
                            @click="activeDetailTab = 'profil'"
                            :class="[
                                'px-4 py-2 text-xs font-bold border-b-2 transition-all cursor-pointer flex items-center gap-1.5',
                                activeDetailTab === 'profil'
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-800',
                            ]"
                        >
                            <Users class="w-3.5 h-3.5" />
                            <span>Profil Pengguna</span>
                        </button>
                        <button
                            type="button"
                            @click="activeDetailTab = 'unit'"
                            :class="[
                                'px-4 py-2 text-xs font-bold border-b-2 transition-all cursor-pointer flex items-center gap-1.5',
                                activeDetailTab === 'unit'
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-800',
                            ]"
                        >
                            <Building2 class="w-3.5 h-3.5" />
                            <span>Data Unit SPPG</span>
                        </button>
                        <button
                            type="button"
                            @click="activeDetailTab = 'langganan'"
                            :class="[
                                'px-4 py-2 text-xs font-bold border-b-2 transition-all cursor-pointer flex items-center gap-1.5',
                                activeDetailTab === 'langganan'
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-800',
                            ]"
                        >
                            <CreditCard class="w-3.5 h-3.5" />
                            <span>Status & Langganan</span>
                        </button>
                    </div>

                    <!-- Modal Body Scrollable -->
                    <div class="p-5 sm:p-6 overflow-y-auto space-y-4 text-xs text-slate-700 flex-1">
                        <!-- TAB 1: PROFIL PENGGUNA -->
                        <div v-if="activeDetailTab === 'profil'" class="space-y-3.5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Nama Lengkap & Gelar</span>
                                    <p class="font-bold text-slate-900 text-sm">
                                        {{ selectedUser.nama_lengkap || selectedUser.nama }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Gelar Depan: {{ selectedUser.gelar_depan || '-' }} | Gelar Belakang: {{ selectedUser.gelar_belakang || '-' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Identitas NIK & NIP</span>
                                    <p class="font-bold text-slate-900 text-sm">
                                        NIK: {{ selectedUser.nik || '-' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        NIP: {{ selectedUser.nip || 'Belum diisi' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Tempat & Tanggal Lahir</span>
                                    <p class="font-bold text-slate-900">
                                        {{ selectedUser.tempat_lahir || '-' }}, {{ selectedUser.tanggal_lahir || '-' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Jenis Kelamin: {{ selectedUser.jenis_kelamin || '-' }} | Agama: {{ selectedUser.agama || '-' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Kontak Pengguna</span>
                                    <p class="font-bold text-slate-900">
                                        Telepon / WA: {{ selectedUser.telepon || '-' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Email: {{ selectedUser.email }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1 md:col-span-2">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Pendidikan Terakhir</span>
                                    <p class="font-semibold text-slate-800">
                                        Jenjang: {{ selectedUser.jenjang_pendidikan || '-' }} — Bidang: {{ selectedUser.bidang_pendidikan || '-' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1 md:col-span-2">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Alamat KTP</span>
                                    <p class="font-medium text-slate-800">
                                        {{ selectedUser.alamat_lengkap_ktp || 'Belum diisi' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1 md:col-span-2">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Alamat Domisili</span>
                                    <p class="font-medium text-slate-800">
                                        {{ selectedUser.alamat_lengkap_domisili || 'Belum diisi' }}
                                    </p>
                                    <p v-if="selectedUser.latitude_domisili" class="text-[11px] text-indigo-600 font-mono mt-1">
                                        Koordinat: {{ selectedUser.latitude_domisili }}, {{ selectedUser.longitude_domisili }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: DATA UNIT SPPG -->
                        <div v-if="activeDetailTab === 'unit'" class="space-y-3.5">
                            <div v-if="selectedUser.unit_sppg" class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div class="p-3.5 bg-indigo-50/60 rounded-xl border border-indigo-100 space-y-1 md:col-span-2">
                                    <span class="text-[10px] font-bold uppercase text-indigo-500">Nama Unit SPPG</span>
                                    <p class="font-black text-slate-900 text-base">
                                        {{ selectedUser.unit_sppg.nama }}
                                    </p>
                                    <p class="text-xs font-mono font-bold text-indigo-700">
                                        ID SPPG: {{ selectedUser.unit_sppg.id_sppg }} | Kode: {{ selectedUser.unit_sppg.kode_sppg || '-' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Status & Operasional</span>
                                    <p class="font-bold text-slate-900 capitalize">
                                        {{ selectedUser.unit_sppg.status || 'Aktif' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Tgl Operasional: {{ selectedUser.unit_sppg.tanggal_operasional || '-' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Wilayah Administratif</span>
                                    <p class="font-bold text-slate-900">
                                        {{ selectedUser.unit_sppg.kabupaten || '-' }}, {{ selectedUser.unit_sppg.provinsi || '-' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Kec: {{ selectedUser.unit_sppg.kecamatan || '-' }} | Desa: {{ selectedUser.unit_sppg.desa_kelurahan || '-' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1 md:col-span-2">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Alamat Lengkap Unit SPPG</span>
                                    <p class="font-medium text-slate-800">
                                        {{ selectedUser.unit_sppg.alamat_lengkap || 'Belum diisi' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Kode Pos: {{ selectedUser.unit_sppg.kode_pos || '-' }}
                                    </p>
                                    <p v-if="selectedUser.unit_sppg.latitude" class="text-[11px] text-indigo-600 font-mono mt-0.5">
                                        Titik Koordinat: {{ selectedUser.unit_sppg.latitude }}, {{ selectedUser.unit_sppg.longitude }}
                                    </p>
                                </div>
                            </div>

                            <div v-else class="text-center py-10 text-slate-400 space-y-2">
                                <Building2 class="w-12 h-12 text-slate-300 mx-auto stroke-1" />
                                <p class="font-semibold text-slate-600">Pengguna belum mendaftarkan unit SPPG operasional.</p>
                                <p class="text-xs text-slate-400">Unit SPPG akan muncul setelah pengguna menyelesaikan tahap Onboarding.</p>
                            </div>
                        </div>

                        <!-- TAB 3: STATUS AKUN & LANGGANAN -->
                        <div v-if="activeDetailTab === 'langganan'" class="space-y-3.5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Hak Akses Role</span>
                                    <p class="font-bold text-slate-900 text-sm">
                                        {{ formatRole(selectedUser.role) }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Verifikasi Email</span>
                                    <p class="font-bold text-slate-900 text-sm">
                                        {{ selectedUser.is_email_verified ? 'Terverifikasi' : 'Belum Terverifikasi' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        {{ selectedUser.email_verified_at || 'Pengguna belum mengklik tautan verifikasi' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Paket & Siklus Langganan</span>
                                    <p class="font-bold text-slate-900 text-sm capitalize">
                                        {{ selectedUser.paket_langganan }} ({{ selectedUser.siklus_langganan }})
                                    </p>
                                    <p class="text-[11px] font-semibold text-indigo-600">
                                        Tarif: {{ formatRp(selectedUser.nominal_langganan) }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Status Pembayaran</span>
                                    <p class="font-bold text-slate-900 capitalize">
                                        {{ selectedUser.status_pembayaran }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Tgl Bayar: {{ selectedUser.tgl_pembayaran || '-' }}
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1 md:col-span-2">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Status Alur Onboarding</span>
                                    <p class="font-bold text-slate-900 capitalize">
                                        {{ selectedUser.status_onboarding }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Kelengkapan Profil & Unit: {{ selectedUser.is_onboarding_completed ? 'Lengkap & Siap Operasional' : 'Masih dalam proses' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-3.5 sm:p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-mono">ID Pengguna: #{{ selectedUser.id }}</span>
                        <button
                            type="button"
                            @click="closeDetail"
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-lg text-xs transition-colors cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- ─── MODAL UBAH ROLE & STATUS (TELEPORT KE BODY) ───────────────── -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div
                v-if="showEditRoleModal && selectedUser"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200"
            >
                <!-- Backdrop Gelap Pekat Menyeluruh (Semua Area Kena Termasuk Sidebar & Header) -->
                <div
                    class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity cursor-pointer"
                    @click="closeEditRole"
                ></div>

                <div
                    class="relative z-10 bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 my-8 animate-in zoom-in-95 duration-200"
                    @click.stop
                >
                    <form @submit.prevent="submitEditRole">
                        <!-- Modal Header -->
                        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                            <div class="flex items-center gap-2.5">
                                <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                    <Edit class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 leading-tight">
                                        Ubah Role & Status Akun
                                    </h3>
                                    <p class="text-xs text-slate-500 truncate max-w-[260px] mt-0.5">
                                        {{ selectedUser.nama }} ({{ selectedUser.email }})
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="closeEditRole"
                                class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-5 space-y-4 text-xs">
                            <!-- Role Selection: Cukup Guest, Member, atau Administrator -->
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Role Pengguna</label>
                                <select
                                    v-model="editForm.role"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-hidden font-semibold"
                                >
                                    <option value="administrator">Administrator</option>
                                    <option value="member">Member</option>
                                    <option value="guest">Guest</option>
                                </select>
                            </div>

                            <!-- Paket Langganan -->
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Paket Langganan</label>
                                <select
                                    v-model="editForm.paket_langganan"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-hidden font-semibold capitalize"
                                >
                                    <option value="free">Free (Trial)</option>
                                    <option value="basic">Basic</option>
                                    <option value="standart">Standart</option>
                                    <option value="pro">Pro</option>
                                </select>
                            </div>

                            <!-- Status Pembayaran -->
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Status Pembayaran</label>
                                <select
                                    v-model="editForm.status_pembayaran"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-hidden font-semibold"
                                >
                                    <option value="lunas">Lunas</option>
                                    <option value="menunggu_pembayaran">Menunggu Pembayaran</option>
                                    <option value="belum_bayar">Belum Bayar</option>
                                </select>
                            </div>

                            <!-- Status Onboarding -->
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Status Onboarding Data</label>
                                <select
                                    v-model="editForm.status_onboarding"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-hidden font-semibold"
                                >
                                    <option value="selesai">Selesai</option>
                                    <option value="menunggu_data">Menunggu Data</option>
                                    <option value="belum_mulai">Belum Mulai</option>
                                </select>
                            </div>
                        </div>

                        <!-- Modal Footer (Seragam Dengan Tombol Petugas) -->
                        <div class="p-3.5 sm:p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                @click="closeEditRole"
                                class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <Check class="w-4 h-4" />
                                <span>{{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- ─── MODAL KONFIRMASI HAPUS PENGGUNA (TELEPORT KE BODY) ───────── -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal && userToDelete"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200"
            >
                <!-- Backdrop Gelap Pekat Seluruh Layar (Semua Area Kena Termasuk Sidebar & Header) -->
                <div
                    class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity cursor-pointer"
                    @click="closeDeleteModal"
                ></div>

                <div class="relative z-10 w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 p-6 space-y-4 animate-in zoom-in-95 duration-200 my-8">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-full bg-rose-100 text-rose-700 shrink-0">
                            <Trash2 class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 leading-tight">
                                Hapus Akun Pengguna
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Tindakan ini tidak dapat dibatalkan
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed">
                        Apakah Anda yakin ingin menghapus akun <span class="font-bold text-slate-900">{{ userToDelete.nama }}</span> (<span class="font-mono text-slate-800">{{ userToDelete.email }}</span>) beserta seluruh data operasional SPPG terkait?
                    </p>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            :disabled="isDeleting"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitDeleteUser"
                            :disabled="isDeleting"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                            <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Akun' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- ─── MODAL VERIFIKASI EMAIL MANUAL (TELEPORT KE BODY) ─────────── -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div
                v-if="showVerifyModal && userToVerify"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200"
            >
                <!-- Backdrop Gelap Pekat Seluruh Layar (Semua Area Kena Termasuk Sidebar & Header) -->
                <div
                    class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity cursor-pointer"
                    @click="closeVerifyModal"
                ></div>

                <div class="relative z-10 w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 p-6 space-y-4 animate-in zoom-in-95 duration-200 my-8">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-full bg-emerald-100 text-emerald-700 shrink-0">
                            <MailCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 leading-tight">
                                Verifikasi Email Manual
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Tandai email pengguna sebagai terverifikasi
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed">
                        Tandai email <span class="font-mono font-semibold text-slate-900">{{ userToVerify.email }}</span> milik <span class="font-bold text-slate-900">{{ userToVerify.nama }}</span> sebagai terverifikasi tanpa menunggu pengguna mengklik tautan email?
                    </p>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeVerifyModal"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitVerifyEmail"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
                        >
                            <Check class="w-3.5 h-3.5" />
                            <span>Verifikasi Sekarang</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
