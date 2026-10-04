<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>{{ $emailSubject }}</title></head>
<body>
    <h2>{{ $emailSubject }}</h2>
    <div style="line-height: 1.6;">{!! $messageHtml !!}</div>
    <p>{{ config('mail.from.name') }}</p>
</body>
</html>
