import { dialogVariants } from '@/Utils/animations';
import { cn } from '@/Utils/utils';
import * as PopoverPrimitive from '@radix-ui/react-popover';
import { motion, useReducedMotion } from 'framer-motion';
import * as React from 'react';

const Popover = PopoverPrimitive.Root;

const PopoverTrigger = PopoverPrimitive.Trigger;

const PopoverAnchor = PopoverPrimitive.Anchor;

const PopoverContent = React.forwardRef(
  ({ className, align = 'center', sideOffset = 4, children, ...props }, ref) => {
    const shouldReduceMotion = useReducedMotion();

    return (
      <PopoverPrimitive.Portal>
        <PopoverPrimitive.Content asChild align={align} sideOffset={sideOffset} {...props}>
          <motion.div
            ref={ref}
            className={cn(
              'z-50 w-72 rounded-md border bg-popover p-4 text-popover-foreground shadow-md outline-none origin-[--radix-popover-content-transform-origin]',
              className
            )}
            initial={shouldReduceMotion ? false : dialogVariants.content.initial}
            animate={dialogVariants.content.animate}
            exit={shouldReduceMotion ? false : dialogVariants.content.exit}
            transition={dialogVariants.content.transition}
            style={{ transformOrigin: 'var(--radix-popover-content-transform-origin)' }}
          >
            {children}
          </motion.div>
        </PopoverPrimitive.Content>
      </PopoverPrimitive.Portal>
    );
  }
);
PopoverContent.displayName = PopoverPrimitive.Content.displayName;

export { Popover, PopoverTrigger, PopoverContent, PopoverAnchor };
