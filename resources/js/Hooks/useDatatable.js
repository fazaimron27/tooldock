import { DEFAULT_PAGE_SIZE, PAGINATION_LIMITS } from '@/Utils/constants';
import { router } from '@inertiajs/react';
import {
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { useCallback, useEffect, useMemo, useState } from 'react';

/**
 * Hook for managing datatable state with server-side pagination, filtering, and sorting
 * @param {object} options - Configuration options
 * @param {array} options.data - Table data array
 * @param {array} options.columns - Column definitions
 * @param {array} options.initialSorting - Initial sorting state (default: [])
 * @param {number} options.pageSize - Initial page size (default: DEFAULT_PAGE_SIZE)
 * @param {string} options.route - Route name or URL for server-side requests (optional)
 * @param {function} options.onPaginationChange - Callback when pagination changes (optional)
 * @param {function} options.onSortChange - Callback when sorting changes (optional)
 * @param {function} options.onFilterChange - Callback when filter changes (optional)
 * @param {boolean} options.serverSide - Enable server-side pagination/filtering (default: true if route provided)
 * @param {object} options.initialFilters - Initial filter values (optional)
 * @returns {object} Table props and handlers
 */
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
}) {
  // Determine if server-side is enabled
  const isServerSide = serverSide !== null ? serverSide : !!route;

  // State management
  const [sorting, setSorting] = useState(initialSorting);
  const [globalFilter, setGlobalFilter] = useState('');
  const [pagination, setPagination] = useState({
    pageIndex: 0,
    pageSize: pageSize,
  });
  const [rowSelection, setRowSelection] = useState({});
  const [filters, setFilters] = useState(initialFilters);

  // Handle server-side pagination/filtering/sorting
  useEffect(() => {
    if (!isServerSide || !route) {
      return;
    }

    const params = {
      page: pagination.pageIndex + 1,
      per_page: pagination.pageSize,
    };

    // Add sorting
    if (sorting.length > 0) {
      const sort = sorting[0];
      params.sort = sort.id;
      params.direction = sort.desc ? 'desc' : 'asc';
    }

    // Add global filter
    if (globalFilter) {
      params.search = globalFilter;
    }

    // Add custom filters
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params[key] = value;
      }
    });

    // Make Inertia request
    router.get(route, params, {
      preserveState: true,
      preserveScroll: true,
      only: ['data'], // Only reload data prop
    });
  }, [pagination, sorting, globalFilter, filters, isServerSide, route]);

  // Handle sorting change
  const handleSortingChange = useCallback(
    (updater) => {
      const newSorting = typeof updater === 'function' ? updater(sorting) : updater;
      setSorting(newSorting);
      onSortChange?.(newSorting);
    },
    [sorting, onSortChange]
  );

  // Handle pagination change
  const handlePaginationChange = useCallback(
    (updater) => {
      const newPagination = typeof updater === 'function' ? updater(pagination) : updater;
      setPagination(newPagination);
      onPaginationChange?.(newPagination);
    },
    [pagination, onPaginationChange]
  );

  // Handle filter change
  const handleFilterChange = useCallback(
    (newFilters) => {
      setFilters((prev) => ({ ...prev, ...newFilters }));
      onFilterChange?.(newFilters);
      // Reset to first page when filters change
      setPagination((prev) => ({ ...prev, pageIndex: 0 }));
    },
    [onFilterChange]
  );

  // Create table instance
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
  });

  // Return props compatible with DataTable component
  return useMemo(
    () => ({
      // Table instance
      table,
      // Data and columns
      data,
      columns,
      // State
      sorting,
      pagination,
      globalFilter,
      filters,
      rowSelection,
      // Handlers
      setSorting: handleSortingChange,
      setPagination: handlePaginationChange,
      setGlobalFilter,
      setFilters: handleFilterChange,
      setRowSelection,
      // Computed values
      selectedRows: table.getSelectedRowModel().rows.map((row) => row.original),
      selectedRowIds: Object.keys(rowSelection),
      // Pagination helpers
      pageSize: pagination.pageSize,
      pageIndex: pagination.pageIndex,
      pageCount: table.getPageCount(),
      canPreviousPage: table.getCanPreviousPage(),
      canNextPage: table.getCanNextPage(),
      // Table props (for direct spread into DataTable)
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
