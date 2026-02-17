import ModuleDashboardPage from '@/Components/Dashboard/ModuleDashboardPage';

/**
 * Routine Dashboard Page
 *
 * Displays detailed widgets for the Routine module (scope: 'detail' or 'both')
 */
export default function Dashboard({ widgets = [], moduleMetadata = {}, filters = {} }) {
  return (
    <ModuleDashboardPage
      moduleName="Routine"
      widgets={widgets}
      moduleMetadata={moduleMetadata}
      filters={filters}
    />
  );
}
