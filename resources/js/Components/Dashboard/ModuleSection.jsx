/**
 * Module Section component for displaying widgets grouped by module
 * Each module section is displayed in its own card with widgets organized by group and type
 */
import WidgetRenderer from '@/Components/Dashboard/WidgetRenderer';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * Render a widget group section
 *
 * @param {Object} props
 * @param {string} props.groupName - Name of the group
 * @param {array} props.statWidgets - Array of stat widgets
 * @param {array} props.chartWidgets - Array of chart widgets
 * @param {array} props.activityWidgets - Array of activity widgets
 * @param {array} props.systemWidgets - Array of system widgets
 */
function WidgetGroup({ groupName, statWidgets, chartWidgets, activityWidgets, systemWidgets }) {
  const hasWidgets =
    statWidgets.length > 0 ||
    chartWidgets.length > 0 ||
    activityWidgets.length > 0 ||
    systemWidgets.length > 0;

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
    </div>
  );
}

/**
 * Render a module section with its widgets grouped by group and type
 *
 * @param {Object} props
 * @param {string} props.moduleName - Name of the module
 * @param {array} props.groups - Array of group objects with name and widget arrays by type
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
          />
        ))}
      </CardContent>
    </Card>
  );
}
