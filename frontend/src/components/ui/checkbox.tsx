import * as React from 'react';
import { Check } from 'lucide-react';
import { cn } from '@/lib/cn';

export interface CheckboxProps
  extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type' | 'onChange'> {
  checked?: boolean;
  onCheckedChange?: (checked: boolean) => void;
}

/**
 * Plain-input checkbox styled to match the shadcn checkbox look, without
 * pulling in @radix-ui/react-checkbox — nothing here needs its indeterminate
 * or form-composition behavior.
 */
const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
  ({ className, checked, onCheckedChange, ...props }, ref) => (
    <span className="relative inline-flex h-5 w-5 shrink-0">
      <input
        ref={ref}
        type="checkbox"
        checked={checked}
        onChange={(e) => onCheckedChange?.(e.target.checked)}
        className={cn(
          'peer h-5 w-5 shrink-0 cursor-pointer appearance-none rounded-sm border border-input shadow-sm checked:border-primary checked:bg-primary focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
          className
        )}
        {...props}
      />
      <Check className="pointer-events-none absolute inset-0 m-auto hidden h-3.5 w-3.5 text-primary-foreground peer-checked:block" />
    </span>
  )
);
Checkbox.displayName = 'Checkbox';

export { Checkbox };
