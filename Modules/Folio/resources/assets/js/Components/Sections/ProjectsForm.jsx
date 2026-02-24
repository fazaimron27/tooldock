/**
 * Projects Form Section
 *
 * Compact list view with dialog editing.
 * Follows project dialog pattern (open/onOpenChange, px-6 spacing).
 */
import { MoreVertical, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useFieldArray, useWatch } from 'react-hook-form';

import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import RichTextFieldRHF from '@/Components/Common/RichTextFieldRHF';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const uuidv4 = () => crypto.randomUUID();

function ProjectItemRow({ control, index, onEdit, onRemove }) {
  const name = useWatch({ control, name: `sections.projects.items.${index}.name` });
  const period = useWatch({ control, name: `sections.projects.items.${index}.period` });

  return (
    <div className="group flex items-center gap-3 rounded-lg border bg-card px-4 py-3 transition-colors hover:bg-accent/50">
      <div className="flex flex-1 cursor-pointer items-center gap-3 min-w-0" onClick={onEdit}>
        <div className="flex h-8 w-1 rounded-full bg-primary/20 group-hover:bg-primary/50 transition-colors" />
        <div className="flex-1 min-w-0">
          <p className="text-sm font-medium truncate">{name || 'Untitled Project'}</p>
          <p className="text-xs text-muted-foreground truncate">
            {period || 'No period specified'}
          </p>
        </div>
      </div>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="ghost"
            size="icon"
            className="h-7 w-7 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <MoreVertical className="h-3.5 w-3.5" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem onClick={onEdit}>
            <Pencil className="mr-2 h-3.5 w-3.5" />
            Edit
          </DropdownMenuItem>
          <DropdownMenuItem className="text-destructive" onClick={onRemove}>
            <Trash2 className="mr-2 h-3.5 w-3.5" />
            Delete
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}

function ProjectEditDialog({ open, onOpenChange, control, index, isNew }) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader className="px-6 pt-6 pb-2">
          <DialogTitle>{isNew ? 'Add Project' : 'Edit Project'}</DialogTitle>
          <DialogDescription>
            {isNew ? 'Fill in the details for your project.' : 'Update your project details.'}
          </DialogDescription>
        </DialogHeader>
        {index !== null && (
          <div className="space-y-4 px-6 py-4">
            <div className="grid grid-cols-2 gap-3">
              <FormFieldRHF
                name={`sections.projects.items.${index}.name`}
                control={control}
                label="Project Name"
                placeholder="Resume Builder"
              />
              <FormFieldRHF
                name={`sections.projects.items.${index}.period`}
                control={control}
                label="Period"
                placeholder="Mar 2023 – Present"
              />
            </div>
            <div className="grid grid-cols-2 gap-3">
              <FormFieldRHF
                name={`sections.projects.items.${index}.website.url`}
                control={control}
                label="URL"
                placeholder="https://github.com/user/project"
              />
              <FormFieldRHF
                name={`sections.projects.items.${index}.website.label`}
                control={control}
                label="URL Label"
                placeholder="GitHub"
              />
            </div>
            <RichTextFieldRHF
              name={`sections.projects.items.${index}.description`}
              control={control}
              label="Description"
              placeholder="Project details and technologies used..."
            />
          </div>
        )}
        <DialogFooter className="px-6 pb-6 pt-2">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Close
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function ProjectsForm({ control }) {
  const [editIndex, setEditIndex] = useState(null);

  const { fields, append, remove } = useFieldArray({
    control,
    name: 'sections.projects.items',
  });

  const addItem = () => {
    append({
      id: uuidv4(),
      name: '',
      period: '',
      website: { url: '', label: '' },
      description: '',
    });
    setEditIndex(fields.length);
  };

  return (
    <div className="space-y-2">
      {fields.map((field, index) => (
        <ProjectItemRow
          key={field.id}
          control={control}
          index={index}
          onEdit={() => setEditIndex(index)}
          onRemove={() => {
            setEditIndex(null);
            remove(index);
          }}
        />
      ))}

      <Button type="button" variant="outline" className="w-full" onClick={addItem}>
        <Plus className="mr-2 h-4 w-4" />
        Add Project
      </Button>

      <ProjectEditDialog
        open={editIndex !== null}
        onOpenChange={(open) => !open && setEditIndex(null)}
        control={control}
        index={editIndex}
        isNew={editIndex !== null && !fields[editIndex]?.name}
      />
    </div>
  );
}
