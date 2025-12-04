/**
 * Zod validation schemas for newsletter campaign forms
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for creating a new newsletter campaign
 */
export const createCampaignSchema = z.object({
  subject: z
    .string()
    .min(1, 'Subject is required')
    .max(255, 'Subject must not exceed 255 characters'),
  content: z.string().min(1, 'Content is required'),
  selected_posts: z.array(z.coerce.number()).min(1, 'At least one blog post must be selected'),
  scheduled_at: z.string().optional().or(z.literal('')),
});

/**
 * Schema for updating an existing newsletter campaign
 */
export const updateCampaignSchema = z.object({
  subject: z
    .string()
    .min(1, 'Subject is required')
    .max(255, 'Subject must not exceed 255 characters'),
  content: z.string().min(1, 'Content is required'),
  selected_posts: z.array(z.coerce.number()).min(1, 'At least one blog post must be selected'),
  scheduled_at: z.string().optional().or(z.literal('')),
});

/**
 * Zod resolver for create campaign form
 */
export const createCampaignResolver = zodResolver(createCampaignSchema);

/**
 * Zod resolver for update campaign form
 */
export const updateCampaignResolver = zodResolver(updateCampaignSchema);
