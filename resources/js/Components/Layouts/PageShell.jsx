/**
 * Page shell component providing consistent page layout structure
 * Includes page title, breadcrumbs, action buttons area, and content area
 *
 * Breadcrumbs are automatically generated from the current route if not provided.
 * They appear below the page title following modern design patterns.
 */
import { cn } from '@/Utils/utils';
import { Head, Link, usePage } from '@inertiajs/react';
import React from 'react';

import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';

/**
 * Generate breadcrumbs from current route
 * Supports nested routes like blog.create, core.users.edit, etc.
 * Handles routes with prefixes and custom actions
 */
function generateBreadcrumbsFromRoute(title, appName) {
  const dashboardHref = route().has('dashboard') ? route('dashboard') : '/';
  const breadcrumbs = [{ label: appName, href: dashboardHref }];

  const currentRoute = route().current();
  if (!currentRoute) {
    return title && title.trim() ? [...breadcrumbs, { label: title }] : breadcrumbs;
  }

  const routeParts = currentRoute.split('.');

  const capitalizeLabel = (str) => {
    if (!str || !str.trim()) return '';

    const acronyms = [
      'API',
      'URL',
      'ID',
      'HTTP',
      'HTTPS',
      'JSON',
      'XML',
      'CSV',
      'PDF',
      'HTML',
      'CSS',
      'JS',
    ];
    const upperStr = str.toUpperCase();

    if (acronyms.includes(upperStr)) {
      return upperStr;
    }

    let withSpaces = str.replace(/([a-z])([A-Z])/g, '$1 $2');
    withSpaces = withSpaces.replace(/_/g, ' ');
    withSpaces = withSpaces.replace(/-/g, ' ');

    return withSpaces
      .split(' ')
      .filter((word) => word.length > 0)
      .map((word) => {
        const upperWord = word.toUpperCase();
        if (acronyms.includes(upperWord)) {
          return upperWord;
        }
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
      })
      .join(' ');
  };

  const titleMatches = (titleValue, label) => {
    if (!titleValue || !label) return false;
    return titleValue.toLowerCase().trim() === label.toLowerCase().trim();
  };

  const actionLabels = {
    index: null,
    create: 'Create',
    edit: 'Edit',
    show: null,
    store: 'Create',
    update: 'Edit',
    destroy: null,
  };

  if (routeParts.length === 1) {
    if (title && title.trim() && !titleMatches(title, appName)) {
      breadcrumbs.push({ label: title });
    }
    return breadcrumbs;
  }

  if (routeParts.length >= 3) {
    const resourceParts = routeParts.slice(0, -1);
    const action = routeParts[routeParts.length - 1];

    resourceParts.forEach((part, index) => {
      const partLabel = capitalizeLabel(part);
      const parentRoute = `${resourceParts.slice(0, index + 1).join('.')}.index`;

      if (route().has(parentRoute)) {
        breadcrumbs.push({
          label: partLabel,
          href: route(parentRoute),
        });
      } else {
        breadcrumbs.push({ label: partLabel });
      }
    });

    if (action === 'index') {
      return breadcrumbs;
    }

    const lastResourceLabel = capitalizeLabel(resourceParts[resourceParts.length - 1]);
    const actionLabel = actionLabels[action];
    if (actionLabel && action !== 'show') {
      breadcrumbs.push({ label: actionLabel });
    } else if (
      title &&
      title.trim() &&
      !titleMatches(title, appName) &&
      !titleMatches(title, lastResourceLabel)
    ) {
      breadcrumbs.push({ label: title });
    } else if (!actionLabel && action !== 'index') {
      breadcrumbs.push({ label: capitalizeLabel(action) });
    }

    return breadcrumbs;
  }

  const resourceName = routeParts[0];
  const action = routeParts[1];
  const actionLabel = actionLabels[action];
  const resourceLabel = capitalizeLabel(resourceName);
  const resourceIndexRoute = `${resourceName}.index`;
  const hasIndexRoute = route().has(resourceIndexRoute);

  if (hasIndexRoute && action !== 'index') {
    breadcrumbs.push({
      label: resourceLabel,
      href: route(resourceIndexRoute),
    });
  } else if (action === 'index') {
    breadcrumbs.push({ label: resourceLabel });
    return breadcrumbs;
  }

  if (actionLabel && action !== 'show') {
    breadcrumbs.push({ label: actionLabel });
  } else if (
    title &&
    title.trim() &&
    !titleMatches(title, resourceLabel) &&
    !titleMatches(title, appName)
  ) {
    breadcrumbs.push({ label: title });
  } else if (!actionLabel && action !== 'index') {
    breadcrumbs.push({ label: capitalizeLabel(action) });
  }

  return breadcrumbs;
}

export default function PageShell({
  title,
  breadcrumbs = null,
  actions = null,
  children,
  className,
}) {
  const { app_name } = usePage().props;
  const appName = app_name || 'Tool Dock';
  const finalBreadcrumbs = breadcrumbs || generateBreadcrumbsFromRoute(title, appName);

  return (
    <>
      {title && <Head title={title} />}
      <div className={cn('space-y-6', className)}>
        {(title || finalBreadcrumbs.length > 0 || actions) && (
          <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div className="space-y-2">
              {title && <h1 className="text-3xl font-bold tracking-tight">{title}</h1>}
              {finalBreadcrumbs.length > 0 && (
                <Breadcrumb>
                  <BreadcrumbList>
                    {finalBreadcrumbs.map((crumb, index) => {
                      const isLast = index === finalBreadcrumbs.length - 1;
                      return (
                        <React.Fragment key={index}>
                          <BreadcrumbItem>
                            {isLast ? (
                              <BreadcrumbPage>{crumb.label}</BreadcrumbPage>
                            ) : crumb.href ? (
                              <BreadcrumbLink asChild>
                                <Link href={crumb.href}>{crumb.label}</Link>
                              </BreadcrumbLink>
                            ) : (
                              <span>{crumb.label}</span>
                            )}
                          </BreadcrumbItem>
                          {!isLast && <BreadcrumbSeparator />}
                        </React.Fragment>
                      );
                    })}
                  </BreadcrumbList>
                </Breadcrumb>
              )}
            </div>
            {actions && <div className="flex items-center gap-2">{actions}</div>}
          </div>
        )}

        {children}
      </div>
    </>
  );
}
