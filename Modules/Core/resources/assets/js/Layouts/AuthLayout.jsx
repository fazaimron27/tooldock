/**
 * Modern authentication layout with split-screen design
 * Left side: Dark container with image, logo, and testimonial (hidden on mobile)
 * Right side: Clean form container with dark mode support
 */
import { Link } from '@inertiajs/react';
import { Quote } from 'lucide-react';

import ApplicationLogo from '@/Components/ApplicationLogo';
import { ModeToggle } from '@/Components/ModeToggle';

export default function AuthLayout({ children }) {
  return (
    <div className="flex min-h-screen">
      <div className="hidden lg:flex lg:w-1/2 relative bg-zinc-900">
        <div className="absolute inset-0">
          <img
            src="https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=1200&q=80"
            alt=""
            className="h-full w-full object-cover"
          />
          <div className="absolute inset-0 bg-gradient-to-br from-zinc-900/70 via-zinc-900/60 to-zinc-900/70" />
        </div>

        <div className="relative z-10 flex flex-col justify-between p-12 text-white">
          <div className="w-fit rounded-lg bg-white/10 backdrop-blur-sm p-4 ring-1 ring-white/20">
            <Link href="/" className="inline-block">
              <ApplicationLogo className="[&_span]:text-white [&_div]:ring-white/30 [&_div]:shadow-lg" />
            </Link>
          </div>

          <div className="space-y-4">
            <Quote className="h-8 w-8 text-zinc-400" />
            <blockquote className="text-lg font-medium leading-relaxed">
              "Tool Dock has transformed how we manage our operations. The intuitive interface and
              powerful features have increased our productivity by 40%."
            </blockquote>
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-full bg-zinc-700 flex items-center justify-center">
                <span className="text-sm font-semibold">JD</span>
              </div>
              <div>
                <div className="font-semibold">John Doe</div>
                <div className="text-sm text-zinc-400">CEO, Acme Corporation</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="flex flex-1 items-center justify-center bg-background p-6 lg:p-12">
        {/* Fixed position in top-right corner of viewport */}
        <div className="fixed top-4 right-4 z-50">
          <ModeToggle />
        </div>

        <div className="w-full max-w-md space-y-6">{children}</div>
      </div>
    </div>
  );
}
