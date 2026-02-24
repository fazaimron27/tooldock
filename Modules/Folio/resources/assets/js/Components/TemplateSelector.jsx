/**
 * Template Selector Component
 *
 * Sidebar card showing active template + gallery dialog for switching.
 * Follows project dialog pattern (open/onOpenChange, no DialogTrigger).
 */
import templates from '@Folio/Config/templates';
import { Check, LayoutTemplate } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

const templateList = Object.values(templates);

const TEMPLATE_TAGS = {
  professional: ['Single-column', 'ATS friendly', 'Minimal'],
  modern: ['Two-column', 'Creative', 'Fresh'],
  elegant: ['Serif', 'Executive', 'Classic'],
  bold: ['High-contrast', 'Strong', 'Impactful'],
};

export default function TemplateSelector({ value, onChange }) {
  const [open, setOpen] = useState(false);
  const activeTpl = templates[value] || templates.professional;
  const activeTags = TEMPLATE_TAGS[activeTpl.id] || [];

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="w-full text-left transition-colors hover:bg-muted/50 rounded-xl border p-3 flex flex-col gap-3 group"
      >
        <div className="flex gap-3 items-start">
          <MiniThumbnail id={activeTpl.id} accent={activeTpl.preview.accent} />
          <div className="flex flex-col gap-1 min-w-0">
            <h4 className="font-semibold text-sm text-foreground group-hover:text-primary transition-colors">
              {activeTpl.name}
            </h4>
            <p className="text-xs text-muted-foreground leading-relaxed">{activeTpl.description}</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-1.5">
          {activeTags.map((tag) => (
            <Badge key={tag} variant="secondary" className="text-[10px] px-1.5 py-0 font-medium">
              {tag}
            </Badge>
          ))}
        </div>
      </button>

      <TemplateGalleryDialog
        open={open}
        onOpenChange={setOpen}
        value={value}
        onChange={(id) => {
          onChange(id);
          setOpen(false);
        }}
      />
    </>
  );
}

function TemplateGalleryDialog({ open, onOpenChange, value, onChange }) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-4xl">
        <DialogHeader className="px-6 pt-6 pb-2">
          <DialogTitle className="flex items-center gap-2 text-lg">
            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
              <LayoutTemplate className="h-4 w-4 text-primary" />
            </span>
            Template Gallery
          </DialogTitle>
          <DialogDescription>
            Choose a template that fits your style. Each one provides a unique layout and
            typography.
          </DialogDescription>
        </DialogHeader>

        <div className="px-6 py-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
            {templateList.map((tpl) => {
              const isActive = value === tpl.id;
              const accent = tpl.preview.accent;
              return (
                <button
                  key={tpl.id}
                  type="button"
                  onClick={() => onChange(tpl.id)}
                  className="group relative flex flex-col items-center gap-3 transition-opacity hover:opacity-80"
                >
                  <div
                    className="w-full aspect-[1/1.4] rounded-lg border-2 bg-white overflow-hidden shadow-sm relative"
                    style={{ borderColor: isActive ? accent : 'var(--border)' }}
                  >
                    <div className="absolute inset-0 p-4">
                      <GalleryThumbnail id={tpl.id} accent={accent} />
                    </div>
                    {isActive && (
                      <div className="absolute inset-0 bg-black/5 flex items-center justify-center">
                        <div
                          className="w-8 h-8 rounded-full flex items-center justify-center shadow-lg"
                          style={{ background: accent }}
                        >
                          <Check className="h-5 w-5 text-white" strokeWidth={3} />
                        </div>
                      </div>
                    )}
                  </div>
                  <span
                    className={`font-semibold text-sm ${isActive ? 'text-foreground' : 'text-muted-foreground group-hover:text-foreground'}`}
                  >
                    {tpl.name}
                  </span>
                </button>
              );
            })}
          </div>
        </div>

        <DialogFooter className="px-6 pb-6 pt-2">
          <Button variant="outline" onClick={() => onOpenChange?.(false)}>
            Close
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function MiniThumbnail({ id, accent }) {
  return (
    <div className="w-14 h-[72px] border rounded bg-white shrink-0 overflow-hidden shadow-sm">
      <div className="w-full h-full p-1.5">
        <GalleryThumbnail id={id} accent={accent} />
      </div>
    </div>
  );
}

function GalleryThumbnail({ id, accent }) {
  if (id === 'modern') {
    return (
      <div className="flex h-full gap-2">
        <div className="w-1/3 h-full rounded-sm" style={{ background: '#f3f4f6' }}>
          <Line w="70%" h="4px" bg={accent} mt="6px" mx="4px" />
          <Line w="60%" h="2px" bg="#d1d5db" mt="4px" mx="4px" />
          <Line w="80%" h="2px" mt="12px" mx="4px" />
          <Line w="70%" h="2px" mt="3px" mx="4px" />
          <Line w="55%" h="2px" mt="3px" mx="4px" />
        </div>
        <div className="w-2/3 h-full pt-1">
          <Line w="30%" h="2px" bg="#9ca3af" mt="4px" />
          <Line w="90%" h="2px" mt="6px" />
          <Line w="80%" h="2px" mt="2px" />
          <Line w="85%" h="2px" mt="2px" />
          <Line w="25%" h="2px" bg="#9ca3af" mt="12px" />
          <Line w="90%" h="2px" mt="6px" />
          <Line w="85%" h="2px" mt="2px" />
        </div>
      </div>
    );
  }

  if (id === 'bold') {
    return (
      <div className="h-full">
        <div
          style={{ borderLeft: `3px solid ${accent}`, paddingLeft: '6px', marginBottom: '10px' }}
        >
          <Line w="55%" h="6px" bg="#111827" />
          <Line w="35%" h="3px" bg={accent} mt="4px" />
          <Line w="45%" h="2px" bg="#9ca3af" mt="4px" />
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '6px' }}>
          <div style={{ background: '#111827', height: '2px', width: '25%' }} />
          <div style={{ background: '#e5e7eb', height: '1px', flex: 1 }} />
        </div>
        <Line w="90%" h="2px" mt="3px" />
        <Line w="80%" h="2px" mt="3px" />
        <div style={{ display: 'flex', alignItems: 'center', gap: '6px', margin: '10px 0 6px' }}>
          <div style={{ background: '#111827', height: '2px', width: '30%' }} />
          <div style={{ background: '#e5e7eb', height: '1px', flex: 1 }} />
        </div>
        <Line w="95%" h="2px" mt="3px" />
        <Line w="85%" h="2px" mt="3px" />
      </div>
    );
  }

  if (id === 'elegant') {
    return (
      <div className="flex flex-col h-full items-center">
        <Line w="45%" h="5px" bg="#1c1917" mt="6px" mx="auto" />
        <Line w="25%" h="2px" bg="#a8a29e" mt="3px" mx="auto" />
        <Line w="15%" h="1px" bg="#a8a29e" mt="4px" mx="auto" />
        <Line w="20%" h="2px" bg="#57534e" mt="10px" mx="auto" />
        <div className="w-full" style={{ marginTop: '8px' }}>
          <Line w="90%" h="2px" mt="2px" mx="auto" />
          <Line w="80%" h="2px" mt="2px" mx="auto" />
          <Line w="85%" h="2px" mt="2px" mx="auto" />
        </div>
        <Line w="25%" h="2px" bg="#57534e" mt="10px" mx="auto" />
        <div className="w-full" style={{ marginTop: '4px' }}>
          <Line w="95%" h="2px" mt="2px" mx="auto" />
          <Line w="85%" h="2px" mt="2px" mx="auto" />
        </div>
      </div>
    );
  }

  return (
    <div className="h-full">
      <Line w="45%" h="5px" bg="#111827" />
      <Line w="30%" h="2px" bg="#9ca3af" mt="4px" />
      <div style={{ background: '#111827', height: '1px', width: '100%', margin: '8px 0' }} />
      <Line w="90%" h="2px" mt="3px" />
      <Line w="80%" h="2px" mt="3px" />
      <div style={{ background: '#111827', height: '1px', width: '100%', margin: '8px 0' }} />
      <Line w="95%" h="2px" mt="3px" />
      <Line w="85%" h="2px" mt="3px" />
      <div style={{ background: '#111827', height: '1px', width: '100%', margin: '8px 0' }} />
      <Line w="75%" h="2px" mt="3px" />
    </div>
  );
}

/** Tiny helper for skeleton lines inside thumbnails */
function Line({ w = '100%', h = '2px', bg = '#e5e7eb', mt = '0', mx = '0', o = 1 }) {
  return (
    <div
      style={{
        background: bg,
        height: h,
        width: w,
        marginTop: mt,
        marginLeft: mx === 'auto' ? 'auto' : mx,
        marginRight: mx === 'auto' ? 'auto' : undefined,
        opacity: o,
      }}
    />
  );
}
