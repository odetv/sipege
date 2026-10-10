<script setup>
import {
    CheckCircle2,
    Building2,
    Phone,
    Printer,
    Coins,
    RefreshCw,
} from "lucide-vue-next";

const props = defineProps({
    items: { type: Array, default: () => [] },
    selectedIds: { type: Array, default: () => [] },
    isAllSelected: { type: Boolean, default: false },
});

const emit = defineEmits([
    "toggleSelectAll",
    "toggleSelect",
    "updateAmount",
    "resetAmount",
    "openReceipt",
]);

function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}

function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}

function formatRibuan(num) {
    if (num === null || num === undefined || isNaN(num) || num === "") return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}

function handleAmountInput(item, valStr) {
    const cleanDigits = String(valStr).replace(/\D/g, "");
    const intVal = cleanDigits === "" ? 0 : parseInt(cleanDigits, 10);
    emit("updateAmount", { id: item.id, amount: intVal, isCustom: true });
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
    <div class="rounded-xl bg-white border border-slate-200 shadow-2xs overflow-hidden">
        <!-- Toolbar Atas Tabel (Style Persis Pembayaran Gaji) -->
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Daftar Penerima Insentif Tunai ({{ items.length }} Titik)
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                    Penyaluran Kas Tunai
                </span>
            </div>
            <div class="text-[11px] text-slate-500 font-medium hidden sm:inline">
                Centang untuk memasukkan ke dalam daftar pembayaran yang diekspor/dicetak.
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/90 border-b border-slate-200 text-[11px] font-bold text-slate-700">
                        <th class="py-2.5 px-3 text-center w-10">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                @change="$emit('toggleSelectAll')"
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                            />
                        </th>
                        <th class="py-2.5 px-2 text-center w-10">No</th>
                        <th class="py-2.5 px-4 min-w-[220px]">Satuan Pendidikan / Posyandu</th>
                        <th class="py-2.5 px-3 min-w-[160px]">Penanggung Jawab (PIC)</th>
                        <th class="py-2.5 px-3 text-right">Sasaran PM</th>
                        <th class="py-2.5 px-3 min-w-[180px]">Skema Insentif Harian</th>
                        <th class="py-2.5 px-3 text-center">Hari Kerja</th>
                        <th class="py-2.5 px-4 text-right min-w-[180px]">Total Insentif Tunai (Rp)</th>
                        <th class="py-2.5 px-3 text-center">Metode</th>
                        <th class="py-2.5 px-3 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="(item, index) in items"
                        :key="item.id"
                        :class="[
                            'hover:bg-slate-50/80 transition-colors',
                            selectedIds.includes(item.id) ? 'bg-emerald-50/20' : '',
                        ]"
                    >
                        <!-- Checkbox -->
                        <td class="py-3 px-3 text-center">
                            <input
                                type="checkbox"
                                :checked="selectedIds.includes(item.id)"
                                @change="$emit('toggleSelect', item.id)"
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                            />
                        </td>

                        <!-- No -->
                        <td class="py-3 px-2 text-center font-medium text-slate-400">
                            {{ index + 1 }}
                        </td>

                        <!-- Satuan / Posyandu -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 line-clamp-1">
                                {{ item.nama_kelompok }}
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                <span :class="['px-1.5 py-0.2 rounded text-[10px] font-bold border', getCategoryBadge(item.kategori)]">
                                    {{ item.kategori }}
                                </span>
                                <span>{{ item.desa_kelurahan || '-' }}</span>
                            </div>
                        </td>

                        <!-- PIC & Kontak -->
                        <td class="py-3 px-3">
                            <div class="font-semibold text-slate-800 line-clamp-1">
                                {{ item.nama_pic || '-' }}
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                <Phone class="h-3 w-3 text-emerald-600 shrink-0" />
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

                        <!-- Sasaran PM -->
                        <td class="py-3 px-3 text-right">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 font-extrabold text-slate-800 text-xs">
                                {{ formatNumber(item.total_penerima) }} PM
                            </span>
                        </td>

                        <!-- Skema Insentif Harian -->
                        <td class="py-3 px-3">
                            <div class="font-bold text-slate-800 text-[11px]">
                                {{ item.deskripsi_tarif }}
                            </div>
                            <div class="text-[10px] text-emerald-700 font-medium">
                                {{ item.skema_tier }}
                            </div>
                        </td>

                        <!-- Hari Kerja (Sesuai Rekap Distribusi Terdistribusikan) -->
                        <td class="py-3 px-3 text-center">
                            <template v-if="(item.hari_distribusi ?? item.hari_operasional) > 0">
                                <span class="font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                    {{ item.hari_distribusi ?? item.hari_operasional }} Hari
                                </span>
                                <span
                                    class="block text-[10px] text-emerald-600 font-semibold mt-0.5"
                                    :title="`${item.hari_distribusi ?? item.hari_operasional} hari tercatat terkirim di Rekap Distribusi`"
                                >
                                    ✓ Terdistribusi
                                </span>
                            </template>
                            <template v-else>
                                <span class="font-medium text-slate-400">
                                    0 Hari
                                </span>
                                <span class="block text-[10px] text-slate-400 mt-0.5">
                                    Belum Terkirim
                                </span>
                            </template>
                        </td>

                        <!-- Total Insentif Tunai (Editable Inline dengan Titik Pembilang & Warning Diedit Manual) -->
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <div class="relative w-36">
                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400">Rp</span>
                                    <input
                                        type="text"
                                        :value="formatRibuan(item.amount)"
                                        @input="handleAmountInput(item, $event.target.value)"
                                        :class="[
                                            'w-full pl-8 pr-2 py-1 rounded-lg border text-xs font-mono font-bold text-right focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors',
                                            item.isCustom
                                                ? 'border-amber-400 bg-amber-50/20 text-amber-900 ring-1 ring-amber-300'
                                                : 'border-slate-300 text-slate-900 bg-emerald-50/20'
                                        ]"
                                    />
                                </div>
                                <button
                                    v-if="item.isCustom"
                                    type="button"
                                    @click="$emit('resetAmount', item.id)"
                                    class="p-1 rounded text-slate-400 hover:text-amber-700 transition-colors cursor-pointer"
                                    title="Kembalikan ke nominal otomatis"
                                >
                                    <RefreshCw class="h-3 w-3" />
                                </button>
                            </div>
                            <p v-if="item.isCustom" class="text-[9.5px] text-amber-600 font-semibold mt-0.5 text-right">
                                *Nominal diedit manual
                            </p>
                        </td>

                        <!-- Metode Bayar -->
                        <td class="py-3 px-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <CheckCircle2 class="h-3 w-3 text-emerald-600" />
                                <span>Tunai Langsung</span>
                            </span>
                        </td>

                        <!-- Aksi Cetak Kuitansi -->
                        <td class="py-3 px-3 text-center">
                            <button
                                type="button"
                                @click="$emit('openReceipt', item)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer shadow-2xs"
                                title="Buka kuitansi tanda terima kas tunai"
                            >
                                <Printer class="h-3.5 w-3.5 text-slate-600" />
                                <span>Kuitansi</span>
                            </button>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="!items || items.length === 0">
                        <td colspan="10" class="py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <Building2 class="h-10 w-10 text-slate-300" />
                                <div class="text-sm font-semibold text-slate-600">Tidak ada data penerima insentif ditemukan</div>
                                <div class="text-xs text-slate-400">Coba sesuaikan kata kunci pencarian atau kategori filter.</div>
                            </div>
                        </td>
                    </tr>
                </tbody>

                <!-- Table Footer (Total Row) -->
                <tfoot v-if="items && items.length > 0" class="bg-slate-100 text-xs font-bold border-t-2 border-slate-300">
                    <tr>
                        <td colspan="4" class="py-2.5 px-4 text-right uppercase tracking-wider text-slate-700">
                            Total Alokasi Insentif Tunai:
                        </td>
                        <td class="py-2.5 px-3 text-right font-extrabold text-slate-900">
                            {{ formatNumber(items.reduce((s, it) => s + (Number(it.total_penerima) || 0), 0)) }} PM
                        </td>
                        <td colspan="2" class="py-2.5 px-3 text-center text-slate-500">
                            {{ items.length }} Titik Terdaftar
                        </td>
                        <td class="py-2.5 px-4 text-right font-black text-emerald-950 bg-emerald-100 text-sm">
                            {{ formatRupiah(items.reduce((s, it) => s + (Number(it.amount) || 0), 0)) }}
                        </td>
                        <td colspan="2" class="py-2.5 px-3 text-center text-emerald-800 font-bold">
                            100% Tunai Kas
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
