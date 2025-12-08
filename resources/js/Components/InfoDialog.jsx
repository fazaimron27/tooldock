/**
 * Information dialog component displaying project details
 * Shows project name, tech stack, repository URL, and other relevant information
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

export default function InfoDialog({ open, onOpenChange }) {
  const { app_name, repository_url } = usePage().props;
  const projectName = app_name || 'Tool Dock';

  const projectInfo = {
    name: projectName,
    description: 'A modern Laravel application built with Inertia.js and React',
    repositoryUrl: repository_url || null,
    techStack: {
      backend: [
        { name: 'Laravel', version: '^12.0' },
        { name: 'PHP', version: '^8.2' },
        { name: 'Inertia.js', version: '^2.0' },
        { name: 'Sanctum', version: '^4.0' },
      ],
      frontend: [
        { name: 'React', version: '^18.2.0' },
        { name: 'Tailwind CSS', version: '^3.2.1' },
        { name: 'shadcn/ui', version: '^3.5.0' },
        { name: 'Framer Motion', version: '^12.23.25' },
      ],
      tools: [
        { name: 'Vite', version: '^7.0.7' },
        { name: 'Laravel Pint', version: '^1.24' },
        { name: 'PHPUnit', version: '^11.5.3' },
      ],
    },
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <div className="p-6">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Info className="h-5 w-5" />
              {projectInfo.name}
            </DialogTitle>
            <DialogDescription>{projectInfo.description}</DialogDescription>
          </DialogHeader>

          <div className="mt-6 max-h-[60vh] overflow-y-auto space-y-6">
            {/* Repository URL */}
            {projectInfo.repositoryUrl && (
              <>
                <div className="space-y-2">
                  <h4 className="text-sm font-semibold">Repository</h4>
                  <a
                    href={projectInfo.repositoryUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2 text-sm text-primary hover:underline"
                  >
                    <ExternalLink className="h-4 w-4" />
                    {projectInfo.repositoryUrl}
                  </a>
                </div>
                <Separator />
              </>
            )}

            {/* Tech Stack */}
            <div className="space-y-4">
              <h4 className="text-sm font-semibold">Technology Stack</h4>

              {/* Backend */}
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-sm">Backend</CardTitle>
                  <CardDescription className="text-xs">
                    Server-side technologies and frameworks
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="flex flex-wrap gap-2">
                    {projectInfo.techStack.backend.map((tech) => (
                      <Badge key={tech.name} variant="secondary" className="text-xs">
                        {tech.name} {tech.version}
                      </Badge>
                    ))}
                  </div>
                </CardContent>
              </Card>

              {/* Frontend */}
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-sm">Frontend</CardTitle>
                  <CardDescription className="text-xs">
                    Client-side technologies and UI libraries
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="flex flex-wrap gap-2">
                    {projectInfo.techStack.frontend.map((tech) => (
                      <Badge key={tech.name} variant="secondary" className="text-xs">
                        {tech.name} {tech.version}
                      </Badge>
                    ))}
                  </div>
                </CardContent>
              </Card>

              {/* Tools */}
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-sm">Development Tools</CardTitle>
                  <CardDescription className="text-xs">
                    Build tools, formatters, and testing frameworks
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="flex flex-wrap gap-2">
                    {projectInfo.techStack.tools.map((tech) => (
                      <Badge key={tech.name} variant="secondary" className="text-xs">
                        {tech.name} {tech.version}
                      </Badge>
                    ))}
                  </div>
                </CardContent>
              </Card>
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
