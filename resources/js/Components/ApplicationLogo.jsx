/**
 * Application logo component displaying the app name
 * Reads the app name from environment variable or defaults to 'Mosaic'
 */
export default function ApplicationLogo({ className = '', ...props }) {
  const appName = import.meta.env.VITE_APP_NAME || 'Mosaic';

  return (
    <span className={`font-semibold text-xl text-center w-full block ${className}`} {...props}>
      {appName}
    </span>
  );
}
