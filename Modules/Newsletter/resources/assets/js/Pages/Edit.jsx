/**
 * Edit newsletter campaign page with form for updating existing campaigns
 * Pre-fills form fields with current campaign data
 * Uses React Hook Form for improved performance and validation
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { Link } from '@inertiajs/react';
import { Calendar } from 'lucide-react';
import { Controller } from 'react-hook-form';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import DatePicker from '@/Components/Form/DatePicker';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Edit({ campaign, posts = [] }) {
  const form = useInertiaForm(
    {
      subject: campaign.subject || '',
      content: campaign.content || '',
      selected_posts: campaign.selected_posts || [],
      scheduled_at: campaign.scheduled_at
        ? new Date(campaign.scheduled_at).toISOString().split('T')[0]
        : '',
    },
    {
      toast: {
        success: 'Campaign updated successfully!',
        error: 'Failed to update campaign. Please try again.',
      },
    }
  );

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('newsletter.update', { newsletter: campaign.id }));
  };

  const handlePostToggle = (postId) => {
    const currentPosts = form.watch('selected_posts') || [];
    if (currentPosts.includes(postId)) {
      form.setValue(
        'selected_posts',
        currentPosts.filter((id) => id !== postId),
        { shouldValidate: false }
      );
    } else {
      form.setValue('selected_posts', [...currentPosts, postId], { shouldValidate: false });
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) {
      return '';
    }
    try {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });
    } catch {
      return dateString;
    }
  };

  return (
    <DashboardLayout header="Newsletter">
      <PageShell title="Edit Campaign">
        <div className="space-y-6">
          <FormCard
            title="Edit Campaign"
            description="Update your email campaign"
            className="max-w-4xl"
            icon={Calendar}
          >
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <FormFieldRHF
                name="subject"
                control={form.control}
                label="Subject"
                required
                placeholder="Enter campaign subject"
                rules={{ required: 'Subject is required' }}
              />

              <FormTextareaRHF
                name="content"
                control={form.control}
                label="Content"
                required
                rows={10}
                placeholder="Enter email body content"
                rules={{ required: 'Content is required' }}
              />

              <Controller
                name="selected_posts"
                control={form.control}
                render={({ field, fieldState: { error } }) => (
                  <div className="space-y-2">
                    <Label>Select Blog Posts</Label>
                    {error && <p className="text-sm text-destructive">{error.message}</p>}
                    {posts.length === 0 ? (
                      <p className="text-sm text-muted-foreground">
                        No published posts available. Create some blog posts first.
                      </p>
                    ) : (
                      <Card>
                        <CardContent className="p-4">
                          <div className="space-y-3 max-h-[400px] overflow-y-auto">
                            {posts.map((post) => {
                              const isSelected = field.value?.includes(post.id) || false;
                              return (
                                <div
                                  key={post.id}
                                  className={`flex items-start gap-3 p-3 rounded-lg border transition-colors ${
                                    isSelected
                                      ? 'border-primary bg-primary/5'
                                      : 'border-border hover:bg-accent/50'
                                  }`}
                                >
                                  <div className="mt-1">
                                    <Checkbox
                                      checked={isSelected}
                                      onCheckedChange={() => handlePostToggle(post.id)}
                                    />
                                  </div>
                                  <div className="flex-1 min-w-0">
                                    <div className="font-medium text-sm">{post.title}</div>
                                    {post.published_at && (
                                      <div className="text-xs text-muted-foreground mt-1">
                                        Published: {formatDate(post.published_at)}
                                      </div>
                                    )}
                                  </div>
                                </div>
                              );
                            })}
                          </div>
                        </CardContent>
                      </Card>
                    )}
                  </div>
                )}
              />

              <Controller
                name="scheduled_at"
                control={form.control}
                render={({ field, fieldState: { error } }) => (
                  <DatePicker
                    label="Scheduled At (Optional)"
                    value={field.value}
                    onChange={(date) => field.onChange(date || '')}
                    error={error?.message}
                    placeholder="Leave empty to send immediately"
                  />
                )}
              />

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={form.formState.isSubmitting}>
                  {form.formState.isSubmitting ? 'Updating...' : 'Update Campaign'}
                </Button>
                <Link href={route('newsletter.index')}>
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
