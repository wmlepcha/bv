@php
    $home = url('/');
    $homeLabel = 'Take me home';

    try {
        $viewer = auth()->user();

        if ($viewer !== null) {
            $home = match (true) {
                $viewer->isRecruitmentManager() => route('recruitment-manager.dashboard'),
                $viewer->isTrainingManager() => route('training-manager.dashboard'),
                default => route('dashboard'),
            };
            $homeLabel = 'Back to my dashboard';
        }
    } catch (\Throwable) {
        // Error pages must render even when sessions or the database are down.
    }

    $primaryHref = trim($__env->yieldContent('retry')) !== '' ? 'javascript:location.reload()' : $home;
    $primaryLabel = trim($__env->yieldContent('retry')) !== '' ? trim($__env->yieldContent('retry')) : $homeLabel;
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>@yield('code') · @yield('title') · {{ config('app.name') }}</title>
        <style>
            :root {
                --bg: #f6f8fc;
                --card: #ffffff;
                --text: #0f1b2d;
                --muted: #5b6b82;
                --cool: #2a80cc;
                --cool-light: #04ceff;
                --warm: #fc5c2e;
                --warm-light: #fba214;
                --border: #e1e8f2;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #08111f;
                    --card: #0f1b2d;
                    --text: #eef3fb;
                    --muted: #93a4bd;
                    --border: #1d2c44;
                }
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 24px 16px;
                background:
                    radial-gradient(60rem 30rem at 15% -10%, color-mix(in srgb, var(--cool-light) 22%, transparent), transparent 60%),
                    radial-gradient(50rem 28rem at 100% 110%, color-mix(in srgb, var(--warm-light) 22%, transparent), transparent 60%),
                    var(--bg);
                color: var(--text);
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            .card {
                position: relative;
                width: 100%;
                max-width: 520px;
                padding: 40px 28px 32px;
                text-align: center;
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 28px;
                box-shadow: 0 24px 60px -30px rgba(9, 89, 181, 0.45);
            }

            .logo { height: 40px; width: auto; margin-bottom: 20px; }
            .logo-dark { display: none; }
            @media (prefers-color-scheme: dark) {
                .logo-light { display: none; }
                .logo-dark { display: inline-block; }
            }

            .stage { position: relative; display: inline-block; margin: 8px 0 4px; }

            .code {
                margin: 0;
                font-size: clamp(88px, 22vw, 128px);
                line-height: 1;
                font-weight: 800;
                letter-spacing: -0.04em;
                background: linear-gradient(135deg, var(--cool) 0%, var(--cool-light) 45%, var(--warm) 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            .emoji {
                position: absolute;
                top: -14px;
                right: -30px;
                font-size: 44px;
                animation: bob 3s ease-in-out infinite;
            }

            .bubble {
                position: relative;
                display: inline-block;
                margin: 4px 0 18px;
                padding: 8px 14px;
                font-size: 13px;
                font-weight: 600;
                color: var(--cool);
                background: color-mix(in srgb, var(--cool) 12%, transparent);
                border-radius: 999px;
            }

            h1 { margin: 0 0 10px; font-size: clamp(22px, 5vw, 28px); letter-spacing: -0.02em; }

            p.message { margin: 0 auto 26px; max-width: 40ch; color: var(--muted); font-size: 16px; line-height: 1.6; }

            .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 46px;
                padding: 0 22px;
                border-radius: 999px;
                font-size: 15px;
                font-weight: 600;
                text-decoration: none;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }

            .btn:active { transform: scale(0.97); }
            .btn:focus-visible { outline: 3px solid var(--cool-light); outline-offset: 2px; }

            .btn-primary {
                color: #fff;
                background: linear-gradient(135deg, var(--warm) 0%, var(--warm-light) 100%);
                box-shadow: 0 10px 24px -12px var(--warm);
            }

            .btn-ghost { color: var(--text); background: transparent; border: 1px solid var(--border); }

            .foot { margin-top: 24px; font-size: 12px; color: var(--muted); }
            .foot b { font-weight: 600; }

            @keyframes bob {
                0%, 100% { transform: translateY(0) rotate(-8deg); }
                50% { transform: translateY(-8px) rotate(8deg); }
            }

            @media (prefers-reduced-motion: reduce) {
                .emoji { animation: none; }
                .btn { transition: none; }
            }
        </style>
    </head>
    <body>
        <main class="card" role="main">
            <a href="{{ url('/') }}" aria-label="{{ config('app.name') }}">
                <img class="logo logo-light" src="/storage/images/bv-logo.png" alt="{{ config('app.name') }}" onerror="this.style.display='none'">
                <img class="logo logo-dark" src="/storage/images/bv-logo-light.png" alt="" onerror="this.style.display='none'">
            </a>

            <div>
                <div class="stage">
                    <p class="code">@yield('code')</p>
                    <span class="emoji" aria-hidden="true">@yield('emoji')</span>
                </div>
            </div>

            <div><span class="bubble">@yield('bubble')</span></div>

            <h1>@yield('title')</h1>
            <p class="message">@yield('message')</p>

            <div class="actions">
                <a class="btn btn-primary" href="{{ $primaryHref }}">{{ $primaryLabel }}</a>
                @if (trim($__env->yieldContent('retry')) === '')
                    <a class="btn btn-ghost" href="javascript:history.back()">Go back</a>
                @else
                    <a class="btn btn-ghost" href="{{ $home }}">{{ $homeLabel }}</a>
                @endif
            </div>

            <p class="foot"><b>@yield('code')</b> · @yield('foot')</p>
        </main>
    </body>
</html>
