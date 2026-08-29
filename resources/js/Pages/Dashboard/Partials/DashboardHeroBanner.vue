<script setup>
import Badge from "@/Components/ui/Badge.vue";
import { Building2, Sparkles } from "lucide-vue-next";
import { formatTanggalWaktuIndo } from "@/lib/utils";

defineProps({
    user: {
        type: Object,
        required: true,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    fullName: {
        type: String,
        required: true,
    },
    statusVariant: {
        type: String,
        default: "secondary",
    },
});
</script>

<template>
    <!-- Welcome Hero Banner -->
    <div
        class="rounded-2xl bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-800 p-6 sm:p-8 text-white shadow-lg shadow-blue-900/10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center mb-6"
    >
        <!-- Sisi Kiri: Info Pengguna & Hak Akses (col-span-7) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        variant="secondary"
                        className="bg-white/20 hover:bg-white/30 text-white border-none text-xs"
                    >
                        <Sparkles class="h-3 w-3 mr-1 text-amber-300" />
                        Terverifikasi
                    </Badge>
                    <Badge
                        :variant="statusVariant"
                        className="text-xs font-semibold"
                    >
                        {{ unitSppg?.status || "Belum Operasional" }}
                    </Badge>
                </div>
                <h1
                    class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white"
                >
                    Hai, {{ fullName }}
                </h1>
                <p class="text-blue-100 text-sm max-w-2xl leading-relaxed">
                    Kepala {{ unitSppg?.nama || "Unit SPPG" }}
                </p>
            </div>

            <!-- Info Hak Akses & Registrasi -->
            <div
                class="grid grid-cols-2 gap-4 pt-3 border-t border-white/15 max-w-md"
            >
                <div>
                    <p
                        class="text-[11px] text-blue-200 uppercase tracking-wider font-medium"
                    >
                        Hak Akses
                    </p>
                    <p
                        class="text-sm font-bold uppercase tracking-wider text-white mt-0.5"
                    >
                        {{ user.role || "-" }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-[11px] text-blue-200 uppercase tracking-wider font-medium"
                    >
                        Terdaftar Pada
                    </p>
                    <p class="text-xs font-medium text-white mt-0.5">
                        {{ formatTanggalWaktuIndo(user.created_at) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Sisi Kanan: Foto Unit SPPG (col-span-5) -->
        <div class="lg:col-span-5 flex justify-center lg:justify-end">
            <div
                v-if="unitSppg?.photo"
                class="relative w-full max-w-md h-44 sm:h-48 rounded-xl overflow-hidden border border-white/20 shadow-md bg-white/10 group"
            >
                <img
                    :src="unitSppg.photo"
                    :alt="unitSppg.nama || 'Foto Unit SPPG'"
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent flex items-end p-3"
                >
                    <span class="text-xs font-semibold text-white truncate">
                        {{ unitSppg?.nama || "Unit SPPG" }}
                    </span>
                </div>
            </div>
            <div
                v-else
                class="w-full max-w-md h-44 sm:h-48 rounded-xl bg-white/10 backdrop-blur-xs border border-white/20 p-5 flex flex-col items-center justify-center text-center shadow-xs"
            >
                <div
                    class="h-12 w-12 rounded-2xl bg-white/20 text-white flex items-center justify-center mb-2 shadow-2xs border border-white/30"
                >
                    <Building2 class="h-6 w-6 text-white" />
                </div>
                <span
                    class="text-xl font-black tracking-widest text-white font-mono"
                >
                    SPPG
                </span>
                <span class="text-xs text-blue-100/80 mt-0.5 font-medium">
                    Foto Unit SPPG
                </span>
            </div>
        </div>
    </div>
</template>
