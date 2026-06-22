<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Services\AiService;
use Illuminate\Http\Request;

class AdminModelController extends Controller
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $remoteModels = $this->aiService->listModels();

        $localMap = [];

        foreach (AiModel::all() as $local) {
            $localMap[$local->model_id] = $local;
        }

        $models = [];
        foreach ($remoteModels as $rm) {
            $mid = $rm['model_id'];
            $entry = $rm;
            if (isset($localMap[$mid])) {
                $entry['local_name'] = $localMap[$mid]->name;
                $entry['local_notes'] = $localMap[$mid]->notes;
                $entry['local_id'] = $localMap[$mid]->id;
            } else {
                $entry['local_name'] = null;
                $entry['local_notes'] = null;
                $entry['local_id'] = null;
            }
            $models[] = $entry;
        }

        return view('admin.models', compact('models'));
    }

    public function activate(string $modelId)
    {
        $result = $this->aiService->activateSpecificModel($modelId);
        if (isset($result['error'])) {
            return back()->with('error', 'Gagal mengaktifkan model: ' . $result['error']);
        }

        AiModel::where('is_active', true)->update(['is_active' => false]);

        $model = AiModel::firstOrNew(['model_id' => $modelId]);
        if (!$model->exists) {
            $model->name = 'Model ' . $modelId;
            $model->class_names = ['Benign', 'Malignant', 'Normal'];
            $model->file_path = 'models/' . $modelId;
        }
        $model->is_active = true;
        $model->save();

        return back()->with('success', "Model berhasil diaktifkan.");
    }

    public function update(Request $request, string $modelId)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $model = AiModel::updateOrCreate(
            ['model_id' => $modelId],
            [
                'name' => $validated['name'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        if ($request->ajax()) {
            return response()->json(['success' => true, 'model' => $model]);
        }

        return back()->with('success', 'Model updated successfully.');
    }

    public function destroy(string $modelId)
    {
        $result = $this->aiService->deleteModel($modelId);

        AiModel::where('model_id', $modelId)->delete();

        if (isset($result['error'])) {
            if (request()->ajax()) {
                return response()->json(['error' => $result['error']], 502);
            }
            return back()->with('error', 'Gagal menghapus model: ' . $result['error']);
        }

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect('/admin/models')->with('success', 'Model berhasil dihapus beserta file-nya.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'model_file' => 'required|file|extensions:keras,h5,hdf5|max:512000',
            'name' => 'nullable|string|max:255',
            'class_names' => 'required|string',
            'accuracy' => 'required|numeric|min:0|max:1',
            'loss' => 'required|numeric|min:0|max:999',
            'cm_file' => 'nullable|file|mimes:png,jpg,jpeg|max:5120',
        ]);

        $file = $request->file('model_file');
        $name = $request->input('name', $file->getClientOriginalName());
        $extension = $file->getClientOriginalExtension();
        $classNames = array_map('trim', explode(',', $request->input('class_names')));
        $accuracy = (float) $request->input('accuracy');
        $loss = (float) $request->input('loss');
        $cmFile = $request->file('cm_file');

        $result = $this->aiService->registerModel(
            filePath: $file->path(),
            name: $name,
            classNames: $classNames,
            accuracy: $accuracy,
            loss: $loss,
            extension: $extension,
            cmFilePath: $cmFile?->path(),
            cmOriginalName: $cmFile?->getClientOriginalName(),
        );

        if (isset($result['error'])) {
            return back()->with('error', 'Gagal mendaftarkan model: ' . $result['error']);
        }

        if (!isset($result['model_id'])) {
            return back()->with('error', 'Gagal mendaftarkan model: FastAPI tidak mengembalikan model_id. Respon: ' . json_encode($result));
        }

        AiModel::create([
            'model_id' => $result['model_id'],
            'name' => $name,
            'class_names' => $classNames,
            'file_path' => $result['file'],
            'is_active' => false,
        ]);

        return redirect('/admin/models')->with('success', 'Model berhasil didaftarkan.');
    }
}
