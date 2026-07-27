<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport financier</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        h1 { color: #2c3e50; }
    </style>
</head>
<body>
    <h1>Rapport financier</h1>
    <p>Période : du {{ $debut }} au {{ $fin }}</p>
    <table>
        <tr><th>Revenus</th><td>{{ number_format($revenus, 0, ',', ' ') }} Ar</td></tr>
        <tr><th>Dépenses</th><td>{{ number_format($depenses, 0, ',', ' ') }} Ar</td></tr>
        <tr><th>Bénéfice</th><td>{{ number_format($benefice, 0, ',', ' ') }} Ar</td></tr>
    </table>
</body>
</html>
