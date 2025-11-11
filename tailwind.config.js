const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    darkMode: 'class', // INI YANG PENTING! Tanpa ini dark mode ga akan jalan

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                latte: {
                    DEFAULT: '#F5E6CC',
                    50: '#FFFDF9',
                    100: '#FAF7F0',
                    200: '#F5EEDF',
                    300: '#F0E5D0',
                    400: '#EBCDC7',
                    500: '#E6C4BD',
                    600: '#DAB8AE',
                    700: '#CEAC9F',
                    800: '#A4897E',
                    900: '#7B665E',
                    950: '#4D403A',
                },
                antiqueCream: {
                    DEFAULT: '#EDE6BE',
                    50: '#FDFCF2',
                    100: '#FBF9ED',
                    200: '#F6F0D9',
                    300: '#F0E7C5',
                    400: '#EADEC2',
                    500: '#E3D5B0',
                    600: '#C7B998',
                    700: '#AB9D81',
                    800: '#6E6652',
                    900: '#38332A',
                    950: '#1F1C16',
                },
                dustyLatte: {
                    DEFAULT: '#D4C8B5',
                    50: '#F9F7F4',
                    100: '#F4F0EB',
                    200: '#EAE5DB',
                    300: '#E0DBCC',
                    400: '#D4C8B5',
                    500: '#C3B5A1',
                    600: '#A1917F',
                    700: '#807264',
                    800: '#5F534C',
                    900: '#3D3631',
                    950: '#1F1C1A',
                },
            }
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
