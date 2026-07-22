<x-layouts.admin title="Getting Started">
    <div class="w:container maxw:lg onboarding">
        <ol class="stepper" id="stepper">
            <li class="stepper__item" data-indicator="1"><span class="stepper__dot"><span class="stepper__num">1</span><input-status class="stepper__status" fill></input-status></span><span class="stepper__label">Connect domain</span></li>
            <li class="stepper__item" data-indicator="2"><span class="stepper__dot"><span class="stepper__num">2</span><input-status class="stepper__status" fill></input-status></span><span class="stepper__label">Company &amp; brand</span></li>
            <li class="stepper__item" data-indicator="3"><span class="stepper__dot"><span class="stepper__num">3</span><input-status class="stepper__status" fill></input-status></span><span class="stepper__label">Inspiration</span></li>
            <li class="stepper__item" data-indicator="4"><span class="stepper__dot"><span class="stepper__num">4</span><input-status class="stepper__status" fill></input-status></span><span class="stepper__label">Business materials</span></li>
            <li class="stepper__item" data-indicator="5"><span class="stepper__dot"><span class="stepper__num">5</span><input-status class="stepper__status" fill></input-status></span><span class="stepper__label">Ready</span></li>
        </ol>

        {{-- Step 1: DNS --}}
        <section class="panel panel--lg step" data-step="1">
            <h2 class="margin:0">Point your domain to your new site</h2>
            <p class="lead margin:top:0o5">
                At your domain registrar, update the DNS for <strong>{{ $siteHost }}</strong> using <em>either</em>
                option below. We'll detect the change automatically &mdash; propagation can take a few minutes to a few
                hours.
            </p>

            <div class="dns-options margin:top:1">
                <div class="dns-option">
                    <p class="dns-option__title">Option A &mdash; Point an A record</p>
                    <p class="muted font-size:0o8 margin:top:0 margin:bottom:0o5">
                        Create an <strong>A record</strong> for your root domain pointing to this IP:
                    </p>
                    @if ($containerIp !== '')
                        <div class="dns-record">
                            <code class="dns-record__value">{{ $containerIp }}</code>
                            <button type="button" class="btn btn--ghost btn--sm dns-copy" data-copy="{{ $containerIp }}">Copy</button>
                        </div>
                    @else
                        <p class="muted">IP not configured yet &mdash; contact support.</p>
                    @endif
                </div>

                <div class="dns-option">
                    <p class="dns-option__title">Option B &mdash; Change your nameservers</p>
                    <p class="muted font-size:0o8 margin:top:0 margin:bottom:0o5">
                        Replace your domain's <strong>nameservers</strong> with ours:
                    </p>
                    @if (!empty($containerNameservers))
                        @foreach ($containerNameservers as $ns)
                            <div class="dns-record">
                                <code class="dns-record__value">{{ $ns }}</code>
                                <button type="button" class="btn btn--ghost btn--sm dns-copy" data-copy="{{ $ns }}">Copy</button>
                            </div>
                        @endforeach
                    @else
                        <p class="muted">Nameservers not configured yet &mdash; contact support.</p>
                    @endif
                </div>
            </div>

            <div class="dns-status margin:top:1" id="dns-status">
                <span class="dns-status__spinner" id="dns-spinner"></span>
                <span id="dns-status-text" class="muted">Checking your domain&hellip;</span>
            </div>

            <div class="margin:top:1 flex:row gap:0o5 items-wrap">
                <button type="button" id="dns-check-now" class="btn btn--ghost btn--sm">Check now</button>
            </div>
        </section>

        {{-- Step 2: Company & brand --}}
        <section class="panel panel--lg step hidden" data-step="2">
            <h2 class="margin:0">Tell us about your company</h2>
            <p class="lead margin:top:0o5">
                Add your company details and logo. We'll pull primary colors straight from your logo &mdash; tweak them
                if you'd like.
            </p>

            <div class="margin:top:1">
                {!! $brandForm->build() !!}
            </div>
        </section>

        {{-- Step 3: Inspiration --}}
        <section class="panel panel--lg step hidden" data-step="3">
            <h2 class="margin:0">Sites that inspire you</h2>
            <p class="lead margin:top:0o5">
                Add up to five websites you'd like yours modeled after. Don't have any in mind? Check out the top sites
                in your category below.
            </p>

            <form id="inspiration-form" class="margin:top:1 flex:column gap:0o5">
                @for ($i = 0; $i < 5; $i++)
                    <input type="url" name="inspiration_domains[]" class="admin-field"
                        placeholder="https://example.com"
                        value="{{ $onboarding->inspiration_domains[$i] ?? '' }}">
                @endfor

                <div class="margin:top:1">
                    <p class="fw-600 margin:bottom:0o5">Top sites in your category</p>
                    <div id="suggested-sites" class="suggested-sites">
                        <p class="muted" id="suggested-loading">Loading suggestions&hellip;</p>
                    </div>
                </div>

                <div class="margin:top:1">
                    <button type="submit" class="btn btn--primary">Save &amp; continue</button>
                </div>
            </form>
        </section>

        {{-- Step 4: Business materials --}}
        <section class="panel panel--lg step hidden" data-step="4">
            <h2 class="margin:0">Add your business materials</h2>
            <p class="lead margin:top:0o5">
                Upload menus, promotions, advertisements, photos, documents, or other source material. We need these
                files before we can generate the complete style guide and site layout.
            </p>

            <div id="onboarding-asset-dropzone" class="asset-dropzone margin:top:1">
                <p class="muted margin:0">Drag &amp; drop files here, or click to choose.</p>
                <input type="file" id="onboarding-asset-input" class="hidden" multiple
                    accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.docx,.xls,.xlsx,.csv,.txt,.rtf">
            </div>
            <div id="onboarding-asset-list" class="asset-dropzone__list">
                @foreach ($assets as $asset)
                    <div class="asset-dropzone__item">
                        <span>{{ $asset->original_filename }}</span>
                        <span class="muted">{{ $asset->ingestion_status }}</span>
                    </div>
                @endforeach
            </div>

            <div class="margin:top:1">
                <button type="button" class="btn btn--primary" id="materials-continue" @disabled($assets->isEmpty())>
                    Save &amp; continue
                </button>
                <p class="muted font-size:0o8 margin:top:0o5 margin:bottom:0" id="materials-requirement" @if ($assets->isNotEmpty()) hidden @endif>
                    Upload at least one file to continue.
                </p>
            </div>
        </section>

        {{-- Step 5: Submit complete brief --}}
        <section class="panel panel--lg step hidden text:center" data-step="5">
            <h2 class="margin:0">Your complete brief is ready</h2>
            <p class="lead margin:x:auto margin:top:0o5">
                Your company details, inspiration, and business materials are ready. Submit the complete brief to begin
                generating your style guide and site layout.
            </p>
            <form method="POST" action="{{ route('admin.onboarding.complete') }}" class="margin:top:1">
                @csrf
                <button type="submit" class="btn btn--primary">Submit brief &amp; start building</button>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cfg = (window.app && window.app.data && window.app.data.onboarding) || {};
            const routes = cfg.routes || {};
            const csrf = document.querySelector('meta[name="csrf"]')?.getAttribute('content') || '';
            let current = cfg.step || 1;

            const steps = Array.from(document.querySelectorAll('.step'));
            const indicators = Array.from(document.querySelectorAll('[data-indicator]'));

            function showStep(n) {
                current = n;
                steps.forEach(s => s.classList.toggle('hidden', Number(s.dataset.step) !== n));
                indicators.forEach(i => {
                    const step = Number(i.dataset.indicator);
                    const done = step < n;
                    i.classList.toggle('is-active', step === n);
                    i.classList.toggle('is-done', done);
                    const status = i.querySelector('input-status');
                    if (status) {
                        if (done) status.setAttribute('state', 'success');
                        else status.removeAttribute('state');
                    }
                });
                if (n === 3) loadSuggestedSites();
            }

            indicators.forEach(i => i.addEventListener('click', () => {
                const step = Number(i.dataset.indicator);
                if (step < current) showStep(step); // only revisit completed steps
            }));

            // ---- Step 1: DNS polling -------------------------------------------------
            const dnsText = document.getElementById('dns-status-text');
            const dnsSpinner = document.getElementById('dns-spinner');
            let dnsTimer = null;

            async function checkDns() {
                try {
                    const res = await fetch(routes.checkDns, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.verified) {
                        if (dnsTimer) clearInterval(dnsTimer);
                        dnsSpinner?.classList.add('hidden');
                        dnsText.textContent = 'Domain connected. Continuing\u2026';
                        setTimeout(() => showStep(2), 900);
                    } else {
                        dnsText.textContent = 'Waiting for DNS to point here (' + (data.host || 'your domain') + ')\u2026';
                    }
                } catch (e) {
                    dnsText.textContent = 'Could not check DNS just now. Retrying\u2026';
                }
            }

            document.getElementById('dns-check-now')?.addEventListener('click', checkDns);

            document.querySelectorAll('.dns-copy').forEach(btn => btn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(btn.dataset.copy);
                    const original = btn.textContent;
                    btn.textContent = 'Copied';
                    setTimeout(() => { btn.textContent = original; }, 1500);
                } catch (e) { /* clipboard unavailable */ }
            }));

            // ---- Step 2: brand form, logo dropzone, color extraction -----------------
            const dropzone = document.getElementById('logo-dropzone');
            const logoInput = document.getElementById('logo-input');
            const logoPreview = document.getElementById('logo-preview');
            const logoPlaceholder = document.getElementById('logo-placeholder');

            if (dropzone && logoInput) {
                dropzone.addEventListener('click', () => logoInput.click());
                ['dragover', 'dragenter'].forEach(ev => dropzone.addEventListener(ev, e => {
                    e.preventDefault(); dropzone.classList.add('is-dragging');
                }));
                ['dragleave', 'drop'].forEach(ev => dropzone.addEventListener(ev, e => {
                    e.preventDefault(); dropzone.classList.remove('is-dragging');
                }));
                dropzone.addEventListener('drop', e => {
                    if (e.dataTransfer.files.length) {
                        logoInput.files = e.dataTransfer.files;
                        handleLogo(e.dataTransfer.files[0]);
                    }
                });
                logoInput.addEventListener('change', () => {
                    if (logoInput.files.length) handleLogo(logoInput.files[0]);
                });
            }

            function handleLogo(file) {
                const url = URL.createObjectURL(file);
                const img = new Image();
                img.onload = () => {
                    logoPreview.src = url;
                    logoPreview.classList.remove('hidden');
                    logoPlaceholder?.classList.add('hidden');
                    if (file.type !== 'image/svg+xml') {
                        const colors = extractColors(img, 4);
                        if (colors.length) populateSwatches(colors);
                    }
                };
                img.src = url;
            }

            function extractColors(img, count) {
                const canvas = document.getElementById('logo-canvas');
                const ctx = canvas.getContext('2d');
                const w = canvas.width, h = canvas.height;
                ctx.clearRect(0, 0, w, h);
                ctx.drawImage(img, 0, 0, w, h);
                let data;
                try { data = ctx.getImageData(0, 0, w, h).data; } catch (e) { return []; }
                const buckets = {};
                for (let i = 0; i < data.length; i += 4) {
                    if (data[i + 3] < 128) continue;
                    const r = data[i], g = data[i + 1], b = data[i + 2];
                    const max = Math.max(r, g, b), min = Math.min(r, g, b);
                    if (max > 245 && min > 245) continue; // near-white
                    if (max < 15) continue;               // near-black
                    const key = (Math.round(r / 24) * 24) + ',' + (Math.round(g / 24) * 24) + ',' + (Math.round(b / 24) * 24);
                    buckets[key] = (buckets[key] || 0) + 1;
                }
                return Object.entries(buckets)
                    .sort((a, b) => b[1] - a[1])
                    .slice(0, count)
                    .map(([k]) => { const [r, g, b] = k.split(',').map(Number); return rgbToHex(r, g, b); });
            }

            function rgbToHex(r, g, b) {
                return '#' + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('');
            }

            const colorsWrap = document.getElementById('primary-colors');

            function makeSwatch(hex) {
                const span = document.createElement('span');
                span.className = 'color-swatch';
                span.innerHTML = '<input type="color" name="primary_colors[]" value="' + hex + '">' +
                    '<button type="button" class="color-remove" aria-label="Remove color">&times;</button>';
                span.querySelector('.color-remove').addEventListener('click', () => span.remove());
                return span;
            }

            function populateSwatches(colors) {
                if (!colorsWrap) return;
                colorsWrap.innerHTML = '';
                colors.forEach(c => colorsWrap.appendChild(makeSwatch(c)));
            }

            colorsWrap?.querySelectorAll('.color-remove').forEach(btn =>
                btn.addEventListener('click', e => e.target.closest('.color-swatch').remove()));

            document.getElementById('add-color')?.addEventListener('click', () => {
                colorsWrap?.appendChild(makeSwatch('#888888'));
            });

            const brandForm = document.getElementById('brand-form');
            brandForm?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = brandForm.querySelector('[type="submit"]');
                if (btn) { btn.disabled = true; btn.textContent = 'Saving\u2026'; }
                try {
                    const res = await fetch(routes.brand, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: new FormData(brandForm),
                    });
                    if (!res.ok) throw new Error('Save failed');
                    showStep(3);
                } catch (err) {
                    alert('We could not save your details. Please check the form and try again.');
                } finally {
                    if (btn) { btn.disabled = false; btn.textContent = 'Save & continue'; }
                }
            });

            // ---- Step 3: suggestions + inspiration -----------------------------------
            let suggestionsLoaded = false;

            async function loadSuggestedSites() {
                if (suggestionsLoaded) return;
                suggestionsLoaded = true;
                const wrap = document.getElementById('suggested-sites');
                try {
                    const res = await fetch(routes.suggestSites, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('Suggestion request failed');
                    const data = await res.json();
                    if (!data.sites || !data.sites.length) {
                        wrap.innerHTML = '<p class="muted">No suggestions available right now.</p>';
                        return;
                    }
                    wrap.innerHTML = data.sites.map(s =>
                        '<div class="suggested-site">' +
                        '<div><a class="link-accent" href="https://' + s.domain + '" target="_blank" rel="noopener">' + s.name + '</a>' +
                        '<p class="muted font-size:0o8 margin:0">' + (s.reason || '') + '</p></div>' +
                        '<button type="button" class="btn btn--ghost btn--sm" data-domain="' + s.domain + '">Use</button>' +
                        '</div>'
                    ).join('');
                    wrap.querySelectorAll('[data-domain]').forEach(btn =>
                        btn.addEventListener('click', () => addInspiration(btn.dataset.domain)));
                } catch (e) {
                    suggestionsLoaded = false;
                    wrap.innerHTML = '<p class="muted">Could not load suggestions. ' +
                        '<button type="button" class="btn btn--ghost btn--sm" id="retry-suggestions">Try again</button></p>';
                    document.getElementById('retry-suggestions')?.addEventListener('click', loadSuggestedSites);
                }
            }

            function addInspiration(domain) {
                const inputs = Array.from(document.querySelectorAll('#inspiration-form input[name="inspiration_domains[]"]'));
                const empty = inputs.find(i => !i.value.trim());
                if (empty) empty.value = 'https://' + domain;
            }

            const inspirationForm = document.getElementById('inspiration-form');
            inspirationForm?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = inspirationForm.querySelector('[type="submit"]');
                if (btn) { btn.disabled = true; btn.textContent = 'Saving\u2026'; }
                try {
                    const res = await fetch(routes.inspiration, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: new FormData(inspirationForm),
                    });
                    if (!res.ok) throw new Error('Save failed');
                    showStep(4);
                } catch (err) {
                    alert('We could not save your inspiration sites. Please try again.');
                } finally {
                    if (btn) { btn.disabled = false; btn.textContent = 'Save & continue'; }
                }
            });

            // ---- Step 4: business materials ------------------------------------------
            const assetZone = document.getElementById('onboarding-asset-dropzone');
            const assetInput = document.getElementById('onboarding-asset-input');
            const assetList = document.getElementById('onboarding-asset-list');
            const materialsContinue = document.getElementById('materials-continue');
            const materialsRequirement = document.getElementById('materials-requirement');
            let assetCount = Number(cfg.assetCount || 0);

            assetZone?.addEventListener('click', () => assetInput?.click());
            ['dragover', 'dragenter'].forEach(eventName => assetZone?.addEventListener(eventName, event => {
                event.preventDefault();
                assetZone.classList.add('is-dragging');
            }));
            ['dragleave', 'drop'].forEach(eventName => assetZone?.addEventListener(eventName, event => {
                event.preventDefault();
                assetZone.classList.remove('is-dragging');
            }));
            assetZone?.addEventListener('drop', event => uploadMaterialFiles(event.dataTransfer.files));
            assetInput?.addEventListener('change', () => uploadMaterialFiles(assetInput.files));

            function uploadMaterialFiles(files) {
                Array.from(files || []).forEach(uploadMaterialFile);
            }

            async function uploadMaterialFile(file) {
                const row = document.createElement('div');
                row.className = 'asset-dropzone__item';
                row.innerHTML = '<span></span><span class="muted">Uploading\u2026</span>';
                row.querySelector('span').textContent = file.name;
                assetList.prepend(row);

                const body = new FormData();
                body.append('file', file);

                try {
                    const res = await fetch(routes.assetUpload, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body,
                    });
                    if (!res.ok) throw new Error('Upload failed');
                    const data = await res.json();
                    row.querySelector('span:last-child').textContent = data.asset.status || 'Queued';
                    assetCount += 1;
                    materialsContinue.disabled = false;
                    materialsRequirement.hidden = true;
                } catch (error) {
                    row.classList.add('is-error');
                    row.querySelector('span:last-child').textContent = 'Failed';
                }
            }

            materialsContinue?.addEventListener('click', async () => {
                if (assetCount < 1) return;
                materialsContinue.disabled = true;
                try {
                    const res = await fetch(routes.materials, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                    if (!res.ok) throw new Error('Save failed');
                    showStep(5);
                } catch (error) {
                    alert('We could not confirm your business materials. Please try again.');
                } finally {
                    materialsContinue.disabled = false;
                }
            });

            // ---- Boot ----------------------------------------------------------------
            showStep(current);
            if (current === 1) {
                checkDns();
                dnsTimer = setInterval(checkDns, 8000);
            }
        });
    </script>
</x-layouts.admin>
