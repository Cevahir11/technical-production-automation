(function () {
    let canvas;
    let ctx;

    let currentTool = 'select';
    let gridEnabled = true;
    let orthoEnabled = false;
    
    let objects = [];
    let history = [];
    const CAD_STORAGE_KEY = 'vertu_imalat_cad_objects';

    let isDrawing = false;
    let isDragging = false;

    let startX = 0;
    let startY = 0;
    let dragStartX = 0;
    let dragStartY = 0;

    let previewObject = null;
    let selectedIndex = -1;

    function initCadEditor() {
        canvas = document.getElementById('manualCanvas');
        if (!canvas) return;

        ctx = canvas.getContext('2d');

        bindToolbar();
        bindCanvasEvents();
        bindFormSubmit();
        bindAutoImport();

        loadSavedCadObjects();
        saveHistory();
        renderAll();
       
    }

    function bindToolbar() {
        document.querySelectorAll('.tool-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.tool-btn').forEach(function (b) {
                    b.classList.remove('active');
                });

                btn.classList.add('active');
                currentTool = btn.dataset.tool;
                selectedIndex = -1;
                renderAll();
            });
        });

        const gridBtn = document.getElementById('toggleGridBtn');
        const orthoBtn = document.getElementById('toggleOrthoBtn');
        const undoBtn = document.getElementById('undoBtn');
        const clearBtn = document.getElementById('clearCanvasBtn');

        if (gridBtn) {
            gridBtn.addEventListener('click', function () {
                gridEnabled = !gridEnabled;
                gridBtn.classList.toggle('active', gridEnabled);
                gridBtn.textContent = gridEnabled ? 'Grid Açık' : 'Grid Kapalı';
                renderAll();
            });
        }

        if (orthoBtn) {
            orthoBtn.addEventListener('click', function () {
                orthoEnabled = !orthoEnabled;
                orthoBtn.classList.toggle('active', orthoEnabled);
                orthoBtn.textContent = orthoEnabled ? 'Ortho Açık' : 'Ortho Kapalı';
            });
        }

        if (undoBtn) {
            undoBtn.addEventListener('click', undoHistory);
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                objects = [];
                previewObject = null;
                selectedIndex = -1;
                history = [];

                localStorage.removeItem(CAD_STORAGE_KEY);

                saveHistory();
                renderAll();
            });
        }
    }


    function bindAutoImport() {
        const button = document.getElementById('autoToCadBtn');
        const form = document.getElementById('imalatForm');

        if (!button || !form) return;

        button.addEventListener('click', async function () {
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Otomatik çizim hazırlanıyor...';

            try {
                const formData = new FormData(form);
                formData.set('drawing_mode', 'auto');

                const response = await fetch('generate_drawing.php', {
                    method: 'POST',
                    body: formData
                });

                const rawText = await response.text();
                let result;

                try {
                    result = JSON.parse(rawText);
                } catch (e) {
                    throw new Error('Sunucudan geçerli JSON gelmedi. generate_drawing.php dosyasını kontrol et.');
                }

                if (!response.ok || !result.success || !result.svg) {
                    throw new Error(result.message || 'Otomatik çizim oluşturulamadı.');
                }

                const importedCount = importSvgToCad(result.svg);

                if (importedCount <= 0) {
                    throw new Error('SVG geldi ancak düzenlenebilir çizim nesnesi bulunamadı.');
                }

                const manualRadio = document.querySelector('input[name="drawing_mode"][value="manual"]');
                if (manualRadio) {
                    manualRadio.checked = true;
                }

                if (typeof window.toggleDrawingMode === 'function') {
                    window.toggleDrawingMode();
                } else if (typeof toggleDrawingMode === 'function') {
                    toggleDrawingMode();
                }

                currentTool = 'select';
                document.querySelectorAll('.tool-btn').forEach(function (btn) {
                    btn.classList.toggle('active', btn.dataset.tool === 'select');
                });

                selectedIndex = -1;
                previewObject = null;
                history = [];
                saveHistory();
                renderAll();

                setTimeout(function () {
                    const box = document.getElementById('manualDrawingBox');
                    if (box) box.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);

                alert('Otomatik çizim mühendis alanına aktarıldı. Artık nesneleri seçip taşıyabilir, silebilir ve üzerine yeni çizimler ekleyebilirsin.');
            } catch (error) {
                console.error(error);
                alert(error.message || 'Otomatik çizim düzenleme alanına aktarılamadı.');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    }

    function importSvgToCad(svgText) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(svgText, 'image/svg+xml');

        if (doc.querySelector('parsererror')) {
            throw new Error('Otomatik çizim SVG formatında okunamadı.');
        }

        const svg = doc.documentElement;
        const viewBox = (svg.getAttribute('viewBox') || '0 0 1000 700')
            .trim()
            .split(/[\s,]+/)
            .map(Number);

        const vbX = Number.isFinite(viewBox[0]) ? viewBox[0] : 0;
        const vbY = Number.isFinite(viewBox[1]) ? viewBox[1] : 0;
        const vbW = Number.isFinite(viewBox[2]) && viewBox[2] > 0 ? viewBox[2] : 1000;
        const vbH = Number.isFinite(viewBox[3]) && viewBox[3] > 0 ? viewBox[3] : 700;

        const scaleX = canvas.width / vbW;
        const scaleY = canvas.height / vbH;
        const imported = [];

        function mapX(value) {
            return (Number(value) - vbX) * scaleX;
        }

        function mapY(value) {
            return (Number(value) - vbY) * scaleY;
        }

        function parseTranslate(transform) {
            const result = { x: 0, y: 0 };
            if (!transform) return result;

            const matches = transform.matchAll(/translate\(\s*([-+]?\d*\.?\d+)\s*(?:[,\s]+\s*([-+]?\d*\.?\d+))?\s*\)/gi);
            for (const match of matches) {
                result.x += Number(match[1] || 0);
                result.y += Number(match[2] || 0);
            }
            return result;
        }

        function parseRotation(transform) {
            if (!transform) return 0;
            const match = transform.match(/rotate\(\s*([-+]?\d*\.?\d+)/i);
            return match ? Number(match[1]) * Math.PI / 180 : 0;
        }

        function attr(el, name, fallback = '') {
            const direct = el.getAttribute(name);
            if (direct !== null && direct !== '') return direct;
            const styleValue = el.style && el.style[name];
            return styleValue || fallback;
        }

        function numberAttr(el, name, fallback = 0) {
            const value = Number.parseFloat(attr(el, name, fallback));
            return Number.isFinite(value) ? value : Number(fallback) || 0;
        }

        function normalizedColor(value, fallback = '#111827') {
            if (!value || value === 'currentColor') return fallback;
            return value;
        }

        function isNone(value) {
            return !value || value === 'none' || value === 'transparent';
        }

        function shouldSkip(el, tag) {
            const stroke = (attr(el, 'stroke', '') || '').toLowerCase();
            const fill = (attr(el, 'fill', '') || '').toLowerCase();

            // Otomatik SVG'nin arka plan gridini içeri alma. Editörde zaten grid var.
            if (stroke === '#e2e8f0' || stroke === '#f1f5f9') return true;

            if (tag === 'rect') {
                const x = numberAttr(el, 'x', 0);
                const y = numberAttr(el, 'y', 0);
                const w = numberAttr(el, 'width', 0);
                const h = numberAttr(el, 'height', 0);
                const isFullBackground = x === 0 && y === 0 && w >= vbW * 0.95 && h >= vbH * 0.95;
                if (isFullBackground && (fill === '#ffffff' || fill === '#fff' || fill === 'white')) return true;
            }

            return false;
        }

        function parsePoints(pointsText, tx, ty) {
            const nums = (pointsText || '').match(/[-+]?\d*\.?\d+(?:e[-+]?\d+)?/gi) || [];
            const points = [];
            for (let i = 0; i + 1 < nums.length; i += 2) {
                points.push({
                    x: mapX(Number(nums[i]) + tx),
                    y: mapY(Number(nums[i + 1]) + ty)
                });
            }
            return points;
        }

        function pathBounds(d, tx, ty) {
            const nums = (d || '').match(/[-+]?\d*\.?\d+(?:e[-+]?\d+)?/gi) || [];
            const points = [];
            for (let i = 0; i + 1 < nums.length; i += 2) {
                points.push({
                    x: mapX(Number(nums[i]) + tx),
                    y: mapY(Number(nums[i + 1]) + ty)
                });
            }
            if (!points.length) return null;
            const xs = points.map(p => p.x);
            const ys = points.map(p => p.y);
            return {
                x: Math.min(...xs),
                y: Math.min(...ys),
                w: Math.max(...xs) - Math.min(...xs),
                h: Math.max(...ys) - Math.min(...ys)
            };
        }

        function walk(el, parentTx = 0, parentTy = 0) {
            if (!(el instanceof Element)) return;

            const tag = el.tagName.toLowerCase();
            const translate = parseTranslate(el.getAttribute('transform') || '');
            const tx = parentTx + translate.x;
            const ty = parentTy + translate.y;

            if (tag === 'svg' || tag === 'g') {
                Array.from(el.children).forEach(child => walk(child, tx, ty));
                return;
            }

            if (shouldSkip(el, tag)) return;

            const stroke = normalizedColor(attr(el, 'stroke', '#111827'));
            const fill = normalizedColor(attr(el, 'fill', 'none'), 'none');
            const lineWidth = Math.max(0.5, numberAttr(el, 'stroke-width', 1.5) * ((scaleX + scaleY) / 2));
            const opacity = Math.max(0, Math.min(1, numberAttr(el, 'opacity', 1)));

            if (tag === 'line') {
                imported.push({
                    type: 'line',
                    x1: mapX(numberAttr(el, 'x1') + tx),
                    y1: mapY(numberAttr(el, 'y1') + ty),
                    x2: mapX(numberAttr(el, 'x2') + tx),
                    y2: mapY(numberAttr(el, 'y2') + ty),
                    color: stroke,
                    lineWidth,
                    opacity
                });
                return;
            }

            if (tag === 'rect') {
                const x = numberAttr(el, 'x') + tx;
                const y = numberAttr(el, 'y') + ty;
                const w = numberAttr(el, 'width');
                const h = numberAttr(el, 'height');
                imported.push({
                    type: 'rect',
                    x1: mapX(x),
                    y1: mapY(y),
                    x2: mapX(x + w),
                    y2: mapY(y + h),
                    color: isNone(stroke) ? '#111827' : stroke,
                    lineWidth,
                    filled: !isNone(fill),
                    fillColor: isNone(fill) ? null : fill,
                    opacity
                });
                return;
            }

            if (tag === 'circle') {
                const cx = numberAttr(el, 'cx') + tx;
                const cy = numberAttr(el, 'cy') + ty;
                const r = numberAttr(el, 'r');
                imported.push({
                    type: 'circle',
                    x1: mapX(cx - r),
                    y1: mapY(cy - r),
                    x2: mapX(cx + r),
                    y2: mapY(cy + r),
                    color: isNone(stroke) ? '#111827' : stroke,
                    lineWidth,
                    filled: !isNone(fill),
                    fillColor: isNone(fill) ? null : fill,
                    opacity
                });
                return;
            }

            if (tag === 'text') {
                const fontSize = Math.max(7, numberAttr(el, 'font-size', 14) * scaleY);
                const fontWeight = attr(el, 'font-weight', '700');
                const textAnchor = attr(el, 'text-anchor', 'start');
                const alignMap = { start: 'left', middle: 'center', end: 'right' };
                imported.push({
                    type: 'text',
                    x: mapX(numberAttr(el, 'x') + tx),
                    y: mapY(numberAttr(el, 'y') + ty),
                    text: el.textContent || '',
                    color: isNone(fill) ? stroke : fill,
                    fontSize,
                    fontWeight,
                    textAlign: alignMap[textAnchor] || 'left',
                    rotation: parseRotation(el.getAttribute('transform') || ''),
                    opacity
                });
                return;
            }

            if (tag === 'polygon' || tag === 'polyline') {
                const points = parsePoints(attr(el, 'points', ''), tx, ty);
                if (points.length >= 2) {
                    imported.push({
                        type: 'svg_poly',
                        points,
                        closed: tag === 'polygon',
                        color: isNone(stroke) ? (isNone(fill) ? '#111827' : fill) : stroke,
                        lineWidth,
                        filled: !isNone(fill),
                        fillColor: isNone(fill) ? null : fill,
                        opacity
                    });
                }
                return;
            }

            if (tag === 'path') {
                const d = attr(el, 'd', '');
                if (!d) return;
                imported.push({
                    type: 'svg_path',
                    d,
                    x: mapX(tx),
                    y: mapY(ty),
                    scaleX,
                    scaleY,
                    color: isNone(stroke) ? '#111827' : stroke,
                    originalLineWidth: numberAttr(el, 'stroke-width', 1.5),
                    filled: !isNone(fill),
                    fillColor: isNone(fill) ? null : fill,
                    opacity,
                    bounds: pathBounds(d, tx, ty)
                });
            }
        }

        walk(svg, 0, 0);

        objects = imported;
        localStorage.setItem(CAD_STORAGE_KEY, JSON.stringify(objects));
        return imported.length;
    }

    function bindCanvasEvents() {
        canvas.addEventListener('mousedown', onPointerDown);
        canvas.addEventListener('mousemove', onPointerMove);
        canvas.addEventListener('mouseup', onPointerUp);
        canvas.addEventListener('mouseleave', onPointerUp);
        canvas.addEventListener('dblclick', onDoubleClick);
        

        canvas.addEventListener('touchstart', function (e) {
            e.preventDefault();
            onPointerDown(normalizeTouch(e.touches[0]));
        }, { passive: false });

        canvas.addEventListener('touchmove', function (e) {
            e.preventDefault();
            onPointerMove(normalizeTouch(e.touches[0]));
        }, { passive: false });

        canvas.addEventListener('touchend', function (e) {
            e.preventDefault();
            onPointerUp();
        }, { passive: false });
    }
    
    function onDoubleClick(event) {
        event.preventDefault();

        if (currentTool !== 'select') return;

        const pos = getCanvasPos(event);
        const hitIndex = hitTest(pos.x, pos.y);
        if (hitIndex === -1) return;

        const obj = objects[hitIndex];
        selectedIndex = hitIndex;

        if (obj.type === 'text') {
            const value = prompt('Yazıyı düzenle:', obj.text || '');
            if (value !== null && value.trim() !== '') {
                obj.text = value.trim();
                saveHistory();
                renderAll();
            }
            return;
        }

        if (obj.type === 'dimension') {
            const value = prompt('Ölçü yazısını düzenle:', obj.text || '');
            if (value !== null && value.trim() !== '') {
                obj.text = value.trim();
                saveHistory();
                renderAll();
            }
            return;
        }

        if (
            obj.type === 'square' ||
            obj.type === 'circle' ||
            obj.type === 'rect' ||
            obj.type === 'triangle' ||
            obj.type === 'parallelogram' ||
            obj.type === 'svg_poly' ||
            obj.type === 'svg_path'
        ) {
            obj.filled = !obj.filled;
            if (obj.filled && !obj.fillColor) {
                obj.fillColor = obj.color || getCadColor();
            }
            saveHistory();
            renderAll();
        }
    }

    function bindFormSubmit() {
        const form = document.getElementById('imalatForm');
        if (!form) return;

        form.addEventListener('submit', function () {
            const mode = document.querySelector('input[name="drawing_mode"]:checked')?.value;
            const hidden = document.getElementById('manual_drawing_data');

            if (mode === 'manual' && hidden && canvas) {
                hidden.value = canvas.toDataURL('image/png');
            }
        });
    }

    function normalizeTouch(touch) {
        return {
            clientX: touch.clientX,
            clientY: touch.clientY
        };
    }

    function getCanvasPos(event) {
        const rect = canvas.getBoundingClientRect();

        return {
            x: (event.clientX - rect.left) * (canvas.width / rect.width),
            y: (event.clientY - rect.top) * (canvas.height / rect.height)
        };
    }

    function applyOrtho(x, y) {
        if (!orthoEnabled) {
            return { x, y };
        }

        const dx = x - startX;
        const dy = y - startY;

        if (Math.abs(dx) >= Math.abs(dy)) {
            return { x, y: startY };
        } else {
            return { x: startX, y };
        }
    }

    function onPointerDown(event) {
        const pos = getCanvasPos(event);
        startX = pos.x;
        startY = pos.y;

        if (currentTool === 'text') {
            const text = prompt('Yazıyı gir:', '3000 x 1750');
            if (text && text.trim() !== '') {
                objects.push({
                    type: 'text',
                    x: startX,
                    y: startY,
                    text: text.trim(),
                    color: getCadColor()
                });
                selectedIndex = objects.length - 1;
                saveHistory();
                renderAll();
            }
            return;
        }

        if (currentTool === 'select') {
            const hitIndex = hitTest(startX, startY);
            selectedIndex = hitIndex;

            if (hitIndex !== -1) {
                isDragging = true;
                dragStartX = startX;
                dragStartY = startY;
            }

            renderAll();
            return;
        }

        if (currentTool === 'delete') {
            const hitIndex = hitTest(startX, startY);
            if (hitIndex !== -1) {
                objects.splice(hitIndex, 1);
                selectedIndex = -1;
                saveHistory();
                renderAll();
            }
            return;
        }

        isDrawing = true;
        previewObject = createPreviewObject(currentTool, startX, startY, startX, startY);
        renderAll();
    }

    function onPointerMove(event) {
        const pos = getCanvasPos(event);

        if (isDragging && currentTool === 'select' && selectedIndex !== -1) {
            const dx = pos.x - dragStartX;
            const dy = pos.y - dragStartY;

            moveObject(objects[selectedIndex], dx, dy);

            dragStartX = pos.x;
            dragStartY = pos.y;

            renderAll();
            return;
        }

        if (!isDrawing || !previewObject) return;

        let end = {
            x: pos.x,
            y: pos.y
        };

        if (orthoEnabled && ['line', 'arrow'].includes(currentTool)) {
            end = applyOrtho(pos.x, pos.y);
        }

        previewObject.x2 = end.x;
        previewObject.y2 = end.y;

        renderAll();
    }

    function onPointerUp() {
        if (isDragging) {
            isDragging = false;
            saveHistory();
            return;
        }

        if (!isDrawing || !previewObject) return;

        if (previewObject.type === 'dimension') {
            const defaultText = buildDimensionText(previewObject.x1, previewObject.y1, previewObject.x2, previewObject.y2);
            const manualText = prompt('Ölçü yazısı:', defaultText);

            previewObject.text = (manualText && manualText.trim() !== '') ? manualText.trim() : defaultText;
        }

        objects.push(JSON.parse(JSON.stringify(previewObject)));
        selectedIndex = objects.length - 1;

        previewObject = null;
        isDrawing = false;

        saveHistory();
        renderAll();
    }

    function getCadColor() {
        const colorInput = document.getElementById('cadColor');
        return colorInput ? colorInput.value : '#111827';
    }

    function createPreviewObject(type, x1, y1, x2, y2) {
        return {
            type,
            x1,
            y1,
            x2,
            y2,
            color: getCadColor()
        };
    }

    function moveObject(obj, dx, dy) {
        if (!obj) return;

        if (obj.type === 'text' || obj.type === 'svg_path') {
            obj.x += dx;
            obj.y += dy;

            if (obj.bounds) {
                obj.bounds.x += dx;
                obj.bounds.y += dy;
            }
            return;
        }

        if (obj.type === 'svg_poly' && Array.isArray(obj.points)) {
            obj.points.forEach(function (point) {
                point.x += dx;
                point.y += dy;
            });
            return;
        }

        obj.x1 += dx;
        obj.y1 += dy;
        obj.x2 += dx;
        obj.y2 += dy;
    }

    function saveHistory() {
        history.push(JSON.stringify(objects));

        if (history.length > 40) {
            history.shift();
        }

        localStorage.setItem(CAD_STORAGE_KEY, JSON.stringify(objects));
    }


    function loadSavedCadObjects() {
        const saved = localStorage.getItem(CAD_STORAGE_KEY);

        if (!saved) return;

        try {
            objects = JSON.parse(saved);

            if (!Array.isArray(objects)) {
                objects = [];
            }
        } catch (e) {
            objects = [];
        }
    }

    function undoHistory() {
        if (history.length <= 1) return;

        history.pop();
        objects = JSON.parse(history[history.length - 1]);
        selectedIndex = -1;
        previewObject = null;
        renderAll();
    }

    function renderAll() {
        clearCanvas();
        drawBackground();

        objects.forEach(function (obj, index) {
            drawObject(obj, index === selectedIndex);
        });

        if (previewObject) {
            drawObject(previewObject, false, true);
        }
    }

    function clearCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    function drawBackground() {
        if (!gridEnabled) return;

        ctx.save();

        const smallGrid = 15;
        const bigGrid = 75;

        // küçük kareler
        ctx.strokeStyle = '#bcc8d8';
        ctx.lineWidth = 0.8;

        for (let x = 0; x <= canvas.width; x += smallGrid) {
            ctx.beginPath();
            ctx.moveTo(x + 0.5, 0);
            ctx.lineTo(x + 0.5, canvas.height);
            ctx.stroke();
        }

        for (let y = 0; y <= canvas.height; y += smallGrid) {
            ctx.beginPath();
            ctx.moveTo(0, y + 0.5);
            ctx.lineTo(canvas.width, y + 0.5);
            ctx.stroke();
        }

        // büyük kareler
        ctx.strokeStyle = '#7c8b9d';
        ctx.lineWidth = 1.3;

        for (let x = 0; x <= canvas.width; x += bigGrid) {
            ctx.beginPath();
            ctx.moveTo(x + 0.5, 0);
            ctx.lineTo(x + 0.5, canvas.height);
            ctx.stroke();
        }

        for (let y = 0; y <= canvas.height; y += bigGrid) {
            ctx.beginPath();
            ctx.moveTo(0, y + 0.5);
            ctx.lineTo(canvas.width, y + 0.5);
            ctx.stroke();
        }

        ctx.restore();
    }
    function drawObject(obj, isSelected = false, isPreview = false) {
        if (!obj) return;

        if (obj.type === 'line') drawLine(obj, isSelected, isPreview);
        if (obj.type === 'rect') drawRect(obj, isSelected, isPreview);
        if (obj.type === 'square') drawSquare(obj, isSelected, isPreview);
        if (obj.type === 'parallelogram') drawParallelogram(obj, isSelected, isPreview);
        if (obj.type === 'triangle') drawTriangle(obj, isSelected, isPreview);
        if (obj.type === 'filled_square') drawFilledSquare(obj, isSelected, isPreview);
        if (obj.type === 'circle') drawCircle(obj, isSelected, isPreview);
        if (obj.type === 'filled_circle') drawFilledCircle(obj, isSelected, isPreview);
        if (obj.type === 'arrow') drawArrow(obj, isSelected, isPreview);
        if (obj.type === 'dimension') drawDimension(obj, isSelected, isPreview);
        if (obj.type === 'text') drawText(obj, isSelected);
        if (obj.type === 'svg_poly') drawSvgPoly(obj, isSelected, isPreview);
        if (obj.type === 'svg_path') drawSvgPath(obj, isSelected, isPreview);
    }

    function strokeStyleFor(type, isPreview, color = '#111827') {
        ctx.strokeStyle = color;
        ctx.fillStyle = color;

        if (type === 'dimension') {
            ctx.lineWidth = isPreview ? 1.2 : 1.6;
        } else if (type === 'text') {
            ctx.lineWidth = 1;
        } else {
            ctx.lineWidth = isPreview ? 1.2 : 1.8;
        }

        ctx.globalAlpha = isPreview ? 0.75 : 1;
    }

    function drawLine(obj, isSelected, isPreview) {
        ctx.save();
        strokeStyleFor('line', isPreview, obj.color || '#111827');

        ctx.globalAlpha = obj.opacity ?? ctx.globalAlpha;
        ctx.lineWidth = obj.lineWidth || ctx.lineWidth;
        ctx.beginPath();
        ctx.moveTo(obj.x1, obj.y1);
        ctx.lineTo(obj.x2, obj.y2);
        ctx.stroke();

        ctx.restore();
        if (isSelected) drawSelectionBox(obj);
    }

    function drawRect(obj, isSelected, isPreview) {
        const x = Math.min(obj.x1, obj.x2);
        const y = Math.min(obj.y1, obj.y2);
        const w = Math.abs(obj.x2 - obj.x1);
        const h = Math.abs(obj.y2 - obj.y1);

        ctx.save();
        strokeStyleFor('rect', isPreview, obj.color || '#111827');

        if (obj.filled) {
            ctx.fillStyle = obj.fillColor || obj.color || '#111827';

            if (isPreview) {
                ctx.globalAlpha = 0.75;
            }

            ctx.fillRect(x, y, w, h);
        }

        ctx.globalAlpha = isPreview ? 0.75 : (obj.opacity ?? 1);
        ctx.strokeStyle = obj.color || '#111827';
        ctx.lineWidth = obj.lineWidth || (isPreview ? 1.2 : 1.8);
        ctx.strokeRect(x, y, w, h);

        ctx.restore();

        if (isSelected) {
            drawSelectionRect(x - 6, y - 6, w + 12, h + 12);
        }
    }
    function getParallelogramData(obj) {
        const x = Math.min(obj.x1, obj.x2);
        const y = Math.min(obj.y1, obj.y2);
        const w = Math.abs(obj.x2 - obj.x1);
        const h = Math.abs(obj.y2 - obj.y1);

        // yana yatıklık miktarı
        let skew = Math.max(18, Math.min(40, w * 0.18));

        // sola doğru çizildiyse de mantıklı kalsın
        if (obj.x2 < obj.x1) {
            skew = -skew;
        }

        return { x, y, w, h, skew };
    }

    function drawParallelogram(obj, isSelected, isPreview) {
        const p = getParallelogramData(obj);

        ctx.save();
        strokeStyleFor('rect', isPreview, obj.color || '#111827');

        ctx.beginPath();
        ctx.moveTo(p.x + p.skew, p.y);
        ctx.lineTo(p.x + p.w, p.y);
        ctx.lineTo(p.x + p.w - p.skew, p.y + p.h);
        ctx.lineTo(p.x, p.y + p.h);
        ctx.closePath();

        if (obj.filled) {
            ctx.fillStyle = obj.color || '#111827';

            if (isPreview) {
                ctx.globalAlpha = 0.75;
            }

            ctx.fill();
        }

        ctx.strokeStyle = obj.color || '#111827';
        ctx.lineWidth = isPreview ? 1.2 : 1.8;
        ctx.stroke();

        ctx.restore();

        if (isSelected) {
            const minX = Math.min(
                p.x,
                p.x + p.skew,
                p.x + p.w,
                p.x + p.w - p.skew
            );

            const maxX = Math.max(
                p.x,
                p.x + p.skew,
                p.x + p.w,
                p.x + p.w - p.skew
            );

            drawSelectionRect(minX - 6, p.y - 6, (maxX - minX) + 12, p.h + 12);
        }
    }

    
    function getTriangleData(obj) {
        const left = Math.min(obj.x1, obj.x2);
        const right = Math.max(obj.x1, obj.x2);
        const top = Math.min(obj.y1, obj.y2);
        const bottom = Math.max(obj.y1, obj.y2);

        const topX = (left + right) / 2;
        const topY = top;

        const leftX = left;
        const leftY = bottom;

        const rightX = right;
        const rightY = bottom;

        return {
            left,
            right,
            top,
            bottom,
            topX,
            topY,
            leftX,
            leftY,
            rightX,
            rightY
        };
    }

    function drawTriangle(obj, isSelected, isPreview) {
        const t = getTriangleData(obj);

        ctx.save();
        strokeStyleFor('rect', isPreview, obj.color || '#111827');

        ctx.beginPath();
        ctx.moveTo(t.topX, t.topY);
        ctx.lineTo(t.leftX, t.leftY);
        ctx.lineTo(t.rightX, t.rightY);
        ctx.closePath();

        if (obj.filled) {
            ctx.fillStyle = obj.color || '#111827';

            if (isPreview) {
                ctx.globalAlpha = 0.75;
            }

            ctx.fill();
        }

        ctx.strokeStyle = obj.color || '#111827';
        ctx.lineWidth = isPreview ? 1.2 : 1.8;
        ctx.stroke();

        ctx.restore();

        if (isSelected) {
            drawSelectionRect(
                t.left - 6,
                t.top - 6,
                (t.right - t.left) + 12,
                (t.bottom - t.top) + 12
            );
        }
    }
            


    function getSquareData(obj) {
        const dx = obj.x2 - obj.x1;
        const dy = obj.y2 - obj.y1;
        const side = Math.max(Math.abs(dx), Math.abs(dy));

        const x = dx >= 0 ? obj.x1 : obj.x1 - side;
        const y = dy >= 0 ? obj.y1 : obj.y1 - side;

        return { x, y, side };
    }

    function drawSquare(obj, isSelected, isPreview) {
        ctx.save();
        strokeStyleFor('rect', isPreview, obj.color || '#111827');

        const s = getSquareData(obj);

        if (obj.filled) {
            ctx.fillStyle = obj.color || '#111827';

            if (isPreview) {
                ctx.globalAlpha = 0.75;
            }

            ctx.fillRect(s.x, s.y, s.side, s.side);
        }

        ctx.strokeStyle = obj.color || '#111827';
        ctx.lineWidth = isPreview ? 1.2 : 1.8;
        ctx.strokeRect(s.x, s.y, s.side, s.side);

        ctx.restore();

        if (isSelected) {
            drawSelectionRect(s.x - 6, s.y - 6, s.side + 12, s.side + 12);
        }
    }

    function drawFilledSquare(obj, isSelected, isPreview) {
        ctx.save();
        strokeStyleFor('rect', isPreview, obj.color || '#111827');

        const s = getSquareData(obj);

        ctx.fillStyle = obj.color || '#111827';
        if (isPreview) ctx.globalAlpha = 0.75;
        ctx.fillRect(s.x, s.y, s.side, s.side);

        ctx.strokeStyle = obj.color || '#111827';
        ctx.lineWidth = 1.5;
        ctx.strokeRect(s.x, s.y, s.side, s.side);

        ctx.restore();
        if (isSelected) {
            drawSelectionRect(s.x - 6, s.y - 6, s.side + 12, s.side + 12);
        }
    }

    function getCircleData(obj) {
        const dx = obj.x2 - obj.x1;
        const dy = obj.y2 - obj.y1;
        const radius = Math.max(Math.abs(dx), Math.abs(dy)) / 2;

        const cx = (obj.x1 + obj.x2) / 2;
        const cy = (obj.y1 + obj.y2) / 2;

        return { cx, cy, radius };
    }

    function drawCircle(obj, isSelected, isPreview) {
        ctx.save();
        strokeStyleFor('line', isPreview, obj.color || '#111827');

        const c = getCircleData(obj);

        ctx.beginPath();
        ctx.arc(c.cx, c.cy, c.radius, 0, Math.PI * 2);

        if (obj.filled) {
            ctx.fillStyle = obj.fillColor || obj.color || '#111827';

            if (isPreview) {
                ctx.globalAlpha = 0.75;
            } else {
                ctx.globalAlpha = obj.opacity ?? 1;
            }

            ctx.fill();
        }

        ctx.strokeStyle = obj.color || '#111827';
        ctx.lineWidth = obj.lineWidth || (isPreview ? 1.2 : 1.8);
        ctx.globalAlpha = isPreview ? 0.75 : (obj.opacity ?? 1);
        ctx.stroke();

        ctx.restore();

        if (isSelected) {
            drawSelectionRect(
                c.cx - c.radius - 6,
                c.cy - c.radius - 6,
                c.radius * 2 + 12,
                c.radius * 2 + 12
            );
        }
    }

    function drawFilledCircle(obj, isSelected, isPreview) {
        ctx.save();
        strokeStyleFor('line', isPreview, obj.color || '#111827');

        const c = getCircleData(obj);

        ctx.beginPath();
        ctx.arc(c.cx, c.cy, c.radius, 0, Math.PI * 2);
        ctx.fillStyle = obj.color || '#111827';
        if (isPreview) ctx.globalAlpha = 0.75;
        ctx.fill();
        ctx.strokeStyle = obj.color || '#111827';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        ctx.restore();
        if (isSelected) {
            drawSelectionRect(c.cx - c.radius - 6, c.cy - c.radius - 6, c.radius * 2 + 12, c.radius * 2 + 12);
        }
    }

    function drawArrow(obj, isSelected, isPreview) {
        ctx.save();
        strokeStyleFor('arrow', isPreview, obj.color || '#111827');

        ctx.beginPath();
        ctx.moveTo(obj.x1, obj.y1);
        ctx.lineTo(obj.x2, obj.y2);
        ctx.stroke();

        drawArrowHead(obj.x1, obj.y1, obj.x2, obj.y2);

        ctx.restore();
        if (isSelected) drawSelectionBox(obj);
    }

    function drawArrowHead(x1, y1, x2, y2) {
        const headLength = 12;
        const angle = Math.atan2(y2 - y1, x2 - x1);

        ctx.beginPath();
        ctx.moveTo(x2, y2);
        ctx.lineTo(
            x2 - headLength * Math.cos(angle - Math.PI / 6),
            y2 - headLength * Math.sin(angle - Math.PI / 6)
        );
        ctx.moveTo(x2, y2);
        ctx.lineTo(
            x2 - headLength * Math.cos(angle + Math.PI / 6),
            y2 - headLength * Math.sin(angle + Math.PI / 6)
        );
        ctx.stroke();
    }

    function drawDimension(obj, isSelected, isPreview) {
        ctx.save();
        strokeStyleFor('dimension', isPreview, obj.color || '#dc2626');

        const x1 = obj.x1;
        const y1 = obj.y1;
        const x2 = obj.x2;
        const y2 = obj.y2;

        const dx = x2 - x1;
        const dy = y2 - y1;
        const len = Math.sqrt(dx * dx + dy * dy);

        if (len < 2) {
            ctx.restore();
            return;
        }

        // çizgiye dik yönde offset
        const offset = 22;
        const nx = -dy / len;
        const ny = dx / len;

        const ax = x1 + nx * offset;
        const ay = y1 + ny * offset;
        const bx = x2 + nx * offset;
        const by = y2 + ny * offset;

        // uzatma çizgileri
        ctx.beginPath();
        ctx.moveTo(x1, y1);
        ctx.lineTo(ax, ay);
        ctx.moveTo(x2, y2);
        ctx.lineTo(bx, by);
        ctx.stroke();

        // ölçü çizgisi
        ctx.beginPath();
        ctx.moveTo(ax, ay);
        ctx.lineTo(bx, by);
        ctx.stroke();

        // ok başları
        drawDimensionArrow(ax, ay, bx, by);
        drawDimensionArrow(bx, by, ax, ay);

        // yazı
        const text = obj.text || buildDimensionText(x1, y1, x2, y2);
        const midX = (ax + bx) / 2;
        const midY = (ay + by) / 2;

        let angle = Math.atan2(by - ay, bx - ax);

        // yazı ters dönmesin
        if (angle > Math.PI / 2 || angle < -Math.PI / 2) {
            angle += Math.PI;
        }

        ctx.save();
        ctx.translate(midX, midY);
        ctx.rotate(angle);
        ctx.font = 'bold 14px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        ctx.fillText(text, 0, -5);
        ctx.restore();

        ctx.restore();

        if (isSelected) {
            drawSelectionBox(obj);
        }
    }

    function drawDimensionArrow(x1, y1, x2, y2) {
        const headLength = 8;
        const angle = Math.atan2(y2 - y1, x2 - x1);

        ctx.beginPath();
        ctx.moveTo(x1, y1);
        ctx.lineTo(
            x1 + headLength * Math.cos(angle + Math.PI / 6),
            y1 + headLength * Math.sin(angle + Math.PI / 6)
        );
        ctx.moveTo(x1, y1);
        ctx.lineTo(
            x1 + headLength * Math.cos(angle - Math.PI / 6),
            y1 + headLength * Math.sin(angle - Math.PI / 6)
        );
        ctx.stroke();
    }

    function drawText(obj, isSelected) {
        const fontSize = obj.fontSize || 16;
        const fontWeight = obj.fontWeight || 'bold';
        const textAlign = obj.textAlign || 'left';

        ctx.save();
        ctx.globalAlpha = obj.opacity ?? 1;
        ctx.fillStyle = obj.color || '#111827';
        ctx.font = fontWeight + ' ' + fontSize + 'px Arial';
        ctx.textAlign = textAlign;
        ctx.textBaseline = 'alphabetic';
        ctx.translate(obj.x, obj.y);
        ctx.rotate(obj.rotation || 0);
        ctx.fillText(obj.text, 0, 0);
        ctx.restore();

        if (isSelected) {
            ctx.save();
            ctx.font = fontWeight + ' ' + fontSize + 'px Arial';
            const width = ctx.measureText(obj.text || '').width;
            const left = textAlign === 'center' ? obj.x - width / 2 : (textAlign === 'right' ? obj.x - width : obj.x);
            drawSelectionRect(left - 5, obj.y - fontSize - 5, width + 10, fontSize + 12);
            ctx.restore();
        }
    }

    function drawSvgPoly(obj, isSelected, isPreview) {
        if (!Array.isArray(obj.points) || obj.points.length < 2) return;

        ctx.save();
        ctx.globalAlpha = isPreview ? 0.75 : (obj.opacity ?? 1);
        ctx.beginPath();
        ctx.moveTo(obj.points[0].x, obj.points[0].y);
        for (let i = 1; i < obj.points.length; i++) {
            ctx.lineTo(obj.points[i].x, obj.points[i].y);
        }
        if (obj.closed) ctx.closePath();

        if (obj.filled) {
            ctx.fillStyle = obj.fillColor || obj.color || '#111827';
            ctx.fill();
        }

        if (obj.color && obj.color !== 'none') {
            ctx.strokeStyle = obj.color;
            ctx.lineWidth = obj.lineWidth || 1.5;
            ctx.stroke();
        }
        ctx.restore();

        if (isSelected) {
            const b = getPolyBounds(obj.points);
            drawSelectionRect(b.x - 6, b.y - 6, b.w + 12, b.h + 12);
        }
    }

    function drawSvgPath(obj, isSelected, isPreview) {
        if (!obj.d || typeof Path2D === 'undefined') return;

        try {
            const path = new Path2D(obj.d);
            ctx.save();
            ctx.globalAlpha = isPreview ? 0.75 : (obj.opacity ?? 1);
            ctx.translate(obj.x || 0, obj.y || 0);
            ctx.scale(obj.scaleX || 1, obj.scaleY || 1);

            if (obj.filled) {
                ctx.fillStyle = obj.fillColor || obj.color || '#111827';
                ctx.fill(path);
            }

            if (obj.color && obj.color !== 'none') {
                ctx.strokeStyle = obj.color;
                ctx.lineWidth = obj.originalLineWidth || 1.5;
                ctx.stroke(path);
            }
            ctx.restore();
        } catch (e) {
            console.warn('SVG path çizilemedi:', e);
        }

        if (isSelected && obj.bounds) {
            drawSelectionRect(obj.bounds.x - 6, obj.bounds.y - 6, obj.bounds.w + 12, obj.bounds.h + 12);
        }
    }

    function getPolyBounds(points) {
        const xs = points.map(point => point.x);
        const ys = points.map(point => point.y);
        return {
            x: Math.min(...xs),
            y: Math.min(...ys),
            w: Math.max(...xs) - Math.min(...xs),
            h: Math.max(...ys) - Math.min(...ys)
        };
    }

    function drawSelectionBox(obj) {
        if (obj.type === 'text') return;

        if (obj.type === 'svg_poly' && Array.isArray(obj.points)) {
            const b = getPolyBounds(obj.points);
            drawSelectionRect(b.x - 6, b.y - 6, b.w + 12, b.h + 12);
            return;
        }

        if (obj.type === 'svg_path') {
            if (obj.bounds) {
                drawSelectionRect(obj.bounds.x - 6, obj.bounds.y - 6, obj.bounds.w + 12, obj.bounds.h + 12);
            }
            return;
        }

        if (obj.type === 'parallelogram') {
            const p = getParallelogramData(obj);

            const minX = Math.min(
                p.x,
                p.x + p.skew,
                p.x + p.w,
                p.x + p.w - p.skew
            );

            const maxX = Math.max(
                p.x,
                p.x + p.skew,
                p.x + p.w,
                p.x + p.w - p.skew
            );

            drawSelectionRect(minX - 6, p.y - 6, (maxX - minX) + 12, p.h + 12);
            return;
        }
        if (obj.type === 'triangle') {
            const t = getTriangleData(obj);

            drawSelectionRect(
                t.left - 6,
                t.top - 6,
                (t.right - t.left) + 12,
                (t.bottom - t.top) + 12
            );

            return;
        }

        if (obj.type === 'square' || obj.type === 'filled_square') {
            const s = getSquareData(obj);
            drawSelectionRect(s.x - 6, s.y - 6, s.side + 12, s.side + 12);
            return;
        }

        if (obj.type === 'circle' || obj.type === 'filled_circle') {
            const c = getCircleData(obj);
            drawSelectionRect(
                c.cx - c.radius - 6,
                c.cy - c.radius - 6,
                c.radius * 2 + 12,
                c.radius * 2 + 12
            );
            return;
        }

        const x = Math.min(obj.x1, obj.x2);
        const y = Math.min(obj.y1, obj.y2);
        const w = Math.abs(obj.x2 - obj.x1);
        const h = Math.abs(obj.y2 - obj.y1);

        drawSelectionRect(x - 6, y - 6, w + 12, h + 12);
    }
    function drawSelectionRect(x, y, w, h) {
        ctx.save();
        ctx.strokeStyle = '#2563eb';
        ctx.lineWidth = 1;
        ctx.setLineDash([6, 4]);
        ctx.strokeRect(x, y, w, h);
        ctx.restore();
    }

    function hitTest(px, py) {
        for (let i = objects.length - 1; i >= 0; i--) {
            const obj = objects[i];

            // YAZI SEÇME / SİLME
            if (obj.type === 'text') {
                const fontSize = obj.fontSize || 16;
                const fontWeight = obj.fontWeight || 'bold';
                const textAlign = obj.textAlign || 'left';
                ctx.font = fontWeight + ' ' + fontSize + 'px Arial';
                const w = ctx.measureText(obj.text || '').width;

                const cos = Math.cos(-(obj.rotation || 0));
                const sin = Math.sin(-(obj.rotation || 0));
                const relX = px - obj.x;
                const relY = py - obj.y;
                const localX = relX * cos - relY * sin;
                const localY = relX * sin + relY * cos;
                const left = textAlign === 'center' ? -w / 2 : (textAlign === 'right' ? -w : 0);

                if (
                    localX >= left - 8 &&
                    localX <= left + w + 8 &&
                    localY >= -fontSize - 8 &&
                    localY <= 10
                ) {
                    return i;
                }
            }

            // DİKDÖRTGEN SEÇME / SİLME
            if (obj.type === 'rect') {
                const x = Math.min(obj.x1, obj.x2);
                const y = Math.min(obj.y1, obj.y2);
                const w = Math.abs(obj.x2 - obj.x1);
                const h = Math.abs(obj.y2 - obj.y1);

                if (
                    px >= x - 8 &&
                    px <= x + w + 8 &&
                    py >= y - 8 &&
                    py <= y + h + 8
                ) {
                    return i;
                }
            }
            if (obj.type === 'parallelogram') {
                const p = getParallelogramData(obj);

                const minX = Math.min(p.x, p.x + p.skew, p.x + p.w, p.x + p.w - p.skew);
                const maxX = Math.max(p.x, p.x + p.skew, p.x + p.w, p.x + p.w - p.skew);

                if (
                    px >= minX - 8 &&
                    px <= maxX + 8 &&
                    py >= p.y - 8 &&
                    py <= p.y + p.h + 8
                ) {
                    return i;
                }
            }
            if (obj.type === 'triangle') {
                const t = getTriangleData(obj);

                const d1 = pointToSegmentDistance(px, py, t.topX, t.topY, t.leftX, t.leftY);
                const d2 = pointToSegmentDistance(px, py, t.leftX, t.leftY, t.rightX, t.rightY);
                const d3 = pointToSegmentDistance(px, py, t.rightX, t.rightY, t.topX, t.topY);

                if (d1 <= 10 || d2 <= 10 || d3 <= 10) {
                    return i;
                }
            }

            // KARE / DOLU KARE SEÇME / SİLME
            if (obj.type === 'square' || obj.type === 'filled_square') {
                const s = getSquareData(obj);

                if (
                    px >= s.x - 8 &&
                    px <= s.x + s.side + 8 &&
                    py >= s.y - 8 &&
                    py <= s.y + s.side + 8
                ) {
                    return i;
                }
            }

            // DAİRE / DOLU DAİRE SEÇME / SİLME
            if (obj.type === 'circle' || obj.type === 'filled_circle') {
                const c = getCircleData(obj);
                const dx = px - c.cx;
                const dy = py - c.cy;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist <= c.radius + 8) {
                    return i;
                }
            }

            // ÇİZGİ / OK SEÇME / SİLME
            if (obj.type === 'line' || obj.type === 'arrow') {
                const d = pointToSegmentDistance(px, py, obj.x1, obj.y1, obj.x2, obj.y2);

                if (d <= 10) {
                    return i;
                }
            }

            // SVG POLYGON / POLYLINE SEÇME / SİLME
            if (obj.type === 'svg_poly' && Array.isArray(obj.points)) {
                if (obj.filled && pointInPolygon(px, py, obj.points)) {
                    return i;
                }

                for (let p = 0; p < obj.points.length - 1; p++) {
                    const a = obj.points[p];
                    const b = obj.points[p + 1];
                    if (pointToSegmentDistance(px, py, a.x, a.y, b.x, b.y) <= 10) {
                        return i;
                    }
                }

                if (obj.closed && obj.points.length > 2) {
                    const a = obj.points[obj.points.length - 1];
                    const b = obj.points[0];
                    if (pointToSegmentDistance(px, py, a.x, a.y, b.x, b.y) <= 10) {
                        return i;
                    }
                }
            }

            // SVG PATH SEÇME / SİLME
            if (obj.type === 'svg_path' && obj.d && typeof Path2D !== 'undefined') {
                try {
                    const path = new Path2D(obj.d);
                    ctx.save();
                    ctx.translate(obj.x || 0, obj.y || 0);
                    ctx.scale(obj.scaleX || 1, obj.scaleY || 1);
                    ctx.lineWidth = Math.max(8, obj.originalLineWidth || 1.5);

                    const localX = (px - (obj.x || 0)) / (obj.scaleX || 1);
                    const localY = (py - (obj.y || 0)) / (obj.scaleY || 1);
                    const hitStroke = ctx.isPointInStroke(path, localX, localY);
                    const hitFill = obj.filled && ctx.isPointInPath(path, localX, localY);
                    ctx.restore();

                    if (hitStroke || hitFill) return i;
                } catch (e) {
                    // Path2D desteklenmezse aşağıdaki bounds kontrolü kullanılır.
                }

                if (obj.bounds &&
                    px >= obj.bounds.x - 8 && px <= obj.bounds.x + obj.bounds.w + 8 &&
                    py >= obj.bounds.y - 8 && py <= obj.bounds.y + obj.bounds.h + 8) {
                    return i;
                }
            }

            // ÖLÇÜ SEÇME / SİLME
            if (obj.type === 'dimension') {
                const horizontal = Math.abs(obj.x2 - obj.x1) >= Math.abs(obj.y2 - obj.y1);
                const offset = 18;

                if (horizontal) {
                    const yDim = obj.y1 - offset;

                    const dMain = pointToSegmentDistance(px, py, obj.x1, yDim, obj.x2, yDim);
                    const dExt1 = pointToSegmentDistance(px, py, obj.x1, obj.y1, obj.x1, yDim);
                    const dExt2 = pointToSegmentDistance(px, py, obj.x2, obj.y2, obj.x2, yDim);

                    if (dMain <= 12 || dExt1 <= 12 || dExt2 <= 12) {
                        return i;
                    }

                    ctx.font = 'bold 14px Arial';
                    const text = obj.text || buildDimensionText(obj.x1, obj.y1, obj.x2, obj.y2);
                    const textW = ctx.measureText(text).width;
                    const textX = (obj.x1 + obj.x2) / 2;
                    const textY = yDim - 6;

                    if (
                        px >= textX - textW / 2 - 8 &&
                        px <= textX + textW / 2 + 8 &&
                        py >= textY - 18 &&
                        py <= textY + 8
                    ) {
                        return i;
                    }
                } else {
                    const xDim = obj.x1 + offset;

                    const dMain = pointToSegmentDistance(px, py, xDim, obj.y1, xDim, obj.y2);
                    const dExt1 = pointToSegmentDistance(px, py, obj.x1, obj.y1, xDim, obj.y1);
                    const dExt2 = pointToSegmentDistance(px, py, obj.x2, obj.y2, xDim, obj.y2);

                    if (dMain <= 12 || dExt1 <= 12 || dExt2 <= 12) {
                        return i;
                    }

                    ctx.font = 'bold 14px Arial';
                    const text = obj.text || buildDimensionText(obj.x1, obj.y1, obj.x2, obj.y2);
                    const textW = ctx.measureText(text).width;
                    const textX = xDim + 18;
                    const textY = (obj.y1 + obj.y2) / 2;

                    if (
                        px >= textX - 18 &&
                        px <= textX + 18 &&
                        py >= textY - textW / 2 - 8 &&
                        py <= textY + textW / 2 + 8
                    ) {
                        return i;
                    }
                }
            }
        }

        return -1;
    }

    function pointInPolygon(x, y, points) {
        let inside = false;
        for (let i = 0, j = points.length - 1; i < points.length; j = i++) {
            const xi = points[i].x;
            const yi = points[i].y;
            const xj = points[j].x;
            const yj = points[j].y;

            const intersects = ((yi > y) !== (yj > y)) &&
                (x < (xj - xi) * (y - yi) / ((yj - yi) || 0.000001) + xi);
            if (intersects) inside = !inside;
        }
        return inside;
    }

    function pointToSegmentDistance(px, py, x1, y1, x2, y2) {
        const A = px - x1;
        const B = py - y1;
        const C = x2 - x1;
        const D = y2 - y1;

        const dot = A * C + B * D;
        const lenSq = C * C + D * D;
        let param = -1;

        if (lenSq !== 0) {
            param = dot / lenSq;
        }

        let xx, yy;

        if (param < 0) {
            xx = x1;
            yy = y1;
        } else if (param > 1) {
            xx = x2;
            yy = y2;
        } else {
            xx = x1 + param * C;
            yy = y1 + param * D;
        }

        const dx = px - xx;
        const dy = py - yy;
        return Math.sqrt(dx * dx + dy * dy);
    }

    function buildDimensionText(x1, y1, x2, y2) {
        const horizontal = Math.abs(x2 - x1) >= Math.abs(y2 - y1);
        const value = horizontal ? Math.abs(x2 - x1) : Math.abs(y2 - y1);
        return Math.round(value).toString();
    }

    window.resizeManualCanvas = function () {
        renderAll();
    };

    document.addEventListener('DOMContentLoaded', initCadEditor);
})();