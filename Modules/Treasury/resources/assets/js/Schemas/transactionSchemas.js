/**
 * Zod validation schemas for Transaction forms
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Transaction schema with conditional validation for transfers
 */
const transactionSchema = z
  .object({
    type: z.enum(['expense', 'income', 'transfer'], {
      required_error: 'Transaction type is required',
      invalid_type_error: 'Type must be one of: expense, income, transfer',
    }),
    wallet_id: z.union([z.string().min(1, 'Wallet is required'), z.number()]).transform(String),
    destination_wallet_id: z
      .union([z.string(), z.number(), z.literal(''), z.literal(null)])
      .optional()
      .transform((val) => (val === '' ? null : val ? String(val) : null)),
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
    fee: z
      .string()
      .optional()
      .or(z.literal(''))
      .transform((val) => val || '0')
      .refine(
        (val) => {
          const num = parseFloat(val.replace(/[^0-9.-]/g, ''));
          return isNaN(num) || num >= 0;
        },
        { message: 'Fee must be a non-negative number' }
      ),
    name: z.string().max(100, 'Name must not exceed 100 characters').optional().or(z.literal('')),
    date: z.string().min(1, 'Date is required'),
    category_id: z
      .union([z.string(), z.number(), z.literal(''), z.literal(null)])
      .optional()
      .transform((val) => (val === '' ? null : val ? String(val) : null)),
    description: z
      .string()
      .max(2000, 'Description must not exceed 2000 characters')
      .optional()
      .or(z.literal('')),
    attachment_ids: z
      .array(z.union([z.string(), z.number()]))
      .optional()
      .default([]),
    remove_attachment_ids: z
      .array(z.union([z.string(), z.number()]))
      .optional()
      .default([]),
  })
  .refine(
    (data) => {
      // Destination wallet is required for transfers
      if (data.type === 'transfer' && !data.destination_wallet_id) {
        return false;
      }
      return true;
    },
    {
      message: 'Destination wallet is required for transfers',
      path: ['destination_wallet_id'],
    }
  )
  .refine(
    (data) => {
      // Source and destination cannot be the same for transfers
      if (data.type === 'transfer' && data.wallet_id === data.destination_wallet_id) {
        return false;
      }
      return true;
    },
    {
      message: 'Source and destination wallets must be different',
      path: ['destination_wallet_id'],
    }
  );

/**
 * Schema for creating a new transaction
 */
export const createTransactionSchema = transactionSchema;

/**
 * Schema for updating an existing transaction
 */
export const updateTransactionSchema = transactionSchema;

/**
 * Zod resolver for create transaction form
 */
export const createTransactionResolver = zodResolver(createTransactionSchema);

/**
 * Zod resolver for update transaction form
 */
export const updateTransactionResolver = zodResolver(updateTransactionSchema);
