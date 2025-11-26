import { getIcon } from '@/Utils/iconResolver';
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, LogOut, User } from 'lucide-react';
import { useState } from 'react';

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/ui/sidebar';

export default function AppSidebar() {
  const { menus, auth } = usePage().props;
  const user = auth?.user;
  const menuEntries = Object.entries(menus || {});
  const [openGroups, setOpenGroups] = useState({});

  const getInitials = (name) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const toggleGroup = (group) => {
    setOpenGroups((prev) => ({
      ...prev,
      [group]: !prev[group],
    }));
  };

  return (
    <Sidebar>
      <SidebarHeader className="border-b p-4">
        <Link
          href={route('profile.edit')}
          className="flex items-center gap-3 px-2 py-2 hover:bg-accent rounded-lg transition-colors group"
        >
          <Avatar className="h-10 w-10 border-2 border-sidebar-border group-hover:border-sidebar-accent transition-colors">
            <AvatarImage src={user?.avatar_url} alt={user?.name} />
            <AvatarFallback className="bg-primary/10 text-primary font-semibold">
              {getInitials(user?.name || 'U')}
            </AvatarFallback>
          </Avatar>
          <div className="flex flex-col min-w-0 flex-1 group-data-[collapsible=icon]:hidden">
            <span className="text-sm font-semibold text-sidebar-foreground truncate">
              {user?.name}
            </span>
            <span className="text-xs text-muted-foreground truncate">{user?.email}</span>
          </div>
        </Link>
      </SidebarHeader>

      <SidebarContent className="gap-1">
        {menuEntries.map(([group, items], index) => {
          const isOpen = openGroups[group] ?? true;
          const hasMultipleItems = items.length > 1;

          return (
            <SidebarGroup key={group} className={index > 0 ? 'mt-1' : ''}>
              {hasMultipleItems ? (
                <Collapsible open={isOpen} onOpenChange={() => toggleGroup(group)}>
                  <SidebarGroupLabel className="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center justify-between group-data-[collapsible=icon]:hidden">
                    <span>{group}</span>
                    <CollapsibleTrigger asChild>
                      <button className="p-0.5 hover:bg-sidebar-accent rounded transition-colors">
                        {isOpen ? (
                          <ChevronDown className="h-3 w-3" />
                        ) : (
                          <ChevronRight className="h-3 w-3" />
                        )}
                      </button>
                    </CollapsibleTrigger>
                  </SidebarGroupLabel>
                  <CollapsibleContent>
                    <SidebarGroupContent>
                      <SidebarMenu>
                        {items.map((item) => {
                          const Icon = getIcon(item.icon);
                          const isActive = route().current(item.route);

                          return (
                            <SidebarMenuItem key={item.route}>
                              <SidebarMenuButton asChild isActive={isActive} tooltip={item.label}>
                                <Link href={route(item.route)}>
                                  <Icon className="h-4 w-4" />
                                  <span>{item.label}</span>
                                </Link>
                              </SidebarMenuButton>
                            </SidebarMenuItem>
                          );
                        })}
                      </SidebarMenu>
                    </SidebarGroupContent>
                  </CollapsibleContent>
                </Collapsible>
              ) : (
                <>
                  <SidebarGroupLabel className="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wider group-data-[collapsible=icon]:hidden">
                    {group}
                  </SidebarGroupLabel>
                  <SidebarGroupContent>
                    <SidebarMenu>
                      {items.map((item) => {
                        const Icon = getIcon(item.icon);
                        const isActive = route().current(item.route);

                        return (
                          <SidebarMenuItem key={item.route}>
                            <SidebarMenuButton asChild isActive={isActive} tooltip={item.label}>
                              <Link href={route(item.route)}>
                                <Icon className="h-4 w-4" />
                                <span>{item.label}</span>
                              </Link>
                            </SidebarMenuButton>
                          </SidebarMenuItem>
                        );
                      })}
                    </SidebarMenu>
                  </SidebarGroupContent>
                </>
              )}
            </SidebarGroup>
          );
        })}
      </SidebarContent>

      <SidebarFooter className="border-t p-2">
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton asChild tooltip="Profile">
              <Link href={route('profile.edit')}>
                <User className="h-4 w-4" />
                <span>Profile</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton
              className="text-destructive focus:text-destructive hover:text-destructive"
              onClick={() => {
                router.post(route('logout'));
              }}
            >
              <LogOut className="h-4 w-4" />
              <span>Log Out</span>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
    </Sidebar>
  );
}
