/**
 * Summary Form Section
 *
 * A dedicated rich-text editor for the professional summary / about section.
 */
import RichTextFieldRHF from '@/Components/Common/RichTextFieldRHF';

export default function SummaryForm({ control }) {
  return (
    <div className="space-y-4">
      <RichTextFieldRHF
        name="basics.summary"
        control={control}
        placeholder="A brief professional summary highlighting your experience, skills, and career objectives..."
      />
    </div>
  );
}
