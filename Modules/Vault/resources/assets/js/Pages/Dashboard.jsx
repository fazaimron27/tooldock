/**
 * Vault module dashboard page
 * Displays widgets for vault statistics and metrics
 */
import ModuleDashboardPage from '@/Components/Dashboard/ModuleDashboardPage';

export default function Dashboard({ widgets = [] }) {
  return <ModuleDashboardPage moduleName="Vault" widgets={widgets} />;
}
