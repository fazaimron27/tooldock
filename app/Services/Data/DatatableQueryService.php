<?php

namespace App\Services\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service for building datatable queries with search, sorting, and pagination.
 *
 * Provides reusable methods to apply common datatable operations to Eloquent queries,
 * ensuring consistent behavior across modules while maintaining security through
 * validation of sort columns and pagination limits.
 */
class DatatableQueryService
{
    /**
     * Apply search filtering across multiple database columns.
     *
     * Searches for the given term in all specified fields using LIKE queries
     * with OR conditions. Returns the query unchanged if no search term is provided.
     *
     * @param  Builder  $query  The Eloquent query builder instance
     * @param  array<string>  $searchFields  Column names to search in
     * @param  string|null  $searchTerm  The search term to look for
     */
    public function applySearch(Builder $query, array $searchFields, ?string $searchTerm): Builder
    {
        if (empty($searchTerm) || empty($searchFields)) {
            return $query;
        }

        $query->where(function ($q) use ($searchFields, $searchTerm) {
            $firstField = array_shift($searchFields);
            $q->where($firstField, 'like', "%{$searchTerm}%");

            foreach ($searchFields as $field) {
                $q->orWhere($field, 'like', "%{$searchTerm}%");
            }
        });

        return $query;
    }

    /**
     * Apply sorting to the query with validation against allowed columns.
     *
     * Validates that the requested sort column is in the allowed list and
     * that the direction is either 'asc' or 'desc'. Falls back to defaults
     * if validation fails.
     *
     * @param  Builder  $query  The Eloquent query builder instance
     * @param  array<string>  $allowedSorts  Whitelist of sortable column names
     * @param  string  $defaultSort  Default column to sort by
     * @param  string  $defaultDirection  Default sort direction ('asc' or 'desc')
     */
    public function applySorting(
        Builder $query,
        array $allowedSorts,
        ?string $defaultSort = 'created_at',
        ?string $defaultDirection = 'desc'
    ): Builder {
        $sort = request()->get('sort', $defaultSort);
        $direction = request()->get('direction', $defaultDirection);

        if (! in_array($sort, $allowedSorts)) {
            $sort = $defaultSort;
        }

        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = $defaultDirection ?? 'desc';
        }

        $query->orderBy($sort, $direction);

        return $query;
    }

    /**
     * Apply pagination with validation against allowed page sizes.
     *
     * Validates that the requested page size is in the allowed list.
     * Uses the configured default if no valid page size is provided.
     *
     * @param  Builder  $query  The Eloquent query builder instance
     * @param  array<int>  $allowedPerPage  Whitelist of allowed page sizes
     * @param  int  $defaultPerPage  Default number of items per page
     */
    public function applyPagination(
        Builder $query,
        array $allowedPerPage = [10, 20, 30, 50],
        ?int $defaultPerPage = 10
    ): LengthAwarePaginator {
        $requestPerPage = request()->query('per_page');

        if ($requestPerPage !== null) {
            $requestPerPage = (int) $requestPerPage;
            $perPage = in_array($requestPerPage, $allowedPerPage, true) ? $requestPerPage : $defaultPerPage;
        } else {
            $perPage = $defaultPerPage;
        }

        return $query->paginate($perPage);
    }

    /**
     * Build a complete datatable query with all operations applied.
     *
     * Applies search, sorting, and pagination to the query in sequence.
     * This is a convenience method that combines all datatable operations.
     *
     * @param  Builder  $query  The Eloquent query builder instance
     * @param  array{searchFields?: array<string>, allowedSorts?: array<string>, defaultSort?: string, defaultDirection?: string, allowedPerPage?: array<int>, defaultPerPage?: int}  $options  Configuration options for datatable operations
     */
    public function build(Builder $query, array $options = []): LengthAwarePaginator
    {
        $searchFields = $options['searchFields'] ?? [];
        $allowedSorts = $options['allowedSorts'] ?? ['created_at'];
        $defaultSort = $options['defaultSort'] ?? 'created_at';
        $defaultDirection = $options['defaultDirection'] ?? 'desc';
        $allowedPerPage = $options['allowedPerPage'] ?? [10, 20, 30, 50];
        $defaultPerPage = $options['defaultPerPage'] ?? 10;

        $searchTerm = request()->get('search');

        $query = $this->applySearch($query, $searchFields, $searchTerm);
        $query = $this->applySorting($query, $allowedSorts, $defaultSort, $defaultDirection);

        return $this->applyPagination($query, $allowedPerPage, $defaultPerPage);
    }
}
