/**
 * Guest Welcome Page
 *
 * Displays a welcoming page for users in the Guest group who have no permissions.
 * Provides information about their account status and next steps.
 */
import { Link, usePage } from '@inertiajs/react';
import { CheckCircle2, HelpCircle, Mail, Sparkles, User } from 'lucide-react';

import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

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

export default function Welcome() {
  const { auth } = usePage().props;
  const user = auth?.user;
  const userName = user?.name || 'User';

  const greeting = getGreeting();

  return (
    <DashboardLayout>
      <PageShell title="Welcome">
        <div className="max-w-4xl mx-auto space-y-6">
          {/* Welcome Banner */}
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
                  <p className="text-sm text-muted-foreground md:text-base">
                    Welcome to Tool Dock. Your account has been created successfully.
                  </p>
                </div>
              </div>
            </div>
          </Card>

          {/* Account Status */}
          <Card>
            <CardHeader>
              <div className="flex items-center gap-2">
                <User className="h-5 w-5 text-primary" />
                <CardTitle>Your Account Status</CardTitle>
              </div>
              <CardDescription>Information about your current account access</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-start gap-3 p-4 rounded-lg bg-muted/50">
                <CheckCircle2 className="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
                <div>
                  <p className="font-medium">Account Created</p>
                  <p className="text-sm text-muted-foreground">
                    Your account has been successfully created and you are currently in the Guest
                    group.
                  </p>
                </div>
              </div>
              <div className="flex items-start gap-3 p-4 rounded-lg bg-muted/50">
                <HelpCircle className="h-5 w-5 text-amber-500 mt-0.5 flex-shrink-0" />
                <div>
                  <p className="font-medium">Access Pending</p>
                  <p className="text-sm text-muted-foreground">
                    Your account is currently limited. To gain full access to the system, please
                    contact your administrator to assign you to a group with appropriate
                    permissions.
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Next Steps */}
          <Card>
            <CardHeader>
              <CardTitle>What's Next?</CardTitle>
              <CardDescription>Here's what you can do while waiting for access</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-3">
                <div className="flex items-start gap-3 p-4 rounded-lg border">
                  <Mail className="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
                  <div className="flex-1">
                    <p className="font-medium mb-1">Contact Administrator</p>
                    <p className="text-sm text-muted-foreground mb-3">
                      Reach out to your system administrator to request access. They can assign you
                      to a group with the appropriate permissions for your role.
                    </p>
                    <Button variant="outline" size="sm" asChild>
                      <Link href={route('profile.edit')}>View Profile</Link>
                    </Button>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Help Section */}
          <Card>
            <CardHeader>
              <CardTitle>Need Help?</CardTitle>
              <CardDescription>If you have questions or need assistance</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground">
                If you believe you should have access or have any questions about your account,
                please contact your system administrator. They can help you get set up with the
                appropriate permissions.
              </p>
            </CardContent>
          </Card>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
