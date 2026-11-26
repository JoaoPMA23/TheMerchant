<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
{{-- O e-mail já é escuro: avisa o cliente para não inverter as cores no modo escuro. --}}
<meta name="color-scheme" content="dark">
<meta name="supported-color-schemes" content="dark">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
color-scheme: dark;
supported-color-schemes: dark;
}

@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}

.content-cell {
padding: 24px !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
text-align: center !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body bgcolor="#0c0e12">

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#0c0e12">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Corpo do e-mail -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" bgcolor="#0c0e12" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#14171d">
<tr>
<td class="accent-bar" height="3" bgcolor="#bbf35b">&nbsp;</td>
</tr>
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
