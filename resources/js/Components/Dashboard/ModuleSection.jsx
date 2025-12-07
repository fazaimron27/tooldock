/**
 * Displays widgets grouped by module in card sections.
 * Organizes widgets by group and type within each module.
 */
import WidgetRenderer from '@/Components/Dashboard/WidgetRenderer';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * Renders widgets in a grid layout (up to 3 columns).
 * Widgets are grouped into rows of 3 for responsive display.
 */
function renderWidgetGrid(widgets, widgetType) {
  if (widgets.length === 0) {
    return null;
  }

  const rows = [];
  for (let i = 0; i < widgets.length; i += 3) {
    rows.push(widgets.slice(i, i + 3));
  }

  const getRowClass = (rowLength) => {
    if (rowLength === 1) return 'grid gap-4 items-start grid-cols-1';
    if (rowLength === 2) return 'grid gap-4 items-start md:grid-cols-2';
    return 'grid gap-4 items-start md:grid-cols-2 lg:grid-cols-3';
  };

  return (
    <div className="space-y-4">
      {rows.map((row, rowIndex) => (
        <div key={rowIndex} className={getRowClass(row.length)}>
          {row.map((widget, index) => (
            <WidgetRenderer
              key={`${widget.module}-${widget.title}-${widgetType}-${rowIndex}-${index}`}
              widget={widget}
            />
          ))}
        </div>
      ))}
    </div>
  );
}

function WidgetGroup({
  groupName,
  statWidgets,
  chartWidgets,
  activityWidgets,
  systemWidgets,
  tableWidgets,
}) {
  const hasWidgets =
    statWidgets.length > 0 ||
    chartWidgets.length > 0 ||
    activityWidgets.length > 0 ||
    systemWidgets.length > 0 ||
    tableWidgets.length > 0;

  if (!hasWidgets) {
    return null;
  }

  return (
    <div className="space-y-4">
      {groupName !== 'General' && (
        <div className="border-b pb-2">
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
            {groupName}
          </h3>
        </div>
      )}

      {renderWidgetGrid(statWidgets, 'stat')}
      {renderWidgetGrid(chartWidgets, 'chart')}
      {renderWidgetGrid(activityWidgets, 'activity')}
      {renderWidgetGrid(systemWidgets, 'system')}

      {tableWidgets.length > 0 && (
        <div className="space-y-4">
          {tableWidgets.map((widget, index) => (
            <WidgetRenderer
              key={`${widget.module}-${widget.title}-table-${index}`}
              widget={widget}
            />
          ))}
        </div>
      )}
    </div>
  );
}

/**
 * Renders a module section with widgets grouped by group and type.
 *
 * @param {string} moduleName - Name of the module
 * @param {array} groups - Array of group objects with widget arrays by type
 */
export default function ModuleSection({ moduleName, groups = [] }) {
  if (!groups || groups.length === 0) {
    return null;
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{moduleName} Module</CardTitle>
      </CardHeader>
      <CardContent className="space-y-6">
        {groups.map((group) => (
          <WidgetGroup
            key={group.name}
            groupName={group.name}
            statWidgets={group.stats || []}
            chartWidgets={group.charts || []}
            activityWidgets={group.activities || []}
            systemWidgets={group.systems || []}
            tableWidgets={group.tables || []}
          />
        ))}
      </CardContent>
    </Card>
  );
}
