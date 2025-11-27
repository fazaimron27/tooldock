/**
 * Dialog component for generating reports
 * Includes form fields for report type selection and date range
 */
import { useFormWithDialog } from '@/Hooks/useFormWithDialog';
import { toast } from 'sonner';

import FormDialog from '@/Components/Common/FormDialog';
import FormField from '@/Components/Common/FormField';
import { Label } from '@/Components/ui/label';

export default function GenerateReportDialog({ trigger }) {
  const { data, setData, errors, processing, submit, dialog, handleDialogChange } =
    useFormWithDialog(
      {
        report_type: 'sales',
        start_date: '',
        end_date: '',
      },
      {
        route: 'reports.generate', // Update with actual route
        method: 'post',
        onSuccess: () => {
          toast.info('Report generation started', {
            description: 'Your report is being generated and will be available shortly.',
          });
        },
      }
    );

  return (
    <FormDialog
      open={dialog.isOpen}
      onOpenChange={handleDialogChange}
      title="Generate Report"
      description="Select the report type and date range to generate."
      trigger={trigger}
      confirmLabel="Generate Report"
      cancelLabel="Cancel"
      processing={processing}
      processingLabel="Generating..."
      formId="generate-report-form"
    >
      <form id="generate-report-form" onSubmit={submit} className="space-y-4">
        <div className="space-y-2">
          <Label htmlFor="report_type">Report Type</Label>
          <select
            id="report_type"
            value={data.report_type}
            onChange={(e) => setData('report_type', e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          >
            <option value="sales">Sales Report</option>
            <option value="revenue">Revenue Report</option>
            <option value="users">Users Report</option>
            <option value="activity">Activity Report</option>
          </select>
          {errors.report_type && <p className="text-sm text-destructive">{errors.report_type}</p>}
        </div>

        <FormField
          name="start_date"
          label="Start Date"
          type="date"
          value={data.start_date}
          onChange={(e) => setData('start_date', e.target.value)}
          error={errors.start_date}
        />

        <FormField
          name="end_date"
          label="End Date"
          type="date"
          value={data.end_date}
          onChange={(e) => setData('end_date', e.target.value)}
          error={errors.end_date}
        />
      </form>
    </FormDialog>
  );
}
