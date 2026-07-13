import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    root: ".",
    plugins: [
        laravel({
            input: [
                "resources/css/style-guide-variables.css",
                "resources/scss/base.scss",
                "resources/css/admin.css",
                "resources/js/app.js"
            ],
            refresh: true
        })
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"]
        }
    }
});
