/**
 * Bot Module Dashboard Page
 */
import ModuleDashboardPage from '@/Components/Dashboard/ModuleDashboardPage';

export default function Dashboard({ widgets = [], moduleMetadata = {} }) {
  return <ModuleDashboardPage moduleName="Bot" widgets={widgets} moduleMetadata={moduleMetadata} />;
}
