/**
 * Zod validation schemas for Bot module forms.
 *
 * Covers bot platform integration create/update flows.
 * Used with react-hook-form via zodResolver.
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for creating a new bot platform integration.
 */
export const createBotPlatformSchema = z.object({
  driver: z.string().min(1, 'Platform is required'),
  name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
  credentials: z.record(z.string(), z.any()).optional(),
  is_active: z.boolean().optional(),
});

/**
 * Schema for updating a bot platform integration.
 */
export const updateBotPlatformSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
  credentials: z.record(z.string(), z.any()).optional(),
  is_active: z.boolean().optional(),
});

/** Zod resolver for the create-platform form. */
export const createBotPlatformResolver = zodResolver(createBotPlatformSchema);

/** Zod resolver for the update-platform form. */
export const updateBotPlatformResolver = zodResolver(updateBotPlatformSchema);
