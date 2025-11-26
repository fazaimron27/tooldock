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

/**
 * Layout wrapper component for pages with title, breadcrumbs, and action buttons
 * @param {object} props
 * @param {string} props.title - Page title (also used for Head title)
 * @param {array} props.breadcrumbs - Breadcrumb items: [{ label, href? }]
 * @param {React.ReactNode} props.actions - Action buttons area (right-aligned)
 * @param {React.ReactNode} props.children - Page content
 * @param {string} props.className - Additional CSS classes
 */
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
        {/* Header Section */}
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

        {/* Content */}
        {children}
      </div>
    </>
  );
}
