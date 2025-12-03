/**
 * Welcome Banner component for dashboard
 * Displays a personalized greeting with modern UI
 */
import { usePage } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';

import { Card } from '@/Components/ui/card';

/**
 * Get greeting based on time of day
 */
function getGreeting() {
  const hour = new Date().getHours();

  if (hour < 12) {
    return 'Good morning';
  } else if (hour < 18) {
    return 'Good afternoon';
  }

  return 'Good evening';
}

/**
 * Get current date in a readable format
 */
function getCurrentDate() {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

export default function WelcomeBanner() {
  const { auth } = usePage().props;
  const user = auth?.user;
  const userName = user?.name || 'User';

  const greeting = getGreeting();
  const currentDate = getCurrentDate();

  return (
    <Card className="relative overflow-hidden border-0 bg-gradient-to-br from-primary/10 via-primary/5 to-background shadow-lg">
      <div className="absolute inset-0 bg-[linear-gradient(to_right,#80808008_1px,transparent_1px),linear-gradient(to_bottom,#80808008_1px,transparent_1px)] bg-[size:24px_24px]" />
      <div className="relative p-6 md:p-8">
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <Sparkles className="h-5 w-5 text-primary" />
              <h1 className="text-2xl font-bold tracking-tight md:text-3xl">
                {greeting}, {userName.split(' ')[0]}!
              </h1>
            </div>
            <p className="text-sm text-muted-foreground md:text-base">{currentDate}</p>
            <p className="text-sm text-muted-foreground">
              Welcome back to your dashboard. Here's what's happening today.
            </p>
          </div>
        </div>
      </div>
    </Card>
  );
}
