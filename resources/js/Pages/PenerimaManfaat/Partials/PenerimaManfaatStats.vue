<script setup>
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import { School, Building2, Utensils, User, Users } from "lucide-vue-next";

defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_kelompok: 0,
            total_laki_laki: 0,
            total_perempuan: 0,
            total_penerima: 0,
            total_porsi_kecil: 0,
            total_porsi_besar: 0,
        }),
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    totalSekolahCount: {
        type: Number,
        default: 0,
    },
    totalPosyanduCount: {
        type: Number,
        default: 0,
    },
    categoryStats: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <!-- ================= 5 RINGKASAN UTAMA METRIC CARDS ================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
        <!-- 1. Total Kelompok -->
        <Card
            className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
        >
            <CardContent
                className="p-4 sm:p-5 flex items-center justify-between gap-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-500 truncate">
                        Total Kelompok
                    </p>
                    <div class="flex items-baseline gap-1.5 sm:gap-2 mt-1 flex-wrap">
                        <span class="text-lg sm:text-xl font-bold text-blue-700">
                            {{ totalSekolahCount.toLocaleString("id-ID") }}
                            <span
                                class="text-[10px] font-sans font-bold text-blue-600"
                                >Sekolah</span
                            >
                        </span>
                        <span class="text-slate-300 font-light">/</span>
                        <span class="text-lg sm:text-xl font-bold text-emerald-700">
                            {{ totalPosyanduCount.toLocaleString("id-ID") }}
                            <span
                                class="text-[10px] font-sans font-bold text-emerald-600"
                                >Posyandu</span
                            >
                        </span>
                    </div>
                    <p
                        class="text-[11px] text-slate-400 mt-0.5 truncate"
                    >
                        Total: <strong class="text-slate-700">{{ (stats.total_kelompok || (kelompokList ? kelompokList.length : 0)).toLocaleString("id-ID") }}</strong> Satuan PM
                    </p>
                </div>
                <div
                    class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                >
                    <School class="h-5 w-5" />
                </div>
            </CardContent>
        </Card>

        <!-- 2. PM Sekolah & Posyandu -->
        <Card
            className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
        >
            <CardContent
                className="p-4 sm:p-5 flex items-center justify-between gap-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-500 truncate">
                        PM Sekolah & Posyandu
                    </p>
                    <div class="flex items-baseline gap-1.5 sm:gap-2 mt-1 flex-wrap">
                        <span class="text-lg sm:text-xl font-bold text-blue-700">
                            {{ categoryStats.sekolahPM.total.toLocaleString("id-ID") }}
                            <span
                                class="text-[10px] font-sans font-bold text-blue-600"
                                >Sekolah</span
                            >
                        </span>
                        <span class="text-slate-300 font-light">/</span>
                        <span class="text-lg sm:text-xl font-bold text-emerald-700">
                            {{ categoryStats.posyanduPM.total.toLocaleString("id-ID") }}
                            <span
                                class="text-[10px] font-sans font-bold text-emerald-600"
                                >Posyandu</span
                            >
                        </span>
                    </div>
                    <p
                        class="text-[11px] text-slate-400 mt-0.5 truncate"
                    >
                        Total: <strong class="text-slate-700">{{ (categoryStats.sekolahPM.total + categoryStats.posyanduPM.total).toLocaleString("id-ID") }}</strong> Porsi
                    </p>
                </div>
                <div
                    class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0 shadow-2xs"
                >
                    <Building2 class="h-5 w-5" />
                </div>
            </CardContent>
        </Card>

        <!-- 3. Rincian Porsi -->
        <Card
            className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
        >
            <CardContent
                className="p-4 sm:p-5 flex items-center justify-between gap-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-500 truncate">
                        Rincian Porsi
                    </p>
                    <div class="flex items-baseline gap-1.5 sm:gap-2 mt-1 flex-wrap">
                        <span class="text-lg sm:text-xl font-bold text-amber-700">
                            {{
                                (
                                    stats.total_porsi_kecil || 0
                                ).toLocaleString("id-ID")
                            }}
                            <span
                                class="text-[10px] font-sans font-bold text-amber-600"
                                >Kecil</span
                            >
                        </span>
                        <span class="text-slate-300 font-light">/</span>
                        <span class="text-lg sm:text-xl font-bold text-blue-700">
                            {{
                                (
                                    stats.total_porsi_besar || 0
                                ).toLocaleString("id-ID")
                            }}
                            <span
                                class="text-[10px] font-sans font-bold text-blue-600"
                                >Besar</span
                            >
                        </span>
                    </div>
                    <p
                        class="text-[11px] text-slate-400 mt-0.5 truncate"
                    >
                        Porsi Kecil & Porsi Besar
                    </p>
                </div>
                <div
                    class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                >
                    <Utensils class="h-5 w-5" />
                </div>
            </CardContent>
        </Card>

        <!-- 4. Rincian Gender -->
        <Card
            className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
        >
            <CardContent
                className="p-4 sm:p-5 flex items-center justify-between gap-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-500 truncate">
                        Rincian Gender
                    </p>
                    <div class="flex items-baseline gap-1.5 sm:gap-2 mt-1 flex-wrap">
                        <span class="text-lg sm:text-xl font-bold text-sky-700">
                            {{
                                (
                                    stats.total_laki_laki || 0
                                ).toLocaleString("id-ID")
                            }}
                            <span
                                class="text-[10px] font-sans font-bold text-sky-600"
                                >L</span
                            >
                        </span>
                        <span class="text-slate-300 font-light">/</span>
                        <span class="text-lg sm:text-xl font-bold text-pink-700">
                            {{
                                (
                                    stats.total_perempuan || 0
                                ).toLocaleString("id-ID")
                            }}
                            <span
                                class="text-[10px] font-sans font-bold text-pink-600"
                                >P</span
                            >
                        </span>
                    </div>
                    <p
                        class="text-[11px] text-slate-400 mt-0.5 truncate"
                    >
                        Laki-Laki & Perempuan
                    </p>
                </div>
                <div
                    class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                >
                    <User class="h-5 w-5" />
                </div>
            </CardContent>
        </Card>

        <!-- 5. Total Penerima -->
        <Card
            className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
        >
            <CardContent
                className="p-4 sm:p-5 flex items-center justify-between gap-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-500 truncate">
                        Total Penerima Manfaat
                    </p>
                    <h3
                        class="text-xl sm:text-2xl font-bold text-primary mt-1 truncate"
                    >
                        {{
                            (stats.total_penerima || 0).toLocaleString("id-ID")
                        }}
                    </h3>
                    <p
                        class="text-[11px] text-slate-400 mt-0.5 truncate"
                    >
                        Porsi Penerima Manfaat
                    </p>
                </div>
                <div
                    class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                >
                    <Users class="h-5 w-5" />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
