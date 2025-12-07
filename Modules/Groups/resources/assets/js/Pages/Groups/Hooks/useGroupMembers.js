/**
 * Custom hook for managing group members data and table state.
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { usePaginationSync } from '@/Hooks/usePaginationSync';
import { useMemo } from 'react';

export function useGroupMembers({ group, members, defaultPerPage = 10, columns = [] }) {
  const pageCount = useMemo(() => {
    if (!members?.total || !members?.per_page) {
      return 0;
    }
    return Math.ceil(members.total / members.per_page);
  }, [members?.total, members?.per_page]);

  const membersData = useMemo(() => {
    if (members?.data) {
      return members.data;
    }
    return group?.users || [];
  }, [members?.data, group?.users]);

  const { tableProps: memberTableProps } = useDatatable({
    data: membersData,
    columns: columns,
    route: group?.id ? route('groups.members', { group: group.id }) : null,
    serverSide: true,
    pageSize: members?.per_page || defaultPerPage,
    initialSorting: [{ id: 'name', desc: false }],
    pageCount: pageCount,
    only: ['members', 'defaultPerPage'],
  });

  usePaginationSync(memberTableProps, members, defaultPerPage);

  const table = memberTableProps.table;
  const selectedRows = useMemo(() => {
    return table?.getSelectedRowModel().rows || [];
  }, [table, table?.getState().rowSelection]);

  const selectedMemberIds = useMemo(() => {
    return selectedRows.map((row) => row.original.id);
  }, [selectedRows]);

  return {
    memberTableProps,
    selectedRows,
    selectedMemberIds,
  };
}
