<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import {
    User,
    Edit3,
    MapPin,
    AlertCircle,
    ArrowRight,
} from "lucide-vue-next";
import { formatTanggalIndo, formatTanggalWaktuIndo } from "@/lib/utils";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    role: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: "gizi", // 'gizi' | 'keuangan'
    },
    petugas: {
        type: Object,
        default: null,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
});

// User Initials
const initials = computed(() => {
    if (!props.petugas?.nama) return "PG";
    const words = props.petugas.nama.trim().split(" ");
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return props.petugas.nama.substring(0, 2).toUpperCase();
});

// Currency Formatter
function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}
</script>

<template>
    <!-- ================= KONDISI 1: JIKA PETUGAS BELUM DITENTUKAN ================= -->
    <div v-if="!petugas" class="w-full">
        <Card className="w-full bg-white border-slate-200 overflow-hidden shadow-2xs">
            <div class="p-8 sm:p-12 text-center max-w-2xl mx-auto">
                <div
                    class="w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4 shadow-md bg-gradient-to-tr from-slate-400 to-slate-500 text-white"
                >
                    <User class="h-8 w-8" />
                </div>

                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 mb-3">
                    <AlertCircle class="h-3.5 w-3.5 text-amber-600" />
                    <span>Belum Ditentukan</span>
                </div>

                <h3 class="text-lg font-bold text-slate-900 tracking-tight">
                    Data {{ title }} Belum Ditentukan
                </h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Saat ini belum ada personil yang ditugaskan sebagai
                    <strong class="text-slate-700 font-semibold">{{ role }}</strong>
                    di unit SPPG ini. Anda dapat menetapkan atau mengubah jabatan personil melalui menu Daftar Petugas.
                </p>

                <div class="mt-6 flex items-center justify-center">
                    <Link
                        :href="route('petugas.index')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold bg-primary hover:bg-primary/90 text-white shadow-xs transition-colors cursor-pointer"
                    >
                        <span>Buka Daftar Petugas</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>
        </Card>
    </div>

    <!-- ================= KONDISI 2: JIKA PETUGAS SUDAH ADA (FULL WIDTH KIRI KANAN) ================= -->
    <div v-else class="w-full">
        <!-- Detail Lengkap Biodata Pengawas (Full Width) -->
        <Card className="w-full bg-white border-slate-200 flex flex-col justify-between">
            <div>
                <!-- Card Header: Avatar Bundar, Nama Lengkap, Subtitle, & Tombol Aksi -->
                <CardHeader className="border-b border-slate-100 pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <!-- Avatar Bundar Sama Persis Seperti Kepala SPPG -->
                            <div
                                class="h-16 w-16 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center text-xl font-extrabold shadow-md shrink-0"
                            >
                                {{ initials }}
                            </div>

                            <div>
                                <CardTitle className="text-lg font-bold text-slate-900">
                                    {{ petugas.nama }}
                                </CardTitle>
                                <CardDescription class="text-xs mt-0.5">
                                    {{ role }} &bull; Informasi Lengkap
                                </CardDescription>
                            </div>
                        </div>

                        <!-- Tombol Kelola Petugas (Gaya Sama dengan Edit Data Pengguna) -->
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('petugas.index')"
                                class="inline-flex items-center gap-1.5 font-medium border border-slate-300 text-slate-700 hover:bg-slate-50 px-3 py-1.5 rounded-lg text-xs transition-colors cursor-pointer"
                            >
                                <Edit3 class="h-3.5 w-3.5 text-primary" />
                                <span>Kelola di Daftar Petugas</span>
                            </Link>
                        </div>
                    </div>
                </CardHeader>

                <!-- Card Content: Grid Kotak Identitas Sama Persis Seperti Kepala SPPG -->
                <CardContent className="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <!-- NIK -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">NIK</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ petugas.nik || "-" }}
                            </span>
                        </div>

                        <!-- NIP / Jam Kerja -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Jam Kerja Operasional</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ petugas.jam_kerja || "-" }}
                            </span>
                        </div>

                        <!-- Email -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Email</span>
                            <span class="text-sm font-semibold text-slate-900 truncate block">
                                {{ petugas.email || "-" }}
                            </span>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Nomor Telepon</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ petugas.no_telp ? (String(petugas.no_telp).startsWith('+') ? petugas.no_telp : `+${petugas.no_telp}`) : "-" }}
                            </span>
                        </div>

                        <!-- Tempat, Tanggal Lahir -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Tempat, Tanggal Lahir</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ petugas.tempat_lahir ? `${petugas.tempat_lahir}, ` : "" }}{{ formatTanggalIndo(petugas.tanggal_lahir) }}
                            </span>
                        </div>

                        <!-- Usia / Umur -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Usia / Umur</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ petugas.umur ? `${petugas.umur} Tahun` : "-" }}
                            </span>
                        </div>

                        <!-- Jenis Kelamin -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Jenis Kelamin</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ petugas.jenis_kelamin === "L" ? "Laki-laki" : (petugas.jenis_kelamin === "P" ? "Perempuan" : "-") }}
                            </span>
                        </div>

                        <!-- Jabatan / Penugasan -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Jabatan / Penugasan</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ petugas.jabatan || role }}
                            </span>
                        </div>

                        <!-- Status Kepegawaian -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Status Kepegawaian</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ petugas.status || "Aktif" }}
                            </span>
                        </div>

                        <!-- Rekening Bank -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Rekening Bank</span>
                            <span class="text-sm font-semibold text-slate-900 font-mono">
                                {{ petugas.jenis_bank || "BNI" }} - {{ petugas.nomor_rekening || "-" }}
                            </span>
                        </div>

                        <!-- Gaji Harian BGN & Bonus Mitra -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Honor Harian</span>
                            <span class="text-sm font-semibold text-slate-900">
                                BGN: {{ formatRupiah(petugas.gaji_harian_bgn) }} • Mitra: {{ formatRupiah(petugas.bonus_harian_mitra) }}
                            </span>
                        </div>

                        <!-- Iuran BPJS Ketenagakerjaan -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Iuran BPJS Ketenagakerjaan</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ formatRupiah(petugas.iuran_bpjs_tk) }} / bulan
                            </span>
                        </div>

                        <!-- Terdaftar Pada -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Terdaftar Pada</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ formatTanggalWaktuIndo(petugas.created_at) }}
                            </span>
                        </div>

                        <!-- Terakhir Diperbarui -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium block">Terakhir Diperbarui</span>
                            <span class="text-sm font-semibold text-slate-900">
                                {{ formatTanggalWaktuIndo(petugas.updated_at || petugas.created_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Kotak Alamat Domisili Sama Persis Seperti Kepala SPPG -->
                    <div class="mt-4 p-4 rounded-lg bg-slate-50 border border-slate-100 space-y-2">
                        <div class="flex items-center gap-1.5 text-slate-500">
                            <MapPin class="h-3.5 w-3.5 text-primary shrink-0" />
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                                Alamat Domisili
                            </span>
                        </div>
                        <div class="text-xs sm:text-sm font-semibold text-slate-800 leading-relaxed">
                            <span>{{ petugas.alamat || "-" }}</span>
                        </div>
                    </div>
                </CardContent>
            </div>
        </Card>
    </div>
</template>
