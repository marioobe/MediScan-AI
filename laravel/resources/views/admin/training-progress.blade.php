@extends('layouts.admin')

@section('title', 'Training Progress')
@section('page-title', 'Training Progress')
@section('active-training', 'bg-white/10')
@section('breadcrumb')
    <span class="text-slate-600">/</span>
    <a href="/admin/training" class="hover:text-indigo-400 transition text-slate-400">Training</a>
    <span class="text-slate-600">/</span>
    <span class="text-indigo-400 font-medium">Progress</span>
@endsection

@section('content')
@php $isFinal = in_array(strtolower($job->status), ['completed', 'failed']); @endphp
<div class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-6">
    @if($isFinal)
        {{-- Static view for already-completed/failed jobs --}}
        <div id="progress-content">
            <div class="flex flex-col lg:flex-row gap-6 mb-6">
                <div class="flex flex-col items-center justify-center shrink-0">
                    <div class="relative w-32 h-32">
                        <svg class="w-32 h-32 -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#334155" stroke-width="8"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="url(#grad)" stroke-width="8"
                                stroke-linecap="round" stroke-dasharray="326.73" stroke-dashoffset="0" class="transition-all duration-700"/>
                            <defs>
                                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#6366f1"/>
                                    <stop offset="100%" stop-color="#0ea5e9"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-2xl font-bold text-white">{{ $job->status === 'completed' ? '100%' : '0%' }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">Overall Progress</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 flex-1">
                    <div class="p-4 bg-slate-700/30 rounded-xl">
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Status</p>
                        <p class="text-lg font-semibold mt-1 {{ $job->status === 'completed' ? 'text-green-400' : 'text-red-400' }}">
                            {{ ucfirst($job->status) }}
                        </p>
                    </div>
                    @php
                        $lastEpoch = $job->current_epoch;
                        if (!$lastEpoch && $job->epoch_history) {
                            $lastEpoch = count($job->epoch_history);
                        }
                    @endphp
                    <div class="p-4 bg-slate-700/30 rounded-xl">
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Epoch</p>
                        <p class="text-lg font-semibold mt-1 text-white">{{ $lastEpoch ? $lastEpoch.' / '.($job->total_epoch ?? $lastEpoch) : '-' }}</p>
                    </div>
                    <div class="p-4 bg-slate-700/30 rounded-xl">
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Accuracy</p>
                        <p class="text-lg font-semibold mt-1 text-white">{{ $job->accuracy_result ? number_format($job->accuracy_result * 100, 1).'%' : '-' }}</p>
                    </div>
                    <div class="p-4 bg-slate-700/30 rounded-xl">
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Loss</p>
                        <p class="text-lg font-semibold mt-1 text-white">{{ $job->loss_result ? number_format($job->loss_result, 4) : '-' }}</p>
                    </div>
                </div>
            </div>

            @if($job->status === 'completed')
            <div class="mb-6 p-4 bg-green-900/30 border border-green-800/50 rounded-xl">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <p class="font-semibold text-green-300">Training Completed!</p>
                        <p class="text-sm text-green-400 mt-1">Accuracy: {{ number_format($job->accuracy_result * 100, 1) }}% | Loss: {{ $job->loss_result ? number_format($job->loss_result, 4) : '-' }}</p>
                    </div>
                    <button onclick="activateModel()" class="bg-gradient-to-r from-indigo-500 to-sky-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:shadow-lg transition">
                        Activate This Model
                    </button>
                </div>
            </div>
            @else
            <div class="mb-6 p-4 bg-red-900/30 border border-red-800/50 rounded-xl">
                <p class="font-semibold text-red-300">Training Failed</p>
                <p class="text-sm text-red-400 mt-1">{{ $job->error_message ?? 'Terjadi kesalahan selama pelatihan.' }}</p>
            </div>
            @endif

            <div class="mb-6">
                <h3 class="text-sm font-medium text-slate-300 mb-3">Epoch History</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-400 uppercase tracking-wide">
                                <th class="pb-2 pr-4">#</th>
                                <th class="pb-2 pr-4 text-right">Accuracy ↑</th>
                                <th class="pb-2 pr-4 text-right">Loss ↓</th>
                                <th class="pb-2 pr-4 text-right">Val Accuracy ↑</th>
                                <th class="pb-2 pr-4 text-right">Val Loss ↓</th>
                            </tr>
                        </thead>
                        @if($job->epoch_history)
                        <tbody>
                            @php $lastPhase = ''; @endphp
                            @foreach($job->epoch_history as $index => $ep)
                                @if(isset($ep['phase']) && $ep['phase'] !== $lastPhase)
                                    @if($lastPhase !== '')
                                    <tr class="phase-separator"><td colspan="5"><div class="border-t border-slate-700 my-1"></div></td></tr>
                                    @endif
                                    <tr class="phase-separator"><td colspan="5"><span class="phase-label">{{ $ep['phase'] }}</span></td></tr>
                                    @php $lastPhase = $ep['phase']; @endphp
                                @endif
                                <tr>
                                    <td class="py-2 pr-4 text-slate-400">{{ $ep['epoch'] }}</td>
                                    <td class="py-2 pr-4 text-right font-mono">{{ isset($ep['accuracy']) ? number_format($ep['accuracy'] * 100, 1).'%' : '-' }}</td>
                                    <td class="py-2 pr-4 text-right font-mono">{{ isset($ep['loss']) ? number_format($ep['loss'], 4) : '-' }}</td>
                                    <td class="py-2 pr-4 text-right font-mono font-semibold text-indigo-400">{{ isset($ep['val_accuracy']) ? number_format($ep['val_accuracy'] * 100, 1).'%' : '-' }}</td>
                                    <td class="py-2 pr-4 text-right font-mono">{{ isset($ep['val_loss']) ? number_format($ep['val_loss'], 4) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        @elseif($job->log)
                        <tbody>
                            @foreach(explode("\n", $job->log) as $epochLine)
                                @if(trim($epochLine))
                                <tr>
                                    <td class="py-2 pr-4 text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="py-2 pr-4 text-right font-mono">{{ $epochLine }}</td>
                                    <td class="py-2 pr-4 text-right font-mono">-</td>
                                    <td class="py-2 pr-4 text-right font-mono text-indigo-400">-</td>
                                    <td class="py-2 pr-4 text-right font-mono">-</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                        @else
                        <tbody>
                            <tr><td colspan="5" class="text-center text-slate-500 py-8">Data epoch tidak tersimpan. Riwayat hanya tersedia saat pelatihan berlangsung.</td></tr>
                        </tbody>
                        @endif
                    </table>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-medium text-slate-300 mb-2">Training Log</h3>
                <div class="bg-gray-900 text-green-400 rounded-xl p-4 h-48 overflow-y-auto font-mono text-xs leading-relaxed">
                    @if($job->log)
                        {{ $job->log }}
                    @else
                        Training telah selesai dengan sukses. Model siap digunakan.
                    @endif
                </div>
            </div>
        </div>
    @else
        {{-- Live polling view for in-progress jobs --}}
        <div id="initial-status" class="text-center py-8">
            <div class="animate-spin w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mx-auto mb-4"></div>
            <p class="text-slate-400">Connecting to training service...</p>
        </div>

        <div id="progress-content" style="display:none">
            <div class="flex flex-col lg:flex-row gap-6 mb-6">
                <div class="flex flex-col items-center justify-center shrink-0">
                    <div class="relative w-32 h-32">
                        <svg class="w-32 h-32 -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#334155" stroke-width="8"/>
                            <circle id="progress-ring" cx="60" cy="60" r="52" fill="none" stroke="url(#grad)" stroke-width="8"
                                stroke-linecap="round" stroke-dasharray="326.73" stroke-dashoffset="326.73" class="transition-all duration-700"/>
                            <defs>
                                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#6366f1"/>
                                    <stop offset="100%" stop-color="#0ea5e9"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span id="ring-pct" class="text-2xl font-bold text-white">0%</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">Overall Progress</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 flex-1">
                    <div class="p-4 bg-slate-700/30 rounded-xl">
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Status</p>
                        <p id="status-text" class="text-lg font-semibold mt-1 text-yellow-400">Pending</p>
                    </div>
                    <div class="p-4 bg-slate-700/30 rounded-xl">
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Epoch</p>
                        <p id="epoch-text" class="text-lg font-semibold mt-1 text-white">0 / 0</p>
                    </div>
                    <div class="p-4 bg-slate-700/30 rounded-xl">
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Accuracy</p>
                        <p id="accuracy-text" class="text-lg font-semibold mt-1 text-white">-</p>
                    </div>
                    <div class="p-4 bg-slate-700/30 rounded-xl">
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Loss</p>
                        <p id="loss-text" class="text-lg font-semibold mt-1 text-white">-</p>
                    </div>
                </div>
            </div>

            <div class="mb-6 hidden">
                <div class="flex justify-between text-sm text-slate-400 mb-1">
                    <span>Progress</span>
                    <span id="progress-label">0%</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-4 overflow-hidden">
                    <div id="progress-bar" class="bg-gradient-to-r from-indigo-500 to-sky-500 h-4 rounded-full transition-all duration-500" style="width:0%"></div>
                </div>
            </div>

            <div id="completed-section" style="display:none" class="mb-6 p-4 bg-green-900/30 border border-green-800/50 rounded-xl">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <p class="font-semibold text-green-300">Training Completed!</p>
                        <p id="completed-metrics" class="text-sm text-green-400 mt-1"></p>
                    </div>
                    <button onclick="activateModel()" class="bg-gradient-to-r from-indigo-500 to-sky-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:shadow-lg transition">
                        Activate This Model
                    </button>
                </div>
            </div>

            <div id="failed-section" style="display:none" class="mb-6 p-4 bg-red-900/30 border border-red-800/50 rounded-xl">
                <p class="font-semibold text-red-300">Training Failed</p>
                <p id="error-text" class="text-sm text-red-400 mt-1"></p>
            </div>

            <div class="mb-6">
                <h3 class="text-sm font-medium text-slate-300 mb-3">Epoch History</h3>
                <div class="overflow-x-auto">
                    <table id="epoch-table" class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-400 uppercase tracking-wide">
                                <th class="pb-2 pr-4">#</th>
                                <th class="pb-2 pr-4 text-right">Accuracy ↑</th>
                                <th class="pb-2 pr-4 text-right">Loss ↓</th>
                                <th class="pb-2 pr-4 text-right">Val Accuracy ↑</th>
                                <th class="pb-2 pr-4 text-right">Val Loss ↓</th>
                            </tr>
                        </thead>
                        <tbody id="epoch-tbody">
                            <tr><td colspan="5" class="text-center text-slate-500 py-8">Waiting for epoch data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-medium text-slate-300 mb-2">Training Log</h3>
                <div id="log-box" class="bg-gray-900 text-green-400 rounded-xl p-4 h-48 overflow-y-auto font-mono text-xs leading-relaxed">
                    Waiting for log output...
                </div>
            </div>
        </div>
    @endif
</div>

<style>
@keyframes pulse-row {
    0%, 100% { background-color: rgba(99, 102, 241, 0.08); }
    50% { background-color: rgba(99, 102, 241, 0.18); }
}
tr.current-row td {
    animation: pulse-row 1.5s ease-in-out infinite;
    font-weight: 600;
}
tr.phase-separator td {
    padding-top: 12px !important;
    padding-bottom: 4px !important;
}
.phase-separator .phase-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6366f1;
}
tbody tr:not(.phase-separator):not(.current-row):nth-child(even) td {
    background-color: #1e293b;
}
</style>
@endsection

@push('scripts')
<script>
function activateModel() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Activating...';
    fetch('{{ $aiServiceUrl }}/train/{{ $job->job_id }}/activate', { method: 'POST' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            showToast('Model ' + data.model_id + ' activated successfully!', 'success');
            setTimeout(function() { window.location.href = '/admin/models'; }, 1000);
        })
        .catch(function() {
            showToast('Failed to activate model. Is the AI Service running?', 'error');
            btn.disabled = false;
            btn.textContent = 'Activate This Model';
        });
}

@if(!$isFinal)
(function() {
    const jobId = '{{ $job->job_id }}';
    const baseUrl = '{{ $aiServiceUrl }}';

    const statusEl = document.getElementById('status-text');
    const epochEl = document.getElementById('epoch-text');
    const accuracyEl = document.getElementById('accuracy-text');
    const lossEl = document.getElementById('loss-text');
    const progressBar = document.getElementById('progress-bar');
    const progressLabel = document.getElementById('progress-label');
    const logBox = document.getElementById('log-box');
    const initialStatus = document.getElementById('initial-status');
    const progressContent = document.getElementById('progress-content');
    const completedSection = document.getElementById('completed-section');
    const failedSection = document.getElementById('failed-section');
    const completedMetrics = document.getElementById('completed-metrics');
    const errorText = document.getElementById('error-text');
    const tbody = document.getElementById('epoch-tbody');
    const ringPct = document.getElementById('ring-pct');
    const ringCircle = document.getElementById('progress-ring');
    const circumference = 326.73;

    const statusColors = {
        'pending': 'text-yellow-400',
        'validating': 'text-blue-400',
        'extracting': 'text-blue-400',
        'training': 'text-indigo-400',
        'completed': 'text-green-400',
        'failed': 'text-red-400',
    };

    function pct(v) {
        return (v * 100).toFixed(1) + '%';
    }

    function fmt4(v) {
        return v.toFixed(4);
    }

    function updateRing(percent) {
        var offset = circumference - (percent / 100) * circumference;
        ringCircle.style.strokeDashoffset = offset;
        ringPct.textContent = Math.round(percent) + '%';
    }

    function updateTable(epochs) {
        if (!epochs || epochs.length === 0) return;

        var html = '';
        var lastPhase = '';
        var count = epochs.length;

        for (var i = 0; i < count; i++) {
            var ep = epochs[i];
            var isLast = (i === count - 1);

            if (ep.phase && ep.phase !== lastPhase) {
                if (lastPhase !== '') {
                    html += '<tr class="phase-separator"><td colspan="5"><div class="border-t border-slate-700 my-1"></div></td></tr>';
                }
                html += '<tr class="phase-separator"><td colspan="5"><span class="phase-label">' + ep.phase + '</span></td></tr>';
                lastPhase = ep.phase;
            }

            var rowClass = isLast ? 'current-row' : '';
            html += '<tr class="' + rowClass + '">';
            html += '<td class="py-2 pr-4 text-slate-400">' + ep.epoch + '</td>';
            html += '<td class="py-2 pr-4 text-right font-mono">' + (ep.accuracy !== undefined ? pct(ep.accuracy) : '-') + '</td>';
            html += '<td class="py-2 pr-4 text-right font-mono">' + (ep.loss !== undefined ? fmt4(ep.loss) : '-') + '</td>';
            html += '<td class="py-2 pr-4 text-right font-mono font-semibold text-indigo-400">' + (ep.val_accuracy !== undefined ? pct(ep.val_accuracy) : '-') + '</td>';
            html += '<td class="py-2 pr-4 text-right font-mono">' + (ep.val_loss !== undefined ? fmt4(ep.val_loss) : '-') + '</td>';
            html += '</tr>';
        }

        tbody.innerHTML = html;
    }

    function updateStatus(data) {
        var s = data.status || 'unknown';
        statusEl.textContent = s.charAt(0).toUpperCase() + s.slice(1);
        statusEl.className = 'text-lg font-semibold mt-1 ' + (statusColors[s] || 'text-slate-400');
        epochEl.textContent = (data.current_epoch || 0) + ' / ' + (data.total_epoch || '-');
        accuracyEl.textContent = data.accuracy !== null && data.accuracy !== undefined
            ? (data.accuracy * 100).toFixed(1) + '%' : '-';
        lossEl.textContent = data.loss !== null && data.loss !== undefined
            ? data.loss.toFixed(4) : '-';

        var total = data.total_epoch || 150;
        var pctVal = total > 0 ? Math.min(100, ((data.current_epoch || 0) / total) * 100) : 0;
        progressBar.style.width = pctVal + '%';
        progressLabel.textContent = pctVal.toFixed(0) + '%';
        updateRing(pctVal);
    }

    function updateLog(logText) {
        if (logText) {
            logBox.innerHTML = logText.replace(/\n/g, '<br>');
            logBox.scrollTop = logBox.scrollHeight;
        }
    }

    function showCompleted(data) {
        completedSection.style.display = 'block';
        completedMetrics.textContent = 'Accuracy: ' + (data.accuracy * 100).toFixed(1) + '% | Loss: ' + (data.loss ? data.loss.toFixed(4) : '-');
    }

    function showFailed(data) {
        failedSection.style.display = 'block';
        errorText.textContent = data.error_message || 'Unknown error';
    }

    function fetchStatus() {
        fetch(baseUrl + '/train/' + jobId + '/status')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                initialStatus.style.display = 'none';
                progressContent.style.display = 'block';

                updateStatus(data);

                if (data.epochs) {
                    updateTable(data.epochs);
                }

                if (data.status === 'completed') {
                    showCompleted(data);
                    clearInterval(window._pollInterval);
                } else if (data.status === 'failed') {
                    showFailed(data);
                    clearInterval(window._pollInterval);
                }

                return fetch(baseUrl + '/train/' + jobId + '/log');
            })
            .then(function(r) { return r.json(); })
            .then(function(logData) {
                updateLog(logData.log || '');
            })
            .catch(function(err) {
                if (!window._pollRetry) window._pollRetry = 0;
                window._pollRetry++;
                if (window._pollRetry > 5) {
                    initialStatus.innerHTML = '<p class="text-red-500">Cannot connect to AI Service. Make sure FastAPI is running on port 8001.</p>';
                }
            });
    }

    window._pollInterval = setInterval(fetchStatus, 3000);
    fetchStatus();
})();
@endif
</script>
@endpush
