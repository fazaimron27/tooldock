import { Head } from '@inertiajs/react';

import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index() {
  return (
    <DashboardLayout header="Blog">
      <Head title="Blog" />

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
    </DashboardLayout>
  );
}
