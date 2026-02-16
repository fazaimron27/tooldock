/**
 * Global application store using Zustand
 * Manages application-wide state that needs to persist across pages
 */
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

/**
 * Application store state and actions
 *
 * Uses skipHydration to prevent flash when hydrating from localStorage.
 * The store will be hydrated after the initial render.
 */
export const useAppStore = create(
  persist(
    (set) => ({
      /**
       * UI state management for sidebar visibility.
       */
      sidebarOpen: true,
      setSidebarOpen: (open) => set({ sidebarOpen: open }),

      /**
       * Sidebar menu state for tracking open groups and menu items.
       */
      sidebarMenu: {
        openGroups: {},
        openItems: {},
      },
      toggleGroup: (group) =>
        set((state) => ({
          sidebarMenu: {
            ...state.sidebarMenu,
            openGroups: {
              ...state.sidebarMenu.openGroups,
              [group]: !state.sidebarMenu.openGroups[group],
            },
          },
        })),
      toggleMenuItem: (route) =>
        set((state) => ({
          sidebarMenu: {
            ...state.sidebarMenu,
            openItems: {
              ...state.sidebarMenu.openItems,
              [route]: !state.sidebarMenu.openItems[route],
            },
          },
        })),

      /**
       * Hydration state - true after store has been hydrated from localStorage
       */
      _hasHydrated: false,
      setHasHydrated: (state) => set({ _hasHydrated: state }),
    }),
    {
      name: 'app-storage',
      /**
       * Only persist certain parts of the state to localStorage.
       */
      partialize: (state) => ({
        sidebarOpen: state.sidebarOpen,
        sidebarMenu: state.sidebarMenu,
      }),
      /**
       * Called when hydration is complete
       */
      onRehydrateStorage: () => (state) => {
        state?.setHasHydrated(true);
      },
    }
  )
);
