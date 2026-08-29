<script setup>
import { Link } from "@inertiajs/vue3";
import {
    MapPin,
    Eye,
    Edit3,
    Trash2,
    School,
    Phone,
    Mail,
    User,
    CheckCircle2,
    Calendar,
    Clock,
    Search,
    RotateCcw,
    HeartPulse,
} from "lucide-vue-next";

defineProps({
    filteredKelompokList: {
        type: Array,
        required: true,
    },
    visibleColumns: {
        type: Object,
        required: true,
    },
    visibleColumnCount: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(["openDetail", "confirmDelete", "resetFilters"]);

function getKategoriBadgeColor(kategori) {
    switch (kategori) {
        case "TK":
        case "RA":
        case "PAUD":
            return "bg-amber-50 text-amber-700 border-amber-200";
        case "SD":
        case "MI":
            return "bg-rose-50 text-rose-700 border-rose-200";
        case "SMP":
        case "MTs":
            return "bg-sky-50 text-sky-700 border-sky-200";
        case "SMA":
        case "SMK":
        case "MA":
        case "MAK":
            return "bg-indigo-50 text-indigo-700 border-indigo-200";
        case "Posyandu":
            return "bg-emerald-50 text-emerald-700 border-emerald-200";
        default:
            return "bg-slate-100 text-slate-700 border-slate-200";
    }
}

function formatDateTimeWita(dateString) {
    if (!dateString) return "-";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "-";

    const dtf = new Intl.DateTimeFormat("id-ID", {
        timeZone: "Asia/Makassar",
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hourCycle: "h23",
    });

    const parts = dtf.formatToParts(date);
    const getPart = (type) => parts.find((p) => p.type === type)?.value || "";

    const weekday = getPart("weekday");
    const day = getPart("day");
    const month = getPart("month");
    const year = getPart("year");
    const hour = getPart("hour");
    const minute = getPart("minute");
    const second = getPart("second");

    return `${weekday}, ${day} ${month} ${year}, ${hour}:${minute}:${second} WITA`;
}
</script>

<template>
    <!-- ================= TABLE DATA KELOMPOK ================= -->
    <div
        class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white"
    >
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr
                        class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                    >
                        <th
                            v-if="visibleColumns.no"
                            class="py-3.5 px-4 w-12 text-center"
                        >
                            No
                        </th>
                        <th
                            v-if="visibleColumns.kelompok"
                            class="py-3.5 px-5 min-w-[240px]"
                        >
                            Kelompok
                        </th>
                        <th
                            v-if="visibleColumns.kontak"
                            class="py-3.5 px-5 min-w-[220px]"
                        >
                            Kontak
                        </th>
                        <th
                            v-if="visibleColumns.alamat"
                            class="py-3.5 px-5 min-w-[200px]"
                        >
                            Alamat
                        </th>
                        <th
                            v-if="visibleColumns.gender"
                            class="py-3.5 px-4 text-center min-w-[110px]"
                        >
                            Gender (L/P)
                        </th>
                        <th
                            v-if="visibleColumns.porsi"
                            class="py-3.5 px-4 text-center min-w-[130px]"
                        >
                            Porsi (PK/PB)
                        </th>
                        <th
                            v-if="visibleColumns.total"
                            class="py-3.5 px-4 text-center w-28"
                        >
                            Total Porsi
                        </th>
                        <th
                            v-if="visibleColumns.waktu"
                            class="py-3.5 px-5 min-w-[260px]"
                        >
                            Waktu Daftar & Perbaharui
                        </th>
                        <th
                            v-if="visibleColumns.aksi"
                            class="py-3.5 px-4 text-center w-36"
                        >
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-slate-100 bg-white"
                >
                    <tr
                        v-for="(item, index) in filteredKelompokList"
                        :key="item.id"
                        class="hover:bg-slate-50/60 transition-colors"
                    >
                        <!-- No -->
                        <td
                            v-if="visibleColumns.no"
                            class="py-4 px-4 text-center font-bold text-slate-400"
                        >
                            {{ index + 1 }}
                        </td>

                        <!-- Kelompok -->
                        <td v-if="visibleColumns.kelompok" class="py-4 px-5">
                            <div class="space-y-1.5">
                                <div
                                    class="flex items-center gap-1.5 flex-wrap"
                                >
                                    <span
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-full border"
                                        :class="
                                            getKategoriBadgeColor(
                                                item.kategori,
                                            )
                                        "
                                    >
                                        {{ item.kategori }}
                                    </span>
                                    <span
                                        class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-slate-100 text-slate-600 border border-slate-200"
                                    >
                                        {{
                                            item.jenis_kepemilikan
                                        }}
                                    </span>
                                    <span
                                        v-if="item.kategori === 'Posyandu' && item.jumlah_kader"
                                        class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-sky-50 text-sky-700 border border-sky-200"
                                        title="Jumlah kader penanggung jawab posyandu (tidak masuk hitungan PM)"
                                    >
                                        {{ item.jumlah_kader }} Kader
                                    </span>
                                </div>

                                <div class="font-bold text-slate-900 text-sm">
                                    {{ item.nama_kelompok }}
                                </div>

                                <div
                                    class="text-[11px] font-mono text-slate-500 flex items-center gap-1"
                                >
                                    <span>{{
                                        item.tipe_identitas
                                    }}:</span>
                                    <span
                                        class="font-semibold text-slate-700"
                                        >{{
                                            item.kode_identitas ||
                                            "-"
                                        }}</span
                                    >
                                </div>
                            </div>
                        </td>

                        <!-- Kontak -->
                        <td v-if="visibleColumns.kontak" class="py-4 px-5">
                            <div class="space-y-2 text-xs">
                                <div>
                                    <p
                                        class="font-bold text-slate-900 flex items-center gap-1"
                                    >
                                        <User
                                            class="h-3 w-3 text-slate-400"
                                        />
                                        <span>{{
                                            item.nama_kepala ||
                                            "-"
                                        }}</span>
                                        <span
                                            class="text-[10px] font-normal text-slate-400"
                                            >(Kepala)</span
                                        >
                                    </p>
                                    <div
                                        class="text-[11px] text-slate-500 pl-4 space-y-0.5 mt-0.5"
                                    >
                                        <p
                                            v-if="
                                                item.telepon_kepala
                                            "
                                            class="flex items-center gap-1"
                                        >
                                            <Phone
                                                class="h-2.5 w-2.5 text-slate-400"
                                            />
                                            <span
                                                >+{{
                                                    item.telepon_kepala
                                                }}</span
                                            >
                                        </p>
                                        <p
                                            v-if="
                                                item.email_kepala
                                            "
                                            class="flex items-center gap-1 truncate max-w-[180px]"
                                            :title="
                                                item.email_kepala
                                            "
                                        >
                                            <Mail
                                                class="h-2.5 w-2.5 text-slate-400"
                                            />
                                            <span>{{
                                                item.email_kepala
                                            }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="pt-1.5 border-t border-slate-100"
                                >
                                    <p
                                        class="font-semibold text-slate-800 flex items-center gap-1"
                                    >
                                        <CheckCircle2
                                            class="h-3 w-3 text-emerald-500"
                                        />
                                        <span>{{
                                            item.nama_pic || "-"
                                        }}</span>
                                        <span
                                            class="text-[10px] font-normal text-slate-400"
                                            >(PIC)</span
                                        >
                                    </p>
                                    <div
                                        class="text-[11px] text-slate-500 pl-4 space-y-0.5 mt-0.5"
                                    >
                                        <p
                                            v-if="item.telepon_pic"
                                            class="flex items-center gap-1"
                                        >
                                            <Phone
                                                class="h-2.5 w-2.5 text-slate-400"
                                            />
                                            <span
                                                >+{{
                                                    item.telepon_pic
                                                }}</span
                                            >
                                        </p>
                                        <p
                                            v-if="item.email_pic"
                                            class="flex items-center gap-1 truncate max-w-[180px]"
                                            :title="item.email_pic"
                                        >
                                            <Mail
                                                class="h-2.5 w-2.5 text-slate-400"
                                            />
                                            <span>{{
                                                item.email_pic
                                            }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Wilayah & Alamat -->
                        <td v-if="visibleColumns.alamat" class="py-4 px-5">
                            <div class="space-y-1">
                                <p
                                    class="font-medium text-slate-800 flex items-center gap-1"
                                >
                                    <MapPin
                                        class="h-3 w-3 text-primary shrink-0"
                                    />
                                    <span>
                                        {{
                                            [
                                                item.desa_kelurahan,
                                                item.kecamatan,
                                                item.kabupaten,
                                            ]
                                                .filter(Boolean)
                                                .join(", ") || "-"
                                        }}
                                    </span>
                                </p>
                                <p
                                    class="text-[11px] text-slate-500 pl-4 line-clamp-2 leading-relaxed"
                                    :title="
                                        item.alamat_lengkap ||
                                        '-'
                                    "
                                >
                                    {{
                                        item.alamat_lengkap ||
                                        "-"
                                    }}
                                    <span
                                        v-if="item.kode_pos"
                                        class="font-mono text-slate-400"
                                        >({{
                                            item.kode_pos
                                        }})</span
                                    >
                                </p>
                            </div>
                        </td>

                        <!-- Penerima (L / P) -->
                        <td v-if="visibleColumns.gender" class="py-4 px-4 text-center">
                            <div
                                class="inline-grid grid-cols-2 gap-1 text-xs min-w-[100px]"
                            >
                                <span
                                    class="py-0.5 px-1.5 rounded bg-sky-50 text-sky-700 border border-sky-200 font-bold whitespace-nowrap text-center flex items-center justify-center"
                                    title="Laki-Laki"
                                >
                                    L: {{ item.total_laki_laki }}
                                </span>
                                <span
                                    class="py-0.5 px-1.5 rounded bg-pink-50 text-pink-700 border border-pink-200 font-bold whitespace-nowrap text-center flex items-center justify-center"
                                    title="Perempuan"
                                >
                                    P: {{ item.total_perempuan }}
                                </span>
                            </div>
                        </td>

                        <!-- Porsi (Kecil / Besar) & Alergi -->
                        <td v-if="visibleColumns.porsi" class="py-4 px-4 text-center">
                            <div
                                class="inline-flex flex-col gap-1 min-w-[120px]"
                            >
                                <div
                                    class="grid grid-cols-2 gap-1 text-xs"
                                >
                                    <span
                                        class="py-0.5 px-1.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold whitespace-nowrap text-center flex items-center justify-center"
                                        title="Porsi Kecil"
                                    >
                                        PK: {{ item.total_porsi_kecil ?? 0 }}
                                    </span>
                                    <span
                                        class="py-0.5 px-1.5 rounded bg-blue-50 text-blue-800 border border-blue-200 font-bold whitespace-nowrap text-center flex items-center justify-center"
                                        title="Porsi Besar"
                                    >
                                        PB: {{ item.total_porsi_besar ?? 0 }}
                                    </span>
                                </div>

                                <!-- Badge Alergi Jika Ada -->
                                <div
                                    v-if="
                                        (item.alergi_porsi_kecil > 0 ||
                                        item.alergi_porsi_besar > 0)
                                    "
                                    class="w-full"
                                >
                                    <span
                                        class="w-full py-0.5 px-2 rounded-md bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[10.5px] flex items-center justify-center gap-1 shadow-2xs"
                                        title="Penerima dengan Alergi / Kebutuhan Khusus"
                                    >
                                        <HeartPulse
                                            class="h-3 w-3 text-rose-600 shrink-0"
                                        />
                                        <span>Alergi: {{ (item.alergi_porsi_kecil || 0) + (item.alergi_porsi_besar || 0) }}</span>
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Total Penerima -->
                        <td v-if="visibleColumns.total" class="py-4 px-4 text-center">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20"
                            >
                                {{ item.total_penerima }}
                            </span>
                        </td>

                        <!-- Terdaftar Pada & Terakhir Diperbaharui -->
                        <td v-if="visibleColumns.waktu" class="py-4 px-5">
                            <div
                                class="space-y-1.5 text-[11px]"
                            >
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Terdaftar Pada:</span
                                    >
                                    <span
                                        class="font-medium text-slate-700 flex items-center gap-1.5 mt-0.5"
                                    >
                                        <Calendar
                                            class="h-3 w-3 text-slate-400 shrink-0"
                                        />
                                        <span>{{
                                            formatDateTimeWita(
                                                item.created_at,
                                            )
                                        }}</span>
                                    </span>
                                </div>
                                <div
                                    class="pt-1 border-t border-slate-100"
                                >
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Terakhir Diperbaharui:</span
                                    >
                                    <span
                                        class="font-medium text-slate-700 flex items-center gap-1.5 mt-0.5"
                                    >
                                        <Clock
                                            class="h-3 w-3 text-slate-400 shrink-0"
                                        />
                                        <span>{{
                                            formatDateTimeWita(
                                                item.updated_at ||
                                                    item.created_at,
                                            )
                                        }}</span>
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Aksi (Tombol Elegan) -->
                        <td v-if="visibleColumns.aksi" class="py-4 px-4 text-center">
                            <div
                                class="flex items-center justify-center gap-1.5"
                            >
                                <button
                                    type="button"
                                    @click="emit('openDetail', item)"
                                    class="h-8 w-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                    title="Lihat Detail Rincian"
                                >
                                    <Eye class="h-4 w-4" />
                                </button>
                                <Link
                                    :href="
                                        route(
                                            'penerima-manfaat.edit',
                                            item.uid || item.id,
                                        )
                                    "
                                    class="h-8 w-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                    title="Edit Data Kelompok"
                                >
                                    <Edit3 class="h-4 w-4" />
                                </Link>
                                <button
                                    type="button"
                                    @click="emit('confirmDelete', item)"
                                    class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                    title="Hapus Kelompok"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty State (Dengan Padding Lega) -->
                    <tr v-if="filteredKelompokList.length === 0">
                        <td
                            :colspan="visibleColumnCount || 1"
                            class="py-16 px-6 text-center"
                        >
                            <div
                                class="flex flex-col items-center justify-center max-w-md mx-auto space-y-3"
                            >
                                <div
                                    class="h-16 w-16 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shadow-xs"
                                >
                                    <Search class="h-8 w-8 text-amber-500" />
                                </div>
                                <div class="space-y-1">
                                    <h3
                                        class="text-sm font-bold text-slate-900"
                                    >
                                        Tidak Ada Kelompok yang Cocok
                                    </h3>
                                    <p
                                        class="text-xs text-slate-500 max-w-sm leading-relaxed"
                                    >
                                        Tidak ada data yang sesuai dengan kata kunci pencarian atau kombinasi filter saat ini.
                                    </p>
                                    <div class="pt-2">
                                        <button
                                            type="button"
                                            @click="emit('resetFilters')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 shadow-2xs transition-colors cursor-pointer"
                                        >
                                            <RotateCcw class="h-3.5 w-3.5" />
                                            <span>Reset Filter</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
