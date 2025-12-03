/**
 * Module Card component for displaying module information and actions
 * Shows install/uninstall buttons, toggle switch, and module metadata
 */
import { useDisclosure } from '@/Hooks/useDisclosure';
import { useNavigationLoading } from '@/Hooks/useNavigationLoading';
import { useSmartForm } from '@/Hooks/useSmartForm';
import { getIcon } from '@/Utils/iconResolver';
import { Link, router } from '@inertiajs/react';
import { Tag } from 'lucide-react';
import { useCallback, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Switch } from '@/Components/ui/switch';

/**
 * Get module route URL - tries Ziggy first, falls back to manual construction
 * @param {string} moduleName - The module name (e.g., "Blog")
 * @returns {string} The route URL (e.g., "/tooldock/blog" or route('blog.index'))
 */
const getModuleRouteUrl = (moduleName) => {
  const moduleNameLower = moduleName.toLowerCase();
  const routeName = `${moduleNameLower}.index`;

  if (route().has(routeName)) {
    return route(routeName);
  }

  // All module routes use the /tooldock prefix
  return `/tooldock/${moduleNameLower}`;
};

export default function ModuleCard({ module, onKeywordClick }) {
  const uninstallDialog = useDisclosure();
  const successDialog = useDisclosure();
  const { showLoading } = useNavigationLoading();
  const [isToggling, setIsToggling] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [moduleRouteUrl, setModuleRouteUrl] = useState(null);
  const [isNavigating, setIsNavigating] = useState(false);

  const installForm = useSmartForm({ module: module.name });
  const uninstallForm = useSmartForm({ module: module.name });

  const handleInstall = useCallback(() => {
    installForm.post(route('core.modules.install'), {
      preserveScroll: true,
      silent: true,
      onSuccess: (page) => {
        const hasFormErrors = installForm.errors && Object.keys(installForm.errors).length > 0;
        const hasFlashError = page?.props?.flash?.error;

        if (!hasFormErrors && !hasFlashError) {
          const routeUrl = page?.props?.flash?.module_route_url || getModuleRouteUrl(module.name);
          setModuleRouteUrl(routeUrl);
          setSuccessMessage(
            `Module "${module.name}" installed successfully. Go to module or reload later? Note: You'll need to reload to see all changes.`
          );
          successDialog.onOpen();
        } else {
          successDialog.onClose();
        }
      },
      onError: () => {
        successDialog.onClose();
      },
    });
  }, [installForm, module.name, successDialog]);

  const handleUninstallClick = useCallback(() => {
    uninstallDialog.onOpen();
  }, [uninstallDialog]);

  const handleUninstallConfirm = useCallback(() => {
    uninstallForm.post(route('core.modules.uninstall'), {
      preserveScroll: true,
      silent: true,
      onSuccess: (page) => {
        const hasFormErrors = uninstallForm.errors && Object.keys(uninstallForm.errors).length > 0;
        const hasFlashError = page?.props?.flash?.error;

        if (!hasFormErrors && !hasFlashError) {
          uninstallDialog.onClose();
          setModuleRouteUrl(null);
          setSuccessMessage(
            `Module "${module.name}" uninstalled successfully. Reload page now or later? Note: You'll need to reload to see all changes.`
          );
          successDialog.onOpen();
        } else {
          successDialog.onClose();
        }
      },
      onError: () => {
        successDialog.onClose();
      },
    });
  }, [uninstallForm, uninstallDialog, module.name, successDialog]);

  const handleToggle = useCallback(
    (checked) => {
      setIsToggling(true);
      const action = checked ? 'enable' : 'disable';

      router.post(
        route('core.modules.toggle'),
        {
          module: module.name,
          action,
        },
        {
          preserveScroll: true,
          onSuccess: (page) => {
            const hasFlashError = page?.props?.flash?.error;

            if (!hasFlashError) {
              const actionText = checked ? 'enabled' : 'disabled';

              if (checked) {
                const routeUrl =
                  page?.props?.flash?.module_route_url || getModuleRouteUrl(module.name);
                setModuleRouteUrl(routeUrl);
                setSuccessMessage(
                  `Module "${module.name}" ${actionText} successfully. Go to module or reload later? Note: You'll need to reload to see all changes.`
                );
              } else {
                setModuleRouteUrl(null);
                setSuccessMessage(
                  `Module "${module.name}" ${actionText} successfully. Reload page now or later? Note: You'll need to reload to see all changes.`
                );
              }
              successDialog.onOpen();
            } else {
              successDialog.onClose();
            }
          },
          onError: () => {
            successDialog.onClose();
          },
          onFinish: () => {
            setIsToggling(false);
          },
        }
      );
    },
    [module.name, successDialog]
  );

  const handleReloadPage = useCallback(() => {
    setIsNavigating(true);
    showLoading();

    if (moduleRouteUrl) {
      // Always use full page reload for newly installed/enabled modules
      // This ensures:
      // 1. Service providers boot and register routes
      // 2. Ziggy routes are reloaded in the frontend
      // 3. The frontend route() helper has updated routes
      window.location.href = moduleRouteUrl;
    } else {
      // Persist loading state to sessionStorage before reload for immediate display
      showLoading();
      window.setTimeout(() => {
        window.location.reload();
      }, 0);
    }
  }, [moduleRouteUrl, showLoading]);

  const Icon = getIcon(module.icon);
  const isProcessing = installForm.processing || uninstallForm.processing || isToggling;

  return (
    <>
      <div className="relative w-full">
        <Card className="group flex h-full w-full flex-col transition-all duration-200 hover:shadow-lg">
          <CardHeader className="pb-4">
            <div className="flex items-start gap-4">
              <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary/20 to-primary/10 ring-1 ring-primary/20 transition-transform group-hover:scale-105">
                <Icon className="h-7 w-7 text-primary" />
              </div>
              <div className="flex-1 min-w-0 space-y-2">
                <div className="flex items-start justify-between gap-2 min-w-0">
                  <CardTitle className="text-xl font-semibold leading-tight truncate min-w-0">
                    {module.name}
                  </CardTitle>
                  <div className="flex gap-1.5 shrink-0">
                    {module.is_active && (
                      <Badge variant="default" className="text-xs whitespace-nowrap">
                        Active
                      </Badge>
                    )}
                    {module.protected && (
                      <Badge variant="outline" className="text-xs whitespace-nowrap">
                        Protected
                      </Badge>
                    )}
                  </div>
                </div>
                <CardDescription className="line-clamp-2 min-h-[2.75rem] text-sm leading-relaxed">
                  {module.description || 'No description available'}
                </CardDescription>
              </div>
            </div>
          </CardHeader>

          <CardContent className="flex-1 space-y-4 pb-4">
            {module.keywords && module.keywords.length > 0 && (
              <div className="flex min-h-[28px] flex-wrap gap-1.5">
                {module.keywords.map((keyword) => (
                  <Badge
                    key={keyword}
                    variant="outline"
                    className="cursor-pointer text-xs transition-all hover:bg-primary/10 hover:text-primary hover:shadow-sm"
                    onClick={(e) => {
                      e.stopPropagation();
                      if (onKeywordClick) {
                        onKeywordClick(keyword.toLowerCase());
                      }
                    }}
                  >
                    <Tag className="mr-1 h-2.5 w-2.5" />
                    {keyword}
                  </Badge>
                ))}
              </div>
            )}

            <div className="rounded-lg border bg-muted/30 p-3 space-y-2">
              <div className="grid grid-cols-1 gap-2 text-sm">
                <div className="flex items-center justify-between">
                  <span className="font-medium text-muted-foreground">Version</span>
                  <span className="text-foreground">{module.version}</span>
                </div>
                {module.author && (
                  <div className="flex items-center justify-between">
                    <span className="font-medium text-muted-foreground">Author</span>
                    <span className="text-foreground">{module.author}</span>
                  </div>
                )}
                {module.requires && module.requires.length > 0 && (
                  <div className="flex items-start justify-between gap-2">
                    <span className="font-medium text-muted-foreground">Requires</span>
                    <span className="text-right text-foreground">{module.requires.join(', ')}</span>
                  </div>
                )}
              </div>
            </div>

            {!module.is_installed && (
              <Badge variant="secondary" className="w-fit">
                Not Installed
              </Badge>
            )}
          </CardContent>

          <CardFooter className="flex flex-col gap-3 border-t pt-4">
            {!module.is_installed ? (
              <Button onClick={handleInstall} disabled={installForm.processing} className="w-full">
                {installForm.processing ? (
                  <>
                    <Spinner className="size-4" />
                    Installing...
                  </>
                ) : (
                  'Install'
                )}
              </Button>
            ) : (
              <>
                <div className="flex w-full items-center justify-between">
                  <Label htmlFor={`toggle-${module.name}`} className="text-sm">
                    Enable Module
                  </Label>
                  <Switch
                    id={`toggle-${module.name}`}
                    checked={module.is_active}
                    onCheckedChange={handleToggle}
                    disabled={isToggling || module.protected}
                  />
                </div>
                {!module.protected && (
                  <Button
                    variant="destructive"
                    onClick={handleUninstallClick}
                    disabled={uninstallForm.processing}
                    className="w-full"
                  >
                    {uninstallForm.processing ? (
                      <>
                        <Spinner className="size-4" />
                        Uninstalling...
                      </>
                    ) : (
                      'Uninstall'
                    )}
                  </Button>
                )}
              </>
            )}
          </CardFooter>
        </Card>

        {isProcessing && (
          <div className="absolute inset-0 z-50 flex items-center justify-center rounded-xl bg-background/80 backdrop-blur-sm">
            <div className="flex flex-col items-center gap-2">
              <Spinner className="size-8" />
              <p className="text-xs text-muted-foreground">
                {installForm.processing && 'Installing...'}
                {uninstallForm.processing && 'Uninstalling...'}
                {isToggling && (module.is_active ? 'Disabling...' : 'Enabling...')}
              </p>
            </div>
          </div>
        )}
      </div>

      <ConfirmDialog
        isOpen={uninstallDialog.isOpen}
        onConfirm={handleUninstallConfirm}
        onCancel={() => {
          uninstallDialog.onClose();
        }}
        title="Uninstall Module"
        message={
          module
            ? `Are you sure you want to uninstall "${module.name}"? This will disable the module and roll back its database migrations. This action cannot be undone.`
            : 'Are you sure you want to uninstall this module?'
        }
        confirmLabel="Uninstall"
        cancelLabel="Cancel"
        variant="destructive"
      />

      <ConfirmDialog
        isOpen={successDialog.isOpen}
        onConfirm={handleReloadPage}
        onCancel={() => {
          if (!isNavigating) {
            successDialog.onClose();
            setModuleRouteUrl(null);
            setIsNavigating(false);
          }
        }}
        title="Module Operation Successful"
        message={successMessage}
        confirmLabel={isNavigating ? 'Loading...' : moduleRouteUrl ? 'Go to Module' : 'Reload Page'}
        cancelLabel="I'll reload later"
        variant="default"
        disabled={isNavigating}
      />
    </>
  );
}
