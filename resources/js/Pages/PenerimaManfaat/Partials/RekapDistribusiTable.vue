<script setup>
import {
    Eye,
    Phone,
    MapPin,
    Building2,
    CheckCircle2,
    Clock,
    Layers,
    UserCheck,
} from "lucide-vue-next";

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["openDetail"]);

function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}

function getCategoryBadge(kategori) {
    switch (kategori) {
        case "Posyandu":
            return "bg-pink-100 text-pink-700 border-pink-200";
        case "SD":
        case "MI":
            return "bg-red-100 text-red-700 border-red-200";
        case "SMP":
        case "MTs":
            return "bg-blue-100 text-blue-700 border-blue-200";
        case "SMA":
        case "SMK":
        case "MA":
            return "bg-slate-100 text-slate-700 border-slate-200";
        case "TK":
        case "RA":
        case "PAUD":
            return "bg-amber-100 text-amber-700 border-amber-200";
        default:
            return "bg-emerald-100 text-emerald-700 border-emerald-200";
    }
}
</script>

<template>
    <div
        class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden"
    >
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500"
                    >
                        <th class="py-3 px-3 text-center w-12">No</th>
                        <th class="py-3 px-4 min-w-[200px]">Kelompok & NPSN</th>
                        <th class="py-3 px-3 text-center">Kategori</th>
                        <th class="py-3 px-3">Desa / Kelurahan</th>
                        <th class="py-3 px-3 text-center">Sasaran (PM)</th>
                        <th class="py-3 px-3 text-right">Porsi Kecil</th>
                        <th class="py-3 px-3 text-right">Porsi Besar</th>
                        <th class="py-3 px-3 text-right">Porsi / Hari</th>
                        <th class="py-3 px-3 text-right">Akumulasi</th>
                        <th class="py-3 px-4">PIC & Kontak</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-3 text-center w-16">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <tr
                        v-for="(item, index) in items"
                        :key="item.id"
                        class="hover:bg-slate-50/80 transition-colors"
                    >
                        <td
                            class="py-3 px-3 text-center font-medium text-slate-400"
                        >
                            {{ index + 1 }}
                        </td>

                        <!-- Nama & NPSN -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 line-clamp-1">
                                {{ item.nama_kelompok }}
                            </div>
                            <div
                                class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5"
                            >
                                <span class="font-semibold text-slate-600"
                                    >{{ item.tipe_identitas || "NPSN" }}:</span
                                >
                                <span>{{ item.kode_identitas || "-" }}</span>
                                <span
                                    v-if="item.jenis_kepemilikan"
                                    class="text-slate-400"
                                    >• {{ item.jenis_kepemilikan }}</span
                                >
                            </div>
                        </td>

                        <!-- Kategori Badge -->
                        <td class="py-3 px-3 text-center">
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-md text-[10px] font-bold border',
                                    getCategoryBadge(item.kategori),
                                ]"
                            >
                                {{ item.kategori }}
                            </span>
                        </td>

                        <!-- Desa -->
                        <td class="py-3 px-3 text-slate-600">
                            <div
                                class="flex items-center gap-1 truncate max-w-[140px]"
                                :title="item.alamat_lengkap"
                            >
                                <MapPin
                                    class="h-3 w-3 text-slate-400 shrink-0"
                                />
                                <span class="truncate">{{
                                    item.desa_kelurahan || "-"
                                }}</span>
                            </div>
                        </td>

                        <!-- Sasaran PM -->
                        <td class="py-3 px-3 text-center">
                            <div class="font-bold text-slate-800">
                                {{ formatNumber(item.total_penerima) }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                L: {{ item.total_laki_laki }} • P:
                                {{ item.total_perempuan }}
                            </div>
                        </td>

                        <!-- PK -->
                        <td
                            class="py-3 px-3 text-right font-medium text-slate-700"
                        >
                            {{ formatNumber(item.porsi_kecil_harian) }}
                        </td>

                        <!-- PB -->
                        <td
                            class="py-3 px-3 text-right font-medium text-slate-700"
                        >
                            {{ formatNumber(item.porsi_besar_harian) }}
                        </td>

                        <!-- Porsi Harian -->
                        <td
                            class="py-3 px-3 text-right font-extrabold text-emerald-700 bg-emerald-50/40"
                        >
                            {{ formatNumber(item.total_porsi_harian) }}
                        </td>

                        <!-- Akumulasi -->
                        <td
                            class="py-3 px-3 text-right font-bold text-slate-900"
                        >
                            {{ formatNumber(item.total_porsi_akumulasi) }}
                        </td>

                        <!-- PIC & Kontak -->
                        <td class="py-3 px-4">
                            <div
                                class="font-semibold text-slate-800 truncate max-w-[150px]"
                            >
                                {{ item.nama_pic || "-" }}
                            </div>
                            <div
                                class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5"
                            >
                                <Phone
                                    class="h-3 w-3 text-emerald-600 shrink-0"
                                />
                                <a
                                    v-if="item.telepon_pic"
                                    :href="`https://wa.me/${item.telepon_pic.replace(/\D/g, '')}`"
                                    target="_blank"
                                    class="text-emerald-700 hover:underline"
                                >
                                    {{ item.telepon_pic }}
                                </a>
                                <span v-else class="text-slate-400">-</span>
                            </div>
                        </td>

                        <!-- Status Distribusi -->
                        <td class="py-3 px-3 text-center">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border',
                                    item.status_distribusi === 'Terkirim'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-blue-50 text-blue-700 border-blue-200',
                                ]"
                            >
                                <CheckCircle2
                                    v-if="item.status_distribusi === 'Terkirim'"
                                    class="h-3 w-3 text-emerald-600"
                                />
                                <Clock v-else class="h-3 w-3 text-blue-600" />
                                <span>{{ item.status_distribusi }}</span>
                            </span>
                        </td>

                        <!-- Aksi -->
                        <td class="py-3 px-3 text-center">
                            <button
                                type="button"
                                @click="$emit('openDetail', item)"
                                title="Lihat Rincian Kelompok"
                                class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-emerald-700 transition-colors cursor-pointer"
                            >
                                <Eye class="h-4 w-4" />
                            </button>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="!items || items.length === 0">
                        <td
                            colspan="12"
                            class="py-12 text-center text-slate-400"
                        >
                            <div
                                class="flex flex-col items-center justify-center gap-2"
                            >
                                <Building2 class="h-10 w-10 text-slate-300" />
                                <div
                                    class="text-sm font-semibold text-slate-600"
                                >
                                    Tidak ada data distribusi ditemukan
                                </div>
                                <div class="text-xs text-slate-400">
                                    Coba ubah kata kunci pencarian atau
                                    sesuaikan filter rentang tanggal.
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
