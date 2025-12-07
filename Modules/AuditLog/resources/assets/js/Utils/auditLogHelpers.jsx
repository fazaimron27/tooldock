/**
 * Shared utility functions for AuditLog module
 * Reduces code duplication across Index and Show pages
 */
import { Badge } from '@/Components/ui/badge';

/**
 * Get event badge configuration and component
 *
 * @param {string} event - The event type
 * @returns {JSX.Element} Badge component with appropriate styling
 */
export function getEventBadge(event) {
  const config = {
    created: {
      variant: 'default',
      className:
        'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-800/60 dark:hover:text-green-200',
      label: 'Created',
    },
    updated: {
      variant: 'default',
      className:
        'bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-800/60 dark:hover:text-blue-200',
      label: 'Updated',
    },
    deleted: {
      variant: 'default',
      className:
        'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-800/60 dark:hover:text-red-200',
      label: 'Deleted',
    },
    registered: {
      variant: 'default',
      className:
        'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-800/60 dark:hover:text-emerald-200',
      label: 'Registered',
    },
    login: {
      variant: 'default',
      className:
        'bg-indigo-100 text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-800/60 dark:hover:text-indigo-200',
      label: 'Login',
    },
    logout: {
      variant: 'default',
      className:
        'bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-800/60 dark:hover:text-amber-200',
      label: 'Logout',
    },
    password_reset: {
      variant: 'default',
      className:
        'bg-purple-100 text-purple-800 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:hover:bg-purple-800/60 dark:hover:text-purple-200',
      label: 'Password Reset',
    },
    password_changed: {
      variant: 'default',
      className:
        'bg-violet-100 text-violet-800 hover:bg-violet-200 dark:bg-violet-900/30 dark:text-violet-400 dark:hover:bg-violet-800/60 dark:hover:text-violet-200',
      label: 'Password Changed',
    },
    password_reset_requested: {
      variant: 'default',
      className:
        'bg-fuchsia-100 text-fuchsia-800 hover:bg-fuchsia-200 dark:bg-fuchsia-900/30 dark:text-fuchsia-400 dark:hover:bg-fuchsia-800/60 dark:hover:text-fuchsia-200',
      label: 'Password Reset Requested',
    },
    email_verified: {
      variant: 'default',
      className:
        'bg-teal-100 text-teal-800 hover:bg-teal-200 dark:bg-teal-900/30 dark:text-teal-400 dark:hover:bg-teal-800/60 dark:hover:text-teal-200',
      label: 'Email Verified',
    },
    email_changed: {
      variant: 'default',
      className:
        'bg-cyan-100 text-cyan-800 hover:bg-cyan-200 dark:bg-cyan-900/30 dark:text-cyan-400 dark:hover:bg-cyan-800/60 dark:hover:text-cyan-200',
      label: 'Email Changed',
    },
    account_deleted: {
      variant: 'default',
      className:
        'bg-rose-100 text-rose-800 hover:bg-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:hover:bg-rose-800/60 dark:hover:text-rose-200',
      label: 'Account Deleted',
    },
    export: {
      variant: 'default',
      className:
        'bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-900/30 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200',
      label: 'Export',
    },
    file_uploaded: {
      variant: 'default',
      className:
        'bg-lime-100 text-lime-800 hover:bg-lime-200 dark:bg-lime-900/30 dark:text-lime-400 dark:hover:bg-lime-800/60 dark:hover:text-lime-200',
      label: 'File Uploaded',
    },
    file_deleted: {
      variant: 'default',
      className:
        'bg-orange-100 text-orange-800 hover:bg-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:hover:bg-orange-800/60 dark:hover:text-orange-200',
      label: 'File Deleted',
    },
    relationship_synced: {
      variant: 'default',
      className:
        'bg-pink-100 text-pink-800 hover:bg-pink-200 dark:bg-pink-900/30 dark:text-pink-400 dark:hover:bg-pink-800/60 dark:hover:text-pink-200',
      label: 'Relationship Synced',
    },
  };

  const eventConfig = config[event] || config.updated;

  return (
    <Badge className={eventConfig.className} variant={eventConfig.variant}>
      {eventConfig.label}
    </Badge>
  );
}

/**
 * Get display name for auditable model
 *
 * @param {string|null} auditableType - The fully qualified model class name
 * @param {string|null} auditableId - The model ID
 * @returns {string} Formatted display name (e.g., "User #123")
 */
export function getModelDisplayName(auditableType, auditableId) {
  if (!auditableType || !auditableId) {
    return 'N/A';
  }

  // Handle system events
  if (auditableType === 'system') {
    return 'System';
  }

  const className = auditableType.split('\\').pop();

  return `${className} #${auditableId}`;
}

/**
 * Parse tags string into array
 *
 * @param {string|null|undefined} tagsString - Comma-separated tags string
 * @returns {string[]} Array of trimmed, non-empty tags
 */
export function parseTags(tagsString) {
  if (!tagsString || typeof tagsString !== 'string') {
    return [];
  }

  return tagsString
    .split(',')
    .map((tag) => tag.trim())
    .filter(Boolean);
}
