/**
 * Zod validation schemas for Wallet forms
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Base wallet schema with common fields
 */
const baseWalletSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
  type: z.string().min(1, 'Wallet type is required'),
  currency: z
    .string()
    .min(1, 'Currency is required')
    .max(10, 'Currency code must not exceed 10 characters'),
  balance: z
    .string()
    .optional()
    .or(z.literal(''))
    .transform((val) => (val === '' ? '0' : val)),
  description: z
    .string()
    .max(1000, 'Description must not exceed 1000 characters')
    .optional()
    .or(z.literal('')),
});

/**
 * Schema for creating a new wallet
 */
export const createWalletSchema = baseWalletSchema;

/**
 * Schema for updating an existing wallet
 * Includes is_active field for edit mode
 */
export const updateWalletSchema = baseWalletSchema.extend({
  is_active: z.boolean().optional().default(true),
});

/**
 * Zod resolver for create wallet form
 */
export const createWalletResolver = zodResolver(createWalletSchema);

/**
 * Zod resolver for update wallet form
 */
export const updateWalletResolver = zodResolver(updateWalletSchema);
