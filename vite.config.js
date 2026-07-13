import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    root: ".",
    plugins: [
        laravel({
            input: [
                "resources/css/site-variables.css",
                "resources/scss/site.scss",
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
