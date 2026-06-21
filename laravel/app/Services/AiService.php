<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('AI_SERVICE_URL', 'http://localhost:8001'), '/');
        $this->timeout = 5;
    }

    protected function safeGet(string $url, array $default = ['active' => false, 'error' => 'Service unavailable']): array
    {
        try {
            $response = Http::timeout($this->timeout)->get($url);
            return $response->json() ?? $default;
        } catch (\Exception $e) {
            return array_merge($default, ['error' => $e->getMessage()]);
        }
    }

    protected function safePost(string $url, array $data = [], array $default = ['error' => 'Service unavailable']): array
    {
        try {
            $response = Http::timeout($this->timeout)->post($url, $data);
            return $response->json() ?? $default;
        } catch (\Exception $e) {
            return array_merge($default, ['error' => $e->getMessage()]);
        }
    }

    public function health(): array
    {
        return $this->safeGet("{$this->baseUrl}/health");
    }

    public function uploadDataset(
        string $zipPath,
        string $originalName,
        int $epochs = 10,
        float $validationSplit = 0.3,
        int $batchSize = 32,
        int $imageSize = 224,
        float $learningRate = 0.0001
    ): array {
        try {
            $fileContent = file_get_contents($zipPath);
            if ($fileContent === false) {
                return ['error' => 'Gagal membaca file ZIP dari penyimpanan sementara.'];
            }
            $response = Http::timeout(300)->attach(
                'file',
                $fileContent,
                $originalName
            )->post("{$this->baseUrl}/train", [
                'epochs'           => $epochs,
                'validation_split' => $validationSplit,
                'batch_size'       => $batchSize,
                'image_size'       => $imageSize,
                'learning_rate'    => $learningRate,
            ]);
            $body = $response->json();
            if ($body === null) {
                $status = $response->status();
                return ['error' => "AI Service merespon dengan status {$status} (bukan JSON). Mungkin file terlalu besar atau service sibuk."];
            }
            return $body;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['error' => 'Timeout atau koneksi gagal ke AI Service. Pastikan FastAPI berjalan di port 8001.'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getTrainingStatus(string $jobId): array
    {
        return $this->safeGet("{$this->baseUrl}/train/{$jobId}/status");
    }

    public function getTrainingLog(string $jobId): array
    {
        return $this->safeGet("{$this->baseUrl}/train/{$jobId}/log", ['log' => '']);
    }

    public function getTrainingResult(string $jobId): array
    {
        return $this->safeGet("{$this->baseUrl}/train/{$jobId}/result");
    }

    public function activateModel(string $jobId): array
    {
        return $this->safePost("{$this->baseUrl}/train/{$jobId}/activate");
    }

    public function cancelTraining(string $jobId): array
    {
        return $this->safePost("{$this->baseUrl}/train/{$jobId}/cancel");
    }

    public function predict(string $imagePath, string $originalName): array
    {
        try {
            $response = Http::timeout(30)->attach(
                'file',
                file_get_contents($imagePath),
                $originalName
            )->post("{$this->baseUrl}/predict");
            return $response->json() ?? ['error' => 'Empty response'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getActiveModel(): array
    {
        return $this->safeGet("{$this->baseUrl}/models/active", ['active' => false, 'model' => null]);
    }

    public function listModels(): array
    {
        $result = $this->safeGet("{$this->baseUrl}/models/list", []);
        if (isset($result['error'])) {
            return [];
        }
        if (is_array($result) && isset($result['value']) && is_array($result['value'])) {
            return $result['value'];
        }
        return is_array($result) ? $result : [];
    }

    public function activateSpecificModel(string $modelId): array
    {
        return $this->safePost("{$this->baseUrl}/models/{$modelId}/activate");
    }

    public function getConfusionMatrixUrl(string $modelId): string
    {
        return "{$this->baseUrl}/files/confusion_matrix/{$modelId}";
    }

    public function getPredictions(): array
    {
        $result = $this->safeGet("{$this->baseUrl}/predictions", []);
        if (is_array($result) && isset($result['value']) && is_array($result['value'])) {
            return $result['value'];
        }
        return is_array($result) ? $result : [];
    }

    public function deleteModel(string $modelId): array
    {
        try {
            $response = Http::timeout(30)->delete("{$this->baseUrl}/models/{$modelId}");
            return $response->json() ?? ['error' => 'Empty response'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function registerModel(
        string $filePath,
        string $name,
        array $classNames = [],
        float $accuracy = 0.0,
        float $loss = 0.0,
        string $extension = 'keras',
        ?string $cmFilePath = null,
        ?string $cmOriginalName = null,
    ): array {
        try {
            $filenameWithExt = Str::slug($name) . '.' . $extension;
            $request = Http::timeout(120)->attach(
                'file',
                file_get_contents($filePath),
                $filenameWithExt
            );
            if ($cmFilePath && $cmOriginalName) {
                $request->attach(
                    'cm_file',
                    file_get_contents($cmFilePath),
                    $cmOriginalName
                );
            }
            $response = $request->post("{$this->baseUrl}/models/register", [
                'name' => $name,
                'class_names_json' => json_encode($classNames),
                'accuracy' => $accuracy,
                'loss' => $loss,
            ]);
            $body = $response->json();
            if ($response->failed() || !$body) {
                $detail = $body['detail'] ?? ($body['error'] ?? 'FastAPI returned status ' . $response->status());
                return ['error' => $detail];
            }
            return $body;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
