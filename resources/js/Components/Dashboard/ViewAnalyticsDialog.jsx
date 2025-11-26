import { toast } from 'sonner';

import SimpleDialog from '@/Components/Common/SimpleDialog';
import { Button } from '@/Components/ui/button';

/**
 * Dialog for viewing analytics
 * @param {object} props
 * @param {boolean} props.open - Whether dialog is open
 * @param {function} props.onOpenChange - Callback when open state changes
 * @param {React.ReactNode} props.trigger - Trigger button/element
 */
export default function ViewAnalyticsDialog({ open, onOpenChange, trigger }) {
  const handleExport = () => {
    onOpenChange?.(false);
    toast.info('Export started', {
      description: 'Your analytics data is being exported.',
    });
  };

  return (
    <SimpleDialog
      open={open}
      onOpenChange={onOpenChange}
      title="Analytics Overview"
      description="View detailed analytics and insights for your dashboard."
      trigger={trigger}
      className="max-w-2xl"
      footer={
        <>
          <Button variant="outline" onClick={() => onOpenChange?.(false)}>
            Close
          </Button>
          <Button onClick={handleExport}>Export Data</Button>
        </>
      }
    >
      <div className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <div className="p-4 border rounded-lg">
            <p className="text-sm text-muted-foreground">Total Views</p>
            <p className="text-2xl font-bold">12,345</p>
          </div>
          <div className="p-4 border rounded-lg">
            <p className="text-sm text-muted-foreground">Conversion Rate</p>
            <p className="text-2xl font-bold">3.2%</p>
          </div>
          <div className="p-4 border rounded-lg">
            <p className="text-sm text-muted-foreground">Avg. Session</p>
            <p className="text-2xl font-bold">4m 32s</p>
          </div>
          <div className="p-4 border rounded-lg">
            <p className="text-sm text-muted-foreground">Bounce Rate</p>
            <p className="text-2xl font-bold">42%</p>
          </div>
        </div>
        <div className="p-4 border rounded-lg">
          <p className="text-sm font-medium mb-2">Top Pages</p>
          <div className="space-y-2">
            <div className="flex justify-between text-sm">
              <span>/dashboard</span>
              <span className="text-muted-foreground">2,345 views</span>
            </div>
            <div className="flex justify-between text-sm">
              <span>/users</span>
              <span className="text-muted-foreground">1,890 views</span>
            </div>
            <div className="flex justify-between text-sm">
              <span>/settings</span>
              <span className="text-muted-foreground">1,234 views</span>
            </div>
          </div>
        </div>
      </div>
    </SimpleDialog>
  );
}
