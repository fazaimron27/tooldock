/**
 * Input error message component for displaying validation errors
 * Only renders when an error message is provided
 * Uses semantic color classes for dark mode compatibility
 */
export default function InputError({ message, className = '', ...props }) {
  return message ? (
    <p {...props} className={'text-sm text-destructive ' + className}>
      {message}
    </p>
  ) : null;
}
