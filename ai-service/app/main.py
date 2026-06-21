import os
import json
import io
import uuid
from datetime import datetime

import numpy as np
import cv2
from PIL import Image
from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse, FileResponse

import tensorflow as tf
from tensorflow.keras.applications.mobilenet_v2 import preprocess_input
from tensorflow.keras.preprocessing.image import img_to_array, load_img

from app.config import MODELS_DIR, PREDICTIONS_DIR, IMG_SIZE, DATASETS_DIR, VALID_EXTENSIONS
from app.trainer import start_training, get_job, cancel_training

app = FastAPI(title="Medical Image Classifier AI Service", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

def _get_active_model():
    active_file = os.path.join(MODELS_DIR, "active_model.json")
    if not os.path.exists(active_file):
        return None
    with open(active_file, "r") as f:
        data = json.load(f)
    model_id = data.get("model_id")
    model_path = os.path.join(MODELS_DIR, data.get("model_path", ""))
    class_names_path = os.path.join(MODELS_DIR, f"class_names_{model_id}.json")
    if not os.path.exists(model_path) or not os.path.exists(class_names_path):
        return None
    return {
        "model_id": model_id,
        "model_path": model_path,
        "class_names_path": class_names_path,
    }

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

def _save_prediction(model_id, image_path, predicted_class, confidence, probabilities):
    pred_id = str(uuid.uuid4())[:8]
    pred_data = {
        "id": pred_id,
        "model_id": model_id,
        "image_path": image_path,
        "predicted_class": predicted_class,
        "confidence": float(confidence),
        "probabilities": probabilities,
        "created_at": datetime.now().isoformat()
    }
    pred_file = os.path.join(PREDICTIONS_DIR, f"pred_{pred_id}.json")
    with open(pred_file, "w") as f:
        json.dump(pred_data, f, indent=2)
    return pred_data

def _generate_gradcam(model, img_array, predicted_idx, original_pil):
    last_conv = model.get_layer('out_relu')
    grad_model = tf.keras.models.Model(
        inputs=model.inputs,
        outputs=[last_conv.output, model.output]
    )
    with tf.GradientTape() as tape:
        conv_out, preds = grad_model(img_array)
        loss = preds[:, predicted_idx]
    grads = tape.gradient(loss, conv_out)
    pooled = tf.reduce_mean(grads, axis=(0, 1, 2))
    heatmap = tf.reduce_sum(tf.multiply(pooled, conv_out[0]), axis=-1)
    heatmap = tf.maximum(heatmap, 0) / (tf.reduce_max(heatmap) + tf.keras.backend.epsilon())
    heatmap = heatmap.numpy()
    orig_width, orig_height = original_pil.size
    heatmap = cv2.resize(heatmap, (orig_width, orig_height))
    heatmap = np.uint8(255 * heatmap)
    heatmap_color = cv2.applyColorMap(heatmap, cv2.COLORMAP_JET)
    original_np = np.array(original_pil)
    original_bgr = cv2.cvtColor(original_np, cv2.COLOR_RGB2BGR)
    overlay = cv2.addWeighted(original_bgr, 0.6, heatmap_color, 0.4, 0)
    filename = f"gradcam_{uuid.uuid4()}.png"
    path = os.path.join(PREDICTIONS_DIR, filename)
    cv2.imwrite(path, overlay)
    return filename

def _load_all_predictions():
    predictions = []
    for f in sorted(os.listdir(PREDICTIONS_DIR), reverse=True):
        if f.endswith(".json"):
            with open(os.path.join(PREDICTIONS_DIR, f), "r") as fh:
                predictions.append(json.load(fh))
    return predictions

@app.get("/health")
async def health():
    return {"status": "ok"}

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
        zip_path=zip_path,
        epochs=epochs,
        validation_split=validation_split,
        batch_size=batch_size,
        image_size=image_size,
        learning_rate=learning_rate,
    )
    return {"job_id": job_id, "status": "pending"}

@app.get("/train/{job_id}/status")
async def training_status(job_id: str):
    job = get_job(job_id)
    if not job:
        raise HTTPException(404, "Job not found")
    return {
        "status": job.get("status"),
        "current_epoch": job.get("current_epoch"),
        "total_epoch": job.get("total_epoch"),
        "progress_percent": job.get("progress_percent"),
        "accuracy": job.get("accuracy_result"),
        "loss": job.get("loss_result"),
        "error_message": job.get("error_message"),
        "model_id": job.get("model_id"),
        "epochs": job.get("epochs", []),
    }

@app.get("/train/{job_id}/log")
async def training_log(job_id: str):
    job = get_job(job_id)
    if not job:
        raise HTTPException(404, "Job not found")
    return {"log": job.get("log", "")}

@app.get("/train/{job_id}/result")
async def training_result(job_id: str):
    job = get_job(job_id)
    if not job:
        raise HTTPException(404, "Job not found")
    if job.get("status") != "completed":
        raise HTTPException(400, "Training not completed yet")
    result = {
        "status": "completed",
        "model_id": job.get("model_id"),
        "accuracy": job.get("accuracy_result"),
        "loss": job.get("loss_result"),
        "class_names": job.get("class_names"),
    }
    if job.get("confusion_matrix_path"):
        result["confusion_matrix_url"] = f"/files/confusion_matrix/{job['model_id']}"
    if job.get("classification_report_path"):
        cr_path = os.path.join(MODELS_DIR, job["classification_report_path"])
        if os.path.exists(cr_path):
            with open(cr_path, "r") as f:
                result["classification_report"] = json.load(f)
    return result

@app.post("/train/{job_id}/cancel")
async def cancel_train(job_id: str):
    job = get_job(job_id)
    if not job:
        raise HTTPException(404, "Job not found")
    success = cancel_training(job_id)
    if not success:
        raise HTTPException(400, "Training cannot be cancelled (already completed or failed)")
    return {"status": "cancelled", "job_id": job_id}

@app.post("/train/{job_id}/activate")
async def activate_model(job_id: str):
    job = get_job(job_id)
    if not job:
        raise HTTPException(404, "Job not found")
    if job.get("status") != "completed":
        raise HTTPException(400, "Training not completed yet")
    _set_active_model(job["model_id"], job["model_path"], job["class_names"])
    return {"status": "activated", "model_id": job["model_id"]}

@app.get("/files/confusion_matrix/{model_id}")
async def get_confusion_matrix(model_id: str):
    path = os.path.join(MODELS_DIR, f"confusion_matrix_{model_id}.png")
    if not os.path.exists(path):
        raise HTTPException(404, "Confusion matrix not found")
    return FileResponse(path, media_type="image/png")

@app.get("/files/gradcam/{filename}")
async def get_gradcam(filename: str):
    path = os.path.join(PREDICTIONS_DIR, filename)
    if not os.path.exists(path):
        raise HTTPException(404, "File not found")
    return FileResponse(path, media_type="image/png")

@app.post("/predict")
async def predict(file: UploadFile = File(...)):
    ext = os.path.splitext(file.filename)[1].lower()
    if ext not in VALID_EXTENSIONS:
        raise HTTPException(400, f"Invalid file type: {ext}. Accepted: {VALID_EXTENSIONS}")
    model, class_names, model_id = _load_active_model()
    if model is None:
        raise HTTPException(400, "No active model available. Please ask admin to activate a model first.")
    image_bytes = await file.read()
    original_pil = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    image = original_pil.resize(IMG_SIZE)
    img_array = img_to_array(image)
    img_array = np.expand_dims(img_array, axis=0)
    img_array = preprocess_input(img_array)
    predictions = model.predict(img_array, verbose=0)[0]
    predicted_idx = int(np.argmax(predictions))
    confidence = float(predictions[predicted_idx])
    predicted_class = class_names[predicted_idx]
    probabilities = {class_names[i]: float(predictions[i]) for i in range(len(class_names))}
    try:
        grad_cam_filename = _generate_gradcam(model, img_array, predicted_idx, original_pil)
        grad_cam_url = f"/files/gradcam/{grad_cam_filename}"
    except Exception as e:
        grad_cam_url = None
    image_filename = f"pred_{uuid.uuid4()}_{file.filename}"
    image_path = os.path.join(PREDICTIONS_DIR, image_filename)
    image.save(image_path)
    pred_data = _save_prediction(model_id, image_filename, predicted_class, confidence, probabilities)
    return {
        "predicted_class": predicted_class,
        "confidence": confidence,
        "probabilities": probabilities,
        "prediction_id": pred_data["id"],
        "grad_cam_url": grad_cam_url,
    }

@app.get("/predictions")
async def list_predictions():
    return _load_all_predictions()

@app.get("/models/active")
async def get_active_model():
    active = _get_active_model()
    if not active:
        return {"active": False, "model": None}
    model_path = active["model_path"]
    model_id = active["model_id"]
    with open(active["class_names_path"], "r") as f:
        class_names = json.load(f)
    history_path = os.path.join(MODELS_DIR, f"history_{model_id}.json")
    history = {}
    if os.path.exists(history_path):
        with open(history_path, "r") as f:
            history = json.load(f)
    return {
        "active": True,
        "model": {
            "model_id": model_id,
            "class_names": class_names,
            "history": {
                "accuracy": history.get("accuracy", []),
                "val_accuracy": history.get("val_accuracy", []),
                "loss": history.get("loss", []),
                "val_loss": history.get("val_loss", []),
            },
        }
    }

@app.get("/models/list")
async def list_models():
    models = []
    if os.path.exists(MODELS_DIR):
        for f in os.listdir(MODELS_DIR):
            if f.startswith("model_") and f.endswith(".keras"):
                model_id = f.replace("model_", "").replace(".keras", "")
                cm_path = os.path.join(MODELS_DIR, f"confusion_matrix_{model_id}.png")
                cr_path = os.path.join(MODELS_DIR, f"classification_report_{model_id}.json")
                history_path = os.path.join(MODELS_DIR, f"history_{model_id}.json")
                cn_path = os.path.join(MODELS_DIR, f"class_names_{model_id}.json")
                class_names = []
                if os.path.exists(cn_path):
                    with open(cn_path, "r") as fh:
                        class_names = json.load(fh)
                history = {}
                if os.path.exists(history_path):
                    with open(history_path, "r") as fh:
                        history = json.load(fh)
                val_acc = history.get("val_accuracy", [None])[-1]
                val_loss = history.get("val_loss", [None])[-1]
                active = _get_active_model()
                is_active = active is not None and active["model_id"] == model_id
                models.append({
                    "model_id": model_id,
                    "class_names": class_names,
                    "accuracy": val_acc,
                    "loss": val_loss,
                    "is_active": is_active,
                    "has_confusion_matrix": os.path.exists(cm_path),
                    "has_classification_report": os.path.exists(cr_path),
                })
    return sorted(models, key=lambda x: x["is_active"], reverse=True)

@app.post("/models/{model_id}/activate")
async def activate_specific_model(model_id: str):
    model_path = os.path.join(MODELS_DIR, f"model_{model_id}.keras")
    cn_path = os.path.join(MODELS_DIR, f"class_names_{model_id}.json")
    if not os.path.exists(model_path) or not os.path.exists(cn_path):
        raise HTTPException(404, "Model not found")
    with open(cn_path, "r") as f:
        class_names = json.load(f)
    _set_active_model(model_id, f"model_{model_id}.keras", class_names)
    return {"status": "activated", "model_id": model_id}

@app.delete("/models/{model_id}")
async def delete_model(model_id: str):
    deleted = []
    patterns = [
        f"model_{model_id}.keras",
        f"confusion_matrix_{model_id}.png",
        f"classification_report_{model_id}.json",
        f"history_{model_id}.json",
        f"class_names_{model_id}.json",
    ]
    for pattern in patterns:
        path = os.path.join(MODELS_DIR, pattern)
        if os.path.exists(path):
            os.remove(path)
            deleted.append(pattern)
    active = _get_active_model()
    if active and active["model_id"] == model_id:
        active_path = os.path.join(MODELS_DIR, "active_model.json")
        if os.path.exists(active_path):
            os.remove(active_path)
    if not deleted:
        raise HTTPException(404, "Model not found")
    return {"status": "deleted", "model_id": model_id, "files": deleted}

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
    try:
        model = tf.keras.models.load_model(dest)
        model.summary()
    except Exception as e:
        os.remove(dest)
        raise HTTPException(400, f"Invalid model file: {e}")
    class_names = json.loads(class_names_json) if class_names_json else []
    class_names_path = os.path.join(MODELS_DIR, f"class_names_{model_id}.json")
    with open(class_names_path, "w") as f:
        json.dump(class_names, f)
    history = {
        "accuracy": [accuracy],
        "val_accuracy": [accuracy],
        "loss": [loss],
        "val_loss": [loss],
    }
    history_path = os.path.join(MODELS_DIR, f"history_{model_id}.json")
    with open(history_path, "w") as f:
        json.dump(history, f)
    if cm_file and cm_file.filename:
        cm_ext = os.path.splitext(cm_file.filename)[1].lower()
        if cm_ext not in (".png", ".jpg", ".jpeg"):
            raise HTTPException(400, "Confusion matrix file must be PNG, JPG, or JPEG")
        cm_content = await cm_file.read()
        cm_dest = os.path.join(MODELS_DIR, f"confusion_matrix_{model_id}.png")
        with open(cm_dest, "wb") as f:
            f.write(cm_content)
    return {
        "status": "registered",
        "model_id": model_id,
        "name": name or file.filename,
        "file": f"model_{model_id}.keras",
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)
