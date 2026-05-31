<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }} - Grads Paths</title>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap");

    :root {
      color-scheme: light;
      --bg: #faf5ff;
      --surface: #ffffff;
      --surface-soft: #f3e8ff;
      --text-main: #1e0d4d;
      --text-muted: #5b30e5;
      --border: #e9d5ff;
      --primary: #5b30e5;
      --secondary: #fe5673;
      --accent: #8c5fe2;
      --shadow: 0 22px 65px rgba(91, 48, 229, .12);
    }
    * { box-sizing: border-box; }
    html { -webkit-text-size-adjust: 100%; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: var(--text-main);
      background:
        radial-gradient(circle at 10% 0%, rgba(254, 86, 115, .16), transparent 30%),
        radial-gradient(circle at 90% 8%, rgba(91, 48, 229, .18), transparent 34%),
        linear-gradient(180deg, #faf5ff 0%, #ffffff 48%, #f8f3ff 100%);
      line-height: 1.65;
      font-weight: 500;
    }
    header {
      position: sticky;
      top: 0;
      z-index: 20;
      border-bottom: 1px solid rgba(233, 213, 255, .9);
      background: rgba(255, 255, 255, .82);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
    }
    nav, main {
      width: min(1040px, calc(100% - 32px));
      margin: 0 auto;
    }
    nav {
      min-height: 72px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .brand {
      display: inline-flex;
      align-items: center;
      text-decoration: none;
      min-width: 136px;
    }
    .brand img {
      display: block;
      width: auto;
      height: 54px;
      max-width: 178px;
      object-fit: contain;
    }
    .nav-links {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 8px;
      font-size: 14px;
    }
    a { color: var(--primary); }
    .nav-links a {
      color: rgba(30, 13, 77, .76);
      text-decoration: none;
      font-weight: 800;
      line-height: 1;
      border: 1px solid transparent;
      border-radius: 999px;
      padding: 10px 13px;
      transition: color .2s ease, background-color .2s ease, border-color .2s ease, transform .2s ease;
    }
    .nav-links a:hover,
    .nav-links a[aria-current="page"] {
      color: var(--primary);
      background: rgba(91, 48, 229, .08);
      border-color: rgba(91, 48, 229, .14);
    }
    .nav-links a:hover {
      transform: translateY(-1px);
    }
    main {
      padding: 46px 0 76px;
    }
    .page-hero {
      margin: 0 0 24px;
      padding: 0 clamp(2px, 1vw, 6px);
    }
    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin: 0 0 12px;
      color: var(--primary);
      font-size: 12px;
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
    }
    .eyebrow::before {
      content: "";
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      box-shadow: 0 0 0 6px rgba(91, 48, 229, .09);
    }
    .page-hero h1 {
      max-width: 780px;
      margin: 0;
      font-size: clamp(36px, 7vw, 62px);
      line-height: 1.02;
      letter-spacing: 0;
      background: linear-gradient(135deg, var(--primary), var(--accent) 48%, var(--secondary));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .document {
      background: var(--surface);
      border: 1px solid rgba(233, 213, 255, .95);
      border-radius: 8px;
      padding: clamp(24px, 4vw, 48px);
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }
    .document::before {
      content: "";
      position: absolute;
      inset: 0 0 auto;
      height: 5px;
      background: linear-gradient(90deg, var(--primary), var(--accent), var(--secondary));
    }
    .document > h1 {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }
    h2 {
      margin: 34px 0 10px;
      font-size: 20px;
      line-height: 1.25;
      letter-spacing: 0;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    h2::before {
      content: "";
      width: 4px;
      height: 20px;
      border-radius: 999px;
      background: linear-gradient(180deg, var(--primary), var(--secondary));
      flex: 0 0 auto;
    }
    p, ul, ol { margin: 10px 0 0; }
    ul, ol { padding-left: 22px; }
    li + li { margin-top: 6px; }
    li::marker { color: var(--primary); font-weight: 800; }
    .lede {
      color: rgba(30, 13, 77, .78);
      font-size: 17px;
      line-height: 1.75;
      max-width: 820px;
      margin-top: 0;
      padding: 18px 20px;
      border: 1px solid rgba(91, 48, 229, .12);
      border-radius: 8px;
      background: linear-gradient(135deg, rgba(91, 48, 229, .07), rgba(254, 86, 115, .06));
    }
    .document a {
      color: var(--primary);
      font-weight: 800;
      text-decoration-color: rgba(91, 48, 229, .32);
      text-underline-offset: 3px;
    }
    .updated {
      color: rgba(30, 13, 77, .62);
      border-top: 1px solid var(--border);
      margin-top: 36px;
      padding-top: 16px;
      font-size: 14px;
      font-weight: 700;
    }
    @media (max-width: 640px) {
      nav {
        align-items: center;
        flex-direction: column;
        padding: 14px 0 16px;
      }
      .nav-links {
        justify-content: center;
        width: 100%;
      }
      .nav-links a { padding: 9px 10px; }
      .brand img { height: 48px; }
      main { padding-top: 34px; }
    }
  </style>
</head>
<body>
  <header>
    <nav aria-label="Public navigation">
      <a class="brand" href="{{ url('/') }}" aria-label="Home - Grads Paths">
        <img src="{{ asset('gradspaths_logo/Gradspaths_logo_transparent.png') }}" alt="Grads Paths">
      </a>
      <div class="nav-links">
        <a href="{{ route('public.terms') }}" @if(request()->routeIs('public.terms')) aria-current="page" @endif>Terms</a>
        <a href="{{ route('public.privacy') }}" @if(request()->routeIs('public.privacy')) aria-current="page" @endif>Privacy</a>
        <a href="{{ route('public.support') }}" @if(request()->routeIs('public.support')) aria-current="page" @endif>Support</a>
        <a href="{{ route('public.zoom-app-guide') }}" @if(request()->routeIs('public.zoom-app-guide')) aria-current="page" @endif>Zoom Guide</a>
      </div>
    </nav>
  </header>
  <main>
    <section class="page-hero" aria-labelledby="page-title">
      <p class="eyebrow">Grads Paths</p>
      <h1 id="page-title">{{ $title }}</h1>
    </section>
    <article class="document">
      {{ $slot }}
    </article>
  </main>
</body>
</html>
