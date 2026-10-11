import cv2, numpy as np, pandas as pd
from pathlib import Path
import mediapipe as mp
from mediapipe.tasks import python as mp_python
from mediapipe.tasks.python import vision

OWN_EXTERNAL = Path(__file__).resolve().parents[2].parent / "SignLanguage_dataset" / "own"
SRC = [Path("ml/dataset/raw"), Path("ml/dataset/own"), OWN_EXTERNAL]
OUT = Path("ml/data/landmarks.csv")
MODEL = "ml/models/hand_landmarker.task"

detector = vision.HandLandmarker.create_from_options(
    vision.HandLandmarkerOptions(
        base_options=mp_python.BaseOptions(model_asset_path=MODEL),
        running_mode=vision.RunningMode.IMAGE,
        num_hands=2,
        min_hand_detection_confidence=0.5,
    )
)

def extract(res):
    if not res.hand_landmarks:
        return None
    pts = [np.array([[p.x, p.y, p.z] for p in h]) for h in res.hand_landmarks]
    allp = np.concatenate(pts)
    center = allp.mean(axis=0)
    scale = np.abs(allp[:, :2] - center[:2]).max() or 1
    slots = {"Left": np.zeros(63), "Right": np.zeros(63)}
    for p, hd in zip(pts, res.handedness):
        slots[hd[0].category_name] = ((p - center) / scale).flatten()
    return np.concatenate([slots["Left"], slots["Right"]])

rows, skipped, n = [], 0, 0
for root in SRC:
    if not root.exists():
        continue
    for folder in sorted(p for p in root.iterdir() if p.is_dir()):
        for img_path in folder.glob("*"):
            img = cv2.imread(str(img_path))
            if img is None:
                continue
            n += 1
            if n % 200 == 0:
                print(f"{n} foto diproses, {len(rows)} terdeteksi", flush=True)
            h, w = img.shape[:2]
            if max(h, w) > 960:
                s = 960 / max(h, w)
                img = cv2.resize(img, (int(w * s), int(h * s)))
            rgb = np.ascontiguousarray(cv2.cvtColor(img, cv2.COLOR_BGR2RGB))
            res = detector.detect(mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb))
            feat = extract(res)
            if feat is None:
                skipped += 1
                continue
            rows.append([*feat, folder.name])

df = pd.DataFrame(rows, columns=[f"f{i}" for i in range(126)] + ["label"])
OUT.parent.mkdir(parents=True, exist_ok=True)
df.to_csv(OUT, index=False)
print(df["label"].value_counts().sort_index())
print("foto tanpa tangan terdeteksi:", skipped)