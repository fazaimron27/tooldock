import { LayoutGrid } from 'lucide-react';

export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <LayoutGrid
            className={`h-9 w-auto ${className}`}
            {...props}
        />
    );
}
