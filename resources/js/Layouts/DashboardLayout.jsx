/**
 * Dashboard layout component providing the main application structure
 *
 * Creates a layout with sidebar, navbar, scrollable content area, and footer.
 * The navbar is absolutely positioned to overlay the scroll container, enabling
 * the backdrop blur effect as content scrolls behind it. The footer remains
 * sticky at the bottom of the viewport.
 *
 * @param {string} header - Optional header text to display in the navbar
 * @param {React.ReactNode} children - Page content to render
 */
import { useFlashNotifications } from '@/Hooks/useFlashNotifications';
import { useNavigationLoading } from '@/Hooks/useNavigationLoading';
import { useRef } from 'react';

import AppSidebar from '@/Components/AppSidebar';
import Footer from '@/Components/Footer';
import Navbar from '@/Components/Navbar';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { Toaster } from '@/Components/ui/sonner';
import { Spinner } from '@/Components/ui/spinner';

export default function DashboardLayout({ header: _header, children }) {
  useFlashNotifications();
  const scrollContainerRef = useRef(null);
  const { isLoading } = useNavigationLoading();

  return (
    <SidebarProvider className="h-svh overflow-hidden">
      <AppSidebar />
      <SidebarInset className="flex h-full flex-col overflow-hidden">
        <div className="relative flex-1 overflow-hidden flex flex-col">
          <Navbar scrollContainerRef={scrollContainerRef} />
          <div
            ref={scrollContainerRef}
            className="relative flex-1 overflow-x-hidden overflow-y-auto pt-16"
          >
            {isLoading && (
              <div className="absolute inset-x-0 top-16 bottom-0 z-40 flex items-center justify-center bg-background/80 backdrop-blur-sm">
                <Spinner className="size-12" />
              </div>
            )}
            <div className="flex flex-col gap-4 p-5 py-7 min-h-full">{children}</div>
          </div>
          <Footer />
        </div>
      </SidebarInset>
      <Toaster />
    </SidebarProvider>
  );
}
