/**
 * Landing layout component for open source project pages
 * Features a sticky navbar with developer-focused navigation and GitHub integration
 */
import { Link, usePage } from '@inertiajs/react';
import { GitHub } from 'lucide-react';

import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/Components/ui/button';

export default function LandingLayout({ children }) {
  const { auth } = usePage().props;
  const isAuthenticated = !!auth?.user;

  return (
    <div className="flex min-h-screen flex-col">
      <header className="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="container flex h-16 items-center justify-between px-4">
          <Link href="/" className="flex items-center">
            <ApplicationLogo />
          </Link>

          <nav className="hidden items-center gap-6 md:flex">
            <Link
              href="#"
              className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
              Documentation
            </Link>
            <Link
              href="#"
              className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
              Modules
            </Link>
            <Link
              href="#"
              className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
              Community
            </Link>
          </nav>

          <nav className="flex items-center gap-3">
            <Button
              asChild
              variant="ghost"
              size="icon"
              className="hidden sm:flex"
              aria-label="View on GitHub"
            >
              <a
                href="https://github.com"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center"
              >
                <GitHub className="h-5 w-5" />
              </a>
            </Button>
            {isAuthenticated ? (
              <Button asChild variant="outline" size="sm">
                <Link href={route('dashboard')}>Dashboard</Link>
              </Button>
            ) : (
              <>
                <Button asChild variant="ghost" size="sm" className="hidden sm:flex">
                  <Link href={route('login')}>Log in</Link>
                </Button>
                <Button asChild variant="outline" size="sm">
                  <Link href={route('register')}>Sign up</Link>
                </Button>
              </>
            )}
          </nav>
        </div>
      </header>

      <main className="flex-1">{children}</main>

      <footer className="border-t py-6">
        <div className="container flex flex-col items-center justify-between gap-4 px-4 md:flex-row">
          <div className="flex flex-col items-center gap-2 md:flex-row md:gap-4">
            <p className="text-sm text-muted-foreground">
              © {new Date().getFullYear()} Mosaic. Open Source under{' '}
              <a
                href="https://opensource.org/licenses/MIT"
                target="_blank"
                rel="noopener noreferrer"
                className="font-medium text-foreground hover:underline"
              >
                MIT License
              </a>
            </p>
          </div>
          <div className="flex items-center gap-6 text-sm text-muted-foreground">
            <Link href="#" className="hover:text-foreground transition-colors">
              Documentation
            </Link>
            <Link href="#" className="hover:text-foreground transition-colors">
              Contributing
            </Link>
            <a
              href="https://github.com"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-1.5 hover:text-foreground transition-colors"
            >
              <GitHub className="h-4 w-4" />
              GitHub
            </a>
          </div>
        </div>
      </footer>
    </div>
  );
}
