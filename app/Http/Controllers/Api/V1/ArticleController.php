<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Articles
 */
class ArticleController extends Controller
{
    /**
     * Lister les articles
     *
     * Paginé (15 par page). Le paramètre `search` filtre sur le nom ou la référence.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $articles = Article::query()
            ->search($request->query('search'))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return ArticleResource::collection($articles);
    }

    /**
     * Créer un article
     */
    public function store(ArticleRequest $request): JsonResponse
    {
        $article = Article::create($request->validated());

        return (new ArticleResource($article))->response()->setStatusCode(201);
    }

    /**
     * Afficher un article
     */
    public function show(Article $article): ArticleResource
    {
        return new ArticleResource($article);
    }

    /**
     * Modifier un article
     */
    public function update(ArticleRequest $request, Article $article): ArticleResource
    {
        $article->update($request->validated());

        return new ArticleResource($article);
    }

    /**
     * Supprimer un article
     *
     * Suppression logique : les lignes de documents existantes ne sont pas modifiées.
     */
    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json(null, 204);
    }
}
