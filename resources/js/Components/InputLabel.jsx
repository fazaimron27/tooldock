/**
 * Input label component for form fields
 * Supports both value prop and children for label content
 */
export default function InputLabel({ value, className = '', children, ...props }) {
  return (
    <label {...props} className={`block text-sm font-medium text-gray-700 ` + className}>
      {value ? value : children}
    </label>
  );
}
