/**
 * Page shell component providing consistent page layout structure
 * Includes page title, breadcrumbs, action buttons area, and content area
 */
import { cn } from '@/Utils/utils';
import { Head } from '@inertiajs/react';

import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';

export default function PageShell({
  title,
  breadcrumbs = [],
  actions = null,
  children,
  className,
}) {
  return (
    <>
      {title && <Head title={title} />}
      <div className={cn('space-y-6', className)}>
        {(title || breadcrumbs.length > 0 || actions) && (
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="space-y-2">
              {title && <h1 className="text-3xl font-bold tracking-tight">{title}</h1>}
              {breadcrumbs.length > 0 && (
                <Breadcrumb>
                  <BreadcrumbList>
                    {breadcrumbs.map((crumb, index) => {
                      const isLast = index === breadcrumbs.length - 1;
                      return (
                        <div key={index} className="flex items-center">
                          <BreadcrumbItem>
                            {isLast ? (
                              <BreadcrumbPage>{crumb.label}</BreadcrumbPage>
                            ) : crumb.href ? (
                              <BreadcrumbLink href={crumb.href}>{crumb.label}</BreadcrumbLink>
                            ) : (
                              <span>{crumb.label}</span>
                            )}
                          </BreadcrumbItem>
                          {!isLast && <BreadcrumbSeparator />}
                        </div>
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
