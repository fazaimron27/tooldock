/**
 * Edit newsletter campaign page with form for updating existing campaigns
 * Pre-fills form fields with current campaign data
 */
import { useSmartForm } from '@/Hooks/useSmartForm';
import { Link } from '@inertiajs/react';
import { Calendar } from 'lucide-react';

import FormCard from '@/Components/Common/FormCard';
import FormField from '@/Components/Common/FormField';
import FormTextarea from '@/Components/Common/FormTextarea';
import DatePicker from '@/Components/Form/DatePicker';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Edit({ campaign, posts = [] }) {
  const { data, setData, errors, processing, put } = useSmartForm(
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
    put(route('newsletter.update', { newsletter: campaign.id }));
  };

  const handlePostToggle = (postId) => {
    const currentPosts = data.selected_posts || [];
    if (currentPosts.includes(postId)) {
      setData(
        'selected_posts',
        currentPosts.filter((id) => id !== postId)
      );
    } else {
      setData('selected_posts', [...currentPosts, postId]);
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
              <FormField
                name="subject"
                label="Subject"
                value={data.subject}
                onChange={(e) => setData('subject', e.target.value)}
                error={errors.subject}
                required
                placeholder="Enter campaign subject"
              />

              <FormTextarea
                name="content"
                label="Content"
                value={data.content}
                onChange={(e) => setData('content', e.target.value)}
                error={errors.content}
                required
                rows={10}
                placeholder="Enter email body content"
              />

              <div className="space-y-2">
                <Label>Select Blog Posts</Label>
                {errors.selected_posts && (
                  <p className="text-sm text-destructive">{errors.selected_posts}</p>
                )}
                {posts.length === 0 ? (
                  <p className="text-sm text-muted-foreground">
                    No published posts available. Create some blog posts first.
                  </p>
                ) : (
                  <Card>
                    <CardContent className="p-4">
                      <div className="space-y-3 max-h-[400px] overflow-y-auto">
                        {posts.map((post) => {
                          const isSelected = data.selected_posts?.includes(post.id) || false;
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

              <DatePicker
                label="Scheduled At (Optional)"
                value={data.scheduled_at}
                onChange={(date) => setData('scheduled_at', date || '')}
                error={errors.scheduled_at}
                placeholder="Leave empty to send immediately"
              />

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={processing}>
                  {processing ? 'Updating...' : 'Update Campaign'}
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
