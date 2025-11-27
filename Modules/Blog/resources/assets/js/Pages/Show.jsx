/**
 * Blog post detail page displaying full post content
 * Includes edit and delete actions with post metadata
 */
import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';

import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Show({ post }) {
  if (!post || !post.id) {
    return (
      <DashboardLayout header="Blog">
        <PageShell title="Post Not Found">
          <div className="text-center py-8">
            <p className="text-muted-foreground">Post not found or invalid.</p>
            <Link href={route('blog.index')}>
              <Button variant="outline" className="mt-4">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Posts
              </Button>
            </Link>
          </div>
        </PageShell>
      </DashboardLayout>
    );
  }

  const editUrl = route('blog.edit', { blog: post.id });
  const deleteUrl = route('blog.destroy', { blog: post.id });

  const handleDelete = () => {
    if (window.confirm('Are you sure you want to delete this post?')) {
      router.delete(deleteUrl);
    }
  };

  return (
    <DashboardLayout header="Blog">
      <PageShell title={post.title}>
        <div className="space-y-6">
          <div className="flex items-center justify-between">
            <Link href={route('blog.index')}>
              <Button variant="outline">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Posts
              </Button>
            </Link>
            <div className="flex gap-2">
              <Link href={editUrl}>
                <Button variant="outline">
                  <Pencil className="mr-2 h-4 w-4" />
                  Edit
                </Button>
              </Link>
              <Button variant="destructive" onClick={handleDelete}>
                <Trash2 className="mr-2 h-4 w-4" />
                Delete
              </Button>
            </div>
          </div>

          <Card>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <CardTitle className="text-3xl">{post.title}</CardTitle>
                  <div className="mt-4 flex items-center gap-4 text-sm text-muted-foreground">
                    <span>By {post.user?.name || 'Unknown'}</span>
                    {post.published_at && (
                      <span>Published on {new Date(post.published_at).toLocaleDateString()}</span>
                    )}
                    {!post.published_at && <span className="text-yellow-600">Draft</span>}
                  </div>
                </div>
                {post.published_at ? (
                  <span className="ml-4 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                    Published
                  </span>
                ) : (
                  <span className="ml-4 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    Draft
                  </span>
                )}
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              {post.excerpt && (
                <div className="rounded-lg bg-muted p-4">
                  <p className="text-lg font-medium">{post.excerpt}</p>
                </div>
              )}
              <div className="prose max-w-none dark:prose-invert">
                <div className="whitespace-pre-wrap">{post.content}</div>
              </div>
            </CardContent>
          </Card>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
