/**
 * Groups Module Dashboard Page
 *
 * Displays detailed widgets for the Groups module (scope: 'detail' or 'both')
 */
import ModuleDashboardPage from '@/Components/Dashboard/ModuleDashboardPage';

export default function Dashboard({ widgets }) {
  return <ModuleDashboardPage moduleName="Groups" widgets={widgets} />;
}
