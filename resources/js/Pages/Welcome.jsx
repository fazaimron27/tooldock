/**
 * Open Source landing page for Tool Dock - The Safe Harbor for Your Digital Life
 * Developer-focused design showcasing technical stack and community
 */
import { Head, Link } from '@inertiajs/react';
import {
  Anchor,
  ArrowRight,
  Box,
  Briefcase,
  Github,
  Heart,
  ShieldCheck,
  Star,
  Wrench,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

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
 * Features animated typing effect for realistic terminal experience
 */
function TerminalWindow() {
  const lines = useMemo(
    () => [
      {
        prefix: { text: '~', color: 'text-green-400' },
        prompt: { text: '$', color: 'text-blue-400' },
        content: 'php artisan module:manage Cookbook --action=install',
        color: 'text-zinc-300',
      },
      {
        prefix: { text: '>', color: 'text-zinc-400' },
        content: ' Fetching container...',
        color: 'text-zinc-300',
      },
      {
        prefix: { text: '>', color: 'text-zinc-400' },
        content: ' Docking module [Cookbook]...',
        color: 'text-zinc-300',
      },
      {
        prefix: { text: '>', color: 'text-zinc-400' },
        content: ' Module docked successfully. Ready to use.',
        color: 'text-green-400',
      },
    ],
    []
  );

  const [displayedLines, setDisplayedLines] = useState([]);
  const [currentLineIndex, setCurrentLineIndex] = useState(0);
  const [currentCharIndex, setCurrentCharIndex] = useState(0);
  const [showCursor, setShowCursor] = useState(true);

  useEffect(() => {
    if (currentLineIndex >= lines.length) {
      const resetTimer = window.setTimeout(() => {
        setDisplayedLines([]);
        setCurrentLineIndex(0);
        setCurrentCharIndex(0);
      }, 2000);

      return () => window.clearTimeout(resetTimer);
    }

    const currentLine = lines[currentLineIndex];
    const fullText = currentLine.content;

    if (currentCharIndex < fullText.length) {
      const timer = window.setTimeout(() => {
        setCurrentCharIndex((prev) => prev + 1);
      }, 30);

      return () => window.clearTimeout(timer);
    } else {
      const timer = window.setTimeout(() => {
        setDisplayedLines((prev) => [...prev, currentLine]);
        setCurrentLineIndex((prev) => prev + 1);
        setCurrentCharIndex(0);
      }, 500);

      return () => window.clearTimeout(timer);
    }
  }, [currentLineIndex, currentCharIndex, lines]);

  useEffect(() => {
    const cursorTimer = window.setInterval(() => {
      setShowCursor((prev) => !prev);
    }, 530);

    return () => window.clearInterval(cursorTimer);
  }, []);

  const currentLine = lines[currentLineIndex];
  const currentText =
    currentLine && currentLineIndex < lines.length
      ? currentLine.content.substring(0, currentCharIndex)
      : '';

  return (
    <div className="mt-12 w-full max-w-3xl overflow-hidden rounded-lg border border-border/50 bg-zinc-900 shadow-2xl">
      <div className="relative flex items-center border-b border-zinc-800 bg-zinc-950 px-4 py-2">
        <div className="flex gap-1.5">
          <div className="h-3 w-3 rounded-full bg-red-500/80" />
          <div className="h-3 w-3 rounded-full bg-yellow-500/80" />
          <div className="h-3 w-3 rounded-full bg-green-500/80" />
        </div>
        <div className="absolute inset-0 flex items-center justify-center">
          <span className="text-xs font-mono text-zinc-400">Terminal</span>
        </div>
      </div>

      <div className="p-6 font-mono text-sm">
        <div className="space-y-2 h-[160px]">
          {displayedLines.map((line, index) => (
            <div key={index} className="flex items-center gap-2">
              {line.prefix && <span className={line.prefix.color}>{line.prefix.text}</span>}
              {line.prompt && <span className={line.prompt.color}>{line.prompt.text}</span>}
              <span className={line.color}>{line.content}</span>
            </div>
          ))}

          {currentLine && currentLineIndex < lines.length && (
            <div className="flex items-center gap-2">
              {currentLine.prefix && (
                <span className={currentLine.prefix.color}>{currentLine.prefix.text}</span>
              )}
              {currentLine.prompt && (
                <span className={currentLine.prompt.color}>{currentLine.prompt.text}</span>
              )}
              <span className={currentLine.color}>
                {currentText}
                {showCursor && <span className="animate-pulse">_</span>}
              </span>
            </div>
          )}

          {currentLineIndex >= lines.length && (
            <div className="flex items-center gap-2 pt-2">
              <span className="text-green-400">~</span>
              <span className="text-blue-400">$</span>
              <span className={`text-zinc-500 ${showCursor ? 'opacity-100' : 'opacity-0'}`}>_</span>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default function Welcome() {
  const features = [
    {
      icon: Anchor,
      title: 'Built on Granite',
      description:
        'Powered by Laravel 12 & React. Solid, reliable, and 100% open source under MIT License.',
      gradient: 'from-slate-500/20 to-zinc-500/20',
    },
    {
      icon: Box,
      title: 'Infinite Docking',
      description:
        'Install only what you need. From URL Shorteners to Finance trackers. Keep your ship light and modular.',
      gradient: 'from-slate-500/20 to-zinc-500/20',
    },
    {
      icon: ShieldCheck,
      title: 'Private Waters',
      description:
        'No tracking, no monthly fees. Your data stays on your server. You own the code.',
      gradient: 'from-slate-500/20 to-zinc-500/20',
    },
  ];

  // const toolCategories = [
  //   {
  //     category: 'Utilities',
  //     icon: Wrench,
  //     tools: ['ShortLink', 'FileDrop', 'QR Generator'],
  //     iconBg: 'bg-blue-100 dark:bg-blue-500/20',
  //     iconColor: 'text-blue-700 dark:text-blue-400',
  //     badgeColor:
  //       'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-500/30',
  //     gradient: 'from-blue-50/50 to-blue-100/30 dark:from-blue-500/5 dark:to-blue-600/5',
  //   },
  //   {
  //     category: 'Life',
  //     icon: Heart,
  //     tools: ['Cookbook', 'GreenThumb', 'HealthTrack'],
  //     iconBg: 'bg-green-100 dark:bg-green-500/20',
  //     iconColor: 'text-green-700 dark:text-green-400',
  //     badgeColor:
  //       'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-300 border-green-200 dark:border-green-500/30',
  //     gradient: 'from-green-50/50 to-green-100/30 dark:from-green-500/5 dark:to-green-600/5',
  //   },
  //   {
  //     category: 'Work',
  //     icon: Briefcase,
  //     tools: ['Project Manager', 'Finance', 'CRM'],
  //     iconBg: 'bg-slate-100 dark:bg-slate-500/20',
  //     iconColor: 'text-slate-700 dark:text-slate-400',
  //     badgeColor:
  //       'bg-slate-50 dark:bg-slate-500/10 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-500/30',
  //     gradient: 'from-slate-50/50 to-slate-100/30 dark:from-slate-500/5 dark:to-slate-600/5',
  //   },
  // ];

  return (
    <LandingLayout>
      <Head title="Welcome" />
      <div className="flex flex-col">
        <section className="relative overflow-hidden border-b py-24 md:py-32">
          <div className="absolute inset-0 bg-background">
            <div className="absolute inset-0 bg-gradient-to-b from-slate-50/80 via-background to-slate-50/60 dark:from-slate-950/50 dark:via-background dark:to-slate-950/40" />
            <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(71,85,105,0.25),transparent_50%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(71,85,105,0.15),transparent_50%)]" />
            <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,rgba(59,130,246,0.2),transparent_50%)] dark:bg-[radial-gradient(ellipse_at_bottom_right,rgba(59,130,246,0.1),transparent_50%)]" />
            <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,rgba(100,116,139,0.2),transparent_50%)] dark:bg-[radial-gradient(ellipse_at_bottom_left,rgba(100,116,139,0.1),transparent_50%)]" />
            <div className="absolute inset-0 bg-[linear-gradient(to_right,#80808015_1px,transparent_1px),linear-gradient(to_bottom,#80808015_1px,transparent_1px)] bg-[size:24px_24px]" />
          </div>
          <div className="container relative z-10 flex flex-col items-center justify-center px-4">
            <div className="mx-auto max-w-4xl text-center">
              <h1 className="text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl">
                <span className="bg-gradient-to-r from-slate-600 via-slate-500 to-slate-600 bg-clip-text text-transparent dark:from-slate-300 dark:via-slate-200 dark:to-slate-300">
                  Tool Dock: The Open Source
                </span>
                <br />
                <span className="bg-gradient-to-r from-slate-500 via-slate-400 to-slate-500 bg-clip-text text-transparent dark:from-slate-200 dark:via-slate-100 dark:to-slate-200">
                  Digital Harbor.
                </span>
              </h1>
              <p className="mt-6 text-lg font-medium leading-relaxed text-slate-700 sm:text-xl md:text-2xl dark:text-slate-300">
                Stop letting your data drift across dozens of SaaS subscriptions. Anchor your
                workflow, utilities, and life management in one secure, self-hosted dock.
              </p>
              <div className="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <Button asChild size="lg" className="w-full sm:w-auto">
                  <Link href={route('register')}>Dock Your First Tool</Link>
                </Button>
                <Button asChild size="lg" variant="outline" className="w-full sm:w-auto">
                  <a
                    href="https://github.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center"
                  >
                    <Github className="mr-2 h-5 w-5" />
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
              <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Philosophy & Tech</h2>
              <p className="mt-4 text-lg text-muted-foreground">
                Built with the latest tools and frameworks for optimal developer experience
              </p>
            </div>
            <div className="grid gap-6 md:grid-cols-3">
              {features.map((feature) => {
                const Icon = feature.icon;
                return (
                  <Card
                    key={feature.title}
                    className="group relative overflow-hidden border transition-all duration-300 hover:border-blue-500/50 hover:shadow-lg"
                  >
                    <div
                      className={`absolute inset-0 bg-gradient-to-br ${feature.gradient} opacity-0 transition-opacity duration-300 group-hover:opacity-100`}
                    />
                    <CardHeader className="relative z-10">
                      <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-500/10 transition-all duration-300 group-hover:scale-110">
                        <Icon className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                      </div>
                      <CardTitle className="text-xl font-semibold">{feature.title}</CardTitle>
                      <CardDescription className="mt-2 text-base">
                        {feature.description}
                      </CardDescription>
                    </CardHeader>
                  </Card>
                );
              })}
            </div>
          </div>
        </section>

        {/* <section className="border-t bg-muted/30 py-16 md:py-24">
          <div className="container px-4">
            <div className="mx-auto max-w-6xl">
              <div className="mb-12 text-center">
                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Available Tools</h2>
                <p className="mt-4 text-lg text-muted-foreground">
                  Showcase the versatility of the open ecosystem
                </p>
              </div>
              <div className="grid gap-6 md:grid-cols-3">
                {toolCategories.map((category) => {
                  const Icon = category.icon;
                  return (
                    <Card
                      key={category.category}
                      className="group relative overflow-hidden border bg-card transition-all duration-300 hover:border-blue-500/50 hover:shadow-lg dark:hover:border-blue-500/30"
                    >
                      <div
                        className={`absolute inset-0 bg-gradient-to-br ${category.gradient} opacity-100 transition-opacity duration-300 group-hover:opacity-100`}
                      />
                      <CardHeader className="relative z-10">
                        <div className="mb-4 flex items-center gap-3">
                          <div
                            className={`flex h-12 w-12 items-center justify-center rounded-lg ${category.iconBg} ${category.iconColor} transition-transform duration-300 group-hover:scale-110 shadow-sm`}
                          >
                            <Icon className="h-6 w-6" />
                          </div>
                          <CardTitle className="text-xl font-semibold text-foreground">
                            {category.category}
                          </CardTitle>
                        </div>
                        <div className="flex flex-wrap gap-2">
                          {category.tools.map((tool) => (
                            <Badge
                              key={tool}
                              variant="outline"
                              className={`${category.badgeColor} font-medium transition-all duration-200 hover:scale-105 hover:shadow-sm`}
                            >
                              {tool}
                            </Badge>
                          ))}
                        </div>
                      </CardHeader>
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
        </section> */}

        <section className="border-t bg-gradient-to-br from-slate-500/10 via-zinc-500/10 to-slate-500/10 py-16 md:py-24">
          <div className="container px-4">
            <div className="mx-auto max-w-4xl text-center">
              <div className="mb-6 flex items-center justify-center gap-2">
                <Github className="h-8 w-8 text-foreground" />
                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                  100% Free & Open Source
                </h2>
              </div>
              <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                Tool Dock is released under the{' '}
                <a
                  href="https://opensource.org/licenses/MIT"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="font-mono font-semibold text-foreground underline decoration-blue-500 underline-offset-4 hover:text-blue-600 dark:hover:text-blue-400"
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
                    <Github className="mr-2 h-5 w-5" />
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
