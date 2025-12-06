/**
 * Zod validation schemas for group forms.
 *
 * These schemas are used with react-hook-form via zodResolver to provide
 * client-side validation that matches server-side validation rules.
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Validation schema for creating a new group.
 *
 * Includes optional members array for initial member assignment during creation.
 */
export const createGroupSchema = z.object({
  name: z
    .string()
    .min(1, 'Group name is required')
    .max(255, 'Group name must not exceed 255 characters'),
  slug: z.string().max(255, 'Slug must not exceed 255 characters').optional().or(z.literal('')),
  description: z.string().optional().or(z.literal('')),
  members: z.array(z.string()).optional(),
  roles: z.array(z.string()).optional(),
  permissions: z.array(z.string()).optional(),
});

/**
 * Validation schema for updating an existing group.
 *
 * Note: Members are managed on the Show page, not in the edit form.
 * This schema does not include a members field to enforce the UX pattern
 * where member management is separate from group editing.
 */
export const updateGroupSchema = z.object({
  name: z
    .string()
    .min(1, 'Group name is required')
    .max(255, 'Group name must not exceed 255 characters'),
  slug: z.string().max(255, 'Slug must not exceed 255 characters').optional().or(z.literal('')),
  description: z.string().optional().or(z.literal('')),
  roles: z.array(z.string()).optional(),
  permissions: z.array(z.string()).optional(),
});

/**
 * Zod resolver for the create group form.
 * Integrates createGroupSchema with react-hook-form.
 */
export const createGroupResolver = zodResolver(createGroupSchema);

/**
 * Zod resolver for the update group form.
 * Integrates updateGroupSchema with react-hook-form.
 */
export const updateGroupResolver = zodResolver(updateGroupSchema);
