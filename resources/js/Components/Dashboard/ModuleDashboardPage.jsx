/**
 * Reusable Module Dashboard Page Component
 *
 * Can be imported by module-specific dashboard pages to reduce code duplication.
 * Handles widget grouping, rendering, and empty states.
 *
 * Note: DashboardLayout is applied automatically via app.jsx persistent layouts
 */
import { groupWidgetsByModule } from '@/Utils/widgetHelpers';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import DashboardFilterBar from '@/Components/Dashboard/DashboardFilterBar';
import ModuleSection from '@/Components/Dashboard/ModuleSection';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';

/**
 * Reusable module dashboard page component
 *
 * @param {Object} props
 * @param {string} props.moduleName - Name of the module (e.g., "Core", "Blog")
 * @param {array} props.widgets - Array of widget objects for this module
 * @param {object} props.moduleMetadata - Metadata for all modules
 * @param {object} props.filters - Current filter values
 * @param {array} props.availableWallets - List of wallets for filtering
 */
export default function ModuleDashboardPage({
  moduleName,
  widgets,
  moduleMetadata = {},
  filters = {},
  availableWallets = [],
}) {
  const widgetsByModule = groupWidgetsByModule(widgets || []);
  const moduleGroups = widgetsByModule[moduleName] || {};
  const groupNames = Object.keys(moduleGroups).sort((a, b) => {
    if (a === 'General') return -1;
    if (b === 'General') return 1;

    const order = {
      'Financial Health': 1,
      Today: 2,
      'This Month': 3,
      'Budget Tracking': 4,
      'Savings Goals': 5,
      'This Year': 6,
    };

    if (order[a] && order[b]) return order[a] - order[b];
    if (order[a]) return -1;
    if (order[b]) return 1;

    return a.localeCompare(b);
  });

  const hasWidgets = groupNames.length > 0;
  const metadata = moduleMetadata[moduleName.toLowerCase()];

  const dashboardTitle = metadata?.title || `${moduleName} Dashboard`;

  return (
    <PageShell title={dashboardTitle}>
      <div className="mb-6 flex items-center justify-between gap-4">
        <Link href={route('dashboard')}>
          <Button variant="ghost" size="sm" className="gap-2">
            <ArrowLeft className="h-4 w-4" />
            Back to Dashboard
          </Button>
        </Link>
      </div>

      {availableWallets.length > 0 && (
        <DashboardFilterBar filters={filters} availableWallets={availableWallets} />
      )}

      {hasWidgets ? (
        <div className="space-y-6">
          <ModuleSection
            moduleName={moduleName}
            metadata={metadata}
            groups={groupNames.map((groupName) => ({
              name: groupName,
              ...moduleGroups[groupName],
            }))}
          />
        </div>
      ) : (
        <div className="text-center py-12">
          <p className="text-muted-foreground">No widgets available for this module.</p>
        </div>
      )}
    </PageShell>
  );
}
