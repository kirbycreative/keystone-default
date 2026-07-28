<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="A clear, professional home for your business.">
        <title>{{ config('app.name') }}</title>
        <style>
            :root {
                color-scheme: light;
                --ink: #17211d;
                --muted: #59645f;
                --paper: #f8f5ee;
                --accent: #bc5b36;
                --line: #ded8cc;
            }

            * { box-sizing: border-box; }
            html { scroll-behavior: smooth; }
            body {
                margin: 0;
                color: var(--ink);
                background: var(--paper);
                font-family: Inter, ui-sans-serif, system-ui, sans-serif;
                line-height: 1.6;
            }

            a { color: inherit; }
            .shell { width: min(1120px, calc(100% - 40px)); margin: 0 auto; }
            header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                min-height: 78px;
                border-bottom: 1px solid var(--line);
            }
            .brand { font-weight: 750; letter-spacing: -.02em; text-decoration: none; }
            nav { display: flex; gap: 24px; }
            nav a { color: var(--muted); font-size: .94rem; text-decoration: none; }
            .hero {
                display: grid;
                grid-template-columns: 1.3fr .7fr;
                gap: 64px;
                align-items: end;
                padding: 110px 0 90px;
            }
            .eyebrow {
                color: var(--accent);
                font-size: .78rem;
                font-weight: 800;
                letter-spacing: .14em;
                text-transform: uppercase;
            }
            h1 {
                max-width: 800px;
                margin: 16px 0 24px;
                font-family: Georgia, serif;
                font-size: clamp(3.2rem, 8vw, 7rem);
                font-weight: 500;
                letter-spacing: -.055em;
                line-height: .94;
            }
            .intro { max-width: 520px; color: var(--muted); font-size: 1.15rem; }
            .button {
                display: inline-block;
                margin-top: 24px;
                padding: 13px 20px;
                color: white;
                background: var(--ink);
                border-radius: 999px;
                font-weight: 700;
                text-decoration: none;
            }
            .proof {
                padding: 28px;
                background: #ece7dc;
                border-radius: 20px;
            }
            .proof strong { display: block; margin-bottom: 8px; font-size: 1.1rem; }
            section { padding: 80px 0; border-top: 1px solid var(--line); }
            h2 {
                margin: 0 0 34px;
                font-family: Georgia, serif;
                font-size: clamp(2rem, 5vw, 3.6rem);
                font-weight: 500;
                letter-spacing: -.035em;
                line-height: 1.05;
            }
            .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
            .card { min-height: 190px; padding: 25px; background: white; border: 1px solid var(--line); border-radius: 16px; }
            .card h3 { margin-top: 0; font-size: 1.15rem; }
            .card p, .about p { color: var(--muted); }
            .about { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; }
            footer { display: flex; justify-content: space-between; gap: 24px; padding: 40px 0; color: var(--muted); }

            @media (max-width: 720px) {
                nav { display: none; }
                .hero, .about, .cards { grid-template-columns: 1fr; }
                .hero { gap: 36px; padding: 72px 0 64px; }
                section { padding: 60px 0; }
                footer { flex-direction: column; }
            }
        </style>
    </head>
    <body data-public-site="minimum-sellable">
        <header class="shell">
            <a class="brand" href="/">{{ config('app.name') }}</a>
            <nav aria-label="Primary navigation">
                <a href="#services">Services</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
            </nav>
        </header>

        <main>
            <div class="hero shell">
                <div>
                    <div class="eyebrow">Built around your goals</div>
                    <h1>A better way forward.</h1>
                    <p class="intro">Clear expertise, thoughtful service, and a straightforward path from first conversation to finished work.</p>
                    <a class="button" href="#contact">Start a conversation</a>
                </div>
                <aside class="proof">
                    <strong>Ready when you are.</strong>
                    <span>Tell us what you need. We’ll listen, recommend the right next step, and make it easy to begin.</span>
                </aside>
            </div>

            <section id="services">
                <div class="shell">
                    <div class="eyebrow">What we do</div>
                    <h2>Practical help that moves your business.</h2>
                    <div class="cards">
                        <article class="card"><h3>Strategy</h3><p>Turn your priorities into a focused, achievable plan.</p></article>
                        <article class="card"><h3>Delivery</h3><p>Get dependable work with clear communication from start to finish.</p></article>
                        <article class="card"><h3>Support</h3><p>Keep improving with a trusted partner who understands your goals.</p></article>
                    </div>
                </div>
            </section>

            <section id="about">
                <div class="shell about">
                    <div>
                        <div class="eyebrow">Why choose us</div>
                        <h2>Good work starts with understanding.</h2>
                    </div>
                    <p>We begin by listening. That means recommendations grounded in your real needs, a process you can follow, and work designed to create lasting value.</p>
                </div>
            </section>

            <section id="contact">
                <div class="shell">
                    <div class="eyebrow">Get started</div>
                    <h2>Let’s talk about what comes next.</h2>
                    <a class="button" href="mailto:{{ config('mail.from.address') }}">Contact us</a>
                </div>
            </section>
        </main>

        <footer class="shell">
            <span>&copy; {{ now()->year }} {{ config('app.name') }}</span>
            <span>Professional service, made personal.</span>
        </footer>
    </body>
</html>
