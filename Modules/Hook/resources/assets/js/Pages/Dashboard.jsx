/**
 * Hook Module Dashboard Page
 *
 * Displays detailed widgets for the Hook module (scope: 'detail').
 */
import ModuleDashboardPage from '@/Components/Dashboard/ModuleDashboardPage';

export default function Dashboard({ widgets = [], moduleMetadata = {} }) {
  return (
    <ModuleDashboardPage moduleName="Hook" widgets={widgets} moduleMetadata={moduleMetadata} />
  );
}
