import { Loader2, Pencil, Save } from 'lucide-react';

export const STATUS_MAP = {
  idle: { label: 'Ready', variant: 'secondary', icon: null },
  unsaved: { label: 'Unsaved', variant: 'outline', icon: Pencil },
  saving: { label: 'Saving...', variant: 'outline', icon: Loader2 },
  saved: { label: 'Saved', variant: 'default', icon: Save },
  error: { label: 'Error saving', variant: 'destructive', icon: null },
};

export const DEFAULT_CONTENT = {
  template: 'professional',
  basics: {
    name: '',
    headline: '',
    email: '',
    phone: '',
    location: '',
    summary: '',
    website: { url: '', label: '' },
  },
  sections: {
    profiles: { title: 'Profiles', items: [] },
    work: { title: 'Work Experience', items: [] },
    education: { title: 'Education', items: [] },
    projects: { title: 'Projects', items: [] },
    skills: { title: 'Skills', items: [] },
    languages: { title: 'Languages', items: [] },
    interests: { title: 'Interests', items: [] },
    awards: { title: 'Awards', items: [] },
    certifications: { title: 'Certifications', items: [] },
    publications: { title: 'Publications', items: [] },
    volunteering: { title: 'Volunteering', items: [] },
    references: { title: 'References', items: [] },
    custom: [],
  },
  settings: {
    sectionOrder: [
      'summary',
      'profiles',
      'skills',
      'work',
      'education',
      'projects',
      'volunteering',
      'references',
      'interests',
      'certifications',
      'awards',
      'publications',
      'languages',
    ],
    typography: {
      body: { fontFamily: 'inter', fontSize: 10.5, lineHeight: 1.5 },
      heading: { fontFamily: 'inter', fontSize: 13.5, lineHeight: 1.5 },
    },
    design: {
      primaryColor: '#dc2626',
      textColor: '#000000',
      backgroundColor: '#ffffff',
    },
    page: {
      format: 'a4',
      marginHorizontal: 18,
      marginVertical: 18,
      spacingHorizontal: 4,
      spacingVertical: 6,
    },
  },
};

/**
 * Deep-merge server content with defaults so no field is null.
 */
export function mergeDefaults(defaults, data) {
  if (!data) return defaults;
  const result = { ...defaults };
  for (const key of Object.keys(defaults)) {
    if (data[key] === null || data[key] === undefined) continue;
    if (
      typeof defaults[key] === 'object' &&
      !Array.isArray(defaults[key]) &&
      defaults[key] !== null
    ) {
      result[key] = mergeDefaults(defaults[key], data[key]);
    } else {
      result[key] = data[key];
    }
  }
  for (const key of Object.keys(data)) {
    if (!(key in result)) {
      result[key] = data[key];
    }
  }
  return result;
}
