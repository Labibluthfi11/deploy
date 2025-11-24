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
        host: "0.0.0.0",
        port: 5173,
        origin: "http://192.168.100.89:5173", // IP WiFi kamu
        hmr: {
            host: "192.168.100.89", // penting untuk HMR
        },
    },
});
