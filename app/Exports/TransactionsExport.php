<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionsExport implements FromCollection, WithHeadings
{
    protected $start;
    protected $end;

    public function __construct($start = null, $end = null)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function collection()
    {
        $query = Transaction::query();

        if ($this->start && $this->end) {
            $query->whereBetween('created_at', [$this->start, $this->end]);
        }

        return $query->with('revendeur:id,name,email')->get()->map(function ($transaction) {
            return [
                'ID' => $transaction->id,
                'Revendeur' => $transaction->revendeur->name ?? '',
                'Email' => $transaction->revendeur->email ?? '',
                'Montant' => $transaction->montant,
                'Statut' => $transaction->statut,
                'Date' => $transaction->created_at->format('Y-m-d H:i'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Revendeur',
            'Email',
            'Montant',
            'Statut',
            'Date',
        ];
    }
}
