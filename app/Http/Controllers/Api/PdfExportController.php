<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfExportController extends Controller
{
    public function export($id)
    {
        $transaction = Transaction::with('revendeur')->findOrFail($id);

        $pdf = Pdf::loadView('pdf.recu_transaction', compact('transaction'));

        return $pdf->download('recu_transaction_'.$transaction->id.'.pdf');
    }
}
