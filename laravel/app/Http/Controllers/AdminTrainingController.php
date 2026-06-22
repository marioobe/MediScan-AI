<?php

namespace App\Http\Controllers;

use App\Models\TrainingJob;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminTrainingController extends Controller
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $jobs = TrainingJob::with('admin')->latest()->get();
        return view('admin.training', compact('jobs'));
    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'dataset'          => 'required|file|mimes:zip|max:512000',
                'epochs'           => 'required|integer|min:1|max:200',
                'validation_split' => 'required|numeric|min:0.1|max:0.5',
                'batch_size'       => 'required|in:16,32,64',
                'image_size'       => 'required|in:128,160,224,256',
                'learning_rate'    => 'required|numeric|min:0.00001|max:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['error' => 'File tidak valid. Pastikan file adalah ZIP maksimal 500MB.'], 422);
            }
            throw $e;
        }

        try {
            $zip = $request->file('dataset');
            $result = $this->aiService->uploadDataset(
                zipPath: $zip->path(),
                originalName: $zip->getClientOriginalName(),
                epochs: (int) $request->input('epochs', 10),
                validationSplit: (float) $request->input('validation_split', 0.3),
                batchSize: (int) $request->input('batch_size', 32),
                imageSize: (int) $request->input('image_size', 224),
                learningRate: (float) $request->input('learning_rate', 0.0001),
            );
        } catch (\Exception $e) {
            $msg = 'Gagal menghubungi AI Service: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['error' => $msg], 502);
            }
            return back()->with('error', $msg);
        }

        if (isset($result['error'])) {
            if ($request->ajax()) {
                return response()->json(['error' => 'AI Service error: ' . $result['error']], 502);
            }
            return back()->with('error', 'AI Service error: ' . $result['error']);
        }

        if (!isset($result['job_id'])) {
            if ($request->ajax()) {
                return response()->json(['error' => 'AI Service tidak mengembalikan job ID.'], 502);
            }
            return back()->with('error', 'AI Service tidak mengembalikan job ID.');
        }

        try {
            $job = TrainingJob::create([
                'job_id' => $result['job_id'],
                'dataset_path' => $zip->getClientOriginalName(),
                'status' => 'pending',
                'created_by' => Auth::guard('admin')->id(),
            ]);
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Gagal menyimpan ke database: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal menyimpan ke database: ' . $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'redirect' => "/admin/training/{$job->id}",
            ]);
        }

        return redirect("/admin/training/{$job->id}")->with('success', 'Dataset uploaded. Training started.');
    }

    public function cancel(string $id)
    {
        $job = TrainingJob::findOrFail($id);
        $result = $this->aiService->cancelTraining($job->job_id);

        if (isset($result['error'])) {
            return back()->with('error', 'Gagal membatalkan training: ' . $result['error']);
        }

        $job->update(['status' => 'cancelled']);

        return redirect('/admin/training')->with('success', 'Training dibatalkan.');
    }

    public function show(string $id)
    {
        $job = TrainingJob::with('admin')->findOrFail($id);

        return view('admin.training-progress', [
            'job' => $job,
            'localId' => $job->id,
            'aiServiceUrl' => env('AI_SERVICE_URL', 'http://localhost:8001'),
        ]);
    }

    public function markCompleted(Request $request, string $id)
    {
        $job = TrainingJob::findOrFail($id);
        $modelId = $request->input('model_id');

        $updateData = [
            'status' => 'completed',
            'finished_at' => now(),
        ];

        if ($modelId) {
            $updateData['log'] = ($job->log ?? '') . "\n[MODEL_ID: {$modelId}]";
        }

        $job->update($updateData);

        return response()->json(['success' => true]);
    }

    public function webhook(Request $request)
    {
        Log::info($request->all());

        $validated = $request->validate([
            'job_id'         => 'required|string',
            'status'         => 'required|string',
            'accuracy'       => 'nullable|numeric',
            'loss'           => 'nullable|numeric',
            'epoch_history'  => 'nullable|array',
            'current_epoch'  => 'nullable|integer',
            'total_epoch'    => 'nullable|integer',
            'model_id'       => 'nullable|string',
        ]);

        $job = TrainingJob::where('job_id', $validated['job_id'])->first();
        if (!$job) {
            return response()->json(['error' => 'Training job not found'], 404);
        }

        $updateData = [
            'status'          => $validated['status'],
            'accuracy_result' => $validated['accuracy'] ?? null,
            'loss_result'     => $validated['loss'] ?? null,
            'epoch_history'   => $validated['epoch_history'] ?? null,
            'finished_at'     => now(),
        ];

        if (isset($validated['current_epoch'])) {
            $updateData['current_epoch'] = $validated['current_epoch'];
        }
        if (isset($validated['total_epoch'])) {
            $updateData['total_epoch'] = $validated['total_epoch'];
        }

        $modelId = $validated['model_id'] ?? null;
        if ($modelId) {
            $updateData['log'] = ($job->log ?? '') . "\n[MODEL_ID: {$modelId}]";
        }

        $job->update($updateData);

        return response()->json(['success' => true]);
    }
}
