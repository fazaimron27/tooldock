/**
 * Zod validation schemas for Goal forms
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Base goal schema with common fields
 */
const baseGoalSchema = z.object({
  name: z.string().min(1, 'Goal name is required').max(255, 'Name must not exceed 255 characters'),
  wallet_id: z
    .union([z.string().min(1, 'Savings wallet is required'), z.number()])
    .transform(String),
  category_id: z
    .union([z.string(), z.number(), z.literal(''), z.literal(null)])
    .optional()
    .transform((val) => (val === '' || val === 'none' ? null : val)),
  target_amount: z
    .string()
    .min(1, 'Target amount is required')
    .refine(
      (val) => {
        const num = parseFloat(val.replace(/[^0-9.-]/g, ''));
        return !isNaN(num) && num > 0;
      },
      { message: 'Target amount must be a positive number' }
    ),
  deadline: z.string().optional().or(z.literal('')),
  description: z
    .string()
    .max(2000, 'Description must not exceed 2000 characters')
    .optional()
    .or(z.literal('')),
});

/**
 * Schema for creating a new goal
 */
export const createGoalSchema = baseGoalSchema;

/**
 * Schema for updating an existing goal
 * Includes is_completed field for edit mode
 */
export const updateGoalSchema = baseGoalSchema.extend({
  is_completed: z.boolean().optional().default(false),
});

/**
 * Zod resolver for create goal form
 */
export const createGoalResolver = zodResolver(createGoalSchema);

/**
 * Zod resolver for update goal form
 */
export const updateGoalResolver = zodResolver(updateGoalSchema);
