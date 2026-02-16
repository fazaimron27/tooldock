/**
 * Shared Category Form component - RHF Version
 * Uses React Hook Form with Controller pattern for auto-revalidation
 * Handles both create and edit modes
 */
import { cn } from '@/Utils/utils';
import ColorPickerRHF from '@Categories/Components/FormFields/ColorPickerRHF';
import ParentSelectRHF from '@Categories/Components/FormFields/ParentSelectRHF';
import TypeInputRHF from '@Categories/Components/FormFields/TypeInputRHF';
import { Link } from '@inertiajs/react';
import { useMemo } from 'react';
import { Controller } from 'react-hook-form';

import FormCard from '@/Components/Common/FormCard';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';

/**
 * Get default form values for category
 */
export function getCategoryDefaults(category = null) {
  if (category) {
    return {
      name: category.name || '',
      slug: category.slug || '',
      type: category.type || '',
      parent_id: category.parent_id ? String(category.parent_id) : '',
      color: category.color || '',
      description: category.description || '',
    };
  }

  return {
    name: '',
    slug: '',
    type: '',
    parent_id: '',
    color: '',
    description: '',
  };
}

export default function CategoryForm({
  control,
  onSubmit,
  isSubmitting = false,
  isEdit = false,
  parentCategories = {},
  types = [],
  watch,
  setValue,
  cancelUrl,
  excludeCategoryId = null,
}) {
  const selectedType = watch?.('type') || '';

  const availableParents = useMemo(() => {
    if (!selectedType || !parentCategories[selectedType]) {
      return [];
    }
    const parents = parentCategories[selectedType];
    if (excludeCategoryId) {
      return parents.filter((parent) => parent.id !== excludeCategoryId);
    }
    return parents;
  }, [selectedType, parentCategories, excludeCategoryId]);

  return (
    <FormCard
      title={isEdit ? 'Edit Category' : 'New Category'}
      description={isEdit ? 'Update category details' : 'Create a new category'}
      className="max-w-3xl"
    >
      <form onSubmit={onSubmit} className="space-y-6" noValidate>
        {/* Name Field with auto-slug generation */}
        <Controller
          name="name"
          control={control}
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
                  const name = e.target.value;
                  const currentSlug = watch?.('slug') || '';
                  if (
                    !currentSlug ||
                    currentSlug ===
                      field.value
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/(^-|-$)/g, '')
                  ) {
                    const newSlug = name
                      .toLowerCase()
                      .replace(/[^a-z0-9]+/g, '-')
                      .replace(/(^-|-$)/g, '');
                    setValue?.('slug', newSlug, { shouldValidate: false });
                  }
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

        {/* Slug Field */}
        <Controller
          name="slug"
          control={control}
          render={({ field, fieldState: { error } }) => (
            <div className="space-y-2">
              <Label htmlFor="slug">Slug</Label>
              <input
                id="slug"
                type="text"
                {...field}
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

        {/* Type Field */}
        <TypeInputRHF
          name="type"
          control={control}
          label="Type"
          required
          types={types}
          placeholder="Enter category type (e.g., product, finance, project)"
          helperText="Type lowercase letters, numbers, underscores, or hyphens. Suggestions appear as you type."
        />

        {/* Parent Category Field */}
        <ParentSelectRHF
          name="parent_id"
          control={control}
          label="Parent Category"
          options={availableParents}
          disabled={!selectedType || availableParents.length === 0}
          noOptionsMessage={
            selectedType && availableParents.length === 0
              ? 'No parent categories available for this type.'
              : undefined
          }
        />

        {/* Color Field */}
        <ColorPickerRHF name="color" control={control} label="Color" />

        {/* Description Field */}
        <FormTextareaRHF
          name="description"
          control={control}
          label="Description"
          rows={4}
          placeholder="Enter category description (optional)"
        />

        {/* Actions */}
        <div className="flex items-center justify-end gap-4">
          <Link href={cancelUrl || route('categories.index')}>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </Link>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? isEdit
                ? 'Saving...'
                : 'Creating...'
              : isEdit
                ? 'Save Changes'
                : 'Create Category'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
