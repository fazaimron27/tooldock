/**
 * Zod validation schemas for Hook module forms.
 *
 * Covers inbound endpoint and outbound webhook create/update flows.
 * Used with react-hook-form via zodResolver.
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

/**
 * Schema for creating a new inbound webhook endpoint.
 */
export const createInboundSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
  description: z
    .string()
    .max(1000, 'Description must not exceed 1000 characters')
    .optional()
    .or(z.literal('')),
});

/**
 * Schema for updating an existing inbound endpoint.
 */
export const updateInboundSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
  description: z
    .string()
    .max(1000, 'Description must not exceed 1000 characters')
    .optional()
    .or(z.literal('')),
  is_active: z.boolean().optional(),
});

/**
 * Schema for creating a new outbound webhook.
 *
 * `target_url` is required only when provider is "generic".
 * For managed providers (Slack, Discord, etc.) the URL is resolved
 * from provider_config fields server-side.
 */
export const createOutboundSchema = z
  .object({
    name: z.string().min(1, 'Name is required').max(255, 'Name must not exceed 255 characters'),
    provider: z.string().min(1, 'Provider is required'),
    target_url: z
      .string()
      .max(2048, 'URL must not exceed 2048 characters')
      .optional()
      .or(z.literal('')),
    method: z.enum(HTTP_METHODS, { message: 'Invalid HTTP method' }),
    trigger: z.string().optional().or(z.literal('')),
    description: z.string().optional().or(z.literal('')),
    provider_config: z.record(z.string(), z.any()).optional(),
    payload_template: z.record(z.string(), z.any()).optional(),
  })
  .superRefine((data, ctx) => {
    if (data.provider === 'generic') {
      if (!data.target_url || data.target_url.trim() === '') {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: 'Target URL is required',
          path: ['target_url'],
        });
      } else if (!/^https?:\/\/.+/.test(data.target_url)) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: 'Must be a valid URL (start with http:// or https://)',
          path: ['target_url'],
        });
      }
    }
  });

/**
 * Schema for updating an existing outbound webhook.
 * Same rules as create — all fields editable.
 */
export const updateOutboundSchema = createOutboundSchema.extend({
  is_active: z.boolean().optional(),
});

/** Zod resolver for the create-inbound form. */
export const createInboundResolver = zodResolver(createInboundSchema);

/** Zod resolver for the update-inbound form (Inbound Show + inline edit). */
export const updateInboundResolver = zodResolver(updateInboundSchema);

/** Zod resolver for the create-outbound form. */
export const createOutboundResolver = zodResolver(createOutboundSchema);

/** Zod resolver for the update-outbound form (OutboundWorkspace edit dialog). */
export const updateOutboundResolver = zodResolver(updateOutboundSchema);
