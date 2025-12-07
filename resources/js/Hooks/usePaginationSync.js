/**
 * Custom hook for syncing table pagination with server-side pagination state
 * Updates table pagination if it's out of sync with server state
 */
import { useEffect } from 'react';

export function usePaginationSync(tableProps, paginatedData, defaultPerPage) {
  useEffect(() => {
    if (!tableProps.table || paginatedData?.current_page === undefined) {
      return;
    }

    const currentPageIndex = paginatedData.current_page - 1;
    const currentPagination = tableProps.table.getState().pagination;
    const serverPageSize = paginatedData?.per_page || defaultPerPage;

    if (
      currentPagination.pageIndex !== currentPageIndex ||
      currentPagination.pageSize !== serverPageSize
    ) {
      window.requestAnimationFrame(() => {
        tableProps.table.setPagination({
          pageIndex: currentPageIndex,
          pageSize: serverPageSize,
        });
      });
    }
  }, [tableProps.table, paginatedData?.current_page, paginatedData?.per_page, defaultPerPage]);
}
