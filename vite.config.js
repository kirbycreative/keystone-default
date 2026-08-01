import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "node:path";
import { spawn, spawnSync } from "node:child_process";

function juiceStyleWatcher() {
    const watcherPath = path.resolve("vendor/keystone/admin/resources/js/juice/styles/watch/watch.js");
    const viewsPath = path.resolve("resources/views");
    const outputPath = path.resolve("resources/css/auto.compiled.css");

    return {
        name: "juice-style-watcher",
        buildStart() {
            const result = spawnSync(process.execPath, [watcherPath, viewsPath, outputPath, "--once"], {
                stdio: "inherit"
            });

            if (result.status !== 0) {
                throw new Error("Juice style generation failed.");
            }
        },
        configureServer(server) {
            const watcher = spawn(process.execPath, [watcherPath, viewsPath, outputPath], {
                stdio: "inherit"
            });

            watcher.on("error", (error) => {
                server.config.logger.error(`Juice style watcher failed to start: ${error.message}`);
            });

            server.httpServer?.once("close", () => {
                if (!watcher.killed) watcher.kill("SIGTERM");
            });
        }
    };
}

export default defineConfig({
    root: ".",
    resolve: {
        preserveSymlinks: true
    },
    plugins: [
        juiceStyleWatcher(),
        laravel({
            input: [
                "resources/css/style-guide-variables.css",
                "resources/css/auto.compiled.css",
                "resources/scss/sections.scss",
                "vendor/keystone/admin/resources/scss/site/base.scss",
                "vendor/keystone/admin/resources/scss/admin/keystone.scss",
                "resources/js/app.js",
                "resources/images/logo/logo-long-2-lt.png"
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
