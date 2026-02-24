import { ChevronDown, ChevronUp, GripVertical } from 'lucide-react';
import { useWatch } from 'react-hook-form';

const SECTION_DISPLAY = {
  summary: 'Summary',
  profiles: 'Profiles',
  skills: 'Skills',
  work: 'Experience',
  education: 'Education',
  projects: 'Projects',
  volunteering: 'Volunteering',
  references: 'References',
  interests: 'Interests',
  certifications: 'Certifications',
  awards: 'Awards',
  publications: 'Publications',
  languages: 'Languages',
};

function LayoutItem({ label, onMoveUp, onMoveDown, isFirst, isLast }) {
  return (
    <div className="flex items-center gap-1 rounded-md bg-muted/50 px-3 py-1.5 group">
      <GripVertical className="h-4 w-4 text-muted-foreground/40 shrink-0" />
      <span className="text-sm flex-1">{label}</span>
      <button
        type="button"
        onClick={onMoveUp}
        disabled={isFirst}
        className="p-0.5 rounded hover:bg-accent disabled:opacity-20 disabled:pointer-events-none"
      >
        <ChevronUp className="h-3.5 w-3.5" />
      </button>
      <button
        type="button"
        onClick={onMoveDown}
        disabled={isLast}
        className="p-0.5 rounded hover:bg-accent disabled:opacity-20 disabled:pointer-events-none"
      >
        <ChevronDown className="h-3.5 w-3.5" />
      </button>
    </div>
  );
}

/**
 * Layout ordering panel — lets users reorder resume sections.
 */
export default function LayoutPanel({ control, setValue }) {
  const order = useWatch({ control, name: 'settings.sectionOrder' }) || [];

  const move = (index, direction) => {
    const next = [...order];
    const target = index + direction;
    if (target < 0 || target >= next.length) return;
    [next[index], next[target]] = [next[target], next[index]];
    setValue('settings.sectionOrder', next, { shouldDirty: true });
  };

  return (
    <div className="space-y-2">
      <div className="space-y-1">
        {order.map((key, i) => (
          <LayoutItem
            key={key}
            label={SECTION_DISPLAY[key] || key}
            isFirst={i === 0}
            isLast={i === order.length - 1}
            onMoveUp={() => move(i, -1)}
            onMoveDown={() => move(i, 1)}
          />
        ))}
      </div>
    </div>
  );
}
