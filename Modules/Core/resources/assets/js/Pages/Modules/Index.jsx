/**
 * Module Manager page - displays all available modules in a grid layout
 * Separates system modules (protected) from user-installable modules
 * Allows installation, uninstallation, and enabling/disabling of modules
 * Supports filtering by keywords and search
 */
import ModuleCard from '@Core/Components/ModuleCard';
import { Filter, Search, Shield, Tag, X } from 'lucide-react';
import { useMemo, useState } from 'react';

import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ modules = [] }) {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedKeywords, setSelectedKeywords] = useState([]);

  // Extract all unique keywords from all modules
  const allKeywords = useMemo(() => {
    const keywordSet = new Set();
    modules.forEach((module) => {
      if (module.keywords && Array.isArray(module.keywords)) {
        module.keywords.forEach((keyword) => keywordSet.add(keyword.toLowerCase()));
      }
    });
    return Array.from(keywordSet).sort();
  }, [modules]);

  const filteredModules = useMemo(() => {
    let filtered = modules;

    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(
        (module) =>
          module.name.toLowerCase().includes(query) ||
          (module.description && module.description.toLowerCase().includes(query)) ||
          (module.keywords &&
            module.keywords.some((keyword) => keyword.toLowerCase().includes(query)))
      );
    }

    if (selectedKeywords.length > 0) {
      filtered = filtered.filter((module) => {
        if (!module.keywords || !Array.isArray(module.keywords)) {
          return false;
        }
        return selectedKeywords.some((selectedKeyword) =>
          module.keywords.some((keyword) => keyword.toLowerCase() === selectedKeyword)
        );
      });
    }

    return filtered;
  }, [modules, searchQuery, selectedKeywords]);

  const toggleKeyword = (keyword) => {
    setSelectedKeywords((prev) => {
      if (prev.includes(keyword)) {
        return prev.filter((k) => k !== keyword);
      }
      return [...prev, keyword];
    });
  };

  const clearFilters = () => {
    setSearchQuery('');
    setSelectedKeywords([]);
  };

  const hasActiveFilters = searchQuery.trim() || selectedKeywords.length > 0;

  const protectedModules = useMemo(
    () => filteredModules.filter((module) => module.protected),
    [filteredModules]
  );

  const availableModules = useMemo(
    () => filteredModules.filter((module) => !module.protected),
    [filteredModules]
  );

  const hasResults = filteredModules.length > 0;
  const hasProtected = protectedModules.length > 0;
  const hasAvailable = availableModules.length > 0;

  return (
    <DashboardLayout header="Module Manager">
      <PageShell title="Module Manager">
        <div className="space-y-8">
          <div className="space-y-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                type="text"
                placeholder="Search modules by name, description, or keywords..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-9"
              />
            </div>

            {allKeywords.length > 0 && (
              <Card>
                <CardHeader className="pb-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Filter className="h-4 w-4 text-muted-foreground" />
                      <h3 className="text-sm font-semibold">Filter by Keywords</h3>
                      {selectedKeywords.length > 0 && (
                        <Badge variant="secondary" className="ml-2">
                          {selectedKeywords.length} selected
                        </Badge>
                      )}
                    </div>
                    {hasActiveFilters && (
                      <Button variant="ghost" size="sm" onClick={clearFilters}>
                        <X className="mr-2 h-4 w-4" />
                        Clear All
                      </Button>
                    )}
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex flex-wrap gap-2">
                    {allKeywords.map((keyword) => {
                      const isSelected = selectedKeywords.includes(keyword);
                      return (
                        <Badge
                          key={keyword}
                          variant={isSelected ? 'default' : 'outline'}
                          className="cursor-pointer px-3 py-1.5 text-sm font-medium transition-all hover:scale-105 hover:shadow-sm active:scale-95"
                          onClick={() => toggleKeyword(keyword)}
                        >
                          <Tag
                            className={`mr-1.5 h-3 w-3 ${isSelected ? 'text-primary-foreground' : 'text-muted-foreground'}`}
                          />
                          {keyword}
                        </Badge>
                      );
                    })}
                  </div>
                  {selectedKeywords.length > 0 && (
                    <div className="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/30 p-3">
                      <span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Active Filters:
                      </span>
                      {selectedKeywords.map((keyword) => (
                        <Badge key={keyword} variant="secondary" className="gap-1.5 pr-1 pl-2.5">
                          <Tag className="h-3 w-3" />
                          {keyword}
                          <button
                            onClick={() => toggleKeyword(keyword)}
                            className="ml-1 rounded-full p-0.5 transition-colors hover:bg-destructive/20 hover:text-destructive"
                            aria-label={`Remove ${keyword} filter`}
                          >
                            <X className="h-3 w-3" />
                          </button>
                        </Badge>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>
            )}
          </div>

          {hasProtected && (
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <Shield className="h-5 w-5 text-muted-foreground" />
                <div>
                  <h2 className="text-xl font-semibold">System Modules</h2>
                  <p className="text-sm text-muted-foreground">
                    Essential system modules that cannot be disabled or uninstalled
                  </p>
                </div>
              </div>
              <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                {protectedModules.map((module) => (
                  <ModuleCard key={module.name} module={module} onKeywordClick={toggleKeyword} />
                ))}
              </div>
            </div>
          )}

          {hasProtected && hasAvailable && <div className="border-t pt-8" />}

          {hasAvailable && (
            <div className="space-y-4">
              <div>
                <h2 className="text-xl font-semibold">Available Modules</h2>
                <p className="text-sm text-muted-foreground">
                  Install and manage additional modules to extend functionality
                </p>
              </div>
              <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                {availableModules.map((module) => (
                  <ModuleCard key={module.name} module={module} onKeywordClick={toggleKeyword} />
                ))}
              </div>
            </div>
          )}

          {!hasResults && (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
              <div className="text-muted-foreground">
                {hasActiveFilters ? (
                  <>
                    <p className="text-lg font-medium">No modules found</p>
                    <p className="text-sm">
                      No modules match your {searchQuery ? 'search' : ''}
                      {searchQuery && selectedKeywords.length > 0 ? ' and ' : ''}
                      {selectedKeywords.length > 0 ? 'keyword filter' : ''}.
                    </p>
                    <Button variant="outline" size="sm" onClick={clearFilters} className="mt-4">
                      <X className="mr-2 h-4 w-4" />
                      Clear Filters
                    </Button>
                  </>
                ) : (
                  <>
                    <p className="text-lg font-medium">No modules available</p>
                    <p className="text-sm">There are no modules available to display.</p>
                  </>
                )}
              </div>
            </div>
          )}
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
