import React from 'react';

import {
  ContactValue,
  UrlLink,
  buildContactItems,
  buildDesignTokens,
  formatKeywords,
} from './helpers';

/**
 * Modern Template — Two-column layout.
 * Left sidebar: contact, skills, profiles, languages, interests.
 * Right main: summary, experience, education, projects, etc.
 * Sections render in the order specified by settings.sectionOrder.
 * Uses settings.design for accent, text, and background colors.
 */
const SIDEBAR_SECTIONS = new Set(['profiles', 'skills', 'languages', 'interests']);

export default function ModernTemplate({ basics, sections, settings }) {
  const contact = buildContactItems(basics);

  const dt = buildDesignTokens(settings, '#059669');
  const order = settings?.sectionOrder || [
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
  ];

  const sidebarOrder = order.filter((k) => SIDEBAR_SECTIONS.has(k));
  const mainOrder = order.filter((k) => !SIDEBAR_SECTIONS.has(k));

  const sideRenderers = {
    skills: () =>
      sections.skills?.items?.length > 0 && (
        <SideSection title="Skills" dt={dt}>
          {sections.skills.items.map((s, i) => (
            <div key={s.id || i} style={{ marginBottom: '8px' }}>
              <div style={{ fontWeight: 600 }}>{s.name}</div>
              {s.keywords?.length > 0 && (
                <div style={{ fontSize: '11px', color: dt.textMuted }}>
                  {formatKeywords(s.keywords)}
                </div>
              )}
            </div>
          ))}
        </SideSection>
      ),

    profiles: () =>
      sections.profiles?.items?.length > 0 && (
        <SideSection title="Profiles" dt={dt}>
          {sections.profiles.items.map((p, i) => (
            <div key={p.id || i} style={{ marginBottom: '8px' }}>
              <div style={{ fontWeight: 600 }}>{p.network}</div>
              {p.url ? (
                <a
                  href={p.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{
                    fontSize: '11px',
                    color: dt.accent,
                    textDecoration: 'none',
                    wordBreak: 'break-all',
                  }}
                >
                  {p.username || p.url}
                </a>
              ) : (
                <div style={{ fontSize: '11px', color: dt.textMuted }}>{p.username}</div>
              )}
            </div>
          ))}
        </SideSection>
      ),

    languages: () =>
      sections.languages?.items?.length > 0 && (
        <SideSection title="Languages" dt={dt}>
          {sections.languages.items.map((l, i) => (
            <div key={l.id || i} style={{ marginBottom: '6px' }}>
              <div style={{ fontWeight: 600 }}>{l.name}</div>
              {l.level && <div style={{ fontSize: '11px', color: dt.textMuted }}>{l.level}</div>}
            </div>
          ))}
        </SideSection>
      ),

    interests: () =>
      sections.interests?.items?.length > 0 && (
        <SideSection title="Interests" dt={dt}>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px 8px' }}>
            {sections.interests.items.map((item, i) => (
              <span key={item.id || i} style={{ fontSize: '12px', fontWeight: 600 }}>
                {item.name}
              </span>
            ))}
          </div>
        </SideSection>
      ),
  };

  const mainRenderers = {
    summary: () =>
      basics.summary && (
        <MainSection title="About" dt={dt}>
          <div
            style={{ margin: 0, color: dt.textMuted, lineHeight: 1.7 }}
            className="resume-description"
            dangerouslySetInnerHTML={{ __html: basics.summary }}
          />
        </MainSection>
      ),

    work: () =>
      sections.work?.items?.length > 0 && (
        <MainSection title="Experience" dt={dt}>
          {sections.work.items.map((w, i) => (
            <div key={w.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 700, fontSize: '14px' }}>{w.position}</span>
                <span style={{ fontSize: '11px', color: dt.accent, fontWeight: 600 }}>
                  {w.period}
                </span>
              </div>
              <div style={{ fontSize: '12px', color: dt.textMuted }}>
                {w.company}
                {w.location && <span style={{ color: dt.textLight }}> · {w.location}</span>}
              </div>
              <Description text={w.description} dt={dt} />
            </div>
          ))}
        </MainSection>
      ),

    education: () =>
      sections.education?.items?.length > 0 && (
        <MainSection title="Education" dt={dt}>
          {sections.education.items.map((e, i) => (
            <div key={e.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 700, fontSize: '14px' }}>
                  {e.degree}
                  {e.area && <span style={{ fontWeight: 400 }}> in {e.area}</span>}
                </span>
                <span style={{ fontSize: '11px', color: dt.textMuted }}>{e.period}</span>
              </div>
              {e.school && <div style={{ fontSize: '12px', color: dt.textMuted }}>{e.school}</div>}
              <Description text={e.description} dt={dt} />
            </div>
          ))}
        </MainSection>
      ),

    projects: () =>
      sections.projects?.items?.length > 0 && (
        <MainSection title="Projects" dt={dt}>
          {sections.projects.items.map((p, i) => (
            <div key={p.id || i} style={{ marginBottom: '16px' }}>
              <span style={{ fontWeight: 700, fontSize: '13px' }}>{p.name}</span>
              {p.website?.url && (
                <UrlLink
                  url={p.website.url}
                  label={p.website.label}
                  style={{
                    fontSize: '11px',
                    color: dt.accent,
                    marginLeft: '8px',
                    textDecoration: 'none',
                  }}
                />
              )}
              <Description text={p.description} dt={dt} />
            </div>
          ))}
        </MainSection>
      ),

    awards: () =>
      sections.awards?.items?.length > 0 && (
        <MainSection title="Awards" dt={dt}>
          {sections.awards.items.map((a, i) => (
            <div key={a.id || i} style={{ marginBottom: '14px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 700, fontSize: '13px' }}>{a.title}</span>
                <span style={{ fontSize: '11px', color: dt.textMuted }}>{a.date}</span>
              </div>
              {a.awarder && (
                <div style={{ fontSize: '12px', color: dt.textMuted }}>{a.awarder}</div>
              )}
              <Description text={a.description} dt={dt} />
            </div>
          ))}
        </MainSection>
      ),

    certifications: () =>
      sections.certifications?.items?.length > 0 && (
        <MainSection title="Certifications" dt={dt}>
          {sections.certifications.items.map((c, i) => (
            <div key={c.id || i} style={{ marginBottom: '14px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 700, fontSize: '13px' }}>{c.name}</span>
                <span style={{ fontSize: '11px', color: dt.textMuted }}>{c.date}</span>
              </div>
              {c.issuer && <div style={{ fontSize: '12px', color: dt.textMuted }}>{c.issuer}</div>}
              {c.url && (
                <UrlLink
                  url={c.url}
                  style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                />
              )}
              <Description text={c.description} dt={dt} />
            </div>
          ))}
        </MainSection>
      ),

    publications: () =>
      sections.publications?.items?.length > 0 && (
        <MainSection title="Publications" dt={dt}>
          {sections.publications.items.map((pub, i) => (
            <div key={pub.id || i} style={{ marginBottom: '14px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 700, fontSize: '13px' }}>{pub.name}</span>
                <span style={{ fontSize: '11px', color: dt.textMuted }}>{pub.date}</span>
              </div>
              {pub.publisher && (
                <div style={{ fontSize: '12px', color: dt.textMuted }}>{pub.publisher}</div>
              )}
              {pub.url && (
                <UrlLink
                  url={pub.url}
                  style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                />
              )}
              <Description text={pub.description} dt={dt} />
            </div>
          ))}
        </MainSection>
      ),

    volunteering: () =>
      sections.volunteering?.items?.length > 0 && (
        <MainSection title="Volunteering" dt={dt}>
          {sections.volunteering.items.map((v, i) => (
            <div key={v.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 700, fontSize: '14px' }}>{v.position}</span>
                <span style={{ fontSize: '11px', color: dt.accent, fontWeight: 600 }}>
                  {v.period}
                </span>
              </div>
              <div style={{ fontSize: '12px', color: dt.textMuted }}>
                {v.organization}
                {v.location && <span style={{ color: dt.textLight }}> · {v.location}</span>}
              </div>
              <Description text={v.description} dt={dt} />
            </div>
          ))}
        </MainSection>
      ),

    references: () =>
      sections.references?.items?.length > 0 && (
        <MainSection title="References" dt={dt}>
          {sections.references.items.map((r, i) => (
            <div key={r.id || i} style={{ marginBottom: '12px' }}>
              <div>
                <span style={{ fontWeight: 700 }}>{r.name}</span>
                {r.relationship && <span style={{ color: dt.textMuted }}> — {r.relationship}</span>}
              </div>
              {(r.phone || r.email) && (
                <div style={{ fontSize: '11px', color: dt.textMuted }}>
                  {[r.phone, r.email].filter(Boolean).join(' · ')}
                </div>
              )}
              <Description text={r.description} dt={dt} />
            </div>
          ))}
        </MainSection>
      ),
  };

  return (
    <div style={{ display: 'flex', gap: `${dt.spacingH * 7}px` }}>
      <div style={{ width: '200px', flexShrink: 0 }}>
        {basics.name && (
          <h1
            style={{
              fontSize: '22px',
              fontWeight: 800,
              margin: '0 0 4px',
              lineHeight: 1.2,
              letterSpacing: '-0.02em',
            }}
          >
            {basics.name}
          </h1>
        )}
        {basics.headline && (
          <div
            style={{ fontSize: '12px', color: dt.accent, fontWeight: 600, marginBottom: '24px' }}
          >
            {basics.headline}
          </div>
        )}

        {contact.length > 0 && (
          <SideSection title="Contact" dt={dt}>
            {contact.map((c, i) => (
              <div key={i} style={{ marginBottom: '8px' }}>
                <div
                  style={{
                    fontSize: '10px',
                    color: dt.textLight,
                    textTransform: 'uppercase',
                    letterSpacing: '0.5px',
                  }}
                >
                  {c.label}
                </div>
                <div style={{ fontSize: '12px', wordBreak: 'break-all' }}>
                  <ContactValue item={c} style={{ color: 'inherit', textDecoration: 'none' }} />
                </div>
              </div>
            ))}
          </SideSection>
        )}

        {sidebarOrder.map((key) => {
          const render = sideRenderers[key];
          return render ? <React.Fragment key={key}>{render()}</React.Fragment> : null;
        })}
      </div>

      <div style={{ flex: 1 }}>
        {mainOrder.map((key) => {
          const render = mainRenderers[key];
          return render ? <React.Fragment key={key}>{render()}</React.Fragment> : null;
        })}

        {sections.custom?.length > 0 &&
          sections.custom.map(
            (cs) =>
              cs.items?.length > 0 && (
                <MainSection key={cs.id} title={cs.title} dt={dt}>
                  {cs.items.map((item, i) => (
                    <div key={item.id || i} style={{ marginBottom: '14px' }}>
                      <div
                        style={{
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'baseline',
                        }}
                      >
                        <span style={{ fontWeight: 700, fontSize: '13px' }}>{item.title}</span>
                        <span style={{ fontSize: '11px', color: dt.textMuted }}>{item.date}</span>
                      </div>
                      {item.subtitle && (
                        <div style={{ fontSize: '12px', color: dt.textMuted }}>{item.subtitle}</div>
                      )}
                      {item.url && (
                        <UrlLink
                          url={item.url}
                          style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                        />
                      )}
                      <Description text={item.description} dt={dt} />
                    </div>
                  ))}
                </MainSection>
              )
          )}
      </div>
    </div>
  );
}

function SideSection({ title, dt, children }) {
  return (
    <div style={{ marginBottom: `${dt.spacingV * 4}px` }}>
      <h2
        style={{
          fontWeight: 700,
          textTransform: 'uppercase',
          letterSpacing: '1px',
          color: dt.textLight,
          margin: `0 0 ${dt.spacingV * 2}px`,
        }}
      >
        {title}
      </h2>
      {children}
    </div>
  );
}

function MainSection({ title, dt, children }) {
  return (
    <div style={{ marginBottom: `${dt.spacingV * 4}px` }}>
      <h2
        style={{
          fontWeight: 700,
          textTransform: 'uppercase',
          letterSpacing: '1px',
          color: dt.textLight,
          margin: `0 0 ${dt.spacingV * 2}px`,
        }}
      >
        {title}
      </h2>
      {children}
    </div>
  );
}

function Description({ text, dt }) {
  if (!text) return null;
  if (text.includes('<')) {
    return (
      <div
        style={{ margin: '6px 0 0', color: dt.textMuted }}
        className="resume-description"
        dangerouslySetInnerHTML={{ __html: text }}
      />
    );
  }
  const lines = text.split('\n').filter((l) => l.trim());
  if (lines.length <= 1 && !text.includes('•') && !text.includes('- ')) {
    return <p style={{ margin: '6px 0 0', color: dt.textMuted }}>{text}</p>;
  }
  return (
    <ul
      style={{ margin: '6px 0 0', paddingLeft: '18px', listStyleType: 'disc', color: dt.textMuted }}
    >
      {lines.map((l, i) => (
        <li key={i} style={{ marginBottom: '3px' }}>
          {l.replace(/^[-•*]\s*/, '')}
        </li>
      ))}
    </ul>
  );
}
