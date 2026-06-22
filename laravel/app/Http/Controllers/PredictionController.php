<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\Prediction;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PredictionController extends Controller
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $activeModel = $this->aiService->getActiveModel();
        return view('public.predict', [
            'hasActiveModel' => $activeModel['active'] ?? false,
            'modelInfo' => $activeModel['model'] ?? null,
        ]);
    }

    public function predict(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'patient_name' => 'required|string|max:255',
            'patient_age' => 'required|integer|min:1|max:150',
        ]);

        $activeModel = $this->aiService->getActiveModel();
        if (!($activeModel['active'] ?? false)) {
            $msg = 'Belum ada model aktif. Silakan hubungi admin.';
            if ($request->ajax()) return response()->json(['error' => $msg], 400);
            return back()->with('error', $msg);
        }

        $image = $request->file('image');
        $result = $this->aiService->predict($image->path(), $image->getClientOriginalName());

        if (!$result || isset($result['error']) || isset($result['detail']) || !isset($result['predicted_class'])) {
            $msg = $result['detail'] ?? ($result['error'] ?? 'Format gambar tidak didukung atau sistem mendeteksi ini bukan citra medis USG yang valid.');
            if ($request->ajax()) return response()->json(['error' => $msg], 400);
            return back()->with('error', $msg);
        }

        try {
            $imagePath = $image->store('predictions', 'public');

            $modelLabel = $result['model_id'] ?? ($activeModel['model']['model_id'] ?? 'unknown');
            $localModel = AiModel::where('model_id', $modelLabel)->first();

            $prediction = Prediction::create([
                'prediction_id' => $result['prediction_id'] ?? null,
                'ai_model_id' => $localModel?->id,
                'patient_name' => $request->input('patient_name'),
                'patient_age' => (int) $request->input('patient_age'),
                'image_path' => $imagePath,
                'original_name' => $image->getClientOriginalName(),
                'predicted_class' => $result['predicted_class'],
                'confidence' => $result['confidence'],
                'probabilities' => $result['probabilities'] ?? [],
                'model_label' => $modelLabel,
            ]);

            // Download Grad-CAM image from FastAPI if available
            if (!empty($result['grad_cam_url'])) {
                try {
                    $gradCamUrl = rtrim(env('AI_SERVICE_URL', 'http://localhost:8001'), '/') . $result['grad_cam_url'];
                    $gradCamContents = @file_get_contents($gradCamUrl);
                    if ($gradCamContents !== false) {
                        $gradCamPath = 'predictions/gradcam_' . uniqid() . '.png';
                        Storage::disk('public')->put($gradCamPath, $gradCamContents);
                        $prediction->update(['grad_cam_path' => $gradCamPath]);
                        $result['grad_cam_url'] = asset('storage/' . $gradCamPath);
                    }
                } catch (\Exception $e) {
                    // Grad-CAM tidak tersedia, lanjutkan
                }
            }

            $result['db_id'] = $prediction->id;
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal menyimpan hasil prediksi: ' . $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json($result);
        }

        return back()
            ->with('result', $result)
            ->with('patient_name', $request->input('patient_name'))
            ->with('patient_age', $request->input('patient_age'));
    }
}
