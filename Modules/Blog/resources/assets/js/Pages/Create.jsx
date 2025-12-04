/**
 * Create blog post page with form for creating new posts
 * Includes fields for title, excerpt, content, and publish date
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

export default function Create() {
  const form = useInertiaForm(
    {
      title: '',
      excerpt: '',
      content: '',
      published_at: '',
    },
    {
      toast: {
        success: 'Post created successfully!',
        error: 'Failed to create post. Please check the form for errors.',
      },
    }
  );

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('blog.store'));
  };

  return (
    <DashboardLayout header="Blog">
      <PageShell title="Create Post">
        <div className="space-y-6">
          <FormCard title="New Post" description="Create a new blog post" className="max-w-3xl">
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <FormFieldRHF
                name="title"
                control={form.control}
                label="Title"
                required
                placeholder="Enter post title"
                rules={{ required: 'Title is required' }}
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
                rules={{ required: 'Content is required' }}
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
                  {form.formState.isSubmitting ? 'Creating...' : 'Create Post'}
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
