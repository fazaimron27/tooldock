import ActivityListItem from '@/Components/Common/ActivityListItem';
import ProgressBar from '@/Components/Common/ProgressBar';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index() {
  return (
    <DashboardLayout header="Blog">
      <PageShell title="Blog">
        <div className="space-y-6">
          <div className="rounded-lg border bg-card p-6 shadow-sm">
            <h1 className="text-3xl font-bold">Module Works!</h1>
            <p className="mt-4 text-muted-foreground">
              This page is loaded from the Blog module using Inertia.js
            </p>
            <div className="mt-6 flex gap-4">
              <Button>Default Button</Button>
              <Button variant="outline">Outline Button</Button>
              <Button variant="secondary">Secondary Button</Button>
              <Button variant="destructive">Destructive Button</Button>
            </div>
          </div>

          {/* Example: Reusable Components */}
          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Blog Statistics</CardTitle>
                <CardDescription>Content metrics and performance</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <ProgressBar
                    label="Published Posts"
                    value="85%"
                    percentage={85}
                    color="success"
                  />
                  <ProgressBar label="Draft Posts" value="15%" percentage={15} color="warning" />
                  <ProgressBar label="Total Views" value="92%" percentage={92} color="info" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Recent Activity</CardTitle>
                <CardDescription>Latest blog events</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <ActivityListItem
                    title="New post published: 'Getting Started with React'"
                    timestamp="2 hours ago"
                  />
                  <ActivityListItem
                    title="Comment received on 'Laravel Best Practices'"
                    timestamp="5 hours ago"
                  />
                  <ActivityListItem
                    title="Post updated: 'Advanced TypeScript Patterns'"
                    timestamp="1 day ago"
                  />
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
