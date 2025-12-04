/**
 * Navigation state store using Zustand
 * Manages complex navigation state if needed
 */
import { create } from 'zustand';

export const useNavigationStore = create((set) => ({
  /**
   * Current navigation context tracking.
   */
  currentPath: null,
  previousPath: null,
  setCurrentPath: (path) =>
    set((state) => ({
      previousPath: state.currentPath,
      currentPath: path,
    })),

  /**
   * Breadcrumb trail for navigation history display.
   */
  breadcrumbs: [],
  setBreadcrumbs: (breadcrumbs) => set({ breadcrumbs }),
  addBreadcrumb: (breadcrumb) =>
    set((state) => ({
      breadcrumbs: [...state.breadcrumbs, breadcrumb],
    })),
  clearBreadcrumbs: () => set({ breadcrumbs: [] }),

  /**
   * Navigation loading state indicator.
   */
  isNavigating: false,
  setNavigating: (isNavigating) => set({ isNavigating }),
}));
