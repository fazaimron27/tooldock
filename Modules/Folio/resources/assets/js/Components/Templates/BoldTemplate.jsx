import React from 'react';

import {
  ContactLine,
  ProfileLink,
  UrlLink,
  buildContactItems,
  buildDesignTokens,
  formatKeywords,
} from './helpers';

/**
 * Bold Template — High-contrast, heavy typography, sharp lines.
 * Sections render in the order specified by settings.sectionOrder.
 * Uses settings.design for accent, text, and background colors.
 */
export default function BoldTemplate({ basics, sections, settings }) {
  const contact = buildContactItems(basics);
  const dt = buildDesignTokens(settings, '#e11d48');
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

  const renderers = {
    summary: () =>
      basics.summary && (
        <div
          style={{ marginBottom: '24px', fontSize: '13px', fontWeight: 500, lineHeight: 1.6 }}
          className="resume-description"
          dangerouslySetInnerHTML={{ __html: basics.summary }}
        />
      ),

    profiles: () =>
      sections.profiles?.items?.length > 0 && (
        <div
          style={{
            fontSize: '11.5px',
            color: dt.textMuted,
            marginTop: '6px',
            marginBottom: '16px',
            fontWeight: 500,
          }}
        >
          {sections.profiles.items.map((p, i) => (
            <React.Fragment key={p.id || i}>
              <ProfileLink
                profile={p}
                style={{ color: dt.accent, textDecoration: 'none', fontWeight: 600 }}
              />
              {i < sections.profiles.items.length - 1 && '   /   '}
            </React.Fragment>
          ))}
        </div>
      ),

    work: () =>
      sections.work?.items?.length > 0 && (
        <Section title="WORK EXPERIENCE" dt={dt}>
          {sections.work.items.map((w, i) => (
            <div key={w.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 800, fontSize: '14px', textTransform: 'uppercase' }}>
                  {w.position}
                </span>
                <span style={{ fontWeight: 700, fontSize: '11px', color: dt.accent }}>
                  {w.period}
                </span>
              </div>
              <div style={{ fontSize: '12px', fontWeight: 600, color: dt.textMuted }}>
                {w.company}
                {w.location && <span style={{ fontWeight: 400 }}> — {w.location}</span>}
              </div>
              <Description text={w.description} dt={dt} />
            </div>
          ))}
        </Section>
      ),

    education: () =>
      sections.education?.items?.length > 0 && (
        <Section title="EDUCATION" dt={dt}>
          {sections.education.items.map((e, i) => (
            <div key={e.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 800, fontSize: '14px', textTransform: 'uppercase' }}>
                  {e.school}
                </span>
                <span style={{ fontWeight: 700, fontSize: '11px', color: dt.textLight }}>
                  {e.period}
                </span>
              </div>
              <div style={{ fontSize: '12px', fontWeight: 600, color: dt.textMuted }}>
                {e.degree}
                {e.area && <span> in {e.area}</span>}
              </div>
              <Description text={e.description} dt={dt} />
            </div>
          ))}
        </Section>
      ),

    skills: () =>
      sections.skills?.items?.length > 0 && (
        <Section title="SKILLS & EXPERTISE" dt={dt}>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
            {sections.skills.items.map((s, i) => (
              <div key={s.id || i} style={{ display: 'flex', alignItems: 'baseline' }}>
                <span style={{ fontWeight: 800, width: '130px', flexShrink: 0 }}>{s.name}</span>
                {s.keywords?.length > 0 && (
                  <span style={{ color: dt.textMuted }}>{formatKeywords(s.keywords, ' / ')}</span>
                )}
              </div>
            ))}
          </div>
        </Section>
      ),

    projects: () =>
      sections.projects?.items?.length > 0 && (
        <Section title="KEY PROJECTS" dt={dt}>
          {sections.projects.items.map((p, i) => (
            <div key={p.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 800, fontSize: '13px', textTransform: 'uppercase' }}>
                  {p.name}
                </span>
                <span style={{ fontSize: '11px', color: dt.textLight }}>{p.period}</span>
              </div>
              {p.website?.url && (
                <UrlLink
                  url={p.website.url}
                  label={p.website.label}
                  style={{
                    fontSize: '11px',
                    color: dt.accent,
                    fontWeight: 600,
                    textDecoration: 'none',
                  }}
                />
              )}
              <Description text={p.description} dt={dt} />
            </div>
          ))}
        </Section>
      ),

    languages: () =>
      sections.languages?.items?.length > 0 && (
        <Section title="LANGUAGES" dt={dt}>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px 24px' }}>
            {sections.languages.items.map((l, i) => (
              <div key={l.id || i}>
                <span style={{ fontWeight: 800 }}>{l.name}</span>
                {l.level && (
                  <span style={{ color: dt.textMuted, fontWeight: 500 }}> — {l.level}</span>
                )}
              </div>
            ))}
          </div>
        </Section>
      ),

    interests: () =>
      sections.interests?.items?.length > 0 && (
        <Section title="INTERESTS" dt={dt}>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px 20px' }}>
            {sections.interests.items.map((item, i) => (
              <span key={item.id || i} style={{ fontWeight: 700 }}>
                {item.name}
              </span>
            ))}
          </div>
        </Section>
      ),

    awards: () =>
      sections.awards?.items?.length > 0 && (
        <Section title="AWARDS" dt={dt}>
          {sections.awards.items.map((a, i) => (
            <div key={a.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 800, fontSize: '13px', textTransform: 'uppercase' }}>
                  {a.title}
                </span>
                <span style={{ fontSize: '11px', color: dt.textLight }}>{a.date}</span>
              </div>
              {a.awarder && (
                <div style={{ fontSize: '12px', fontWeight: 600, color: dt.textMuted }}>
                  {a.awarder}
                </div>
              )}
              <Description text={a.description} dt={dt} />
            </div>
          ))}
        </Section>
      ),

    certifications: () =>
      sections.certifications?.items?.length > 0 && (
        <Section title="CERTIFICATIONS" dt={dt}>
          {sections.certifications.items.map((c, i) => (
            <div key={c.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 800, fontSize: '13px', textTransform: 'uppercase' }}>
                  {c.name}
                </span>
                <span style={{ fontSize: '11px', color: dt.textLight }}>{c.date}</span>
              </div>
              {c.issuer && (
                <div style={{ fontSize: '12px', fontWeight: 600, color: dt.textMuted }}>
                  {c.issuer}
                </div>
              )}
              {c.url && (
                <UrlLink
                  url={c.url}
                  style={{
                    fontSize: '11px',
                    color: dt.accent,
                    fontWeight: 600,
                    textDecoration: 'none',
                  }}
                />
              )}
              <Description text={c.description} dt={dt} />
            </div>
          ))}
        </Section>
      ),

    publications: () =>
      sections.publications?.items?.length > 0 && (
        <Section title="PUBLICATIONS" dt={dt}>
          {sections.publications.items.map((pub, i) => (
            <div key={pub.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 800, fontSize: '13px', textTransform: 'uppercase' }}>
                  {pub.name}
                </span>
                <span style={{ fontSize: '11px', color: dt.textLight }}>{pub.date}</span>
              </div>
              {pub.publisher && (
                <div style={{ fontSize: '12px', fontWeight: 600, color: dt.textMuted }}>
                  {pub.publisher}
                </div>
              )}
              {pub.url && (
                <UrlLink
                  url={pub.url}
                  style={{
                    fontSize: '11px',
                    color: dt.accent,
                    fontWeight: 600,
                    textDecoration: 'none',
                  }}
                />
              )}
              <Description text={pub.description} dt={dt} />
            </div>
          ))}
        </Section>
      ),

    volunteering: () =>
      sections.volunteering?.items?.length > 0 && (
        <Section title="VOLUNTEERING" dt={dt}>
          {sections.volunteering.items.map((v, i) => (
            <div key={v.id || i} style={{ marginBottom: '16px' }}>
              <div
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}
              >
                <span style={{ fontWeight: 800, fontSize: '14px', textTransform: 'uppercase' }}>
                  {v.position}
                </span>
                <span style={{ fontWeight: 700, fontSize: '11px', color: dt.accent }}>
                  {v.period}
                </span>
              </div>
              <div style={{ fontSize: '12px', fontWeight: 600, color: dt.textMuted }}>
                {v.organization}
                {v.location && <span style={{ fontWeight: 400 }}> — {v.location}</span>}
              </div>
              <Description text={v.description} dt={dt} />
            </div>
          ))}
        </Section>
      ),

    references: () =>
      sections.references?.items?.length > 0 && (
        <Section title="REFERENCES" dt={dt}>
          {sections.references.items.map((r, i) => (
            <div key={r.id || i} style={{ marginBottom: '12px' }}>
              <div>
                <span style={{ fontWeight: 800 }}>{r.name}</span>
                {r.relationship && <span style={{ color: dt.textMuted }}> — {r.relationship}</span>}
              </div>
              {(r.phone || r.email) && (
                <div style={{ fontSize: '11.5px', color: dt.textMuted }}>
                  {[r.phone, r.email].filter(Boolean).join(' | ')}
                </div>
              )}
              <Description text={r.description} dt={dt} />
            </div>
          ))}
        </Section>
      ),
  };

  return (
    <div>
      <div
        style={{
          borderBottom: `3px solid ${dt.text}`,
          paddingBottom: '16px',
          marginBottom: '20px',
        }}
      >
        {basics.name && (
          <h1
            style={{
              fontSize: '32px',
              fontWeight: 900,
              margin: '0 0 4px',
              lineHeight: 1,
              letterSpacing: '-1px',
              textTransform: 'uppercase',
            }}
          >
            {basics.name}
          </h1>
        )}
        {basics.headline && (
          <div
            style={{
              fontSize: '14px',
              color: dt.accent,
              fontWeight: 800,
              textTransform: 'uppercase',
              letterSpacing: '1px',
            }}
          >
            {basics.headline}
          </div>
        )}
        {contact.length > 0 && (
          <div
            style={{ fontSize: '11.5px', color: dt.textMuted, marginTop: '10px', fontWeight: 500 }}
          >
            <ContactLine
              items={contact}
              separator="/"
              separatorStyle={{ margin: '0 10px', color: dt.textSeparator }}
              itemStyle={{ color: 'inherit', textDecoration: 'none' }}
            />
          </div>
        )}
      </div>

      {order.map((key) => {
        const render = renderers[key];
        return render ? <React.Fragment key={key}>{render()}</React.Fragment> : null;
      })}

      {sections.custom?.length > 0 &&
        sections.custom.map(
          (cs) =>
            cs.items?.length > 0 && (
              <Section key={cs.id} title={cs.title?.toUpperCase()} dt={dt}>
                {cs.items.map((item, i) => (
                  <div key={item.id || i} style={{ marginBottom: '16px' }}>
                    <div
                      style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'baseline',
                      }}
                    >
                      <span
                        style={{ fontWeight: 800, fontSize: '13px', textTransform: 'uppercase' }}
                      >
                        {item.title}
                      </span>
                      <span style={{ fontSize: '11px', color: dt.textLight }}>{item.date}</span>
                    </div>
                    {item.subtitle && (
                      <div style={{ fontSize: '12px', fontWeight: 600, color: dt.textMuted }}>
                        {item.subtitle}
                      </div>
                    )}
                    {item.url && (
                      <UrlLink
                        url={item.url}
                        style={{
                          fontSize: '11px',
                          color: dt.accent,
                          fontWeight: 600,
                          textDecoration: 'none',
                        }}
                      />
                    )}
                    <Description text={item.description} dt={dt} />
                  </div>
                ))}
              </Section>
            )
        )}
    </div>
  );
}

function Section({ title, dt, children }) {
  return (
    <div style={{ marginBottom: `${dt.spacingV * 4}px` }}>
      <h2
        style={{
          fontWeight: 900,
          letterSpacing: '1px',
          margin: `0 0 ${dt.spacingV * 2}px`,
          display: 'flex',
          alignItems: 'center',
          gap: '10px',
        }}
      >
        <span>{title}</span>
        <div style={{ flex: 1, height: '2px', background: dt.textSeparator }} />
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
      style={{
        margin: '6px 0 0',
        paddingLeft: '18px',
        listStyleType: 'square',
        color: dt.textMuted,
      }}
    >
      {lines.map((l, i) => (
        <li key={i} style={{ marginBottom: '3px' }}>
          {l.replace(/^[-•*]\s*/, '')}
        </li>
      ))}
    </ul>
  );
}
