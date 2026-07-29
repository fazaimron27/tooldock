/**
 * Zod validation schemas for Routine module habit forms.
 *
 * Handles both boolean (yes/no) and measurable habit types.
 * Conditional validation enforces that "unit" is required only for
 * measurable habits, using z.discriminatedUnion for clean branching.
 *
 * Used with react-hook-form via zodResolver.
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const nameField = z
  .string()
  .min(1, 'Habit name is required')
  .max(255, 'Name must not exceed 255 characters');

const iconField = z.string().min(1, 'Icon is required');

const colorField = z
  .string()
  .regex(/^#[A-Fa-f0-9]{6}$/, 'Color must be a valid six-digit hex color');

const goalPerWeekField = z
  .string()
  .or(z.number())
  .refine((val) => {
    const n = parseInt(val, 10);
    return n >= 1 && n <= 7;
  }, 'Goal must be between 1 and 7 days per week');

const categoryIdField = z.string().optional().or(z.literal(''));

/**
 * Boolean habit — tracked as done/not done.
 * No unit or target_value required.
 */
const booleanHabitFields = z.object({
  type: z.literal('boolean'),
  unit: z.string().optional().or(z.literal('')),
  target_value: z.string().optional().or(z.literal('')),
});

/**
 * Measurable habit — tracked against a numeric target.
 * Unit is required; target_value is optional.
 */
const measurableHabitFields = z.object({
  type: z.literal('measurable'),
  unit: z
    .string()
    .min(1, 'Unit is required for measurable habits')
    .max(50, 'Unit must not exceed 50 characters'),
  target_value: z
    .string()
    .optional()
    .or(z.literal(''))
    .refine((val) => {
      if (!val) {
        return true;
      }

      const targetValue = Number(val);

      return Number.isFinite(targetValue) && targetValue >= 0;
    }, 'Target value must be a non-negative number'),
});

const habitTypeUnion = z.discriminatedUnion('type', [booleanHabitFields, measurableHabitFields]);

/**
 * Schema for creating or editing a habit.
 * Intersects shared fields with the type-discriminated union.
 */
export const habitSchema = z.intersection(
  z.object({
    name: nameField,
    icon: iconField,
    color: colorField,
    goal_per_week: goalPerWeekField,
    category_id: categoryIdField,
  }),
  habitTypeUnion
);

/**
 * Alias — update uses the same shape (all fields editable).
 */
export const updateHabitSchema = habitSchema;

/**
 * Zod resolver for the create habit form.
 * Integrates habitSchema with react-hook-form.
 */
export const createHabitResolver = zodResolver(habitSchema);

/**
 * Zod resolver for the update habit form.
 * Same schema as create since all fields are editable.
 */
export const updateHabitResolver = zodResolver(updateHabitSchema);
