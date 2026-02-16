/**
 * Zod validation schemas for Goal Allocation form (inline on Show page)
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for allocating funds to a goal
 */
export const allocateSchema = z.object({
  wallet_id: z
    .union([z.string().min(1, 'Source wallet is required'), z.number()])
    .transform(String),
  amount: z
    .string()
    .min(1, 'Amount is required')
    .refine(
      (val) => {
        const num = parseFloat(val.replace(/[^0-9.-]/g, ''));
        return !isNaN(num) && num > 0;
      },
      { message: 'Amount must be a positive number' }
    ),
  description: z
    .string()
    .max(500, 'Description must not exceed 500 characters')
    .optional()
    .or(z.literal('')),
});

/**
 * Zod resolver for allocate form
 */
export const allocateResolver = zodResolver(allocateSchema);
