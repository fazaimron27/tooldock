/**
 * Edit category page
 * Uses shared CategoryForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import CategoryForm, { getCategoryDefaults } from '@Categories/Components/Forms/CategoryForm';
import { updateCategoryResolver } from '@Categories/Schemas/categorySchemas';

import PageShell from '@/Components/Layouts/PageShell';

export default function Edit({ category, parentCategories = {}, types = [] }) {
  const form = useInertiaForm(getCategoryDefaults(category), {
    resolver: updateCategoryResolver,
    toast: {
      success: 'Category updated successfully!',
      error: 'Failed to update category. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('categories.update', { category: category.id }));
  };

  return (
    <PageShell title="Edit Category">
      <div className="space-y-6">
        <CategoryForm
          control={form.control}
          onSubmit={handleSubmit}
          isSubmitting={form.formState.isSubmitting}
          isEdit
          parentCategories={parentCategories}
          types={types}
          watch={form.watch}
          setValue={form.setValue}
          excludeCategoryId={category.id}
        />
      </div>
    </PageShell>
  );
}
