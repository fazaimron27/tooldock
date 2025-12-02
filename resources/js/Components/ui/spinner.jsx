import { cn } from '@/Utils/utils';

function Spinner({ className, ...props }) {
  return (
    <div role="status" aria-label="Loading" className={cn('relative', className)} {...props}>
      <div className="absolute inset-0 rounded-full border-4 border-primary/10" />
      <div className="absolute inset-0 rounded-full border-4 border-transparent border-t-primary border-r-primary/50 animate-spin" />
    </div>
  );
}

export { Spinner };
