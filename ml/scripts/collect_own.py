import math
import re
import sys
import time
from pathlib import Path

import cv2
import mediapipe as mp
from mediapipe.tasks import python as mp_python
from mediapipe.tasks.python import vision


def parse_labels(arg):
    if "," in arg:
        return [x.strip() for x in arg.split(",") if x.strip()]
    a, sep, b = arg.partition("-")
    if sep and a.isdigit() and b.isdigit() and int(a) <= int(b):
        return [str(i) for i in range(int(a), int(b) + 1)]
    return [arg]


LABELS = parse_labels(sys.argv[1] if len(sys.argv) > 1 else "1-10")
PERSON = sys.argv[2] if len(sys.argv) > 2 else "p1"
TARGET = int(sys.argv[3]) if len(sys.argv) > 3 else 150
CAM = int(sys.argv[4]) if len(sys.argv) > 4 else 0
# Path model dihitung dari lokasi file ini, jadi bisa dijalankan dari folder mana pun
MODEL = str(Path(__file__).resolve().parents[1] / "models" / "hand_landmarker.task")

# Label yang butuh DUA tangan terlihat (angka 11 ke atas otomatis dua tangan).
# Untuk label huruf/kata, tambahkan nama folder-nya di sini (spasi sudah jadi "_").
TWO_HANDS = {"6", "7", "8", "9", "10"}

# Penyeragaman nama label supaya input kontributor berbeda tetap masuk ke folder yang sama.
# Ubah ke "upper" kalau mau semuanya huruf besar, atau "none" untuk tidak mengubah huruf.
LABEL_CASE = "lower"


def norm_label(text):
    """Seragamkan label: buang karakter terlarang, spasi jadi '_', lalu atur huruf besar/kecil."""
    name = re.sub(r'[\\/:*?"<>|]', "", text).strip()
    name = re.sub(r"\s+", "_", name)
    if LABEL_CASE == "lower":
        name = name.lower()
    elif LABEL_CASE == "upper":
        name = name.upper()
    return name


# Seragamkan label dari argumen (hapus duplikat, urutan tetap) dan daftar TWO_HANDS
LABELS = list(dict.fromkeys(n for n in map(norm_label, LABELS) if n)) or ["1"]
TWO_HANDS = {norm_label(x) for x in TWO_HANDS}

# Folder foto di LUAR repo: <folder induk repo>/SignLanguage_dataset/own/<label>
BASE = Path(__file__).resolve().parents[2].parent / "SignLanguage_dataset" / "own"
print("Model:", MODEL, "| ada:", Path(MODEL).exists())
print("Foto disimpan di:", BASE, "| daftar:", LABELS)

idx = 0
out = BASE / LABELS[0]
exist = 0  # jumlah foto yang sudah ada di folder label sekarang


def goto(label):
    """Pindah ke label lain (boleh label di luar daftar awal)."""
    global idx, out, exist
    label = norm_label(label)
    if label not in LABELS:
        LABELS.append(label)
    idx = LABELS.index(label)
    out = BASE / label
    out.mkdir(parents=True, exist_ok=True)
    exist = len(list(out.glob("*.jpg")))


HANDS_OVERRIDE = {}  # diisi saat berjalan lewat tombol H: {label: 1 atau 2}


def hands_needed(label):
    if label in HANDS_OVERRIDE:
        return HANDS_OVERRIDE[label]
    return 2 if (label in TWO_HANDS or (label.isdigit() and int(label) >= 11)) else 1


goto(LABELS[0])

landmarker = vision.HandLandmarker.create_from_options(
    vision.HandLandmarkerOptions(
        base_options=mp_python.BaseOptions(model_asset_path=MODEL),
        running_mode=vision.RunningMode.VIDEO,
        num_hands=2,
    )
)

cap = cv2.VideoCapture(CAM)
t0 = time.monotonic()
state, saved, count_end, last_save, typed = "idle", 0, 0.0, 0.0, ""
typing = False  # True saat mode ketik (tombol T) aktif


def put(img, text, y, scale=0.7):
    cv2.putText(img, text, (20, y), cv2.FONT_HERSHEY_SIMPLEX, scale, (0, 0, 0), 4)
    cv2.putText(img, text, (20, y), cv2.FONT_HERSHEY_SIMPLEX, scale, (255, 255, 255), 2)


while True:
    ok, frame = cap.read()
    if not ok:
        print("Kamera tidak terbaca. Coba nomor kamera lain (argumen ke-4, mis. 1).")
        break
    now = time.monotonic()
    rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
    res = landmarker.detect_for_video(
        mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb), int((now - t0) * 1000)
    )
    label = LABELS[idx]
    need = hands_needed(label)
    has_hand = len(res.hand_landmarks) >= need
    has_next = idx + 1 < len(LABELS)

    if state == "count" and now >= count_end:
        state = "rec"
    if state == "rec" and has_hand and now - last_save >= 0.12:
        # frame disimpan TANPA dibalik (sama seperti webcam.py dan browser)
        cv2.imwrite(str(out / f"{PERSON}_{int(time.time() * 1000)}.jpg"), frame)
        saved += 1
        exist += 1
        last_save = now
        if saved >= TARGET:
            state = "done"

    view = cv2.flip(frame, 1)  # cermin hanya untuk tampilan
    put(view, f"LABEL {label} | sesi {saved}/{TARGET} | total di folder: {exist}", 35, 0.65)
    if state == "idle":
        put(view, f"Bentuk isyarat {label} ({need} tangan). SPASI = mulai", 70)
    elif state == "count":
        put(view, str(math.ceil(count_end - now)), 160, 3)
        put(view, "SPASI = batal", 70)
    elif state == "rec":
        put(view, "MEREKAM - gerakkan tangan sedikit" if has_hand else f"BUTUH {need} TANGAN TERLIHAT", 70)
        put(view, "SPASI = jeda", 100, 0.55)
    else:
        nxt = f"SPASI = lanjut label {LABELS[idx + 1]}" if has_next else "SELESAI SEMUA. SPASI = tambah lagi"
        put(view, nxt, 70)
    if typing:
        put(view, f"Ketik label: {typed}_  (ENTER = lompat, ESC = batal)", view.shape[0] - 15, 0.5)
    elif state in ("idle", "done"):
        jump = (
            f"Lompat ke: {typed}_ (ENTER)"
            if typed
            else "Angka+ENTER = lompat | T = ketik | H = 1/2 tangan | N/B = berikut/sebelum | Q = keluar"
        )
        put(view, jump, view.shape[0] - 15, 0.5)
    cv2.imshow("Rekam dataset", view)

    key = cv2.waitKey(1) & 0xFF
    if key == ord("q") and not typing:
        break
    if state in ("count", "rec"):
        if key == ord(" "):  # jeda / batal, foto yang sudah masuk tetap tersimpan
            state = "idle"
        continue

    # Mode ketik: semua karakter masuk sebagai teks label
    if typing:
        if key == 27:  # ESC = batal
            typing, typed = False, ""
        elif key in (13, 10):  # ENTER = lompat
            name = norm_label(typed)
            if name:
                goto(name)
                saved, state = 0, "idle"
            typing, typed = False, ""
        elif key == 8:  # backspace
            typed = typed[:-1]
        elif 32 <= key <= 126 and len(typed) < 30:
            typed += chr(key)
        continue
    if key == ord("t"):  # masuk mode ketik
        typing, typed = True, ""
        continue
    if key == ord("h"):  # ganti kebutuhan 1 tangan <-> 2 tangan untuk label ini
        HANDS_OVERRIDE[label] = 1 if need == 2 else 2
        continue

    # state idle / done
    if ord("0") <= key <= ord("9") and len(typed) < 4:
        typed += chr(key)
    elif key == 8:  # backspace
        typed = typed[:-1]
    elif key in (13, 10) and typed:  # enter
        goto(typed)
        typed, saved, state = "", 0, "idle"
    elif key == ord("n") and has_next:
        goto(LABELS[idx + 1])
        saved, state = 0, "idle"
    elif key == ord("b") and idx > 0:
        goto(LABELS[idx - 1])
        saved, state = 0, "idle"
    elif key == ord(" "):
        if state == "done":
            if has_next:
                goto(LABELS[idx + 1])
            saved = 0
        typed = ""
        state, count_end = "count", now + 3

cap.release()
cv2.destroyAllWindows()
