<?php

namespace App\Http\Controllers;

use App\Foundation\Legal\LegalDocumentRepository;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Zeigt exakt dieselbe kanonische Dokumentdatei, deren Digest beim Vorgang gespeichert wird.
 */
final class LegalDocumentController extends Controller
{
    /**
     * Rendert die kontrollierte Markdown-Vorlage ohne eingebettetes Fremd-HTML.
     */
    public function show(string $document, LegalDocumentRepository $documents): View
    {
        abort_unless(in_array($document, ['terms', 'privacy'], true), 404);
        $legalDocument = $documents->get($document);

        return view('legal.show', [
            'document' => $legalDocument,
            'html' => Str::markdown($legalDocument->content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }
}
