/**
 * Zod validation schemas for category forms
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Type validation regex: lowercase letters, numbers, underscores, or hyphens
 */
const typeRegex = /^[a-z0-9_-]+$/;

/**
 * Color validation regex: hex color (#RRGGBB or #RGB)
 */
const colorRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;

/**
 * Schema for creating a new category
 */
export const createCategorySchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
  slug: z.string().max(255, 'Slug must not exceed 255 characters').optional().or(z.literal('')),
  type: z
    .string()
    .min(1, 'Type is required')
    .max(50, 'Type must not exceed 50 characters')
    .regex(typeRegex, 'Type must contain only lowercase letters, numbers, underscores, or hyphens')
    .transform((val) => val.toLowerCase()),
  parent_id: z.coerce.number().optional().or(z.literal('')),
  color: z
    .string()
    .regex(colorRegex, 'Color must be a valid hex color (e.g., #000000)')
    .optional()
    .or(z.literal('')),
  description: z.string().optional().or(z.literal('')),
});

/**
 * Schema for updating an existing category
 */
export const updateCategorySchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
  slug: z.string().max(255, 'Slug must not exceed 255 characters').optional().or(z.literal('')),
  type: z
    .string()
    .min(1, 'Type is required')
    .max(50, 'Type must not exceed 50 characters')
    .regex(typeRegex, 'Type must contain only lowercase letters, numbers, underscores, or hyphens')
    .transform((val) => val.toLowerCase()),
  parent_id: z.coerce.number().optional().or(z.literal('')),
  color: z
    .string()
    .regex(colorRegex, 'Color must be a valid hex color (e.g., #000000)')
    .optional()
    .or(z.literal('')),
  description: z.string().optional().or(z.literal('')),
});

/**
 * Zod resolver for create category form
 */
export const createCategoryResolver = zodResolver(createCategorySchema);

/**
 * Zod resolver for update category form
 */
export const updateCategoryResolver = zodResolver(updateCategorySchema);
