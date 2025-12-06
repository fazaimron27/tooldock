/**
 * Zod validation schemas for user forms
 * Used with react-hook-form via zodResolver
 */
import { emailSchema, nameSchema, passwordSchema } from '@/Utils/validation';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for creating a new user
 */
export const createUserSchema = z
  .object({
    name: nameSchema,
    email: emailSchema,
    password: passwordSchema,
    password_confirmation: z.string(),
    roles: z.array(z.string()).optional(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords don't match",
    path: ['password_confirmation'],
  });

/**
 * Schema for updating an existing user
 * Password is optional - empty strings are filtered out by useInertiaForm
 */
export const updateUserSchema = z
  .object({
    name: nameSchema,
    email: emailSchema,
    password: z.string().optional().or(z.literal('')),
    password_confirmation: z.string().optional().or(z.literal('')),
    roles: z.array(z.string()).optional(),
  })
  .refine(
    (data) => {
      if (data.password && data.password !== '') {
        return data.password.length >= 8;
      }
      return true;
    },
    {
      message: 'Password must be at least 8 characters',
      path: ['password'],
    }
  )
  .refine(
    (data) => {
      if (data.password && data.password !== '') {
        return data.password === data.password_confirmation;
      }
      return true;
    },
    {
      message: "Passwords don't match",
      path: ['password_confirmation'],
    }
  );

/**
 * Zod resolver for create user form
 */
export const createUserResolver = zodResolver(createUserSchema);

/**
 * Zod resolver for update user form
 */
export const updateUserResolver = zodResolver(updateUserSchema);
