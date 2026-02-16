/**
 * Goals Index page - List all savings goals
 */
import { useAppearance } from '@/Hooks/useAppearance';
import EmptyState from '@Treasury/Components/EmptyState';
import { getGoalIcon } from '@Treasury/Utils/goalIcons';
import { Link, router } from '@inertiajs/react';
import { CheckCircle, Eye, MoreVertical, Pencil, Plus, Target, Trash2, Wallet } from 'lucide-react';
import { useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import ProgressBar from '@/Components/Common/ProgressBar';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

export default function Index({ goals, wallets = [] }) {
  const { formatCurrency, formatDate } = useAppearance();
  const [deleteGoal, setDeleteGoal] = useState(null);

  const handleDelete = () => {
    if (deleteGoal) {
      router.delete(route('treasury.goals.destroy', deleteGoal.id), {
        onSuccess: () => setDeleteGoal(null),
      });
    }
  };

  // Context-aware delete message based on goal state
  const getDeleteMessage = (goal) => {
    if (!goal) return '';

    const savedAmount = parseFloat(goal.saved_amount) || 0;
    const formattedAmount = formatCurrency(savedAmount, goal.currency);

    if (goal.is_completed) {
      return `This completed goal has ${formattedAmount} allocated. The funds will remain in your savings wallet. Delete "${goal.name}"?`;
    } else if (savedAmount > 0) {
      return `This goal has ${formattedAmount} allocated (${goal.progress}% progress). The funds will remain in your savings wallet. Delete "${goal.name}"?`;
    }
    return `Are you sure you want to delete "${goal.name}"? This action cannot be undone.`;
  };

  const activeGoals = goals?.filter((g) => !g.is_completed) || [];
  const completedGoals = goals?.filter((g) => g.is_completed) || [];
  const hasSavingsWallet = wallets.some((w) => w.type === 'savings');

  const GoalCard = ({ goal }) => {
    // Get icon from category slug using goalIcons utility
    const IconComponent = getGoalIcon(goal.category?.slug);
    const categoryColor = goal.category?.color || '#6B7280';

    return (
      <Card className="hover:shadow-md transition-shadow overflow-hidden">
        {/* Color accent bar at top */}
        <div className="h-1" style={{ backgroundColor: categoryColor }} />

        <CardHeader className="pb-3">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-xl shrink-0" style={{ backgroundColor: categoryColor }}>
                {goal.is_completed ? (
                  <CheckCircle className="w-5 h-5 text-white" />
                ) : (
                  <IconComponent className="w-5 h-5 text-white" />
                )}
              </div>
              <div className="min-w-0">
                <Link href={route('treasury.goals.show', goal.id)}>
                  <h3 className="font-semibold hover:underline truncate">{goal.name}</h3>
                </Link>
                {goal.category && (
                  <p className="text-sm text-muted-foreground">{goal.category.name}</p>
                )}
              </div>
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="shrink-0">
                  <MoreVertical className="w-4 h-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem asChild>
                  <Link href={route('treasury.goals.show', goal.id)}>
                    <Eye className="w-4 h-4 mr-2" />
                    View Details
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link href={route('treasury.goals.edit', goal.id)}>
                    <Pencil className="w-4 h-4 mr-2" />
                    Edit
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem className="text-destructive" onClick={() => setDeleteGoal(goal)}>
                  <Trash2 className="w-4 h-4 mr-2" />
                  Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </CardHeader>

        <CardContent className="space-y-4">
          {/* Progress section */}
          <div className="space-y-2">
            <div className="flex justify-between text-sm">
              <span className="text-muted-foreground">{goal.progress}% complete</span>
              <span className="font-medium">
                {formatCurrency(goal.saved_amount ?? 0, goal.currency)} /{' '}
                {formatCurrency(goal.target_amount, goal.currency)}
              </span>
            </div>
            <ProgressBar value={goal.progress} />
          </div>

          {/* Footer info */}
          <div className="flex items-center justify-between text-xs text-muted-foreground pt-2 border-t">
            {goal.wallet ? (
              <div className="flex items-center gap-1.5">
                <Wallet className="w-3.5 h-3.5" />
                <span>{goal.wallet.name}</span>
              </div>
            ) : (
              <span>No wallet linked</span>
            )}
            {goal.deadline && (
              <span className={goal.is_overdue ? 'text-red-500 font-medium' : ''}>
                Due: {formatDate(goal.deadline)}
              </span>
            )}
          </div>
        </CardContent>
      </Card>
    );
  };

  return (
    <PageShell
      title="Savings Goals"
      description="Track your financial goals"
      actions={
        hasSavingsWallet ? (
          <Link href={route('treasury.goals.create')}>
            <Button>
              <Plus className="w-4 h-4 mr-2" />
              New Goal
            </Button>
          </Link>
        ) : (
          <div className="relative group">
            <Button disabled className="opacity-50 cursor-not-allowed">
              <Plus className="w-4 h-4 mr-2" />
              New Goal
            </Button>
            <div className="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-3 py-2 bg-popover border rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">
              <p className="text-sm font-medium">Create a savings wallet first</p>
              <p className="text-xs text-muted-foreground">
                You need a savings wallet to create goals
              </p>
              <div className="absolute left-full top-1/2 -translate-y-1/2 -ml-1 border-8 border-transparent border-l-popover" />
            </div>
          </div>
        )
      }
    >
      <Tabs defaultValue="active">
        <TabsList className="mb-6">
          <TabsTrigger value="active">Active ({activeGoals.length})</TabsTrigger>
          <TabsTrigger value="completed">Completed ({completedGoals.length})</TabsTrigger>
        </TabsList>

        <TabsContent value="active">
          {activeGoals.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {activeGoals.map((goal) => (
                <GoalCard key={goal.id} goal={goal} />
              ))}
            </div>
          ) : (
            <Card>
              <CardContent className="py-12">
                <EmptyState icon={Target} message="Create a goal to start saving." />
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="completed">
          {completedGoals.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {completedGoals.map((goal) => (
                <GoalCard key={goal.id} goal={goal} />
              ))}
            </div>
          ) : (
            <Card>
              <CardContent className="py-12">
                <EmptyState icon={CheckCircle} message="No completed goals yet. Keep saving!" />
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>

      <ConfirmDialog
        isOpen={!!deleteGoal}
        onCancel={() => setDeleteGoal(null)}
        title="Delete Goal"
        message={getDeleteMessage(deleteGoal)}
        onConfirm={handleDelete}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
