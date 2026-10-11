"""Latih model huruf atau angka dari landmarks CSV (126 fitur + label).

Pakai:
  python ml/scripts/train.py                  -> model HURUF (A-Z)  -> public/models/letter_model.json
  python ml/scripts/train.py angka            -> model ANGKA (1,2,..) -> public/models/number_model.json
Opsi:
  --csv  ml/data/landmarks_all.csv     file data (default: ml/data/landmarks.csv)
  --test ml/data/landmarks_kawan.csv   uji pada data ORANG LAIN (hasil lebih jujur)
Dengan --test, model dilatih dari --csv lalu dinilai pada --test.
Tanpa --test, 20% data diambil acak untuk uji (cenderung terlalu optimis, lihat catatan).
"""
import json
import sys
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.metrics import classification_report
from sklearn.model_selection import train_test_split
from sklearn.neural_network import MLPClassifier
from sklearn.preprocessing import StandardScaler

ROOT = Path(__file__).resolve().parents[2]  # ml/scripts/train.py -> root proyek
args = sys.argv[1:]


def opt(name, default=None):
    if name in args:
        i = args.index(name)
        return args[i + 1] if i + 1 < len(args) else default
    return default


MODE = "angka" if "angka" in args else "huruf"
CSV = Path(opt("--csv", ROOT / "ml" / "data" / "landmarks.csv"))
TEST = opt("--test")
OUT = ROOT / "public" / "models" / ("number_model.json" if MODE == "angka" else "letter_model.json")
OUT.parent.mkdir(parents=True, exist_ok=True)


def load(path):
    df = pd.read_csv(path, dtype={"label": str}).dropna()
    df["label"] = df["label"].str.strip().str.upper()
    keep = df["label"].str.isdigit() if MODE == "angka" else df["label"].str.isalpha()
    df = df[keep]
    feats = df.drop(columns=["label"])
    assert feats.shape[1] == 126, "Harus 126 kolom fitur (tangan kiri 63 + kanan 63). Cek extract.py."
    return feats.values.astype(np.float32), df["label"].values


X, y = load(CSV)
print(f"mode: {MODE} | data: {CSV.name} | baris: {len(X)}")
print(pd.Series(y).value_counts().sort_index().to_string())
if len(set(y)) < 2:
    sys.exit(f"Butuh minimal 2 label untuk mode '{MODE}'. Cek isi {CSV.name}.")
both = ((np.abs(X[:, :63]).sum(1) > 0) & (np.abs(X[:, 63:]).sum(1) > 0)).mean()
print(f"baris dua tangan: {both:.0%}")

CLASSES = sorted(set(y), key=lambda s: (len(s), s))  # 1,2,..,10 (bukan 1,10,2)
yi = np.array([CLASSES.index(v) for v in y])  # label sebagai angka (aman untuk semua versi sklearn)

flip = np.array([-1, 1, 1] * 21, dtype=np.float32)


def fit(Xs, ys):
    # Augmentasi cermin: x dibalik dan slot kiri/kanan ditukar (untuk pengguna kidal)
    Xm = np.hstack([Xs[:, 63:] * flip, Xs[:, :63] * flip])
    Xa, ya = np.vstack([Xs, Xm]), np.concatenate([ys, ys])
    scaler = StandardScaler().fit(Xa)
    clf = MLPClassifier(
        hidden_layer_sizes=(64, 32), activation="relu", max_iter=500,
        early_stopping=len(Xa) >= 200, random_state=42,
    ).fit(scaler.transform(Xa), ya)
    return scaler, clf


def report(clf, scaler, Xt, yt_idx, judul):
    pred = clf.predict(scaler.transform(Xt))
    present = sorted(set(yt_idx))
    print(f"\n=== {judul} ===")
    print(classification_report(yt_idx, pred, labels=present,
                                target_names=[CLASSES[i] for i in present], zero_division=0))


if TEST:
    Xt, yt = load(TEST)
    yt_idx = np.array([CLASSES.index(v) for v in yt if v in CLASSES])
    Xt = Xt[[v in CLASSES for v in yt]]
    scaler, clf = fit(X, yi)
    report(clf, scaler, Xt, yt_idx, f"UJI pada {Path(TEST).name} (orang lain)")
else:
    Xtr, Xte, ytr, yte = train_test_split(X, yi, test_size=0.2, stratify=yi, random_state=42)
    scaler, clf = fit(Xtr, ytr)
    report(clf, scaler, Xte, yte, "UJI acak 20% (cenderung terlalu optimis: foto berurutan hampir kembar)")
    print("Tip: uji pada data orang lain dengan --test untuk angka yang lebih jujur.")
    scaler, clf = fit(X, yi)  # model akhir dilatih dari SEMUA data

model = {
    "labels": CLASSES,
    "mean": np.round(scaler.mean_.astype(np.float64), 6).tolist(),
    "scale": np.round(scaler.scale_.astype(np.float64), 6).tolist(),
    "layers": [
        # astype(float64) dulu: float32 yang di-round tercetak panjang (file jadi 2x lebih besar)
        {"W": np.round(w.astype(np.float64), 5).tolist(), "b": np.round(b.astype(np.float64), 5).tolist()}
        for w, b in zip(clf.coefs_, clf.intercepts_)
    ],
}
OUT.write_text(json.dumps(model))
print(f"\nmodel {MODE} tersimpan di {OUT} ({OUT.stat().st_size / 1024:.0f} KB)")