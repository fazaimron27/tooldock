/**
 * Main Dashboard page component with Hybrid Architecture
 *
 * System Health Section: Hardcoded module counts (Total, Active, Inactive)
 * Overview Widgets: Summary widgets from all modules (scope: 'overview' or 'both')
 * Module Quick Links: Links to detailed module dashboards
 */
import { groupWidgetsByModule, sortModules } from '@/Utils/widgetHelpers';
import { Link } from '@inertiajs/react';
import { ArrowRight, LayoutDashboard, Package, Power, PowerOff } from 'lucide-react';

import ModuleSection from '@/Components/Dashboard/ModuleSection';
import WelcomeBanner from '@/Components/Dashboard/WelcomeBanner';
import StatCard from '@/Components/DataDisplay/StatCard';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Dashboard({ systemHealth, widgets, modules = [] }) {
  const widgetsByModule = groupWidgetsByModule(widgets || []);
  const moduleNames = sortModules(Object.keys(widgetsByModule));

  return (
    <DashboardLayout>
      <PageShell title="Dashboard">
        <WelcomeBanner />

        <div className="grid gap-4 md:grid-cols-3">
          <StatCard title="Total Modules" value={systemHealth.total} icon={Package} />
          <StatCard title="Active Modules" value={systemHealth.active} icon={Power} />
          <StatCard title="Inactive Modules" value={systemHealth.inactive} icon={PowerOff} />
        </div>

        {modules.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Module Dashboards</CardTitle>
              <CardDescription>
                Access detailed analytics and insights for each module
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {modules.map((module) => (
                  <Link key={module.name} href={module.route}>
                    <Card className="transition-colors hover:bg-accent cursor-pointer h-full">
                      <CardContent className="flex items-center justify-between p-4">
                        <div className="flex items-center gap-3">
                          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <LayoutDashboard className="h-5 w-5 text-primary" />
                          </div>
                          <div>
                            <p className="font-medium">{module.name}</p>
                            <p className="text-sm text-muted-foreground">View dashboard</p>
                          </div>
                        </div>
                        <ArrowRight className="h-4 w-4 text-muted-foreground" />
                      </CardContent>
                    </Card>
                  </Link>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {moduleNames.length > 0 && (
          <div className="space-y-6">
            <div>
              <h2 className="text-2xl font-bold mb-4">Overview</h2>
              <p className="text-muted-foreground mb-6">
                Key metrics from all modules. Visit individual module dashboards for detailed
                analytics.
              </p>
            </div>
            {moduleNames.map((moduleName) => {
              const moduleGroups = widgetsByModule[moduleName];
              const groupNames = Object.keys(moduleGroups).sort((a, b) => {
                // Put "General" group first if it exists
                if (a === 'General') return -1;
                if (b === 'General') return 1;
                return a.localeCompare(b);
              });

              return (
                <ModuleSection
                  key={moduleName}
                  moduleName={moduleName}
                  groups={groupNames.map((groupName) => ({
                    name: groupName,
                    ...moduleGroups[groupName],
                  }))}
                />
              );
            })}
          </div>
        )}
      </PageShell>
    </DashboardLayout>
  );
}
