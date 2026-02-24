/**
 * Builder Form Pane
 *
 * The left pane of the Builder: renders all form sections as accordion items
 * (data-driven from FORM_SECTIONS) plus custom sections and the "Add Custom
 * Section" dialog. This extracts ~240 lines from Builder.jsx.
 */
import CustomSectionForm from '@Folio/Components/Sections/CustomSectionForm';
import FORM_SECTIONS from '@Folio/constants/formSections';
import { Plus, Puzzle, Trash2 } from 'lucide-react';
import { useState } from 'react';

import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/Components/ui/accordion';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function BuilderFormPane({ control, customSections, appendCustom, removeCustom }) {
  const [customDialogOpen, setCustomDialogOpen] = useState(false);
  const [customSectionTitle, setCustomSectionTitle] = useState('');

  const addCustomSection = () => {
    if (!customSectionTitle.trim()) return;
    appendCustom({
      id: globalThis.crypto.randomUUID(),
      title: customSectionTitle.trim(),
      items: [],
    });
    setCustomSectionTitle('');
    setCustomDialogOpen(false);
  };

  return (
    <div className="w-[380px] shrink-0 overflow-y-auto border-r pr-4">
      <Accordion type="multiple" defaultValue={['basics']} className="space-y-2">
        {FORM_SECTIONS.map(({ key, label, icon: Icon, Form }) => (
          <AccordionItem key={key} value={key} className="border rounded-lg px-4">
            <AccordionTrigger className="hover:no-underline">
              <div className="flex items-center gap-2">
                <Icon className="h-4 w-4 text-primary" />
                <span>{label}</span>
              </div>
            </AccordionTrigger>
            <AccordionContent>
              <Form control={control} />
            </AccordionContent>
          </AccordionItem>
        ))}

        {customSections.map((section, sectionIndex) => (
          <AccordionItem
            key={section.id}
            value={`custom-${section.id}`}
            className="border rounded-lg px-4"
          >
            <AccordionTrigger className="hover:no-underline">
              <div className="flex items-center gap-2 flex-1">
                <Puzzle className="h-4 w-4 text-primary" />
                <span>{section.title || 'Custom Section'}</span>
              </div>
            </AccordionTrigger>
            <AccordionContent>
              <CustomSectionForm control={control} sectionIndex={sectionIndex} />
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="w-full mt-2 text-destructive hover:text-destructive"
                onClick={() => removeCustom(sectionIndex)}
              >
                <Trash2 className="mr-2 h-3.5 w-3.5" />
                Remove Section
              </Button>
            </AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>

      <Button
        type="button"
        variant="outline"
        className="w-full mt-3"
        onClick={() => setCustomDialogOpen(true)}
      >
        <Plus className="mr-2 h-4 w-4" />
        Add Custom Section
      </Button>

      <Dialog open={customDialogOpen} onOpenChange={setCustomDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader className="px-6 pt-6 pb-2">
            <DialogTitle>Add Custom Section</DialogTitle>
            <DialogDescription>Enter a title for your new section.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4 px-6 py-4">
            <div>
              <Label htmlFor="custom-section-title">Section Title</Label>
              <Input
                id="custom-section-title"
                className="mt-2"
                placeholder="e.g. Hobbies, Courses, Patents..."
                value={customSectionTitle}
                onChange={(e) => setCustomSectionTitle(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && addCustomSection()}
                autoFocus
              />
            </div>
          </div>
          <DialogFooter className="px-6 pb-6 pt-2">
            <Button variant="outline" onClick={() => setCustomDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={addCustomSection} disabled={!customSectionTitle.trim()}>
              Add Section
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
