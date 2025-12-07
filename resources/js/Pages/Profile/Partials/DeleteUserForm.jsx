import { useFormWithDialog } from '@/Hooks/useFormWithDialog';
import { cn } from '@/Utils/utils';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

import FormCard from '@/Components/Common/FormCard';
import FormDialog from '@/Components/Common/FormDialog';
import PasswordFieldRHF from '@/Components/Common/PasswordFieldRHF';
import { Button } from '@/Components/ui/button';

export default function DeleteUserForm({ className = '' }) {
  const page = usePage();

  const formHook = useFormWithDialog(
    {
      password: '',
    },
    {
      route: 'profile.destroy',
      method: 'delete',
      errorBag: 'deleteAccount',
      toast: {
        success: 'Account deleted successfully!',
        error: 'The password is incorrect.',
      },
      onSuccess: () => {
        window.location.href = '/';
      },
      onError: () => {
        // Keep dialog open when there are errors
        if (!formHook.dialog.isOpen) {
          formHook.dialog.onOpen();
        }
      },
    }
  );

  /**
   * Handle errors from error bag scoped to delete account form.
   * Prevents errors from being shared with Update Password form on the same page.
   */
  useEffect(() => {
    const deleteErrors = page.props.errors?.deleteAccount;
    if (!deleteErrors) return;

    const passwordError = deleteErrors.password;
    if (passwordError && !formHook.formState.errors.password) {
      const errorMessage = Array.isArray(passwordError) ? passwordError[0] : passwordError;
      if (errorMessage) {
        formHook.setError('password', {
          type: 'server',
          message: String(errorMessage),
        });
        /**
         * Ensure dialog stays open to display validation error to user.
         */
        if (!formHook.dialog.isOpen) {
          formHook.dialog.onOpen();
        }
        toast.error(String(errorMessage));
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page.props.errors?.deleteAccount]);

  /**
   * Form submission handler.
   * useFormWithDialog handles React Hook Form validation and Inertia submission.
   */
  const handleSubmit = (e) => {
    e.preventDefault();
    formHook.submit(e);
  };

  return (
    <FormCard
      title="Delete Account"
      description="Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain."
      className={cn('border-destructive/50', className)}
      titleClassName="text-destructive"
    >
      <FormDialog
        open={formHook.dialog.isOpen}
        onOpenChange={formHook.handleDialogChange}
        onCancel={formHook.handleCancel}
        title="Are you absolutely sure?"
        description="This action cannot be undone. This will permanently delete your account and remove all your data from our servers. Please enter your password to confirm you would like to permanently delete your account."
        trigger={<Button variant="destructive">Delete Account</Button>}
        confirmLabel="Delete Account"
        cancelLabel="Cancel"
        variant="destructive"
        processing={formHook.processing}
        processingLabel="Deleting..."
        formId="delete-account-form"
      >
        <form id="delete-account-form" onSubmit={handleSubmit} className="space-y-4" noValidate>
          <PasswordFieldRHF
            name="password"
            control={formHook.control}
            label="Password"
            required
            placeholder="Enter your password"
            autoFocus
            id="delete-account-password"
            autoComplete="current-password"
            rules={{
              required: 'Password is required to confirm account deletion',
            }}
          />
        </form>
      </FormDialog>
    </FormCard>
  );
}
