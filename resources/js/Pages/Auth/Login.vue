<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardFooter from "@/Components/ui/CardFooter.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    Building2,
    LogIn,
    Lock,
    Mail,
    AlertCircle,
    Eye,
    EyeOff,
} from "lucide-vue-next";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Masuk ke Sistem - SIPEGE" />

    <div
        class="min-h-screen bg-gradient-to-b from-slate-50 via-slate-100/50 to-blue-50/30 flex flex-col justify-center items-center p-4 sm:p-6"
    >
        <!-- Header / Brand -->
        <div class="mb-6 text-center">
            <Link :href="'/'" class="inline-flex items-center gap-2.5 group">
                <div
                    class="h-11 w-11 rounded-xl bg-primary text-white flex items-center justify-center shadow-md shadow-primary/25 transition-transform group-hover:scale-105"
                >
                    <Building2 class="h-6 w-6" />
                </div>
                <div class="text-left">
                    <span
                        class="font-extrabold text-2xl tracking-tight text-slate-900 leading-tight block"
                        >SIPEGE</span
                    >
                    <span
                        class="text-xs text-slate-500 font-medium leading-none"
                        >Sistem Pengelolaan SPPG</span
                    >
                </div>
            </Link>
        </div>

        <!-- Login Card -->
        <Card className="w-full max-w-md shadow-lg border-slate-200 bg-white">
            <CardHeader className="space-y-1 text-center pb-4">
                <div class="flex justify-center mb-1">
                    <Badge variant="secondary" className="text-xs px-2.5 py-0.5"
                        >Autentikasi Akun</Badge
                    >
                </div>
                <CardTitle className="text-2xl font-bold text-slate-900"
                    >Selamat Datang</CardTitle
                >
                <CardDescription className="text-slate-500 text-sm">
                    Masukkan email dan password yang terdaftar
                </CardDescription>
            </CardHeader>

            <CardContent>
                <!-- Status Message -->
                <div
                    v-if="status"
                    class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium"
                >
                    {{ status }}
                </div>

                <!-- General Errors -->
                <div
                    v-if="Object.keys(form.errors).length > 0"
                    class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2"
                >
                    <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
                    <div>
                        <p class="font-semibold mb-0.5">
                            Terjadi kesalahan login:
                        </p>
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li
                                v-for="(err, field) in form.errors"
                                :key="field"
                            >
                                {{ err }}
                            </li>
                        </ul>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email Input -->
                    <div class="space-y-1.5">
                        <Label for="email" :required="true"
                            >Email</Label
                        >
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"
                            >
                                <Mail class="h-4 w-4" />
                            </div>
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                placeholder="nama@domain.com"
                                required
                                autofocus
                                autocomplete="username"
                                className="pl-9"
                            />
                        </div>
                        <p
                            v-if="form.errors.email"
                            class="text-xs text-destructive font-medium"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password" :required="true"
                                >Password</Label
                            >
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs text-primary font-medium hover:underline"
                            >
                                Lupa Password?
                            </Link>
                        </div>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"
                            >
                                <Lock class="h-4 w-4" />
                            </div>
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                                className="pl-9 pr-10"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                tabindex="-1"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password"
                            class="text-xs text-destructive font-medium"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between pt-1">
                        <label
                            class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer select-none"
                        >
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary/20"
                            />
                            <span>Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        className="w-full h-11 text-base font-semibold shadow-md shadow-primary/20 flex items-center justify-center gap-2 mt-2"
                        :disabled="form.processing"
                    >
                        <LogIn class="h-4 w-4" />
                        <span>{{
                            form.processing ? "Memproses..." : "Masuk Sekarang"
                        }}</span>
                    </Button>
                </form>
            </CardContent>

            <CardFooter
                class="flex flex-col border-t border-slate-100 pt-4 bg-slate-50/50 rounded-b-xl text-center"
            >
                <p class="text-xs text-slate-500">
                    Belum memiliki akun?
                    <Link
                        :href="route('register')"
                        class="text-primary font-semibold hover:underline ml-1"
                    >
                        Daftar Baru
                    </Link>
                </p>
            </CardFooter>
        </Card>

        <!-- Demo user info helper -->
        <div class="mt-6 text-center text-xs text-slate-400 max-w-sm">
            <p>
                Akun Demo Seeder:
                <span class="font-mono text-slate-600">admin@sppg.id</span> /
                <span class="font-mono text-slate-600">password123</span>
            </p>
        </div>
    </div>
</template>
