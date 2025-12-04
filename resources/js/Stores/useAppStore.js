/**
 * Global application store using Zustand
 * Manages application-wide state that needs to persist across pages
 */
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

/**
 * Application store state and actions
 */
export const useAppStore = create(
  persist(
    (set) => ({
      /**
       * UI state management for sidebar visibility.
       */
      sidebarOpen: true,
      setSidebarOpen: (open) => set({ sidebarOpen: open }),
      toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),

      /**
       * User preferences for UI customization.
       */
      preferences: {
        compactMode: false,
        denseTables: false,
      },
      setPreferences: (preferences) =>
        set((state) => ({
          preferences: { ...state.preferences, ...preferences },
        })),

      /**
       * Navigation history state for complex navigation scenarios.
       */
      navigationHistory: [],
      addToHistory: (path) =>
        set((state) => ({
          navigationHistory: [...state.navigationHistory.slice(-9), path],
        })),
      clearHistory: () => set({ navigationHistory: [] }),

      /**
       * Temporary UI state for modals, dialogs, and other global UI components.
       */
      uiState: {},
      setUIState: (key, value) =>
        set((state) => ({
          uiState: { ...state.uiState, [key]: value },
        })),
      clearUIState: (key) =>
        set((state) => {
          const newState = { ...state.uiState };
          delete newState[key];
          return { uiState: newState };
        }),
    }),
    {
      name: 'app-storage',
      /**
       * Only persist certain parts of the state to localStorage.
       * Excludes temporary UI state that shouldn't persist across sessions.
       */
      partialize: (state) => ({
        sidebarOpen: state.sidebarOpen,
        preferences: state.preferences,
        navigationHistory: state.navigationHistory,
      }),
    }
  )
);
