<x-layouts.admin :title="'Template: ' . $name">
    <div class="w:container maxw:xl">
        <div class="page-head">
            <div>
                <a href="{{ route('admin.templates.index') }}" class="link-accent font-size:0o9">&larr; Back to templates</a>
                <h1 class="margin:top:0o5">{{ $name }}</h1>
                <p class="muted margin:0">{{ $relativePath }}</p>
            </div>
            <a href="{{ route('admin.templates.index') }}" class="btn btn--ghost btn--sm">List view</a>
        </div>

        <div class="panel margin:top:1">
            <div class="flex:row items-wrap gap:1">
                <span class="badge">{{ Str::contains($relativePath, '/') ? explode('/', $relativePath)[0] : $relativePath }}</span>
                <span class="muted font-size:0o9">{{ strlen($content) }} characters</span>
                <span class="muted font-size:0o9">{{ substr_count($content, "\n") + 1 }} lines</span>
            </div>
        </div>

        <div class="code-viewer margin:top:1">
            <div class="code-viewer__toolbar">
                <div class="flex:row align:center gap:0o5">
                    <label for="theme">Theme:</label>
                    <select id="theme" class="admin-field admin-select" style="width:auto;padding:.4rem .6rem;">
                        <option value="github">GitHub Light</option>
                        <option value="github-dark">GitHub Dark</option>
                        <option value="monokai">Monokai</option>
                        <option value="dracula">Dracula</option>
                        <option value="vs">Visual Studio</option>
                        <option value="vs-dark">VS Code Dark</option>
                    </select>
                </div>

                <div class="flex:row align:center gap:1 margin:left:auto">
                    <label class="flex:row align:center gap:0o5 muted">
                        <input type="checkbox" id="wrapLines"> Wrap lines
                    </label>
                    <label class="flex:row align:center gap:0o5 muted">
                        <input type="checkbox" id="showLineNumbers" checked> Line numbers
                    </label>
                    <button type="button" id="copyBtn" class="btn btn--ghost btn--sm">Copy code</button>
                </div>
            </div>

            <pre id="codeContainer"><code id="codeContent" class="language-blade">{{ $content }}</code></pre>
        </div>

        <textarea id="rawContent" class="hidden">{{ $content }}</textarea>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const codeContainer = document.getElementById('codeContainer');
            const copyBtn = document.getElementById('copyBtn');

            function initPrism() {
                if (!Prism.languages.blade) {
                    const bladeScript = document.createElement('script');
                    bladeScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-blade.min.js';
                    bladeScript.onload = () => Prism.highlightElement(document.getElementById('codeContent'));
                    document.head.appendChild(bladeScript);
                } else {
                    Prism.highlightElement(document.getElementById('codeContent'));
                }
            }

            if (typeof Prism === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js';
                script.onload = initPrism;
                document.head.appendChild(script);

                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css';
                document.head.appendChild(css);
            } else {
                initPrism();
            }

            document.getElementById('theme').addEventListener('change', function () {
                document.querySelectorAll('link[href*="prism"]').forEach(link => link.remove());
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = `https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-${this.value}.min.css`;
                document.head.appendChild(css);
            });

            document.getElementById('wrapLines').addEventListener('change', function () {
                codeContainer.style.whiteSpace = this.checked ? 'pre-wrap' : 'pre';
            });

            document.getElementById('showLineNumbers').addEventListener('change', function () {
                codeContainer.classList.toggle('line-numbers', this.checked);
            });

            copyBtn.addEventListener('click', async function () {
                const rawContent = document.getElementById('rawContent').value;
                try {
                    await navigator.clipboard.writeText(rawContent);
                } catch (e) {
                    const textarea = document.getElementById('rawContent');
                    textarea.classList.remove('hidden');
                    textarea.select();
                    document.execCommand('copy');
                    textarea.classList.add('hidden');
                }
                copyBtn.textContent = 'Copied!';
                copyBtn.classList.add('is-on-success');
                setTimeout(() => {
                    copyBtn.textContent = 'Copy code';
                    copyBtn.classList.remove('is-on-success');
                }, 2000);
            });

            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'c' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                    e.preventDefault();
                    copyBtn.click();
                }
                if (e.key === 'Escape') {
                    window.location.href = @json(route('admin.templates.index'));
                }
            });
        });
    </script>
</x-layouts.admin>
