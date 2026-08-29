<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Label from "@/Components/ui/Label.vue";
import { User, AlertCircle } from "lucide-vue-next";

defineProps({
    form: {
        type: Object,
        required: true,
    },
    rawTeleponKepala: {
        type: String,
        default: "",
    },
    rawTeleponPIC: {
        type: String,
        default: "",
    },
    getFieldError: {
        type: Function,
        required: true,
    },
    clearFieldError: {
        type: Function,
        required: true,
    },
});

defineEmits(["update:rawTeleponKepala", "update:rawTeleponPIC"]);
</script>

<template>
    <!-- 2. KONTAK PENANGGUNG JAWAB (KS & PIC) -->
    <Card className="bg-white border-slate-200/80 shadow-xs">
        <CardHeader
            className="border-b border-slate-100 p-5 bg-slate-50/50"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="h-8 w-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"
                >
                    <User class="h-4 w-4" />
                </div>
                <div>
                    <CardTitle
                        className="text-base font-bold text-slate-900"
                    >
                        2. Kontak Satuan
                    </CardTitle>
                    <CardDescription
                        className="text-xs text-slate-500 mt-0.5"
                    >
                        Data narahubung resmi Kepala Satuan dan PIC.
                    </CardDescription>
                </div>
            </div>
        </CardHeader>
        <CardContent className="p-5 sm:p-6 space-y-6">
            <!-- KS Section -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span
                        class="text-xs font-bold uppercase tracking-wider text-slate-700"
                    >
                        A. Data Kepala / Pimpinan Satuan
                    </span>
                    <div
                        class="flex-1 border-t border-slate-200"
                    ></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <Label
                            for="nama_kepala"
                            class="text-xs font-semibold text-slate-700"
                        >
                            Nama Kepala Satuan
                            <span class="text-rose-500">*</span>
                        </Label>
                        <input
                            id="nama_kepala"
                            v-model="form.nama_kepala"
                            @input="clearFieldError('nama_kepala')"
                            type="text"
                            placeholder="Nama Lengkap Kepala Satuan"
                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                            :class="{
                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                    getFieldError('nama_kepala'),
                            }"
                            required
                        />
                        <p
                            v-if="getFieldError('nama_kepala')"
                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                            />
                            <span>{{
                                getFieldError("nama_kepala")
                            }}</span>
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="email_kepala"
                            class="text-xs font-semibold text-slate-700"
                        >
                            Email Kepala Satuan
                            <span class="text-rose-500">*</span>
                        </Label>
                        <input
                            id="email_kepala"
                            v-model="form.email_kepala"
                            @input="clearFieldError('email_kepala')"
                            type="email"
                            placeholder="kepala@domain.com"
                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                            :class="{
                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                    getFieldError('email_kepala'),
                            }"
                            required
                        />
                        <p
                            v-if="getFieldError('email_kepala')"
                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                            />
                            <span>{{
                                getFieldError("email_kepala")
                            }}</span>
                        </p>
                    </div>

                    <!-- WhatsApp KS (Format Register +62) -->
                    <div class="space-y-1.5">
                        <Label
                            for="telepon_kepala"
                            class="text-xs font-semibold text-slate-700"
                        >
                            Nomor Telp Kepala Satuan
                            <span class="text-rose-500">*</span>
                        </Label>
                        <div class="flex rounded-lg shadow-2xs">
                            <span
                                class="inline-flex items-center px-3.5 rounded-l-lg border border-r-0 border-slate-200 bg-slate-100 text-slate-700 font-bold text-xs select-none"
                            >
                                +62
                            </span>
                            <input
                                id="telepon_kepala"
                                :value="rawTeleponKepala"
                                @input="$emit('update:rawTeleponKepala', $event.target.value)"
                                type="text"
                                placeholder="81234567890 (tanpa 0 di depan)"
                                class="flex-1 h-11 px-3 text-xs rounded-r-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                :class="{
                                    'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                        getFieldError(
                                            'telepon_kepala',
                                        ),
                                }"
                                required
                            />
                        </div>
                        <p
                            v-if="getFieldError('telepon_kepala')"
                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                            />
                            <span>{{
                                getFieldError("telepon_kepala")
                            }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- PIC Section -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span
                        class="text-xs font-bold uppercase tracking-wider text-slate-700"
                    >
                        B. Data Person In Charge (PIC) / Narahubung
                    </span>
                    <div
                        class="flex-1 border-t border-slate-200"
                    ></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <Label
                            for="nama_pic"
                            class="text-xs font-semibold text-slate-700"
                        >
                            Nama PIC
                            <span class="text-rose-500">*</span>
                        </Label>
                        <input
                            id="nama_pic"
                            v-model="form.nama_pic"
                            @input="clearFieldError('nama_pic')"
                            type="text"
                            placeholder="Nama Lengkap PIC"
                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                            :class="{
                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                    getFieldError('nama_pic'),
                            }"
                            required
                        />
                        <p
                            v-if="getFieldError('nama_pic')"
                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                            />
                            <span>{{
                                getFieldError("nama_pic")
                            }}</span>
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="email_pic"
                            class="text-xs font-semibold text-slate-700"
                        >
                            Email PIC
                            <span class="text-rose-500">*</span>
                        </Label>
                        <input
                            id="email_pic"
                            v-model="form.email_pic"
                            @input="clearFieldError('email_pic')"
                            type="email"
                            placeholder="pic@domain.com"
                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                            :class="{
                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                    getFieldError('email_pic'),
                            }"
                            required
                        />
                        <p
                            v-if="getFieldError('email_pic')"
                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                            />
                            <span>{{
                                getFieldError("email_pic")
                            }}</span>
                        </p>
                    </div>

                    <!-- WhatsApp PIC (Format Register +62) -->
                    <div class="space-y-1.5">
                        <Label
                            for="telepon_pic"
                            class="text-xs font-semibold text-slate-700"
                        >
                            Nomor Telp PIC
                            <span class="text-rose-500">*</span>
                        </Label>
                        <div class="flex rounded-lg shadow-2xs">
                            <span
                                class="inline-flex items-center px-3.5 rounded-l-lg border border-r-0 border-slate-200 bg-slate-100 text-slate-700 font-bold text-xs select-none"
                            >
                                +62
                            </span>
                            <input
                                id="telepon_pic"
                                :value="rawTeleponPIC"
                                @input="$emit('update:rawTeleponPIC', $event.target.value)"
                                type="text"
                                placeholder="81234567890 (tanpa 0 di depan)"
                                class="flex-1 h-11 px-3 text-xs rounded-r-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                :class="{
                                    'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                        getFieldError(
                                            'telepon_pic',
                                        ),
                                }"
                                required
                            />
                        </div>
                        <p
                            v-if="getFieldError('telepon_pic')"
                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                            />
                            <span>{{
                                getFieldError("telepon_pic")
                            }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
