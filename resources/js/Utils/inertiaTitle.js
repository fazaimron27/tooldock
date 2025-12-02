/**
 * Generate page title with app name
 * @param {string} title - Page title
 * @returns {string} - Formatted title with app name
 */
export function generateInertiaTitle(title) {
  try {
    const appElement = document.getElementById('app');
    if (appElement?.dataset?.page) {
      const parsed = JSON.parse(appElement.dataset.page);
      const appName = parsed?.props?.app_name || 'Laravel';
      return `${title} - ${appName}`;
    }
  } catch {
    // Ignore parsing errors and fall through to default
  }
  return `${title} - Laravel`;
}
