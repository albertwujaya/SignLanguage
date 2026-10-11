"""Fungsi bersama untuk gerakan dinamis (dipakai record_clips.py dan train_dynamic.py).

Tidak memakai mediapipe/cv2 di sini, supaya bisa dipakai dan diuji tanpa kamera.
PENTING: logika yang sama ada di public/js/dynamic.js. Kalau ini diubah, ubah juga di sana.
"""
import numpy as np

T = 12          # jumlah frame setelah klip diratakan
MIN_FRAMES = 8  # klip dengan tangan terlihat kurang dari ini dianggap gagal
D_FRAME = 134   # 126 bentuk + 4 posisi + 4 kecepatan


def frame_features(res):
    """Hasil MediaPipe satu frame -> (shape126, centers4, ada_tangan).

    shape126  : sama persis dengan extract.py (bentuk tangan, posisi dihilangkan).
    centers4  : posisi pusat tangan kiri dan kanan (x, y) di gambar 0..1, 0 kalau tidak ada.
    """
    if not res.hand_landmarks:
        return np.zeros(126, np.float32), np.zeros(4, np.float32), False
    pts = [np.array([[p.x, p.y, p.z] for p in h]) for h in res.hand_landmarks]
    allp = np.concatenate(pts)
    center = allp.mean(axis=0)
    scale = np.abs(allp[:, :2] - center[:2]).max() or 1
    slots = {"Left": np.zeros(63), "Right": np.zeros(63)}
    pos = {"Left": np.zeros(2), "Right": np.zeros(2)}
    for p, hd in zip(pts, res.handedness):
        side = hd[0].category_name
        slots[side] = ((p - center) / scale).flatten()
        pos[side] = p[:, :2].mean(axis=0)
    shape = np.concatenate([slots["Left"], slots["Right"]]).astype(np.float32)
    centers = np.concatenate([pos["Left"], pos["Right"]]).astype(np.float32)
    return shape, centers, True


def resample(seq, t=T):
    """(n_frame, d) -> (t, d), diratakan merata sepanjang waktu."""
    n = len(seq)
    idx = np.linspace(0, n - 1, t)
    lo = np.floor(idx).astype(int)
    hi = np.minimum(lo + 1, n - 1)
    w = (idx - lo)[:, None]
    return seq[lo] * (1 - w) + seq[hi] * w


def clip_features(shape, centers, has):
    """Urutan frame -> satu vektor panjang tetap (T * 134), atau None kalau klip gagal.

    Frame di awal/akhir yang tidak ada tangannya dibuang, lalu sisanya diratakan ke T frame.
    Isinya: bentuk + posisi + kecepatan posisi.
    """
    has = np.asarray(has, bool)
    idx = np.flatnonzero(has)
    if len(idx) == 0:
        return None
    a, b = idx[0], idx[-1] + 1
    if b - a < MIN_FRAMES:
        return None
    x = np.hstack([np.asarray(shape)[a:b], np.asarray(centers)[a:b]])  # (n, 130)
    x = resample(x)
    vel = np.diff(x[:, 126:], axis=0, prepend=x[:1, 126:])
    return np.hstack([x, vel]).flatten().astype(np.float32)


def mirror_features(f):
    """Versi cermin dari satu vektor klip (untuk augmentasi pengguna kidal)."""
    x = np.asarray(f).reshape(T, D_FRAME)
    sh, ce, ve = x[:, :126], x[:, 126:130], x[:, 130:134]
    flip = np.tile(np.array([-1, 1, 1], np.float32), 21)
    L, R = sh[:, :63], sh[:, 63:]
    pres_l = (np.abs(L).sum(1) > 0)[:, None]
    pres_r = (np.abs(R).sum(1) > 0)[:, None]
    new_sh = np.hstack([R * flip, L * flip])
    new_cl = np.where(pres_r, np.stack([1 - ce[:, 2], ce[:, 3]], 1), 0)
    new_cr = np.where(pres_l, np.stack([1 - ce[:, 0], ce[:, 1]], 1), 0)
    new_vl = np.stack([-ve[:, 2], ve[:, 3]], 1)
    new_vr = np.stack([-ve[:, 0], ve[:, 1]], 1)
    return np.hstack([new_sh, new_cl, new_cr, new_vl, new_vr]).flatten().astype(np.float32)
