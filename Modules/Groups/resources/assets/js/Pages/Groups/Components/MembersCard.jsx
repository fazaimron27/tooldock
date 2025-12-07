/**
 * Card component displaying group members with management actions.
 */
import { ArrowRightLeft, Plus, UserMinus, Users } from 'lucide-react';

import DataTable from '@/Components/DataDisplay/DataTable';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

export default function MembersCard({
  group,
  members,
  memberTableProps,
  selectedMemberIds,
  availableGroups,
  isBulkTransferring,
  isRemovingMembers,
  onAddMembersClick,
  onBulkTransferClick,
  onRemoveMembersClick,
  onClearSelection,
}) {
  const memberCount = members?.total ?? group.users?.length ?? 0;

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Users className="h-5 w-5 text-primary" />
            <CardTitle>Members</CardTitle>
          </div>
          <Badge variant="secondary">
            {memberCount} {memberCount === 1 ? 'member' : 'members'}
          </Badge>
        </div>
        <CardDescription>
          Users assigned to this group. Super Admin users have full system access regardless of
          group membership.
        </CardDescription>
      </CardHeader>
      <CardContent>
        {!members?.data?.length && !group.users?.length ? (
          <div className="space-y-4">
            <div className="py-8 text-center text-muted-foreground">
              <Users className="mx-auto h-12 w-12 opacity-50" />
              <p className="mt-2">No members assigned to this group</p>
            </div>
            <div className="flex justify-center">
              <Button onClick={onAddMembersClick}>
                <Plus className="mr-2 h-4 w-4" />
                Add Members
              </Button>
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            {/* Bulk Actions Toolbar */}
            <div className="flex flex-wrap items-center gap-2">
              <Button onClick={onAddMembersClick} size="sm">
                <Plus className="mr-2 h-4 w-4" />
                Add Members
              </Button>
              {selectedMemberIds.length > 0 && (
                <>
                  {availableGroups.length > 0 && (
                    <Button
                      onClick={onBulkTransferClick}
                      variant="outline"
                      size="sm"
                      disabled={isBulkTransferring}
                    >
                      <ArrowRightLeft className="mr-2 h-4 w-4" />
                      Transfer Selected ({selectedMemberIds.length})
                    </Button>
                  )}
                  <Button
                    onClick={onRemoveMembersClick}
                    variant="destructive"
                    size="sm"
                    disabled={isRemovingMembers}
                  >
                    <UserMinus className="mr-2 h-4 w-4" />
                    Remove Selected ({selectedMemberIds.length})
                  </Button>
                  <Button onClick={onClearSelection} variant="ghost" size="sm">
                    Clear Selection
                  </Button>
                </>
              )}
            </div>

            <DataTable
              {...memberTableProps}
              searchable={true}
              pagination={true}
              sorting={true}
              showCard={false}
            />
          </div>
        )}
      </CardContent>
    </Card>
  );
}
