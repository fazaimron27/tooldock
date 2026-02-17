/**
 * Routine Index Page
 *
 * Main habit tracker view with stats, habit list, and heatmap.
 * Uses FormDialog for create/edit, ConfirmDialog for delete,
 * and FormFieldRHF with react-hook-form for form management.
 */
import { useDisclosure } from '@/Hooks/useDisclosure';
import ContributionHeatmap from '@Routine/Components/ContributionHeatmap';
import HabitRow from '@Routine/Components/HabitRow';
import { getHabitIcon, habitIconSlugs } from '@Routine/Utils/habitIcons';
import { router, usePage } from '@inertiajs/react';
import {
  addWeeks,
  eachDayOfInterval,
  endOfWeek,
  format,
  startOfToday,
  startOfWeek,
} from 'date-fns';
import {
  Archive,
  BarChart3,
  CheckSquare,
  ChevronLeft,
  ChevronRight,
  Flame,
  Pause,
  Plus,
  Repeat,
  Target,
  TrendingUp,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import FormDialog from '@/Components/Common/FormDialog';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import MetricCard from '@/Components/DataDisplay/MetricCard';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

const FORM_ID = 'habit-form';

const PRESET_COLORS = [
  '#10b981',
  '#3b82f6',
  '#f59e0b',
  '#ef4444',
  '#8b5cf6',
  '#ec4899',
  '#14b8a6',
  '#f97316',
];

/**
 * Map day names to date-fns weekStartsOn values.
 * @type {Object.<string, number>}
 */
const WEEK_START_MAP = {
  sunday: 0,
  monday: 1,
  tuesday: 2,
  wednesday: 3,
  thursday: 4,
  friday: 5,
  saturday: 6,
};

/**
 * Get default form values for a habit
 * @param {Object|null} habit - Existing habit for edit mode
 * @param {Object} settings - Module settings from backend
 * @returns {Object} Form default values
 */
function getHabitDefaults(habit = null, settings = {}) {
  if (habit) {
    return {
      name: habit.name || '',
      type: habit.type || 'boolean',
      icon: habit.icon || 'target',
      color: habit.color || '#10b981',
      goal_per_week: String(habit.goal_per_week || 7),
      unit: habit.unit || '',
      target_value: habit.target_value ? String(parseFloat(habit.target_value)) : '',
    };
  }
  return {
    name: '',
    type: settings.default_habit_type || 'boolean',
    icon: 'target',
    color: '#10b981',
    goal_per_week: String(settings.default_goal_per_week || 7),
    unit: '',
    target_value: '',
  };
}

export default function Index() {
  const { habits, inactiveHabits = [], stats, settings = {} } = usePage().props;
  const weekStartsOn = WEEK_START_MAP[settings.week_start?.toLowerCase()] ?? 1;
  const formDialog = useDisclosure();
  const [editingHabit, setEditingHabit] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [activeTab, setActiveTab] = useState('active');
  const [weekOffset, setWeekOffset] = useState(0);

  const { control, handleSubmit, reset, watch, setValue } = useForm({
    defaultValues: getHabitDefaults(null, settings),
  });

  const watchedIcon = watch('icon');
  const watchedColor = watch('color');
  const watchedType = watch('type');

  const today = useMemo(() => startOfToday(), []);
  const isCurrentWeek = weekOffset === 0;

  const days = useMemo(() => {
    const base = addWeeks(today, weekOffset);
    const weekStart = startOfWeek(base, { weekStartsOn });
    const weekEnd = endOfWeek(base, { weekStartsOn });
    return eachDayOfInterval({ start: weekStart, end: weekEnd });
  }, [today, weekOffset, weekStartsOn]);

  const weekLabel = useMemo(() => {
    const start = days[0];
    const end = days[days.length - 1];
    const sameMonth = format(start, 'MMM') === format(end, 'MMM');
    if (sameMonth) {
      return `${format(start, 'MMM d')} – ${format(end, 'd, yyyy')}`;
    }
    return `${format(start, 'MMM d')} – ${format(end, 'MMM d, yyyy')}`;
  }, [days]);

  const openCreate = useCallback(() => {
    setEditingHabit(null);
    reset(getHabitDefaults(null, settings));
    formDialog.onOpen();
  }, [reset, formDialog, settings]);

  const openEdit = useCallback(
    (habit) => {
      setEditingHabit(habit);
      reset(getHabitDefaults(habit, settings));
      formDialog.onOpen();
    },
    [reset, formDialog, settings]
  );

  const onSubmit = useCallback(
    (data) => {
      setIsSubmitting(true);
      const payload = {
        ...data,
        goal_per_week: parseInt(data.goal_per_week, 10),
        target_value:
          data.type === 'measurable' && data.target_value ? parseFloat(data.target_value) : null,
        unit: data.type === 'measurable' ? data.unit : null,
      };

      const options = {
        onSuccess: () => {
          formDialog.onClose();
          setEditingHabit(null);
          reset(getHabitDefaults());
        },
        onFinish: () => setIsSubmitting(false),
      };

      if (editingHabit) {
        router.put(route('routine.update', editingHabit.id), payload, options);
      } else {
        router.post(route('routine.store'), payload, options);
      }
    },
    [editingHabit, formDialog, reset]
  );

  const handleDelete = useCallback(() => {
    if (deleteTarget) {
      router.delete(route('routine.destroy', deleteTarget.id), {
        onSuccess: () => setDeleteTarget(null),
      });
    }
  }, [deleteTarget]);

  const handleStatusChange = useCallback((habit, status) => {
    router.put(route('routine.update', habit.id), { status });
  }, []);

  const displayedHabits =
    activeTab === 'active' ? habits : inactiveHabits.filter((h) => h.status === activeTab);

  const pausedCount = inactiveHabits.filter((h) => h.status === 'paused').length;
  const archivedCount = inactiveHabits.filter((h) => h.status === 'archived').length;

  return (
    <PageShell
      title="Routine"
      description="Track your daily habits and build consistency."
      actions={
        <Button onClick={openCreate}>
          <Plus className="mr-2 h-4 w-4" />
          Add Habit
        </Button>
      }
    >
      <div className="space-y-6">
        {/* Stats Row */}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <MetricCard title="Active Habits" icon={Repeat}>
            <div className="text-2xl font-bold">{stats.total_habits}</div>
            <p className="text-xs text-muted-foreground">habits tracked</p>
          </MetricCard>
          <MetricCard title="Current Streak" icon={Flame}>
            <div className="text-2xl font-bold">{stats.best_streak}</div>
            <p className="text-xs text-muted-foreground">highest among active habits</p>
          </MetricCard>
          <MetricCard title="Weekly Progress" icon={Target}>
            <div className="text-2xl font-bold">
              {stats.weekly_completions}
              <span className="text-base font-normal text-muted-foreground">
                /{stats.weekly_goal}
              </span>
            </div>
            <p className="text-xs text-muted-foreground">completions this week</p>
          </MetricCard>
          <MetricCard title="Weekly Rate" icon={TrendingUp}>
            <div className="text-2xl font-bold">{stats.weekly_rate}%</div>
            <p className="text-xs text-muted-foreground">goal completion rate</p>
          </MetricCard>
        </div>

        {/* Habits List */}
        <Card>
          <CardHeader className="flex-row items-center justify-between space-y-0">
            <div>
              <CardTitle className="text-lg font-semibold">Your Habits</CardTitle>
              <CardDescription>Track your weekly progress at a glance</CardDescription>
            </div>
            <Tabs value={activeTab} onValueChange={setActiveTab} className="w-auto">
              <TabsList>
                <TabsTrigger value="active">Active ({habits.length})</TabsTrigger>
                <TabsTrigger value="paused">Paused ({pausedCount})</TabsTrigger>
                <TabsTrigger value="archived">Archived ({archivedCount})</TabsTrigger>
              </TabsList>
            </Tabs>
          </CardHeader>
          <CardContent className="space-y-0">
            {displayedHabits.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-12 text-center">
                {activeTab === 'active' ? (
                  <>
                    <Repeat className="mb-4 h-12 w-12 text-muted-foreground/30" />
                    <p className="text-lg font-medium text-muted-foreground">No habits yet</p>
                    <p className="mt-1 text-sm text-muted-foreground/70">
                      Create your first habit to start building consistency.
                    </p>
                  </>
                ) : (
                  <>
                    {activeTab === 'paused' ? (
                      <Pause className="mb-4 h-12 w-12 text-muted-foreground/30" />
                    ) : (
                      <Archive className="mb-4 h-12 w-12 text-muted-foreground/30" />
                    )}
                    <p className="text-lg font-medium text-muted-foreground">
                      No {activeTab} habits
                    </p>
                  </>
                )}
              </div>
            ) : (
              <>
                {activeTab === 'active' && (
                  <>
                    {/* Week Navigation */}
                    <div className="flex items-center justify-end px-3 pb-3 mb-1">
                      <div className="inline-flex items-center gap-1 rounded-lg border bg-muted/40 p-1">
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-7 w-7"
                          onClick={() => setWeekOffset((prev) => prev - 1)}
                        >
                          <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <span className="min-w-[140px] text-center text-sm font-medium">
                          {weekLabel}
                        </span>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-7 w-7"
                          onClick={() => setWeekOffset((prev) => prev + 1)}
                          disabled={isCurrentWeek}
                        >
                          <ChevronRight className="h-4 w-4" />
                        </Button>
                        {!isCurrentWeek && (
                          <Button
                            variant="secondary"
                            size="sm"
                            className="h-7 text-xs"
                            onClick={() => setWeekOffset(0)}
                          >
                            Today
                          </Button>
                        )}
                      </div>
                    </div>

                    {/* Day Column Headers */}
                    <div className="flex items-center gap-3 border-b px-3 pb-2 mb-1">
                      <div className="w-56 shrink-0" />
                      <div className="flex flex-1 items-center gap-1">
                        {days.map((day) => (
                          <div
                            key={format(day, 'yyyy-MM-dd')}
                            className="flex flex-1 shrink-0 flex-col items-center justify-center text-center"
                          >
                            <span className="text-[10px] font-medium uppercase text-muted-foreground">
                              {format(day, 'EEE')}
                            </span>
                            <span className="text-xs text-muted-foreground/70">
                              {format(day, 'd')}
                            </span>
                          </div>
                        ))}
                      </div>
                      <div className="w-7 shrink-0" />
                    </div>
                  </>
                )}

                {/* Habit Rows */}
                {displayedHabits.map((habit) => (
                  <HabitRow
                    key={habit.id}
                    habit={habit}
                    days={activeTab === 'active' ? days : []}
                    onEdit={openEdit}
                    onDelete={setDeleteTarget}
                    onStatusChange={handleStatusChange}
                  />
                ))}
              </>
            )}
          </CardContent>
        </Card>

        {/* Contribution Heatmap */}
        {habits.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="text-lg font-semibold">Consistency Tracker</CardTitle>
              <CardDescription>Your daily habit activity over the last year</CardDescription>
            </CardHeader>
            <CardContent>
              <ContributionHeatmap habits={habits} />
            </CardContent>
          </Card>
        )}
      </div>

      {/* Create / Edit Habit Dialog */}
      <FormDialog
        open={formDialog.isOpen}
        onOpenChange={(open) => !open && formDialog.onClose()}
        onCancel={() => {
          formDialog.onClose();
          setEditingHabit(null);
          reset(getHabitDefaults());
        }}
        title={editingHabit ? 'Edit Habit' : 'New Habit'}
        description={
          editingHabit ? 'Update your habit details.' : 'Create a new daily habit to track.'
        }
        formId={FORM_ID}
        confirmLabel={editingHabit ? 'Save Changes' : 'Create Habit'}
        processing={isSubmitting}
        processingLabel={editingHabit ? 'Saving...' : 'Creating...'}
      >
        <form id={FORM_ID} onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <FormFieldRHF
            name="name"
            control={control}
            label="Name"
            required
            placeholder="e.g. Morning Exercise"
            rules={{ required: 'Habit name is required' }}
          />

          {/* Habit Type */}
          <div className="space-y-2">
            <Label>Tracking Type</Label>
            <Controller
              name="type"
              control={control}
              render={({ field }) => (
                <div className="flex gap-2">
                  <button
                    type="button"
                    className={`flex-1 rounded-lg border-2 px-3 py-2 text-sm font-medium transition-colors ${
                      field.value === 'boolean'
                        ? 'border-primary bg-primary/10 text-primary'
                        : 'border-muted text-muted-foreground hover:border-muted-foreground/30'
                    }`}
                    onClick={() => field.onChange('boolean')}
                  >
                    <CheckSquare className="mr-1.5 inline h-4 w-4" />
                    Yes / No
                  </button>
                  <button
                    type="button"
                    className={`flex-1 rounded-lg border-2 px-3 py-2 text-sm font-medium transition-colors ${
                      field.value === 'measurable'
                        ? 'border-primary bg-primary/10 text-primary'
                        : 'border-muted text-muted-foreground hover:border-muted-foreground/30'
                    }`}
                    onClick={() => field.onChange('measurable')}
                  >
                    <BarChart3 className="mr-1.5 inline h-4 w-4" />
                    Track Value
                  </button>
                </div>
              )}
            />
          </div>

          {/* Measurable Fields (conditional) */}
          {watchedType === 'measurable' && (
            <div className="space-y-3 rounded-lg border border-dashed p-3">
              <FormFieldRHF
                name="unit"
                control={control}
                label="Unit"
                placeholder="e.g. menit, jam, pages"
                rules={{ required: watchedType === 'measurable' ? 'Unit is required' : false }}
              />
              <div className="space-y-2">
                <Label>Daily Target</Label>
                <Controller
                  name="target_value"
                  control={control}
                  render={({ field }) => (
                    <Input
                      type="number"
                      step="0.1"
                      min="0"
                      placeholder="e.g. 30"
                      value={field.value}
                      onChange={field.onChange}
                    />
                  )}
                />
                <p className="text-xs text-muted-foreground">
                  Optional. If set, a day counts as &quot;complete&quot; only when this target is
                  met.
                </p>
              </div>
            </div>
          )}

          {/* Icon Picker */}
          <div className="space-y-2">
            <Label>Icon</Label>
            <TooltipProvider delayDuration={200}>
              <div className="flex flex-wrap gap-1.5">
                {habitIconSlugs.map((slug) => {
                  const IconComp = getHabitIcon(slug);
                  return (
                    <Tooltip key={slug}>
                      <TooltipTrigger asChild>
                        <button
                          type="button"
                          className={`flex h-9 w-9 items-center justify-center rounded-lg border-2 transition-colors ${
                            watchedIcon === slug
                              ? 'border-primary bg-primary/10 text-primary'
                              : 'border-muted text-muted-foreground hover:border-muted-foreground/30 hover:text-foreground'
                          }`}
                          onClick={() => setValue('icon', slug)}
                        >
                          <IconComp className="h-4 w-4" />
                        </button>
                      </TooltipTrigger>
                      <TooltipContent side="top" className="text-xs capitalize">
                        {slug.replace(/-/g, ' ')}
                      </TooltipContent>
                    </Tooltip>
                  );
                })}
              </div>
            </TooltipProvider>
          </div>

          {/* Color Picker */}
          <div className="space-y-2">
            <Label>Color</Label>
            <div className="flex flex-wrap gap-2">
              {PRESET_COLORS.map((color) => (
                <button
                  key={color}
                  type="button"
                  className={`h-8 w-8 rounded-full border-2 transition-transform ${
                    watchedColor === color
                      ? 'scale-110 border-foreground'
                      : 'border-transparent hover:scale-105'
                  }`}
                  style={{ backgroundColor: color }}
                  onClick={() => setValue('color', color)}
                />
              ))}
            </div>
          </div>

          {/* Weekly Goal */}
          <div className="space-y-2">
            <Label>Weekly Goal (days)</Label>
            <Controller
              name="goal_per_week"
              control={control}
              render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {[1, 2, 3, 4, 5, 6, 7].map((n) => (
                      <SelectItem key={n} value={String(n)}>
                        {n} {n === 1 ? 'day' : 'days'} / week
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
          </div>
        </form>
      </FormDialog>

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        isOpen={!!deleteTarget}
        onCancel={() => setDeleteTarget(null)}
        title="Delete Habit"
        message={`Are you sure you want to delete "${deleteTarget?.name}"? This action cannot be undone and all completion history will be lost.`}
        onConfirm={handleDelete}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
