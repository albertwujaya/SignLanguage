"""Rekam klip gerakan dinamis -> ml/data/clips/<label>/<nama>_<waktu>.npz

Yang disimpan HANYA urutan titik tangan (angka), TIDAK ada foto atau video.
Jadi aman dibagikan ke teman dan boleh di-commit.

Pakai:  python ml/scripts/record_clips.py [label,label,...] [nama_orang] [detik] [kamera]
Contoh: python ml/scripts/record_clips.py terima-kasih,selamat-datang,diam nahda 3
Tombol: SPASI = rekam satu klip (hitung mundur 3 detik, lalu rekam)
        N / B = label berikutnya / sebelumnya,  Q = keluar
Pakai slug yang sama dengan kartu di admin sebagai label (mis. terima-kasih).
Label "diam" = tangan terlihat tapi TIDAK melakukan gerakan target.
"""
import math
import re
import sys
import time
from pathlib import Path

import cv2
import mediapipe as mp
import numpy as np
from mediapipe.tasks import python as mp_python
from mediapipe.tasks.python import vision

from dyn_common import MIN_FRAMES, frame_features

ROOT = Path(__file__).resolve().parents[2]
MODEL = ROOT / "ml" / "models" / "hand_landmarker.task"
CLIPS = ROOT / "ml" / "data" / "clips"


def slug(s):
    return re.sub(r"[^a-z0-9-]+", "-", s.strip().lower()).strip("-")


LABELS = [slug(x) for x in (sys.argv[1] if len(sys.argv) > 1 else "diam").split(",") if slug(x)]
PERSON = slug(sys.argv[2]) if len(sys.argv) > 2 else "p1"
DUR = float(sys.argv[3]) if len(sys.argv) > 3 else 3.0
CAM = int(sys.argv[4]) if len(sys.argv) > 4 else 0
if not LABELS:
    sys.exit("Label kosong. Contoh: python ml/scripts/record_clips.py terima-kasih,diam nahda 3")
PERSON = PERSON.replace("_", "-")  # '_' dipakai sebagai pemisah di nama file

landmarker = vision.HandLandmarker.create_from_options(
    vision.HandLandmarkerOptions(
        base_options=mp_python.BaseOptions(model_asset_path=str(MODEL)),
        running_mode=vision.RunningMode.VIDEO,
        num_hands=2,
    )
)
cap = cv2.VideoCapture(CAM)
t0 = time.monotonic()
idx = 0
state, count_end, rec_end = "idle", 0.0, 0.0
buf_shape, buf_cent, buf_has, buf_t = [], [], [], []
msg, msg_until = "", 0.0


def folder(label):
    d = CLIPS / label
    d.mkdir(parents=True, exist_ok=True)
    return d


def n_clips(label):
    return len(list(folder(label).glob("*.npz")))


def put(img, text, y, scale=0.7):
    cv2.putText(img, text, (20, y), cv2.FONT_HERSHEY_SIMPLEX, scale, (0, 0, 0), 4)
    cv2.putText(img, text, (20, y), cv2.FONT_HERSHEY_SIMPLEX, scale, (255, 255, 255), 2)


def save_clip(label):
    hands = int(np.sum(buf_has))
    if hands < MIN_FRAMES:
        return f"GAGAL: tangan terlihat cuma {hands} frame, klip tidak disimpan"
    path = folder(label) / f"{PERSON}_{int(time.time() * 1000)}.npz"
    np.savez_compressed(
        path,
        shape=np.array(buf_shape, np.float32),
        centers=np.array(buf_cent, np.float32),
        has=np.array(buf_has, bool),
        t=np.array(buf_t, np.float32),
    )
    return f"Tersimpan ({hands} frame bertangan)"


while True:
    ok, frame = cap.read()
    if not ok:
        print("Kamera tidak terbaca. Coba nomor kamera lain (argumen ke-4, mis. 1).")
        break
    now = time.monotonic()
    rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)  # frame TIDAK dibalik, sama seperti browser
    res = landmarker.detect_for_video(
        mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb), int((now - t0) * 1000)
    )
    label = LABELS[idx]

    if state == "count" and now >= count_end:
        state, rec_end = "rec", now + DUR
        buf_shape, buf_cent, buf_has, buf_t = [], [], [], []
    if state == "rec":
        s, c, h = frame_features(res)
        buf_shape.append(s); buf_cent.append(c); buf_has.append(h)
        buf_t.append(now - (rec_end - DUR))
        if now >= rec_end:
            msg, msg_until = save_clip(label), now + 2.5
            state = "idle"

    view = cv2.flip(frame, 1)  # cermin hanya untuk tampilan
    hh, ww = view.shape[:2]
    for hand in res.hand_landmarks:
        for p in hand:
            cv2.circle(view, (int((1 - p.x) * ww), int(p.y * hh)), 3, (0, 255, 0), -1)

    put(view, f"GERAKAN: {label} | klip di folder: {n_clips(label)}", 35, 0.65)
    if state == "idle":
        put(view, "SPASI = rekam klip", 70)
    elif state == "count":
        put(view, str(math.ceil(count_end - now)), 170, 3)
        put(view, "Siap-siap. SPASI = batal", 70, 0.6)
    else:
        left = max(0.0, rec_end - now)
        put(view, f"MEREKAM {left:0.1f}s - lakukan gerakannya", 70)
    if msg and now < msg_until:
        put(view, msg, 105, 0.6)
    put(view, "N/B = label lain | Q = keluar", hh - 15, 0.5)
    cv2.imshow("Rekam klip gerakan", view)

    key = cv2.waitKey(1) & 0xFF
    if key == ord("q"):
        break
    if state in ("count", "rec"):
        if key == ord(" "):
            state = "idle"
        continue
    if key == ord(" "):
        state, count_end = "count", now + 3
    elif key == ord("n") and idx + 1 < len(LABELS):
        idx += 1
    elif key == ord("b") and idx > 0:
        idx -= 1

cap.release()
cv2.destroyAllWindows()
