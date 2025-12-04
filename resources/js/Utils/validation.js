/**
 * Reusable Zod validation schemas and utilities
 * Provides common validation patterns for forms across the application
 */
import { z } from 'zod';

/**
 * Email validation schema
 */
export const emailSchema = z.string().email('Invalid email address');

/**
 * Password validation schema with strength requirements
 */
export const passwordSchema = z
  .string()
  .min(8, 'Password must be at least 8 characters')
  .max(255, 'Password must not exceed 255 characters');

/**
 * Name validation schema
 */
export const nameSchema = z
  .string()
  .min(1, 'Name is required')
  .max(255, 'Name must not exceed 255 characters');

/**
 * Optional name schema for updates
 */
export const optionalNameSchema = z
  .string()
  .max(255, 'Name must not exceed 255 characters')
  .optional();

/**
 * Optional email schema for updates
 */
export const optionalEmailSchema = z.string().email('Invalid email address').optional();

/**
 * Optional password schema for updates
 */
export const optionalPasswordSchema = z
  .string()
  .min(8, 'Password must be at least 8 characters')
  .max(255, 'Password must not exceed 255 characters')
  .optional();

/**
 * Helper to create a password confirmation schema
 * Validates that password and password_confirmation match
 */
export function createPasswordConfirmationSchema(passwordField = 'password') {
  return z
    .object({
      [passwordField]: passwordSchema,
      password_confirmation: z.string(),
    })
    .refine((data) => data[passwordField] === data.password_confirmation, {
      message: "Passwords don't match",
      path: ['password_confirmation'],
    });
}

/**
 * Helper to create an optional password confirmation schema for updates
 */
export function createOptionalPasswordConfirmationSchema(passwordField = 'password') {
  return z
    .object({
      [passwordField]: optionalPasswordSchema,
      password_confirmation: z.string().optional(),
    })
    .refine(
      (data) => {
        if (data[passwordField] && !data.password_confirmation) {
          return false;
        }
        if (data[passwordField] && data.password_confirmation) {
          return data[passwordField] === data.password_confirmation;
        }
        return true;
      },
      {
        message: "Passwords don't match",
        path: ['password_confirmation'],
      }
    );
}
