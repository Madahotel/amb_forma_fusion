<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transactions</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .header { margin-bottom: 20px; }
        .footer { margin-top: 20px; font-size: 0.8em; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Historique des Transactions</h1>
        @if($startDate && $endDate)
            <p>Période du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th class="text-right">Montant (Ar)</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $transaction->description }}</td>
                <td class="text-right">{{ number_format($transaction->montant, 0, ',', ' ') }}</td>
                <td>{{ ucfirst($transaction->statut) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total</th>
                <th class="text-right">{{ number_format($total, 0, ',', ' ') }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Généré le {{ now()->format('d/m/Y à H:i') }} par {{ auth()->user()->name }}
    </div>
</body>
</html>