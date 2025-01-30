<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\SearchLog;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);

        // Log search query for analytics
        SearchLog::create([
            'query' => $query,
            'user_id' => auth()->id(),
            'filters' => $request->except(['q', 'page', 'per_page']),
            'results_count' => 0 // Will be updated after search
        ]);

        // Set up faceted search
        $facets = [];
        if ($request->has('category')) {
            $facets[] = [
                'field' => 'categories',
                'key' => 'name',
                'value' => $request->category
            ];
        }

        if ($request->has('tag')) {
            $facets[] = [
                'field' => 'tags',
                'key' => 'name',
                'value' => $request->tag
            ];
        }

        // Set up price range
        $priceRange = null;
        if ($request->has(['min_price', 'max_price'])) {
            $priceRange = [
                'min' => $request->min_price,
                'max' => $request->max_price
            ];
        }

        // Perform the search
        $results = Product::search($query)
            ->when($facets, function ($search) use ($facets) {
                $search->model->facets = $facets;
                return $search;
            })
            ->when($priceRange, function ($search) use ($priceRange) {
                $search->model->priceRange = $priceRange;
                return $search;
            })
            ->paginate($perPage);

        // Update search log with results count
        SearchLog::where('query', $query)
            ->where('user_id', auth()->id())
            ->latest()
            ->first()
            ->update(['results_count' => $results->total()]);

        return response()->json([
            'data' => $results->items(),
            'facets' => $this->getFacets($results),
            'total' => $results->total(),
            'per_page' => $results->perPage(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
        ]);
    }

    public function suggest(Request $request)
    {
        $query = $request->input('q');
        
        return Cache::remember("search_suggest_{$query}", 60, function () use ($query) {
            $suggestions = Product::search($query)
                ->take(5)
                ->get()
                ->pluck('name');

            return response()->json([
                'suggestions' => $suggestions
            ]);
        });
    }

    public function analytics()
    {
        $popularSearches = SearchLog::select('query')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $zeroResults = SearchLog::where('results_count', 0)
            ->select('query')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $searchesByDay = SearchLog::selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'popular_searches' => $popularSearches,
            'zero_results' => $zeroResults,
            'searches_by_day' => $searchesByDay
        ]);
    }

    protected function getFacets($results)
    {
        if (!isset($results->facets)) {
            return [];
        }

        return [
            'categories' => collect($results->facets['categories']['names']['buckets'])
                ->map(function ($bucket) {
                    return [
                        'name' => $bucket['key'],
                        'count' => $bucket['doc_count']
                    ];
                }),
            'tags' => collect($results->facets['tags']['names']['buckets'])
                ->map(function ($bucket) {
                    return [
                        'name' => $bucket['key'],
                        'count' => $bucket['doc_count']
                    ];
                }),
            'price_ranges' => collect($results->facets['price_ranges']['buckets'])
                ->map(function ($bucket) {
                    return [
                        'range' => [
                            'from' => $bucket['from'] ?? null,
                            'to' => $bucket['to'] ?? null,
                        ],
                        'count' => $bucket['doc_count']
                    ];
                })
        ];
    }
}
