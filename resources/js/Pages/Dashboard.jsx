import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { TrendingUp, Users, DollarSign, Activity, ArrowUpRight, ArrowDownRight, ArrowUpDown, Search } from 'lucide-react';
import { BarChart, Bar, LineChart, Line, AreaChart, Area, XAxis, YAxis, CartesianGrid } from 'recharts';
import { useReactTable, getCoreRowModel, getSortedRowModel, getPaginationRowModel, getFilteredRowModel, flexRender } from '@tanstack/react-table';
import { useMemo, useState } from 'react';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/Components/ui/alert-dialog';
import { Trash2, Edit } from 'lucide-react';
import { toast } from 'sonner';

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
    { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin', status: 'Active', lastActive: '2024-01-15' },
    { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'User', status: 'Active', lastActive: '2024-01-14' },
    { id: 3, name: 'Bob Johnson', email: 'bob@example.com', role: 'Editor', status: 'Inactive', lastActive: '2024-01-10' },
    { id: 4, name: 'Alice Williams', email: 'alice@example.com', role: 'User', status: 'Active', lastActive: '2024-01-15' },
    { id: 5, name: 'Charlie Brown', email: 'charlie@example.com', role: 'User', status: 'Active', lastActive: '2024-01-13' },
    { id: 6, name: 'Diana Prince', email: 'diana@example.com', role: 'Admin', status: 'Active', lastActive: '2024-01-15' },
    { id: 7, name: 'Edward Norton', email: 'edward@example.com', role: 'Editor', status: 'Inactive', lastActive: '2024-01-08' },
    { id: 8, name: 'Fiona Apple', email: 'fiona@example.com', role: 'User', status: 'Active', lastActive: '2024-01-14' },
    { id: 9, name: 'George Lucas', email: 'george@example.com', role: 'Admin', status: 'Active', lastActive: '2024-01-15' },
    { id: 10, name: 'Helen Keller', email: 'helen@example.com', role: 'User', status: 'Active', lastActive: '2024-01-12' },
    { id: 11, name: 'Isaac Newton', email: 'isaac@example.com', role: 'Editor', status: 'Active', lastActive: '2024-01-15' },
    { id: 12, name: 'Julia Roberts', email: 'julia@example.com', role: 'User', status: 'Inactive', lastActive: '2024-01-05' },
];

export default function Dashboard() {
    const [sorting, setSorting] = useState([]);
    const [globalFilter, setGlobalFilter] = useState('');
    const [pagination, setPagination] = useState({
        pageIndex: 0,
        pageSize: 10,
    });
    const [createOrderOpen, setCreateOrderOpen] = useState(false);
    const [addUserOpen, setAddUserOpen] = useState(false);
    const [generateReportOpen, setGenerateReportOpen] = useState(false);
    const [viewAnalyticsOpen, setViewAnalyticsOpen] = useState(false);
    const [userToDelete, setUserToDelete] = useState(null);

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
                    return (
                        <span
                            className={`px-2 py-1 text-xs rounded-full ${
                                status === 'Active'
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                            }`}
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
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                        onClick={() => {
                                            setUserToDelete(row);
                                        }}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            This action cannot be undone. This will permanently delete the user
                                            <strong> {userToDelete?.name || row.name}</strong> ({userToDelete?.email || row.email}) from the system.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                        <AlertDialogAction
                                            onClick={() => {
                                                const user = userToDelete || row;
                                                // Simulate deletion
                                                toast.success(`User ${user.name} has been deleted.`, {
                                                    description: 'The user has been permanently removed from the system.',
                                                });
                                                setUserToDelete(null);
                                            }}
                                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                        >
                                            Delete User
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    );
                },
            },
        ],
        [userToDelete]
    );

    const table = useReactTable({
        data: tableData,
        columns,
        state: {
            sorting,
            globalFilter,
            pagination,
        },
        onSortingChange: setSorting,
        onGlobalFilterChange: setGlobalFilter,
        onPaginationChange: setPagination,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        globalFilterFn: 'includesString',
    });

    return (
        <DashboardLayout header="Dashboard">
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* Stats Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {stats.map((stat) => {
                        const Icon = stat.icon;
                        const TrendIcon = stat.trend === 'up' ? ArrowUpRight : ArrowDownRight;

                        return (
                            <Card key={stat.title}>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">
                                        {stat.title}
                                    </CardTitle>
                                    <Icon className="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{stat.value}</div>
                                    <p className={`text-xs flex items-center gap-1 mt-1 ${
                                        stat.trend === 'up' ? 'text-green-600' : 'text-red-600'
                                    }`}>
                                        <TrendIcon className="h-3 w-3" />
                                        {stat.change} from last month
                                    </p>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Charts Grid */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Sales Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Sales Overview</CardTitle>
                            <CardDescription>
                                Monthly sales performance
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="overflow-hidden">
                            <ChartContainer
                                config={{
                                    value: {
                                        label: 'Sales',
                                        color: 'hsl(var(--chart-1))',
                                    },
                                }}
                                className="h-[300px] w-full"
                            >
                                <BarChart data={salesData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <ChartTooltip content={<ChartTooltipContent />} />
                                    <Bar dataKey="value" fill="var(--color-value)" radius={[8, 8, 0, 0]} />
                                </BarChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>

                    {/* Revenue Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Revenue & Expenses</CardTitle>
                            <CardDescription>
                                Revenue vs expenses comparison
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="overflow-hidden">
                            <ChartContainer
                                config={{
                                    revenue: {
                                        label: 'Revenue',
                                        color: 'hsl(var(--chart-1))',
                                    },
                                    expenses: {
                                        label: 'Expenses',
                                        color: 'hsl(var(--chart-2))',
                                    },
                                }}
                                className="h-[300px] w-full"
                            >
                                <AreaChart data={revenueData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="month" />
                                    <YAxis />
                                    <ChartTooltip content={<ChartTooltipContent />} />
                                    <Area
                                        type="monotone"
                                        dataKey="revenue"
                                        stroke="var(--color-revenue)"
                                        fill="var(--color-revenue)"
                                        fillOpacity={0.6}
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey="expenses"
                                        stroke="var(--color-expenses)"
                                        fill="var(--color-expenses)"
                                        fillOpacity={0.6}
                                    />
                                </AreaChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Line Chart */}
                <Card>
                    <CardHeader>
                        <CardTitle>Growth Trend</CardTitle>
                        <CardDescription>
                            User growth over the last 7 months
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="overflow-hidden">
                        <ChartContainer
                            config={{
                                users: {
                                    label: 'Users',
                                    color: 'hsl(var(--chart-3))',
                                },
                            }}
                            className="h-[300px] w-full"
                        >
                            <LineChart data={salesData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" />
                                <YAxis />
                                <ChartTooltip content={<ChartTooltipContent />} />
                                <Line
                                    type="monotone"
                                    dataKey="value"
                                    stroke="var(--color-users)"
                                    strokeWidth={2}
                                    dot={{ r: 4 }}
                                />
                            </LineChart>
                        </ChartContainer>
                    </CardContent>
                </Card>

                {/* Additional Widgets */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Activity</CardTitle>
                            <CardDescription>Latest system events</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex items-center gap-4">
                                    <div className="h-2 w-2 rounded-full bg-primary"></div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">New user registered</p>
                                        <p className="text-xs text-muted-foreground">2 minutes ago</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-4">
                                    <div className="h-2 w-2 rounded-full bg-primary"></div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">Order completed</p>
                                        <p className="text-xs text-muted-foreground">15 minutes ago</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-4">
                                    <div className="h-2 w-2 rounded-full bg-primary"></div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">Payment received</p>
                                        <p className="text-xs text-muted-foreground">1 hour ago</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                            <CardDescription>Common tasks</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <Dialog open={createOrderOpen} onOpenChange={setCreateOrderOpen}>
                                    <DialogTrigger asChild>
                                        <Button variant="ghost" className="w-full justify-start text-sm">
                                            Create New Order
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Create New Order</DialogTitle>
                                            <DialogDescription>
                                                Fill in the details below to create a new order.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="space-y-4 py-4">
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Customer Name</label>
                                                <Input placeholder="Enter customer name" />
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Product</label>
                                                <Input placeholder="Enter product name" />
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Quantity</label>
                                                <Input type="number" placeholder="Enter quantity" />
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setCreateOrderOpen(false)}>
                                                Cancel
                                            </Button>
                                            <Button onClick={() => {
                                                setCreateOrderOpen(false);
                                                testToast('success');
                                            }}>
                                                Create Order
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <Dialog open={addUserOpen} onOpenChange={setAddUserOpen}>
                                    <DialogTrigger asChild>
                                        <Button variant="ghost" className="w-full justify-start text-sm">
                                            Add New User
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Add New User</DialogTitle>
                                            <DialogDescription>
                                                Enter the user information to add them to the system.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="space-y-4 py-4">
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Full Name</label>
                                                <Input placeholder="Enter full name" />
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Email</label>
                                                <Input type="email" placeholder="Enter email address" />
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Role</label>
                                                <select className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                                    <option value="user">User</option>
                                                    <option value="admin">Admin</option>
                                                    <option value="editor">Editor</option>
                                                </select>
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setAddUserOpen(false)}>
                                                Cancel
                                            </Button>
                                            <Button onClick={() => {
                                                setAddUserOpen(false);
                                                testToast('success');
                                            }}>
                                                Add User
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <Dialog open={generateReportOpen} onOpenChange={setGenerateReportOpen}>
                                    <DialogTrigger asChild>
                                        <Button variant="ghost" className="w-full justify-start text-sm">
                                            Generate Report
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Generate Report</DialogTitle>
                                            <DialogDescription>
                                                Select the report type and date range to generate.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="space-y-4 py-4">
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Report Type</label>
                                                <select className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                                    <option value="sales">Sales Report</option>
                                                    <option value="revenue">Revenue Report</option>
                                                    <option value="users">Users Report</option>
                                                    <option value="activity">Activity Report</option>
                                                </select>
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Start Date</label>
                                                <Input type="date" />
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">End Date</label>
                                                <Input type="date" />
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setGenerateReportOpen(false)}>
                                                Cancel
                                            </Button>
                                            <Button onClick={() => {
                                                setGenerateReportOpen(false);
                                                testToast('info');
                                            }}>
                                                Generate Report
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <Dialog open={viewAnalyticsOpen} onOpenChange={setViewAnalyticsOpen}>
                                    <DialogTrigger asChild>
                                        <Button variant="ghost" className="w-full justify-start text-sm">
                                            View Analytics
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent className="max-w-2xl">
                                        <DialogHeader>
                                            <DialogTitle>Analytics Overview</DialogTitle>
                                            <DialogDescription>
                                                View detailed analytics and insights for your dashboard.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="space-y-4 py-4">
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
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setViewAnalyticsOpen(false)}>
                                                Close
                                            </Button>
                                            <Button onClick={() => {
                                                setViewAnalyticsOpen(false);
                                                testToast('info');
                                            }}>
                                                Export Data
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

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

                                <AlertDialog>
                                    <AlertDialogTrigger asChild>
                                        <Button variant="ghost" className="w-full justify-start text-sm text-destructive hover:text-destructive">
                                            Delete Account
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This action cannot be undone. This will permanently delete your account
                                                and remove all your data from our servers.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction
                                                onClick={() => {
                                                    testToast('error');
                                                }}
                                                className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                            >
                                                Delete Account
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>

                                <AlertDialog>
                                    <AlertDialogTrigger asChild>
                                        <Button variant="ghost" className="w-full justify-start text-sm">
                                            Clear All Data
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Clear all data?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This will clear all cached data and reset your preferences. You will need to
                                                reconfigure your settings after this action.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction
                                                onClick={() => {
                                                    testToast('warning');
                                                }}
                                            >
                                                Clear Data
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>System Status</CardTitle>
                            <CardDescription>Current system health</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div>
                                    <div className="flex justify-between text-sm mb-1">
                                        <span>CPU Usage</span>
                                        <span>45%</span>
                                    </div>
                                    <div className="h-2 bg-secondary rounded-full overflow-hidden">
                                        <div className="h-full bg-primary" style={{ width: '45%' }}></div>
                                    </div>
                                </div>
                                <div>
                                    <div className="flex justify-between text-sm mb-1">
                                        <span>Memory</span>
                                        <span>62%</span>
                                    </div>
                                    <div className="h-2 bg-secondary rounded-full overflow-hidden">
                                        <div className="h-full bg-primary" style={{ width: '62%' }}></div>
                                    </div>
                                </div>
                                <div>
                                    <div className="flex justify-between text-sm mb-1">
                                        <span>Storage</span>
                                        <span>38%</span>
                                    </div>
                                    <div className="h-2 bg-secondary rounded-full overflow-hidden">
                                        <div className="h-full bg-primary" style={{ width: '38%' }}></div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* TanStack Table Example */}
                <Card>
                    <CardHeader>
                        <CardTitle>Users Table</CardTitle>
                        <CardDescription>
                            Example table built with TanStack Table - supports search, sorting, and pagination
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {/* Search Input */}
                        <div className="mb-4">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Search users..."
                                    value={globalFilter ?? ''}
                                    onChange={(e) => setGlobalFilter(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                        </div>

                        <div className="rounded-md border overflow-x-auto">
                            <table className="w-full min-w-[640px]">
                                    <thead>
                                        {table.getHeaderGroups().map((headerGroup) => (
                                            <tr key={headerGroup.id} className="border-b">
                                                {headerGroup.headers.map((header) => (
                                                    <th
                                                        key={header.id}
                                                        className="h-12 px-4 text-left align-middle font-medium text-muted-foreground whitespace-nowrap"
                                                    >
                                                        {header.isPlaceholder ? null : (
                                                            <div
                                                                className={`flex items-center gap-2 ${
                                                                    header.column.getCanSort()
                                                                        ? 'cursor-pointer select-none hover:text-foreground'
                                                                        : ''
                                                                }`}
                                                                onClick={header.column.getToggleSortingHandler()}
                                                            >
                                                                {flexRender(
                                                                    header.column.columnDef.header,
                                                                    header.getContext()
                                                                )}
                                                                {header.column.getCanSort() && (
                                                                    <ArrowUpDown className="h-4 w-4 opacity-50" />
                                                                )}
                                                                {(() => {
                                                                    const sorted = header.column.getIsSorted();
                                                                    if (sorted === 'asc') return <ArrowUpRight className="h-4 w-4" />;
                                                                    if (sorted === 'desc') return <ArrowDownRight className="h-4 w-4" />;
                                                                    return null;
                                                                })()}
                                                            </div>
                                                        )}
                                                    </th>
                                                ))}
                                            </tr>
                                        ))}
                                    </thead>
                                    <tbody>
                                        {table.getRowModel().rows.map((row) => (
                                            <tr key={row.id} className="border-b transition-colors hover:bg-muted/50">
                                                {row.getVisibleCells().map((cell) => (
                                                    <td key={cell.id} className="p-4 align-middle whitespace-nowrap">
                                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                        </div>

                        {/* Pagination Controls */}
                        <div className="flex flex-col gap-4 mt-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-2 flex-wrap">
                                <button
                                    onClick={() => table.firstPage()}
                                    disabled={!table.getCanPreviousPage()}
                                    className="px-3 py-1 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-accent whitespace-nowrap"
                                >
                                    First
                                </button>
                                <button
                                    onClick={() => table.previousPage()}
                                    disabled={!table.getCanPreviousPage()}
                                    className="px-3 py-1 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-accent whitespace-nowrap"
                                >
                                    Previous
                                </button>
                                <button
                                    onClick={() => table.nextPage()}
                                    disabled={!table.getCanNextPage()}
                                    className="px-3 py-1 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-accent whitespace-nowrap"
                                >
                                    Next
                                </button>
                                <button
                                    onClick={() => table.lastPage()}
                                    disabled={!table.getCanNextPage()}
                                    className="px-3 py-1 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-accent whitespace-nowrap"
                                >
                                    Last
                                </button>
                            </div>

                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                                <span className="text-sm text-muted-foreground whitespace-nowrap">
                                    Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
                                </span>
                                <div className="relative">
                                    <select
                                        value={table.getState().pagination.pageSize}
                                        onChange={(e) => table.setPageSize(Number(e.target.value))}
                                        className="px-3 py-1 pr-8 text-sm border rounded-md bg-background cursor-pointer w-full sm:w-auto"
                                    >
                                        {[10, 20, 30, 50].map((pageSize) => (
                                            <option key={pageSize} value={pageSize}>
                                                Show {pageSize}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <span className="text-sm text-muted-foreground whitespace-nowrap">
                                    Showing {table.getRowModel().rows.length} of {table.getRowCount()} rows
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
