/**
 * Folio Module Dashboard Page
 *
 * Displays detailed widgets for the Folio module (scope: 'detail').
 */
import ModuleDashboardPage from '@/Components/Dashboard/ModuleDashboardPage';

export default function Dashboard({ widgets = [], moduleMetadata = {} }) {
  return (
    <ModuleDashboardPage moduleName="Folio" widgets={widgets} moduleMetadata={moduleMetadata} />
  );
}
