import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
    ],
    server: {
        host: "0.0.0.0", // biar bisa diakses dari device lain
        port: 5173,
        origin: "http://192.168.1.11:5173", // <-- ganti ke IP kamu
        hmr: {
            host: "192.168.1.11", // Hot Module Replacement
        },
    },
});
