/**
 * Dashboard layout component providing the main application structure
 * Includes sidebar, navbar, content area, footer, and toast notifications
 */
import { useFlashNotifications } from '@/Hooks/useFlashNotifications';

import AppSidebar from '@/Components/AppSidebar';
import Footer from '@/Components/Footer';
import Navbar from '@/Components/Navbar';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { Toaster } from '@/Components/ui/sonner';

export default function DashboardLayout({ header, children }) {
  useFlashNotifications();

  return (
    <SidebarProvider className="h-svh overflow-hidden">
      <AppSidebar />
      <SidebarInset className="flex h-full flex-col overflow-hidden">
        <Navbar header={header} />
        <div className="flex flex-1 flex-col gap-4 p-4 overflow-x-hidden overflow-y-auto min-h-0">
          {children}
        </div>
        <Footer />
      </SidebarInset>
      <Toaster />
    </SidebarProvider>
  );
}
