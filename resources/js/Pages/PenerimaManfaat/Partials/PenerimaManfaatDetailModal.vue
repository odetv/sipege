<script setup>
import { Link } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import Button from "@/Components/ui/Button.vue";
import {
    User,
    Users,
    Mail,
    Phone,
    MapPin,
    ExternalLink,
    HeartPulse,
} from "lucide-vue-next";
import { sortRincianByKategori } from "@/Services/penerimaManfaatConfig";
import { formatWilayahName, formatKabupatenName } from "@/Services/wilayah";

defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    activeKelompok: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close"]);

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
    <!-- ================= MODAL DETAIL KELOMPOK ================= -->
    <Modal :show="isOpen" @close="emit('close')" max-width="3xl">
        <div v-if="activeKelompok" class="p-6 space-y-6">
            <!-- Modal Header -->
            <div
                class="flex items-start justify-between border-b border-slate-100 pb-4"
            >
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-xl font-bold text-slate-900">
                            {{ activeKelompok.nama_kelompok }}
                        </h3>
                        <span
                            :class="[
                                'px-2 py-0.5 text-xs font-bold rounded-full border',
                                getKategoriBadgeColor(
                                    activeKelompok.kategori,
                                ),
                            ]"
                        >
                            {{ activeKelompok.kategori }}
                        </span>
                        <span
                            :class="[
                                'px-2 py-0.5 text-xs font-medium rounded',
                                activeKelompok.jenis_kepemilikan ===
                                'Negeri'
                                    ? 'bg-blue-50 text-blue-700 border border-blue-200'
                                    : 'bg-amber-50 text-amber-700 border border-amber-200',
                            ]"
                        >
                            {{ activeKelompok.jenis_kepemilikan }}
                        </span>
                        <span
                            v-if="activeKelompok.kategori === 'Posyandu' && activeKelompok.jumlah_kader"
                            class="px-2 py-0.5 text-xs font-bold rounded-full bg-sky-50 text-sky-700 border border-sky-200"
                            title="Jumlah kader penanggung jawab (tidak dihitung sebagai PM)"
                        >
                            {{ activeKelompok.jumlah_kader }} Kader
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ activeKelompok.tipe_identitas }}:
                        {{ activeKelompok.kode_identitas }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="emit('close')"
                    class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-100 cursor-pointer transition duration-150 ease-in-out"
                >
                    ✕
                </button>
            </div>

            <!-- Kontak & Alamat Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- KS & PIC Card -->
                <div
                    class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3"
                >
                    <h4
                        class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5"
                    >
                        <User class="h-3.5 w-3.5 text-primary" />
                        <span>Kontak Satuan</span>
                    </h4>

                    <div class="space-y-2 text-xs">
                        <!-- Khusus Posyandu: Jumlah Kader Card -->
                        <div
                            v-if="activeKelompok.kategori === 'Posyandu'"
                            class="p-2.5 rounded-lg bg-sky-50/80 border border-sky-200 flex items-center justify-between"
                        >
                            <div>
                                <p class="text-[11px] font-bold text-sky-900 uppercase flex items-center gap-1.5">
                                    <Users class="h-3 w-3 text-sky-600" />
                                    <span>Kader Posyandu</span>
                                </p>
                                <p class="text-[10px] text-sky-700 mt-0.5">
                                    Penanggung jawab (tidak dihitung PM)
                                </p>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-extrabold rounded-lg bg-sky-600 text-white shadow-2xs">
                                {{ activeKelompok.jumlah_kader || 0 }} Orang
                            </span>
                        </div>

                        <div
                            class="p-2.5 rounded-lg bg-white border border-slate-200/70"
                        >
                            <p
                                class="text-[11px] font-bold text-slate-400 uppercase"
                            >
                                Kepala Satuan / Pengelola
                            </p>
                            <p class="font-bold text-slate-900 mt-0.5">
                                {{ activeKelompok.nama_kepala }}
                            </p>
                            <p
                                class="text-slate-600 text-[11px] mt-0.5 flex items-center gap-1"
                            >
                                <Mail class="h-3 w-3 text-slate-400" />
                                <span>{{
                                    activeKelompok.email_kepala
                                }}</span>
                            </p>
                            <p
                                class="text-slate-600 text-[11px] mt-0.5 flex items-center gap-1"
                            >
                                <Phone class="h-3 w-3 text-slate-400" />
                                <span
                                    >+{{
                                        activeKelompok.telepon_kepala
                                    }}</span
                                >
                            </p>
                        </div>

                        <div
                            class="p-2.5 rounded-lg bg-white border border-slate-200/70"
                        >
                            <p
                                class="text-[11px] font-bold text-slate-400 uppercase"
                            >
                                PIC (Petugas Lapangan)
                            </p>
                            <p class="font-bold text-slate-900 mt-0.5">
                                {{ activeKelompok.nama_pic }}
                            </p>
                            <p
                                class="text-slate-600 text-[11px] mt-0.5 flex items-center gap-1"
                            >
                                <Mail class="h-3 w-3 text-slate-400" />
                                <span>{{ activeKelompok.email_pic }}</span>
                            </p>
                            <p
                                class="text-slate-600 text-[11px] mt-0.5 flex items-center gap-1"
                            >
                                <Phone class="h-3 w-3 text-slate-400" />
                                <span
                                    >+{{ activeKelompok.telepon_pic }}</span
                                >
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Alamat & Lokasi Card -->
                <div
                    class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3"
                >
                    <h4
                        class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5"
                    >
                        <MapPin class="h-3.5 w-3.5 text-rose-500" />
                        <span>Alamat & Geografis</span>
                    </h4>

                    <div class="space-y-2 text-xs">
                        <div
                            class="p-2.5 rounded-lg bg-white border border-slate-200/70"
                        >
                            <p
                                class="text-[10px] font-bold text-slate-400 uppercase"
                            >
                                Alamat Lengkap
                            </p>
                            <p class="text-[11px] text-slate-500 mt-1">
                                {{ activeKelompok.alamat_lengkap }},
                                Desa/Kelurahan
                                {{
                                    formatWilayahName(
                                        activeKelompok.desa_kelurahan,
                                    )
                                }}, Kecamatan
                                {{
                                    formatWilayahName(
                                        activeKelompok.kecamatan,
                                    )
                                }}, Kabupaten
                                {{
                                    formatKabupatenName(
                                        activeKelompok.kabupaten,
                                    )
                                }}, Provinsi
                                {{
                                    formatWilayahName(
                                        activeKelompok.provinsi,
                                    )
                                }}
                                ({{ activeKelompok.kode_pos }})
                            </p>
                        </div>

                        <div
                            class="p-2.5 rounded-lg bg-white border border-slate-200/70 flex items-center justify-between"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-bold text-slate-400 uppercase"
                                >
                                    Titik Koordinat
                                </p>
                                <p
                                    class="text-xs font-bold text-slate-800 mt-0.5"
                                >
                                    {{
                                        Number(
                                            activeKelompok.latitude,
                                        ).toFixed(6)
                                    }},
                                    {{
                                        Number(
                                            activeKelompok.longitude,
                                        ).toFixed(6)
                                    }}
                                </p>
                            </div>
                            <a
                                :href="`https://www.google.com/maps?q=${activeKelompok.latitude},${activeKelompok.longitude}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700"
                            >
                                <span>Google Maps</span>
                                <ExternalLink class="h-3 w-3" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABEL RINCIAN JUMLAH PENERIMA MANFAAT -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4
                        class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5"
                    >
                        <Users class="h-4 w-4 text-primary" />
                        <span>Rincian Jumlah Penerima Manfaat</span>
                    </h4>

                    <div
                        class="flex flex-wrap items-center gap-1.5 text-xs"
                    >
                        <span
                            class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold"
                        >
                            K: {{ activeKelompok.total_porsi_kecil ?? 0 }}
                        </span>
                        <span
                            class="px-2 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200 font-bold"
                        >
                            B: {{ activeKelompok.total_porsi_besar ?? 0 }}
                        </span>
                        <span
                            class="px-2 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200 font-bold"
                        >
                            L: {{ activeKelompok.total_laki_laki }}
                        </span>
                        <span
                            class="px-2 py-0.5 rounded bg-pink-50 text-pink-700 border border-pink-200 font-bold"
                        >
                            P: {{ activeKelompok.total_perempuan }}
                        </span>
                        <span
                            class="px-2.5 py-0.5 rounded bg-primary text-white font-extrabold shadow-xs"
                        >
                            Total: {{ activeKelompok.total_penerima }}
                        </span>
                    </div>
                </div>

                <div
                    class="border border-slate-200 rounded-xl overflow-x-auto"
                >
                    <table class="w-full min-w-[600px] text-left text-xs border-collapse">
                        <thead>
                            <tr
                                class="bg-slate-100/80 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase"
                            >
                                <th class="py-2.5 px-4 w-12 text-center">
                                    No
                                </th>
                                <th class="py-2.5 px-4">Sub Kategori</th>
                                <th class="py-2.5 px-4 text-center w-28">
                                    Jenis Porsi
                                </th>
                                <th class="py-2.5 px-4 text-center w-24">
                                    Laki-Laki
                                </th>
                                <th class="py-2.5 px-4 text-center w-24">
                                    Perempuan
                                </th>
                                <th class="py-2.5 px-4 text-center w-24">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(
                                    rincian, idx
                                ) in sortRincianByKategori(
                                    activeKelompok.rincian,
                                    activeKelompok.kategori,
                                )"
                                :key="rincian.id || idx"
                                class="hover:bg-slate-50/50"
                            >
                                <td
                                    class="py-2.5 px-4 text-center text-slate-400 font-medium"
                                >
                                    {{ idx + 1 }}
                                </td>
                                <td
                                    class="py-2.5 px-4 font-semibold text-slate-800"
                                >
                                    {{ rincian.sub_kategori }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span
                                        v-if="
                                            rincian.jenis_porsi ===
                                            'Porsi Kecil'
                                        "
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                    >
                                        Porsi Kecil
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200"
                                    >
                                        Porsi Besar
                                    </span>
                                </td>
                                <td
                                    class="py-2.5 px-4 text-center font-medium text-sky-700"
                                >
                                    {{ rincian.jumlah_laki_laki }}
                                </td>
                                <td
                                    class="py-2.5 px-4 text-center font-medium text-pink-700"
                                >
                                    {{ rincian.jumlah_perempuan }}
                                </td>
                                <td
                                    class="py-2.5 px-4 text-center font-bold text-slate-900"
                                >
                                    {{ rincian.total }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr
                                class="bg-slate-50 font-bold border-t border-slate-200 text-xs"
                            >
                                <td
                                    colspan="3"
                                    class="py-2.5 px-4 text-right uppercase tracking-wider text-slate-600"
                                >
                                    Total Penerima Manfaat
                                </td>
                                <td
                                    class="py-2.5 px-4 text-center text-sky-700"
                                >
                                    {{ activeKelompok.total_laki_laki }}
                                </td>
                                <td
                                    class="py-2.5 px-4 text-center text-pink-700"
                                >
                                    {{ activeKelompok.total_perempuan }}
                                </td>
                                <td
                                    class="py-2.5 px-4 text-center text-primary font-extrabold"
                                >
                                    {{ activeKelompok.total_penerima }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- DATA ALERGI MAKANAN DI MODAL -->
            <div
                class="p-4 rounded-xl bg-rose-50/50 border border-rose-200/70 space-y-3"
            >
                <div
                    class="flex items-center justify-between flex-wrap gap-2"
                >
                    <h4
                        class="text-xs font-bold uppercase tracking-wider text-rose-900 flex items-center gap-1.5"
                    >
                        <HeartPulse class="h-4 w-4 text-rose-600" />
                        <span>Data Alergi Makanan & Kebutuhan Khusus</span>
                    </h4>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span
                            class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100/80 text-amber-900 border border-amber-200"
                        >
                            PK: {{ activeKelompok.alergi_porsi_kecil || 0 }}
                        </span>
                        <span
                            class="px-2 py-0.5 rounded text-xs font-bold bg-blue-100/80 text-blue-900 border border-blue-200"
                        >
                            PB: {{ activeKelompok.alergi_porsi_besar || 0 }}
                        </span>
                        <span
                            class="px-2.5 py-0.5 rounded-full text-xs font-black bg-white text-rose-700 border border-rose-200 shadow-2xs"
                        >
                            Total:
                            {{
                                (activeKelompok.alergi_porsi_kecil || 0) +
                                (activeKelompok.alergi_porsi_besar || 0)
                            }}
                        </span>
                    </div>
                </div>

                <!-- Tabel Klasifikasi Alergi per Satuan Jenis -->
                <div
                    v-if="
                        activeKelompok.keterangan_alergi &&
                        activeKelompok.keterangan_alergi.length > 0
                    "
                    class="border border-rose-200/80 rounded-lg overflow-x-auto bg-white mt-2"
                >
                    <table class="w-full min-w-[500px] text-left text-xs border-collapse">
                        <thead>
                            <tr
                                class="bg-rose-100/50 text-rose-900 font-bold text-[10.5px] uppercase border-b border-rose-200/70"
                            >
                                <th class="py-2 px-3 w-10 text-center">No</th>
                                <th class="py-2 px-3">Jenis Alergi / Pantangan</th>
                                <th class="py-2 px-3 text-center w-32">
                                    Porsi Kecil (PK)
                                </th>
                                <th class="py-2 px-3 text-center w-32">
                                    Porsi Besar (PB)
                                </th>
                                <th class="py-2 px-3 text-center w-28">
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-100/60 text-slate-800">
                            <tr
                                v-for="(al, idx) in activeKelompok.keterangan_alergi"
                                :key="idx"
                                class="hover:bg-rose-50/30"
                            >
                                <td
                                    class="py-2 px-3 text-center text-slate-400 font-medium"
                                >
                                    {{ idx + 1 }}
                                </td>
                                <td class="py-2 px-3 font-semibold text-slate-900">
                                    <div class="flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        <span>{{
                                            typeof al === "string"
                                                ? al
                                                : al.jenis_alergi
                                        }}</span>
                                    </div>
                                </td>
                                <td
                                    class="py-2 px-3 text-center font-bold text-amber-800"
                                >
                                    {{
                                        typeof al === "string"
                                            ? "-"
                                            : (al.porsi_kecil || 0)
                                    }}
                                </td>
                                <td
                                    class="py-2 px-3 text-center font-bold text-blue-800"
                                >
                                    {{
                                        typeof al === "string"
                                            ? "-"
                                            : (al.porsi_besar || 0)
                                    }}
                                </td>
                                <td
                                    class="py-2 px-3 text-center font-extrabold text-slate-900"
                                >
                                    {{
                                        typeof al === "string"
                                            ? "-"
                                            : (Number(al.porsi_kecil) || 0) +
                                              (Number(al.porsi_besar) || 0)
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-xs text-slate-400 italic">
                    Tidak ada riwayat alergi makanan khusus yang dilaporkan pada kelompok ini.
                </p>
            </div>

            <!-- Footer Modal -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100"
            >
                <div class="text-[11px] text-slate-500 space-y-0.5">
                    <p>
                        <span class="font-semibold text-slate-700"
                            >Terdaftar:</span
                        >
                        {{ formatDateTimeWita(activeKelompok.created_at) }}
                    </p>
                    <p>
                        <span class="font-semibold text-slate-700"
                            >Terakhir Diperbarui:</span
                        >
                        {{ formatDateTimeWita(activeKelompok.updated_at) }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="
                            route(
                                'penerima-manfaat.edit',
                                activeKelompok.uid || activeKelompok.id,
                            )
                        "
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-600 text-white shadow-xs"
                    >
                        Edit Data
                    </Link>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="emit('close')"
                    >
                        Tutup
                    </Button>
                </div>
            </div>
        </div>
    </Modal>
</template>
