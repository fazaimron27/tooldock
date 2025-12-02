/**
 * Main application sidebar component with collapsible menu groups
 * Displays user profile, navigation menus, and logout functionality
 * Supports grouped menu items with expand/collapse functionality
 * Modern implementation with proper nested menu support
 */
import { getIcon } from '@/Utils/iconResolver';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';

import ApplicationLogo from '@/Components/ApplicationLogo';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import {
  Sidebar,
  SidebarContent,
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
  SidebarSeparator,
} from '@/Components/ui/sidebar';

export default function AppSidebar() {
  const { menus } = usePage().props;
  const menuEntries = Object.entries(menus || {});
  const [openGroups, setOpenGroups] = useState({});
  const [openItems, setOpenItems] = useState({});

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
                  className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground data-[active=true]:bg-sidebar-accent/80 data-[active=true]:shadow-sm"
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
                  className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground data-[active=true]:bg-sidebar-accent/80 data-[active=true]:shadow-sm"
                >
                  <Icon className="h-4 w-4 shrink-0" />
                  <span>{item.label}</span>
                  <ChevronRight className="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </SidebarMenuButton>
              </CollapsibleTrigger>
            )}
            <CollapsibleContent className="mt-1">
              <SidebarMenuSub className="border-l-0">
                {validChildren.map((child) => {
                  const ChildIcon = getIcon(child.icon);
                  const childIsActive = route().has(child.route) && route().current(child.route);
                  const childHasChildren = child.children && child.children.length > 0;

                  if (childHasChildren) {
                    const isChildOpen = openItems[child.route] ?? false;

                    return (
                      <SidebarMenuSubItem key={child.route}>
                        <Collapsible
                          open={isChildOpen}
                          onOpenChange={() => toggleMenuItem(child.route)}
                          className="group/collapsible"
                        >
                          {route().has(child.route) ? (
                            <CollapsibleTrigger asChild>
                              <SidebarMenuSubButton
                                asChild
                                isActive={childIsActive}
                                className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground data-[active=true]:bg-sidebar-accent/80 data-[active=true]:shadow-sm"
                              >
                                <Link
                                  href={route(child.route)}
                                  className="flex items-center gap-2 flex-1"
                                >
                                  <ChildIcon className="h-4 w-4 shrink-0" />
                                  <span>{child.label}</span>
                                </Link>
                                <ChevronRight className="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                              </SidebarMenuSubButton>
                            </CollapsibleTrigger>
                          ) : (
                            <CollapsibleTrigger asChild>
                              <SidebarMenuSubButton
                                isActive={false}
                                className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                              >
                                <ChildIcon className="h-4 w-4 shrink-0" />
                                <span>{child.label}</span>
                                <ChevronRight className="ml-auto h-4 w-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                              </SidebarMenuSubButton>
                            </CollapsibleTrigger>
                          )}
                          <CollapsibleContent className="mt-1">
                            <SidebarMenuSub className="border-l-0">
                              {child.children
                                .filter(
                                  (grandchild) =>
                                    route().has(grandchild.route) ||
                                    (grandchild.children && grandchild.children.length > 0)
                                )
                                .map((grandchild) => {
                                  const GrandchildIcon = getIcon(grandchild.icon);
                                  const grandchildIsActive =
                                    route().has(grandchild.route) &&
                                    route().current(grandchild.route);

                                  return (
                                    <SidebarMenuSubItem key={grandchild.route}>
                                      <SidebarMenuSubButton
                                        asChild
                                        isActive={grandchildIsActive}
                                        className="data-[active=true]:bg-sidebar-accent/80 data-[active=true]:shadow-sm"
                                      >
                                        <Link href={route(grandchild.route)}>
                                          <GrandchildIcon className="h-4 w-4" />
                                          <span>{grandchild.label}</span>
                                        </Link>
                                      </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                  );
                                })}
                            </SidebarMenuSub>
                          </CollapsibleContent>
                        </Collapsible>
                      </SidebarMenuSubItem>
                    );
                  }

                  return (
                    <SidebarMenuSubItem key={child.route}>
                      <SidebarMenuSubButton
                        asChild
                        isActive={childIsActive}
                        className="data-[active=true]:bg-sidebar-accent/80 data-[active=true]:shadow-sm"
                      >
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
          <SidebarMenuButton
            asChild
            isActive={isActive}
            tooltip={item.label}
            className="data-[active=true]:bg-sidebar-accent/80 data-[active=true]:shadow-sm"
          >
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
      <SidebarHeader className="px-4 py-3 flex-row items-center !gap-0">
        <Link href={route('dashboard')} className="flex items-center justify-center w-full">
          <ApplicationLogo className="py-2" />
        </Link>
      </SidebarHeader>

      <SidebarSeparator className="mx-0 w-full" />

      <SidebarContent className="gap-1">
        {menuEntries.map(([group, items]) => {
          const isOpen = openGroups[group] ?? true;
          const validItems = items.filter(
            (item) => route().has(item.route) || (item.children && item.children.length > 0)
          );

          if (validItems.length === 0) {
            return null;
          }

          const hasMultipleItems = validItems.length > 1;

          return (
            <SidebarGroup key={group} className="border-0 before:content-none after:content-none">
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
    </Sidebar>
  );
}
