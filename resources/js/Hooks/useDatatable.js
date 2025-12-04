/**
 * Hook for managing datatable state with optimized server-side operations
 * Prevents duplicate requests, debounces search/filter inputs, and handles
 * pagination/sorting state synchronization between client and server
 */
import { DEFAULT_PAGE_SIZE, PAGINATION_LIMITS } from '@/Utils/constants';
import { router } from '@inertiajs/react';
import {
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useDebounce } from 'use-debounce';

export function useDatatable({
  data = [],
  columns = [],
  initialSorting = [],
  pageSize = DEFAULT_PAGE_SIZE,
  route = null,
  onPaginationChange = null,
  onSortChange = null,
  onFilterChange = null,
  serverSide = null,
  initialFilters = {},
  pageCount = undefined,
  only = null,
}) {
  const isServerSide = serverSide !== null ? serverSide : !!route;

  const [sorting, setSorting] = useState(initialSorting);
  const [globalFilter, setGlobalFilter] = useState('');
  const [pagination, setPagination] = useState({
    pageIndex: 0,
    pageSize: pageSize,
  });
  const [rowSelection, setRowSelection] = useState({});
  const [filters, setFilters] = useState(initialFilters);

  /**
   * Debounced search and filter values to reduce server requests.
   * 300ms delay balances responsiveness with performance.
   */
  const [debouncedGlobalFilter] = useDebounce(globalFilter, 300);
  const [debouncedFilters] = useDebounce(filters, 300);

  const previousParamsRef = useRef(null);
  const isInitialMount = useRef(true);

  useEffect(() => {
    if (!isServerSide || !route) {
      return;
    }

    /**
     * Skip initial request if data already exists to prevent duplicate requests.
     * This handles cases where data is pre-loaded via Inertia props.
     */
    if (isInitialMount.current && data.length > 0) {
      isInitialMount.current = false;
      return;
    }
    isInitialMount.current = false;

    const params = {
      page: pagination.pageIndex + 1,
      per_page: pagination.pageSize,
    };

    if (sorting.length > 0) {
      const sort = sorting[0];
      params.sort = sort.id;
      params.direction = sort.desc ? 'desc' : 'asc';
    }

    /**
     * Use debounced values for search and filters to reduce server load.
     * Pagination and sorting remain immediate for better user experience.
     */
    const hasSearchOrFilters = debouncedGlobalFilter || Object.keys(debouncedFilters).length > 0;

    if (debouncedGlobalFilter) {
      params.search = debouncedGlobalFilter;
    }

    Object.entries(debouncedFilters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params[key] = value;
      }
    });

    /**
     * Determine whether to use debounced or immediate values.
     * Immediate values for pagination/sorting, debounced for search/filters.
     */
    const shouldUseDebounced = hasSearchOrFilters;
    const effectiveGlobalFilter = shouldUseDebounced ? debouncedGlobalFilter : globalFilter;
    const effectiveFilters = shouldUseDebounced ? debouncedFilters : filters;

    const finalParams = {
      page: pagination.pageIndex + 1,
      per_page: pagination.pageSize,
    };

    if (sorting.length > 0) {
      const sort = sorting[0];
      finalParams.sort = sort.id;
      finalParams.direction = sort.desc ? 'desc' : 'asc';
    }

    if (effectiveGlobalFilter) {
      finalParams.search = effectiveGlobalFilter;
    }

    Object.entries(effectiveFilters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        finalParams[key] = value;
      }
    });

    const paramsString = JSON.stringify(finalParams);
    if (previousParamsRef.current === paramsString) {
      return;
    }
    previousParamsRef.current = paramsString;

    const options = {
      preserveState: true,
      preserveScroll: true,
      skipLoadingIndicator: true,
    };

    if (only !== null && Array.isArray(only) && only.length > 0) {
      options.only = only;
    }

    router.get(route, finalParams, options);
  }, [
    pagination,
    sorting,
    globalFilter,
    filters,
    debouncedGlobalFilter,
    debouncedFilters,
    isServerSide,
    route,
    only,
    data.length,
  ]);

  const handleSortingChange = useCallback(
    (updater) => {
      const newSorting = typeof updater === 'function' ? updater(sorting) : updater;
      setSorting(newSorting);
      onSortChange?.(newSorting);
    },
    [sorting, onSortChange]
  );

  const handlePaginationChange = useCallback(
    (updater) => {
      const newPagination = typeof updater === 'function' ? updater(pagination) : updater;
      setPagination(newPagination);
      onPaginationChange?.(newPagination);
    },
    [pagination, onPaginationChange]
  );

  const handleFilterChange = useCallback(
    (newFilters) => {
      setFilters((prev) => ({ ...prev, ...newFilters }));
      onFilterChange?.(newFilters);
      setPagination((prev) => ({ ...prev, pageIndex: 0 }));
    },
    [onFilterChange]
  );

  const table = useReactTable({
    data,
    columns,
    state: {
      sorting,
      globalFilter,
      pagination,
      rowSelection,
    },
    onSortingChange: handleSortingChange,
    onGlobalFilterChange: setGlobalFilter,
    onPaginationChange: handlePaginationChange,
    onRowSelectionChange: setRowSelection,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: isServerSide ? undefined : getFilteredRowModel(),
    getSortedRowModel: isServerSide ? undefined : getSortedRowModel(),
    getPaginationRowModel: isServerSide ? undefined : getPaginationRowModel(),
    globalFilterFn: 'includesString',
    enableRowSelection: true,
    manualPagination: isServerSide,
    manualSorting: isServerSide,
    manualFiltering: isServerSide,
    pageCount: isServerSide && pageCount !== undefined ? pageCount : undefined,
  });

  return useMemo(
    () => ({
      table,
      data,
      columns,
      sorting,
      pagination,
      globalFilter,
      filters,
      rowSelection,
      setSorting: handleSortingChange,
      setPagination: handlePaginationChange,
      setGlobalFilter,
      setFilters: handleFilterChange,
      setRowSelection,
      selectedRows: table.getSelectedRowModel().rows.map((row) => row.original),
      selectedRowIds: Object.keys(rowSelection),
      pageSize: pagination.pageSize,
      pageIndex: pagination.pageIndex,
      pageCount: table.getPageCount(),
      canPreviousPage: table.getCanPreviousPage(),
      canNextPage: table.getCanNextPage(),
      tableProps: {
        table,
        data,
        columns,
        sorting,
        pagination,
        globalFilter,
        filters,
        rowSelection,
        onSortingChange: handleSortingChange,
        onPaginationChange: handlePaginationChange,
        onGlobalFilterChange: setGlobalFilter,
        onFilterChange: handleFilterChange,
        onRowSelectionChange: setRowSelection,
        pageSizeOptions: PAGINATION_LIMITS,
        serverSide: isServerSide,
      },
    }),
    [
      table,
      data,
      columns,
      sorting,
      pagination,
      globalFilter,
      filters,
      rowSelection,
      isServerSide,
      handleSortingChange,
      handlePaginationChange,
      handleFilterChange,
    ]
  );
}
