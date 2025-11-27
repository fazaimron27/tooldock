/**
 * Dialog component for adding new users to the system
 * Includes form fields for name, email, and role selection
 */
import { useFormWithDialog } from '@/Hooks/useFormWithDialog';
import { toast } from 'sonner';

import FormDialog from '@/Components/Common/FormDialog';
import FormField from '@/Components/Common/FormField';
import { Label } from '@/Components/ui/label';

export default function AddUserDialog({ trigger }) {
  const { data, setData, errors, processing, submit, dialog, handleDialogChange } =
    useFormWithDialog(
      {
        name: '',
        email: '',
        role: 'user',
      },
      {
        route: 'users.store', // Update with actual route
        method: 'post',
        onSuccess: () => {
          toast.success('User added successfully!', {
            description: 'The user has been added to the system.',
          });
        },
      }
    );

  return (
    <FormDialog
      open={dialog.isOpen}
      onOpenChange={handleDialogChange}
      title="Add New User"
      description="Enter the user information to add them to the system."
      trigger={trigger}
      confirmLabel="Add User"
      cancelLabel="Cancel"
      processing={processing}
      processingLabel="Adding..."
      formId="add-user-form"
    >
      <form id="add-user-form" onSubmit={submit} className="space-y-4">
        <FormField
          name="name"
          label="Full Name"
          value={data.name}
          onChange={(e) => setData('name', e.target.value)}
          error={errors.name}
          placeholder="Enter full name"
        />

        <FormField
          name="email"
          label="Email"
          type="email"
          value={data.email}
          onChange={(e) => setData('email', e.target.value)}
          error={errors.email}
          placeholder="Enter email address"
        />

        <div className="space-y-2">
          <Label htmlFor="role">Role</Label>
          <select
            id="role"
            value={data.role}
            onChange={(e) => setData('role', e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          >
            <option value="user">User</option>
            <option value="admin">Admin</option>
            <option value="editor">Editor</option>
          </select>
          {errors.role && <p className="text-sm text-destructive">{errors.role}</p>}
        </div>
      </form>
    </FormDialog>
  );
}
