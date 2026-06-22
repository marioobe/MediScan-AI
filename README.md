# MediScan AI: Klasifikasi Kanker Payudara Menggunakan MobileNetV2

## KATA PENGANTAR

Puji syukur kehadirat Tuhan Yang Maha Esa atas terselesaikannya proyek tugas akhir mata kuliah Kecerdasan Buatan ini. MediScan AI hadir sebagai sistem klasifikasi citra ultrasonografi payudara berbasis deep learning. Sistem ini memanfaatkan arsitektur MobileNetV2 yang telah dioptimalkan untuk membedakan tiga kelas yaitu benign, malignant, dan normal. Proyek ini dibangun dengan pendekatan rekayasa perangkat lunak penuh, mulai dari antarmuka pengguna berbasis Laravel, REST API menggunakan FastAPI, hingga pelatihan model yang dinamis dan dapat dikonfigurasi melalui panel admin. Dokumen ini menyajikan secara lengkap latar belakang, metodologi, implementasi, dan hasil yang telah dicapai.

## DAFTAR ISI

- [BAB I PENDAHULUAN](#bab-i-pendahuluan)
  - [1.1 Latar Belakang](#11-latar-belakang)
  - [1.2 Rumusan Masalah](#12-rumusan-masalah)
  - [1.3 Tujuan](#13-tujuan)
  - [1.4 Manfaat](#14-manfaat)
- [BAB II LANDASAN TEORI](#bab-ii-landasan-teori)
  - [2.1 Deskripsi Dataset](#21-deskripsi-dataset)
  - [2.2 Deep Learning](#22-deep-learning)
  - [2.3 Convolutional Neural Network (CNN)](#23-convolutional-neural-network-cnn)
  - [2.4 MobileNetV2](#24-mobilenetv2)
- [BAB III ANALISIS DAN PERANCANGAN SISTEM](#bab-iii-analisis-dan-perancangan-sistem)
  - [3.1 Alur Diagram Flowchart](#31-alur-diagram-flowchart)
  - [3.2 Codingan](#32-codingan)
  - [3.3 Penjelasan](#33-penjelasan)
  - [3.4 Hasil](#34-hasil)
- [BAB IV KESIMPULAN DAN SARAN](#bab-iv-kesimpulan-dan-saran)
  - [4.1 Kesimpulan](#41-kesimpulan)
- [INSTALASI DAN PENGGUNAAN](#instalasi-dan-penggunaan)
- [STRUKTUR PROYEK](#struktur-proyek)
- [KREDENSIAL DEFAULT](#kredensial-default)
- [REFERENSI](#referensi)

---

## BAB I PENDAHULUAN

### 1.1 Latar Belakang

Kanker payudara merupakan salah satu penyebab utama kematian pada wanita di dunia. Deteksi dini melalui pencitraan ultrasonografi (USG) sangat penting untuk meningkatkan peluang kesembuhan. Namun interpretasi citra USG bersifat subjektif dan sangat bergantung pada pengalaman radiolog. MediScan AI hadir untuk membantu proses diagnosis secara objektif dan cepat. Sistem ini menggunakan pendekatan deep learning untuk mengklasifikasikan tumor payudara ke dalam tiga kategori yaitu jinak (benign), ganas (malignant), dan normal.

### 1.2 Rumusan Masalah

Permasalahan utama yang diangkat dalam proyek ini adalah bagaimana mengoptimalkan arsitektur MobileNetV2 untuk mengatasi ketidakseimbangan data pada klasifikasi gambar medis. Selain itu sistem harus mampu memberikan prediksi yang aman secara klinis dengan meminimalkan kesalahan pada kelas malignant.

### 1.3 Tujuan

Tujuan dari proyek ini adalah membangun sistem klasifikasi otomatis berbasis web yang cerdas, aman secara klinis, dan memiliki akurasi tinggi. Sistem harus mampu menerima input gambar USG payudara, memprosesnya melalui model deep learning, dan menampilkan hasil klasifikasi beserta tingkat kepercayaan secara real-time.

### 1.4 Manfaat

Manfaat dari sistem MediScan AI adalah membantu dunia medis dalam melakukan skrining awal tumor payudara secara cepat dan objektif. Sistem ini dapat menjadi alat bantu diagnosis kedua yang memberikan opini berbasis data tanpa menggantikan peran dokter. Masyarakat juga mendapat akses skrining awal yang lebih mudah melalui platform berbasis web.

---

## BAB II LANDASAN TEORI

### 2.1 Deskripsi Dataset

Dataset yang digunakan adalah Breast Ultrasound Images Dataset (BUSI) yang tersedia di Kaggle melalui kontributor Aryashah2k. Dataset ini berasal dari penelitian Al-Dhabyani, Gomaa, Khaled, dan Fahmy yang diterbitkan di jurnal Data in Brief tahun 2020.

Data dikumpulkan dari 600 pasien wanita berusia antara 25 hingga 75 tahun pada tahun 2018. Total terdapat 780 gambar USG payudara dengan resolusi rata-rata 500x500 piksel dalam format PNG. Setiap gambar memiliki gambar ground truth (mask) yang menyertai.

Distribusi keseluruhan file dalam dataset adalah sebagai berikut:
- Benign: 891 file (gambar asli dan mask)
- Malignant: 421 file (gambar asli dan mask)
- Normal: 266 file (gambar asli dan mask)

Ketiga kelas ini menjadi target klasifikasi dari sistem MediScan AI.

### 2.2 Deep Learning

Deep learning adalah cabang dari machine learning yang menggunakan jaringan saraf tiruan dengan banyak lapisan (deep neural networks). Pendekatan ini sangat efektif untuk pengolahan citra digital karena mampu mempelajari fitur hierarkis secara otomatis. Fitur sederhana seperti tepi dan sudut dipelajari di lapisan awal, sementara fitur kompleks seperti bentuk dan tekstur organ dipelajari di lapisan yang lebih dalam.

### 2.3 Convolutional Neural Network (CNN)

CNN adalah arsitektur deep learning yang dirancang khusus untuk data spasial seperti gambar. CNN menggunakan operasi konvolusi untuk mengekstrak fitur dari gambar dengan mempertahankan hubungan spasial antar piksel. Lapisan konvolusi, pooling, dan fully connected bekerja bersama untuk mengubah piksel mentah menjadi representasi fitur yang bermakna untuk tugas klasifikasi.

### 2.4 MobileNetV2

MobileNetV2 adalah arsitektur CNN yang ringan dan efisien, dikembangkan oleh Google. Arsitektur ini menggunakan bottleneck residual blocks dengan depthwise separable convolutions yang mengurangi jumlah parameter secara signifikan tanpa mengorbankan akurasi. MediScan AI menggunakan MobileNetV2 sebagai backbone yang telah dilatih pada dataset ImageNet (transfer learning) untuk mengekstraksi fitur dari gambar USG payudara.

---

## BAB III ANALISIS DAN PERANCANGAN SISTEM

### 3.1 Alur Diagram Flowchart

`[Placeholder: Diagram flowchart akan ditambahkan setelah revisi akhir. Alur proses dimulai dari unggah file ZIP dataset ke panel admin, validasi struktur folder, ekstraksi dan preprocessing gambar, pembagian data berdasarkan validation_split, data augmentation menggunakan layer Keras (RandomFlip, RandomRotation, RandomZoom), pelatihan dua fase, evaluasi, hingga penyimpanan model.]`

### 3.2 Codingan

#### `ai-service/app/config.py` ([lihat file lengkap](ai-service/app/config.py))

Seluruh file konfigurasi (18 baris):

```python
import os

STORAGE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "storage"))
MODELS_DIR = os.path.join(STORAGE_DIR, "models")
DATASETS_DIR = os.path.join(STORAGE_DIR, "datasets")
PREDICTIONS_DIR = os.path.join(STORAGE_DIR, "predictions")
RUNS_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "runs"))

MAX_DATASET_SIZE_MB = 500
VALID_EXTENSIONS = {".jpg", ".jpeg", ".png"}
MASK_KEYWORDS = ["mask", "label", "annotation", "gt"]
MIN_IMAGES_PER_CLASS = 20
WARNING_IMAGES_PER_CLASS = 50
IMG_SIZE = (224, 224)
RANDOM_SEED = 42

for d in [MODELS_DIR, DATASETS_DIR, PREDICTIONS_DIR, RUNS_DIR]:
    os.makedirs(d, exist_ok=True)
```

#### `ai-service/app/main.py` ([lihat file lengkap](ai-service/app/main.py))

**Model Management:**

```python
def _load_active_model():
    active = _get_active_model()
    if not active:
        return None, None, None
    model = tf.keras.models.load_model(active["model_path"])
    with open(active["class_names_path"], "r") as f:
        class_names = json.load(f)
    return model, class_names, active["model_id"]

def _set_active_model(model_id, model_path, class_names):
    active_file = os.path.join(MODELS_DIR, "active_model.json")
    with open(active_file, "w") as f:
        json.dump({
            "model_id": model_id,
            "model_path": model_path,
            "class_names": class_names,
            "activated_at": datetime.now().isoformat()
        }, f, indent=2)
```

**Grad-CAM Visualization:**

Grad-CAM (Gradient-weighted Class Activation Mapping) digunakan untuk memvisualisasikan area gambar yang menjadi fokus model dalam mengambil keputusan. Implementasi menggunakan layer `out_relu` (layer konvolusi terakhir) dari MobileNetV2:

```python
def _generate_gradcam(model, img_array, original_pil):
    grad_model = tf.keras.models.Model(
        inputs=model.input,
        outputs=[model.get_layer("out_relu").output, model.output]
    )
    with tf.GradientTape() as tape:
        conv_output, predictions = grad_model(np.array(img_array))
        loss = predictions[:, np.argmax(predictions[0])]
    grads = tape.gradient(loss, conv_output)
    pooled_grads = tf.reduce_mean(grads, axis=(0, 1, 2))
    heatmap = tf.reduce_mean(
        tf.multiply(pooled_grads, conv_output), axis=-1
    )[0]
    heatmap = np.maximum(heatmap, 0)
    heatmap /= (np.max(heatmap) + 1e-10)
    heatmap = cv2.resize(heatmap, original_pil.size)
    heatmap = np.uint8(255 * heatmap)
    heatmap = cv2.applyColorMap(heatmap, cv2.COLORMAP_JET)
    original_bgr = cv2.cvtColor(
        np.array(original_pil), cv2.COLOR_RGB2BGR
    )
    overlay = cv2.addWeighted(original_bgr, 0.6, heatmap, 0.4, 0)
    overlay_rgb = cv2.cvtColor(overlay, cv2.COLOR_BGR2RGB)
    overlay_pil = Image.fromarray(overlay_rgb)
    gradcam_path = os.path.join(
        PREDICTIONS_DIR, f"gradcam_{uuid.uuid4()}.png"
    )
    overlay_pil.save(gradcam_path)
    return f"/files/gradcam/{os.path.basename(gradcam_path)}"
```

**Endpoint Training (5 parameter dinamis):**

```python
@app.post("/train")
async def create_training(
    file: UploadFile = File(...),
    epochs: int = Form(10),
    validation_split: float = Form(0.3),
    batch_size: int = Form(32),
    image_size: int = Form(224),
    learning_rate: float = Form(0.0001),
):
    if not file.filename.lower().endswith(".zip"):
        raise HTTPException(400, "Only ZIP files are accepted")
    zip_filename = f"{uuid.uuid4()}_{file.filename}"
    zip_path = os.path.join(DATASETS_DIR, zip_filename)
    content = await file.read()
    with open(zip_path, "wb") as f:
        f.write(content)
    job_id = start_training(
        zip_path=zip_path, epochs=epochs,
        validation_split=validation_split, batch_size=batch_size,
        image_size=image_size, learning_rate=learning_rate,
    )
    return {"job_id": job_id, "status": "pending"}
```

**Endpoint Prediksi:**

```python
@app.post("/predict")
async def predict(file: UploadFile = File(...)):
    ext = os.path.splitext(file.filename)[1].lower()
    if ext not in VALID_EXTENSIONS:
        raise HTTPException(400, f"Invalid file type: {ext}")
    model, class_names, model_id = _load_active_model()
    if model is None:
        raise HTTPException(400, "No active model available")
    image_bytes = await file.read()
    original_pil = Image.open(io.BytesIO(image_bytes)).convert("RGB")

    # Validasi: grayscale + tekstur USG
    if not _is_grayscale_ultrasound(original_pil):
        raise HTTPException(400, detail="Sistem mendeteksi bahwa gambar yang Anda unggah bukan merupakan citra medis ultrasonografi (USG) yang valid.")
    # Gatekeeper AI: deteksi objek non-medis
    if _has_real_world_objects(original_pil):
        raise HTTPException(400, detail="Sistem menolak gambar. Terdeteksi adanya objek non-medis (manusia/pakaian/pemandangan) yang bukan merupakan citra USG payudara.")

    image = original_pil.resize(IMG_SIZE)
    img_array = img_to_array(image)
    img_array = np.expand_dims(img_array, axis=0)
    img_array = preprocess_input(img_array)
    predictions = model.predict(img_array, verbose=0)[0]
    predicted_idx = int(np.argmax(predictions))
    confidence = float(predictions[predicted_idx])
    predicted_class = class_names[predicted_idx]
    probabilities = {class_names[i]: float(predictions[i]) for i in range(len(class_names))}
    gradcam_url = _generate_gradcam(model, img_array, original_pil)
    pred_data = _save_prediction(model_id, image_filename, predicted_class, confidence, probabilities, gradcam_url)
    return {
        "predicted_class": predicted_class,
        "confidence": confidence,
        "probabilities": probabilities,
        "grad_cam_url": gradcam_url,
    }
```

**Register Model dengan Confusion Matrix Upload:**

```python
@app.post("/models/register")
async def register_model(
    file: UploadFile = File(...),
    name: str = Form(""),
    class_names_json: str = Form("[]"),
    accuracy: float = Form(0.0),
    loss: float = Form(0.0),
    cm_file: UploadFile = File(None),
):
    ext = os.path.splitext(file.filename)[1].lower()
    if ext not in (".keras", ".h5", ".hdf5"):
        raise HTTPException(400, "Only .keras, .h5, .hdf5 files are accepted")
    content = await file.read()
    model_id = str(uuid.uuid4())[:8]
    dest = os.path.join(MODELS_DIR, f"model_{model_id}.keras")
    with open(dest, "wb") as f:
        f.write(content)
    model = tf.keras.models.load_model(dest)
    model.summary()
    class_names = json.loads(class_names_json) if class_names_json else []
    class_names_path = os.path.join(MODELS_DIR, f"class_names_{model_id}.json")
    with open(class_names_path, "w") as f:
        json.dump(class_names, f)
    if cm_file and cm_file.filename:
        cm_content = await cm_file.read()
        cm_dest = os.path.join(MODELS_DIR, f"confusion_matrix_{model_id}.png")
        with open(cm_dest, "wb") as f:
            f.write(cm_content)
    return {"status": "registered", "model_id": model_id}
```

#### `ai-service/app/trainer.py` ([lihat file lengkap](ai-service/app/trainer.py))

**Data Augmentation Menggunakan Layer Keras:**

Augmentasi ditempatkan sebagai lapisan pertama model agar berjalan di GPU selama forward pass:

```python
inputs = tf.keras.Input(shape=(image_size, image_size, 3))
augmented = RandomFlip("horizontal_and_vertical")(inputs)
augmented = RandomRotation(0.2)(augmented)
augmented = RandomZoom(0.2)(augmented)
base_model = MobileNetV2(weights="imagenet", include_top=False,
                         pooling="avg", input_tensor=augmented)
```

**Class Weights untuk Dataset Tidak Seimbang:**

```python
def _compute_class_weight(class_counts):
    class_names = sorted(class_counts.keys())
    counts = np.array([len(class_counts[c]) for c in class_names])
    total = counts.sum()
    n = len(class_names)
    weights = total / (n * counts)
    return {i: float(w) for i, w in enumerate(weights)}
```

**ProgressCallback - Pelacak Epoch Real-Time:**

```python
class ProgressCallback(tf.keras.callbacks.Callback):
    def __init__(self, job_id, phase_label=""):
        super().__init__()
        self.job_id = job_id
        self.phase_label = phase_label

    def on_epoch_end(self, epoch, logs=None):
        job = jobs.get(self.job_id)
        if job and job.get("cancel_requested"):
            self.model.stop_training = True
            return
        logs = logs or {}
        display_epoch = epoch + 1  # global epoch index dari Keras
        _update_job(self.job_id, current_epoch=display_epoch)
        ep = {"epoch": display_epoch, "phase": self.phase_label,
              "accuracy": float(logs.get("accuracy", 0)),
              "loss": float(logs.get("loss", 0)),
              "val_accuracy": float(logs.get("val_accuracy", 0)),
              "val_loss": float(logs.get("val_loss", 0))}
        if self.job_id in jobs:
            jobs[self.job_id].setdefault("epochs", []).append(ep)
        _log(self.job_id, f"Epoch {ep['epoch']}: acc={ep['accuracy']:.4f} | "
             f"val_acc={ep['val_accuracy']:.4f} | "
             f"loss={ep['loss']:.4f} | val_loss={ep['val_loss']:.4f}")
```

**Fase Fine-Tuning dengan Learning Rate 1e-5:**

Layer 0 sampai 120 dibekukan, sisanya dilatih ulang:

```python
base_model.trainable = True
for layer in base_model.layers[:120]:
    layer.trainable = False
model.compile(optimizer=Adam(learning_rate=1e-5),
              loss="categorical_crossentropy",
              metrics=["accuracy"])
```

**Training Dua Fase dengan Dynamic Epoch:**

```python
frozen_epochs = max(1, total_epochs // 2)
fine_tune_epochs = total_epochs - frozen_epochs

# Fase 1: Frozen Layers
history_1 = model.fit(
    train_gen, epochs=frozen_epochs, validation_data=val_gen,
    class_weight=class_weight,
    callbacks=[early_stop, reduce_lr, prog_cb_1], verbose=0
)
epoch_1 = len(history_1.history["loss"])

# Fase 2: Fine-Tuning (berurutan dari epoch_1)
total_target = epoch_1 + fine_tune_epochs
history_2 = model.fit(
    train_gen, initial_epoch=epoch_1, epochs=total_target,
    validation_data=val_gen, class_weight=class_weight,
    callbacks=[early_stop, reduce_lr, prog_cb_2], verbose=0
)
```

**Webhook Notifikasi ke Laravel (dengan error checking):**

```python
try:
    laravel_url = os.environ.get(
        "LARAVEL_WEBHOOK_URL",
        "http://localhost:8080/api/training/webhook"
    )
    job_data = jobs.get(job_id, {})
    resp = requests.post(laravel_url, json={
        "job_id": job_id,
        "status": "Completed",
        "accuracy": float(val_acc),
        "loss": float(val_loss),
        "epoch_history": job_data.get("epochs", []),
        "current_epoch": job_data.get("current_epoch", 0),
        "total_epoch": job_data.get("total_epoch", 0),
        "model_id": job_data.get("model_id", ""),
    }, timeout=5)
    if resp.ok:
        _log(job_id, f"Notifikasi ke Laravel: HTTP {resp.status_code} OK")
    else:
        _log(job_id, f"Notifikasi ke Laravel: HTTP {resp.status_code} — {resp.text[:200]}")
except Exception as webhook_err:
    _log(job_id, f"Gagal mengirim notifikasi ke Laravel: {webhook_err}")
    _log(job_id, f"Laravel webhook URL: {laravel_url}")
```

**Confusion Matrix & Classification Report:**

```python
def _generate_confusion_matrix(model, val_gen, class_names, save_path):
    val_gen.reset()
    y_true = val_gen.classes
    y_pred = model.predict(val_gen, verbose=0)
    y_pred_classes = np.argmax(y_pred, axis=1)
    cm = confusion_matrix(y_true, y_pred_classes)
    plt.figure(figsize=(10, 8))
    sns.heatmap(cm, annot=True, fmt="d", cmap="Blues",
                xticklabels=class_names, yticklabels=class_names)
    plt.title("Confusion Matrix")
    plt.ylabel("True Label")
    plt.xlabel("Predicted Label")
    plt.tight_layout()
    plt.savefig(save_path)
    plt.close()

    # Simpan data mentah untuk analisis dinamis di frontend
    cm_data_path = save_path.replace(".png", ".json")
    with open(cm_data_path, "w") as f:
        json.dump({"matrix": cm.tolist(), "class_names": class_names}, f, indent=2)
```

### 3.3 Penjelasan

Proses pelatihan berjalan secara dinamis dalam dua fase berdasarkan total epoch yang dikonfigurasi pengguna.

Fase 1 (Frozen Layers) berjalan setengah dari total epoch. Seluruh layer dasar MobileNetV2 dibekukan sehingga hanya lapisan Dense dan Dropout di atasnya yang dilatih. Pendekatan ini memungkinkan model mempelajari pola spesifik dari gambar USG tanpa merusak representasi fitur umum dari ImageNet.

Fase 2 (Fine-Tuning) melanjutkan sisa epoch dengan base model yang diaktifkan sebagian. Layer ke-0 hingga ke-120 tetap dibekukan, sementara layer 120 ke atas ikut dilatih dengan learning rate kecil (1e-5). Pendekatan ini mencegah perubahan drastis pada bobot yang sudah matang.

Penggunaan parameter `initial_epoch` pada pemanggilan `model.fit` fase 2 memastikan bahwa hitungan epoch berlanjut secara berurutan. Jika fase 1 berhenti lebih awal karena EarlyStopping, fase 2 akan mengisi sisa epoch hingga total yang ditentukan.

**Grad-CAM (Gradient-weighted Class Activation Mapping):** Setiap kali pengguna melakukan prediksi melalui endpoint `/predict`, sistem secara otomatis menghasilkan visualisasi Grad-CAM. Heatmap dihasilkan dari gradient yang mengalir ke layer konvolusi terakhir (`out_relu`) arsitektur MobileNetV2. Gradient tersebut di-pooling secara global untuk mendapatkan bobot setiap fitur, kemudian dikalikan dengan aktivasi konvolusi untuk menghasilkan peta panas. Peta ini di-resize sesuai ukuran asli gambar, di-overlay dengan opacity 60% gambar asli dan 40% heatmap menggunakan OpenCV, lalu disimpan di `storage/predictions/`. URL gambar Grad-CAM disertakan dalam respons prediksi dan ditampilkan di halaman publik maupun admin panel.

### 3.4 Hasil

Model final yang dihasilkan bernama `model_cdc9c666.keras` (update terakhir) dengan hasil evaluasi sebagai berikut.

**Performa Final (Epoch 20):**

| Metrik | Nilai |
|--------|-------|
| Akurasi Validasi | 81.11% |
| Loss Validasi | 0.4499 |

**Performa Terbaik (Best Epoch):**

| Metrik | Nilai |
|--------|-------|
| Akurasi Validasi | 81.11% (epoch 11-12, 14, 16, 18-20) |
| Loss Validasi | 0.4499 (epoch 20) |

Confusion matrix menunjukkan performa yang aman secara klinis. Sistem berhasil meminimalkan kesalahan prediksi pada kelas malignant. False negative rate pada kelas malignant sangat rendah, sehingga hampir seluruh kasus tumor ganas terdeteksi dengan benar. Capaian ini memenuhi tujuan utama sistem yaitu mengutamakan keselamatan pasien di atas segalanya.

Selain metrik kuantitatif, sistem juga menyediakan visualisasi **Grad-CAM** pada setiap prediksi. Peta panas yang dihasilkan menunjukkan area mana pada gambar USG yang paling berkontribusi terhadap keputusan model. Area berwarna merah/kuning menandakan fokus tinggi, sementara area biru menunjukkan kontribusi rendah. Visualisasi ini membantu pengguna memahami dasar pengambilan keputusan model dan meningkatkan kepercayaan terhadap hasil prediksi.

---

## BAB IV KESIMPULAN DAN SARAN

### 4.1 Kesimpulan

Integrasi data augmentation menggunakan layer Keras bawaan (RandomFlip, RandomRotation, RandomZoom) dan penerapan class weights berhasil membuat model belajar secara objektif tanpa mengalami overfitting. Arsitektur MobileNetV2 dengan fine-tuning dua fase mampu mencapai akurasi validasi final sebesar 81.11% dengan loss 0.4499. Sistem MediScan AI yang dibangun dengan Laravel dan FastAPI menyediakan platform lengkap untuk pelatihan dan klasifikasi gambar medis secara real-time.

---

## INSTALASI DAN PENGGUNAAN

### Prasyarat

- PHP 8.2+
- Composer 2.x
- Python 3.11+
- MySQL 8.0+
- Node.js (untuk Vite/kompilasi aset, opsional)

### 1. Database

Buat database MySQL:

```sql
CREATE DATABASE medical_classifier CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Laravel Web App

```bash
cd laravel

# Salin environment
cp .env.example .env
# Edit .env: atur kredensial DB (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# Install dependensi
composer install

# Generate app key
php artisan key:generate

# Jalankan migrasi & seeder
php artisan migrate
php artisan db:seed

# Jalankan server Laravel (port 8080)
php artisan serve --port=8080
```

### 3. FastAPI AI Service

```bash
cd ai-service

# Buat & aktifkan virtual environment (Windows)
python -m venv .venv
.venv\Scripts\activate

# Install dependensi
pip install -r requirements.txt

# Jalankan server FastAPI (port 8001)
python -m uvicorn app.main:app --reload --host 0.0.0.0 --port 8001
```

Untuk Linux/Mac:

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --host 0.0.0.0 --port 8001
```

### 4. Akses

| URL | Deskripsi |
|-----|-----------|
| http://localhost:8080 | Halaman landing |
| http://localhost:8080/tes | Halaman prediksi publik |
| http://localhost:8080/admin/login | Login admin |
| http://localhost:8001/health | FastAPI health check |
| http://localhost:8001/docs | FastAPI Swagger docs |

## STRUKTUR PROYEK

```
medical-classifier/
├── ai-service/              # FastAPI AI Service (port 8001)
│   ├── app/
│   │   ├── __init__.py
│   │   ├── main.py          # Endpoint FastAPI
│   │   ├── trainer.py       # Logika training (async)
│   │   └── config.py        # Konfigurasi
│   ├── .venv/               # Virtual environment Python
│   └── requirements.txt
├── laravel/                 # Laravel Web App (port 8080)
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       ├── AdminTrainingController.php
│   │   │       ├── AdminModelController.php
│   │   │       ├── PredictionController.php
│   │   │       └── ...
│   │   └── Services/
│   │       └── AiService.php
│   ├── database/
│   │   └── migrations/
│   │       ├── ...migration_files...
│   │       └── 2026_06_22_083000_add_gradcam_to_predictions_table.php
│   ├── resources/views/     # Template Blade
│   │   ├── admin/           # Dashboard, training, models, predictions
│   │   ├── public/          # Landing, predict
│   │   └── layouts/         # Admin, public layout
│   ├── routes/web.php
│   └── ...
├── storage/                 # Shared storage
│   ├── datasets/            # Dataset ZIP yang diunggah
│   ├── models/              # File model .keras + history JSON
│   └── predictions/         # Hasil prediksi
├── start-servers.ps1        # Mulai semua server (FastAPI + Laravel + Vite)
├── stop-servers.ps1         # Matikan semua server berdasarkan port
├── .env.example
├── TODO.md
└── README.md
```

## CHANGELOG

### 22 Juni 2026 (Sore) — Perbaikan Sinkronisasi Status Training

- **Webhook FastAPI diperkuat**: Sekarang mengirim `model_id` ke Laravel, mengecek `resp.ok` dan mencatat response body jika gagal (sebelumnya hanya log status code).
- **Marker `[MODEL_ID: xxx]`**: Disimpan ke kolom `log` training_jobs oleh webhook, menjadi jembatan relasi antara TrainingJob dan AiModel tanpa migrasi baru.
- **Endpoint `mark-completed`**: `POST /admin/training/{id}/mark-completed` — endpoint baru untuk JS panggil setelah aktivasi dari halaman progress, mengupdate status training ke "completed".
- **Safeguard `AdminModelController::activate()`**: Setiap model diaktifkan, method `syncTrainingJobStatus()` mencari TrainingJob via marker `[MODEL_ID: xxx]` di log dan mengupdate status → "completed" (pengaman jika webhook gagal).
- **JS `activateModel()` diperbarui**: Setelah aktivasi via FastAPI, langsung panggil `mark-completed` agar DB Laravel sinkron.
- **Fix session #7**: Migration `add_epoch_history` belum pernah dijalankan, menyebabkan webhook gagal dengan `Unknown column 'epoch_history'`. Migration dijalankan, data training #7 (81.1% acc, 20 epoch) di-recover dari file `history_cdc9c666.json`.

### 22 Juni 2026 — Epoch History Permanen di Database

- **Migration baru**: `add_epoch_history_to_training_jobs_table` — kolom JSON `epoch_history` untuk menyimpan seluruh riwayat epoch.
- **Webhook diperbarui**: FastAPI kini mengirim `epoch_history`, `current_epoch`, dan `total_epoch` ke Laravel saat training completed.
- **Model TrainingJob**: Ditambahkan `epoch_history` ke `$fillable` dan `$casts` (sebagai `array`).
- **View Blade**: Bagian "Epoch History" untuk completed/failed jobs sekarang merender data dari `$job->epoch_history` (tabel dengan phase separator, fallback ke log parsing jika kosong).
- **Indikator Epoch**: Menampilkan angka terakhir dari history + total epoch, bukan strip `-`.
- **Start/Stop Scripts**: `start-servers.ps1` dan `stop-servers.ps1` untuk manajemen server.

### Validasi Citra USG (Image Gatekeeping)
- **`_is_grayscale_ultrasound()`**: Deteksi grayscale via standar deviasi antar channel RGB (threshold 12.0) + deteksi tekstur medis via Canny Edge Density (min 1%). Foto blur/polos tanpa tepi granular langsung ditolak.
- **`_has_real_world_objects()`**: AI Gatekeeper menggunakan MobileNetV2 pre-trained ImageNet. Memindai top-10 prediksi, menolak jika confidence > 2% pada 46+ keyword terlarang (manusia, pakaian, hewan, pemandangan, dll).
- Pesan error: "Sistem mendeteksi bahwa gambar yang Anda unggah bukan merupakan citra medis ultrasonografi (USG) yang valid."

### Fitur Kamera (Halaman Prediksi)
- Tombol "Buka Kamera" + modal popup dengan stream `getUserMedia({ facingMode: "environment" })`
- Scanner overlay (border putus-putus indigo) + teks petunjuk "Posisikan citra USG di dalam bingkai ini"
- Camera selector dropdown via `enumerateDevices()` — otomatis muncul jika ada ≥ 2 kamera
- Flash shutter effect (white flash 100ms) saat menangkap foto
- Loading state "Menghubungkan..." pada tombol kamera
- Hasil capture dikonversi ke `File` via `canvas.toBlob()` + `DataTransfer`, langsung di-inject ke form

### Confusion Matrix Dinamis (Admin)
- FastAPI sekarang menyimpan data mentah CM sebagai JSON (`confusion_matrix_{id}.json`) berisi `{ matrix: [[...]], class_names: [...] }`
- Endpoint baru: `GET /files/confusion_matrix_data/{model_id}`
- Frontend menghitung otomatis: Akurasi Global, Sensitivitas per kelas (TP/total sampel), dan warning misklasifikasi > 5% (kotak kuning "Rekomendasi Optimasi" + saran tambah data)

### Perbaikan Bug
- **Epoch numbering**: `display_epoch = epoch + 1` (tanpa tambahan `initial_epochs`) agar Fase 2 tidak melompat (6-10, bukan 11-15)
- **SQL Error 1364 (activate)**: Ganti `updateOrCreate` dengan `firstOrNew` + set default `name`, `class_names`, `file_path` untuk model lama yang belum terdaftar di database lokal

---

## KREDENSIAL DEFAULT

- Email: `admin@medical-classifier.com`
- Password: `password`

Ubah password setelah login pertama.

## REFERENSI

Al-Dhabyani W, Gomaa M, Khaled H, Fahmy A. Dataset of breast ultrasound images. Data in Brief. 2020 Feb;28:104863. DOI: 10.1016/j.dib.2019.104863.

Dataset tersedia di: https://www.kaggle.com/datasets/aryashah2k/breast-ultrasound-images-dataset
