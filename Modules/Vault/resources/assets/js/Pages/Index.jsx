/**
 * Vault Index page with card-based grid layout
 * Displays vault items in a responsive grid with search, filtering, and pagination
 */
import VaultCard from '@Vault/Components/VaultCard';
import VaultLockButton from '@Vault/Components/VaultLockButton';
import { capitalizeType } from '@Vault/Utils/vaultUtils';
import { Link, router } from '@inertiajs/react';
import { Plus, Search, Star, X } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useDebounce } from 'use-debounce';

import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ vaults, categories = [], types = [] }) {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedType, setSelectedType] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [showFavorites, setShowFavorites] = useState(false);

  const [debouncedSearchQuery] = useDebounce(searchQuery, 300);
  const isInitialMount = useRef(true);

  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }

    router.get(
      route('vault.index'),
      {
        search: debouncedSearchQuery || null,
        type: selectedType || null,
        category_id: selectedCategory || null,
        favorite: showFavorites || null,
      },
      {
        preserveState: true,
        preserveScroll: true,
        only: ['vaults'],
        replace: true,
      }
    );
  }, [debouncedSearchQuery, selectedType, selectedCategory, showFavorites]);

  const handleSearch = useCallback((value) => {
    setSearchQuery(value);
  }, []);

  const handleTypeChange = useCallback((value) => {
    setSelectedType(value);
  }, []);

  const handleCategoryChange = useCallback((value) => {
    setSelectedCategory(value);
  }, []);

  const handleFavoriteToggle = useCallback(() => {
    setShowFavorites((prev) => !prev);
  }, []);

  const clearFilters = useCallback(() => {
    setSearchQuery('');
    setSelectedType('');
    setSelectedCategory('');
    setShowFavorites(false);
    router.get(
      route('vault.index'),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        only: ['vaults'],
        replace: true,
      }
    );
  }, []);

  const hasActiveFilters = useMemo(() => {
    return debouncedSearchQuery.trim() || selectedType || selectedCategory || showFavorites;
  }, [debouncedSearchQuery, selectedType, selectedCategory, showFavorites]);

  const vaultItems = vaults?.data || [];
  const hasResults = vaultItems.length > 0;

  return (
    <DashboardLayout header="Vault">
      <PageShell
        title="Vault"
        actions={
          <div className="flex gap-2">
            <VaultLockButton />
            <Link href={route('vault.create')}>
              <Button>
                <Plus className="mr-2 h-4 w-4" />
                New Item
              </Button>
            </Link>
          </div>
        }
      >
        <div className="space-y-6">
          {/* Search and Filters */}
          <div className="space-y-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                type="text"
                placeholder="Search by name, username, or URL..."
                value={searchQuery}
                onChange={(e) => handleSearch(e.target.value)}
                className="pl-9"
              />
            </div>

            <div className="flex flex-wrap items-center gap-4">
              <Tabs
                value={selectedType || 'all'}
                onValueChange={handleTypeChange}
                className="w-auto"
              >
                <TabsList>
                  <TabsTrigger value="all">All</TabsTrigger>
                  {types.map((type) => (
                    <TabsTrigger key={type} value={type}>
                      {capitalizeType(type)}
                    </TabsTrigger>
                  ))}
                </TabsList>
              </Tabs>

              <Select
                value={selectedCategory || undefined}
                onValueChange={(value) => handleCategoryChange(value || '')}
              >
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="All Categories" />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((category) => (
                    <SelectItem key={category.id} value={category.id}>
                      {category.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Button
                variant={showFavorites ? 'default' : 'outline'}
                size="sm"
                onClick={handleFavoriteToggle}
              >
                <Star className={`mr-2 h-4 w-4 ${showFavorites ? 'fill-current' : ''}`} />
                Favorites
              </Button>

              {hasActiveFilters && (
                <Button variant="ghost" size="sm" onClick={clearFilters}>
                  <X className="mr-2 h-4 w-4" />
                  Clear
                </Button>
              )}
            </div>

            {/* Active Filters Display */}
            {hasActiveFilters && (
              <Card>
                <CardHeader className="pb-3">
                  <div className="flex items-center gap-2 text-sm font-semibold">
                    Active Filters
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex flex-wrap gap-2">
                    {debouncedSearchQuery && (
                      <Badge variant="secondary" className="gap-1.5 pr-1 pl-2.5">
                        Search: {debouncedSearchQuery}
                        <button
                          onClick={() => handleSearch('')}
                          className="ml-1 rounded-full p-0.5 transition-colors hover:bg-destructive/20 hover:text-destructive"
                        >
                          <X className="h-3 w-3" />
                        </button>
                      </Badge>
                    )}
                    {selectedType && (
                      <Badge variant="secondary" className="gap-1.5 pr-1 pl-2.5">
                        Type: {selectedType}
                        <button
                          onClick={() => handleTypeChange('')}
                          className="ml-1 rounded-full p-0.5 transition-colors hover:bg-destructive/20 hover:text-destructive"
                        >
                          <X className="h-3 w-3" />
                        </button>
                      </Badge>
                    )}
                    {selectedCategory && (
                      <Badge variant="secondary" className="gap-1.5 pr-1 pl-2.5">
                        Category: {categories.find((c) => c.id === selectedCategory)?.name}
                        <button
                          onClick={() => handleCategoryChange('')}
                          className="ml-1 rounded-full p-0.5 transition-colors hover:bg-destructive/20 hover:text-destructive"
                        >
                          <X className="h-3 w-3" />
                        </button>
                      </Badge>
                    )}
                    {showFavorites && (
                      <Badge variant="secondary" className="gap-1.5 pr-1 pl-2.5">
                        Favorites Only
                        <button
                          onClick={handleFavoriteToggle}
                          className="ml-1 rounded-full p-0.5 transition-colors hover:bg-destructive/20 hover:text-destructive"
                        >
                          <X className="h-3 w-3" />
                        </button>
                      </Badge>
                    )}
                  </div>
                </CardContent>
              </Card>
            )}
          </div>

          {/* Vault Cards Grid */}
          {hasResults ? (
            <>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {vaultItems.map((vault) => (
                  <VaultCard key={vault.id} vault={vault} />
                ))}
              </div>

              {/* Pagination */}
              {vaults?.links && vaults.links.length > 3 && (
                <div className="flex items-center justify-center gap-2">
                  {vaults.links.map((link, index) => (
                    <Button
                      key={index}
                      variant={link.active ? 'default' : 'outline'}
                      size="sm"
                      disabled={!link.url}
                      onClick={() => {
                        if (link.url) {
                          router.visit(link.url, {
                            preserveState: true,
                            preserveScroll: true,
                            only: ['vaults'],
                          });
                        }
                      }}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                  ))}
                </div>
              )}
            </>
          ) : (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
              <div className="text-muted-foreground">
                {hasActiveFilters ? (
                  <>
                    <p className="text-lg font-medium">No vault items found</p>
                    <p className="text-sm">
                      No items match your current filters. Try adjusting your search or filters.
                    </p>
                    <Button variant="outline" size="sm" onClick={clearFilters} className="mt-4">
                      <X className="mr-2 h-4 w-4" />
                      Clear Filters
                    </Button>
                  </>
                ) : (
                  <>
                    <p className="text-lg font-medium">No vault items yet</p>
                    <p className="text-sm">Get started by creating your first vault item.</p>
                    <Link href={route('vault.create')}>
                      <Button size="sm" className="mt-4">
                        <Plus className="mr-2 h-4 w-4" />
                        Create Vault Item
                      </Button>
                    </Link>
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
