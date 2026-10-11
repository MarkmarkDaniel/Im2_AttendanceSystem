{{--
    Pop-up shown on the dashboard of a student whose record is not Active.
    It cannot be closed, and the page behind it is made inert, so the only
    thing left to click is Log Out. The real lock is on the server, in the
    RestrictInactiveStudent middleware; this is what the student sees.
--}}
@php
    $lockedStudent = auth()->user()?->isStudent() ? auth()->user()->student : null;
@endphp

@if ($lockedStudent && ! $lockedStudent->isActive())
    @php $notice = $lockedStudent->statusNotice(); @endphp

    {{-- No "fade": it must be there the instant the dashboard appears --}}
    <div class="modal status-lock" id="statusLockModal" tabindex="-1"
         data-bs-backdrop="static" data-bs-keyboard="false"
         aria-labelledby="statusLockTitle" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-4">

                    <div class="text-center mb-4">
                        <div class="status-lock-icon mx-auto mb-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <p class="status-lock-eyebrow mb-1">Your Status</p>
                        <h2 class="h3 fw-bold mb-2" id="statusLockTitle">{{ $lockedStudent->status }}</h2>
                        <p class="small text-muted mb-0">
                            {{ $lockedStudent->full_name }} &middot; {{ $lockedStudent->student_number }}
                        </p>
                    </div>

                    <p class="mb-3">{{ $notice['summary'] }}</p>

                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <p class="small fw-semibold mb-2">
                            <i class="bi bi-list-check me-1"></i>What you need to do
                        </p>
                        <ol class="small mb-0 ps-3">
                            @foreach ($notice['steps'] as $step)
                                <li class="mb-1">{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>

                    <p class="small text-muted mb-4">
                        <i class="bi bi-lock me-1"></i>
                        Your dashboard, subjects, attendance and QR scanning are locked until
                        your status is changed back to Active.
                    </p>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-brand w-100 py-2">
                            <i class="bi bi-box-arrow-right me-1"></i>Log Out
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- Inline, because the layout's "styles" stack has already been printed by now --}}
    <style>
        .status-lock-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            color: var(--brand-deep);
            background: linear-gradient(135deg, var(--gold-soft), var(--gold));
            box-shadow: 0 0 0 6px rgba(217, 180, 90, .25);
        }
        .status-lock-eyebrow {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: #6b7a90;
        }
        /* Dim and blur the dashboard behind the pop-up */
        body.status-locked .modal-backdrop.show {
            opacity: 1;
            background: rgba(12, 31, 53, .55);
            -webkit-backdrop-filter: blur(4px);
            backdrop-filter: blur(4px);
        }
    </style>

    @push('scripts')
    <script>
        (function () {
            // Nothing behind the pop-up can be clicked or reached with the keyboard
            document.querySelectorAll('#sidebar, .main-wrap').forEach(function (el) {
                el.setAttribute('inert', '');
            });
            document.body.classList.add('status-locked');

            const modalEl = document.getElementById('statusLockModal');

            // Put it back if something tries to hide it
            modalEl.addEventListener('hide.bs.modal', function (event) { event.preventDefault(); });

            new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false }).show();
        })();
    </script>
    @endpush
@endif
