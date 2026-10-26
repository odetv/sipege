# SIPEGE Frontend & Backend Style Guide

## 1. Desain & Antarmuka (Vue 3 + Tailwind CSS)

### Palet Warna Standar
- **Primary / Brand**: Blue Palette (`#2563eb` - `blue-600` / `primary`)
- **Success / Terverifikasi / Siap Produksi**: Emerald Palette (`bg-emerald-50`, `text-emerald-700`, `border-emerald-300`)
- **Warning / Draft / Menunggu**: Amber Palette (`bg-amber-50`, `text-amber-700`, `border-amber-300`)
- **Error / Ditolak**: Rose Palette (`bg-rose-50`, `text-rose-700`, `border-rose-300`)
- **Manual Mode Indicator**: Amber/Orange Theme (`bg-amber-100 text-amber-900 border-amber-300`)
- **Otomatis/Sistem Indicator**: Blue Theme (`bg-blue-100 text-blue-900 border-blue-300`)

### Komponen & Tipografi
- Font Family: Inter / sans-serif modern.
- Border radius seragam: `rounded-xl` untuk kartu kecil/elemen kontrol, `rounded-2xl` untuk container utama.
- Shadow: Gunakan soft shadow (`shadow-xs`, `shadow-2xs`) untuk menghindari tampilan kaku.
- Stepper Pipeline: Harus selalu responsif, menyediakan layout horizontal 5 kolom pada desktop (`sm:block`) dan tampilan vertikal bersambung pada layar ponsel (`sm:hidden`).

---

## 2. Prinsip Arsitektur Vue 3 Inertia
- Gunakan `<script setup>` syntax untuk seluruh komponen Vue.
- Hindari inline mutations langsung pada props; manfaatkan `computed`, `ref`, atau event `emit`.
- Tautan navigasi internal wajib menggunakan `<Link>` dari `@inertiajs/vue3` dan helper `route(...)`.
- State tracking Work Order harus secara dinamis menyesuaikan tipe WO (`metode_wo === 'manual'` vs `metode_wo === 'sistem'`).
