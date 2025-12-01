/**
 * Edit category page with form for updating existing categories
 * Includes fields for name, type, parent, color, and description
 */
import { useSmartForm } from '@/Hooks/useSmartForm';
import { Link } from '@inertiajs/react';
import { useMemo } from 'react';

import FormCard from '@/Components/Common/FormCard';
import FormField from '@/Components/Common/FormField';
import FormTextarea from '@/Components/Common/FormTextarea';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Edit({ category, parentCategories = {}, types = [] }) {
  const form = useSmartForm(
    {
      name: category.name || '',
      slug: category.slug || '',
      type: category.type || '',
      parent_id: category.parent_id || '',
      color: category.color || '',
      description: category.description || '',
    },
    {
      toast: {
        success: 'Category updated successfully!',
        error: 'Failed to update category. Please check the form for errors.',
      },
    }
  );

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('categories.update', { category: category.id }));
  };

  const handleNameChange = (e) => {
    const name = e.target.value;
    form.setData('name', name);
    if (!form.data.slug || form.data.slug === '') {
      const slug = name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
      form.setData('slug', slug);
    }
  };

  const availableParents = useMemo(() => {
    if (!form.data.type || !parentCategories[form.data.type]) {
      return [];
    }
    return parentCategories[form.data.type].filter((parent) => parent.id !== category.id);
  }, [form.data.type, parentCategories, category.id]);

  return (
    <DashboardLayout header="Categories">
      <PageShell title="Edit Category">
        <div className="space-y-6">
          <FormCard
            title="Edit Category"
            description="Update category information"
            className="max-w-3xl"
          >
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <FormField
                name="name"
                label="Name"
                value={form.data.name}
                onChange={handleNameChange}
                error={form.errors.name}
                required
                placeholder="Enter category name"
              />

              <FormField
                name="slug"
                label="Slug"
                value={form.data.slug}
                onChange={(e) => form.setData('slug', e.target.value)}
                error={form.errors.slug}
                placeholder="Auto-generated from name"
              />

              <div className="space-y-2">
                <Label htmlFor="type">
                  Type <span className="text-destructive">*</span>
                </Label>
                <div className="relative">
                  <input
                    id="type"
                    type="text"
                    list="type-options"
                    value={form.data.type}
                    onChange={(e) => {
                      const value = e.target.value.toLowerCase().replace(/[^a-z0-9_-]/g, '');
                      form.setData('type', value);
                    }}
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
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
                {form.errors.type && <p className="text-sm text-destructive">{form.errors.type}</p>}
                <p className="text-xs text-muted-foreground">
                  Type lowercase letters, numbers, underscores, or hyphens. Suggestions appear as
                  you type.
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="parent_id">Parent Category</Label>
                <select
                  id="parent_id"
                  value={form.data.parent_id}
                  onChange={(e) => form.setData('parent_id', e.target.value || '')}
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                  disabled={!form.data.type || availableParents.length === 0}
                >
                  <option value="">None (Top-level category)</option>
                  {availableParents.map((parent) => (
                    <option key={parent.id} value={parent.id}>
                      {parent.name}
                    </option>
                  ))}
                </select>
                {form.errors.parent_id && (
                  <p className="text-sm text-destructive">{form.errors.parent_id}</p>
                )}
                {form.data.type && availableParents.length === 0 && (
                  <p className="text-sm text-muted-foreground">
                    No parent categories available for this type.
                  </p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="color">Color</Label>
                <div className="flex items-center gap-2">
                  <input
                    type="color"
                    id="color"
                    value={form.data.color || '#000000'}
                    onChange={(e) => form.setData('color', e.target.value)}
                    className="h-10 w-20 rounded border border-input"
                  />
                  <FormField
                    name="color"
                    label=""
                    type="text"
                    value={form.data.color || ''}
                    onChange={(e) => form.setData('color', e.target.value)}
                    error={form.errors.color}
                    placeholder="#000000"
                    className="flex-1"
                  />
                </div>
                {form.errors.color && (
                  <p className="text-sm text-destructive">{form.errors.color}</p>
                )}
              </div>

              <FormTextarea
                name="description"
                label="Description"
                value={form.data.description}
                onChange={(e) => form.setData('description', e.target.value)}
                error={form.errors.description}
                rows={4}
                placeholder="Enter category description (optional)"
              />

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={form.processing}>
                  {form.processing ? 'Updating...' : 'Update Category'}
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
