<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfExportController extends Controller
{
    public function export($id)
    {
        $transaction = DB::table('transactions')
            ->leftJoin('users as r', 'transactions.revendeur_id', '=', 'r.id')
            ->select(
                'transactions.*',
                'r.name as revendeur_name',
                'r.email as revendeur_email'
            )
            ->where('transactions.id', $id)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction non trouvée'], 404);
        }

        $pdf = Pdf::loadView('pdf.recu_transaction', compact('transaction'));

        return $pdf->download('recu_transaction_' . $transaction->id . '.pdf');
    }
}
