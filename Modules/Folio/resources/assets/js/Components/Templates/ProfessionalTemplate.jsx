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
 * Professional Template — Clean, ATS-friendly, single-column layout.
 * Serif font, full-width section borders, bulleted descriptions.
 * Sections render in the order specified by settings.sectionOrder.
 * Uses settings.design for accent, text, and background colors.
 */
export default function ProfessionalTemplate({ basics, sections, settings }) {
  const contact = buildContactItems(basics);
  const dt = buildDesignTokens(settings, '#2563eb');
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
        <Section title="Summary" dt={dt}>
          <div
            style={{ lineHeight: 1.6 }}
            className="resume-description"
            dangerouslySetInnerHTML={{ __html: basics.summary }}
          />
        </Section>
      ),

    profiles: () =>
      sections.profiles?.items?.length > 0 && (
        <div style={{ fontSize: '11.5px', color: dt.textMuted, marginBottom: '12px' }}>
          {sections.profiles.items.map((p, i) => (
            <React.Fragment key={p.id || i}>
              <ProfileLink profile={p} style={{ color: dt.accent, textDecoration: 'none' }} />
              {i < sections.profiles.items.length - 1 && (
                <span style={{ margin: '0 6px', color: dt.textSeparator }}>|</span>
              )}
            </React.Fragment>
          ))}
        </div>
      ),

    work: () =>
      sections.work?.items?.length > 0 && (
        <Section title="Experience" dt={dt}>
          {sections.work.items.map((w, i) => (
            <Entry key={w.id || i}>
              <Row
                left={<b>{w.position}</b>}
                right={<span style={{ fontSize: '11.5px' }}>{w.period}</span>}
              />
              <div style={{ color: dt.textMuted }}>
                {w.company}
                {w.location && <span> — {w.location}</span>}
              </div>
              <Description text={w.description} />
            </Entry>
          ))}
        </Section>
      ),

    education: () =>
      sections.education?.items?.length > 0 && (
        <Section title="Education" dt={dt}>
          {sections.education.items.map((e, i) => (
            <Entry key={e.id || i}>
              <Row
                left={
                  <b>
                    {e.degree}
                    {e.area && <span style={{ fontWeight: 400 }}> in {e.area}</span>}
                  </b>
                }
                right={<span style={{ fontSize: '11.5px' }}>{e.period}</span>}
              />
              <div style={{ color: dt.textMuted }}>{e.school}</div>
              <Description text={e.description} />
            </Entry>
          ))}
        </Section>
      ),

    skills: () =>
      sections.skills?.items?.length > 0 && (
        <Section title="Skills" dt={dt}>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px 16px' }}>
            {sections.skills.items.map((s, i) => (
              <span key={s.id || i}>
                <b>{s.name}</b>
                {s.keywords?.length > 0 && (
                  <span style={{ color: dt.textLight, marginLeft: '4px' }}>
                    ({formatKeywords(s.keywords)})
                  </span>
                )}
              </span>
            ))}
          </div>
        </Section>
      ),

    projects: () =>
      sections.projects?.items?.length > 0 && (
        <Section title="Projects" dt={dt}>
          {sections.projects.items.map((p, i) => (
            <Entry key={p.id || i}>
              <Row
                left={<b>{p.name}</b>}
                right={<span style={{ fontSize: '11.5px' }}>{p.period}</span>}
              />
              {p.website?.url && (
                <UrlLink
                  url={p.website.url}
                  label={p.website.label}
                  style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                />
              )}
              <Description text={p.description} />
            </Entry>
          ))}
        </Section>
      ),

    languages: () =>
      sections.languages?.items?.length > 0 && (
        <Section title="Languages" dt={dt}>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px 16px' }}>
            {sections.languages.items.map((l, i) => (
              <span key={l.id || i}>
                <b>{l.name}</b>
                {l.level && (
                  <span style={{ color: dt.textLight, marginLeft: '4px' }}>({l.level})</span>
                )}
              </span>
            ))}
          </div>
        </Section>
      ),

    interests: () =>
      sections.interests?.items?.length > 0 && (
        <Section title="Interests" dt={dt}>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px 16px' }}>
            {sections.interests.items.map((item, i) => (
              <span key={item.id || i} style={{ fontWeight: 600 }}>
                {item.name}
              </span>
            ))}
          </div>
        </Section>
      ),

    awards: () =>
      sections.awards?.items?.length > 0 && (
        <Section title="Awards" dt={dt}>
          {sections.awards.items.map((a, i) => (
            <Entry key={a.id || i}>
              <Row
                left={<b>{a.title}</b>}
                right={<span style={{ fontSize: '11.5px' }}>{a.date}</span>}
              />
              {a.awarder && <div style={{ color: dt.textMuted }}>{a.awarder}</div>}
              <Description text={a.description} />
            </Entry>
          ))}
        </Section>
      ),

    certifications: () =>
      sections.certifications?.items?.length > 0 && (
        <Section title="Certifications" dt={dt}>
          {sections.certifications.items.map((c, i) => (
            <Entry key={c.id || i}>
              <Row
                left={<b>{c.name}</b>}
                right={<span style={{ fontSize: '11.5px' }}>{c.date}</span>}
              />
              {c.issuer && <div style={{ color: dt.textMuted }}>{c.issuer}</div>}
              {c.url && (
                <UrlLink
                  url={c.url}
                  style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                />
              )}
              <Description text={c.description} />
            </Entry>
          ))}
        </Section>
      ),

    publications: () =>
      sections.publications?.items?.length > 0 && (
        <Section title="Publications" dt={dt}>
          {sections.publications.items.map((pub, i) => (
            <Entry key={pub.id || i}>
              <Row
                left={<b>{pub.name}</b>}
                right={<span style={{ fontSize: '11.5px' }}>{pub.date}</span>}
              />
              {pub.publisher && <div style={{ color: dt.textMuted }}>{pub.publisher}</div>}
              {pub.url && (
                <UrlLink
                  url={pub.url}
                  style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                />
              )}
              <Description text={pub.description} />
            </Entry>
          ))}
        </Section>
      ),

    volunteering: () =>
      sections.volunteering?.items?.length > 0 && (
        <Section title="Volunteering" dt={dt}>
          {sections.volunteering.items.map((v, i) => (
            <Entry key={v.id || i}>
              <Row
                left={<b>{v.position}</b>}
                right={<span style={{ fontSize: '11.5px' }}>{v.period}</span>}
              />
              <div style={{ color: dt.textMuted }}>
                {v.organization}
                {v.location && <span> — {v.location}</span>}
              </div>
              <Description text={v.description} />
            </Entry>
          ))}
        </Section>
      ),

    references: () =>
      sections.references?.items?.length > 0 && (
        <Section title="References" dt={dt}>
          {sections.references.items.map((r, i) => (
            <Entry key={r.id || i}>
              <div>
                <b>{r.name}</b>
                {r.relationship && <span style={{ color: dt.textMuted }}> — {r.relationship}</span>}
              </div>
              {(r.phone || r.email) && (
                <div style={{ fontSize: '11.5px', color: dt.textMuted }}>
                  {[r.phone, r.email].filter(Boolean).join(' | ')}
                </div>
              )}
              <Description text={r.description} />
            </Entry>
          ))}
        </Section>
      ),
  };

  return (
    <div>
      {basics.name && (
        <h1 style={{ fontSize: '26px', fontWeight: 700, margin: '0 0 2px' }}>{basics.name}</h1>
      )}
      {basics.headline && (
        <div style={{ fontSize: '14px', color: dt.textMuted, marginBottom: '6px' }}>
          {basics.headline}
        </div>
      )}
      {contact.length > 0 && (
        <div style={{ fontSize: '11.5px', color: dt.textMuted, marginBottom: '8px' }}>
          <ContactLine
            items={contact}
            separatorStyle={{ margin: '0 6px', color: dt.textSeparator }}
            itemStyle={{ color: 'inherit', textDecoration: 'none' }}
          />
        </div>
      )}

      {order.map((key) => {
        const render = renderers[key];
        return render ? <React.Fragment key={key}>{render()}</React.Fragment> : null;
      })}

      {sections.custom?.length > 0 &&
        sections.custom.map(
          (cs) =>
            cs.items?.length > 0 && (
              <Section key={cs.id} title={cs.title} dt={dt}>
                {cs.items.map((item, i) => (
                  <Entry key={item.id || i}>
                    <Row
                      left={<b>{item.title}</b>}
                      right={<span style={{ fontSize: '11.5px' }}>{item.date}</span>}
                    />
                    {item.subtitle && <div style={{ color: dt.textMuted }}>{item.subtitle}</div>}
                    {item.location && (
                      <div style={{ fontSize: '11.5px', color: dt.textMuted }}>{item.location}</div>
                    )}
                    {item.url && (
                      <UrlLink
                        url={item.url}
                        style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                      />
                    )}
                    <Description text={item.description} />
                  </Entry>
                ))}
              </Section>
            )
        )}
    </div>
  );
}

function Section({ title, dt, children }) {
  return (
    <div style={{ marginBottom: `${dt.spacingV * 3}px` }}>
      <h2
        style={{
          fontWeight: 700,
          margin: `0 0 ${dt.spacingV}px`,
          paddingBottom: '4px',
          borderBottom: `1px solid ${dt.headingBorder}`,
        }}
      >
        {title}
      </h2>
      {children}
    </div>
  );
}

function Entry({ children }) {
  return <div style={{ marginBottom: '12px' }}>{children}</div>;
}

function Row({ left, right }) {
  return (
    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}>
      <div>{left}</div>
      {right && <div style={{ textAlign: 'right', whiteSpace: 'nowrap' }}>{right}</div>}
    </div>
  );
}

function Description({ text }) {
  if (!text) return null;
  if (text.includes('<')) {
    return (
      <div
        style={{ margin: '6px 0 0' }}
        className="resume-description"
        dangerouslySetInnerHTML={{ __html: text }}
      />
    );
  }
  const lines = text.split('\n').filter((l) => l.trim());
  if (lines.length <= 1 && !text.includes('•') && !text.includes('- ')) {
    return <p style={{ margin: '6px 0 0' }}>{text}</p>;
  }
  return (
    <ul style={{ margin: '6px 0 0', paddingLeft: '18px', listStyleType: 'disc' }}>
      {lines.map((l, i) => (
        <li key={i} style={{ marginBottom: '3px' }}>
          {l.replace(/^[-•*]\s*/, '')}
        </li>
      ))}
    </ul>
  );
}
