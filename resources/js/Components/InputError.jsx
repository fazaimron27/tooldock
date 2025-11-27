/**
 * Input error message component for displaying validation errors
 * Only renders when an error message is provided
 */
export default function InputError({ message, className = '', ...props }) {
  return message ? (
    <p {...props} className={'text-sm text-red-600 ' + className}>
      {message}
    </p>
  ) : null;
}
