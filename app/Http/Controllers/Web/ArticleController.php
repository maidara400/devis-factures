<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        return view('articles.index', [
            'articles' => Article::search($request->query('search'))
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('articles.form', ['article' => new Article(['tax_rate' => 18, 'unit' => 'unité', 'is_active' => true])]);
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        Article::create($this->data($request));

        return redirect()->route('articles.index')->with('success', 'Article créé.');
    }

    public function edit(Article $article): View
    {
        return view('articles.form', compact('article'));
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $article->update($this->data($request));

        return redirect()->route('articles.index')->with('success', 'Article mis à jour.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('articles.index')->with('success', 'Article supprimé.');
    }

    /**
     * Une case à cocher décochée n'est pas envoyée : on la convertit explicitement.
     */
    private function data(ArticleRequest $request): array
    {
        return [...$request->validated(), 'is_active' => $request->boolean('is_active')];
    }
}
