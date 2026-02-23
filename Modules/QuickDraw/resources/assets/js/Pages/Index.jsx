/**
 * QuickDraw Index Page
 *
 * Lists all saved whiteboard canvases with create and delete functionality.
 * Uses FormDialog for canvas creation and ConfirmDialog for deletion.
 */
import { router, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { PenTool, Plus, Trash2 } from 'lucide-react';
import { useCallback, useState } from 'react';
import { useForm } from 'react-hook-form';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import FormDialog from '@/Components/Common/FormDialog';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

const CREATE_FORM_ID = 'quickdraw-create-form';

export default function Index() {
  const { quickdraws } = usePage().props;
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { control, handleSubmit, reset } = useForm({
    defaultValues: { name: '', description: '' },
  });

  const openCreate = useCallback(() => {
    reset({ name: '', description: '' });
    setIsCreateOpen(true);
  }, [reset]);

  const onSubmit = useCallback(
    (data) => {
      setIsSubmitting(true);
      router.post(route('quickdraw.store'), data, {
        onSuccess: () => {
          setIsCreateOpen(false);
          reset({ name: '', description: '' });
        },
        onFinish: () => setIsSubmitting(false),
      });
    },
    [reset]
  );

  const handleDelete = useCallback(() => {
    if (deleteTarget) {
      router.delete(route('quickdraw.destroy', deleteTarget.id), {
        onSuccess: () => setDeleteTarget(null),
      });
    }
  }, [deleteTarget]);

  return (
    <PageShell
      title="QuickDraw"
      description="Infinite whiteboard for brainstorming and sketching."
      actions={
        <Button onClick={openCreate}>
          <Plus className="mr-2 h-4 w-4" />
          New Canvas
        </Button>
      }
    >
      <div className="space-y-6">
        {quickdraws.length > 0 ? (
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {quickdraws.map((canvas) => (
              <Card
                key={canvas.id}
                className="group cursor-pointer transition-all hover:shadow-md hover:border-primary/30"
                onClick={() => router.visit(route('quickdraw.show', canvas.id))}
              >
                <CardHeader className="pb-3">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center gap-2 min-w-0">
                      <PenTool className="h-4 w-4 shrink-0 text-primary" />
                      <CardTitle className="text-base truncate">{canvas.name}</CardTitle>
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-7 w-7 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-destructive"
                      onClick={(e) => {
                        e.stopPropagation();
                        setDeleteTarget(canvas);
                      }}
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                  </div>
                  {canvas.description && (
                    <CardDescription className="line-clamp-2 text-xs">
                      {canvas.description}
                    </CardDescription>
                  )}
                </CardHeader>
                <CardContent className="pt-0">
                  <p className="text-xs text-muted-foreground">
                    {canvas.updated_at
                      ? `Edited ${format(new Date(canvas.updated_at), 'MMM d, yyyy')}`
                      : `Created ${format(new Date(canvas.created_at), 'MMM d, yyyy')}`}
                  </p>
                </CardContent>
              </Card>
            ))}
          </div>
        ) : (
          <Card>
            <CardContent className="py-12">
              <div className="flex flex-col items-center justify-center text-center">
                <PenTool className="w-10 h-10 mx-auto text-muted-foreground mb-2" />
                <p className="text-muted-foreground mb-3">
                  Create your first whiteboard canvas to start sketching.
                </p>
              </div>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Create Canvas Dialog */}
      <FormDialog
        open={isCreateOpen}
        onOpenChange={(open) => !open && setIsCreateOpen(false)}
        onCancel={() => {
          setIsCreateOpen(false);
          reset({ name: '', description: '' });
        }}
        title="New Canvas"
        description="Create a new whiteboard canvas for brainstorming and sketching."
        formId={CREATE_FORM_ID}
        confirmLabel="Create Canvas"
        processing={isSubmitting}
        processingLabel="Creating..."
      >
        <form id={CREATE_FORM_ID} onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <FormFieldRHF
            name="name"
            control={control}
            label="Name"
            required
            placeholder="e.g. Architecture Diagram"
            rules={{ required: 'Canvas name is required' }}
          />
          <FormFieldRHF
            name="description"
            control={control}
            label="Description"
            placeholder="Optional description..."
            multiline
          />
        </form>
      </FormDialog>

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        isOpen={!!deleteTarget}
        onCancel={() => setDeleteTarget(null)}
        title="Delete Canvas"
        message={`Are you sure you want to delete "${deleteTarget?.name}"? This action cannot be undone.`}
        onConfirm={handleDelete}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
