/**
 * UI Settings store using Zustand
 * Manages user interface preferences and settings
 */
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export const useUISettingsStore = create(
  persist(
    (set) => ({
      /**
       * Table display settings and preferences.
       */
      tableSettings: {
        defaultPageSize: 10,
        showRowNumbers: false,
        compactMode: false,
      },
      setTableSettings: (settings) =>
        set((state) => ({
          tableSettings: { ...state.tableSettings, ...settings },
        })),

      /**
       * Dashboard layout and widget configuration.
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
    }
  )
);
