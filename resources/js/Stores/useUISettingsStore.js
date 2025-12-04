/**
 * UI Settings store using Zustand
 * Manages user interface preferences and settings
 *
 * This store is reserved for future UI preferences that need client-side persistence.
 * Currently contains placeholder structures for features not yet implemented.
 */
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export const useUISettingsStore = create(
  persist(
    (set) => ({
      /**
       * Dashboard layout and widget configuration.
       * TODO: Implement dashboard customization features
       */
      dashboardSettings: {
        layout: 'grid',
        widgets: [],
      },
      setDashboardSettings: (settings) =>
        set((state) => ({
          dashboardSettings: { ...state.dashboardSettings, ...settings },
        })),

      /**
       * Notification preferences and settings.
       * TODO: Implement notification system
       */
      notifications: {
        enabled: true,
        sound: false,
        desktop: false,
      },
      setNotifications: (settings) =>
        set((state) => ({
          notifications: { ...state.notifications, ...settings },
        })),
    }),
    {
      name: 'ui-settings-storage',
      /**
       * Only persist settings that should survive page refreshes.
       * Excludes temporary UI state that shouldn't persist across sessions.
       */
      partialize: (state) => ({
        dashboardSettings: state.dashboardSettings,
        notifications: state.notifications,
      }),
    }
  )
);
