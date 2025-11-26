import AppSidebar from '@/Components/AppSidebar';
import Footer from '@/Components/Footer';
import Navbar from '@/Components/Navbar';
import { Toaster } from '@/Components/ui/sonner';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { useFlashNotifications } from '@/hooks/useFlashNotifications';

export default function DashboardLayout({ header, children }) {
    useFlashNotifications();

    return (
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset>
                <Navbar header={header} />
                <main className="flex flex-1 flex-col gap-4 p-4">
                    {children}
                </main>
                <Footer />
            </SidebarInset>
            <Toaster />
        </SidebarProvider>
    );
}

