"""Latih model gerakan dinamis dari klip -> public/models/dynamic_model.json

Pakai:  python ml/scripts/train_dynamic.py [--no-mirror]
Membaca: ml/data/clips/<label>/*.npz   (hasil record_clips.py)
Hasil  : public/models/dynamic_model.json (dibaca browser lewat public/js/dynamic.js)
"""
import json
import sys
from collections import Counter
from pathlib import Path

import numpy as np
from sklearn.metrics import classification_report
from sklearn.model_selection import GroupShuffleSplit, train_test_split
from sklearn.neural_network import MLPClassifier
from sklearn.preprocessing import StandardScaler

from dyn_common import D_FRAME, T, clip_features, mirror_features

ROOT = Path(__file__).resolve().parents[2]
CLIPS = ROOT / "ml" / "data" / "clips"
OUT = ROOT / "public" / "models" / "dynamic_model.json"
MIRROR = "--no-mirror" not in sys.argv
rng = np.random.default_rng(42)

X, y, who, skipped = [], [], [], 0
folders = sorted(p for p in CLIPS.iterdir() if p.is_dir()) if CLIPS.exists() else []
for folder in folders:
    for f in sorted(folder.glob("*.npz")):
        d = np.load(f)
        feat = clip_features(d["shape"], d["centers"], d["has"])
        if feat is None:
            skipped += 1
            continue
        X.append(feat)
        y.append(folder.name)
        who.append(f.stem.rsplit("_", 1)[0])  # nama orang = bagian sebelum '_'
X, y, who = np.array(X), np.array(y), np.array(who)
CLASSES = sorted(set(y))                      # nama label, urut abjad
yi = np.array([CLASSES.index(v) for v in y])  # label sebagai angka (aman untuk semua versi sklearn)

counts = Counter(str(v) for v in y)
print("klip per label:", dict(counts), "| klip gagal/terlalu pendek:", skipped)
print("orang:", sorted(str(v) for v in set(who)))
if len(counts) < 2:
    sys.exit("Butuh minimal 2 label (misal gerakanmu + 'diam').")
for lab, n in counts.items():
    if n < 10:
        print(f"PERINGATAN: label '{lab}' baru {n} klip, idealnya 30+ dari beberapa orang.")


def augment(Xs, ys):
    parts, labels = [Xs], [ys]
    if MIRROR:
        parts.append(np.array([mirror_features(v) for v in Xs])); labels.append(ys)
    parts.append(Xs + rng.normal(0, 0.01, Xs.shape).astype(np.float32)); labels.append(ys)
    return np.vstack(parts), np.concatenate(labels)


def fit(Xs, ys):
    Xa, ya = augment(Xs, ys)
    scaler = StandardScaler().fit(Xa)
    clf = MLPClassifier(
        hidden_layer_sizes=(64, 32), max_iter=600, random_state=42,
        early_stopping=len(Xa) >= 200,
    ).fit(scaler.transform(Xa), ya)
    return scaler, clf


# --- Evaluasi: sebisa mungkin uji pada ORANG yang tidak dipakai melatih ---
try:
    if len(set(who)) >= 2:
        tr, te = next(GroupShuffleSplit(n_splits=1, test_size=0.3, random_state=42).split(X, y, who))
        if set(yi[tr]) != set(yi):
            raise ValueError("ada label yang hilang dari data latih")
        print("\nEvaluasi: uji pada orang yang tidak dipakai melatih (lebih jujur)")
    else:
        tr, te = train_test_split(np.arange(len(X)), test_size=0.25, stratify=yi, random_state=42)
        print("\nEvaluasi: baru 1 orang, hasilnya cenderung terlalu optimis. Tambah data teman.")
    sc, clf = fit(X[tr], yi[tr])
    print(classification_report(yi[te], clf.predict(sc.transform(X[te])), labels=range(len(CLASSES)),
                                target_names=CLASSES, zero_division=0))
except ValueError as e:
    print("\nEvaluasi dilewati:", e)

# --- Model akhir: dilatih dari SEMUA klip, lalu diekspor ---
scaler, clf = fit(X, yi)
model = {
    "T": T,
    "dim": T * D_FRAME,
    "labels": CLASSES,
    "mean": np.round(scaler.mean_.astype(np.float64), 5).tolist(),
    "scale": np.round(scaler.scale_.astype(np.float64), 5).tolist(),
    "layers": [
        # astype(float64) dulu: float32 yang di-round tercetak panjang (ukuran file 2x lipat)
        {"W": np.round(w.astype(np.float64), 5).tolist(), "b": np.round(b.astype(np.float64), 5).tolist()}
        for w, b in zip(clf.coefs_, clf.intercepts_)
    ],
}
OUT.parent.mkdir(parents=True, exist_ok=True)
OUT.write_text(json.dumps(model))
print(f"model tersimpan di {OUT} ({OUT.stat().st_size / 1024:.0f} KB)")
