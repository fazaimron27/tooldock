/**
 * Table Widget component for displaying data tables in the dashboard.
 *
 * Renders a data table using TanStack Table via the useDatatable hook and DataTable component.
 * Supports multiple column definition formats and auto-generates columns from data when needed.
 *
 * @example
 * // PHP: Register a table widget
 * new DashboardWidget(
 *     type: 'table',
 *     title: 'Users Table',
 *     value: 0,
 *     icon: 'Users',
 *     module: 'Core',
 *     data: fn() => User::all()->toArray(),
 *     config: ['columns' => [['accessorKey' => 'name', 'header' => 'Name']]],
 * )
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { useMemo } from 'react';

import DataTable from '@/Components/DataDisplay/DataTable';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * Default cell renderer that converts values to strings, handling null/undefined.
 */
const DEFAULT_CELL_RENDERER = (info) => {
  const value = info.getValue();
  return value !== null && value !== undefined ? String(value) : '';
};

/**
 * Normalizes a column definition to TanStack Table format.
 * Supports string format, object with 'key' property, or full column definition.
 */
function normalizeColumn(col, data = []) {
  if (typeof col === 'string') {
    return {
      accessorKey: col,
      header: col.charAt(0).toUpperCase() + col.slice(1).replace(/_/g, ' '),
      cell: DEFAULT_CELL_RENDERER,
    };
  }

  if (col.key && !col.accessorKey && !col.accessorFn) {
    return {
      ...col,
      accessorKey: col.key,
      header: col.header || col.title || col.key,
      cell: col.cell || DEFAULT_CELL_RENDERER,
    };
  }

  if (col.accessorKey || col.accessorFn) {
    return {
      ...col,
      cell: col.cell || DEFAULT_CELL_RENDERER,
    };
  }

  if (data.length > 0 && typeof data[0] === 'object' && data[0] !== null) {
    const firstKey = Object.keys(data[0])[0];
    if (firstKey) {
      return {
        accessorKey: firstKey,
        header: col.header || col.title || firstKey,
        cell: DEFAULT_CELL_RENDERER,
      };
    }
  }

  if (process.env.NODE_ENV === 'development') {
    console.error('TableWidget: Unable to normalize column definition', col);
  }

  return {
    id: `unnormalized-${Math.random().toString(36).substr(2, 9)}`,
    header: col.header || col.title || 'Unknown',
    cell: DEFAULT_CELL_RENDERER,
  };
}

/**
 * Validates that column accessorKeys exist in the data structure.
 * Checks all rows to ensure keys are present across the entire dataset.
 */
function validateColumns(columns, data) {
  if (data.length === 0) {
    return columns;
  }

  const allDataKeys = new Set();
  data.forEach((row) => {
    if (row && typeof row === 'object' && row !== null) {
      Object.keys(row).forEach((key) => allDataKeys.add(key));
    }
  });

  return columns.filter((col) => {
    if (col.accessorFn) {
      return true;
    }

    if (col.accessorKey) {
      const isValid = allDataKeys.has(col.accessorKey);
      if (!isValid && process.env.NODE_ENV === 'development') {
        console.warn(
          `TableWidget: Column accessorKey "${col.accessorKey}" not found in data. Available keys: ${Array.from(allDataKeys).sort().join(', ')}`
        );
      }
      return isValid;
    }

    if (process.env.NODE_ENV === 'development') {
      console.warn('TableWidget: Column missing accessorKey or accessorFn', col);
    }
    return false;
  });
}

/**
 * Auto-generates column definitions from data structure keys.
 */
function autoGenerateColumns(data) {
  if (data.length === 0 || typeof data[0] !== 'object') {
    return [];
  }

  const allKeys = new Set();
  data.forEach((row) => {
    if (row && typeof row === 'object') {
      Object.keys(row).forEach((key) => allKeys.add(key));
    }
  });

  return Array.from(allKeys).map((key) => ({
    accessorKey: key,
    header: key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' '),
    cell: DEFAULT_CELL_RENDERER,
  }));
}

/**
 * Renders a table widget with data from the dashboard widget registry.
 *
 * @param {Object} props.widget - Widget configuration from registry
 */
export default function TableWidget({ widget }) {
  const title = widget?.title || 'Data Table';
  const description = widget?.description ?? null;
  const serverSide = widget?.serverSide ?? false;
  const route = widget?.route ?? null;
  const pageSize = widget?.pageSize ?? 10;

  const data = useMemo(() => {
    return Array.isArray(widget?.data) ? widget.data : [];
  }, [widget?.data]);

  const rawColumns = useMemo(() => {
    return Array.isArray(widget?.columns)
      ? widget.columns
      : Array.isArray(widget?.config?.columns)
        ? widget.config.columns
        : [];
  }, [widget?.columns, widget?.config?.columns]);

  const columns = useMemo(() => {
    if (rawColumns.length > 0) {
      const normalized = rawColumns.map((col) => normalizeColumn(col, data));
      return validateColumns(normalized, data);
    }

    if (data.length > 0) {
      return autoGenerateColumns(data);
    }

    return [];
  }, [rawColumns, data]);

  const { table } = useDatatable({
    data,
    columns,
    serverSide,
    route,
    pageSize,
  });

  if (data.length === 0 && !serverSide) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{title}</CardTitle>
          {description && <CardDescription>{description}</CardDescription>}
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-center h-32 text-muted-foreground">
            <p>No data available</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (columns.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{title}</CardTitle>
          {description && <CardDescription>{description}</CardDescription>}
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-center h-32 text-muted-foreground">
            <p>No columns defined</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <DataTable
      table={table}
      title={title}
      description={description}
      searchable={widget.searchable !== false}
      pagination={widget.pagination !== false}
      sorting={widget.sorting !== false}
      showCard={true}
    />
  );
}
