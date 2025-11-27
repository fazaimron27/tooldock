/**
 * Edit blog post page with form for updating existing posts
 * Pre-fills form fields with current post data
 */
import { useFormHandler } from '@/Hooks/useFormHandler';
import { Link } from '@inertiajs/react';

import FormCard from '@/Components/Common/FormCard';
import FormField from '@/Components/Common/FormField';
import FormTextarea from '@/Components/Common/FormTextarea';
import DatePicker from '@/Components/Form/DatePicker';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Edit({ post }) {
  const { data, setData, errors, processing, submit } = useFormHandler(
    {
      title: post.title || '',
      excerpt: post.excerpt || '',
      content: post.content || '',
      published_at: post.published_at
        ? new Date(post.published_at).toISOString().split('T')[0]
        : '',
    },
    {
      route: () => route('blog.update', { blog: post.id }),
      method: 'put',
    }
  );

  return (
    <DashboardLayout header="Blog">
      <PageShell title="Edit Post">
        <div className="space-y-6">
          <FormCard title="Edit Post" description="Update your blog post" className="max-w-3xl">
            <form onSubmit={submit} className="space-y-6" noValidate>
              <FormField
                name="title"
                label="Title"
                value={data.title}
                onChange={(e) => setData('title', e.target.value)}
                error={errors.title}
                required
                placeholder="Enter post title"
              />

              <FormField
                name="excerpt"
                label="Excerpt"
                value={data.excerpt}
                onChange={(e) => setData('excerpt', e.target.value)}
                error={errors.excerpt}
                placeholder="Brief summary of the post (optional)"
              />

              <FormTextarea
                name="content"
                label="Content"
                value={data.content}
                onChange={(e) => setData('content', e.target.value)}
                error={errors.content}
                required
                rows={10}
                placeholder="Enter post content"
              />

              <DatePicker
                label="Publish Date"
                value={data.published_at}
                onChange={(date) => setData('published_at', date || '')}
                error={errors.published_at}
                placeholder="Leave empty for draft"
              />

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={processing}>
                  {processing ? 'Updating...' : 'Update Post'}
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
