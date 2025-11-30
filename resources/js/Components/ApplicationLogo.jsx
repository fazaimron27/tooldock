/**
 * Application logo component displaying the app name
 * Reads the app name from Inertia shared props or defaults to 'Mosaic'
 */
import { usePage } from '@inertiajs/react';

export default function ApplicationLogo({ className = '', ...props }) {
  const { app_name } = usePage().props;
  const appName = app_name || 'Mosaic';

  return (
    <span className={`font-semibold text-xl text-center w-full block ${className}`} {...props}>
      {appName}
    </span>
  );
}
