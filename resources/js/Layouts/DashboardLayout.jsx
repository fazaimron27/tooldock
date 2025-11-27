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
import { useRef } from 'react';

import AppSidebar from '@/Components/AppSidebar';
import Footer from '@/Components/Footer';
import Navbar from '@/Components/Navbar';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { Toaster } from '@/Components/ui/sonner';

export default function DashboardLayout({ header, children }) {
  useFlashNotifications();
  const scrollContainerRef = useRef(null);

  return (
    <SidebarProvider className="h-svh overflow-hidden">
      <AppSidebar />
      <SidebarInset className="flex h-full flex-col overflow-hidden">
        <div className="relative flex-1 overflow-hidden flex flex-col">
          {/* Navbar overlays the scroll container for backdrop blur effect */}
          <Navbar header={header} scrollContainerRef={scrollContainerRef} />
          {/* Scroll container with top padding to account for navbar height */}
          <div ref={scrollContainerRef} className="flex-1 overflow-x-hidden overflow-y-auto pt-16">
            <div className="flex flex-col gap-4 p-4 min-h-full">{children}</div>
          </div>
          {/* Footer remains sticky at the bottom */}
          <Footer />
        </div>
      </SidebarInset>
      <Toaster />
    </SidebarProvider>
  );
}
