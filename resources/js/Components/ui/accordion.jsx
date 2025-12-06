import { pageTransition } from '@/Utils/animations';
import { cn } from '@/Utils/utils';
import * as AccordionPrimitive from '@radix-ui/react-accordion';
// eslint-disable-next-line no-unused-vars
import { motion, useReducedMotion } from 'framer-motion';
import { ChevronDown } from 'lucide-react';
import * as React from 'react';

const Accordion = AccordionPrimitive.Root;

const AccordionItem = React.forwardRef(({ className, ...props }, ref) => (
  <AccordionPrimitive.Item ref={ref} className={cn('border-b', className)} {...props} />
));
AccordionItem.displayName = 'AccordionItem';

const AccordionTrigger = React.forwardRef(({ className, children, ...props }, ref) => (
  <AccordionPrimitive.Header className="flex">
    <AccordionPrimitive.Trigger
      ref={ref}
      className={cn(
        'flex flex-1 items-center justify-between py-4 text-sm font-medium transition-all hover:underline text-left [&[data-state=open]>svg]:rotate-180',
        className
      )}
      {...props}
    >
      {children}
      <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200" />
    </AccordionPrimitive.Trigger>
  </AccordionPrimitive.Header>
));
AccordionTrigger.displayName = AccordionPrimitive.Trigger.displayName;

const AccordionContent = React.forwardRef(({ className, children, ...props }, ref) => {
  const shouldReduceMotion = useReducedMotion();

  return (
    <AccordionPrimitive.Content ref={ref} {...props}>
      <motion.div
        className={cn('overflow-hidden text-sm pb-4 pt-0', className)}
        initial={shouldReduceMotion ? false : pageTransition.initial}
        animate={pageTransition.animate}
        exit={shouldReduceMotion ? false : pageTransition.exit}
        transition={pageTransition.transition}
      >
        {children}
      </motion.div>
    </AccordionPrimitive.Content>
  );
});
AccordionContent.displayName = AccordionPrimitive.Content.displayName;

export { Accordion, AccordionItem, AccordionTrigger, AccordionContent };
