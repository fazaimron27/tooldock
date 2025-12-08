/**
 * Zod validation schemas for vault forms
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Base vault schema with common fields
 */
const baseVaultSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
  // Note: Types are defined in backend Vault::TYPES constant
  // This enum should match: ['login', 'card', 'note', 'server']
  type: z.enum(['login', 'card', 'note', 'server'], {
    required_error: 'Type is required',
    invalid_type_error: 'Type must be one of: login, card, note, server',
  }),
  value: z.string().optional().or(z.literal('')),
  username: z
    .string()
    .max(255, 'Username must not exceed 255 characters')
    .optional()
    .or(z.literal('')),
  email: z
    .union([
      z
        .string()
        .email('Email must be a valid email address')
        .max(255, 'Email must not exceed 255 characters'),
      z.literal(''),
    ])
    .optional(),
  issuer: z.string().max(255, 'Issuer must not exceed 255 characters').optional().or(z.literal('')),
  url: z
    .union([
      z.string().url('URL must be a valid URL').max(2048, 'URL must not exceed 2048 characters'),
      z.literal(''),
    ])
    .optional(),
  totp_secret: z.string().optional().or(z.literal('')),
  fields: z
    .object({
      // Credit Card fields
      card_number: z
        .string()
        .max(23, 'Card number must not exceed 23 characters')
        .optional()
        .or(z.literal('')),
      cardholder_name: z
        .string()
        .max(255, 'Cardholder name must not exceed 255 characters')
        .optional()
        .or(z.literal('')),
      expiration_month: z.string().max(2, 'Month must be 2 digits').optional().or(z.literal('')),
      expiration_year: z.string().max(2, 'Year must be 2 digits').optional().or(z.literal('')),
      cvv: z.string().max(4, 'CVV must not exceed 4 digits').optional().or(z.literal('')),
      card_type: z
        .enum(['visa', 'mastercard', 'amex', 'discover', 'jcb', 'diners', 'other'], {
          errorMap: () => ({ message: 'Invalid card type' }),
        })
        .optional()
        .or(z.literal('')),
      billing_address: z
        .string()
        .max(1000, 'Billing address must not exceed 1000 characters')
        .optional()
        .or(z.literal('')),
      // Server fields
      host: z.string().max(255, 'Host must not exceed 255 characters').optional().or(z.literal('')),
      port: z
        .string()
        .regex(/^[1-9]\d{0,4}$|^$/, 'Port must be between 1 and 65535')
        .optional()
        .or(z.literal('')),
      protocol: z
        .enum(
          [
            'ssh',
            'ftp',
            'ftps',
            'sftp',
            'rdp',
            'vnc',
            'telnet',
            'http',
            'https',
            'mysql',
            'postgresql',
            'mongodb',
            'redis',
            'other',
          ],
          {
            errorMap: () => ({ message: 'Invalid protocol' }),
          }
        )
        .optional()
        .or(z.literal('')),
      server_notes: z
        .string()
        .max(2000, 'Server notes must not exceed 2000 characters')
        .optional()
        .or(z.literal('')),
    })
    .optional()
    .nullable()
    .transform((val) => {
      // Convert empty objects to null
      if (val && typeof val === 'object' && Object.keys(val).length === 0) {
        return null;
      }
      return val;
    }),
  category_id: z
    .union([z.string().uuid('Category ID must be a valid UUID'), z.literal(''), z.literal(null)])
    .optional(),
  is_favorite: z.boolean().optional().default(false),
});

/**
 * Schema for creating a new vault
 */
export const createVaultSchema = baseVaultSchema;

/**
 * Schema for updating an existing vault
 */
export const updateVaultSchema = baseVaultSchema;

/**
 * Zod resolver for create vault form
 */
export const createVaultResolver = zodResolver(createVaultSchema);

/**
 * Zod resolver for update vault form
 */
export const updateVaultResolver = zodResolver(updateVaultSchema);
