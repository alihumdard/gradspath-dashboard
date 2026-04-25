@php
    $toastItems = collect();
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();

    if (session('success')) {
        $toastItems->push([
            'type' => 'success',
            'title' => 'Success',
            'message' => session('success'),
        ]);
    }

    if (session('warning')) {
        $toastItems->push([
            'type' => 'warning',
            'title' => 'Warning',
            'message' => session('warning'),
        ]);
    }

    if (session('error')) {
        $toastItems->push([
            'type' => 'error',
            'title' => 'Error',
            'message' => session('error'),
        ]);
    }

    if (session('status')) {
        $toastItems->push([
            'type' => 'info',
            'title' => 'Notice',
            'message' => session('status'),
        ]);
    }

    if ($viewErrors->any()) {
        $toastItems->push([
            'type' => 'error',
            'title' => 'Something went wrong',
            'message' => $viewErrors->first() ?: 'Please review the highlighted fields and try again.',
        ]);
    }
@endphp

<style>
  .app-toast-viewport {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 1200;
    display: flex;
    flex-direction: column-reverse;
    align-items: flex-end;
    gap: 12px;
    width: min(380px, calc(100vw - 32px));
    pointer-events: none;
  }

  .app-toast {
    --toast-accent: #0f172a;
    --toast-bg: rgba(255, 255, 255, 0.96);
    --toast-border: rgba(148, 163, 184, 0.28);
    --toast-text: #0f172a;
    --toast-muted: #475569;
    --toast-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
    position: relative;
    width: 100%;
    overflow: hidden;
    border: 1px solid var(--toast-border);
    border-radius: 18px;
    background: var(--toast-bg);
    box-shadow: var(--toast-shadow);
    backdrop-filter: blur(14px);
    color: var(--toast-text);
    pointer-events: auto;
    opacity: 0;
    transform: translateX(24px) translateY(8px) scale(0.98);
    transition: opacity 220ms ease, transform 220ms ease;
  }

  .app-toast::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--toast-accent);
  }

  .app-toast.is-visible {
    opacity: 1;
    transform: translateX(0) translateY(0) scale(1);
  }

  .app-toast.is-hiding {
    opacity: 0;
    transform: translateX(28px) scale(0.98);
  }

  .app-toast[data-type="success"] {
    --toast-accent: #16a34a;
    --toast-bg: rgba(240, 253, 244, 0.98);
    --toast-border: rgba(34, 197, 94, 0.24);
    --toast-text: #14532d;
    --toast-muted: #166534;
  }

  .app-toast[data-type="warning"] {
    --toast-accent: #d97706;
    --toast-bg: rgba(255, 251, 235, 0.98);
    --toast-border: rgba(245, 158, 11, 0.24);
    --toast-text: #78350f;
    --toast-muted: #92400e;
  }

  .app-toast[data-type="error"] {
    --toast-accent: #dc2626;
    --toast-bg: rgba(254, 242, 242, 0.98);
    --toast-border: rgba(248, 113, 113, 0.28);
    --toast-text: #7f1d1d;
    --toast-muted: #991b1b;
  }

  .app-toast[data-type="info"] {
    --toast-accent: #334155;
    --toast-bg: rgba(248, 250, 252, 0.98);
    --toast-border: rgba(148, 163, 184, 0.24);
    --toast-text: #0f172a;
    --toast-muted: #475569;
  }

  .app-toast-inner {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 12px;
    padding: 16px 16px 16px 18px;
    align-items: start;
  }

  .app-toast-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--toast-accent) 14%, white);
    color: var(--toast-accent);
    flex-shrink: 0;
  }

  .app-toast-icon svg {
    width: 18px;
    height: 18px;
  }

  .app-toast-copy {
    min-width: 0;
  }

  .app-toast-title {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: -0.01em;
  }

  .app-toast-message {
    margin: 4px 0 0;
    font-size: 0.885rem;
    line-height: 1.45;
    color: var(--toast-muted);
    word-break: break-word;
  }

  .app-toast-close {
    appearance: none;
    border: 0;
    background: transparent;
    color: color-mix(in srgb, var(--toast-text) 68%, transparent);
    width: 30px;
    height: 30px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 160ms ease, color 160ms ease;
  }

  .app-toast-close:hover {
    background: color-mix(in srgb, var(--toast-accent) 10%, white);
    color: var(--toast-text);
  }

  .app-toast-close svg {
    width: 15px;
    height: 15px;
  }

  .app-toast-progress {
    position: absolute;
    inset: auto 0 0 0;
    height: 3px;
    background: color-mix(in srgb, var(--toast-accent) 14%, transparent);
    overflow: hidden;
  }

  .app-toast-progress-bar {
    width: 100%;
    height: 100%;
    transform-origin: left center;
    background: linear-gradient(90deg, var(--toast-accent), color-mix(in srgb, var(--toast-accent) 58%, white));
    animation: app-toast-progress linear forwards;
  }

  .app-toast[data-paused="true"] .app-toast-progress-bar {
    animation-play-state: paused;
  }

  @keyframes app-toast-progress {
    from { transform: scaleX(1); }
    to { transform: scaleX(0); }
  }

  @media (max-width: 640px) {
    .app-toast-viewport {
      left: 16px;
      right: 16px;
      bottom: 16px;
      width: auto;
      align-items: stretch;
    }
  }
</style>

<div class="app-toast-viewport" id="appToastViewport" aria-live="polite" aria-atomic="true">
  @foreach ($toastItems as $toast)
    <div
      class="app-toast"
      data-type="{{ $toast['type'] }}"
      data-duration="{{ in_array($toast['type'], ['warning', 'error'], true) ? 7200 : 4200 }}"
    >
      <div class="app-toast-inner">
        <div class="app-toast-icon" aria-hidden="true">
          @if ($toast['type'] === 'success')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          @elseif ($toast['type'] === 'warning')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5"/><path d="M12 17h.01"/><path d="M10.3 3.84 2.82 16.5A2 2 0 0 0 4.55 19.5h14.9a2 2 0 0 0 1.73-3L13.7 3.84a2 2 0 0 0-3.4 0Z"/></svg>
          @elseif ($toast['type'] === 'error')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9 9 15"/><path d="m9 9 6 6"/></svg>
          @else
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 10v5"/><path d="M12 7h.01"/></svg>
          @endif
        </div>
        <div class="app-toast-copy">
          <p class="app-toast-title">{{ $toast['title'] }}</p>
          <p class="app-toast-message">{{ $toast['message'] }}</p>
        </div>
        <button class="app-toast-close" type="button" aria-label="Dismiss notification">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>
      <div class="app-toast-progress" aria-hidden="true">
        <div class="app-toast-progress-bar"></div>
      </div>
    </div>
  @endforeach
</div>

<script>
  (function () {
    const viewport = document.getElementById("appToastViewport");
    if (!viewport) return;

    const iconMarkup = {
      success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
      warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5"/><path d="M12 17h.01"/><path d="M10.3 3.84 2.82 16.5A2 2 0 0 0 4.55 19.5h14.9a2 2 0 0 0 1.73-3L13.7 3.84a2 2 0 0 0-3.4 0Z"/></svg>',
      error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9 9 15"/><path d="m9 9 6 6"/></svg>',
      info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 10v5"/><path d="M12 7h.01"/></svg>',
    };

    function removeToast(toast) {
      if (!toast || toast.dataset.closing === "true") return;
      toast.dataset.closing = "true";
      toast.classList.remove("is-visible");
      toast.classList.add("is-hiding");
      window.setTimeout(() => toast.remove(), 220);
    }

    function scheduleToast(toast) {
      const duration = Number(toast.dataset.duration || 4200);
      const progress = toast.querySelector(".app-toast-progress-bar");
      let timeoutId = null;
      let startedAt = window.performance.now();
      let remaining = duration;

      function startTimer() {
        toast.dataset.paused = "false";
        startedAt = window.performance.now();
        if (progress) {
          progress.style.animationDuration = remaining + "ms";
          progress.style.animationName = "none";
          void progress.offsetWidth;
          progress.style.animationName = "app-toast-progress";
        }
        timeoutId = window.setTimeout(() => removeToast(toast), remaining);
      }

      function pauseTimer() {
        if (!timeoutId) return;
        window.clearTimeout(timeoutId);
        timeoutId = null;
        remaining = Math.max(remaining - (window.performance.now() - startedAt), 0);
        toast.dataset.paused = "true";
        if (progress) {
          progress.style.animationPlayState = "paused";
        }
      }

      function resumeTimer() {
        if (toast.dataset.closing === "true" || remaining <= 0 || timeoutId) return;
        if (progress) {
          progress.style.animationPlayState = "running";
        }
        startTimer();
      }

      toast.addEventListener("mouseenter", pauseTimer);
      toast.addEventListener("mouseleave", resumeTimer);
      toast.querySelector(".app-toast-close")?.addEventListener("click", () => removeToast(toast));

      window.requestAnimationFrame(() => toast.classList.add("is-visible"));
      startTimer();
    }

    function buildToast(payload) {
      const type = ["success", "warning", "error", "info"].includes(payload.type) ? payload.type : "info";
      const toast = document.createElement("div");
      toast.className = "app-toast";
      toast.dataset.type = type;
      toast.dataset.duration = String(payload.duration || (type === "warning" || type === "error" ? 7200 : 4200));
      toast.innerHTML = `
        <div class="app-toast-inner">
          <div class="app-toast-icon" aria-hidden="true">${iconMarkup[type]}</div>
          <div class="app-toast-copy">
            <p class="app-toast-title">${payload.title || ({ success: "Success", warning: "Warning", error: "Error", info: "Notice" }[type])}</p>
            <p class="app-toast-message">${payload.message || ""}</p>
          </div>
          <button class="app-toast-close" type="button" aria-label="Dismiss notification">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          </button>
        </div>
        <div class="app-toast-progress" aria-hidden="true">
          <div class="app-toast-progress-bar"></div>
        </div>
      `;

      return toast;
    }

    Array.from(viewport.querySelectorAll(".app-toast")).forEach(scheduleToast);

    window.AppToast = {
      show(payload) {
        if (!payload || !payload.message) return;
        const toast = buildToast(payload);
        viewport.prepend(toast);
        scheduleToast(toast);
      },
    };
  })();
</script>
