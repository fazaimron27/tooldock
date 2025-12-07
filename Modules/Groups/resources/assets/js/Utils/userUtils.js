import { ROLES } from '@Modules/Core/resources/assets/js/constants';

/**
 * Check if a user has the Super Admin role.
 *
 * @param {Object} user - The user object
 * @param {Array<Object>} user.roles - Array of role objects with name property
 * @returns {boolean} True if user has Super Admin role, false otherwise
 */
export function isSuperAdmin(user) {
  return user?.roles?.some((role) => role.name === ROLES.SUPER_ADMIN) || false;
}
