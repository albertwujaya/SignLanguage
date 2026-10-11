/* Pengenal gerakan dinamis. Model dibuat oleh ml/scripts/train_dynamic.py.
 * Logika fitur HARUS sama dengan ml/scripts/dyn_common.py.
 *
 * Pemakaian:
 *   await DynamicGesture.load('/models/dynamic_model.json');
 *   DynamicGesture.reset();                 // setelah hitung mundur selesai
 *   DynamicGesture.push(res);               // tiap frame, res = hasil lm.detectForVideo(...)
 *   const r = DynamicGesture.predict();     // setelah klip selesai (~3 detik)
 *   // r = { label, p, probs } atau null kalau tangan hampir tak terlihat
 */
(function (root) {
    'use strict';
    const MIN_FRAMES = 8;
    let model = null;
    let frames = [];

    function frameFeatures(res) {
        const hands = res && res.landmarks ? res.landmarks : [];
        if (!hands.length) return { shape: new Array(126).fill(0), centers: [0, 0, 0, 0], has: false };
        const sides = res.handedness || res.handednesses;
        const all = hands.flat();
        let cx = 0, cy = 0, cz = 0;
        all.forEach(p => { cx += p.x; cy += p.y; cz += p.z; });
        cx /= all.length; cy /= all.length; cz /= all.length;
        let scale = 0;
        all.forEach(p => { scale = Math.max(scale, Math.abs(p.x - cx), Math.abs(p.y - cy)); });
        if (!scale) scale = 1;
        const slots = { Left: new Array(63).fill(0), Right: new Array(63).fill(0) };
        const pos = { Left: [0, 0], Right: [0, 0] };
        hands.forEach((h, k) => {
            const flat = [];
            let mx = 0, my = 0;
            h.forEach(p => { flat.push((p.x - cx) / scale, (p.y - cy) / scale, (p.z - cz) / scale); mx += p.x; my += p.y; });
            const side = sides[k][0].categoryName;
            slots[side] = flat;
            pos[side] = [mx / h.length, my / h.length];
        });
        return { shape: slots.Left.concat(slots.Right), centers: pos.Left.concat(pos.Right), has: true };
    }

    function clipFeatures(fr) {
        const T = model.T;
        const a = fr.findIndex(f => f.has);
        if (a < 0) return null;
        let b = fr.length - 1;
        while (!fr[b].has) b--;
        b += 1;
        if (b - a < MIN_FRAMES) return null;
        const seq = fr.slice(a, b).map(f => f.shape.concat(f.centers)); // (n, 130)
        const n = seq.length;
        const rs = [];
        for (let k = 0; k < T; k++) {
            const idx = k * (n - 1) / (T - 1);
            const lo = Math.floor(idx), hi = Math.min(lo + 1, n - 1), w = idx - lo;
            rs.push(seq[lo].map((v, i) => v * (1 - w) + seq[hi][i] * w));
        }
        const out = [];
        for (let k = 0; k < T; k++) {
            for (let i = 0; i < 130; i++) out.push(rs[k][i]);
            for (let i = 126; i < 130; i++) out.push(k === 0 ? 0 : rs[k][i] - rs[k - 1][i]);
        }
        return out;
    }

    function forward(x) {
        const m = model;
        let a = x.map((v, i) => (v - m.mean[i]) / m.scale[i]);
        m.layers.forEach((L, li) => {
            const out = L.b.slice();
            for (let i = 0; i < a.length; i++) {
                const ai = a[i];
                if (ai === 0) continue;
                const row = L.W[i];
                for (let j = 0; j < out.length; j++) out[j] += ai * row[j];
            }
            a = li < m.layers.length - 1 ? out.map(v => Math.max(0, v)) : out;
        });
        const mx = Math.max(...a);
        const ex = a.map(v => Math.exp(v - mx));
        const sum = ex.reduce((s, v) => s + v, 0);
        return ex.map(v => v / sum);
    }

    const api = {
        ready: false,
        load(url) {
            return fetch(url)
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(m => { model = m; api.ready = true; return true; })
                .catch(e => { console.warn('Model gerakan dinamis belum tersedia:', e); return false; });
        },
        setModel(m) { model = m; api.ready = true; },
        reset() { frames = []; },
        push(res) { frames.push(frameFeatures(res)); },
        predict() {
            if (!model) return null;
            const f = clipFeatures(frames);
            if (!f) return null;
            const probs = forward(f);
            let k = 0;
            probs.forEach((v, i) => { if (v > probs[k]) k = i; });
            return { label: model.labels[k], p: probs[k], probs: Object.fromEntries(model.labels.map((l, i) => [l, probs[i]])) };
        },
        _frameFeatures: frameFeatures,
        _clipFeatures: clipFeatures,
    };
    root.DynamicGesture = api;
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
})(typeof window !== 'undefined' ? window : globalThis);
