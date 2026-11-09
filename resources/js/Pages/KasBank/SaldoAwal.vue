<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Wallet,
    CheckCircle2,
    Edit3,
    RotateCcw,
    Info,
    Calendar,
    DollarSign,
    ShieldCheck,
    AlertCircle,
    X,
    Save,
    Sparkles,
} from "lucide-vue-next";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: null,
    },
    saldoAwal: {
        type: Object,
        default: null,
    },
});

function getTodayStr() {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

function formatRupiah(num) {
    const val = Number(num) || 0;
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}

function formatTanggalIndo(dateStr) {
    if (!dateStr) return "-";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}

// ─── STATE SALDO AWAL (TETAPKAN BARU) ───
const showSaldoAwalConfirmModal = ref(false);
const isSubmittingSaldoAwal = ref(false);

const saldoAwalForm = ref({
    tanggal: getTodayStr(),
    nominal: "",
    nominalDisplay: "",
    keterangan: "Saldo Awal Kas / Bank Unit SPPG",
});

function onSaldoAwalNominalInput(e) {
    const raw = e.target.value.replace(/[^0-9]/g, "");
    if (!raw) {
        saldoAwalForm.value.nominal = "";
        saldoAwalForm.value.nominalDisplay = "";
        return;
    }
    const num = parseInt(raw, 10);
    saldoAwalForm.value.nominal = num;
    saldoAwalForm.value.nominalDisplay = "Rp " + num.toLocaleString("id-ID");
}

function openSaldoAwalConfirm() {
    if (!saldoAwalForm.value.nominal || Number(saldoAwalForm.value.nominal) < 0) {
        alert("Silakan masukkan nominal saldo awal yang valid.");
        return;
    }
    showSaldoAwalConfirmModal.value = true;
}

function submitSaldoAwal() {
    isSubmittingSaldoAwal.value = true;
    router.post(
        route("kas-bank.saldo-awal.store"),
        {
            tanggal: saldoAwalForm.value.tanggal,
            nominal: saldoAwalForm.value.nominal,
            keterangan: saldoAwalForm.value.keterangan,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showSaldoAwalConfirmModal.value = false;
                isSubmittingSaldoAwal.value = false;
            },
            onError: () => {
                isSubmittingSaldoAwal.value = false;
            },
        }
    );
}

// ─── STATE UBAH SALDO AWAL ───
const showEditSaldoAwalModal = ref(false);
const isSubmittingEditSaldoAwal = ref(false);
const editSaldoAwalForm = ref({
    tanggal: getTodayStr(),
    nominal: "",
    nominalDisplay: "",
    keterangan: "",
});

function openEditSaldoAwal() {
    if (!props.saldoAwal) return;
    const nom = Number(props.saldoAwal.nominal) || 0;
    editSaldoAwalForm.value = {
        tanggal: props.saldoAwal.tanggal || getTodayStr(),
        nominal: nom,
        nominalDisplay: nom ? "Rp " + nom.toLocaleString("id-ID") : "",
        keterangan: props.saldoAwal.keterangan || "Saldo Awal Kas / Bank Unit SPPG",
    };
    showEditSaldoAwalModal.value = true;
}

function onEditSaldoAwalNominalInput(e) {
    const raw = e.target.value.replace(/[^0-9]/g, "");
    if (!raw) {
        editSaldoAwalForm.value.nominal = "";
        editSaldoAwalForm.value.nominalDisplay = "";
        return;
    }
    const num = parseInt(raw, 10);
    editSaldoAwalForm.value.nominal = num;
    editSaldoAwalForm.value.nominalDisplay = "Rp " + num.toLocaleString("id-ID");
}

function submitUpdateSaldoAwal() {
    if (!editSaldoAwalForm.value.nominal || Number(editSaldoAwalForm.value.nominal) < 0) {
        alert("Nominal saldo awal wajib diisi.");
        return;
    }

    isSubmittingEditSaldoAwal.value = true;
    router.put(
        route("kas-bank.saldo-awal.update"),
        {
            tanggal: editSaldoAwalForm.value.tanggal,
            nominal: editSaldoAwalForm.value.nominal,
            keterangan: editSaldoAwalForm.value.keterangan,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showEditSaldoAwalModal.value = false;
                isSubmittingEditSaldoAwal.value = false;
            },
            onError: () => {
                isSubmittingEditSaldoAwal.value = false;
            },
        }
    );
}

// ─── STATE RESET / TARIK SALDO AWAL ───
const showTarikSaldoAwalModal = ref(false);
const isSubmittingTarikSaldoAwal = ref(false);

function confirmTarikSaldoAwal() {
    showTarikSaldoAwalModal.value = true;
}

function executeTarikSaldoAwal() {
    isSubmittingTarikSaldoAwal.value = true;
    router.delete(route("kas-bank.saldo-awal.destroy"), {
        preserveScroll: true,
        onSuccess: () => {
            showTarikSaldoAwalModal.value = false;
            isSubmittingTarikSaldoAwal.value = false;
        },
        onError: () => {
            isSubmittingTarikSaldoAwal.value = false;
        },
    });
}
</script>

<template>
    <Head title="Saldo Awal - Kas & Bank SPPG" />

    <AppLayout>
        <div class="space-y-6 w-full pb-16">
            <!-- ─── HEADER UTAMA ─── -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="h-11 w-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm shadow-emerald-600/30">
                        <Wallet class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                                Saldo Awal Kas / Bank
                            </h1>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                Kas & Bank
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Penetapan modal saldo awal kas tunai dan rekening bank pada permulaan operasional SPPG.
                        </p>
                    </div>
                </div>

                <div v-if="props.saldoAwal" class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openEditSaldoAwal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-300/80 rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
                    >
                        <Edit3 class="h-3.5 w-3.5 text-amber-600" />
                        <span>Ubah Saldo Awal</span>
                    </button>
                    <button
                        type="button"
                        @click="confirmTarikSaldoAwal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
                    >
                        <RotateCcw class="h-3.5 w-3.5 text-rose-600" />
                        <span>Tarik / Reset</span>
                    </button>
                </div>
            </div>

            <!-- ─── KARTU SALDO AWAL SUDAH DITETAPKAN ─── -->
            <div
                v-if="props.saldoAwal"
                class="rounded-2xl border border-emerald-200 bg-white overflow-hidden shadow-2xs"
            >
                <div class="p-6 sm:p-8 bg-gradient-to-br from-emerald-500/10 via-teal-50/30 to-white">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 mb-3">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                Saldo Awal Telah Aktif & Ditetapkan
                            </span>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                                Posisi Saldo Awal Kas / Bank
                            </p>
                            <p class="text-3xl sm:text-4xl font-black text-emerald-700 tracking-tight mt-1">
                                {{ formatRupiah(props.saldoAwal.nominal) }}
                            </p>
                            <p class="text-xs text-slate-600 mt-2 flex items-center gap-1.5">
                                <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                <span>Berlaku per tanggal: <strong class="text-slate-800">{{ formatTanggalIndo(props.saldoAwal.tanggal) }}</strong></span>
                            </p>
                        </div>

                        <div class="bg-white/90 p-4 rounded-xl border border-emerald-100 text-xs text-slate-600 space-y-1.5 max-w-md">
                            <p class="font-bold text-slate-800">Catatan Saldo Awal:</p>
                            <p class="italic text-slate-500">&ldquo;{{ props.saldoAwal.keterangan || 'Saldo Awal Unit SPPG' }}&rdquo;</p>
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                <span v-if="props.saldoAwal.user_name">Dicatat oleh: <strong>{{ props.saldoAwal.user_name }}</strong></span>
                                <span v-if="props.saldoAwal.updated_at">Diperbarui: {{ formatTanggalIndo(props.saldoAwal.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 bg-emerald-50/50 border-t border-emerald-100 flex items-center gap-2 text-xs text-emerald-800">
                    <Info class="h-4 w-4 shrink-0 text-emerald-600" />
                    <span>Saldo awal ini dihitung sebagai saldo permulaan dalam Buku Kas Umum (BKU), Transaksi, dan Ringkasan Saldo Akhir SPPG.</span>
                </div>
            </div>

            <!-- ─── FORM TETAPKAN SALDO AWAL BARU ─── -->
            <div
                v-else
                class="rounded-2xl border border-amber-200 bg-white overflow-hidden shadow-2xs"
            >
                <div class="p-6 sm:p-8 bg-gradient-to-br from-amber-500/10 via-orange-50/40 to-white">
                    <div class="flex items-start gap-3.5 mb-5">
                        <div class="h-11 w-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-amber-500/30">
                            <Wallet class="h-5 w-5" />
                        </div>
                        <div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 mb-1.5">
                                Belum Ditetapkan
                            </span>
                            <h2 class="text-lg font-extrabold text-slate-900">
                                Tetapkan Saldo Awal Kas / Bank Unit SPPG
                            </h2>
                            <p class="text-xs text-slate-600 mt-0.5">
                                Masukkan modal kas tunai awal atau saldo rekening bank pertama saat operasional unit SPPG dimulai.
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="openSaldoAwalConfirm" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Tanggal Saldo Awal <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="saldoAwalForm.tanggal"
                                    type="date"
                                    required
                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Nominal Saldo Awal (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    :value="saldoAwalForm.nominalDisplay"
                                    @input="onSaldoAwalNominalInput"
                                    type="text"
                                    required
                                    placeholder="Rp 0"
                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-black text-slate-900 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Keterangan / Catatan Saldo Awal
                            </label>
                            <input
                                v-model="saldoAwalForm.keterangan"
                                type="text"
                                placeholder="Contoh: Saldo Awal Modal Kas / Bank Operasional SPPG BGN"
                                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                            />
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition-all shadow-xs cursor-pointer"
                            >
                                <Save class="h-4 w-4" />
                                <span>Tetapkan Saldo Awal</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ─── PANDUAN & KETENTUAN SALDO AWAL ─── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center gap-2">
                        <ShieldCheck class="h-4 w-4 text-primary" />
                        Ketentuan Saldo Awal SPPG
                    </h3>
                    <ul class="text-xs text-slate-600 space-y-1.5 list-disc pl-4">
                        <li>Saldo awal merupakan saldo kas induk sebelum adanya transaksi belanja atau dropping berkala.</li>
                        <li>Dapat diubah atau di-reset kapan saja jika ada penyesuaian modal awal atau koreksi pembukuan.</li>
                        <li>Pencatatan saldo awal otomatis terintegrasi ke seluruh laporan keuangan SPPG.</li>
                    </ul>
                </div>

                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center gap-2">
                        <Sparkles class="h-4 w-4 text-emerald-600" />
                        Integrasi Kas & Bank
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Setelah saldo awal ditetapkan, seluruh penerimaan dan pengeluaran operasional di menu <strong>Keuangan > Transaksi</strong> akan secara otomatis mengkalkulasi saldo akhir kas/bank secara akurat (real-time).
                    </p>
                </div>
            </div>
        </div>

        <!-- ─── MODAL KONFIRMASI TETAPKAN SALDO AWAL ─── -->
        <Modal :show="showSaldoAwalConfirmModal" @close="showSaldoAwalConfirmModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Konfirmasi Saldo Awal</h3>
                        <p class="text-xs text-slate-400">Pastikan nominal yang Anda masukkan sudah sesuai</p>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 mb-4 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tanggal:</span>
                        <span class="font-bold text-slate-800">{{ formatTanggalIndo(saldoAwalForm.tanggal) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nominal:</span>
                        <span class="text-base font-black text-emerald-700">{{ saldoAwalForm.nominalDisplay }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Keterangan:</span>
                        <span class="font-medium text-slate-700">{{ saldoAwalForm.keterangan }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        @click="showSaldoAwalConfirmModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitSaldoAwal"
                        :disabled="isSubmittingSaldoAwal"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer disabled:opacity-50"
                    >
                        {{ isSubmittingSaldoAwal ? 'Menyimpan...' : 'Ya, Tetapkan Saldo Awal' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- ─── MODAL UBAH SALDO AWAL ─── -->
        <Modal :show="showEditSaldoAwalModal" @close="showEditSaldoAwalModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-amber-100 text-amber-600">
                            <Edit3 class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">Ubah Saldo Awal</h3>
                            <p class="text-xs text-slate-400">Perbarui nominal atau rincian saldo awal kas / bank</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showEditSaldoAwalModal = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitUpdateSaldoAwal" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Tanggal Saldo Awal <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="editSaldoAwalForm.tanggal"
                            type="date"
                            required
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:ring-1 focus:ring-primary focus:border-primary"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Nominal Saldo Awal (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            :value="editSaldoAwalForm.nominalDisplay"
                            @input="onEditSaldoAwalNominalInput"
                            type="text"
                            required
                            placeholder="Rp 0"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-black text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Keterangan
                        </label>
                        <input
                            v-model="editSaldoAwalForm.keterangan"
                            type="text"
                            placeholder="Keterangan saldo awal kas / bank..."
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs text-slate-800 focus:ring-1 focus:ring-primary focus:border-primary"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showEditSaldoAwalModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmittingEditSaldoAwal"
                            class="px-5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold cursor-pointer disabled:opacity-50"
                        >
                            {{ isSubmittingEditSaldoAwal ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ─── MODAL TARIK / RESET SALDO AWAL ─── -->
        <Modal :show="showTarikSaldoAwalModal" @close="showTarikSaldoAwalModal = false" max-width="sm">
            <div class="p-5 text-center">
                <div class="h-12 w-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <RotateCcw class="h-6 w-6" />
                </div>
                <h3 class="text-base font-extrabold text-slate-900 mb-1">Tarik / Reset Saldo Awal?</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Apakah Anda yakin ingin menarik / me-reset Saldo Awal sebesar <strong class="text-slate-800">{{ formatRupiah(props.saldoAwal?.nominal) }}</strong>? Data akan di-reset ke status belum ditetapkan.
                </p>

                <div class="flex items-center justify-center gap-2">
                    <button
                        type="button"
                        @click="showTarikSaldoAwalModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeTarikSaldoAwal"
                        :disabled="isSubmittingTarikSaldoAwal"
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold cursor-pointer disabled:opacity-50"
                    >
                        {{ isSubmittingTarikSaldoAwal ? 'Menarik...' : 'Ya, Tarik Saldo Awal' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

