@extends('layouts.admin')

@section('title', 'Predictions')
@section('page-title', 'Prediction History')
@section('active-predictions', 'bg-white/10')
@section('breadcrumb')
    <span class="text-slate-600">/</span>
    <span class="text-indigo-400 font-medium">Predictions</span>
@endsection

@section('content')
<div class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-6">
    @if(count($predictions) === 0)
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-slate-400 text-lg">No predictions yet</p>
            <p class="text-slate-500 text-sm mt-1">Predictions will appear here once users start using the system</p>
        </div>
    @else
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div class="relative flex-1 max-w-xs">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="search-input" placeholder="Search by class or file name..."
                    class="w-full pl-9 pr-4 py-2.5 bg-slate-900/50 border border-slate-600 rounded-lg text-sm text-white focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
            </div>
            <p class="text-sm text-slate-400">{{ count($predictions) }} total predictions</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full" id="predictions-table">
                <thead>
                    <tr class="border-b border-slate-700">
                        <th class="text-left py-3 px-4 text-sm font-medium text-slate-400 uppercase">No</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-slate-400 uppercase">Predicted Class</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-slate-400 uppercase">Confidence</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-slate-400 uppercase hidden md:table-cell">AI Model</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-slate-400 uppercase">Date</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-slate-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody id="predictions-tbody">
                    @foreach($predictions as $idx => $pred)
                    <tr class="border-b border-slate-700/50 hover:bg-slate-700/30 transition prediction-row"
                        id="row-{{ $pred->id }}"
                        data-id="{{ $pred->id }}"
                        data-class="{{ strtolower($pred->predicted_class) }}"
                        data-file="{{ strtolower($pred->original_name ?? '') }}">
                        <td class="py-3 px-4 text-sm text-slate-400">{{ $idx + 1 }}</td>
                        <td class="py-3 px-4">
                            <span class="font-medium text-slate-100">{{ $pred->predicted_class }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-2">
                            <div class="w-20 bg-slate-700 rounded-full h-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-indigo-500 to-sky-500 h-2 rounded-full" style="width: {{ $pred->confidence * 100 }}%"></div>
                            </div>
                            <span class="text-sm text-slate-400">{{ number_format($pred->confidence * 100, 1) }}%</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-sm text-slate-500 font-mono hidden md:table-cell">{{ \Illuminate\Support\Str::limit($pred->model_label ?? '-', 12) }}</td>
                        <td class="py-3 px-4 text-sm text-slate-500" title="{{ $pred->created_at }}">
                            {{ $pred->created_at->diffForHumans() }}
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-2">
                                <button onclick="showDetail({{ $pred->id }})"
                                        class="p-1.5 text-indigo-400 hover:bg-indigo-900/20 rounded-lg transition"
                                        title="Lihat Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <button onclick="confirmDeletePrediction({{ $pred->id }})"
                                        class="p-1.5 text-red-500 hover:bg-red-900/20 rounded-lg transition"
                                        title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="pagination" class="flex items-center justify-between mt-4 pt-4 border-t border-slate-700">
            <p class="text-sm text-slate-400" id="pagination-info"></p>
            <div class="flex gap-2">
                <button id="prev-page" class="px-3 py-1.5 border border-slate-600 text-slate-300 rounded-lg text-xs font-medium hover:bg-slate-700/50 transition disabled:opacity-40 disabled:cursor-not-allowed">Prev</button>
                <button id="next-page" class="px-3 py-1.5 border border-slate-600 text-slate-300 rounded-lg text-xs font-medium hover:bg-slate-700/50 transition disabled:opacity-40 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    @endif
</div>

{{-- Detail Modal --}}
<div id="detail-modal" class="fixed inset-0 z-50 hidden" aria-modal="true">
    <div class="absolute inset-0 bg-black/50" onclick="closeDetail()"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="bg-slate-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-slate-700">
                <h3 class="text-lg font-semibold text-white">Detail Prediksi</h3>
                <button onclick="closeDetail()" class="p-1 text-slate-500 hover:text-slate-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-5" id="modal-body">
                <div id="modal-loading" class="text-center py-8">
                    <div class="animate-spin w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mx-auto mb-2"></div>
                    <p class="text-slate-500 text-sm">Loading...</p>
                </div>
                <div id="modal-content" class="hidden"></div>

                {{-- Patient Info Section --}}
                <div id="modal-patient" class="hidden bg-indigo-900/20 border border-indigo-800/30 rounded-xl p-4">
                    <h4 class="text-sm font-medium text-slate-300 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Informasi Pasien
                    </h4>
                    <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                        <div>
                            <span class="text-slate-500">Nama Pasien:</span><br>
                            <span id="modal-patient-name" class="font-medium text-slate-100">-</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Usia:</span><br>
                            <span id="modal-patient-age" class="font-medium text-slate-100">-</span>
                        </div>
                    </div>
                    <img id="modal-patient-image" class="w-full max-h-48 rounded-lg object-contain border border-indigo-800/30 bg-slate-900/30" alt="Foto Pasien" onerror="this.style.display='none'">
                </div>
            </div>
        </div>
    </div>
</div>

<form id="delete-form" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
var predictionsData = {!! $predictions->toJson() !!};
var currentPage = 1;
var perPage = 10;

// ===== Search + Pagination =====
function filterAndPaginate() {
    var query = document.getElementById('search-input').value.toLowerCase().trim();
    var rows = document.querySelectorAll('.prediction-row');
    var filtered = [];
    var allData = [];

    rows.forEach(function(row, idx) {
        var cls = row.getAttribute('data-class') || '';
        var file = row.getAttribute('data-file') || '';
        var match = cls.includes(query) || file.includes(query);
        row.style.display = 'none';
        if (match) {
            filtered.push(row);
            allData.push(predictionsData[idx]);
        }
    });

    var totalPages = Math.ceil(filtered.length / perPage) || 1;
    if (currentPage > totalPages) currentPage = totalPages;

    var start = (currentPage - 1) * perPage;
    var end = start + perPage;
    var pageItems = filtered.slice(start, end);

    pageItems.forEach(function(row) { row.style.display = ''; });

    var info = document.getElementById('pagination-info');
    if (info) {
        info.textContent = 'Showing ' + (start + 1) + '-' + Math.min(end, filtered.length) + ' of ' + filtered.length;
    }

    var prevBtn = document.getElementById('prev-page');
    var nextBtn = document.getElementById('next-page');
    if (prevBtn) prevBtn.disabled = (currentPage <= 1);
    if (nextBtn) nextBtn.disabled = (currentPage >= totalPages);
}

var searchInput = document.getElementById('search-input');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        currentPage = 1;
        filterAndPaginate();
    });
}

document.getElementById('prev-page')?.addEventListener('click', function() {
    if (currentPage > 1) { currentPage--; filterAndPaginate(); }
});

document.getElementById('next-page')?.addEventListener('click', function() {
    currentPage++; filterAndPaginate();
});

if (document.querySelector('.prediction-row')) filterAndPaginate();

// ===== Detail Modal =====
function showDetail(id) {
    var modal = document.getElementById('detail-modal');
    var loading = document.getElementById('modal-loading');
    var content = document.getElementById('modal-content');

    modal.classList.remove('hidden');
    loading.style.display = 'block';
    content.classList.add('hidden');
    content.innerHTML = '';

    var pred = predictionsData.find(function(p) { return p.id === id; });
    if (!pred) {
        loading.style.display = 'none';
        content.innerHTML = '<p class="text-red-500 text-center">Data tidak ditemukan.</p>';
        content.classList.remove('hidden');
        return;
    }

    var probs = pred.probabilities || {};
    var probKeys = Object.keys(probs);
    var sortedProbs = probKeys.sort(function(a, b) { return probs[b] - probs[a]; });

    var imageUrl = pred.image_path
        ? '{{ asset("storage") }}/' + pred.image_path
        : null;

    // Patient Info
    var patientDiv = document.getElementById('modal-patient');
    var patientNameEl = document.getElementById('modal-patient-name');
    var patientAgeEl = document.getElementById('modal-patient-age');
    var patientImageEl = document.getElementById('modal-patient-image');
    if (pred.patient_name || pred.patient_age || imageUrl) {
        patientDiv.classList.remove('hidden');
        patientNameEl.textContent = pred.patient_name || '-';
        patientAgeEl.textContent = pred.patient_age ? pred.patient_age + ' Tahun' : '-';
        if (imageUrl) {
            patientImageEl.style.display = '';
            patientImageEl.src = imageUrl;
        } else {
            patientImageEl.style.display = 'none';
        }
    } else {
        patientDiv.classList.add('hidden');
    }

    var html = '';

    if (sortedProbs.length > 0) {
        html += '<div class="mb-5">';
        html += '<h4 class="text-sm font-medium text-slate-300 mb-3">Probability Distribution</h4>';
        html += '<div class="space-y-2.5">';
        var topClass = sortedProbs[0];
        var topProb = probs[topClass];
        var maxBarWidth = Math.max(topProb * 100, 5);
        for (var i = 0; i < sortedProbs.length; i++) {
            var cls = sortedProbs[i];
            var prob = probs[cls];
            var isTop = (i === 0);
            var barWidth = Math.max((prob / topProb) * maxBarWidth, 3);
            var barColor = isTop ? 'bg-gradient-to-r from-indigo-500 to-sky-500' : 'bg-slate-600';
            html += '<div>';
            html += '<div class="flex justify-between text-sm mb-0.5">';
            html += '<span class="' + (isTop ? 'font-semibold text-indigo-300' : 'text-slate-300') + '">' + escapeHtml(cls) + '</span>';
            html += '<span class="' + (isTop ? 'font-semibold text-indigo-400' : 'text-slate-500') + '">' + (prob * 100).toFixed(1) + '%</span>';
            html += '</div>';
            html += '<div class="w-full bg-slate-700 rounded-full h-2.5 overflow-hidden">';
            html += '<div class="' + barColor + ' h-2.5 rounded-full transition-all" style="width:' + barWidth + '%"></div>';
            html += '</div>';
            html += '</div>';
        }
        html += '</div>';
        html += '</div>';
    }

    if (imageUrl) {
        html += '<div class="mb-4 rounded-xl overflow-hidden bg-slate-900/30 flex items-center justify-center p-2 border border-slate-700">';
        html += '<img src="' + imageUrl + '" alt="Medical Image" class="max-h-64 rounded-lg object-contain" onerror="this.parentElement.innerHTML=\'<p class=\\\'text-slate-500 py-8\\\'>Gambar tidak tersedia</p>\'">';
        html += '</div>';
    }

    // Grad-CAM
    var gradcamPath = pred.grad_cam_path;
    if (gradcamPath) {
        var gradcamUrl = '{{ asset("storage") }}/' + gradcamPath;
        html += '<div class="mb-4 rounded-xl overflow-hidden bg-slate-900/30 p-2 border border-amber-800/50">';
        html += '<h4 class="text-sm font-medium text-amber-400 mb-2 flex items-center gap-2">';
        html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
        html += 'Visualisasi Grad-CAM';
        html += '</h4>';
        html += '<p class="text-xs text-slate-500 mb-3">Area merah/kuning = area fokus model dalam mengambil keputusan.</p>';
        html += '<img src="' + gradcamUrl + '" alt="Grad-CAM" class="max-h-64 rounded-lg object-contain mx-auto" onerror="this.parentElement.innerHTML=\'<p class=\\\'text-slate-500 py-8\\\'>Grad-CAM tidak tersedia</p>\'">';
        html += '</div>';
    }

    html += '<div class="text-center mb-4">';
    html += '<p class="text-sm text-slate-500 uppercase tracking-wide">Predicted Class</p>';
    html += '<p class="text-2xl font-bold bg-gradient-to-r from-indigo-400 to-sky-400 bg-clip-text text-transparent mt-1">' + escapeHtml(pred.predicted_class) + '</p>';
    html += '<div class="mt-2 inline-flex items-center space-x-2 bg-indigo-900/30 px-4 py-2 rounded-full">';
    html += '<span class="text-sm font-medium text-indigo-300">Confidence</span>';
    html += '<span class="text-lg font-bold text-indigo-400">' + (pred.confidence * 100).toFixed(1) + '%</span>';
    html += '</div>';
    html += '</div>';

    html += '<div class="bg-slate-700/30 rounded-xl p-4 mb-4">';
    html += '<div class="grid grid-cols-2 gap-3 text-sm">';
    html += '<div><span class="text-slate-500">Model:</span><br><span class="font-medium text-slate-300">' + escapeHtml(pred.model_label || '-') + '</span></div>';
    html += '<div><span class="text-slate-500">File:</span><br><span class="font-medium text-slate-300 truncate block flex items-center gap-1.5">' + escapeHtml(pred.original_name || '-');
    if (imageUrl) {
        html += '<a href="' + imageUrl + '" download="' + escapeHtml(pred.original_name || 'image') + '" class="inline-flex text-indigo-400 hover:text-indigo-300 transition shrink-0" title="Download Gambar">';
        html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>';
        html += '</a>';
    }
    html += '</span></div>';
    html += '<div><span class="text-slate-500">Date:</span><br><span class="font-medium text-slate-300">' + new Date(pred.created_at).toLocaleString('id-ID') + '</span></div>';
    html += '</div>';
    html += '</div>';

    loading.style.display = 'none';
    content.innerHTML = html;
    content.classList.remove('hidden');
}

function closeDetail() {
    document.getElementById('detail-modal').classList.add('hidden');
}

// ===== Delete =====
function confirmDeletePrediction(id) {
    showConfirm(
        'Delete Prediction',
        'Yakin ingin menghapus prediksi ini? Data tidak dapat dikembalikan.',
        function() { executeDeletePrediction(id); }
    );
}

function executeDeletePrediction(id) {
    var form = document.getElementById('delete-form');
    form.action = '/admin/predictions/' + id;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', form.action);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('input[name=_token]').value);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            var row = document.getElementById('row-' + id);
            if (row) row.remove();
            predictionsData = predictionsData.filter(function(p) { return p.id !== id; });
            filterAndPaginate();
            showToast('Prediction deleted.', 'success');
            if (predictionsData.length === 0) {
                setTimeout(function() { location.reload(); }, 500);
            }
        } else {
            try {
                var err = JSON.parse(xhr.responseText);
                showToast('Failed: ' + (err.error || err.message || 'Unknown error'), 'error');
            } catch (_) {
                showToast('Failed to delete.', 'error');
            }
        }
    };
    xhr.onerror = function() {
        showToast('Connection lost.', 'error');
    };
    xhr.send(new FormData(form));
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('detail-modal')) {
        closeDetail();
    }
});
</script>
@endpush
