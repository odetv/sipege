<script setup>
import { ref, computed } from "vue";
import {
    CheckCircle2,
    Building2,
    Phone,
    Printer,
    Receipt,
    Coins,
    RefreshCw,
    ChevronDown,
    ChevronRight,
    Layers,
    Info,
} from "lucide-vue-next";

const props = defineProps({
    items: { type: Array, default: () => [] },
    selectedIds: { type: Array, default: () => [] },
    isAllSelected: { type: Boolean, default: false },
    komponenSekolah: { type: String, default: "semua" },
});

const emit = defineEmits([
    "toggleSelectAll",
    "toggleSelect",
    "updateAmount",
    "resetAmount",
    "openReceipt",
]);

// Hitung total titik nyata (sekolah + seluruh posyandu dihitung per posyandunya)
const totalTitikCount = computed(() => {
    return props.items.reduce((acc, it) => {
        return acc + (it.rincian_posyandu && it.rincian_posyandu.length > 0 ? it.rincian_posyandu.length : 1);
    }, 0);
});

// Hitung titik nyata yang aktif terlayani menerima MBG
const activeTitikCount = computed(() => {
    return props.items.reduce((acc, it) => {
        if (it.rincian_posyandu && it.rincian_posyandu.length > 0) {
            return acc + it.rincian_posyandu.filter(
                sub => (parseInt(sub.hari_distribusi, 10) || 0) > 0 && (Number(sub.amount) || 0) > 0
            ).length;
        }
        return acc + ((parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0 ? 1 : 0);
    }, 0);
});

// Hitung total PM terlayani (hanya dari yang aktif menerima MBG)
const activePmCount = computed(() => {
    return props.items.reduce((sum, it) => {
        if (it.rincian_posyandu && it.rincian_posyandu.length > 0) {
            return sum + it.rincian_posyandu
                .filter(sub => (parseInt(sub.hari_distribusi, 10) || 0) > 0 && (Number(sub.amount) || 0) > 0)
                .reduce((s, sub) => s + (Number(sub.total_penerima) || 0), 0);
        }
        if ((parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0) {
            return sum + (Number(it.total_penerima) || 0);
        }
        return sum;
    }, 0);
});

// Row 99999 (3B Posyandu) default terbuka agar rincian posyandu langsung terlihat
const expandedIds = ref([99999]);

function toggleExpand(id) {
    const idx = expandedIds.value.indexOf(id);
    if (idx > -1) {
        expandedIds.value.splice(idx, 1);
    } else {
        expandedIds.value.push(id);
    }
}

function isExpanded(id) {
    return expandedIds.value.includes(id);
}

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

function getKomponenShortLabel(k) {
    if (k === "siswa") return "Siswa";
    if (k === "guru") return "Guru";
    if (k === "tendik") return "Tendik";
    if (k === "guru_tendik") return "Guru & Tendik";
    return "PM";
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

function isItemDisabled(item) {
    const hari = Number(item.hari_distribusi ?? item.hari_operasional) || 0;
    const amount = Number(item.amount) || 0;
    return hari <= 0 || amount <= 0;
}
</script>

<template>
    <div class="rounded-2xl bg-white border border-slate-200/90 shadow-2xs overflow-hidden">
        <!-- Toolbar Atas Tabel (Style Persis Pembayaran Gaji) -->
        <div class="px-4 sm:px-5 py-3 sm:py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Daftar Penerima Insentif Tunai ({{ activeTitikCount }} Terlayani / {{ totalTitikCount }} Titik)
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                    Penyaluran Kas Tunai
                </span>
            </div>
            <div class="text-[11px] text-slate-500 font-medium hidden sm:inline">
                Centang untuk memasukkan ke dalam daftar pembayaran yang diekspor/dicetak.
            </div>
        </div>

        <!-- Mobile Swipe Hint -->
        <div class="sm:hidden px-3.5 py-1.5 bg-emerald-50/70 border-b border-emerald-100 flex items-center justify-between text-[11px] text-emerald-800 font-medium">
            <span>👉 Geser tabel ke kanan untuk rincian skema insentif & kuitansi</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs min-w-[1050px]">
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
                    <template v-for="(item, index) in items" :key="item.id">
                        <tr
                            :class="[
                                'transition-colors',
                                isItemDisabled(item)
                                    ? 'bg-slate-50/70 opacity-60 text-slate-400'
                                    : (item.rincian_posyandu
                                        ? 'bg-emerald-50/30 hover:bg-emerald-50/50'
                                        : (selectedIds.includes(item.id) ? 'bg-emerald-50/20 hover:bg-slate-50/80' : 'hover:bg-slate-50/80'))
                            ]"
                        >
                            <!-- Checkbox -->
                            <td class="py-3 px-3 text-center">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(item.id)"
                                    :disabled="isItemDisabled(item)"
                                    @change="!isItemDisabled(item) && $emit('toggleSelect', item.id)"
                                    :class="[
                                        'rounded border-slate-300 text-emerald-600 focus:ring-emerald-500',
                                        isItemDisabled(item) ? 'opacity-30 cursor-not-allowed bg-slate-200' : 'cursor-pointer'
                                    ]"
                                    :title="isItemDisabled(item) ? 'Tidak dapat dipilih karena tidak menerima MBG (0 hari kerja)' : 'Pilih sasaran ini'"
                                />
                            </td>

                            <!-- No -->
                            <td class="py-3 px-2 text-center font-medium text-slate-400">
                                {{ index + 1 }}
                            </td>

                            <!-- Satuan / Posyandu -->
                            <td class="py-3 px-4">
                                <div class="font-bold line-clamp-1" :class="isItemDisabled(item) ? 'text-slate-500' : 'text-slate-900'">
                                    {{ item.nama_kelompok }}
                                </div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                    <span :class="['px-1.5 py-0.2 rounded text-[10px] font-bold border', getCategoryBadge(item.kategori)]">
                                        {{ item.kategori }}
                                    </span>
                                    <span>{{ item.desa_kelurahan || '-' }}</span>
                                    <span
                                        v-if="isItemDisabled(item)"
                                        class="px-1.5 py-0.2 rounded text-[9.5px] font-extrabold bg-rose-50 text-rose-600 border border-rose-200"
                                    >
                                        Tidak Menerima MBG
                                    </span>
                                </div>

                                <!-- Tombol Expand/Collapse untuk Rincian Posyandu 3B -->
                                <button
                                    v-if="item.rincian_posyandu && item.rincian_posyandu.length > 0"
                                    type="button"
                                    @click="toggleExpand(item.id)"
                                    class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-1 rounded-lg text-[10.5px] font-bold transition-all bg-emerald-100/90 hover:bg-emerald-200 text-emerald-900 border border-emerald-300/80 shadow-2xs cursor-pointer group"
                                >
                                    <component
                                        :is="isExpanded(item.id) ? ChevronDown : ChevronRight"
                                        class="w-3.5 h-3.5 text-emerald-700 transition-transform group-hover:scale-110"
                                    />
                                    <span>{{ isExpanded(item.id) ? 'Tutup Rincian' : 'Buka Rincian' }} ({{ item.rincian_posyandu.length }} Posyandu)</span>
                                </button>
                            </td>

                            <!-- PIC & Kontak -->
                            <td class="py-3 px-3">
                                <template v-if="item.nama_pic || item.telepon_pic">
                                    <div class="font-semibold text-slate-800 line-clamp-1">
                                        {{ item.nama_pic || '-' }}
                                    </div>
                                    <div v-if="item.telepon_pic" class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                        <Phone class="h-3 w-3 text-emerald-600 shrink-0" />
                                        <a
                                            :href="`https://wa.me/${item.telepon_pic.replace(/\D/g, '')}`"
                                            target="_blank"
                                            class="text-emerald-700 hover:underline"
                                        >
                                            {{ item.telepon_pic }}
                                        </a>
                                    </div>
                                </template>
                                <span v-else class="text-slate-400 font-medium">-</span>
                            </td>

                            <!-- Sasaran PM -->
                            <td class="py-3 px-3 text-right">
                                <div class="inline-flex flex-col items-end">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full font-extrabold text-xs"
                                        :class="item.kategori !== 'Posyandu' && komponenSekolah !== 'semua' ? 'bg-indigo-100 text-indigo-900 ring-1 ring-indigo-300' : 'bg-slate-100 text-slate-800'"
                                        :title="item.breakdown_pm ? `Rincian: ${formatNumber(item.breakdown_pm.siswa)} Siswa, ${formatNumber(item.breakdown_pm.guru)} Guru, ${formatNumber(item.breakdown_pm.tendik)} Tendik (Total: ${formatNumber(item.breakdown_pm.semua)})` : ''"
                                    >
                                        {{ formatNumber(item.total_penerima) }} {{ item.kategori === 'Posyandu' ? 'PM' : getKomponenShortLabel(komponenSekolah) }}
                                    </span>
                                    <span
                                        v-if="item.kategori !== 'Posyandu' && item.breakdown_pm && komponenSekolah !== 'semua'"
                                        class="text-[9px] text-slate-400 font-medium mt-0.5"
                                        :title="`Total keseluruhan PM di sekolah ini: ${formatNumber(item.breakdown_pm.semua)} PM`"
                                    >
                                        dari total {{ formatNumber(item.breakdown_pm.semua) }}
                                    </span>
                                </div>
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
                                    <span class="font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">
                                        0 Hari
                                    </span>
                                    <span class="block text-[10px] text-rose-500 font-semibold mt-0.5">
                                        Tidak Menerima
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
                                            :disabled="isItemDisabled(item)"
                                            @input="handleAmountInput(item, $event.target.value)"
                                            :class="[
                                                'w-full pl-8 pr-2 py-1 rounded-lg border text-xs font-mono font-bold text-right transition-colors',
                                                isItemDisabled(item)
                                                    ? 'border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed opacity-60'
                                                    : (item.isCustom
                                                        ? 'border-amber-400 bg-amber-50/20 text-amber-900 ring-1 ring-amber-300 focus:ring-1 focus:ring-emerald-500'
                                                        : 'border-slate-300 text-slate-900 bg-emerald-50/20 focus:ring-1 focus:ring-emerald-500')
                                            ]"
                                        />
                                    </div>
                                    <button
                                        v-if="item.isCustom && !isItemDisabled(item)"
                                        type="button"
                                        @click="$emit('resetAmount', item.id)"
                                        class="p-1 rounded text-slate-400 hover:text-amber-700 transition-colors cursor-pointer"
                                        title="Kembalikan ke nominal otomatis"
                                    >
                                        <RefreshCw class="h-3 w-3" />
                                    </button>
                                </div>
                                <p v-if="item.isCustom && !isItemDisabled(item)" class="text-[9.5px] text-amber-600 font-semibold mt-0.5 text-right">
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
                                    :disabled="isItemDisabled(item)"
                                    @click="!isItemDisabled(item) && $emit('openReceipt', item)"
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-bold text-[11px] border transition-all whitespace-nowrap',
                                        !isItemDisabled(item)
                                            ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200 cursor-pointer shadow-2xs hover:shadow-xs'
                                            : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed opacity-50'
                                    ]"
                                    :title="!isItemDisabled(item) ? 'Lihat & download kuitansi resmi untuk titik sasaran ini' : 'Tidak dapat mencetak kuitansi karena sasaran ini tidak menerima MBG'"
                                >
                                    <Receipt class="h-3.5 w-3.5 shrink-0" :class="!isItemDisabled(item) ? 'text-emerald-600' : 'text-slate-400'" />
                                    <span>Kwitansi</span>
                                </button>
                            </td>
                        </tr>

                        <!-- Baris Rincian Expand Detail Masing-Masing Posyandu 3B -->
                        <tr
                            v-if="item.rincian_posyandu && item.rincian_posyandu.length > 0 && isExpanded(item.id)"
                            class="bg-gradient-to-b from-emerald-50/30 to-slate-50/60 border-b-2 border-emerald-200/80"
                        >
                            <td colspan="10" class="py-3.5 px-3 sm:px-6">
                                <div class="rounded-xl border border-emerald-200 bg-white shadow-xs overflow-hidden">
                                    <!-- Header Panel Rincian -->
                                    <div class="px-4 py-3 bg-gradient-to-r from-emerald-50/90 via-teal-50/40 to-white border-b border-emerald-100 flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="p-2 bg-emerald-100 text-emerald-700 rounded-lg shrink-0">
                                                <Layers class="w-4 h-4" />
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900 text-xs flex items-center gap-2 flex-wrap">
                                                    <span>Rincian Pembagian Alokasi {{ item.rincian_posyandu.length }} Posyandu 3B</span>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                        Total {{ formatNumber(item.total_penerima) }} PM • {{ item.skema_tier }}
                                                    </span>
                                                </div>
                                                <div class="text-[11px] text-slate-500 mt-0.5">
                                                    Alokasi 3B Posyandu didistribusikan secara proporsional sesuai jumlah sasaran PM masing-masing Posyandu.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200">
                                                Opsi Bagi Rata: {{ formatRupiah(item.rincian_posyandu[0]?.nominal_rata || 0) }} / posyandu
                                            </span>
                                            <span class="px-2.5 py-1 rounded-md text-[11px] font-black bg-emerald-700 text-white shadow-2xs">
                                                Total Alokasi: {{ formatRupiah(item.amount) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Tabel Rincian Posyandu -->
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left text-xs border-collapse">
                                            <thead>
                                                <tr class="bg-slate-100/80 border-b border-slate-200 text-[10.5px] font-bold text-slate-600 uppercase tracking-wider">
                                                    <th class="py-2.5 px-3 text-center w-8">#</th>
                                                    <th class="py-2.5 px-3 min-w-[220px]">Nama Posyandu & Lokasi</th>
                                                    <th class="py-2.5 px-3 min-w-[170px]">Kader / PIC & Kontak</th>
                                                    <th class="py-2.5 px-3 text-right">
                                <div class="flex flex-col items-end">
                                    <span>Sasaran PM</span>
                                    <span v-if="komponenSekolah !== 'semua'" class="text-[9px] font-bold text-indigo-700 normal-case">
                                        ({{ getKomponenShortLabel(komponenSekolah) }})
                                    </span>
                                </div>
                            </th>
                                                    <th class="py-2.5 px-3 text-center min-w-[130px]">Porsi Sasaran (%)</th>
                                                    <th class="py-2.5 px-3 text-center">Hari Layanan</th>
                                                    <th class="py-2.5 px-4 text-right min-w-[160px]">Nominal Insentif (Rp)</th>
                                                    <th class="py-2.5 px-3 text-center w-28">Kwitansi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <tr
                                                    v-for="(sub, subIdx) in item.rincian_posyandu"
                                                    :key="sub.id"
                                                    :class="[
                                                        'transition-colors',
                                                        sub.hari_distribusi > 0 && sub.amount > 0
                                                            ? 'hover:bg-emerald-50/30'
                                                            : 'bg-slate-50/70 opacity-60 text-slate-400'
                                                    ]"
                                                >
                                                    <td class="py-2.5 px-3 text-center font-bold text-slate-400">
                                                        {{ subIdx + 1 }}
                                                    </td>
                                                    <td class="py-2.5 px-3">
                                                        <div class="font-bold" :class="(sub.hari_distribusi > 0 && sub.amount > 0) ? 'text-slate-800' : 'text-slate-500'">
                                                            {{ sub.nama_kelompok }}
                                                        </div>
                                                        <div class="text-[10.5px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                                            <span class="px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-pink-100 text-pink-700 border border-pink-200">
                                                                Posyandu
                                                            </span>
                                                            <span>{{ sub.desa_kelurahan }}</span>
                                                            <span
                                                                v-if="sub.hari_distribusi <= 0 || sub.amount <= 0"
                                                                class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-rose-50 text-rose-600 border border-rose-200"
                                                            >
                                                                Tidak Menerima MBG
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5 px-3">
                                                        <div class="font-semibold text-slate-800">
                                                            {{ sub.nama_pic }}
                                                        </div>
                                                        <div class="text-[10.5px] text-slate-500 flex items-center gap-1 mt-0.5">
                                                            <Phone class="h-3 w-3 text-emerald-600 shrink-0" />
                                                            <a
                                                                v-if="sub.telepon_pic"
                                                                :href="`https://wa.me/${sub.telepon_pic.replace(/\D/g, '')}`"
                                                                target="_blank"
                                                                class="text-emerald-700 hover:underline font-mono"
                                                            >
                                                                {{ sub.telepon_pic }}
                                                            </a>
                                                            <span v-else class="text-slate-400">-</span>
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-right">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 font-extrabold text-slate-900 text-xs">
                                                            {{ formatNumber(sub.total_penerima) }} PM
                                                        </span>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <div class="inline-flex flex-col items-center">
                                                            <span class="font-black text-slate-800 text-[11px]">{{ sub.persentase_pm }}%</span>
                                                            <div class="w-16 bg-slate-200 rounded-full h-1.5 mt-1 overflow-hidden">
                                                                <div
                                                                    class="bg-emerald-600 h-1.5 rounded-full"
                                                                    :style="{ width: `${Math.min(100, sub.persentase_pm)}%` }"
                                                                ></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <template v-if="sub.hari_distribusi > 0 && sub.amount > 0">
                                                            <span class="font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded text-[11px] border border-emerald-200">
                                                                {{ sub.hari_distribusi }} Hari
                                                            </span>
                                                            <span class="block text-[9.5px] text-emerald-600 font-semibold mt-0.5">
                                                                ✓ Menerima MBG
                                                            </span>
                                                        </template>
                                                        <template v-else>
                                                            <span class="font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded text-[11px] border border-slate-200">
                                                                0 Hari
                                                            </span>
                                                            <span class="block text-[9.5px] text-rose-500 font-bold mt-0.5">
                                                                Tidak Menerima
                                                            </span>
                                                        </template>
                                                    </td>
                                                    <td class="py-2.5 px-4 text-right">
                                                        <div class="font-black text-xs" :class="(sub.hari_distribusi > 0 && sub.amount > 0) ? 'text-slate-900' : 'text-slate-400'">
                                                            {{ formatRupiah(sub.amount) }}
                                                        </div>
                                                        <div class="text-[9.5px] text-slate-500">
                                                            <template v-if="sub.hari_distribusi > 0 && sub.amount > 0">
                                                                <span v-if="sub.hari_distribusi > 1" class="font-medium text-slate-600">{{ formatRupiah(sub.tarif_harian) }}/hari • </span>Proporsional ({{ sub.persentase_pm }}%)
                                                            </template>
                                                            <template v-else>
                                                                <span class="text-slate-400 italic">Tidak ada insentif</span>
                                                            </template>
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <button
                                                            type="button"
                                                            :disabled="sub.hari_distribusi <= 0 || sub.amount <= 0"
                                                            @click="(sub.hari_distribusi > 0 && sub.amount > 0) && $emit('openReceipt', sub)"
                                                            :class="[
                                                                'inline-flex items-center gap-1 px-2.5 py-1 rounded font-bold text-[10.5px] border transition-all whitespace-nowrap',
                                                                (sub.hari_distribusi > 0 && sub.amount > 0)
                                                                    ? 'bg-white hover:bg-emerald-50 text-emerald-800 border-emerald-300 cursor-pointer shadow-2xs hover:shadow-xs'
                                                                    : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed opacity-50'
                                                            ]"
                                                            :title="(sub.hari_distribusi > 0 && sub.amount > 0) ? 'Cetak Kuitansi Resmi Khusus Posyandu Ini' : 'Tidak dapat mencetak kuitansi karena tidak menerima MBG'"
                                                        >
                                                            <Receipt class="h-3 w-3 shrink-0" :class="(sub.hari_distribusi > 0 && sub.amount > 0) ? 'text-emerald-600' : 'text-slate-400'" />
                                                            <span>Kwitansi</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="bg-slate-50 border-t border-slate-200 text-xs font-bold">
                                                <tr>
                                                    <td colspan="3" class="py-2.5 px-3 text-right uppercase tracking-wider text-slate-600 text-[10.5px]">
                                                        Total Akumulasi {{ item.rincian_posyandu.length }} Posyandu:
                                                    </td>
                                                    <td class="py-2.5 px-3 text-right font-extrabold text-slate-900">
                                                        {{ formatNumber(item.rincian_posyandu.reduce((s, p) => s + (Number(p.total_penerima) || 0), 0)) }} PM
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center font-extrabold text-emerald-700">
                                                        100%
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">
                                                        {{ item.hari_distribusi }} Hari
                                                    </td>
                                                    <td class="py-2.5 px-4 text-right font-black text-emerald-900 text-xs bg-emerald-50">
                                                        {{ formatRupiah(item.rincian_posyandu.reduce((s, p) => s + (Number(p.amount) || 0), 0)) }}
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center text-[10px] text-emerald-700 font-bold">
                                                        Sesuai Alokasi
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

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
                            {{ formatNumber(activePmCount) }} PM
                        </td>
                        <td colspan="2" class="py-2.5 px-3 text-center text-slate-600 font-semibold text-[11px]">
                            {{ activeTitikCount }} Terlayani / {{ totalTitikCount }} Titik
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
