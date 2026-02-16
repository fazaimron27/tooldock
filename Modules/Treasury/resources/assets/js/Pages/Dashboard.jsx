/**
 * Treasury module dashboard page
 * Displays widgets for treasury statistics and metrics
 */
import ModuleDashboardPage from '@/Components/Dashboard/ModuleDashboardPage';

export default function Dashboard({
  widgets = [],
  moduleMetadata = {},
  filters = {},
  availableWallets = [],
}) {
  return (
    <ModuleDashboardPage
      moduleName="Treasury"
      widgets={widgets}
      moduleMetadata={moduleMetadata}
      filters={filters}
      availableWallets={availableWallets}
    />
  );
}
