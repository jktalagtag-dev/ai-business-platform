import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export type Theme = 'light' | 'dark' | 'system';

const AI_DOCK_MIN_WIDTH = 320;
const AI_DOCK_MAX_WIDTH = 560;
const AI_DOCK_DEFAULT_WIDTH = 380;

function clampAiDockWidth(width: number): number {
  return Math.min(AI_DOCK_MAX_WIDTH, Math.max(AI_DOCK_MIN_WIDTH, width));
}

interface UiState {
  theme: Theme;
  sidebarCollapsed: boolean;
  /** Docked AI assistant panel (DESIGN_SYSTEM.md's persistent chat dock). */
  aiDockOpen: boolean;
  aiDockWidth: number;
  setTheme: (theme: Theme) => void;
  toggleSidebar: () => void;
  setSidebarCollapsed: (collapsed: boolean) => void;
  setAiDockOpen: (open: boolean) => void;
  toggleAiDock: () => void;
  setAiDockWidth: (width: number) => void;
}

/** UI preferences persisted to localStorage (theme, sidebar collapse, AI dock). */
export const useUiStore = create<UiState>()(
  persist(
    (set) => ({
      theme: 'system',
      sidebarCollapsed: false,
      aiDockOpen: false,
      aiDockWidth: AI_DOCK_DEFAULT_WIDTH,
      setTheme: (theme) => set({ theme }),
      toggleSidebar: () => set((s) => ({ sidebarCollapsed: !s.sidebarCollapsed })),
      setSidebarCollapsed: (collapsed) => set({ sidebarCollapsed: collapsed }),
      setAiDockOpen: (open) => set({ aiDockOpen: open }),
      toggleAiDock: () => set((s) => ({ aiDockOpen: !s.aiDockOpen })),
      setAiDockWidth: (width) => set({ aiDockWidth: clampAiDockWidth(width) }),
    }),
    { name: 'abp.ui' }
  )
);
