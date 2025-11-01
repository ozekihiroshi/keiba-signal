<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Ingest;
use Illuminate\Http\Request;

class IngestPublicController extends Controller
{
    public function index(Request $request)
    {
        $ingests = Ingest::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('news.index', compact('ingests'));
    }

    public function show(Ingest $ingest)
    {
        abort_unless($ingest->status === 'published', 404);

        return view('news.show', compact('ingest'));
    }
}
