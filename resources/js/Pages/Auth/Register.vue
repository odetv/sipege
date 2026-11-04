<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import {
    Lock,
    Mail,
    User,
    AlertCircle,
    Eye,
    EyeOff,
    ShieldCheck,
    Check,
    X,
    FileText,
    ArrowRight,
} from "lucide-vue-next";

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const isAgreed = ref(false);
const showTermsModal = ref(false);

const form = useForm({
    nama: "",
    email: "",
    password: "",
    password_confirmation: "",
});

function handleCheckboxClick(e) {
    if (!isAgreed.value) {
        // Jika belum disetujui, buka modal syarat & ketentuan terlebih dahulu
        e.preventDefault();
        showTermsModal.value = true;
    } else {
        // Jika sudah disetujui lalu diklik kembali, batalkan centang
        isAgreed.value = false;
    }
}

function openTermsModal() {
    showTermsModal.value = true;
}

function handleAcceptTerms() {
    isAgreed.value = true;
    showTermsModal.value = false;
}

function handleRejectTerms() {
    isAgreed.value = false;
    showTermsModal.value = false;
}

const submit = () => {
    if (!isAgreed.value) {
        showTermsModal.value = true;
        return;
    }
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Daftar" />

    <div
        class="min-h-screen bg-[#f8fafc] flex flex-col justify-center items-center p-4 sm:p-6 relative overflow-hidden select-none"
    >
        <!-- Card Container with Background Geometric Decorations -->
        <div class="relative w-full max-w-[460px]">
            <!-- Decorative Top-Right Dots -->
            <div
                class="absolute -top-7 -right-7 pointer-events-none z-0"
                aria-hidden="true"
            >
                <svg
                    width="120"
                    height="120"
                    fill="none"
                    viewBox="0 0 120 120"
                    class="text-indigo-400/40"
                >
                    <pattern
                        id="dots-top"
                        x="0"
                        y="0"
                        width="20"
                        height="20"
                        patternUnits="userSpaceOnUse"
                    >
                        <circle cx="3" cy="3" r="1.5" fill="currentColor" />
                    </pattern>
                    <rect width="120" height="120" fill="url(#dots-top)" />
                </svg>
            </div>

            <!-- Decorative Bottom-Left Rounded Box with Dots -->
            <div
                class="absolute -bottom-8 -left-8 pointer-events-none z-0 w-36 h-36 rounded-2xl bg-indigo-50/70 border border-indigo-100/60 p-3.5 flex items-center justify-center shadow-xs"
                aria-hidden="true"
            >
                <svg
                    width="110"
                    height="110"
                    fill="none"
                    viewBox="0 0 110 110"
                    class="text-indigo-400/45"
                >
                    <pattern
                        id="dots-bottom"
                        x="0"
                        y="0"
                        width="20"
                        height="20"
                        patternUnits="userSpaceOnUse"
                    >
                        <circle cx="3" cy="3" r="1.5" fill="currentColor" />
                    </pattern>
                    <rect width="110" height="110" fill="url(#dots-bottom)" />
                </svg>
            </div>

            <!-- Main White Card -->
            <div
                class="relative z-10 w-full bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 px-6 py-8 sm:px-9 sm:py-9"
            >
                <!-- Brand Header (Centered Logo + Titles) -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center mb-3">
                        <img
                            src="/images/logo/BGN_LOGO_MAIN.png"
                            alt="Logo BGN"
                            class="h-14 w-14 object-contain drop-shadow-sm"
                        />
                    </div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">
                        SIPEGE
                    </h1>
                    <p class="text-xs text-slate-500 font-normal mt-1">
                        Silahkan daftar untuk membuat akun baru.
                    </p>
                </div>

                <!-- Registration Form -->
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Nama Lengkap -->
                    <div>
                        <label
                            for="nama"
                            class="block text-[11px] font-bold tracking-wider text-slate-600 uppercase mb-1.5"
                        >
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
                            >
                                <User class="h-4 w-4" />
                            </div>
                            <input
                                id="nama"
                                v-model="form.nama"
                                type="text"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Masukkan nama lengkap Anda"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition duration-150"
                                :class="{
                                    'border-rose-400 bg-rose-50/20 focus:ring-rose-500/20 focus:border-rose-500':
                                        form.errors.nama,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.nama"
                            class="text-rose-500 text-[11px] mt-1.5 flex items-center gap-1 font-medium"
                        >
                            <AlertCircle class="h-3 w-3 shrink-0" />
                            {{ form.errors.nama }}
                        </p>
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label
                            for="email"
                            class="block text-[11px] font-bold tracking-wider text-slate-600 uppercase mb-1.5"
                        >
                            Email <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
                            >
                                <Mail class="h-4 w-4" />
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="Masukkan alamat email aktif"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition duration-150"
                                :class="{
                                    'border-rose-400 bg-rose-50/20 focus:ring-rose-500/20 focus:border-rose-500':
                                        form.errors.email,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.email"
                            class="text-rose-500 text-[11px] mt-1.5 flex items-center gap-1 font-medium"
                        >
                            <AlertCircle class="h-3 w-3 shrink-0" />
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            for="password"
                            class="block text-[11px] font-bold tracking-wider text-slate-600 uppercase mb-1.5"
                        >
                            Password <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
                            >
                                <Lock class="h-4 w-4" />
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                placeholder="Minimal 8 karakter"
                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition duration-150"
                                :class="{
                                    'border-rose-400 bg-rose-50/20 focus:ring-rose-500/20 focus:border-rose-500':
                                        form.errors.password,
                                }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password"
                            class="text-rose-500 text-[11px] mt-1.5 flex items-center gap-1 font-medium"
                        >
                            <AlertCircle class="h-3 w-3 shrink-0" />
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-[11px] font-bold tracking-wider text-slate-600 uppercase mb-1.5"
                        >
                            Konfirmasi Password <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
                            >
                                <Lock class="h-4 w-4" />
                            </div>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                placeholder="Ulangi password di atas"
                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition duration-150"
                                :class="{
                                    'border-rose-400 bg-rose-50/20 focus:ring-rose-500/20 focus:border-rose-500':
                                        form.errors.password_confirmation,
                                }"
                            />
                            <button
                                type="button"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                            >
                                <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password_confirmation"
                            class="text-rose-500 text-[11px] mt-1.5 flex items-center gap-1 font-medium"
                        >
                            <AlertCircle class="h-3 w-3 shrink-0" />
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <!-- Agreement Checkbox -->
                    <div class="pt-1">
                        <div class="flex items-start gap-2.5 text-xs text-slate-600">
                            <input
                                id="terms-checkbox"
                                type="checkbox"
                                :checked="isAgreed"
                                @click="handleCheckboxClick"
                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 mt-0.5 cursor-pointer"
                            />
                            <label
                                for="terms-checkbox"
                                class="text-[11px] leading-relaxed cursor-pointer select-none"
                            >
                                Saya menyetujui
                                <button
                                    type="button"
                                    @click.prevent="openTermsModal"
                                    class="font-semibold text-primary underline hover:text-primary/80 inline cursor-pointer"
                                >
                                    Ketentuan Layanan & Kebijakan Privasi
                                </button>
                                Sistem Pengelolaan SPPG (SIPEGE).
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing || !isAgreed"
                            :class="[
                                'w-full py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-white transition duration-150 flex items-center justify-center gap-2',
                                isAgreed && !form.processing
                                    ? 'bg-primary hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-primary/20 shadow-md shadow-primary/20 cursor-pointer opacity-100'
                                    : 'bg-slate-300 text-slate-500 cursor-not-allowed opacity-70'
                            ]"
                        >
                            <span v-if="form.processing">Mendaftarkan...</span>
                            <span v-else>Daftar</span>
                        </button>
                        <p v-if="!isAgreed" class="text-[10.5px] text-slate-400 text-center mt-1.5">
                            *Centang persetujuan di atas untuk melanjutkan pendaftaran
                        </p>
                    </div>
                </form>

                <!-- Divider & Link to Login -->
                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-500">
                        Sudah memiliki akun?
                        <Link
                            :href="route('login')"
                            class="font-bold text-primary hover:underline ml-1"
                        >
                            Masuk
                        </Link>
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal Syarat, Ketentuan & Kebijakan Privasi Pengguna -->
        <Teleport to="body">
            <div
                v-if="showTermsModal"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200"
            >
                <!-- Backdrop Gelap Pekat dengan Blur -->
                <div
                    class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity cursor-pointer"
                    @click="handleRejectTerms"
                ></div>

                <!-- Card Modal -->
                <div
                    class="relative z-10 w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200 overflow-hidden"
                >
                    <!-- Header Modal -->
                    <div class="p-5 pb-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                <ShieldCheck class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">
                                    Ketentuan Layanan & Privasi
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Sistem Pengelolaan SPPG (SIPEGE)
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="handleRejectTerms"
                            class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Body Modal (Scrollable Content) -->
                    <div class="p-5 overflow-y-auto space-y-4 text-xs text-slate-600 leading-relaxed text-justify">
                        <div class="p-3 rounded-xl bg-blue-50 border border-blue-200/80 text-blue-800 text-[11px]">
                            <p class="font-bold flex items-center gap-1.5 mb-1 text-blue-900">
                                <FileText class="w-3.5 h-3.5" />
                                <span>Pemberitahuan Persetujuan Pengguna</span>
                            </p>
                            <span>
                                Harap baca dan pahami seluruh ketentuan di bawah ini. Dengan menyetujui, Anda menyatakan tunduk dan sepakat pada seluruh pedoman operasional sistem SIPEGE.
                            </span>
                        </div>

                        <!-- Poin 1 -->
                        <div class="space-y-1">
                            <h4 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 font-bold text-[10px] flex items-center justify-center">1</span>
                                <span>Ketentuan Akun & Peran Pengguna (Role)</span>
                            </h4>
                            <p class="pl-5.5 text-[11.5px] text-slate-600">
                                Pendaftaran awal memberikan hak akses berstatus <strong>Guest</strong>. Untuk dapat mengoperasikan sistem SPPG secara penuh, akun wajib ditingkatkan statusnya menjadi <strong>Member</strong> setelah pembayaran paket langganan diverifikasi valid. Pengguna wajib mendaftarkan identitas yang benar, sah, dan dapat dipertanggungjawabkan.
                            </p>
                        </div>

                        <!-- Poin 2 -->
                        <div class="space-y-1">
                            <h4 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 font-bold text-[10px] flex items-center justify-center">2</span>
                                <span>Paket Langganan & Masa Operasional Lisensi</span>
                            </h4>
                            <p class="pl-5.5 text-[11.5px] text-slate-600">
                                Layanan sistem SIPEGE disediakan berdasarkan skema paket langganan (Basic, Standart, atau Pro) dengan durasi operasional bulanan (30 hari). Layanan dapat diperpanjang secara berkala untuk menjamin kesinambungan pengelolaan SPPG.
                            </p>
                        </div>

                        <!-- Poin 3 -->
                        <div class="space-y-1">
                            <h4 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 font-bold text-[10px] flex items-center justify-center">3</span>
                                <span>Integritas & Akurasi Data Operasional SPPG</span>
                            </h4>
                            <p class="pl-5.5 text-[11.5px] text-slate-600">
                                Pengguna bertanggung jawab penuh atas keabsahan seluruh data yang dimasukkan ke dalam sistem, meliputi perencanaan Work Order (WO), perhitungan formula porsi gizi makanan, data kelompok penerima manfaat (KPM), serta penjadwalan dan absensi shifting personil petugas.
                            </p>
                        </div>

                        <!-- Poin 4 -->
                        <div class="space-y-1">
                            <h4 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 font-bold text-[10px] flex items-center justify-center">4</span>
                                <span>Kerahasiaan Akun & Keamanan Data (Privasi)</span>
                            </h4>
                            <p class="pl-5.5 text-[11.5px] text-slate-600">
                                Pengguna wajib menjaga kerahasiaan nama pengguna dan kata sandi masing-masing. SIPEGE menjamin perlindungan privasi data pribadi dan operasional, serta tidak akan memindahtangankan data kepada pihak yang tidak berkepentingan di luar kepatuhan hukum dan regulasi.
                            </p>
                        </div>

                        <!-- Poin 5 -->
                        <div class="space-y-1">
                            <h4 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 font-bold text-[10px] flex items-center justify-center">5</span>
                                <span>Kepatuhan Regulasi Program Gizi Nasional</span>
                            </h4>
                            <p class="pl-5.5 text-[11.5px] text-slate-600">
                                Seluruh modul dan fungsionalitas sistem didesain untuk mendukung tata kelola Satuan Pelayanan Pemenuhan Gizi (SPPG) agar selaras dengan standar dan petunjuk teknis Badan Gizi Nasional.
                            </p>
                        </div>
                    </div>

                    <!-- Footer Action Modal -->
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="handleRejectTerms"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                        >
                            Batal / Tidak Setuju
                        </button>
                        <button
                            type="button"
                            @click="handleAcceptTerms"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white text-xs font-bold transition shadow-md shadow-primary/20 flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <Check class="w-4 h-4 stroke-[3]" />
                            <span>Saya Telah Membaca & Menyetujui</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
