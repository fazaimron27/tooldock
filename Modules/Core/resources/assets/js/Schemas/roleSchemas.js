/**
 * Zod validation schemas for role forms
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for creating a new role
 */
export const createRoleSchema = z.object({
  name: z
    .string()
    .min(1, 'Role name is required')
    .max(255, 'Role name must not exceed 255 characters'),
  permissions: z.array(z.coerce.number()).optional(),
});

/**
 * Schema for updating an existing role
 */
export const updateRoleSchema = z.object({
  name: z
    .string()
    .min(1, 'Role name is required')
    .max(255, 'Role name must not exceed 255 characters'),
  permissions: z.array(z.coerce.number()).optional(),
});

/**
 * Zod resolver for create role form
 */
export const createRoleResolver = zodResolver(createRoleSchema);

/**
 * Zod resolver for update role form
 */
export const updateRoleResolver = zodResolver(updateRoleSchema);
