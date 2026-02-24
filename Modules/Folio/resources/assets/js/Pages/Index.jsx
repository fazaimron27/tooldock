/**
 * Folio Index Page
 *
 * Lists all saved resumes with create, delete, and download actions.
 * Uses FormDialog for resume creation and ConfirmDialog for deletion.
 */
import { router, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { Download, FileUser, Plus, Trash2 } from 'lucide-react';
import { useCallback, useState } from 'react';
import { useForm } from 'react-hook-form';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import FormDialog from '@/Components/Common/FormDialog';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

const CREATE_FORM_ID = 'folio-create-form';

export default function Index() {
  const { folios } = usePage().props;
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { control, handleSubmit, reset } = useForm({
    defaultValues: { name: '' },
  });

  const openCreate = useCallback(() => {
    reset({ name: '' });
    setIsCreateOpen(true);
  }, [reset]);

  const onSubmit = useCallback(
    (data) => {
      setIsSubmitting(true);
      router.post(route('folio.store'), data, {
        onSuccess: () => {
          setIsCreateOpen(false);
          reset({ name: '' });
        },
        onFinish: () => setIsSubmitting(false),
      });
    },
    [reset]
  );

  const handleDelete = useCallback(() => {
    if (deleteTarget) {
      router.delete(route('folio.destroy', deleteTarget.id), {
        onSuccess: () => setDeleteTarget(null),
      });
    }
  }, [deleteTarget]);

  return (
    <PageShell
      title="Folio"
      description="Build and export professional resumes."
      actions={
        <Button onClick={openCreate}>
          <Plus className="mr-2 h-4 w-4" />
          New Resume
        </Button>
      }
    >
      <div className="space-y-6">
        {folios.length > 0 ? (
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {folios.map((folio) => (
              <Card
                key={folio.id}
                className="group cursor-pointer transition-all hover:shadow-md hover:border-primary/30"
                onClick={() => router.visit(route('folio.edit', folio.id))}
              >
                <CardHeader className="pb-3">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center gap-2 min-w-0">
                      <FileUser className="h-4 w-4 shrink-0 text-primary" />
                      <CardTitle className="text-base truncate">{folio.name}</CardTitle>
                    </div>
                    <div className="flex items-center gap-1">
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-7 w-7 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-destructive"
                        onClick={(e) => {
                          e.stopPropagation();
                          setDeleteTarget(folio);
                        }}
                      >
                        <Trash2 className="h-3.5 w-3.5" />
                      </Button>
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="pt-0">
                  <p className="text-xs text-muted-foreground">
                    {folio.updated_at
                      ? `Edited ${format(new Date(folio.updated_at), 'MMM d, yyyy')}`
                      : `Created ${format(new Date(folio.created_at), 'MMM d, yyyy')}`}
                  </p>
                </CardContent>
              </Card>
            ))}
          </div>
        ) : (
          <Card>
            <CardContent className="py-12">
              <div className="flex flex-col items-center justify-center text-center">
                <FileUser className="w-10 h-10 mx-auto text-muted-foreground mb-2" />
                <p className="text-muted-foreground mb-3">
                  Create your first resume to get started.
                </p>
              </div>
            </CardContent>
          </Card>
        )}
      </div>

      <FormDialog
        open={isCreateOpen}
        onOpenChange={(open) => !open && setIsCreateOpen(false)}
        onCancel={() => {
          setIsCreateOpen(false);
          reset({ name: '' });
        }}
        title="New Resume"
        description="Create a new resume to start building your professional profile."
        formId={CREATE_FORM_ID}
        confirmLabel="Create Resume"
        processing={isSubmitting}
        processingLabel="Creating..."
      >
        <form id={CREATE_FORM_ID} onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <FormFieldRHF
            name="name"
            control={control}
            label="Name"
            required
            placeholder="e.g. Software Engineer Resume"
            rules={{ required: 'Resume name is required' }}
          />
        </form>
      </FormDialog>

      <ConfirmDialog
        isOpen={!!deleteTarget}
        onCancel={() => setDeleteTarget(null)}
        title="Delete Resume"
        message={`Are you sure you want to delete "${deleteTarget?.name}"? This action cannot be undone.`}
        onConfirm={handleDelete}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
