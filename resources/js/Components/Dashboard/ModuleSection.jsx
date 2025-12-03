/**
 * Displays widgets grouped by module in card sections.
 * Organizes widgets by group and type within each module.
 */
import WidgetRenderer from '@/Components/Dashboard/WidgetRenderer';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

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

      {statWidgets.length > 0 && (
        <div
          className={
            statWidgets.length === 2
              ? 'grid gap-4 items-start md:grid-cols-2'
              : 'grid gap-4 items-start md:grid-cols-2 lg:grid-cols-3'
          }
        >
          {statWidgets.map((widget, index) => (
            <WidgetRenderer key={`${widget.module}-${widget.title}-${index}`} widget={widget} />
          ))}
        </div>
      )}

      {chartWidgets.length > 0 && (
        <div className="grid gap-4 md:grid-cols-2 items-start">
          {chartWidgets.map((widget, index) => (
            <WidgetRenderer key={`${widget.module}-${widget.title}-${index}`} widget={widget} />
          ))}
        </div>
      )}

      {(activityWidgets.length > 0 || systemWidgets.length > 0) && (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 items-start">
          {activityWidgets.map((widget, index) => (
            <WidgetRenderer key={`${widget.module}-${widget.title}-${index}`} widget={widget} />
          ))}
          {systemWidgets.map((widget, index) => (
            <WidgetRenderer key={`${widget.module}-${widget.title}-${index}`} widget={widget} />
          ))}
        </div>
      )}

      {tableWidgets.length > 0 && (
        <div className="space-y-4">
          {tableWidgets.map((widget, index) => (
            <WidgetRenderer key={`${widget.module}-${widget.title}-${index}`} widget={widget} />
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
