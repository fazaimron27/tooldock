import PageShell from '@/Components/Layouts/PageShell';

import DashboardLayout from '@/Layouts/DashboardLayout';

import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status, avatar }) {
  return (
    <DashboardLayout header="Profile">
      <PageShell title="Profile">
        <div className="space-y-6">
          <UpdateProfileInformationForm
            mustVerifyEmail={mustVerifyEmail}
            status={status}
            avatar={avatar}
            className="max-w-2xl"
          />

          <UpdatePasswordForm className="max-w-2xl" />

          <DeleteUserForm className="max-w-2xl" />
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
