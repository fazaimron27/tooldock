/**
 * Section card with header containing title and "View All" link
 * Common pattern in Treasury dashboard for wallets, goals, budgets, transactions
 */
import { cn } from '@/Utils/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

export default function SectionCard({ title, viewAllRoute, action, className, children }) {
  return (
    <Card className={cn('h-full flex flex-col', className)}>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle>{title}</CardTitle>
        <div className="flex items-center gap-2">
          {action}
          {viewAllRoute && (
            <Link href={viewAllRoute}>
              <Button variant="ghost" size="sm">
                View All <ArrowRight className="w-4 h-4 ml-1" />
              </Button>
            </Link>
          )}
        </div>
      </CardHeader>
      <CardContent className="flex-1">{children}</CardContent>
    </Card>
  );
}
