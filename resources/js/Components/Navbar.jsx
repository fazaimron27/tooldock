/**
 * Top navigation bar component with dynamic backdrop blur effect
 *
 * Displays breadcrumbs, search, theme toggle, and notifications. The navbar
 * features a dynamic blur effect that intensifies when the page is scrolled,
 * creating a glassmorphism effect. The navbar is absolutely positioned to
 * overlay the scroll container, allowing content to scroll behind it.
 *
 * @param {string} header - Optional header text to display in breadcrumbs
 * @param {React.RefObject} scrollContainerRef - Ref to the scrollable container
 */
import { useScrollBlur } from '@/Hooks/useScrollBlur';
import SignalBell from '@Signal/Components/SignalBell';
import { Link, router, usePage } from '@inertiajs/react';
import { Info, LogOut, Mail, Search, Settings, User } from 'lucide-react';
import { useState } from 'react';

import InfoDialog from '@/Components/InfoDialog';
import { ModeToggle } from '@/Components/ModeToggle';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import { SidebarTrigger } from '@/Components/ui/sidebar';

export default function Navbar({ scrollContainerRef }) {
  const { auth } = usePage().props;
  const user = auth?.user;
  const isScrolled = useScrollBlur(scrollContainerRef);
  const [isInfoDialogOpen, setIsInfoDialogOpen] = useState(false);

  const getInitials = (name) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const blurClasses = isScrolled
    ? 'bg-background/40 backdrop-blur-xl'
    : 'bg-background/95 backdrop-blur-sm';

  return (
    <header
      className={`absolute top-0 left-0 right-0 z-50 flex shrink-0 border-b px-4 py-4 pb-5 transition-all duration-200 ${blurClasses}`}
    >
      <div className="flex w-full items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <SidebarTrigger className="-ml-1" />
        </div>

        <div className="flex items-center gap-4">
          <div className="hidden md:block relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            <Input type="search" placeholder="Search..." className="pl-9 w-64 h-9" />
          </div>
          <div className="flex items-center gap-1">
            <ModeToggle />
            <SignalBell />
          </div>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                className="relative h-10 w-10 rounded-full p-0 transition-all hover:ring-2 hover:ring-primary/20 focus-visible:ring-2 focus-visible:ring-primary/20"
              >
                <Avatar className="h-10 w-10 ring-2 ring-background">
                  <AvatarImage src={user?.avatar_url} alt={user?.name} />
                  <AvatarFallback className="bg-gradient-to-br from-primary/20 to-primary/10 text-primary font-semibold">
                    {getInitials(user?.name || 'U')}
                  </AvatarFallback>
                </Avatar>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-64">
              <div className="rounded-t-md bg-muted px-3 py-3.5 -mx-1 -mt-1 mb-1">
                <div className="flex flex-col gap-2">
                  <div className="flex flex-col gap-1.5">
                    <p className="text-sm font-semibold leading-tight text-foreground">
                      {user?.name || 'User'}
                    </p>
                    <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                      <Mail className="h-3 w-3 shrink-0 opacity-70" />
                      <span className="truncate">{user?.email || ''}</span>
                    </div>
                    {user?.roles && user.roles.length > 0 && (
                      <div className="flex flex-wrap gap-1.5 pt-0.5">
                        {user.roles.map((role) => (
                          <Badge
                            key={role.id}
                            variant="outline"
                            className="text-xs bg-background/50 border-border/50 text-foreground"
                          >
                            {role.name}
                          </Badge>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              </div>
              <DropdownMenuItem asChild className="cursor-pointer">
                <Link href={route('profile.edit')} className="flex items-center">
                  <User className="mr-2 h-4 w-4" />
                  <span>Profile</span>
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem disabled className="cursor-not-allowed opacity-50">
                <Settings className="mr-2 h-4 w-4" />
                <span>Settings</span>
              </DropdownMenuItem>
              <DropdownMenuItem
                className="cursor-pointer"
                onClick={() => setIsInfoDialogOpen(true)}
              >
                <Info className="mr-2 h-4 w-4" />
                <span>Info</span>
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                className="text-destructive focus:text-destructive focus:bg-destructive/10 cursor-pointer"
                onClick={() => {
                  router.post(route('logout'));
                }}
              >
                <LogOut className="mr-2 h-4 w-4" />
                <span>Log out</span>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
      <InfoDialog open={isInfoDialogOpen} onOpenChange={setIsInfoDialogOpen} />
    </header>
  );
}
