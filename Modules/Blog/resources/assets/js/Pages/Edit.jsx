/**
 * Edit blog post page with form for updating existing posts
 * Pre-fills form fields with current post data
 * Uses React Hook Form for improved performance and validation
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { Link } from '@inertiajs/react';
import { Controller } from 'react-hook-form';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import DatePicker from '@/Components/Form/DatePicker';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

import { updatePostResolver } from '../Schemas/blogSchemas';

export default function Edit({ post }) {
  const form = useInertiaForm(
    {
      title: post.title || '',
      excerpt: post.excerpt || '',
      content: post.content || '',
      published_at: post.published_at
        ? new Date(post.published_at).toISOString().split('T')[0]
        : '',
    },
    {
      resolver: updatePostResolver,
      toast: {
        success: 'Post updated successfully!',
        error: 'Failed to update post. Please check the form for errors.',
      },
    }
  );

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('blog.update', { blog: post.id }));
  };

  return (
    <DashboardLayout header="Blog">
      <PageShell title="Edit Post">
        <div className="space-y-6">
          <FormCard title="Edit Post" description="Update your blog post" className="max-w-3xl">
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <FormFieldRHF
                name="title"
                control={form.control}
                label="Title"
                required
                placeholder="Enter post title"
              />

              <FormFieldRHF
                name="excerpt"
                control={form.control}
                label="Excerpt"
                placeholder="Brief summary of the post (optional)"
              />

              <FormTextareaRHF
                name="content"
                control={form.control}
                label="Content"
                required
                rows={10}
                placeholder="Enter post content"
              />

              <Controller
                name="published_at"
                control={form.control}
                render={({ field, fieldState: { error } }) => (
                  <div className="space-y-2">
                    <DatePicker
                      label="Publish Date"
                      value={field.value}
                      onChange={(date) => field.onChange(date || '')}
                      error={error?.message}
                      placeholder="Leave empty for draft"
                    />
                  </div>
                )}
              />

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={form.formState.isSubmitting}>
                  {form.formState.isSubmitting ? 'Updating...' : 'Update Post'}
                </Button>
                <Link href={route('blog.index')}>
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
