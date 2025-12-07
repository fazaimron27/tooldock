/**
 * Reusable Module Dashboard Page Component
 *
 * Can be imported by module-specific dashboard pages to reduce code duplication.
 * Handles widget grouping, rendering, and empty states.
 */
import { groupWidgetsByModule } from '@/Utils/widgetHelpers';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import ModuleSection from '@/Components/Dashboard/ModuleSection';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

/**
 * Reusable module dashboard page component
 *
 * @param {Object} props
 * @param {string} props.moduleName - Name of the module (e.g., "Core", "Blog")
 * @param {array} props.widgets - Array of widget objects for this module
 */
export default function ModuleDashboardPage({ moduleName, widgets }) {
  const widgetsByModule = groupWidgetsByModule(widgets || []);
  const moduleGroups = widgetsByModule[moduleName] || {};
  const groupNames = Object.keys(moduleGroups).sort((a, b) => {
    if (a === 'General') return -1;
    if (b === 'General') return 1;
    return a.localeCompare(b);
  });

  const hasWidgets = groupNames.length > 0;

  // Use custom title for Core module dashboard
  const dashboardTitle =
    moduleName === 'Core' ? 'User Management Dashboard' : `${moduleName} Dashboard`;

  return (
    <DashboardLayout>
      <PageShell title={dashboardTitle}>
        <div className="mb-6">
          <Link href={route('dashboard')}>
            <Button variant="ghost" size="sm" className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Back to Dashboard
            </Button>
          </Link>
        </div>

        {hasWidgets ? (
          <div className="space-y-6">
            {groupNames.map((groupName) => (
              <ModuleSection
                key={groupName}
                moduleName={moduleName}
                groups={[
                  {
                    name: groupName,
                    ...moduleGroups[groupName],
                  },
                ]}
              />
            ))}
          </div>
        ) : (
          <div className="text-center py-12">
            <p className="text-muted-foreground">No widgets available for this module.</p>
          </div>
        )}
      </PageShell>
    </DashboardLayout>
  );
}
