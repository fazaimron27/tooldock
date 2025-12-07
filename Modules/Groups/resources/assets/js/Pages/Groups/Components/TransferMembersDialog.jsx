/**
 * Dialog component for transferring members between groups.
 *
 * Allows bulk transfer of selected members from the current group
 * to another target group.
 */
import { getInitials } from '@/Utils/format';
import { router } from '@inertiajs/react';
import { isSuperAdmin } from '@modules/Groups/resources/assets/js/Utils/userUtils';
import { ArrowRight, ArrowRightLeft, Shield, UserPlus, Users } from 'lucide-react';
import { useState } from 'react';

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

import MemberDialog from '../../../Components/MemberDialog';

export default function TransferMembersDialog({
  open,
  onOpenChange,
  group,
  availableGroups,
  selectedRows,
  selectedMemberIds,
  onSuccess,
}) {
  const [targetGroupId, setTargetGroupId] = useState('');
  const [isTransferring, setIsTransferring] = useState(false);

  const handleTransferConfirm = () => {
    if (selectedMemberIds.length === 0 || !targetGroupId) {
      return;
    }

    setIsTransferring(true);
    router.post(
      route('groups.transfer-members', { group: group.id }),
      {
        user_ids: selectedMemberIds,
        target_group_id: targetGroupId,
      },
      {
        onSuccess: () => {
          onOpenChange(false);
          setTargetGroupId('');
          setIsTransferring(false);
          onSuccess?.();
        },
        onError: () => {
          setIsTransferring(false);
        },
        preserveScroll: true,
      }
    );
  };

  const handleCancel = () => {
    onOpenChange(false);
    setTargetGroupId('');
  };

  return (
    <MemberDialog
      open={open}
      onOpenChange={handleCancel}
      title="Transfer Members to Another Group"
      description={
        <>
          Transfer {selectedMemberIds.length} selected member
          {selectedMemberIds.length === 1 ? '' : 's'} from <strong>{group.name}</strong> to another
          group
        </>
      }
      maxWidth="500px"
      footer={
        <>
          <Button variant="outline" onClick={handleCancel} disabled={isTransferring}>
            Cancel
          </Button>
          <Button
            onClick={handleTransferConfirm}
            disabled={!targetGroupId || isTransferring || availableGroups.length === 0}
          >
            {isTransferring ? (
              <>Transferring...</>
            ) : (
              <>
                <ArrowRightLeft className="mr-2 h-4 w-4" />
                Transfer {selectedMemberIds.length} Member
                {selectedMemberIds.length === 1 ? '' : 's'}
              </>
            )}
          </Button>
        </>
      }
    >
      <div className="space-y-6">
        {/* Selected Users Preview */}
        <div className="space-y-2">
          <Label className="text-sm font-medium">
            Selected Members ({selectedMemberIds.length})
          </Label>
          <div className="max-h-[200px] space-y-2 overflow-y-auto rounded-md border p-2">
            {selectedRows.slice(0, 10).map((row) => {
              const user = row.original;
              const userIsSuperAdmin = isSuperAdmin(user);
              return (
                <div key={user.id} className="flex items-center gap-2 rounded-lg border p-2">
                  <Avatar className="h-8 w-8 shrink-0">
                    {user.avatar?.url ? (
                      <AvatarImage src={user.avatar.url} alt={user.name} />
                    ) : null}
                    <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
                  </Avatar>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <div className="truncate text-sm font-medium">{user.name}</div>
                      {userIsSuperAdmin && (
                        <Badge variant="default" className="text-xs shrink-0">
                          <Shield className="mr-1 h-3 w-3" />
                          Super Admin
                        </Badge>
                      )}
                    </div>
                    <div className="truncate text-xs text-muted-foreground">{user.email}</div>
                  </div>
                </div>
              );
            })}
            {selectedRows.length > 10 && (
              <div className="text-center text-sm text-muted-foreground">
                and {selectedRows.length - 10} more...
              </div>
            )}
          </div>
        </div>

        {/* Transfer Flow */}
        <div className="space-y-4">
          {/* From Group */}
          <div className="space-y-2">
            <Label className="text-sm font-medium text-muted-foreground">Current Group</Label>
            <div className="flex items-center gap-2 rounded-md border bg-background px-3 py-2.5">
              <Users className="h-4 w-4 shrink-0 text-muted-foreground" />
              <span className="truncate font-medium">{group.name}</span>
            </div>
          </div>

          {/* Arrow Icon */}
          <div className="flex justify-center">
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
              <ArrowRight className="h-4 w-4 text-primary" />
            </div>
          </div>

          {/* To Group Select */}
          <div className="space-y-2">
            <Label htmlFor="bulk-target-group" className="text-sm font-medium">
              Target Group
            </Label>
            <Select value={targetGroupId} onValueChange={setTargetGroupId}>
              <SelectTrigger id="bulk-target-group" className="h-11">
                <SelectValue placeholder="Select a group to transfer to..." />
              </SelectTrigger>
              <SelectContent>
                {availableGroups.length === 0 ? (
                  <div className="px-2 py-6 text-center text-sm text-muted-foreground">
                    No other groups available
                  </div>
                ) : (
                  availableGroups.map((g) => (
                    <SelectItem key={g.id} value={String(g.id)}>
                      <div className="flex items-center gap-2">
                        <UserPlus className="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span className="truncate">{g.name}</span>
                      </div>
                    </SelectItem>
                  ))
                )}
              </SelectContent>
            </Select>
            {availableGroups.length === 0 && (
              <p className="text-xs text-muted-foreground">
                Create another group first to enable transfers
              </p>
            )}
          </div>
        </div>

        {/* Info Message */}
        <div className="rounded-md border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
          <div className="flex items-start gap-2">
            <Shield className="mt-0.5 h-4 w-4 shrink-0" />
            <div className="min-w-0 flex-1">
              The selected member{selectedMemberIds.length === 1 ? '' : 's'} will be{' '}
              <strong>removed</strong> from <strong>{group.name}</strong> and <strong>added</strong>{' '}
              to the selected group. Their permissions will be updated accordingly.
            </div>
          </div>
        </div>
      </div>
    </MemberDialog>
  );
}
