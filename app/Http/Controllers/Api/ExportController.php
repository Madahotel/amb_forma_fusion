<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function exportTransactions(Request $request)
    {
        $start = $request->query('start'); // format: YYYY-MM-DD
        $end = $request->query('end');     // format: YYYY-MM-DD

        return Excel::download(new TransactionsExport($start, $end), 'transactions.xlsx');
    }
}
