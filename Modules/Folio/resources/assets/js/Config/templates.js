/**
 * Resume template definitions.
 *
 * Each template provides metadata used by TemplateSelector and ResumePreview.
 * Layout and styling are handled by the individual template components.
 */
const templates = {
  professional: {
    id: 'professional',
    name: 'Professional',
    description:
      'Clean single-column layout with serif typography. Great for corporate and tech roles.',
    preview: { accent: '#6366f1' },
  },
  modern: {
    id: 'modern',
    name: 'Modern',
    description:
      'Two-column layout with sidebar for contact and skills. Ideal for startups and creative tech.',
    preview: { accent: '#059669' },
  },
  elegant: {
    id: 'elegant',
    name: 'Elegant',
    description:
      'Centered serif typography with refined spacing. Perfect for executive and academic roles.',
    preview: { accent: '#57534e' },
  },
  bold: {
    id: 'bold',
    name: 'Bold',
    description:
      'High-contrast layout with heavy typography and sharp lines. Stands out in competitive fields.',
    preview: { accent: '#e11d48' },
  },
};

export default templates;
