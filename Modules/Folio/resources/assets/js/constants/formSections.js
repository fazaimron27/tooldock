/**
 * Data-driven definition of all form sections shown in the Builder's left pane.
 * Each entry maps an accordion key to its icon, label, and form component.
 */
import AwardsForm from '@Folio/Components/Sections/AwardsForm';
import BasicsForm from '@Folio/Components/Sections/BasicsForm';
import CertificationsForm from '@Folio/Components/Sections/CertificationsForm';
import EducationForm from '@Folio/Components/Sections/EducationForm';
import InterestsForm from '@Folio/Components/Sections/InterestsForm';
import LanguagesForm from '@Folio/Components/Sections/LanguagesForm';
import ProfilesForm from '@Folio/Components/Sections/ProfilesForm';
import ProjectsForm from '@Folio/Components/Sections/ProjectsForm';
import PublicationsForm from '@Folio/Components/Sections/PublicationsForm';
import ReferencesForm from '@Folio/Components/Sections/ReferencesForm';
import SkillsForm from '@Folio/Components/Sections/SkillsForm';
import SummaryForm from '@Folio/Components/Sections/SummaryForm';
import VolunteeringForm from '@Folio/Components/Sections/VolunteeringForm';
import WorkForm from '@Folio/Components/Sections/WorkForm';
import {
  AlignLeft,
  Award,
  BadgeCheck,
  BookOpen,
  Briefcase,
  Compass,
  GraduationCap,
  HandHeart,
  Languages,
  Link2,
  Phone,
  User,
  Wrench,
} from 'lucide-react';
import { FolderKanban } from 'lucide-react';

/**
 * Ordered list of built-in form sections.
 * @type {Array<{ key: string, label: string, icon: React.ComponentType, Form: React.ComponentType }>}
 */
const FORM_SECTIONS = [
  { key: 'basics', label: 'Basics', icon: User, Form: BasicsForm },
  { key: 'summary', label: 'Summary', icon: AlignLeft, Form: SummaryForm },
  { key: 'profiles', label: 'Profiles', icon: Link2, Form: ProfilesForm },
  { key: 'work', label: 'Work Experience', icon: Briefcase, Form: WorkForm },
  { key: 'education', label: 'Education', icon: GraduationCap, Form: EducationForm },
  { key: 'skills', label: 'Skills', icon: Wrench, Form: SkillsForm },
  { key: 'projects', label: 'Projects', icon: FolderKanban, Form: ProjectsForm },
  { key: 'languages', label: 'Languages', icon: Languages, Form: LanguagesForm },
  { key: 'interests', label: 'Interests', icon: Compass, Form: InterestsForm },
  { key: 'awards', label: 'Awards', icon: Award, Form: AwardsForm },
  { key: 'certifications', label: 'Certifications', icon: BadgeCheck, Form: CertificationsForm },
  { key: 'publications', label: 'Publications', icon: BookOpen, Form: PublicationsForm },
  { key: 'volunteering', label: 'Volunteering', icon: HandHeart, Form: VolunteeringForm },
  { key: 'references', label: 'References', icon: Phone, Form: ReferencesForm },
];

export default FORM_SECTIONS;
