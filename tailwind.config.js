import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/**/*.php', // [SISTEM KUA] accessor status_color di Model butuh di-scan
    ],

    theme: {
        extend: {
            // [SISTEM KUA] Palet identitas.
            //
            // `kua`  — hijau institusional, mengikuti warna Kementerian Agama.
            //          Dipakai untuk navigasi, tombol utama, dan penanda aktif.
            // `emas` — aksen. SENGAJA dipakai tipis: garis rambut, penanda, angka
            //          penting. Kalau dipakai sebagai latar luas, kesannya jatuh
            //          jadi norak, bukan mewah.
            //
            // Netralnya pakai `stone` bawaan Tailwind (hangat), BUKAN `gray`
            // (dingin/kebiruan) — ini yang paling menentukan kesan "mahal".
            colors: {
                kua: {
                    50: '#f2f8f5',
                    100: '#dcece4',
                    200: '#bbd9ca',
                    300: '#8fbfa9',
                    400: '#5d9f84',
                    500: '#3c8168',
                    600: '#2b6752',
                    700: '#235343',
                    800: '#1e4237',
                    900: '#1a372f',
                    950: '#0d1f1a',
                },
                emas: {
                    50: '#fbf8ef',
                    100: '#f6eed6',
                    200: '#ecdaad',
                    300: '#dfc07c',
                    400: '#d3a654',
                    500: '#c68f3c',
                    600: '#ad7231',
                    700: '#8f552b',
                    800: '#76452a',
                    900: '#623a26',
                },
            },

            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                // Serif hanya untuk judul & angka besar — jangan untuk teks panjang.
                display: ['Lora', ...defaultTheme.fontFamily.serif],
            },

            // Bayangan berlapis & sangat lembut. Bayangan pekat berkesan murah;
            // yang mahal justru nyaris tidak terlihat.
            boxShadow: {
                kartu: '0 1px 2px 0 rgb(28 25 23 / 0.04), 0 1px 3px 0 rgb(28 25 23 / 0.06)',
                naik: '0 4px 6px -2px rgb(28 25 23 / 0.05), 0 12px 20px -4px rgb(28 25 23 / 0.10)',
            },
        },
    },

    plugins: [forms],
};
