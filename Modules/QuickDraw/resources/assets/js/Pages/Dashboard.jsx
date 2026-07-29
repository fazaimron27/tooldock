/**
 * QuickDraw Module Dashboard Page
 *
 * Displays detailed widgets for the QuickDraw module (scope: 'detail').
 */
import ModuleDashboardPage from '@/Components/Dashboard/ModuleDashboardPage';

export default function Dashboard({ widgets = [], moduleMetadata = {} }) {
  return (
    <ModuleDashboardPage moduleName="QuickDraw" widgets={widgets} moduleMetadata={moduleMetadata} />
  );
}
