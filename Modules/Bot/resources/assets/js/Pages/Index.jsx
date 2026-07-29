/**
 * Bot Module Index Page
 *
 * Lists all configured bot platform integrations for the current user.
 * Allows creating, editing, deleting, and testing each platform.
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { createBotPlatformResolver, updateBotPlatformResolver } from '@Bot/Schemas/botSchemas';
import { usePage } from '@inertiajs/react';
import { Bot, Check, Copy, MessageCircle, Pencil, Plus, Trash2, Wifi } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Controller } from 'react-hook-form';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import FormDialog from '@/Components/Common/FormDialog';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

const CREATE_FORM_ID = 'bot-platform-create-form';
const EDIT_FORM_ID = 'bot-platform-edit-form';

export default function Index() {
  const { platforms: initialPlatforms = [], driverOptions = [] } = usePage().props;

  const [platforms, setPlatforms] = useState(initialPlatforms);
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [editTarget, setEditTarget] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [testingId, setTestingId] = useState(null);
  const [testResults, setTestResults] = useState({});
  const [copiedId, setCopiedId] = useState(null);

  useEffect(() => {
    setPlatforms(initialPlatforms || []);
  }, [initialPlatforms]);

  // ── Create form ──────────────────────────────────────────────────────────
  const form = useInertiaForm(
    {
      driver: driverOptions[0]?.value ?? 'telegram',
      name: '',
      credentials: {},
      is_active: true,
    },
    {
      componentLevel: true,
      toast: { success: 'Integration created.' },
      resolver: createBotPlatformResolver,
    }
  );

  // ── Edit form ────────────────────────────────────────────────────────────
  const editForm = useInertiaForm(
    { name: '', credentials: {}, is_active: true },
    {
      componentLevel: true,
      toast: { success: 'Integration updated.' },
      resolver: updateBotPlatformResolver,
    }
  );

  // ── Delete form ───────────────────────────────────────────────────────────
  const deleteForm = useInertiaForm(
    {},
    {
      componentLevel: true,
      toast: { success: 'Integration deleted.' },
    }
  );

  const watchedDriver = form.watch('driver');
  const selectedOption = driverOptions.find((o) => o.value === watchedDriver) ?? driverOptions[0];

  // The driver of the platform being edited (for showing correct credential fields)
  const editDriverOption = driverOptions.find((o) => o.value === editTarget?.driver) ?? null;

  // ── Handlers ─────────────────────────────────────────────────────────────
  const onSubmit = form.handleSubmit((data) => {
    form.post(route('bot.platform.store'), {
      data,
      onSuccess: () => {
        setIsCreateOpen(false);
        form.reset({
          driver: driverOptions[0]?.value ?? 'telegram',
          name: '',
          credentials: {},
          is_active: true,
        });
      },
    });
  });

  const openEdit = useCallback(
    (platform) => {
      setEditTarget(platform);
      editForm.reset({ name: platform.name, credentials: {}, is_active: platform.is_active });
    },
    [editForm]
  );

  const onEditSubmit = editForm.handleSubmit((data) => {
    editForm.put(route('bot.platform.update', editTarget.id), {
      data,
      onSuccess: () => {
        setEditTarget(null);
        editForm.reset({ name: '', credentials: {}, is_active: true });
      },
    });
  });

  const handleDelete = useCallback(() => {
    if (!deleteTarget) return;
    deleteForm.delete(route('bot.platform.destroy', deleteTarget.id), {
      onSuccess: () => setDeleteTarget(null),
    });
  }, [deleteTarget, deleteForm]);

  const handleTest = useCallback(async (platform) => {
    setTestingId(platform.id);
    try {
      const response = await fetch(route('bot.platform.test', platform.id), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
      });
      const result = await response.json();
      setTestResults((prev) => ({ ...prev, [platform.id]: result }));
    } finally {
      setTestingId(null);
    }
  }, []);

  const handleCopyWebhook = useCallback((platform) => {
    if (!platform.webhook_url) return;
    navigator.clipboard.writeText(platform.webhook_url);
    setCopiedId(platform.id);
    setTimeout(() => setCopiedId(null), 2000);
  }, []);

  return (
    <PageShell
      title="Bot"
      description="Manage multi-platform bot integrations."
      actions={
        <Button onClick={() => setIsCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Add Integration
        </Button>
      }
    >
      {platforms.length === 0 ? (
        <Card>
          <CardContent className="py-16">
            <div className="flex flex-col items-center justify-center text-center gap-3">
              <Bot className="h-12 w-12 text-muted-foreground" />
              <p className="text-muted-foreground">
                No bot integrations yet. Add Telegram or Discord to get started.
              </p>
              <Button variant="outline" onClick={() => setIsCreateOpen(true)}>
                <Plus className="mr-2 h-4 w-4" />
                Add Integration
              </Button>
            </div>
          </CardContent>
        </Card>
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {platforms.map((platform) => {
            const testResult = testResults[platform.id];
            return (
              <Card key={platform.id}>
                <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-2">
                  <div className="flex items-center gap-2">
                    <MessageCircle className="h-5 w-5 text-muted-foreground" />
                    <CardTitle className="text-sm font-medium">{platform.name}</CardTitle>
                  </div>
                  <Badge variant={platform.is_active ? 'default' : 'secondary'}>
                    {platform.driver_label}
                  </Badge>
                </CardHeader>
                <CardContent className="space-y-3">
                  {platform.tested_at && (
                    <p className="text-xs text-muted-foreground">
                      Last tested: {new Date(platform.tested_at).toLocaleString()}
                    </p>
                  )}
                  {testResult && (
                    <p
                      className={`text-xs ${testResult.ok ? 'text-green-600' : 'text-destructive'}`}
                    >
                      {testResult.message}
                    </p>
                  )}
                  {platform.webhook_url && (
                    <div className="flex items-center gap-1.5 rounded bg-muted px-2 py-1">
                      <span className="flex-1 truncate font-mono text-[10px] text-muted-foreground">
                        {platform.webhook_url}
                      </span>
                      <button
                        type="button"
                        title="Copy webhook URL"
                        onClick={() => handleCopyWebhook(platform)}
                        className="shrink-0 text-muted-foreground hover:text-foreground transition-colors"
                      >
                        {copiedId === platform.id ? (
                          <Check className="h-3 w-3 text-green-500" />
                        ) : (
                          <Copy className="h-3 w-3" />
                        )}
                      </button>
                    </div>
                  )}
                  {/* Connected account indicator — one connection per platform */}
                  {platform.connections?.length > 0 ? (
                    <div className="rounded-md border border-green-500/20 bg-green-500/5 px-3 py-2 flex items-center gap-2">
                      <span className="inline-block w-1.5 h-1.5 rounded-full bg-green-500 shrink-0" />
                      <p className="text-[10px] text-muted-foreground truncate">
                        <span className="font-medium text-green-600 uppercase tracking-wider">
                          Connected
                        </span>
                        {' as '}
                        <span className="font-medium text-foreground">
                          {platform.connections[0].platform_username}
                        </span>
                      </p>
                    </div>
                  ) : (
                    <div className="rounded-md border border-dashed border-muted-foreground/30 px-3 py-2">
                      <p className="text-[10px] text-muted-foreground">
                        Not connected — open the bot and send{' '}
                        <code className="rounded bg-muted px-1">/start</code> to link an account.
                      </p>
                    </div>
                  )}
                  <div className="flex gap-2 pt-1">
                    <Button
                      variant="outline"
                      size="sm"
                      className="flex-1"
                      disabled={testingId === platform.id}
                      onClick={() => handleTest(platform)}
                    >
                      <Wifi className="mr-1.5 h-3.5 w-3.5" />
                      {testingId === platform.id ? 'Testing...' : 'Test'}
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => openEdit(platform)}>
                      <Pencil className="h-3.5 w-3.5" />
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => setDeleteTarget(platform)}>
                      <Trash2 className="h-3.5 w-3.5 text-destructive" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}

      {/* Create Dialog */}
      <FormDialog
        open={isCreateOpen}
        onOpenChange={(open) => !open && setIsCreateOpen(false)}
        onCancel={() => {
          setIsCreateOpen(false);
          form.reset({
            driver: driverOptions[0]?.value ?? 'telegram',
            name: '',
            credentials: {},
            is_active: true,
          });
        }}
        title="Add Bot Integration"
        description="Connect a Telegram bot or Discord webhook to Tool Dock."
        formId={CREATE_FORM_ID}
        confirmLabel="Save Integration"
        processing={form.formState.isSubmitting}
        processingLabel="Saving..."
      >
        <form id={CREATE_FORM_ID} onSubmit={onSubmit} className="space-y-4">
          {/* Driver */}
          <div className="space-y-2">
            <Label>Platform</Label>
            <Controller
              name="driver"
              control={form.control}
              render={({ field }) => (
                <Select
                  value={field.value}
                  onValueChange={(v) => {
                    field.onChange(v);
                    form.setValue('credentials', {});
                  }}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select platform" />
                  </SelectTrigger>
                  <SelectContent>
                    {driverOptions.map((o) => (
                      <SelectItem key={o.value} value={o.value}>
                        {o.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
          </div>

          {/* Name */}
          <FormFieldRHF
            name="name"
            control={form.control}
            label="Name"
            required
            placeholder="e.g. My Telegram Bot"
          />

          {/* Dynamic credential fields */}
          {selectedOption?.fields?.map((field) => (
            <div key={field.key} className="space-y-2">
              <Label>
                {field.label} <span className="text-destructive">*</span>
              </Label>
              <Controller
                name={`credentials.${field.key}`}
                control={form.control}
                render={({ field: f }) => (
                  <Input
                    type={field.type}
                    placeholder={field.placeholder}
                    value={f.value ?? ''}
                    onChange={f.onChange}
                  />
                )}
              />
            </div>
          ))}

          {/* Active toggle */}
          <div className="flex items-center gap-3">
            <Controller
              name="is_active"
              control={form.control}
              render={({ field }) => (
                <Switch checked={field.value} onCheckedChange={field.onChange} />
              )}
            />
            <Label>Active</Label>
          </div>
        </form>
      </FormDialog>

      {/* Edit Dialog */}
      <FormDialog
        open={!!editTarget}
        onOpenChange={(open) => !open && setEditTarget(null)}
        onCancel={() => {
          setEditTarget(null);
          editForm.reset({ name: '', credentials: {}, is_active: true });
        }}
        title="Edit Integration"
        description={`Update settings for ${editTarget?.name ?? 'this integration'}.`}
        formId={EDIT_FORM_ID}
        confirmLabel="Save Changes"
        processing={editForm.formState.isSubmitting}
        processingLabel="Saving..."
      >
        <form id={EDIT_FORM_ID} onSubmit={onEditSubmit} className="space-y-4">
          {/* Name */}
          <FormFieldRHF
            name="name"
            control={editForm.control}
            label="Name"
            required
            placeholder="e.g. My Telegram Bot"
          />

          {/* Credential fields (update only — leave blank to keep existing) */}
          {editDriverOption?.fields?.map((field) => (
            <div key={field.key} className="space-y-2">
              <Label>{field.label}</Label>
              <Controller
                name={`credentials.${field.key}`}
                control={editForm.control}
                render={({ field: f }) => (
                  <Input
                    type={field.type}
                    placeholder={`Leave blank to keep existing ${field.label.toLowerCase()}`}
                    value={f.value ?? ''}
                    onChange={f.onChange}
                  />
                )}
              />
            </div>
          ))}

          {/* Active toggle */}
          <div className="flex items-center gap-3">
            <Controller
              name="is_active"
              control={editForm.control}
              render={({ field }) => (
                <Switch checked={field.value} onCheckedChange={field.onChange} />
              )}
            />
            <Label>Active</Label>
          </div>
        </form>
      </FormDialog>

      {/* Delete Confirmation */}
      <ConfirmDialog
        isOpen={!!deleteTarget}
        onCancel={() => setDeleteTarget(null)}
        title="Delete Integration"
        message={`Are you sure you want to delete "${deleteTarget?.name}"? This cannot be undone.`}
        onConfirm={handleDelete}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
