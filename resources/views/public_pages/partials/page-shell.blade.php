<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }} - Grads Paths</title>
  <style>
    :root {
      color-scheme: light;
      --ink: #121826;
      --muted: #5f6b7a;
      --line: #d9e0ea;
      --brand: #5b30e5;
      --bg: #f7f9fc;
      --surface: #ffffff;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: var(--ink);
      background: var(--bg);
      line-height: 1.65;
    }
    header {
      border-bottom: 1px solid var(--line);
      background: var(--surface);
    }
    nav, main {
      width: min(920px, calc(100% - 32px));
      margin: 0 auto;
    }
    nav {
      min-height: 68px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .brand {
      color: var(--ink);
      font-weight: 800;
      text-decoration: none;
      letter-spacing: .01em;
    }
    .nav-links {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 14px;
      font-size: 14px;
    }
    a { color: var(--brand); }
    .nav-links a {
      color: var(--muted);
      text-decoration: none;
      font-weight: 650;
    }
    main {
      padding: 48px 0 72px;
    }
    .document {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: clamp(24px, 4vw, 44px);
      box-shadow: 0 16px 40px rgba(18, 24, 38, .06);
    }
    h1 {
      margin: 0 0 12px;
      font-size: clamp(30px, 5vw, 44px);
      line-height: 1.08;
      letter-spacing: 0;
    }
    h2 {
      margin: 34px 0 8px;
      font-size: 20px;
      line-height: 1.25;
    }
    p, ul, ol { margin: 10px 0 0; }
    ul, ol { padding-left: 22px; }
    li + li { margin-top: 6px; }
    .lede {
      color: var(--muted);
      font-size: 17px;
      max-width: 760px;
    }
    .updated {
      color: var(--muted);
      border-top: 1px solid var(--line);
      margin-top: 36px;
      padding-top: 16px;
      font-size: 14px;
    }
    @media (max-width: 640px) {
      nav {
        align-items: flex-start;
        flex-direction: column;
        padding: 16px 0;
      }
      .nav-links {
        justify-content: flex-start;
      }
    }
  </style>
</head>
<body>
  <header>
    <nav aria-label="Public navigation">
      <a class="brand" href="{{ url('/') }}">Grads Paths</a>
      <div class="nav-links">
        <a href="{{ route('public.terms') }}">Terms</a>
        <a href="{{ route('public.privacy') }}">Privacy</a>
        <a href="{{ route('public.support') }}">Support</a>
        <a href="{{ route('public.zoom-app-guide') }}">Zoom Guide</a>
      </div>
    </nav>
  </header>
  <main>
    <article class="document">
      {{ $slot }}
    </article>
  </main>
</body>
</html>
