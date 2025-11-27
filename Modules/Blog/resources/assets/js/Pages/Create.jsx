/**
 * Create blog post page with form for creating new posts
 * Includes fields for title, excerpt, content, and publish date
 */
import { useFormHandler } from '@/Hooks/useFormHandler';
import { Link } from '@inertiajs/react';

import FormCard from '@/Components/Common/FormCard';
import FormField from '@/Components/Common/FormField';
import DatePicker from '@/Components/Form/DatePicker';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Create() {
  const { data, setData, errors, processing, submit } = useFormHandler(
    {
      title: '',
      excerpt: '',
      content: '',
      published_at: '',
    },
    {
      route: 'blog.store',
      method: 'post',
    }
  );

  return (
    <DashboardLayout header="Blog">
      <PageShell title="Create Post">
        <div className="space-y-6">
          <FormCard title="New Post" description="Create a new blog post" className="max-w-3xl">
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

              <div className="space-y-2">
                <Label htmlFor="content">Content</Label>
                <textarea
                  id="content"
                  name="content"
                  value={data.content}
                  onChange={(e) => setData('content', e.target.value)}
                  rows={10}
                  className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                  required
                />
                {errors.content && <p className="text-sm text-destructive">{errors.content}</p>}
              </div>

              <DatePicker
                label="Publish Date"
                value={data.published_at}
                onChange={(date) =>
                  setData('published_at', date ? date.toISOString().split('T')[0] : '')
                }
                error={errors.published_at}
                placeholder="Leave empty for draft"
              />

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={processing}>
                  {processing ? 'Creating...' : 'Create Post'}
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
