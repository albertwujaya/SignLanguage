import json
import numpy as np
import pandas as pd
from pathlib import Path
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.neural_network import MLPClassifier
from sklearn.metrics import classification_report

ROOT = Path(__file__).resolve().parents[2]  # ml/scripts/train.py -> root proyek
CSV = ROOT / "ml" / "data" / "landmarks.csv"
OUT = ROOT / "public" / "models" / "letter_model.json"
OUT.parent.mkdir(parents=True, exist_ok=True)

df = pd.read_csv(CSV).dropna()
feats = df.drop(columns=["label"])
print("jumlah baris:", len(df), "| jumlah fitur:", feats.shape[1])
assert feats.shape[1] == 126, "Harus 126 kolom fitur (tangan kiri 63 + kanan 63). Cek extract.py."

X = feats.values.astype(np.float32)  # sudah dinormalisasi oleh extract.py
both = ((np.abs(X[:, :63]).sum(1) > 0) & (np.abs(X[:, 63:]).sum(1) > 0)).mean()
print(f"baris dua tangan: {both:.0%}")
y = df["label"].astype(str).str.upper().values

Xtr, Xte, ytr, yte = train_test_split(X, y, test_size=0.2, stratify=y, random_state=42)

# Augmentasi cermin: x dibalik dan slot kiri/kanan ditukar
flip = np.array([-1, 1, 1] * 21, dtype=np.float32)
Xm = np.hstack([Xtr[:, 63:] * flip, Xtr[:, :63] * flip])
Xtr = np.vstack([Xtr, Xm])
ytr = np.concatenate([ytr, ytr])

scaler = StandardScaler().fit(Xtr)
clf = MLPClassifier(
    hidden_layer_sizes=(64, 32),
    activation="relu",
    max_iter=500,
    early_stopping=True,
    random_state=42,
).fit(scaler.transform(Xtr), ytr)

print(classification_report(yte, clf.predict(scaler.transform(Xte)), zero_division=0))

model = {
    "labels": [str(c) for c in clf.classes_],
    "mean": np.round(scaler.mean_, 6).tolist(),
    "scale": np.round(scaler.scale_, 6).tolist(),
    "layers": [
        {"W": np.round(w, 5).tolist(), "b": np.round(b, 5).tolist()}
        for w, b in zip(clf.coefs_, clf.intercepts_)
    ],
}
OUT.write_text(json.dumps(model))
print("model tersimpan di", OUT)