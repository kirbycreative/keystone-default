@php($logoUrl = $logoUrl ?? (isset($onboarding) ? $onboarding->logoUrl() : null))
<div class="field flex:row gap:2 columns:2" data-logo-field>
    <div class="column w:50">
        <input-image name="{{ $name ?? 'logo' }}" aspect="1.5" button-label="Choose Logo" class="w:100"></input-image>
    </div>
    <div class="column w:50">
        <h3>Color Guide</h3>
        <div id="primary-colors" class="color-swatches">
        </div>
        <h4>Primary Color:</h4>
        <input-color name="primary_color" value="{{ $primaryColor ?? '' }}" formats="hex,rgba"
            show-requirement="false"></input-color>
        <h4>Secondary Color:</h4>
        <input-color name="secondary_color" value="{{ $secondaryColor ?? '' }}" formats="hex,rgba"
            show-requirement="false"></input-color>
    </div>
</div>

<script>
    (() => {
        const field = document.currentScript.previousElementSibling;
        const imageInput = field?.querySelector('input-image');
        const colorsPanel = field?.querySelector('#primary-colors');
        const maxSwatches = 12;
        let activeSampler = null;

        if (!imageInput || !colorsPanel) return;

        function clampChannel(value) {
            return Math.max(0, Math.min(255, Math.round(value)));
        }

        function rgbToHex(r, g, b) {
            return '#' + [r, g, b].map(value => clampChannel(value).toString(16).padStart(2, '0')).join('');
        }

        function applyStyles(element, styles) {
            Object.assign(element.style, styles);
        }

        function canvasPointFromClient(canvas, clientX, clientY) {
            const rect = canvas.getBoundingClientRect();
            if (!rect.width || !rect.height || !canvas.width || !canvas.height) return null;

            const x = Math.max(0, Math.min(canvas.width - 1, Math.round((clientX - rect.left) * canvas.width / rect
                .width)));
            const y = Math.max(0, Math.min(canvas.height - 1, Math.round((clientY - rect.top) * canvas.height / rect
                .height)));

            return {
                x,
                y,
                rect
            };
        }

        function sampleCanvasColor(canvas, clientX, clientY) {
            const point = canvasPointFromClient(canvas, clientX, clientY);
            if (!point) return null;

            const color = imageInput.getPixel(point.x, point.y);
            if (!color) return null;

            return {
                ...color,
                hex: rgbToHex(color.r, color.g, color.b),
                point
            };
        }

        function drawSamplerZoom(sourceCanvas, zoomCanvas, point) {
            const zoomContext = zoomCanvas.getContext('2d');
            const size = zoomCanvas.width;
            const sampleSize = 18;
            const sourceX = Math.max(0, Math.min(sourceCanvas.width - sampleSize, point.x - Math.floor(sampleSize /
                2)));
            const sourceY = Math.max(0, Math.min(sourceCanvas.height - sampleSize, point.y - Math.floor(sampleSize /
                2)));

            zoomContext.imageSmoothingEnabled = false;
            zoomContext.clearRect(0, 0, size, size);
            zoomContext.drawImage(sourceCanvas, sourceX, sourceY, sampleSize, sampleSize, 0, 0, size, size);
        }

        function startEyedropperSampler(colorInput) {
            const sourceCanvas = imageInput.canvas;
            if (!sourceCanvas || !sourceCanvas.width || !sourceCanvas.height) return;
            if (activeSampler) activeSampler.close();

            const rect = sourceCanvas.getBoundingClientRect();
            const sampler = document.createElement('div');
            const zoomCanvas = document.createElement('canvas');
            const crosshair = document.createElement('span');
            const swatch = document.createElement('span');
            const size = 132;
            let sampledColor = null;
            let dragging = false;

            zoomCanvas.width = size;
            zoomCanvas.height = size;
            sampler.setAttribute('role', 'button');
            sampler.setAttribute('aria-label', 'Drag to sample logo color');

            applyStyles(sampler, {
                position: 'fixed',
                left: `${rect.left + (rect.width / 2) - (size / 2)}px`,
                top: `${rect.top + (rect.height / 2) - (size / 2)}px`,
                width: `${size}px`,
                height: `${size}px`,
                border: '2px solid #0f172a',
                borderRadius: '6px',
                boxShadow: '0 14px 34px rgba(15, 23, 42, 0.28)',
                background: '#ffffff',
                cursor: 'crosshair',
                overflow: 'hidden',
                zIndex: 9999,
                touchAction: 'none'
            });
            applyStyles(zoomCanvas, {
                display: 'block',
                width: '100%',
                height: '100%'
            });
            applyStyles(crosshair, {
                position: 'absolute',
                left: '50%',
                top: '50%',
                width: '22px',
                height: '22px',
                border: '1px solid #ffffff',
                boxShadow: '0 0 0 1px #0f172a',
                transform: 'translate(-50%, -50%)',
                pointerEvents: 'none'
            });
            applyStyles(swatch, {
                position: 'absolute',
                right: '8px',
                bottom: '8px',
                width: '22px',
                height: '22px',
                border: '1px solid rgba(15, 23, 42, 0.45)',
                borderRadius: '4px',
                background: '#000000',
                pointerEvents: 'none'
            });

            sampler.append(zoomCanvas, crosshair, swatch);
            document.body.appendChild(sampler);

            const moveTo = (clientX, clientY) => {
                const clampedX = Math.max(rect.left, Math.min(rect.right, clientX));
                const clampedY = Math.max(rect.top, Math.min(rect.bottom, clientY));
                sampledColor = sampleCanvasColor(sourceCanvas, clampedX, clampedY);

                if (!sampledColor) return;

                sampler.style.left = `${clampedX - (size / 2)}px`;
                sampler.style.top = `${clampedY - (size / 2)}px`;
                swatch.style.background = sampledColor.hex;
                drawSamplerZoom(sourceCanvas, zoomCanvas, sampledColor.point);
            };

            const close = () => {
                sampler.remove();
                document.removeEventListener('keydown', onKeyDown);
                activeSampler = null;
            };

            const commit = () => {
                if (sampledColor && typeof colorInput.commit === 'function') {
                    colorInput.commit(sampledColor.hex);
                } else if (sampledColor) {
                    colorInput.value = sampledColor.hex;
                    colorInput.setAttribute('value', sampledColor.hex);
                }
                close();
            };

            const onKeyDown = event => {
                if (event.key === 'Escape') close();
                if (event.key === 'Enter') commit();
            };

            sampler.addEventListener('pointerdown', event => {
                dragging = true;
                sampler.setPointerCapture?.(event.pointerId);
                moveTo(event.clientX, event.clientY);
            });
            sampler.addEventListener('pointermove', event => {
                if (!dragging) return;
                moveTo(event.clientX, event.clientY);
            });
            sampler.addEventListener('pointerup', event => {
                dragging = false;
                moveTo(event.clientX, event.clientY);
                commit();
            });
            sampler.addEventListener('pointercancel', close);
            document.addEventListener('keydown', onKeyDown);

            activeSampler = {
                close
            };
            moveTo(rect.left + rect.width / 2, rect.top + rect.height / 2);
        }

        function srgbToLinear(value) {
            value /= 255;
            return value <= 0.04045 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
        }

        function rgbToLab(r, g, b) {
            let x = srgbToLinear(r) * 0.4124 + srgbToLinear(g) * 0.3576 + srgbToLinear(b) * 0.1805;
            let y = srgbToLinear(r) * 0.2126 + srgbToLinear(g) * 0.7152 + srgbToLinear(b) * 0.0722;
            let z = srgbToLinear(r) * 0.0193 + srgbToLinear(g) * 0.1192 + srgbToLinear(b) * 0.9505;

            x /= 0.95047;
            z /= 1.08883;

            const pivot = value => value > 0.008856 ? Math.cbrt(value) : (7.787 * value) + (16 / 116);
            x = pivot(x);
            y = pivot(y);
            z = pivot(z);

            return [(116 * y) - 16, 500 * (x - y), 200 * (y - z)];
        }

        function colorDistance(left, right) {
            const labA = left.lab || rgbToLab(left.r, left.g, left.b);
            const labB = right.lab || rgbToLab(right.r, right.g, right.b);
            return Math.hypot(labA[0] - labB[0], labA[1] - labB[1], labA[2] - labB[2]);
        }

        function chroma(color) {
            return Math.max(color.r, color.g, color.b) - Math.min(color.r, color.g, color.b);
        }

        function isIgnoredBackground(color) {
            const max = Math.max(color.r, color.g, color.b);
            const min = Math.min(color.r, color.g, color.b);

            if (max > 248 && min > 238 && chroma(color) < 12) return true;
            if (max < 10) return true;
            return false;
        }

        function extractDistinctColors(imageData, options = {}) {
            const sampleStep = options.sampleStep || 2;
            const bucketSize = options.bucketSize || 24;
            const mergeDistance = options.mergeDistance || 18;
            const minimumPixels = options.minimumPixels || 6;
            const buckets = new Map();

            for (let y = 0; y < imageData.height; y += sampleStep) {
                for (let x = 0; x < imageData.width; x += sampleStep) {
                    const index = (y * imageData.width + x) * 4;
                    const alpha = imageData.data[index + 3];
                    if (alpha < 128) continue;

                    const r = imageData.data[index];
                    const g = imageData.data[index + 1];
                    const b = imageData.data[index + 2];
                    const color = {
                        r,
                        g,
                        b
                    };
                    if (isIgnoredBackground(color)) continue;

                    const key = [
                        Math.round(r / bucketSize) * bucketSize,
                        Math.round(g / bucketSize) * bucketSize,
                        Math.round(b / bucketSize) * bucketSize
                    ].map(clampChannel).join(',');

                    const bucket = buckets.get(key) || {
                        r: 0,
                        g: 0,
                        b: 0,
                        count: 0
                    };
                    bucket.r += r;
                    bucket.g += g;
                    bucket.b += b;
                    bucket.count += 1;
                    buckets.set(key, bucket);
                }
            }

            const candidates = [...buckets.values()]
                .filter(bucket => bucket.count >= minimumPixels)
                .map(bucket => {
                    const color = {
                        r: bucket.r / bucket.count,
                        g: bucket.g / bucket.count,
                        b: bucket.b / bucket.count,
                        count: bucket.count
                    };
                    color.lab = rgbToLab(color.r, color.g, color.b);
                    return color;
                })
                .sort((a, b) => b.count - a.count);

            const distinct = [];

            for (const candidate of candidates) {
                const existing = distinct.find(color => colorDistance(color, candidate) < mergeDistance);

                if (existing) {
                    const total = existing.count + candidate.count;
                    existing.r = ((existing.r * existing.count) + (candidate.r * candidate.count)) / total;
                    existing.g = ((existing.g * existing.count) + (candidate.g * candidate.count)) / total;
                    existing.b = ((existing.b * existing.count) + (candidate.b * candidate.count)) / total;
                    existing.count = total;
                    existing.lab = rgbToLab(existing.r, existing.g, existing.b);
                    continue;
                }

                distinct.push(candidate);
                if (distinct.length >= maxSwatches) break;
            }

            return distinct
                .sort((a, b) => b.count - a.count)
                .map(color => rgbToHex(color.r, color.g, color.b));
        }

        function makeSwatch(hex) {
            let active = false;
            const swatch = document.createElement('span');
            swatch.className = 'color-swatch';

            swatch.innerHTML = `
                <div class="color" style="background-color: ${hex};"></div>
                <span class="color-remove" title="Remove Color" onclick="this.closest('.color-swatch').remove()">&times;</span>
                <span class="color-hex">${hex}</span>
        `;
            swatch.addEventListener('click', () => {
                if (active) return;
                const colorInput = document.createElement('input-color');
                colorInput.setAttribute('name', 'primary_colors[]');
                colorInput.setAttribute('value', hex);
                colorInput.setAttribute('formats', 'hex,rgba');
                colorInput.setAttribute('show-requirement', 'false');
                colorInput.addEventListener('input', () => {
                    swatch.querySelector('.color-hex').textContent = colorInput.value;
                    swatch.querySelector('.color').style.backgroundColor = colorInput.value;
                });
                swatch.appendChild(colorInput);
                // startEyedropperSampler(colorInput);
                active = true;
            });
            // swatch.querySelector('.color-remove').addEventListener('click', () => swatch.remove());
            return swatch;
        }

        function renderSwatches(colors) {
            colorsPanel.innerHTML = '';
            colors.forEach(color => colorsPanel.appendChild(makeSwatch(color)));
        }

        imageInput.addEventListener('image-load', () => {
            const imageData = imageInput.getImageData();
            if (!imageData) return;
            renderSwatches(extractDistinctColors(imageData));
        });

        colorsPanel.addEventListener('eyedropper-request', event => {
            const colorInput = event.detail?.input || event.target;
            event.preventDefault();
            startEyedropperSampler(colorInput);
        });
    })();
</script>
