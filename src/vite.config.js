import fs from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// mkcertで生成したローカル証明書（存在しない環境ではHTTPで起動）
const certPath = '../data/certs/cert.pem';
const keyPath = '../data/certs/key.pem';
const useHttps = fs.existsSync(certPath) && fs.existsSync(keyPath);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: 'kabu-dash.com',
        ...(useHttps && {
            https: {
                cert: fs.readFileSync(certPath),
                key: fs.readFileSync(keyPath),
            },
        }),
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
