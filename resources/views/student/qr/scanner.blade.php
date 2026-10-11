@extends('layouts.app')

@section('title', 'Scan QR Attendance')
@section('page-title', 'Scan QR Attendance')
@section('page-subtitle', 'Point your camera at the QR code shown by your instructor')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="card">
            <div class="card-body">

                {{-- ==================== SCANNER ==================== --}}
                <div id="scannerWrap">
                    <div id="reader" class="mb-3" style="width: 100%;"></div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-brand py-2" id="startBtn">
                            <i class="bi bi-camera me-1"></i>Start Camera
                        </button>
                        <button type="button" class="btn btn-outline-secondary d-none" id="stopBtn">
                            <i class="bi bi-stop-fill me-1"></i>Stop Camera
                        </button>
                    </div>
                </div>

                {{-- ==================== RESULT ==================== --}}
                <div id="resultBox" class="d-none mt-3"></div>

                <hr class="my-4">

                {{-- ==================== MANUAL ENTRY FALLBACK ==================== --}}
                <h2 class="h6">Camera not working?</h2>
                <p class="small text-muted">
                    Enter the code from your instructor's screen, or scan with your phone's
                    built-in camera app and open the link.
                </p>

                <div class="input-group">
                    <input type="text" class="form-control text-uppercase" id="manualToken"
                           placeholder="5-character code" maxlength="5" style="letter-spacing: 2px;">
                    <button class="btn btn-secondary" type="button" id="manualBtn">Submit</button>
                </div>

            </div>
        </div>

        <div class="alert alert-light border small mt-3">
            <i class="bi bi-shield-check me-1"></i>
            The QR code contains only a random token. Your name and student number are
            never stored inside it.
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    const SUBMIT_URL = '{{ route('student.qr.submit') }}';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    let scanner = null;
    let busy    = false;

    const startBtn  = document.getElementById('startBtn');
    const stopBtn   = document.getElementById('stopBtn');
    const resultBox = document.getElementById('resultBox');
    const manualBtn = document.getElementById('manualBtn');

    function showResult(ok, message, detail) {
        resultBox.classList.remove('d-none');
        resultBox.innerHTML = `
            <div class="alert alert-${ok ? 'success' : 'danger'} mb-0">
                <div class="d-flex gap-2">
                    <i class="bi bi-${ok ? 'check-circle-fill' : 'x-circle-fill'} fs-4"></i>
                    <div>
                        <div class="fw-medium">${ok ? 'Attendance Recorded' : 'Scan Failed'}</div>
                        <div class="small">${message}</div>
                        ${detail ? `<div class="small mt-1">${detail}</div>` : ''}
                    </div>
                </div>
            </div>`;
        resultBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function sendToken(token) {
        if (busy) return;
        busy = true;

        try {
            const response = await fetch(SUBMIT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({ token: token })
            });

            const data = await response.json();

            if (data.ok) {
                showResult(true, data.message, `${data.subject} — Section ${data.section}`);
                await stopScanner();
            } else {
                showResult(false, data.message || 'Something went wrong.');
                setTimeout(() => { busy = false; }, 2500);
                return;
            }

        } catch (error) {
            showResult(false, 'Could not reach the server. Check your connection.');
            setTimeout(() => { busy = false; }, 2500);
            return;
        }

        busy = false;
    }

    async function startScanner() {
        resultBox.classList.add('d-none');

        scanner = new Html5Qrcode('reader');

        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                function onSuccess(decodedText) {
                    sendToken(decodedText);
                },
                function onError() {
                    // Fires constantly while searching. Ignore it.
                }
            );

            startBtn.classList.add('d-none');
            stopBtn.classList.remove('d-none');

        } catch (error) {
            showResult(false,
                'Could not access the camera. Allow camera permission in your browser, ' +
                'or use the manual entry below.');
            scanner = null;
        }
    }

    async function stopScanner() {
        if (!scanner) return;

        try {
            await scanner.stop();
            scanner.clear();
        } catch (error) {
            console.error('Stop failed:', error);
        }

        scanner = null;
        startBtn.classList.remove('d-none');
        stopBtn.classList.add('d-none');
    }

    startBtn.addEventListener('click', startScanner);
    stopBtn.addEventListener('click', stopScanner);

    manualBtn.addEventListener('click', function () {
        const value = document.getElementById('manualToken').value.trim();

        if (!value) {
            showResult(false, 'Enter a code first.');
            return;
        }

        busy = false;
        sendToken(value);
    });
</script>
@endpush
