import { cn } from '@/Utils/utils';
import { flexRender } from '@tanstack/react-table';
import { ArrowDownRight, ArrowUpDown, ArrowUpRight, Search } from 'lucide-react';

import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

/**
 * Smart DataTable component that wraps TanStack Table with built-in UI
 * @param {object} props
 * @param {object} props.table - TanStack Table instance
 * @param {array} props.data - Table data
 * @param {array} props.columns - Column definitions
 * @param {boolean} props.searchable - Show search input (default: true)
 * @param {boolean} props.pagination - Show pagination controls (default: true)
 * @param {boolean} props.sorting - Enable sorting (default: true)
 * @param {function} props.onRowClick - Callback when row is clicked
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.title - Table title (optional)
 * @param {string} props.description - Table description (optional)
 * @param {array} props.pageSizeOptions - Page size options (default: [10, 20, 30, 50])
 * @param {boolean} props.showCard - Wrap table in Card component (default: true)
 */
export default function DataTable({
  table,
  data: _data = [],
  columns: _columns = [],
  searchable = true,
  pagination = true,
  sorting = true,
  onRowClick = null,
  className,
  title = null,
  description = null,
  pageSizeOptions = [10, 20, 30, 50],
  showCard = true,
}) {
  const tableContent = (
    <div className={cn('space-y-4', className)}>
      {/* Search Input */}
      {searchable && (
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            type="text"
            placeholder="Search..."
            value={table.getState().globalFilter ?? ''}
            onChange={(e) => table.setGlobalFilter(e.target.value)}
            className="pl-9"
          />
        </div>
      )}

      {/* Table */}
      <div className="rounded-md border overflow-x-auto">
        <table className="w-full min-w-[640px]">
          <thead>
            {table.getHeaderGroups().map((headerGroup) => (
              <tr key={headerGroup.id} className="border-b">
                {headerGroup.headers.map((header) => (
                  <th
                    key={header.id}
                    className="h-12 px-4 text-left align-middle font-medium text-muted-foreground whitespace-nowrap"
                  >
                    {header.isPlaceholder ? null : (
                      <div
                        className={cn(
                          'flex items-center gap-2',
                          sorting && header.column.getCanSort()
                            ? 'cursor-pointer select-none hover:text-foreground'
                            : ''
                        )}
                        onClick={sorting ? header.column.getToggleSortingHandler() : undefined}
                      >
                        {flexRender(header.column.columnDef.header, header.getContext())}
                        {sorting && header.column.getCanSort() && (
                          <>
                            <ArrowUpDown className="h-4 w-4 opacity-50" />
                            {(() => {
                              const sorted = header.column.getIsSorted();
                              if (sorted === 'asc') {
                                return <ArrowUpRight className="h-4 w-4" />;
                              }
                              if (sorted === 'desc') {
                                return <ArrowDownRight className="h-4 w-4" />;
                              }
                              return null;
                            })()}
                          </>
                        )}
                      </div>
                    )}
                  </th>
                ))}
              </tr>
            ))}
          </thead>
          <tbody>
            {table.getRowModel().rows.length === 0 ? (
              <tr>
                <td
                  colSpan={table.getAllColumns().length}
                  className="h-24 text-center text-muted-foreground"
                >
                  No results found.
                </td>
              </tr>
            ) : (
              table.getRowModel().rows.map((row) => (
                <tr
                  key={row.id}
                  className={cn(
                    'border-b transition-colors hover:bg-muted/50',
                    onRowClick && 'cursor-pointer'
                  )}
                  onClick={onRowClick ? () => onRowClick(row.original) : undefined}
                >
                  {row.getVisibleCells().map((cell) => (
                    <td key={cell.id} className="p-4 align-middle whitespace-nowrap">
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination Controls */}
      {pagination && (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-2 flex-wrap">
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.firstPage()}
              disabled={!table.getCanPreviousPage()}
            >
              First
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.previousPage()}
              disabled={!table.getCanPreviousPage()}
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.nextPage()}
              disabled={!table.getCanNextPage()}
            >
              Next
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.lastPage()}
              disabled={!table.getCanNextPage()}
            >
              Last
            </Button>
          </div>

          <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
            <span className="text-sm text-muted-foreground whitespace-nowrap">
              Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
            </span>
            <div className="relative">
              <select
                value={table.getState().pagination.pageSize}
                onChange={(e) => table.setPageSize(Number(e.target.value))}
                className="px-3 py-1 pr-8 text-sm border rounded-md bg-background cursor-pointer w-full sm:w-auto"
              >
                {pageSizeOptions.map((pageSize) => (
                  <option key={pageSize} value={pageSize}>
                    Show {pageSize}
                  </option>
                ))}
              </select>
            </div>
            <span className="text-sm text-muted-foreground whitespace-nowrap">
              Showing {table.getRowModel().rows.length} of {table.getRowCount()} rows
            </span>
          </div>
        </div>
      )}
    </div>
  );

  if (showCard && (title || description)) {
    return (
      <Card>
        {title && (
          <CardHeader>
            <CardTitle>{title}</CardTitle>
            {description && <CardDescription>{description}</CardDescription>}
          </CardHeader>
        )}
        <CardContent>{tableContent}</CardContent>
      </Card>
    );
  }

  if (showCard) {
    return <Card>{tableContent}</Card>;
  }

  return tableContent;
}
