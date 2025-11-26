import { useDatatable } from '@/Hooks/useDatatable';
import { useDisclosure } from '@/Hooks/useDisclosure';
import { STATUS_COLORS } from '@/Utils/constants';
import { Edit, Trash2 } from 'lucide-react';
import { Activity, DollarSign, TrendingUp, Users } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import AddUserDialog from '@/Components/Dashboard/AddUserDialog';
import CreateOrderDialog from '@/Components/Dashboard/CreateOrderDialog';
import GenerateReportDialog from '@/Components/Dashboard/GenerateReportDialog';
import GrowthTrendChartCard from '@/Components/Dashboard/GrowthTrendChartCard';
import RecentActivityCard from '@/Components/Dashboard/RecentActivityCard';
import RevenueChartCard from '@/Components/Dashboard/RevenueChartCard';
import SalesChartCard from '@/Components/Dashboard/SalesChartCard';
import SystemStatusCard from '@/Components/Dashboard/SystemStatusCard';
import ViewAnalyticsDialog from '@/Components/Dashboard/ViewAnalyticsDialog';
import DataTable from '@/Components/DataDisplay/DataTable';
import StatGrid from '@/Components/DataDisplay/StatGrid';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

const stats = [
  {
    title: 'Total Revenue',
    value: '$45,231.89',
    change: '+20.1%',
    trend: 'up',
    icon: DollarSign,
  },
  {
    title: 'Active Users',
    value: '2,350',
    change: '+180.1%',
    trend: 'up',
    icon: Users,
  },
  {
    title: 'Sales',
    value: '12,234',
    change: '+19%',
    trend: 'up',
    icon: TrendingUp,
  },
  {
    title: 'Active Now',
    value: '573',
    change: '-12%',
    trend: 'down',
    icon: Activity,
  },
];

const salesData = [
  { name: 'Jan', value: 4000 },
  { name: 'Feb', value: 3000 },
  { name: 'Mar', value: 2000 },
  { name: 'Apr', value: 2780 },
  { name: 'May', value: 1890 },
  { name: 'Jun', value: 2390 },
  { name: 'Jul', value: 3490 },
];

const revenueData = [
  { month: 'Jan', revenue: 4000, expenses: 2400 },
  { month: 'Feb', revenue: 3000, expenses: 1398 },
  { month: 'Mar', revenue: 2000, expenses: 9800 },
  { month: 'Apr', revenue: 2780, expenses: 3908 },
  { month: 'May', revenue: 1890, expenses: 4800 },
  { month: 'Jun', revenue: 2390, expenses: 3800 },
  { month: 'Jul', revenue: 3490, expenses: 4300 },
  { month: 'Aug', revenue: 3490, expenses: 4300 },
];

const tableData = [
  {
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    role: 'Admin',
    status: 'Active',
    lastActive: '2024-01-15',
  },
  {
    id: 2,
    name: 'Jane Smith',
    email: 'jane@example.com',
    role: 'User',
    status: 'Active',
    lastActive: '2024-01-14',
  },
  {
    id: 3,
    name: 'Bob Johnson',
    email: 'bob@example.com',
    role: 'Editor',
    status: 'Inactive',
    lastActive: '2024-01-10',
  },
  {
    id: 4,
    name: 'Alice Williams',
    email: 'alice@example.com',
    role: 'User',
    status: 'Active',
    lastActive: '2024-01-15',
  },
  {
    id: 5,
    name: 'Charlie Brown',
    email: 'charlie@example.com',
    role: 'User',
    status: 'Active',
    lastActive: '2024-01-13',
  },
  {
    id: 6,
    name: 'Diana Prince',
    email: 'diana@example.com',
    role: 'Admin',
    status: 'Active',
    lastActive: '2024-01-15',
  },
  {
    id: 7,
    name: 'Edward Norton',
    email: 'edward@example.com',
    role: 'Editor',
    status: 'Inactive',
    lastActive: '2024-01-08',
  },
  {
    id: 8,
    name: 'Fiona Apple',
    email: 'fiona@example.com',
    role: 'User',
    status: 'Active',
    lastActive: '2024-01-14',
  },
  {
    id: 9,
    name: 'George Lucas',
    email: 'george@example.com',
    role: 'Admin',
    status: 'Active',
    lastActive: '2024-01-15',
  },
  {
    id: 10,
    name: 'Helen Keller',
    email: 'helen@example.com',
    role: 'User',
    status: 'Active',
    lastActive: '2024-01-12',
  },
  {
    id: 11,
    name: 'Isaac Newton',
    email: 'isaac@example.com',
    role: 'Editor',
    status: 'Active',
    lastActive: '2024-01-15',
  },
  {
    id: 12,
    name: 'Julia Roberts',
    email: 'julia@example.com',
    role: 'User',
    status: 'Inactive',
    lastActive: '2024-01-05',
  },
];

export default function Dashboard() {
  // Dialog states using useDisclosure
  const viewAnalyticsDialog = useDisclosure();
  const deleteUserDialog = useDisclosure();

  const [userToDelete, setUserToDelete] = useState(null);

  // Toast helper function
  const testToast = (type = 'success') => {
    switch (type) {
      case 'success':
        toast.success('Operation completed successfully!', {
          description: 'Your changes have been saved.',
        });
        break;
      case 'error':
        toast.error('Something went wrong!', {
          description: 'Please try again or contact support.',
        });
        break;
      case 'warning':
        toast.warning('Please review this action', {
          description: 'This action cannot be undone.',
        });
        break;
      case 'info':
        toast.info('New update available', {
          description: 'Check out the latest features.',
        });
        break;
      default:
        toast('Default toast notification');
    }
  };

  // Table columns definition
  const columns = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: 'Name',
        cell: (info) => info.getValue(),
      },
      {
        accessorKey: 'email',
        header: 'Email',
        cell: (info) => info.getValue(),
      },
      {
        accessorKey: 'role',
        header: 'Role',
        cell: (info) => (
          <span className="px-2 py-1 text-xs rounded-full bg-primary/10 text-primary">
            {String(info.getValue())}
          </span>
        ),
      },
      {
        accessorKey: 'status',
        header: 'Status',
        cell: (info) => {
          const status = String(info.getValue());
          const statusConfig = STATUS_COLORS[status] || STATUS_COLORS.Inactive;
          return (
            <span
              className={`px-2 py-1 text-xs rounded-full ${statusConfig.bg} ${statusConfig.dark}`}
            >
              {status}
            </span>
          );
        },
      },
      {
        accessorKey: 'lastActive',
        header: 'Last Active',
        cell: (info) => info.getValue(),
      },
      {
        id: 'actions',
        header: 'Actions',
        cell: (info) => {
          const row = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8"
                onClick={() => {
                  toast.info(`Editing user: ${row.name}`);
                }}
              >
                <Edit className="h-4 w-4" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                onClick={() => {
                  setUserToDelete(row);
                  deleteUserDialog.onOpen();
                }}
              >
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          );
        },
      },
    ],
    [deleteUserDialog, setUserToDelete]
  );

  // Use datatable hook
  const { tableProps } = useDatatable({
    data: tableData,
    columns,
    serverSide: false, // Client-side for demo
  });

  // Handle user deletion
  const handleDeleteUser = () => {
    if (userToDelete) {
      toast.success(`User ${userToDelete.name} has been deleted.`, {
        description: 'The user has been permanently removed from the system.',
      });
      setUserToDelete(null);
      deleteUserDialog.onClose();
    }
  };

  return (
    <DashboardLayout header="Dashboard">
      <PageShell title="Dashboard">
        {/* Stats Grid */}
        <StatGrid stats={stats} />

        {/* Charts Grid */}
        <div className="grid gap-4 md:grid-cols-2">
          <SalesChartCard data={salesData} />
          <RevenueChartCard data={revenueData} />
        </div>

        {/* Growth Trend Chart */}
        <GrowthTrendChartCard data={salesData} />

        {/* Additional Widgets */}
        <div className="grid gap-4 md:grid-cols-3">
          <RecentActivityCard
            activities={[
              { title: 'New user registered', timestamp: '2 minutes ago' },
              { title: 'Order completed', timestamp: '15 minutes ago' },
              { title: 'Payment received', timestamp: '1 hour ago' },
            ]}
          />

          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
              <CardDescription>Common tasks</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                <CreateOrderDialog
                  trigger={
                    <Button variant="ghost" className="w-full justify-start text-sm">
                      Create New Order
                    </Button>
                  }
                />

                <AddUserDialog
                  trigger={
                    <Button variant="ghost" className="w-full justify-start text-sm">
                      Add New User
                    </Button>
                  }
                />

                <GenerateReportDialog
                  trigger={
                    <Button variant="ghost" className="w-full justify-start text-sm">
                      Generate Report
                    </Button>
                  }
                />

                <ViewAnalyticsDialog
                  open={viewAnalyticsDialog.isOpen}
                  onOpenChange={viewAnalyticsDialog.onToggle}
                  trigger={
                    <Button variant="ghost" className="w-full justify-start text-sm">
                      View Analytics
                    </Button>
                  }
                />

                <Button
                  variant="ghost"
                  onClick={() => testToast('success')}
                  className="w-full justify-start text-sm"
                >
                  Test Success Toast
                </Button>
                <Button
                  variant="ghost"
                  onClick={() => testToast('error')}
                  className="w-full justify-start text-sm"
                >
                  Test Error Toast
                </Button>
                <Button
                  variant="ghost"
                  onClick={() => testToast('warning')}
                  className="w-full justify-start text-sm"
                >
                  Test Warning Toast
                </Button>
                <Button
                  variant="ghost"
                  onClick={() => testToast('info')}
                  className="w-full justify-start text-sm"
                >
                  Test Info Toast
                </Button>
              </div>
            </CardContent>
          </Card>

          <SystemStatusCard
            metrics={[
              { label: 'CPU Usage', value: '45%', percentage: 45 },
              { label: 'Memory', value: '62%', percentage: 62 },
              { label: 'Storage', value: '38%', percentage: 38 },
            ]}
          />
        </div>

        {/* DataTable */}
        <DataTable
          {...tableProps}
          title="Users Table"
          description="Example table built with TanStack Table - supports search, sorting, and pagination"
        />

        {/* Delete User Confirmation Dialog */}
        <ConfirmDialog
          isOpen={deleteUserDialog.isOpen}
          onConfirm={handleDeleteUser}
          onCancel={() => {
            setUserToDelete(null);
            deleteUserDialog.onClose();
          }}
          title="Are you sure?"
          message={
            <>
              This action cannot be undone. This will permanently delete the user
              <strong> {userToDelete?.name}</strong> ({userToDelete?.email}) from the system.
            </>
          }
          confirmLabel="Delete User"
          variant="destructive"
        />
      </PageShell>
    </DashboardLayout>
  );
}
