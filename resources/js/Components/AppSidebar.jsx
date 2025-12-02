/**
 * Main application sidebar component with collapsible menu groups
 * Displays user profile, navigation menus, and logout functionality
 * Supports grouped menu items with expand/collapse functionality
 * Modern implementation with proper nested menu support
 */
import { getIcon } from '@/Utils/iconResolver';
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronRight, LogOut, User } from 'lucide-react';
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
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/Components/ui/sidebar';

export default function AppSidebar() {
  const { menus, auth } = usePage().props;
  const user = auth?.user;
  const menuEntries = Object.entries(menus || {});
  const [openGroups, setOpenGroups] = useState({});
  const [openItems, setOpenItems] = useState({});

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

  const toggleMenuItem = (route) => {
    setOpenItems((prev) => ({
      ...prev,
      [route]: !prev[route],
    }));
  };

  const isChildActive = (item) => {
    if (!item.children || item.children.length === 0) {
      return false;
    }

    return item.children.some((child) => {
      if (route().current(child.route)) {
        return true;
      }

      return isChildActive(child);
    });
  };

  const renderMenuItem = (item, depth = 0) => {
    const Icon = getIcon(item.icon);
    const hasChildren = item.children && item.children.length > 0;
    const hasValidRoute = route().has(item.route);
    const isActive = hasValidRoute && route().current(item.route);
    const hasActiveChild = isChildActive(item);
    const isOpen = openItems[item.route] ?? (hasActiveChild && depth === 0);

    if (!hasValidRoute && !hasChildren) {
      return null;
    }

    const validChildren = hasChildren
      ? item.children.filter(
          (child) => route().has(child.route) || (child.children && child.children.length > 0)
        )
      : [];

    if (hasChildren && validChildren.length === 0) {
      return null;
    }

    if (hasChildren && validChildren.length > 0) {
      return (
        <Collapsible
          key={item.route}
          asChild
          open={isOpen}
          onOpenChange={() => toggleMenuItem(item.route)}
          className="group/collapsible"
        >
          <SidebarMenuItem>
            {hasValidRoute ? (
              <CollapsibleTrigger asChild>
                <SidebarMenuButton
                  tooltip={item.label}
                  isActive={isActive || hasActiveChild}
                  className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                >
                  <Link href={route(item.route)} className="flex items-center gap-2 flex-1">
                    <Icon className="h-4 w-4 shrink-0" />
                    <span>{item.label}</span>
                  </Link>
                  <ChevronRight className="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </SidebarMenuButton>
              </CollapsibleTrigger>
            ) : (
              <CollapsibleTrigger asChild>
                <SidebarMenuButton
                  tooltip={item.label}
                  isActive={hasActiveChild}
                  className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                >
                  <Icon className="h-4 w-4 shrink-0" />
                  <span>{item.label}</span>
                  <ChevronRight className="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </SidebarMenuButton>
              </CollapsibleTrigger>
            )}
            <CollapsibleContent>
              <SidebarMenuSub>
                {validChildren.map((child) => {
                  const ChildIcon = getIcon(child.icon);
                  const childIsActive = route().has(child.route) && route().current(child.route);
                  const childHasChildren = child.children && child.children.length > 0;

                  if (childHasChildren) {
                    return renderMenuItem(child, depth + 1);
                  }

                  return (
                    <SidebarMenuSubItem key={child.route}>
                      <SidebarMenuSubButton asChild isActive={childIsActive}>
                        <Link href={route(child.route)}>
                          <ChildIcon className="h-4 w-4" />
                          <span>{child.label}</span>
                        </Link>
                      </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                  );
                })}
              </SidebarMenuSub>
            </CollapsibleContent>
          </SidebarMenuItem>
        </Collapsible>
      );
    }

    if (hasValidRoute) {
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
    }

    return null;
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
          const validItems = items.filter(
            (item) => route().has(item.route) || (item.children && item.children.length > 0)
          );

          if (validItems.length === 0) {
            return null;
          }

          const hasMultipleItems = validItems.length > 1;

          return (
            <SidebarGroup key={group} className={index > 0 ? 'mt-1' : ''}>
              {hasMultipleItems ? (
                <Collapsible open={isOpen} onOpenChange={() => toggleGroup(group)}>
                  <SidebarGroupLabel
                    asChild
                    className="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center justify-between group-data-[collapsible=icon]:hidden hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors cursor-pointer"
                  >
                    <CollapsibleTrigger className="flex items-center justify-between w-full">
                      <span>{group}</span>
                      <ChevronRight className="h-3 w-3 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </CollapsibleTrigger>
                  </SidebarGroupLabel>
                  <CollapsibleContent>
                    <SidebarGroupContent>
                      <SidebarMenu>{validItems.map((item) => renderMenuItem(item))}</SidebarMenu>
                    </SidebarGroupContent>
                  </CollapsibleContent>
                </Collapsible>
              ) : (
                <>
                  <SidebarGroupLabel className="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wider group-data-[collapsible=icon]:hidden">
                    {group}
                  </SidebarGroupLabel>
                  <SidebarGroupContent>
                    <SidebarMenu>{validItems.map((item) => renderMenuItem(item))}</SidebarMenu>
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
