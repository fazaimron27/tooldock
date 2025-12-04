/**
 * Zod validation schemas for blog post forms
 * Used with react-hook-form via zodResolver
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for creating a new blog post
 */
export const createPostSchema = z.object({
  title: z.string().min(1, 'Title is required').max(255, 'Title must not exceed 255 characters'),
  excerpt: z
    .string()
    .max(500, 'Excerpt must not exceed 500 characters')
    .optional()
    .or(z.literal('')),
  content: z.string().min(1, 'Content is required'),
  published_at: z.string().optional().or(z.literal('')),
});

/**
 * Schema for updating an existing blog post
 */
export const updatePostSchema = z.object({
  title: z.string().min(1, 'Title is required').max(255, 'Title must not exceed 255 characters'),
  excerpt: z
    .string()
    .max(500, 'Excerpt must not exceed 500 characters')
    .optional()
    .or(z.literal('')),
  content: z.string().min(1, 'Content is required'),
  published_at: z.string().optional().or(z.literal('')),
});

/**
 * Zod resolver for create post form
 */
export const createPostResolver = zodResolver(createPostSchema);

/**
 * Zod resolver for update post form
 */
export const updatePostResolver = zodResolver(updatePostSchema);
