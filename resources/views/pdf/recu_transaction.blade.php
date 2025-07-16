<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reçu de Transaction</title>
    <style>
        body { font-family: sans-serif; }
        h1 { text-align: center; color: #444; }
        .info { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Reçu de Transaction</h1>
    <p><strong>Référence:</strong> #{{ $transaction->id }}</p>
    <p><strong>Date:</strong> {{ $transaction->created_at->format('d/m/Y H:i') }}</p>
    <p><strong>Revendeur:</strong> {{ $transaction->revendeur->name }} ({{ $transaction->revendeur->email }})</p>
    <p><strong>Montant:</strong> {{ number_format($transaction->montant, 0, ',', ' ') }} Ar</p>
    <p><strong>Statut:</strong> {{ ucfirst($transaction->statut) }}</p>
</body>
</html>
