class MediaLibraryPicker extends HTMLElement {
    connectedCallback() {
        if (this.dataset.ready === "true") return;
        this.dataset.ready = "true";
        this.render();
        this.querySelector("[data-media-open]")?.addEventListener("click", () => this.open());
    }

    render() {
        const label = this.getAttribute("button-label") || "Choose image";
        this.innerHTML = `
            <button type="button" class="btn btn--ghost" data-media-open>${label}</button>
            <dialog class="media-library" data-media-dialog>
                <div class="media-library__head">
                    <div>
                        <h2>Media library</h2>
                        <p class="muted">Choose an existing site image or upload a new one.</p>
                    </div>
                    <button type="button" class="btn btn--ghost" data-media-close>Close</button>
                </div>
                <label class="media-library__upload">
                    <span>Upload image</span>
                    <input type="file" accept="image/avif,image/gif,image/jpeg,image/png,image/svg+xml,image/webp" data-media-upload>
                </label>
                <p class="muted" data-media-status></p>
                <div class="media-library__grid" data-media-grid></div>
            </dialog>`;

        this.dialog = this.querySelector("[data-media-dialog]");
        this.grid = this.querySelector("[data-media-grid]");
        this.status = this.querySelector("[data-media-status]");
        this.querySelector("[data-media-close]")?.addEventListener("click", () => this.dialog.close());
        this.querySelector("[data-media-upload]")?.addEventListener("change", event => {
            const file = event.target.files?.[0];
            if (file) this.upload(file);
        });
    }

    async open() {
        this.dialog.showModal();
        await this.load();
    }

    async load() {
        this.setStatus("Loading images…");
        try {
            const response = await fetch(this.requiredAttribute("browse-url"), {
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            });
            if (!response.ok) throw new Error("The media library could not be loaded.");
            const payload = await response.json();
            this.renderAssets(payload.assets || []);
            this.setStatus(payload.assets?.length ? "" : "No site images have been uploaded yet.");
        } catch (error) {
            this.setStatus(error.message);
        }
    }

    async upload(file) {
        this.setStatus(`Uploading ${file.name}…`);
        const body = new FormData();
        body.append("file", file);

        try {
            const response = await fetch(this.requiredAttribute("upload-url"), {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf"]')?.content || "",
                },
                credentials: "same-origin",
                body,
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || "The image could not be uploaded.");
            }
            this.select(payload.asset);
        } catch (error) {
            this.setStatus(error.message);
        }
    }

    renderAssets(assets) {
        this.grid.replaceChildren();
        for (const asset of assets) {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "media-library__asset";
            button.title = asset.name;

            const image = document.createElement("img");
            image.src = asset.url;
            image.alt = asset.name;
            image.loading = "lazy";

            const name = document.createElement("span");
            name.textContent = asset.name;
            button.append(image, name);
            button.addEventListener("click", () => this.select(asset));
            this.grid.append(button);
        }
    }

    select(asset) {
        const target = document.querySelector(this.requiredAttribute("target"));
        if (!target) throw new Error("The media picker target was not found.");

        if (this.getAttribute("selection-mode") === "insert" && "selectionStart" in target) {
            const insertion = JSON.stringify(asset.url);
            target.setRangeText(insertion, target.selectionStart, target.selectionEnd, "end");
        } else {
            target.value = asset.url;
        }
        target.dispatchEvent(new Event("input", { bubbles: true }));
        target.dispatchEvent(new Event("change", { bubbles: true }));
        this.dispatchEvent(new CustomEvent("media-selected", { detail: asset, bubbles: true }));
        this.dialog.close();
    }

    setStatus(message) {
        this.status.textContent = message;
    }

    requiredAttribute(name) {
        const value = this.getAttribute(name);
        if (!value) throw new Error(`media-library-picker requires ${name}.`);
        return value;
    }
}

if (!customElements.get("media-library-picker")) {
    customElements.define("media-library-picker", MediaLibraryPicker);
}
