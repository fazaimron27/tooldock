import { useFlashNotifications } from '@/Hooks/useFlashNotifications';

import AppSidebar from '@/Components/AppSidebar';
import Footer from '@/Components/Footer';
import Navbar from '@/Components/Navbar';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { Toaster } from '@/Components/ui/sonner';

export default function DashboardLayout({ header, children }) {
  useFlashNotifications();

  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset className="flex h-screen flex-col overflow-y-auto">
        <Navbar header={header} />
        <main className="flex flex-1 flex-col gap-4 p-4">{children}</main>
        <Footer />
      </SidebarInset>
      <Toaster />
    </SidebarProvider>
  );
}
