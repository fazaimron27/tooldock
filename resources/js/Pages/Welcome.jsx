/**
 * Open Source landing page for Mosaic - The Modular ERP for Artisans
 * Developer-focused design showcasing technical stack and community
 */
import { Head, Link } from '@inertiajs/react';
import {
  ArrowRight,
  Atom,
  BookOpen,
  GitHub,
  Image,
  Layers,
  Mail,
  Palette,
  Star,
  Tag,
  Zap,
} from 'lucide-react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card';

import LandingLayout from '@/Layouts/LandingLayout';

/**
 * Terminal window component displaying a demo module installation command
 * Simulates the developer experience of installing modules via artisan
 */
function TerminalWindow() {
  return (
    <div className="mt-12 w-full max-w-3xl overflow-hidden rounded-lg border border-border/50 bg-zinc-900 shadow-2xl">
      <div className="flex items-center gap-2 border-b border-zinc-800 bg-zinc-950 px-4 py-2">
        <div className="flex gap-1.5">
          <div className="h-3 w-3 rounded-full bg-red-500/80" />
          <div className="h-3 w-3 rounded-full bg-yellow-500/80" />
          <div className="h-3 w-3 rounded-full bg-green-500/80" />
        </div>
        <div className="ml-4 flex-1 text-center">
          <span className="text-xs font-mono text-zinc-400">Terminal</span>
        </div>
      </div>

      <div className="p-6 font-mono text-sm">
        <div className="space-y-2">
          <div className="flex items-center gap-2">
            <span className="text-green-400">~</span>
            <span className="text-blue-400">$</span>
            <span className="text-zinc-300">php artisan module:Manage Sales --action=install</span>
          </div>
          <div className="text-yellow-400">{'>'} Installing module: Sales...</div>
          <div className="text-zinc-400">{'>'} Checking dependencies...</div>
          <div className="text-zinc-400">{'>'} Running migrations...</div>
          <div className="text-green-400">{'>'} Module 'Sales' installed successfully!</div>
          <div className="flex items-center gap-2 pt-2">
            <span className="text-green-400">~</span>
            <span className="text-blue-400">$</span>
            <span className="text-zinc-500 animate-pulse">_</span>
          </div>
        </div>
      </div>
    </div>
  );
}

export default function Welcome() {
  const techStack = [
    {
      icon: Zap,
      title: 'Laravel 12',
      description: 'Latest PHP framework features.',
      gradient: 'from-blue-500/20 to-cyan-500/20',
    },
    {
      icon: Layers,
      title: 'Modular Architecture',
      description: 'Domain-driven design with nwidart/modules.',
      gradient: 'from-purple-500/20 to-pink-500/20',
    },
    {
      icon: Atom,
      title: 'Inertia & React',
      description: 'Single-page application feel with server-side routing.',
      gradient: 'from-violet-500/20 to-indigo-500/20',
    },
    {
      icon: Palette,
      title: 'Shadcn UI',
      description: 'Accessible, customizable components.',
      gradient: 'from-emerald-500/20 to-teal-500/20',
    },
  ];

  const modules = [
    {
      name: 'Blog',
      description:
        'Content management system built with Laravel. Includes rich text editing, SEO optimization, and category management.',
      icon: BookOpen,
      color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
      keywords: ['CMS', 'Content'],
      version: '1.0.0',
      features: ['Rich Editor', 'SEO Tools', 'Categories'],
    },
    {
      name: 'Newsletter',
      description:
        'Email marketing module with campaign management, analytics, and automation capabilities.',
      icon: Mail,
      color: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
      keywords: ['Email', 'Marketing'],
      version: '1.0.0',
      features: ['Templates', 'Analytics', 'Automation'],
    },
    {
      name: 'Categories',
      description:
        'Flexible taxonomy system for organizing content with hierarchical structures and tagging.',
      icon: Layers,
      color: 'bg-green-500/10 text-green-600 dark:text-green-400',
      keywords: ['Organization', 'Taxonomy'],
      version: '1.0.0',
      features: ['Hierarchical', 'Tags', 'Filtering'],
    },
    {
      name: 'Media',
      description:
        'File management system with upload, preview, and organization features for assets.',
      icon: Image,
      color: 'bg-orange-500/10 text-orange-600 dark:text-orange-400',
      keywords: ['Files', 'Storage'],
      version: '1.0.0',
      features: ['Upload', 'Preview', 'Organize'],
    },
  ];

  return (
    <LandingLayout>
      <Head title="Welcome" />
      <div className="flex flex-col">
        <section className="relative overflow-hidden border-b py-24 md:py-32">
          <div className="absolute inset-0 bg-background">
            <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(120,119,198,0.15),transparent_50%)]" />
            <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,rgba(59,130,246,0.1),transparent_50%)]" />
            <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,rgba(139,92,246,0.1),transparent_50%)]" />
            <div className="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]" />
          </div>
          <div className="container relative z-10 flex flex-col items-center justify-center px-4">
            <div className="mx-auto max-w-4xl text-center">
              <h1 className="text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl">
                <span className="bg-gradient-to-r from-violet-400 via-blue-400 to-cyan-400 bg-clip-text text-transparent">
                  The Open Source
                </span>
                <br />
                <span className="bg-gradient-to-r from-violet-300 via-blue-300 to-cyan-300 bg-clip-text text-transparent">
                  Modular ERP for Artisans.
                </span>
              </h1>
              <p className="mt-6 text-lg text-muted-foreground sm:text-xl md:text-2xl">
                Built with{' '}
                <span className="font-mono font-semibold text-foreground">Laravel 12</span>,{' '}
                <span className="font-mono font-semibold text-foreground">Inertia</span>, and{' '}
                <span className="font-mono font-semibold text-foreground">React</span>. A fully
                customizable{' '}
                <span className="font-mono font-semibold text-foreground">Modular Monolith</span>{' '}
                architecture designed to scale.
              </p>
              <div className="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <Button asChild size="lg" className="w-full sm:w-auto">
                  <Link href="#">
                    <BookOpen className="mr-2 h-5 w-5" />
                    Read the Docs
                  </Link>
                </Button>
                <Button asChild size="lg" variant="outline" className="w-full sm:w-auto">
                  <a
                    href="https://github.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center"
                  >
                    <Star className="mr-2 h-5 w-5" />
                    Star on GitHub
                  </a>
                </Button>
              </div>
            </div>

            <TerminalWindow />
          </div>
        </section>

        <section className="container px-4 py-16 md:py-24">
          <div className="mx-auto max-w-6xl">
            <div className="mb-12 text-center">
              <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                Powered by Modern Tech
              </h2>
              <p className="mt-4 text-lg text-muted-foreground">
                Built with the latest tools and frameworks for optimal developer experience
              </p>
            </div>
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
              {techStack.map((tech) => {
                const Icon = tech.icon;
                return (
                  <Card
                    key={tech.title}
                    className="group relative overflow-hidden border transition-all duration-300 hover:border-primary/50 hover:shadow-lg"
                  >
                    <div
                      className={`absolute inset-0 bg-gradient-to-br ${tech.gradient} opacity-0 transition-opacity duration-300 group-hover:opacity-100`}
                    />
                    <CardHeader className="relative z-10">
                      <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 transition-all duration-300 group-hover:scale-110">
                        <Icon className="h-6 w-6 text-primary" />
                      </div>
                      <CardTitle className="text-xl font-semibold">{tech.title}</CardTitle>
                      <CardDescription className="mt-2 text-base">
                        {tech.description}
                      </CardDescription>
                    </CardHeader>
                  </Card>
                );
              })}
            </div>
          </div>
        </section>

        <section className="border-t bg-muted/30 py-16 md:py-24">
          <div className="container px-4">
            <div className="mx-auto max-w-6xl">
              <div className="mb-12 text-center">
                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Available Modules</h2>
                <p className="mt-4 text-lg text-muted-foreground">
                  Extend functionality with our growing ecosystem of modules
                </p>
              </div>
              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {modules.map((module) => {
                  const Icon = module.icon;
                  return (
                    <Card
                      key={module.name}
                      className="group relative flex h-full flex-col overflow-hidden border transition-all duration-300 hover:border-primary/30 hover:shadow-md"
                    >
                      <CardHeader className="relative z-10 flex-shrink-0 pb-3">
                        <div className="mb-3 flex items-center gap-3">
                          <div
                            className={`relative flex h-12 w-12 items-center justify-center rounded-xl ${module.color} shadow-md transition-all duration-300 group-hover:scale-110`}
                          >
                            <Icon className="relative z-10 h-6 w-6" />
                          </div>
                          <div className="flex-1 min-w-0">
                            <CardTitle className="text-lg font-semibold">{module.name}</CardTitle>
                            <div className="mt-1 flex items-center gap-2">
                              <Badge variant="outline" className="h-5 text-xs font-normal">
                                v{module.version}
                              </Badge>
                            </div>
                          </div>
                        </div>
                        <CardDescription className="mt-2 h-24 overflow-hidden leading-relaxed">
                          {module.description}
                        </CardDescription>
                      </CardHeader>

                      <CardContent className="relative z-10 flex flex-1 flex-col pb-3">
                        <div className="flex flex-1 flex-col gap-3">
                          <div className="h-8 flex-shrink-0">
                            {module.keywords && module.keywords.length > 0 && (
                              <div className="flex flex-wrap gap-1.5">
                                {module.keywords.map((keyword) => (
                                  <Badge
                                    key={keyword}
                                    variant="outline"
                                    className="h-6 text-xs font-normal transition-colors hover:bg-primary/10 hover:text-primary"
                                  >
                                    <Tag className="mr-1 h-3 w-3" />
                                    {keyword}
                                  </Badge>
                                ))}
                              </div>
                            )}
                          </div>

                          <div className="mt-auto flex-shrink-0">
                            {module.features && module.features.length > 0 && (
                              <div className="space-y-1.5 rounded-lg border bg-muted/30 p-3">
                                <div className="text-xs font-medium text-muted-foreground">
                                  Features
                                </div>
                                <ul className="space-y-1">
                                  {module.features.map((feature, index) => (
                                    <li
                                      key={index}
                                      className="flex items-center gap-2 text-xs text-foreground/80"
                                    >
                                      <div className="h-1.5 w-1.5 rounded-full bg-primary/60" />
                                      {feature}
                                    </li>
                                  ))}
                                </ul>
                              </div>
                            )}
                          </div>
                        </div>
                      </CardContent>

                      <CardFooter className="relative z-10 border-t pt-4">
                        <Button
                          variant="ghost"
                          size="sm"
                          className="w-full group-hover:text-primary"
                          asChild
                        >
                          <Link href="#">
                            View Docs
                            <ArrowRight className="ml-2 h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                          </Link>
                        </Button>
                      </CardFooter>
                    </Card>
                  );
                })}
              </div>
              <div className="mt-8 text-center">
                <p className="text-sm text-muted-foreground">
                  Install only what you need. All modules are open source and customizable.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section className="border-t bg-gradient-to-br from-violet-500/10 via-blue-500/10 to-cyan-500/10 py-16 md:py-24">
          <div className="container px-4">
            <div className="mx-auto max-w-4xl text-center">
              <div className="mb-6 flex items-center justify-center gap-2">
                <GitHub className="h-8 w-8 text-foreground" />
                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                  100% Free & Open Source
                </h2>
              </div>
              <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                Mosaic is released under the{' '}
                <a
                  href="https://opensource.org/licenses/MIT"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="font-mono font-semibold text-foreground underline decoration-violet-400 underline-offset-4 hover:text-violet-400"
                >
                  MIT License
                </a>
                . Use it freely, modify it, and contribute back to the community.
              </p>
              <div className="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <Button asChild variant="outline" size="lg" className="w-full sm:w-auto">
                  <a
                    href="https://github.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center"
                  >
                    <GitHub className="mr-2 h-5 w-5" />
                    Contribute on GitHub
                  </a>
                </Button>
                <Button asChild size="lg" variant="ghost" className="w-full sm:w-auto">
                  <Link href="#">View Documentation</Link>
                </Button>
              </div>
            </div>
          </div>
        </section>
      </div>
    </LandingLayout>
  );
}
