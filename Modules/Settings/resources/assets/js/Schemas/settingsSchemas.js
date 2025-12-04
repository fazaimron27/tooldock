/**
 * Zod validation schemas for settings forms
 * Used with react-hook-form via zodResolver
 *
 * Note: Settings are dynamic and can have any keys.
 * This schema accepts any object structure with string values.
 */
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

/**
 * Schema for updating settings
 * Accepts any object with string keys and any value type (string, number, boolean)
 * All fields are optional since settings can be updated partially
 */
export const updateSettingsSchema = z.record(z.string(), z.any());

/**
 * Zod resolver for update settings form
 */
export const updateSettingsResolver = zodResolver(updateSettingsSchema);
