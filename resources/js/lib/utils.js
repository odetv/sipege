import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

/**
 * Format tanggal ke standar Bahasa Indonesia lengkap dengan nama hari.
 * Contoh hasil: "Selasa, 18 Februari 2026"
 *
 * @param {string|number|Date} dateValue
 * @param {string} fallback
 * @returns {string}
 */
export function formatTanggalIndo(dateValue, fallback = "-") {
    if (!dateValue) return fallback;
    const date = new Date(dateValue);
    if (isNaN(date.getTime())) return String(dateValue);

    return date.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}

/**
 * Format tanggal dan waktu ke standar Bahasa Indonesia lengkap dengan zona waktu WITA.
 * Contoh hasil: "Sabtu, 22 Agustus 2026, 14:30:45 WITA"
 *
 * @param {string|number|Date} dateValue
 * @param {string} fallback
 * @returns {string}
 */
export function formatTanggalWaktuIndo(dateValue, fallback = "-") {
    if (!dateValue) return fallback;
    const date = new Date(dateValue);
    if (isNaN(date.getTime())) return String(dateValue);

    const formattedDate = date.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });

    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");
    const seconds = String(date.getSeconds()).padStart(2, "0");

    return `${formattedDate}, ${hours}:${minutes}:${seconds} WITA`;
}
