/**
 * Information dialog component displaying project details
 * Shows project name, description, repository URL, and technology stack
 */
import { usePage } from '@inertiajs/react';
import { ExternalLink, Info } from 'lucide-react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Separator } from '@/Components/ui/separator';

const techStack = {
  backend: {
    label: 'Backend',
    description: 'Server-side technologies and frameworks',
    items: ['PHP', 'Laravel', 'Inertia.js', 'PostgreSQL', 'Redis'],
  },
  frontend: {
    label: 'Frontend',
    description: 'Client-side technologies and UI libraries',
    items: [
      'React',
      'Radix UI',
      'shadcn/ui',
      'TanStack Table',
      'TanStack Query',
      'Zustand',
      'Lucide React',
    ],
  },
  realtime: {
    label: 'Real-time & Monitoring',
    description: 'WebSocket, queue, and observability tools',
    items: ['Laravel Reverb', 'Laravel Horizon', 'Laravel Pulse', 'Laravel Telescope'],
  },
  tools: {
    label: 'Infrastructure & Code Quality',
    description: 'Build tools, formatters, and testing',
    items: [
      'Vite',
      'Docker Compose',
      'Tailwind CSS',
      'ESLint',
      'Prettier',
      'Laravel Pint',
      'Lefthook',
      'PHPUnit',
    ],
  },
};

export default function InfoDialog({ open, onOpenChange }) {
  const { app_name, repository_url } = usePage().props;
  const projectName = app_name || 'Tool Dock';

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <div className="p-6">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Info className="h-5 w-5" />
              {projectName}
            </DialogTitle>
            <DialogDescription>
              A modular monolith personal productivity workspace built with Laravel, React, and
              Inertia.js.
            </DialogDescription>
          </DialogHeader>

          <div className="mt-6 max-h-[60vh] space-y-6 overflow-y-auto">
            {/* Repository URL */}
            {repository_url && (
              <>
                <div className="space-y-2">
                  <h4 className="text-sm font-semibold">Repository</h4>
                  <a
                    href={repository_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary flex items-center gap-2 text-sm hover:underline"
                  >
                    <ExternalLink className="h-4 w-4" />
                    {repository_url}
                  </a>
                </div>
                <Separator />
              </>
            )}

            {/* Tech Stack */}
            <div className="space-y-4">
              <h4 className="text-sm font-semibold">Technology Stack</h4>

              {Object.entries(techStack).map(([key, category]) => (
                <Card key={key}>
                  <CardHeader className="pb-3">
                    <CardTitle className="text-sm">{category.label}</CardTitle>
                    <CardDescription className="text-xs">{category.description}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="flex flex-wrap gap-2">
                      {category.items.map((name) => (
                        <Badge key={name} variant="secondary" className="text-xs">
                          {name}
                        </Badge>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>

          <DialogFooter className="mt-6">
            <Button variant="outline" onClick={() => onOpenChange(false)}>
              Close
            </Button>
          </DialogFooter>
        </div>
      </DialogContent>
    </Dialog>
  );
}
