<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionsExport;

class ExportController extends Controller
{
    public function exportTransactions(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        // Excel export
        if ($request->query('format') === 'excel') {
            return Excel::download(new TransactionsExport($start, $end), 'transactions.xlsx');
        }

        // PDF export
        return $this->exportPDF($start, $end);
    }

    private function exportPDF($start = null, $end = null)
    {
        $query = DB::table('transactions')
            ->where('revendeur_id', auth()->id())
            ->orderByDesc('created_at');

        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }

        $transactions = $query->get();

        // Calcul total avec collection simple
        $total = $transactions->sum('montant');

        $pdf = PDF::loadView('exports.transactions-pdf', [
            'transactions' => $transactions,
            'total' => $total,
            'startDate' => $start,
            'endDate' => $end
        ]);

        return $pdf->download('transactions.pdf');
    }
}
