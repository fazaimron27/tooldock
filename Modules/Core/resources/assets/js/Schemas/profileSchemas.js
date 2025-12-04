/**
 * Zod validation schemas for profile forms
 * Used with react-hook-form via zodResolver
 */
import { emailSchema, nameSchema, passwordSchema } from '@/Utils/validation';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for updating password
 * Requires current password and validates new password matches confirmation
 */
export const updatePasswordSchema = z
  .object({
    current_password: z.string().min(1, 'Current password is required'),
    password: passwordSchema,
    password_confirmation: z.string().min(1, 'Password confirmation is required'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords don't match",
    path: ['password_confirmation'],
  });

/**
 * Schema for updating profile information
 */
export const updateProfileSchema = z.object({
  name: nameSchema,
  email: emailSchema,
  avatar_id: z.number().nullable().optional(),
});

/**
 * Zod resolver for update password form
 */
export const updatePasswordResolver = zodResolver(updatePasswordSchema);

/**
 * Zod resolver for update profile form
 */
export const updateProfileResolver = zodResolver(updateProfileSchema);
