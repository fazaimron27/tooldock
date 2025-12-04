/**
 * Create category page with form for creating new categories
 * Includes fields for name, type, parent, color, and description
 * Uses React Hook Form for improved performance and validation
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { cn } from '@/Utils/utils';
import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Controller } from 'react-hook-form';

import FormCard from '@/Components/Common/FormCard';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

import { createCategoryResolver } from '../Schemas/categorySchemas';

export default function Create({ parentCategories = {}, types = [] }) {
  const form = useInertiaForm(
    {
      name: '',
      slug: '',
      type: '',
      parent_id: '',
      color: '',
      description: '',
    },
    {
      resolver: createCategoryResolver,
      toast: {
        success: 'Category created successfully!',
        error: 'Failed to create category. Please check the form for errors.',
      },
    }
  );

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('categories.store'));
  };

  const [slugManuallyEdited, setSlugManuallyEdited] = useState(false);

  /**
   * Handles name field changes and auto-generates slug if not manually edited.
   */
  const handleNameChange = (e) => {
    const name = e.target.value;
    form.setValue('name', name);
    if (!slugManuallyEdited) {
      const slug = name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
      form.setValue('slug', slug, { shouldValidate: false });
    }
  };

  const handleSlugChange = (e) => {
    form.setValue('slug', e.target.value);
    setSlugManuallyEdited(true);
  };

  const type = form.watch('type');
  const availableParents = useMemo(() => {
    if (!type || !parentCategories[type]) {
      return [];
    }
    return parentCategories[type];
  }, [type, parentCategories]);

  return (
    <DashboardLayout header="Categories">
      <PageShell title="Create Category">
        <div className="space-y-6">
          <FormCard title="New Category" description="Create a new category" className="max-w-3xl">
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <Controller
                name="name"
                control={form.control}
                render={({ field, fieldState: { error } }) => (
                  <div className="space-y-2">
                    <Label htmlFor="name">
                      Name <span className="text-destructive">*</span>
                    </Label>
                    <input
                      id="name"
                      type="text"
                      {...field}
                      onChange={(e) => {
                        field.onChange(e);
                        handleNameChange(e);
                      }}
                      className={cn(
                        'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                        error && 'border-destructive'
                      )}
                      placeholder="Enter category name"
                      required
                    />
                    {error && <p className="text-sm text-destructive">{error.message}</p>}
                  </div>
                )}
              />

              <Controller
                name="slug"
                control={form.control}
                render={({ field, fieldState: { error } }) => (
                  <div className="space-y-2">
                    <Label htmlFor="slug">Slug</Label>
                    <input
                      id="slug"
                      type="text"
                      {...field}
                      onChange={(e) => {
                        field.onChange(e);
                        handleSlugChange(e);
                      }}
                      className={cn(
                        'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                        error && 'border-destructive'
                      )}
                      placeholder="Auto-generated from name"
                    />
                    {error && <p className="text-sm text-destructive">{error.message}</p>}
                  </div>
                )}
              />

              <Controller
                name="type"
                control={form.control}
                render={({ field, fieldState: { error } }) => (
                  <div className="space-y-2">
                    <Label htmlFor="type">
                      Type <span className="text-destructive">*</span>
                    </Label>
                    <div className="relative">
                      <input
                        id="type"
                        type="text"
                        list="type-options"
                        value={field.value}
                        onChange={(e) => {
                          const value = e.target.value.toLowerCase().replace(/[^a-z0-9_-]/g, '');
                          field.onChange(value);
                        }}
                        className={cn(
                          'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                          error && 'border-destructive'
                        )}
                        placeholder="Enter category type (e.g., product, finance, project)"
                        required
                        autoComplete="off"
                      />
                      <datalist id="type-options">
                        {types.map((type) => (
                          <option key={type} value={type}>
                            {type.charAt(0).toUpperCase() + type.slice(1)}
                          </option>
                        ))}
                      </datalist>
                    </div>
                    {error && <p className="text-sm text-destructive">{error.message}</p>}
                    <p className="text-xs text-muted-foreground">
                      Type lowercase letters, numbers, underscores, or hyphens. Suggestions appear
                      as you type.
                    </p>
                  </div>
                )}
              />

              <Controller
                name="parent_id"
                control={form.control}
                render={({ field, fieldState: { error } }) => {
                  const type = form.watch('type');
                  const disabled = !type || availableParents.length === 0;
                  return (
                    <div className="space-y-2">
                      <Label htmlFor="parent_id">Parent Category</Label>
                      <select
                        id="parent_id"
                        value={field.value}
                        onChange={(e) => field.onChange(e.target.value || '')}
                        disabled={disabled}
                        className={cn(
                          'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                          error && 'border-destructive'
                        )}
                      >
                        <option value="">None (Top-level category)</option>
                        {availableParents.map((parent) => (
                          <option key={parent.id} value={parent.id}>
                            {parent.name}
                          </option>
                        ))}
                      </select>
                      {error && <p className="text-sm text-destructive">{error.message}</p>}
                      {type && availableParents.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                          No parent categories available for this type.
                        </p>
                      )}
                    </div>
                  );
                }}
              />

              <Controller
                name="color"
                control={form.control}
                render={({ field, fieldState: { error } }) => (
                  <div className="space-y-2">
                    <Label htmlFor="color">Color</Label>
                    <div className="flex items-center gap-2">
                      <input
                        type="color"
                        id="color"
                        value={field.value || '#000000'}
                        onChange={(e) => field.onChange(e.target.value)}
                        className="h-10 w-20 rounded border border-input"
                      />
                      <input
                        type="text"
                        value={field.value || ''}
                        onChange={(e) => field.onChange(e.target.value)}
                        className={cn(
                          'flex-1 flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                          error && 'border-destructive'
                        )}
                        placeholder="#000000"
                      />
                    </div>
                    {error && <p className="text-sm text-destructive">{error.message}</p>}
                  </div>
                )}
              />

              <FormTextareaRHF
                name="description"
                control={form.control}
                label="Description"
                rows={4}
                placeholder="Enter category description (optional)"
              />

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={form.formState.isSubmitting}>
                  {form.formState.isSubmitting ? 'Creating...' : 'Create Category'}
                </Button>
                <Link href={route('categories.index')}>
                  <Button type="button" variant="outline">
                    Cancel
                  </Button>
                </Link>
              </div>
            </form>
          </FormCard>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
