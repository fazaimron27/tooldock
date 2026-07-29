import { router, usePage } from '@inertiajs/react';
import { ArrowRight, Link2, MessageCircle, Shield } from 'lucide-react';

import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Separator } from '@/Components/ui/separator';

const PLATFORM_CONFIG = {
  discord: {
    label: 'Discord',
    icon: MessageCircle,
    accent: 'hsl(235 86% 65%)',
    accentMuted: 'hsl(235 86% 65% / 0.12)',
  },
  telegram: {
    label: 'Telegram',
    icon: MessageCircle,
    accent: 'hsl(200 82% 53%)',
    accentMuted: 'hsl(200 82% 53% / 0.12)',
  },
};

function Avatar({ initial, style }) {
  return (
    <div
      className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white"
      style={style}
    >
      {initial}
    </div>
  );
}

export default function Connect({ platform, platformUsername, connectUrl }) {
  const { auth } = usePage().props;

  const config = PLATFORM_CONFIG[platform.driver] ?? {
    label: platform.driver,
    icon: MessageCircle,
    accent: 'hsl(var(--primary))',
    accentMuted: 'hsl(var(--primary) / 0.1)',
  };

  const PlatformIcon = config.icon;

  function handleConnect() {
    router.post(connectUrl);
  }

  function handleCancel() {
    router.visit(route('bot.index'));
  }

  return (
    <PageShell title={`Connect ${config.label}`}>
      <div className="flex justify-center">
        <div className="w-full max-w-sm overflow-hidden rounded-xl border bg-card shadow-xs">
          {/* Platform accent stripe */}
          <div className="h-1 w-full" style={{ background: config.accent }} />

          <div className="space-y-5 p-6">
            {/* Header */}
            <div className="space-y-1">
              <div className="flex items-center gap-2">
                <div
                  className="flex h-8 w-8 items-center justify-center rounded-md"
                  style={{ background: config.accentMuted }}
                >
                  <PlatformIcon className="h-4 w-4" style={{ color: config.accent }} />
                </div>
                <h2 className="text-base font-semibold text-card-foreground">
                  Connect {config.label}
                </h2>
              </div>
              <p className="pl-10 text-xs text-muted-foreground">
                Link your {config.label} identity to your Tool Dock account.
              </p>
            </div>

            <Separator />

            {/* Connection flow */}
            <div className="space-y-3">
              {/* Platform user */}
              <div className="flex items-center gap-3">
                <Avatar
                  initial={platformUsername?.[0]?.toUpperCase() ?? '?'}
                  style={{ background: config.accent }}
                />
                <div className="min-w-0">
                  <p className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">
                    {config.label}
                  </p>
                  <p className="truncate text-sm font-medium text-foreground">{platformUsername}</p>
                </div>
              </div>

              {/* Connector */}
              <div className="flex items-center gap-2 pl-1">
                <div className="flex flex-col items-center">
                  <div className="h-3 w-px bg-border" />
                  <Link2 className="h-3.5 w-3.5 text-muted-foreground" />
                  <div className="h-3 w-px bg-border" />
                </div>
                <span className="ml-1 text-[10px] text-muted-foreground">will be linked to</span>
              </div>

              {/* Tool Dock user */}
              <div className="flex items-center gap-3">
                <Avatar
                  initial={auth?.user?.name?.[0]?.toUpperCase() ?? '?'}
                  style={{ background: 'hsl(var(--primary))' }}
                />
                <div className="min-w-0">
                  <p className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">
                    Tool Dock
                  </p>
                  <p className="truncate text-sm font-medium text-foreground">{auth?.user?.name}</p>
                  <p className="truncate text-[10px] text-muted-foreground">{auth?.user?.email}</p>
                </div>
              </div>
            </div>

            <Separator />

            {/* Privacy note */}
            <div className="flex items-start gap-2">
              <Shield className="mt-0.5 h-3.5 w-3.5 shrink-0 text-muted-foreground" />
              <p className="text-[11px] leading-relaxed text-muted-foreground">
                This bot only accesses data belonging to your account. You can disconnect at any
                time from Bot settings.
              </p>
            </div>

            {/* Actions */}
            <div className="flex gap-2 pt-1">
              <Button variant="outline" size="sm" className="flex-1" onClick={handleCancel}>
                Cancel
              </Button>
              <Button size="sm" className="flex-1" onClick={handleConnect}>
                Authorize
                <ArrowRight className="ml-1.5 h-3.5 w-3.5" />
              </Button>
            </div>
          </div>
        </div>
      </div>
    </PageShell>
  );
}
