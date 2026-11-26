/* MotuCloud Favicon Geometry, Canvas, Vector SVG & Multi-Frame ICO Renderer */
(function (global) {
    'use strict';

    const FONT_MAP = {
        system: '"Spinnaker", "wenfeng-albbpht", "Alibaba PuHuiTi R", "PingFang SC", "Hiragino Sans GB", "Noto Sans CJK SC", "Microsoft YaHei", system-ui, -apple-system, sans-serif',
        serif: '"Songti SC", "Noto Serif CJK SC", "Source Han Serif SC", "STSong", Georgia, "Times New Roman", serif',
        mono: 'ui-monospace, "SFMono-Regular", Menlo, Monaco, Consolas, "PingFang SC", monospace',
        rounded: '"SF Pro Rounded", "Hiragino Maru Gothic ProN", "PingFang SC", "Microsoft YaHei", ui-rounded, system-ui, sans-serif'
    };

    const EMOJI_FONT = '"Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif';

    function normalizeHex(raw) {
        if (!raw) return null;
        let value = String(raw).trim();
        if (!value.startsWith('#')) {
            value = '#' + value;
        }
        if (/^#[0-9a-fA-F]{3}$/.test(value)) {
            value = '#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3];
        }
        if (/^#[0-9a-fA-F]{6}$/.test(value)) {
            return value.toUpperCase();
        }
        return null;
    }

    function hexToRgb(hex) {
        const clean = normalizeHex(hex);
        if (!clean) return { r: 10, g: 10, b: 10 };
        return {
            r: parseInt(clean.slice(1, 3), 16),
            g: parseInt(clean.slice(3, 5), 16),
            b: parseInt(clean.slice(5, 7), 16)
        };
    }

    function rgbToHex(r, g, b) {
        const clamp = function (v) {
            return Math.max(0, Math.min(255, Math.round(v)));
        };
        return (
            '#' +
            [clamp(r), clamp(g), clamp(b)]
                .map(function (v) {
                    return v.toString(16).padStart(2, '0');
                })
                .join('')
                .toUpperCase()
        );
    }

    function adjustColor(hex, percent) {
        const rgb = hexToRgb(hex);
        const delta = Math.round(2.55 * percent);
        return rgbToHex(rgb.r + delta, rgb.g + delta, rgb.b + delta);
    }

    function getLuminance(hex) {
        const rgb = hexToRgb(hex);
        const parts = [rgb.r, rgb.g, rgb.b].map(function (v) {
            const s = v / 255;
            return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
        });
        return parts[0] * 0.2126 + parts[1] * 0.7152 + parts[2] * 0.0722;
    }

    function calculateContrast(hex1, hex2) {
        const l1 = getLuminance(hex1);
        const l2 = getLuminance(hex2);
        const hi = Math.max(l1, l2);
        const lo = Math.min(l1, l2);
        return (hi + 0.05) / (lo + 0.05);
    }

    function getContrastBadgeInfo(ratio) {
        const value = Number.isFinite(ratio) ? ratio : 1;
        const formatted = value.toFixed(1) + ':1';
        if (value >= 7) {
            return { ratio: formatted, grade: 'AAA', level: 'good' };
        }
        if (value >= 4.5) {
            return { ratio: formatted, grade: 'AA', level: 'good' };
        }
        if (value >= 3) {
            return { ratio: formatted, grade: '大字可用', level: 'warn' };
        }
        return { ratio: formatted, grade: '偏低', level: 'low' };
    }

    function getGradientStops(baseHex) {
        const clean = normalizeHex(baseHex) || '#0A0A0A';
        const lum = getLuminance(clean);
        if (lum < 0.04) {
            return { top: adjustColor(clean, 14), bottom: clean };
        }
        if (lum > 0.92) {
            return { top: clean, bottom: adjustColor(clean, -7) };
        }
        return { top: adjustColor(clean, 6), bottom: adjustColor(clean, -12) };
    }

    function resolveStrokeColor(state) {
        if (state.mode === 'text' || state.mode === 'icon') {
            return normalizeHex(state.textColor) || '#FFFFFF';
        }
        if (state.bgMode === 'transparent') {
            return '#0A0A0A';
        }
        const bgLum = getLuminance(state.bgColor || '#FFFFFF');
        return bgLum > 0.45 ? '#0A0A0A' : '#FFFFFF';
    }

    function isEmojiText(text) {
        if (!text) return false;
        try {
            return /\p{Extended_Pictographic}/u.test(text);
        } catch (e) {
            return /[\uD83C-\uDBFF\uDC00-\uDFFF]/.test(text);
        }
    }

    function escapeXml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    }

    function getHexagonPoints(cx, cy, r) {
        const pts = [];
        for (let i = 0; i < 6; i++) {
            const angle = (Math.PI / 3) * i - Math.PI / 2;
            pts.push([cx + r * Math.cos(angle), cy + r * Math.sin(angle)]);
        }
        return pts;
    }

    function buildShapePath(ctx, size, shape, radiusPct, inset) {
        const pad = inset || 0;
        const x = pad;
        const y = pad;
        const w = Math.max(1, size - pad * 2);
        const h = Math.max(1, size - pad * 2);
        const cx = size / 2;
        const cy = size / 2;
        const half = w / 2;

        ctx.beginPath();
        if (shape === 'square') {
            ctx.rect(x, y, w, h);
        } else if (shape === 'circle') {
            ctx.arc(cx, cy, half, 0, Math.PI * 2);
        } else if (shape === 'hexagon') {
            const pts = getHexagonPoints(cx, cy, half);
            pts.forEach(function (pt, idx) {
                if (idx === 0) ctx.moveTo(pt[0], pt[1]);
                else ctx.lineTo(pt[0], pt[1]);
            });
        } else if (shape === 'diamond') {
            ctx.moveTo(cx, y);
            ctx.lineTo(x + w, cy);
            ctx.lineTo(cx, y + h);
            ctx.lineTo(x, cy);
        } else if (shape === 'shield') {
            ctx.moveTo(cx, y);
            ctx.lineTo(x + w, y + h * 0.16);
            ctx.lineTo(x + w, y + h * 0.52);
            ctx.quadraticCurveTo(x + w, y + h * 0.85, cx, y + h);
            ctx.quadraticCurveTo(x, y + h * 0.85, x, y + h * 0.52);
            ctx.lineTo(x, y + h * 0.16);
        } else {
            // 'rounded' (default)
            const rPct = Math.max(0, Math.min(50, Number(radiusPct) || 0));
            if (rPct <= 0) {
                ctx.rect(x, y, w, h);
            } else if (rPct >= 50) {
                ctx.arc(cx, cy, half, 0, Math.PI * 2);
            } else {
                const r = Math.min(half, (w * rPct) / 100);
                ctx.moveTo(x + r, y);
                ctx.arcTo(x + w, y, x + w, y + h, r);
                ctx.arcTo(x + w, y + h, x, y + h, r);
                ctx.arcTo(x, y + h, x, y, r);
                ctx.arcTo(x, y, x + w, y, r);
            }
        }
        ctx.closePath();
    }

    function buildSvgShapeElement(size, shape, radiusPct, inset, attrs) {
        const pad = inset || 0;
        const x = pad;
        const y = pad;
        const w = Math.max(1, size - pad * 2);
        const h = Math.max(1, size - pad * 2);
        const cx = size / 2;
        const cy = size / 2;
        const half = w / 2;
        const fmt = function (n) {
            return Number(n.toFixed(2));
        };
        const attrStr = attrs ? ' ' + attrs : '';

        if (shape === 'square') {
            return '<rect x="' + fmt(x) + '" y="' + fmt(y) + '" width="' + fmt(w) + '" height="' + fmt(h) + '"' + attrStr + '/>';
        }
        if (shape === 'circle') {
            return '<circle cx="' + fmt(cx) + '" cy="' + fmt(cy) + '" r="' + fmt(half) + '"' + attrStr + '/>';
        }
        if (shape === 'hexagon') {
            const pts = getHexagonPoints(cx, cy, half)
                .map(function (pt) {
                    return fmt(pt[0]) + ',' + fmt(pt[1]);
                })
                .join(' ');
            return '<polygon points="' + pts + '"' + attrStr + '/>';
        }
        if (shape === 'diamond') {
            const pts = [
                fmt(cx) + ',' + fmt(y),
                fmt(x + w) + ',' + fmt(cy),
                fmt(cx) + ',' + fmt(y + h),
                fmt(x) + ',' + fmt(cy)
            ].join(' ');
            return '<polygon points="' + pts + '"' + attrStr + '/>';
        }
        if (shape === 'shield') {
            const d = [
                'M', fmt(cx), fmt(y),
                'L', fmt(x + w), fmt(y + h * 0.16),
                'L', fmt(x + w), fmt(y + h * 0.52),
                'Q', fmt(x + w), fmt(y + h * 0.85), fmt(cx), fmt(y + h),
                'Q', fmt(x), fmt(y + h * 0.85), fmt(x), fmt(y + h * 0.52),
                'L', fmt(x), fmt(y + h * 0.16),
                'Z'
            ].join(' ');
            return '<path d="' + d + '"' + attrStr + '/>';
        }
        // 'rounded'
        const rPct = Math.max(0, Math.min(50, Number(radiusPct) || 0));
        if (rPct >= 50) {
            return '<circle cx="' + fmt(cx) + '" cy="' + fmt(cy) + '" r="' + fmt(half) + '"' + attrStr + '/>';
        }
        const r = Math.min(half, (w * rPct) / 100);
        return (
            '<rect x="' + fmt(x) + '" y="' + fmt(y) + '" width="' + fmt(w) + '" height="' + fmt(h) +
            '" rx="' + fmt(r) + '" ry="' + fmt(r) + '"' + attrStr + '/>'
        );
    }

    function getShapeInnerFactor(shape) {
        if (shape === 'diamond') return 0.72;
        if (shape === 'hexagon') return 0.82;
        if (shape === 'shield') return 0.82;
        return 1;
    }

    function getShapeCenterYOffset(shape, size) {
        if (shape === 'shield') return -size * 0.03;
        return 0;
    }

    function renderIconToCanvas(canvas, size, state) {
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, size, size);

        const shape = state.shape || 'rounded';
        const strokeUnits = Math.max(0, Math.min(36, Number(state.stroke) || 0));
        const strokePx = (strokeUnits / 512) * size;
        const clipInset = strokePx > 0 ? strokePx / 2 : 0;

        ctx.save();
        buildShapePath(ctx, size, shape, state.radius, clipInset);
        ctx.clip();

        if (state.bgMode === 'gradient') {
            const stops = getGradientStops(state.bgColor);
            const grad = ctx.createLinearGradient(0, 0, 0, size);
            grad.addColorStop(0, stops.top);
            grad.addColorStop(1, stops.bottom);
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, size, size);
        } else if (state.bgMode === 'solid') {
            ctx.fillStyle = normalizeHex(state.bgColor) || '#FFFFFF';
            ctx.fillRect(0, 0, size, size);
        }

        const pad = (size * (Number(state.padding) || 0)) / 100 + strokePx * 0.65;
        const shapeFactor = getShapeInnerFactor(shape);
        const inner = Math.max(1, (size - pad * 2) * shapeFactor);
        const centerOffsetY = getShapeCenterYOffset(shape, size);

        const activeImage =
            state.mode === 'image'
                ? (state.uploadedImage || state.defaultBrandImage)
                : (state.mode === 'icon' ? state.selectedIconImage : null);

        if (activeImage) {
            const img = activeImage;
            const natW = img.naturalWidth || img.width || size;
            const natH = img.naturalHeight || img.height || size;
            const iconBoxRatio = state.mode === 'icon' ? 0.72 : 1;
            const scaleFactor =
                state.mode === 'image'
                    ? Math.max(0.5, Math.min(1.5, (Number(state.imageScale) || 100) / 100))
                    : 1;
            const targetInner = inner * iconBoxRatio;
            const baseScale = Math.min(targetInner / natW, targetInner / natH) * scaleFactor;
            const drawW = natW * baseScale;
            const drawH = natH * baseScale;
            const dx = (size - drawW) / 2;
            const dy = (size - drawH) / 2 + centerOffsetY;

            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, dx, dy, drawW, drawH);
        } else {
            const label = Array.from((state.text || '墨').trim()).slice(0, 2).join('') || '墨';
            const charCount = Array.from(label).length;
            const emoji = isEmojiText(label);
            const family = emoji ? EMOJI_FONT : (FONT_MAP[state.fontFamily] || FONT_MAP.system);

            let fontSize = charCount <= 1 ? inner * (emoji ? 0.68 : 0.64) : inner * 0.44;
            ctx.font = '400 ' + fontSize + 'px ' + family;

            const measured = ctx.measureText(label);
            const maxWidth = inner * 0.9;
            if (measured.width > maxWidth && measured.width > 0) {
                fontSize = fontSize * (maxWidth / measured.width);
                ctx.font = '400 ' + fontSize + 'px ' + family;
            }

            ctx.fillStyle = normalizeHex(state.textColor) || '#FFFFFF';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            const metrics = ctx.measureText(label);
            let centerY = size / 2 + centerOffsetY;
            if (
                !emoji &&
                typeof metrics.actualBoundingBoxAscent === 'number' &&
                typeof metrics.actualBoundingBoxDescent === 'number' &&
                (metrics.actualBoundingBoxAscent > 0 || metrics.actualBoundingBoxDescent > 0)
            ) {
                centerY =
                    size / 2 +
                    centerOffsetY +
                    (metrics.actualBoundingBoxAscent - metrics.actualBoundingBoxDescent) / 2;
            } else {
                centerY = size / 2 + centerOffsetY + fontSize * 0.05;
            }

            ctx.fillText(label, size / 2, centerY);
        }

        ctx.restore();

        if (strokePx > 0) {
            ctx.save();
            buildShapePath(ctx, size, shape, state.radius, strokePx / 2);
            ctx.strokeStyle = resolveStrokeColor(state);
            ctx.lineWidth = strokePx;
            ctx.lineJoin = 'round';
            ctx.stroke();
            ctx.restore();
        }
    }

    function encodeUtf8Base64(str) {
        const bytes = new TextEncoder().encode(str);
        let binary = '';
        for (let i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }

    function buildSvgString(state) {
        const size = 512;
        const shape = state.shape || 'rounded';
        const strokeUnits = Math.max(0, Math.min(36, Number(state.stroke) || 0));
        const clipInset = strokeUnits > 0 ? strokeUnits / 2 : 0;

        const defs = [];
        defs.push(
            '<clipPath id="motu-fav-clip">' +
            buildSvgShapeElement(size, shape, state.radius, clipInset, '') +
            '</clipPath>'
        );

        let bgElement = '';
        if (state.bgMode === 'gradient') {
            const stops = getGradientStops(state.bgColor);
            defs.push(
                '<linearGradient id="motu-fav-grad" x1="0" y1="0" x2="0" y2="1">' +
                '<stop offset="0%" stop-color="' + stops.top + '"/>' +
                '<stop offset="100%" stop-color="' + stops.bottom + '"/>' +
                '</linearGradient>'
            );
            bgElement = buildSvgShapeElement(size, shape, state.radius, clipInset, 'fill="url(#motu-fav-grad)"');
        } else if (state.bgMode === 'solid') {
            const fill = normalizeHex(state.bgColor) || '#FFFFFF';
            bgElement = buildSvgShapeElement(size, shape, state.radius, clipInset, 'fill="' + fill + '"');
        }

        const pad = (size * (Number(state.padding) || 0)) / 100 + strokeUnits * 0.65;
        const shapeFactor = getShapeInnerFactor(shape);
        const inner = Math.max(1, (size - pad * 2) * shapeFactor);
        const centerOffsetY = getShapeCenterYOffset(shape, size);

        let contentElement = '';
        const activeImage = state.mode === 'image' ? (state.uploadedImage || state.defaultBrandImage) : null;

        if (state.mode === 'icon' && state.selectedIconRecord) {
            const rec = state.selectedIconRecord;
            const iconBox = inner * 0.72;
            const vbW = rec.width || 24;
            const vbH = rec.height || 24;
            const ratio = Math.min(iconBox / vbW, iconBox / vbH);
            const drawW = vbW * ratio;
            const drawH = vbH * ratio;
            const dx = (size - drawW) / 2;
            const dy = (size - drawH) / 2 + centerOffsetY;
            const fg = normalizeHex(state.textColor) || '#FFFFFF';
            const bodyColored = String(rec.body || '').replace(/currentColor/gi, fg);
            contentElement =
                '<svg x="' + dx.toFixed(2) + '" y="' + dy.toFixed(2) +
                '" width="' + drawW.toFixed(2) + '" height="' + drawH.toFixed(2) +
                '" viewBox="0 0 ' + vbW + ' ' + vbH + '" color="' + fg + '">' +
                bodyColored + '</svg>';
        } else if (activeImage) {
            const natW = activeImage.naturalWidth || activeImage.width || size;
            const natH = activeImage.naturalHeight || activeImage.height || size;
            const scaleFactor = Math.max(0.5, Math.min(1.5, (Number(state.imageScale) || 100) / 100));
            const baseScale = Math.min(inner / natW, inner / natH) * scaleFactor;
            const drawW = natW * baseScale;
            const drawH = natH * baseScale;
            const dx = (size - drawW) / 2;
            const dy = (size - drawH) / 2 + centerOffsetY;

            let href = '';
            const rawSvg = state.uploadedImage ? state.uploadedSvgText : state.defaultBrandSvgText;
            if (rawSvg) {
                href = 'data:image/svg+xml;base64,' + encodeUtf8Base64(rawSvg);
            } else {
                const tmp = document.createElement('canvas');
                tmp.width = Math.min(1024, Math.max(1, Math.round(natW)));
                tmp.height = Math.min(1024, Math.max(1, Math.round(natH)));
                const tctx = tmp.getContext('2d');
                tctx.imageSmoothingEnabled = true;
                tctx.imageSmoothingQuality = 'high';
                tctx.drawImage(activeImage, 0, 0, tmp.width, tmp.height);
                href = tmp.toDataURL('image/png');
            }

            contentElement =
                '<image href="' + href + '" x="' + dx.toFixed(2) + '" y="' + dy.toFixed(2) +
                '" width="' + drawW.toFixed(2) + '" height="' + drawH.toFixed(2) +
                '" preserveAspectRatio="xMidYMid meet"/>';
        } else {
            const label = Array.from((state.text || '墨').trim()).slice(0, 2).join('') || '墨';
            const charCount = Array.from(label).length;
            const emoji = isEmojiText(label);
            const family = emoji ? EMOJI_FONT : (FONT_MAP[state.fontFamily] || FONT_MAP.system);
            const fontSize = charCount <= 1 ? inner * (emoji ? 0.68 : 0.64) : inner * 0.44;
            const textFill = normalizeHex(state.textColor) || '#FFFFFF';
            const cy = size / 2 + centerOffsetY + (emoji ? fontSize * 0.04 : fontSize * 0.02);

            contentElement =
                '<text x="' + (size / 2) + '" y="' + cy.toFixed(2) +
                '" text-anchor="middle" dominant-baseline="central" fill="' + textFill +
                '" font-family="' + escapeXml(family) + '" font-weight="400" font-size="' + fontSize.toFixed(1) + '">' +
                escapeXml(label) + '</text>';
        }

        const strokeElement = strokeUnits > 0
            ? buildSvgShapeElement(
                size,
                shape,
                state.radius,
                strokeUnits / 2,
                'fill="none" stroke="' + resolveStrokeColor(state) + '" stroke-width="' + strokeUnits + '" stroke-linejoin="round"'
            )
            : '';

        return [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">',
            '  <defs>' + defs.join('') + '</defs>',
            '  <g clip-path="url(#motu-fav-clip)">',
            bgElement ? '    ' + bgElement : '',
            '    ' + contentElement,
            '  </g>',
            strokeElement ? '  ' + strokeElement : '',
            '</svg>',
            ''
        ].filter(Boolean).join('\n');
    }

    function canvasToPngBytes(canvas) {
        return new Promise(function (resolve, reject) {
            if (canvas.toBlob) {
                canvas.toBlob(function (blob) {
                    if (!blob) {
                        reject(new Error('PNG 编码失败'));
                        return;
                    }
                    blob.arrayBuffer().then(function (buf) {
                        resolve(new Uint8Array(buf));
                    }, reject);
                }, 'image/png');
                return;
            }
            try {
                const dataUrl = canvas.toDataURL('image/png');
                const bin = atob(dataUrl.split(',')[1]);
                const bytes = new Uint8Array(bin.length);
                for (let i = 0; i < bin.length; i++) {
                    bytes[i] = bin.charCodeAt(i);
                }
                resolve(bytes);
            } catch (err) {
                reject(err);
            }
        });
    }

    /**
     * Build a genuine multi-resolution binary .ico buffer with PNG frames.
     * Layout: 6-byte ICONDIR + N * 16-byte ICONDIRENTRY + concatenated PNG payloads.
     */
    function buildIcoBinary(frames) {
        const count = frames.length;
        const headerSize = 6;
        const entrySize = 16;
        let offset = headerSize + count * entrySize;
        let totalSize = offset;

        for (let i = 0; i < count; i++) {
            totalSize += frames[i].bytes.byteLength;
        }

        const buffer = new ArrayBuffer(totalSize);
        const view = new DataView(buffer);
        const output = new Uint8Array(buffer);

        view.setUint16(0, 0, true);
        view.setUint16(2, 1, true);
        view.setUint16(4, count, true);

        let dataCursor = offset;
        for (let i = 0; i < count; i++) {
            const frame = frames[i];
            const entryOffset = headerSize + i * entrySize;
            const dimByte = frame.size >= 256 ? 0 : frame.size;

            view.setUint8(entryOffset + 0, dimByte);
            view.setUint8(entryOffset + 1, dimByte);
            view.setUint8(entryOffset + 2, 0);
            view.setUint8(entryOffset + 3, 0);
            view.setUint16(entryOffset + 4, 1, true);
            view.setUint16(entryOffset + 6, 32, true);
            view.setUint32(entryOffset + 8, frame.bytes.byteLength, true);
            view.setUint32(entryOffset + 12, dataCursor, true);

            output.set(frame.bytes, dataCursor);
            dataCursor += frame.bytes.byteLength;
        }

        return output;
    }

    global.MotuFaviconRenderer = {
        FONT_MAP: FONT_MAP,
        normalizeHex: normalizeHex,
        adjustColor: adjustColor,
        calculateContrast: calculateContrast,
        getContrastBadgeInfo: getContrastBadgeInfo,
        renderIconToCanvas: renderIconToCanvas,
        buildSvgString: buildSvgString,
        canvasToPngBytes: canvasToPngBytes,
        buildIcoBinary: buildIcoBinary
    };
})(window);
