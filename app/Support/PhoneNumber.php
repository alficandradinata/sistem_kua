<?php

namespace App\Support;

/**
 * [SISTEM KUA] Normalisasi nomor HP Indonesia ke format WhatsApp (62…).
 *
 * Warga menuliskan nomor dengan bentuk yang berbeda-beda (0812…, +62 812…,
 * 62-812…). Nomor pengirim dari Cloud API selalu "62812…", jadi semua nomor
 * disimpan dalam bentuk itu supaya bisa dicocokkan.
 */
class PhoneNumber
{
    /**
     * Ubah ke format 62… ; kembalikan null bila jelas bukan nomor.
     */
    public static function normalize(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        // Sisakan angka saja (buang spasi, strip, kurung, tanda plus).
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($digits === '') {
            return null;
        }

        // 0812… → 62812…
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            // 812… (tanpa awalan apa pun) → 62812…
            $digits = '62'.$digits;
        }

        // Terlalu pendek untuk sebuah nomor HP.
        return strlen($digits) >= 9 ? $digits : null;
    }

    /**
     * Tampilan enak dibaca: 62812xxxx → +62 812-xxxx-xxxx.
     */
    public static function format(?string $number): string
    {
        $normal = self::normalize($number);

        if ($normal === null) {
            return '-';
        }

        // Kebiasaan penulisan Indonesia: kode operator 3 digit, sisanya per 4.
        $sisa = substr($normal, 2);
        $operator = substr($sisa, 0, 3);
        $ekor = rtrim(chunk_split(substr($sisa, 3), 4, '-'), '-');

        return '+62 '.$operator.($ekor !== '' ? '-'.$ekor : '');
    }

    /**
     * Tautan wa.me berisi pesan siap kirim.
     */
    public static function waMeLink(?string $number, string $message = ''): ?string
    {
        $normal = self::normalize($number);

        if ($normal === null) {
            return null;
        }

        return 'https://wa.me/'.$normal
            .($message !== '' ? '?text='.rawurlencode($message) : '');
    }
}
