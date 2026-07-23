import { useCallback, useEffect, useRef } from 'react';
import { GripVertical, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useUiStore } from '@/store/uiStore';

/**
 * Docked AI assistant — DESIGN_SYSTEM.md calls for a persistent, resizable
 * chat dock rather than only a full-page chat. Rendered in `AppLayout` as a
 * full-height sibling to the right of the Topbar/main column; hidden below
 * `lg` (a resizable side dock has no room on narrower viewports — the
 * full-page `/ai/conversations` routes remain the small-screen path).
 *
 * This is the frame only: open/close, drag-to-resize, width persistence.
 * The chat itself (history, composer, streaming) is wired in the next phase.
 */
export function AiDockPanel() {
  const isOpen = useUiStore((s) => s.aiDockOpen);
  const width = useUiStore((s) => s.aiDockWidth);
  const setAiDockOpen = useUiStore((s) => s.setAiDockOpen);
  const setAiDockWidth = useUiStore((s) => s.setAiDockWidth);

  const isResizing = useRef(false);

  const handlePointerMove = useCallback(
    (e: PointerEvent) => {
      if (!isResizing.current) return;
      // The dock is anchored to the right edge, so its width is the distance
      // from the pointer to the viewport's right edge (clamped in the store).
      setAiDockWidth(window.innerWidth - e.clientX);
    },
    [setAiDockWidth]
  );

  const stopResizing = useCallback(() => {
    if (!isResizing.current) return;
    isResizing.current = false;
    document.body.style.removeProperty('cursor');
    document.body.style.removeProperty('user-select');
  }, []);

  useEffect(() => {
    window.addEventListener('pointermove', handlePointerMove);
    window.addEventListener('pointerup', stopResizing);
    return () => {
      window.removeEventListener('pointermove', handlePointerMove);
      window.removeEventListener('pointerup', stopResizing);
    };
  }, [handlePointerMove, stopResizing]);

  function startResizing() {
    isResizing.current = true;
    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
  }

  if (!isOpen) return null;

  return (
    <aside
      className="relative hidden shrink-0 border-l bg-card lg:flex lg:flex-col"
      style={{ width }}
    >
      <div
        role="separator"
        aria-orientation="vertical"
        aria-label="Resize AI assistant panel"
        onPointerDown={startResizing}
        className="group absolute -left-1.5 top-0 z-10 flex h-full w-3 cursor-col-resize touch-none items-center justify-center"
      >
        <GripVertical className="h-4 w-4 text-transparent group-hover:text-muted-foreground" strokeWidth={1.75} />
      </div>

      <div className="flex h-16 shrink-0 items-center justify-between border-b px-4">
        <p className="text-sm font-semibold">AI Assistant</p>
        <Button
          variant="ghost"
          size="icon"
          aria-label="Close AI assistant"
          onClick={() => setAiDockOpen(false)}
        >
          <X className="h-5 w-5" strokeWidth={1.75} />
        </Button>
      </div>

      <div className="flex flex-1 items-center justify-center overflow-y-auto p-4 text-center text-sm text-muted-foreground">
        Chat coming soon.
      </div>
    </aside>
  );
}
