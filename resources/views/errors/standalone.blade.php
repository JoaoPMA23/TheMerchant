{{--
    Página de erro autônoma: não usa o layout do site, sessão, banco nem CDN.
    Assim continua abrindo mesmo quando a falha vem do próprio layout ou da infraestrutura.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>TheMerchant - {{ $title }}</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: rgb(12 14 18);
            color: rgb(244 245 247);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
        }
        main {
            width: 100%;
            max-width: 560px;
            padding: 48px 32px;
            text-align: center;
            background: rgb(20 23 29);
            border: 1px solid rgb(41 46 54);
            border-radius: 12px;
        }
        .brand { margin: 0 0 32px; font-size: 18px; font-weight: 800; letter-spacing: -0.01em; }
        .brand span { color: rgb(187 243 91); }
        .code { margin: 0; font-size: 13px; font-weight: 700; color: rgb(187 243 91); }
        h1 { margin: 8px 0 12px; font-size: 24px; line-height: 1.25; }
        p { margin: 0; color: rgb(165 170 181); font-size: 16px; }
        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            margin-top: 32px;
            padding: 12px 20px;
            border-radius: 12px;
            background: rgb(187 243 91);
            color: rgb(12 14 18);
            font-weight: 700;
            text-decoration: none;
        }
        a:hover { background: rgb(210 255 139); }
        a:focus-visible { outline: 3px solid rgb(244 245 247); outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <p class="brand">the<span>merchant</span>.</p>
        <p class="code">Erro {{ $code }}</p>
        <h1>{{ $heading }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ url('/') }}">Voltar ao início</a>
    </main>
</body>
</html>
