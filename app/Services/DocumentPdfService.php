<?php

namespace App\Services;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentPdfService
{
    public function render(Document $document): string
    {
        $document->loadMissing(['client', 'lines']);

        return Pdf::loadView('pdf.document', ['document' => $document])
            ->setPaper('a4')
            ->output();
    }

    public function filename(Document $document): string
    {
        return $document->number.'.pdf';
    }
}
