/**
 * Card component displaying basic group information.
 */
import { formatDate } from '@/Utils/format';
import { UserPlus } from 'lucide-react';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

export default function GroupDetailsCard({ group }) {
  return (
    <Card>
      <CardHeader>
        <div className="flex items-center gap-2">
          <UserPlus className="h-5 w-5 text-primary" />
          <CardTitle>Group Details</CardTitle>
        </div>
        <CardDescription>Basic information about this group</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid gap-4 md:grid-cols-2">
          <div>
            <div className="text-sm font-medium text-muted-foreground">Name</div>
            <div className="mt-1 font-medium">{group.name}</div>
          </div>
          <div>
            <div className="text-sm font-medium text-muted-foreground">Slug</div>
            <div className="mt-1 font-mono text-sm">{group.slug}</div>
          </div>
          {group.description && (
            <div className="md:col-span-2">
              <div className="text-sm font-medium text-muted-foreground">Description</div>
              <div className="mt-1 text-sm">{group.description}</div>
            </div>
          )}
          <div>
            <div className="text-sm font-medium text-muted-foreground">Created</div>
            <div className="mt-1 text-sm">{formatDate(group.created_at, 'full')}</div>
          </div>
          <div>
            <div className="text-sm font-medium text-muted-foreground">Last Updated</div>
            <div className="mt-1 text-sm">{formatDate(group.updated_at, 'full')}</div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
