/**
 * Zod validation schemas for group forms
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for creating a new group
 */
export const createGroupSchema = z.object({
  name: z
    .string()
    .min(1, 'Group name is required')
    .max(255, 'Group name must not exceed 255 characters'),
  slug: z.string().max(255, 'Slug must not exceed 255 characters').optional().or(z.literal('')),
  description: z.string().optional().or(z.literal('')),
  members: z.array(z.coerce.number()).optional(),
  roles: z.array(z.coerce.number()).optional(),
  permissions: z.array(z.coerce.number()).optional(),
});

/**
 * Schema for updating an existing group
 */
export const updateGroupSchema = z.object({
  name: z
    .string()
    .min(1, 'Group name is required')
    .max(255, 'Group name must not exceed 255 characters'),
  slug: z.string().max(255, 'Slug must not exceed 255 characters').optional().or(z.literal('')),
  description: z.string().optional().or(z.literal('')),
  members: z.array(z.coerce.number()).optional(),
  roles: z.array(z.coerce.number()).optional(),
  permissions: z.array(z.coerce.number()).optional(),
});

/**
 * Zod resolver for create group form
 */
export const createGroupResolver = zodResolver(createGroupSchema);

/**
 * Zod resolver for update group form
 */
export const updateGroupResolver = zodResolver(updateGroupSchema);
