/**
 * Zod validation schemas for Budget forms
 * Used with react-hook-form via zodResolver
 * Supports both template and period edit modes
 *
 * Note: Budget 'name' field removed - category name serves as the identifier
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for creating a new budget template
 * One budget per category per user
 */
export const createBudgetSchema = z.object({
  category_id: z.union([z.string().min(1, 'Category is required'), z.number()]).transform(String),
  amount: z
    .string()
    .min(1, 'Budget amount is required')
    .refine(
      (val) => {
        const num = parseFloat(val.replace(/[^0-9.-]/g, ''));
        return !isNaN(num) && num > 0;
      },
      { message: 'Amount must be a positive number' }
    ),
  is_recurring: z.boolean().optional().default(true),
  rollover_enabled: z.boolean().optional().default(false),
});

/**
 * Schema for updating a budget template
 */
export const updateBudgetTemplateSchema = z.object({
  category_id: z.union([z.string(), z.number()]).optional().transform(String),
  amount: z
    .string()
    .min(1, 'Budget amount is required')
    .refine(
      (val) => {
        const num = parseFloat(val.replace(/[^0-9.-]/g, ''));
        return !isNaN(num) && num > 0;
      },
      { message: 'Amount must be a positive number' }
    ),
  is_recurring: z.boolean().optional().default(true),
  rollover_enabled: z.boolean().optional().default(false),
  update_type: z.literal('template'),
  period: z.string().optional().or(z.literal(null)),
});

/**
 * Schema for updating a specific budget period
 * Only amount and description can be changed
 */
export const updateBudgetPeriodSchema = z.object({
  category_id: z.union([z.string(), z.number()]).optional(),
  amount: z
    .string()
    .min(1, 'Budget amount is required')
    .refine(
      (val) => {
        const num = parseFloat(val.replace(/[^0-9.-]/g, ''));
        return !isNaN(num) && num > 0;
      },
      { message: 'Amount must be a positive number' }
    ),
  is_recurring: z.boolean().optional(),
  rollover_enabled: z.boolean().optional(),
  description: z
    .string()
    .max(2000, 'Description must not exceed 2000 characters')
    .optional()
    .or(z.literal('')),
  update_type: z.literal('period'),
  period: z.string().min(1, 'Period is required'),
});

/**
 * Zod resolver for create budget form
 */
export const createBudgetResolver = zodResolver(createBudgetSchema);

/**
 * Zod resolver for update budget template form
 */
export const updateBudgetTemplateResolver = zodResolver(updateBudgetTemplateSchema);

/**
 * Zod resolver for update budget period form
 */
export const updateBudgetPeriodResolver = zodResolver(updateBudgetPeriodSchema);

/**
 * Get the appropriate resolver based on edit mode
 * @param {boolean} isPeriodEdit - Whether editing a specific period
 * @returns {Function} Zod resolver
 */
export function getBudgetResolver(isPeriodEdit = false) {
  return isPeriodEdit ? updateBudgetPeriodResolver : updateBudgetTemplateResolver;
}
