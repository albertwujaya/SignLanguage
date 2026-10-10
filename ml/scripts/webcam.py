import json
import sys
import time
from collections import Counter, deque

import cv2
import numpy as np
import mediapipe as mp
from mediapipe.tasks import python as mp_python
from mediapipe.tasks.python import vision

MODEL_JSON = "public/models/letter_model.json"
TASK_FILE = "public/models/hand_landmarker.task"
CAM_INDEX = int(sys.argv[1]) if len(sys.argv) > 1 else 0

CONNECTIONS = [
    (0, 1), (1, 2), (2, 3), (3, 4),
    (0, 5), (5, 6), (6, 7), (7, 8),
    (5, 9), (9, 10), (10, 11), (11, 12),
    (9, 13), (13, 14), (14, 15), (15, 16),
    (13, 17), (17, 18), (18, 19), (19, 20), (0, 17),
]

with open(MODEL_JSON) as f:
    M = json.load(f)
LABELS = M["labels"]
MEAN = np.array(M["mean"], dtype=np.float32)
SCALE = np.array(M["scale"], dtype=np.float32)
LAYERS = [(np.array(l["W"], dtype=np.float32), np.array(l["b"], dtype=np.float32)) for l in M["layers"]]


def build_features(res):
    pts = [np.array([[p.x, p.y, p.z] for p in h]) for h in res.hand_landmarks]
    allp = np.concatenate(pts)
    center = allp.mean(axis=0)
    scale = np.abs(allp[:, :2] - center[:2]).max() or 1
    slots = {"Left": np.zeros(63), "Right": np.zeros(63)}
    for p, hd in zip(pts, res.handedness):
        slots[hd[0].category_name] = ((p - center) / scale).flatten()
    return np.concatenate([slots["Left"], slots["Right"]]).astype(np.float32)


def predict(x):
    a = (x - MEAN) / SCALE
    for i, (W, b) in enumerate(LAYERS):
        a = a @ W + b
        if i < len(LAYERS) - 1:
            a = np.maximum(a, 0)
    e = np.exp(a - a.max())
    p = e / e.sum()
    k = int(p.argmax())
    return LABELS[k], float(p[k])

options = vision.HandLandmarkerOptions(
    base_options=mp_python.BaseOptions(model_asset_path=TASK_FILE),
    running_mode=vision.RunningMode.VIDEO,
    num_hands=2,
)
landmarker = vision.HandLandmarker.create_from_options(options)

cap = cv2.VideoCapture(CAM_INDEX)
recent = deque(maxlen=6)
t0 = time.monotonic()

while cap.isOpened():
    ok, frame = cap.read()
    if not ok:
        break

    rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
    img = mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb)
    res = landmarker.detect_for_video(img, int((time.monotonic() - t0) * 1000))

    text = "Tangan belum terlihat"
    if res.hand_landmarks:
        h, w = frame.shape[:2]
        for lms in res.hand_landmarks:
            pts = [(int(l.x * w), int(l.y * h)) for l in lms]
            for a, b in CONNECTIONS:
                cv2.line(frame, pts[a], pts[b], (208, 216, 24), 2)
            for pt in pts:
                cv2.circle(frame, pt, 4, (229, 243, 168), -1)

        recent.append(predict(build_features(res)))
        top = Counter(r[0] for r in recent).most_common(1)[0][0]
        conf = np.mean([r[1] for r in recent if r[0] == top])
        text = f"{top}  {conf * 100:.0f}%"
    else:
        recent.clear()

    cv2.putText(frame, text, (20, 50), cv2.FONT_HERSHEY_SIMPLEX, 1.3, (0, 0, 0), 5)
    cv2.putText(frame, text, (20, 50), cv2.FONT_HERSHEY_SIMPLEX, 1.3, (255, 255, 255), 2)
    cv2.imshow("Tes huruf BISINDO (q untuk keluar)", frame)
    if cv2.waitKey(1) & 0xFF == ord("q"):
        break

cap.release()
cv2.destroyAllWindows()