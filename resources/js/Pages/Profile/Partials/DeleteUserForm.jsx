import { useFormWithDialog } from '@/Hooks/useFormWithDialog';
import { cn } from '@/Utils/utils';

import FormCard from '@/Components/Common/FormCard';
import FormDialog from '@/Components/Common/FormDialog';
import FormField from '@/Components/Common/FormField';
import { Button } from '@/Components/ui/button';

export default function DeleteUserForm({ className = '' }) {
  const { data, setData, errors, processing, submit, dialog, handleDialogChange, fieldRefs } =
    useFormWithDialog(
      {
        password: '',
      },
      {
        route: 'profile.destroy',
        method: 'delete',
        focusFields: ['password'],
      }
    );

  return (
    <FormCard
      title="Delete Account"
      description="Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain."
      className={cn('border-destructive/50', className)}
      titleClassName="text-destructive"
    >
      <FormDialog
        open={dialog.isOpen}
        onOpenChange={handleDialogChange}
        title="Are you absolutely sure?"
        description="This action cannot be undone. This will permanently delete your account and remove all your data from our servers. Please enter your password to confirm you would like to permanently delete your account."
        trigger={<Button variant="destructive">Delete Account</Button>}
        confirmLabel="Delete Account"
        cancelLabel="Cancel"
        variant="destructive"
        processing={processing}
        processingLabel="Deleting..."
        formId="delete-account-form"
      >
        <form id="delete-account-form" onSubmit={submit} className="space-y-4">
          <FormField
            name="password"
            label="Password"
            type="password"
            value={data.password}
            onChange={(e) => setData('password', e.target.value)}
            error={errors.password}
            placeholder="Enter your password"
            inputRef={fieldRefs.password}
            autoFocus
          />
        </form>
      </FormDialog>
    </FormCard>
  );
}
