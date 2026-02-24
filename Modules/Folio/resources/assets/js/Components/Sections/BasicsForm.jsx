/**
 * Basics Form Section
 *
 * Form fields for the resume basics: name, headline, email, phone, location, website.
 */
import FormFieldRHF from '@/Components/Common/FormFieldRHF';

export default function BasicsForm({ control }) {
  return (
    <div className="space-y-4">
      <FormFieldRHF name="basics.name" control={control} label="Full Name" placeholder="John Doe" />
      <FormFieldRHF
        name="basics.headline"
        control={control}
        label="Headline"
        placeholder="Software Engineer"
      />
      <FormFieldRHF
        name="basics.email"
        control={control}
        label="Email"
        placeholder="john@example.com"
      />
      <FormFieldRHF
        name="basics.phone"
        control={control}
        label="Phone"
        placeholder="+1 (555) 123-4567"
      />
      <FormFieldRHF
        name="basics.location"
        control={control}
        label="Location"
        placeholder="San Francisco, CA"
      />
      <FormFieldRHF
        name="basics.website.url"
        control={control}
        label="Website URL"
        placeholder="https://example.com"
      />
      <FormFieldRHF
        name="basics.website.label"
        control={control}
        label="Website Label"
        placeholder="Personal Website"
      />
    </div>
  );
}
