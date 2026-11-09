<script setup>
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Landmark,
    CreditCard,
    Plus,
    Edit3,
    Trash2,
    CheckCircle2,
    Copy,
    Check,
    Star,
    X,
    Building2,
    Info,
    AlertCircle,
    Hash,
    User,
    MapPin,
    FileText,
} from "lucide-vue-next";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: null,
    },
    rekeningList: {
        type: Array,
        default: () => [],
    },
});

const bankOptions = [
    { value: "BNI", label: "Bank BNI (Bank Negara Indonesia)" },
    { value: "BRI", label: "Bank BRI (Bank Rakyat Indonesia)" },
    { value: "Mandiri", label: "Bank Mandiri" },
    { value: "BCA", label: "Bank Central Asia (BCA)" },
    { value: "BSI", label: "Bank Syariah Indonesia (BSI)" },
    { value: "Bank BPD Bali", label: "Bank BPD Bali" },
    { value: "Bank Jatim", label: "Bank Jatim" },
    { value: "Bank Jateng", label: "Bank Jateng" },
    { value: "Bank BJB", label: "Bank BJB" },
    { value: "Bank DKI", label: "Bank DKI" },
    { value: "Bank Nagari", label: "Bank Nagari" },
    { value: "Lainnya", label: "Bank Lainnya..." },
];

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isSubmitting = ref(false);

const form = ref({
    jenis_bank: "BNI",
    nama_bank_lainnya: "",
    nama_rekening: "",
    nomor_rekening: "",
    nomor_virtual_account: "",
    kantor_cabang: "",
    keterangan: "",
    is_utama: false,
});

// Modal Hapus
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

// State Salin Teks
const copiedText = ref("");
function copyToClipboard(text, id) {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copiedText.value = id;
    setTimeout(() => {
        if (copiedText.value === id) {
            copiedText.value = "";
        }
    }, 2000);
}

function openTambahModal() {
    isEditing.value = false;
    editingId.value = null;
    form.value = {
        jenis_bank: "BNI",
        nama_bank_lainnya: "",
        nama_rekening: props.unitSppg?.nama ? `SPPG ${props.unitSppg.nama}` : "",
        nomor_rekening: "",
        nomor_virtual_account: "",
        kantor_cabang: "",
        keterangan: "",
        is_utama: props.rekeningList.length === 0,
    };
    showModal.value = true;
}

function openEditModal(rek) {
    isEditing.value = true;
    editingId.value = rek.id;
    form.value = {
        jenis_bank: rek.jenis_bank || "BNI",
        nama_bank_lainnya: rek.nama_bank_lainnya || "",
        nama_rekening: rek.nama_rekening || "",
        nomor_rekening: rek.nomor_rekening || "",
        nomor_virtual_account: rek.nomor_virtual_account || "",
        kantor_cabang: rek.kantor_cabang || "",
        keterangan: rek.keterangan || "",
        is_utama: Boolean(rek.is_utama),
    };
    showModal.value = true;
}

function submitForm() {
    if (!form.value.nama_rekening || !form.value.nomor_rekening) {
        alert("Nama rekening dan nomor rekening wajib diisi.");
        return;
    }

    isSubmitting.value = true;

    if (isEditing.value && editingId.value) {
        router.put(route("kas-bank.rekening-bank.update", editingId.value), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                isSubmitting.value = false;
            },
            onError: () => {
                isSubmitting.value = false;
            },
        });
    } else {
        router.post(route("kas-bank.rekening-bank.store"), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                isSubmitting.value = false;
            },
            onError: () => {
                isSubmitting.value = false;
            },
        });
    }
}

function confirmDelete(rek) {
    itemToDelete.value = rek;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!itemToDelete.value) return;
    isDeleting.value = true;
    router.delete(route("kas-bank.rekening-bank.destroy", itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
}

function setAsUtama(rek) {
    router.patch(route("kas-bank.rekening-bank.set-utama", rek.id), {}, {
        preserveScroll: true,
    });
}

function getBankBadgeStyle(bank) {
    const b = (bank || "").toUpperCase();
    if (b.includes("BNI")) return "bg-orange-50 text-orange-700 border-orange-200";
    if (b.includes("BRI")) return "bg-blue-50 text-blue-700 border-blue-200";
    if (b.includes("MANDIRI")) return "bg-amber-50 text-amber-800 border-amber-200";
    if (b.includes("BCA")) return "bg-sky-50 text-sky-700 border-sky-200";
    if (b.includes("BSI")) return "bg-emerald-50 text-emerald-700 border-emerald-200";
    return "bg-slate-50 text-slate-700 border-slate-200";
}
</script>

<template>
    <Head title="Rekening Bank - Kas & Bank SPPG" />

    <AppLayout>
        <div class="space-y-6 w-full pb-16">
            <!-- ─── HEADER UTAMA ─── -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="h-11 w-11 rounded-2xl bg-primary text-white flex items-center justify-center shrink-0 shadow-sm shadow-primary/25">
                        <Landmark class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                                Rekening Bank & Virtual Account
                            </h1>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20">
                                Kas & Bank
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pengelolaan nomor rekening bank operasional dan Virtual Account (VA) resmi Unit SPPG.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="openTambahModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-all shadow-xs cursor-pointer shrink-0"
                >
                    <Plus class="h-4 w-4" />
                    <span>Tambah Rekening Bank</span>
                </button>
            </div>

            <!-- ─── STATS MINI BANNER ─── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex items-center gap-3.5">
                    <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                        <CreditCard class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Rekening Bank</div>
                        <div class="text-lg font-black text-slate-900 mt-0.5">
                            {{ props.rekeningList.length }} <span class="text-xs font-semibold text-slate-500">Rekening</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex items-center gap-3.5">
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                        <Star class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Rekening Utama</div>
                        <div class="text-sm font-bold text-slate-900 mt-0.5 truncate max-w-[220px]">
                            {{ props.rekeningList.find(r => r.is_utama)?.nama_rekening || 'Belum diatur' }}
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex items-center gap-3.5">
                    <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                        <Building2 class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Unit SPPG</div>
                        <div class="text-sm font-bold text-slate-900 mt-0.5 truncate max-w-[220px]">
                            {{ props.unitSppg?.nama || 'SPPG Mandiri' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── DAFTAR REKENING BANK ─── -->
            <div v-if="props.rekeningList && props.rekeningList.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="rek in props.rekeningList"
                    :key="rek.id"
                    :class="[
                        'rounded-2xl border p-5 transition-all duration-200 relative bg-white shadow-2xs hover:shadow-md flex flex-col justify-between',
                        rek.is_utama ? 'border-primary ring-1 ring-primary/20 bg-gradient-to-b from-primary/5 via-white to-white' : 'border-slate-200'
                    ]"
                >
                    <!-- Header Kartu Rekening -->
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span :class="['px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wider border', getBankBadgeStyle(rek.jenis_bank)]">
                                {{ rek.jenis_bank === 'Lainnya' ? rek.nama_bank_lainnya : rek.jenis_bank }}
                            </span>
                            <div class="flex items-center gap-1.5">
                                <span
                                    v-if="rek.is_utama"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300"
                                >
                                    <Star class="h-3 w-3 fill-emerald-600 text-emerald-600" /> Rekening Utama
                                </span>
                                <button
                                    v-else
                                    type="button"
                                    @click="setAsUtama(rek)"
                                    class="text-[10px] text-slate-500 hover:text-primary hover:underline font-semibold cursor-pointer"
                                    title="Jadikan sebagai rekening utama"
                                >
                                    Set Utama
                                </button>
                            </div>
                        </div>

                        <!-- Atas Nama Rekening -->
                        <div class="mb-3">
                            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Atas Nama Rekening</p>
                            <p class="text-sm font-bold text-slate-900 leading-snug truncate" :title="rek.nama_rekening">
                                {{ rek.nama_rekening }}
                            </p>
                        </div>

                        <!-- Rincian Nomor Rekening & VA -->
                        <div class="space-y-2 bg-slate-50/80 p-3 rounded-xl border border-slate-100">
                            <!-- Nomor Rekening -->
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">No. Rekening</span>
                                    <p class="text-xs sm:text-sm font-black font-mono text-slate-900 tracking-wider">
                                        {{ rek.nomor_rekening }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="copyToClipboard(rek.nomor_rekening, `rek-${rek.id}`)"
                                    class="p-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 transition-colors cursor-pointer"
                                    title="Salin Nomor Rekening"
                                >
                                    <Check v-if="copiedText === `rek-${rek.id}`" class="h-3.5 w-3.5 text-emerald-600" />
                                    <Copy v-else class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Nomor Virtual Account -->
                            <div v-if="rek.nomor_virtual_account" class="pt-2 border-t border-slate-200/60 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <span class="text-[10px] font-bold text-primary uppercase">Virtual Account (VA)</span>
                                    <p class="text-xs sm:text-sm font-black font-mono text-primary tracking-wider">
                                        {{ rek.nomor_virtual_account }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="copyToClipboard(rek.nomor_virtual_account, `va-${rek.id}`)"
                                    class="p-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 transition-colors cursor-pointer"
                                    title="Salin Nomor Virtual Account"
                                >
                                    <Check v-if="copiedText === `va-${rek.id}`" class="h-3.5 w-3.5 text-emerald-600" />
                                    <Copy v-else class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Kantor Cabang & Keterangan -->
                        <div class="mt-2.5 text-[11px] text-slate-500 space-y-0.5">
                            <p v-if="rek.kantor_cabang" class="flex items-center gap-1.5">
                                <MapPin class="h-3 w-3 text-slate-400 shrink-0" />
                                <span class="truncate">Cabang: {{ rek.kantor_cabang }}</span>
                            </p>
                            <p v-if="rek.keterangan" class="italic text-slate-400">
                                &ldquo;{{ rek.keterangan }}&rdquo;
                            </p>
                        </div>
                    </div>

                    <!-- Footer Tombol Aksi -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end gap-1.5">
                        <button
                            type="button"
                            @click="openEditModal(rek)"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                        >
                            <Edit3 class="h-3 w-3 text-slate-600" />
                            <span>Edit</span>
                        </button>
                        <button
                            type="button"
                            @click="confirmDelete(rek)"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition-colors cursor-pointer"
                        >
                            <Trash2 class="h-3 w-3 text-rose-600" />
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── EMPTY STATE ─── -->
            <div
                v-else
                class="rounded-2xl border border-dashed border-slate-300 p-10 bg-white text-center flex flex-col items-center justify-center shadow-2xs"
            >
                <div class="h-16 w-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-3">
                    <Landmark class="h-8 w-8" />
                </div>
                <h3 class="text-base font-extrabold text-slate-900 mb-1">
                    Belum Ada Rekening Bank Ditambahkan
                </h3>
                <p class="text-xs text-slate-500 max-w-md mb-4">
                    Tambahkan rekening bank operasional dan Virtual Account (VA) unit SPPG untuk keperluan pencatatan transaksi, penyaluran insentif, dan penerimaan anggaran.
                </p>
                <button
                    type="button"
                    @click="openTambahModal"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-all shadow-xs cursor-pointer"
                >
                    <Plus class="h-4 w-4" />
                    <span>Tambah Rekening Sekarang</span>
                </button>
            </div>
        </div>

        <!-- ─── MODAL FORM TAMBAH / EDIT REKENING ─── -->
        <Modal :show="showModal" @close="showModal = false" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-primary/10 text-primary">
                            <CreditCard class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">
                                {{ isEditing ? 'Ubah Data Rekening Bank' : 'Tambah Rekening Bank Baru' }}
                            </h3>
                            <p class="text-xs text-slate-400">
                                Masukkan rincian nomor rekening dan virtual account unit SPPG
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showModal = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Jenis Bank -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Jenis Bank <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.jenis_bank"
                                required
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:ring-1 focus:ring-primary focus:border-primary bg-white cursor-pointer"
                            >
                                <option v-for="b in bankOptions" :key="b.value" :value="b.value">
                                    {{ b.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Jika Pilih Lainnya -->
                        <div v-if="form.jenis_bank === 'Lainnya'">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Nama Bank Lainnya <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.nama_bank_lainnya"
                                type="text"
                                required
                                placeholder="Contoh: Bank Danamon"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:ring-1 focus:ring-primary focus:border-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Kantor Cabang (KCP)
                            </label>
                            <input
                                v-model="form.kantor_cabang"
                                type="text"
                                placeholder="Contoh: KCP Singaraja Sukasada"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:ring-1 focus:ring-primary focus:border-primary"
                            />
                        </div>
                    </div>

                    <!-- Atas Nama Rekening -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Nama Rekening (Atas Nama) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.nama_rekening"
                            type="text"
                            required
                            placeholder="Contoh: SPPG BULELENG SUKASADA TEGALLINGGAH"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary uppercase"
                        />
                    </div>

                    <!-- Nomor Rekening & Virtual Account -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Nomor Rekening Bank <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-mono text-xs">#</span>
                                <input
                                    v-model="form.nomor_rekening"
                                    type="text"
                                    required
                                    placeholder="Contoh: 0123456789"
                                    class="w-full pl-7 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-black font-mono text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Nomor Virtual Account (VA)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-mono text-xs">VA</span>
                                <input
                                    v-model="form.nomor_virtual_account"
                                    type="text"
                                    placeholder="Contoh: 9881234567890001"
                                    class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-black font-mono text-primary focus:ring-1 focus:ring-primary focus:border-primary"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Keterangan / Catatan Rekening
                        </label>
                        <textarea
                            v-model="form.keterangan"
                            rows="2"
                            placeholder="Contoh: Rekening operasional utama BGN dropping APBN"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs text-slate-800 focus:ring-1 focus:ring-primary focus:border-primary"
                        ></textarea>
                    </div>

                    <!-- Jadikan Rekening Utama -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            v-model="form.is_utama"
                            id="is_utama_check"
                            type="checkbox"
                            class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4"
                        />
                        <label for="is_utama_check" class="text-xs font-semibold text-slate-700 select-none cursor-pointer">
                            Jadikan sebagai rekening utama Unit SPPG
                        </label>
                    </div>

                    <!-- Tombol Form -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-colors cursor-pointer disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Rekening') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ─── MODAL HAPUS REKENING ─── -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="sm">
            <div class="p-5 text-center">
                <div class="h-12 w-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <Trash2 class="h-6 w-6" />
                </div>
                <h3 class="text-base font-extrabold text-slate-900 mb-1">Hapus Rekening Bank?</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Apakah Anda yakin ingin menghapus rekening <span class="font-bold text-slate-800">{{ itemToDelete?.jenis_bank }} - {{ itemToDelete?.nomor_rekening }}</span>?
                </p>

                <div class="flex items-center justify-center gap-2">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        :disabled="isDeleting"
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold cursor-pointer disabled:opacity-50"
                    >
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Rekening' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

