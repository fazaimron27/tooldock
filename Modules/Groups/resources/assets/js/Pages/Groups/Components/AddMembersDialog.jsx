/**
 * Dialog component for adding members to a group.
 *
 * Provides search functionality and user selection interface
 * for adding new members to a group.
 */
import { getInitials } from '@/Utils/format';
import { router } from '@inertiajs/react';
import { isSuperAdmin } from '@modules/Groups/resources/assets/js/Utils/userUtils';
import { Plus, Search, Shield, Users } from 'lucide-react';
import { useState } from 'react';

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';

import MemberDialog from '../../../Components/MemberDialog';

export default function AddMembersDialog({
  open,
  onOpenChange,
  group,
  availableUsers,
  isLoadingAvailableUsers,
  search,
  setSearch,
  onSuccess,
}) {
  const [usersToAdd, setUsersToAdd] = useState([]);
  const [isAddingMembers, setIsAddingMembers] = useState(false);

  const filteredAvailableUsers = availableUsers?.data || [];

  const handleToggleUserToAdd = (userId) => {
    setUsersToAdd((prev) =>
      prev.includes(userId) ? prev.filter((id) => id !== userId) : [...prev, userId]
    );
  };

  const handleAddMembersConfirm = () => {
    if (usersToAdd.length === 0) {
      return;
    }

    setIsAddingMembers(true);
    router.post(
      route('groups.add-members', { group: group.id }),
      { user_ids: usersToAdd },
      {
        onSuccess: () => {
          onOpenChange(false);
          setUsersToAdd([]);
          setIsAddingMembers(false);
          onSuccess?.();
        },
        onError: () => {
          setIsAddingMembers(false);
        },
        preserveScroll: true,
      }
    );
  };

  const handleClose = () => {
    onOpenChange(false);
    setUsersToAdd([]);
  };

  return (
    <MemberDialog
      open={open}
      onOpenChange={handleClose}
      title="Add Members to Group"
      description={
        <>
          Select users to add to <strong>{group.name}</strong>
        </>
      }
      footer={
        <>
          <Button variant="outline" onClick={handleClose} disabled={isAddingMembers}>
            Cancel
          </Button>
          <Button
            onClick={handleAddMembersConfirm}
            disabled={usersToAdd.length === 0 || isAddingMembers}
          >
            {isAddingMembers ? (
              <>Adding...</>
            ) : (
              <>
                <Plus className="mr-2 h-4 w-4" />
                Add {usersToAdd.length > 0 ? `${usersToAdd.length} ` : ''}Member
                {usersToAdd.length !== 1 ? 's' : ''}
              </>
            )}
          </Button>
        </>
      }
    >
      <div className="space-y-4">
        {/* Search Input */}
        <div className="relative">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            type="text"
            placeholder="Search users by name or email..."
            value={search || ''}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9"
          />
        </div>

        {/* Users List */}
        {isLoadingAvailableUsers ? (
          <div className="py-8 text-center text-muted-foreground">
            <Users className="mx-auto h-12 w-12 opacity-50 animate-pulse" />
            <p className="mt-2">Loading users...</p>
          </div>
        ) : filteredAvailableUsers.length === 0 ? (
          <div className="py-8 text-center text-muted-foreground">
            <Users className="mx-auto h-12 w-12 opacity-50" />
            <p className="mt-2">
              {search?.trim()
                ? 'No users found matching your search'
                : availableUsers?.total === 0
                  ? 'All users are already members of this group'
                  : 'No users available'}
            </p>
          </div>
        ) : (
          <div className="max-h-[400px] space-y-2 overflow-y-auto rounded-md border p-2">
            {filteredAvailableUsers.map((user) => {
              const userIsSuperAdmin = isSuperAdmin(user);
              return (
                <div
                  key={user.id}
                  className="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent"
                >
                  <Checkbox
                    checked={usersToAdd.includes(user.id)}
                    onCheckedChange={() => handleToggleUserToAdd(user.id)}
                  />
                  <Avatar className="h-10 w-10 shrink-0">
                    {user.avatar?.url ? (
                      <AvatarImage src={user.avatar.url} alt={user.name} />
                    ) : null}
                    <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
                  </Avatar>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <div className="truncate font-medium">{user.name}</div>
                      {userIsSuperAdmin && (
                        <Badge variant="default" className="text-xs shrink-0">
                          <Shield className="mr-1 h-3 w-3" />
                          Super Admin
                        </Badge>
                      )}
                    </div>
                    <div className="truncate text-sm text-muted-foreground">{user.email}</div>
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {usersToAdd.length > 0 && (
          <div className="rounded-md bg-muted p-3 text-sm">
            <strong>{usersToAdd.length}</strong> user{usersToAdd.length === 1 ? '' : 's'} selected
          </div>
        )}
      </div>
    </MemberDialog>
  );
}
