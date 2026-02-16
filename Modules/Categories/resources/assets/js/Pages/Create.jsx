/**
 * Create category page
 * Uses shared CategoryForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import CategoryForm, { getCategoryDefaults } from '@Categories/Components/Forms/CategoryForm';
import { createCategoryResolver } from '@Categories/Schemas/categorySchemas';

import PageShell from '@/Components/Layouts/PageShell';

export default function Create({ parentCategories = {}, types = [] }) {
  const form = useInertiaForm(getCategoryDefaults(), {
    resolver: createCategoryResolver,
    toast: {
      success: 'Category created successfully!',
      error: 'Failed to create category. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('categories.store'));
  };

  return (
    <PageShell title="Create Category">
      <div className="space-y-6">
        <CategoryForm
          control={form.control}
          onSubmit={handleSubmit}
          isSubmitting={form.formState.isSubmitting}
          parentCategories={parentCategories}
          types={types}
          watch={form.watch}
          setValue={form.setValue}
        />
      </div>
    </PageShell>
  );
}
