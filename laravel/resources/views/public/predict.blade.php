@extends('layouts.public')

@section('title', 'Prediksi')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white">Klasifikasi Citra Medis</h1>
        <p class="mt-2 text-slate-400">Unggah citra medis untuk mendapatkan hasil klasifikasi berbasis AI</p>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-900/30 border border-red-700/50 text-red-300 rounded-lg">{{ session('error') }}</div>
    @endif

    @if(!($hasActiveModel ?? false))
        <div class="mb-6 p-6 bg-amber-900/30 border border-amber-700/50 rounded-lg text-center">
            <svg class="w-12 h-12 text-amber-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <h2 class="text-lg font-semibold text-amber-300">Tidak Ada Model Aktif</h2>
            <p class="mt-1 text-amber-400">Sistem belum memiliki model AI yang aktif. Hubungi administrator untuk mengaktifkan model.</p>
        </div>
    @else
        <form id="predict-form" action="/tes/predict" method="POST" enctype="multipart/form-data" class="mb-8">
            @csrf

            {{-- Patient Form --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="patient_name" class="block text-sm font-medium text-slate-300 mb-1.5">Nama Lengkap Pasien <span class="text-red-400">*</span></label>
                    <input type="text" id="patient_name" name="patient_name" required
                        class="w-full bg-slate-800/60 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        placeholder="Masukkan nama pasien">
                </div>
                <div>
                    <label for="patient_age" class="block text-sm font-medium text-slate-300 mb-1.5">Usia <span class="text-red-400">*</span></label>
                    <input type="number" id="patient_age" name="patient_age" min="1" max="150" required
                        class="w-full bg-slate-800/60 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        placeholder="Usia pasien">
                </div>
            </div>

            {{-- Dropzone with Preview --}}
            <div id="dropzone"
                class="border-2 border-dashed border-slate-700 rounded-2xl p-8 text-center hover:border-indigo-500/50 transition cursor-pointer relative overflow-hidden"
                onclick="document.getElementById('image-input').click()">
                <div id="dropzone-placeholder">
                    <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-slate-400 text-lg">Tap/klik untuk ambil foto atau pilih gambar</p>
                    <p class="text-slate-600 text-sm mt-1">JPG, JPEG, PNG (max 10MB)</p>
                </div>
                <img id="image-preview" class="max-h-[400px] mx-auto rounded-xl hidden" alt="Preview">
                <button type="button" id="remove-image"
                    class="absolute top-3 right-3 bg-slate-900/80 text-slate-400 hover:text-white p-1.5 rounded-lg hidden transition"
                    onclick="event.stopPropagation(); removeImage()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <input id="image-input" type="file" name="image" accept="image/*" class="hidden">
            </div>

            {{-- Disclaimer --}}
            <div class="mt-5 flex items-start gap-3 bg-slate-800/40 border border-slate-700/50 rounded-xl p-4">
                <input type="checkbox" id="disclaimer" class="mt-0.5 w-4 h-4 rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer shrink-0">
                <label for="disclaimer" class="text-xs leading-relaxed text-slate-400 select-none cursor-pointer">
                    Saya memahami bahwa hasil ini adalah prediksi model kecerdasan buatan. Kebenaran medis sepenuhnya tetap harus dikonsultasikan kepada dokter atau pihak medis untuk mendapatkan penanganan lebih lanjut.
                </label>
            </div>

            {{-- Submit --}}
            <div class="mt-6 text-center">
                <button type="submit" id="analyze-btn" disabled
                    class="w-full sm:w-auto bg-gradient-to-r from-indigo-500 to-cyan-500 text-white px-10 py-3 rounded-xl text-base font-medium opacity-50 cursor-not-allowed transition-all glow-btn">
                    Analisis Gambar
                </button>
            </div>
        </form>

        {{-- Result Section (hidden initially) --}}
        <div id="result-section" class="hidden mt-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="flex flex-col gap-6">
                    {{-- Patient Info Card --}}
                    <div class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-sm">
                        <h2 class="text-lg font-semibold text-white mb-5 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Informasi Pasien
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wide">Nama Pasien</p>
                                <p id="result-name" class="text-white font-medium mt-0.5">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wide">Usia</p>
                                <p id="result-age" class="text-white font-medium mt-0.5">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wide mb-2">Foto Rontgen</p>
                                <img id="result-image" class="w-full rounded-xl border border-slate-700" alt="Patient X-Ray">
                            </div>
                        </div>
                    </div>

                    {{-- Grad-CAM Card --}}
                    <div id="result-gradcam-card" class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-sm hidden">
                        <h2 class="text-lg font-semibold text-white mb-5 flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Visualisasi Grad-CAM
                        </h2>
                        <p class="text-xs text-slate-500 mb-4">Area berwarna merah/kuning menunjukkan area yang menjadi fokus model saat mengambil keputusan.</p>
                        <img id="result-gradcam" class="w-full rounded-xl border border-slate-700" alt="Grad-CAM Heatmap" style="display:none" onerror="document.getElementById('result-gradcam').style.display='none';document.getElementById('result-gradcam-na').style.display='block'">
                        <p id="result-gradcam-na" class="text-slate-500 text-sm text-center py-8">Peta panas tidak tersedia</p>
                    </div>
                </div>

                {{-- Classification Result Card --}}
                <div id="result-classification" class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-sm">
                    <h2 class="text-lg font-semibold text-white mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Hasil Klasifikasi
                    </h2>
                    <div class="text-center mb-6">
                        <p class="text-sm text-slate-500 uppercase tracking-wide">Kelas yang Diprediksi</p>
                        <p id="result-class" class="text-2xl font-bold bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent mt-1">-</p>
                        <div class="mt-3 inline-flex items-center space-x-2 bg-indigo-900/40 px-4 py-2 rounded-full border border-indigo-700/30">
                            <span class="text-sm font-medium text-indigo-300">Tingkat Kepercayaan</span>
                            <span id="result-confidence" class="text-lg font-bold text-indigo-400">-</span>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wide mb-4">Distribusi Probabilitas</h3>
                    <div id="result-probabilities" class="space-y-3"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Fallback: show result from session (non-AJAX) --}}
    @if(session('result'))
        @php $sess = session('result') @endphp
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-sm">
                <h2 class="text-lg font-semibold text-white mb-5 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Informasi Pasien
                </h2>
                <div class="space-y-4">
                    <div><p class="text-xs text-slate-500 uppercase tracking-wide">Nama Pasien</p><p class="text-white font-medium mt-0.5">{{ session('patient_name', '-') }}</p></div>
                    <div><p class="text-xs text-slate-500 uppercase tracking-wide">Usia</p><p class="text-white font-medium mt-0.5">{{ session('patient_age', '-') }}</p></div>
                </div>
            </div>
            <div class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-sm">
                <h2 class="text-lg font-semibold text-white mb-5">Hasil Klasifikasi</h2>
                <div class="text-center mb-6">
                    <p class="text-sm text-slate-500 uppercase tracking-wide">Kelas yang Diprediksi</p>
                    <p class="text-2xl font-bold bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent mt-1">{{ $sess['predicted_class'] }}</p>
                    <div class="mt-3 inline-flex items-center space-x-2 bg-indigo-900/40 px-4 py-2 rounded-full border border-indigo-700/30">
                        <span class="text-sm font-medium text-indigo-300">Tingkat Kepercayaan</span>
                        <span class="text-lg font-bold text-indigo-400">{{ number_format($sess['confidence'] * 100, 1) }}%</span>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wide mb-4">Distribusi Probabilitas</h3>
                <div class="space-y-3">
                    @foreach($sess['probabilities'] as $class => $prob)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-300 font-medium">{{ $class }}</span>
                            <span class="text-slate-500">{{ number_format($prob * 100, 1) }}%</span>
                        </div>
                        <div class="w-full bg-slate-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-cyan-500 h-3 rounded-full transition-all duration-500" style="width: {{ $prob * 100 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @if(!empty($sess['grad_cam_url']))
        <div class="mt-6 bg-slate-800/60 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-sm">
            <h2 class="text-lg font-semibold text-white mb-5 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Visualisasi Grad-CAM
            </h2>
            <p class="text-xs text-slate-500 mb-4">Area berwarna merah/kuning menunjukkan area yang menjadi fokus model saat mengambil keputusan.</p>
            <img src="{{ $sess['grad_cam_url'] }}" class="w-full max-w-lg rounded-xl border border-slate-700 mx-auto" alt="Grad-CAM Heatmap">
        </div>
        @endif
    @endif
@endsection

@push('scripts')
<script>
(function() {
    var form = document.getElementById('predict-form');
    if (!form) return;

    var input = document.getElementById('image-input');
    var preview = document.getElementById('image-preview');
    var placeholder = document.getElementById('dropzone-placeholder');
    var removeBtn = document.getElementById('remove-image');
    var dropzone = document.getElementById('dropzone');
    var analyzeBtn = document.getElementById('analyze-btn');
    var nameInput = document.getElementById('patient_name');
    var ageInput = document.getElementById('patient_age');
    var disclaimer = document.getElementById('disclaimer');

    var resultSection = document.getElementById('result-section');
    var resultName = document.getElementById('result-name');
    var resultAge = document.getElementById('result-age');
    var resultImage = document.getElementById('result-image');
    var resultClass = document.getElementById('result-class');
    var resultConfidence = document.getElementById('result-confidence');
    var resultProbabilities = document.getElementById('result-probabilities');

    var hasImage = false;
    var currentFile = null;

    function checkForm() {
        var valid = nameInput.value.trim() !== '' &&
                    ageInput.value.trim() !== '' &&
                    parseInt(ageInput.value) > 0 &&
                    hasImage &&
                    disclaimer.checked;
        if (valid) {
            analyzeBtn.disabled = false;
            analyzeBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            analyzeBtn.disabled = true;
            analyzeBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    nameInput.addEventListener('input', checkForm);
    ageInput.addEventListener('input', checkForm);
    disclaimer.addEventListener('change', checkForm);

    input.addEventListener('change', function() {
        var file = this.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            showToast('Hanya file gambar yang diperbolehkan.', 'error');
            return;
        }

        currentFile = file;
        hasImage = true;
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            removeBtn.classList.remove('hidden');
            checkForm();
        };
        reader.readAsDataURL(file);
    });

    window.removeImage = function() {
        input.value = '';
        preview.src = '';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
        currentFile = null;
        hasImage = false;
        checkForm();
    };

    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-indigo-500', 'bg-indigo-500/5');
    });

    dropzone.addEventListener('dragleave', function() {
        this.classList.remove('border-indigo-500', 'bg-indigo-500/5');
    });

    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-indigo-500', 'bg-indigo-500/5');
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        }
    });

    function populateResult(data) {
        resultName.innerText = nameInput.value.trim();
        resultAge.innerText = ageInput.value.trim() + ' tahun';

        if (currentFile) {
            resultImage.src = URL.createObjectURL(currentFile);
        }

        resultClass.innerText = data.predicted_class;
        resultConfidence.innerText = (data.confidence * 100).toFixed(1) + '%';

        resultProbabilities.innerHTML = '';
        for (var cls in data.probabilities) {
            var prob = data.probabilities[cls];
            var pct = (prob * 100).toFixed(1);
            var div = document.createElement('div');
            div.innerHTML =
                '<div class="flex justify-between text-sm mb-1">' +
                    '<span class="text-slate-300 font-medium">' + cls + '</span>' +
                    '<span class="text-slate-500">' + pct + '%</span>' +
                '</div>' +
                '<div class="w-full bg-slate-700 rounded-full h-3 overflow-hidden">' +
                    '<div class="bg-gradient-to-r from-indigo-500 to-cyan-500 h-3 rounded-full transition-all duration-500" style="width: ' + pct + '%"></div>' +
                '</div>';
            resultProbabilities.appendChild(div);
        }

        // Grad-CAM
        var gradcamCard = document.getElementById('result-gradcam-card');
        var gradcamImg = document.getElementById('result-gradcam');
        var gradcamNa = document.getElementById('result-gradcam-na');
        if (data.grad_cam_url) {
            var baseUrl = 'http://127.0.0.1:8001';
            var imgUrl = data.grad_cam_url.startsWith('http') ? data.grad_cam_url : baseUrl + data.grad_cam_url;
            gradcamImg.src = imgUrl;
            gradcamImg.style.display = 'block';
            gradcamNa.style.display = 'none';
            gradcamCard.classList.remove('hidden');
        } else {
            gradcamImg.style.display = 'none';
            gradcamNa.style.display = 'block';
            gradcamCard.classList.add('hidden');
        }

        resultSection.classList.remove('hidden');
        resultSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (analyzeBtn.disabled) return;

        analyzeBtn.disabled = true;
        analyzeBtn.textContent = 'Memproses...';

        var fd = new FormData();
        fd.append('_token', document.querySelector('input[name=_token]').value);
        fd.append('image', currentFile);
        fd.append('patient_name', nameInput.value.trim());
        fd.append('patient_age', ageInput.value.trim());

        var xhr = new XMLHttpRequest();

        xhr.addEventListener('load', function() {
            analyzeBtn.disabled = false;
            analyzeBtn.textContent = 'Analisis Gambar';

            var data;
            try {
                data = JSON.parse(xhr.responseText);
            } catch (_) {
                showToast('Server mengembalikan respons tidak terduga (status ' + xhr.status + ').', 'error');
                return;
            }

            if (xhr.status >= 200 && xhr.status < 300) {
                populateResult(data);
                showToast('Analisis berhasil!', 'success');
            } else {
                showToast(data.error || 'Analisis gagal. Silakan coba lagi.', 'error');
            }
        });

        xhr.addEventListener('error', function() {
            analyzeBtn.disabled = false;
            analyzeBtn.textContent = 'Analisis Gambar';
            showToast('Koneksi terputus. Silakan coba lagi.', 'error');
        });

        xhr.open('POST', '/tes/predict');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(fd);
    });

    if (typeof showToast !== 'function') {
        window.showToast = function(message, type) {
            var container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'fixed top-4 right-4 z-[100] flex flex-col space-y-2 w-80 pointer-events-none';
                document.body.appendChild(container);
            }
            var toast = document.createElement('div');
            var bg = type === 'error' ? 'bg-red-900/80 border-red-700 text-red-200' : 'bg-green-900/80 border-green-700 text-green-200';
            toast.className = bg + ' border rounded-xl p-4 shadow-lg flex items-start gap-3 transition-all duration-300 translate-x-full opacity-0 pointer-events-auto';
            toast.innerHTML = '<p class="text-sm flex-1">' + message + '</p>';
            container.appendChild(toast);
            requestAnimationFrame(function() {
                toast.classList.remove('translate-x-full', 'opacity-0');
            });
            setTimeout(function() {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(function() { if (toast.parentElement) toast.remove(); }, 300);
            }, 4000);
        };
    }
})();
</script>
@endpush