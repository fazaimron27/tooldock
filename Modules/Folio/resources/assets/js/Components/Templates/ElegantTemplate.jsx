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
 * Elegant Template — Centered serif layout, refined spacing, italic accents.
 * Sections render in the order specified by settings.sectionOrder.
 * Uses settings.design for accent, text, and background colors.
 */
export default function ElegantTemplate({ basics, sections, settings }) {
  const contact = buildContactItems(basics);
  const dt = buildDesignTokens(settings, '#78716c');
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
          style={{ textAlign: 'justify', marginBottom: '28px' }}
          className="resume-description"
          dangerouslySetInnerHTML={{ __html: basics.summary }}
        />
      ),

    profiles: () =>
      sections.profiles?.items?.length > 0 && (
        <div
          style={{
            fontSize: '11px',
            color: dt.textLight,
            letterSpacing: '0.3px',
            marginTop: '4px',
            marginBottom: '16px',
            textAlign: 'center',
          }}
        >
          {sections.profiles.items.map((p, i) => (
            <React.Fragment key={p.id || i}>
              <ProfileLink profile={p} style={{ color: dt.accent, textDecoration: 'none' }} />
              {i < sections.profiles.items.length - 1 && <span style={{ margin: '0 8px' }}>·</span>}
            </React.Fragment>
          ))}
        </div>
      ),

    work: () =>
      sections.work?.items?.length > 0 && (
        <Section title="Experience" dt={dt}>
          {sections.work.items.map((w, i) => (
            <div key={w.id || i} style={{ marginBottom: '16px' }}>
              <Row
                left={
                  <>
                    <b>{w.position}</b>
                    {w.company && <span>, {w.company}</span>}
                  </>
                }
                right={<i style={{ color: dt.textMuted }}>{w.period}</i>}
              />
              {w.location && <div style={{ fontSize: '11px', color: dt.accent }}>{w.location}</div>}
              <Description text={w.description} />
            </div>
          ))}
        </Section>
      ),

    education: () =>
      sections.education?.items?.length > 0 && (
        <Section title="Education" dt={dt}>
          {sections.education.items.map((e, i) => (
            <div key={e.id || i} style={{ marginBottom: '16px' }}>
              <Row
                left={<b>{e.school}</b>}
                right={<i style={{ color: dt.textMuted }}>{e.period}</i>}
              />
              <div>
                {e.degree}
                {e.area && <span> in {e.area}</span>}
              </div>
              <Description text={e.description} />
            </div>
          ))}
        </Section>
      ),

    projects: () =>
      sections.projects?.items?.length > 0 && (
        <Section title="Selected Projects" dt={dt}>
          {sections.projects.items.map((p, i) => (
            <div key={p.id || i} style={{ marginBottom: '16px' }}>
              <Row
                left={<b>{p.name}</b>}
                right={<i style={{ color: dt.textMuted }}>{p.period}</i>}
              />
              {p.website?.url && (
                <UrlLink
                  url={p.website.url}
                  label={p.website.label}
                  style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                />
              )}
              <Description text={p.description} />
            </div>
          ))}
        </Section>
      ),

    skills: () =>
      sections.skills?.items?.length > 0 && (
        <Section title="Areas of Expertise" dt={dt}>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px 20px' }}>
            {sections.skills.items.map((s, i) => (
              <span key={s.id || i}>
                <b>{s.name}</b>
                {s.keywords?.length > 0 && (
                  <span style={{ color: dt.textLight, marginLeft: '6px', fontSize: '11.5px' }}>
                    ({formatKeywords(s.keywords)})
                  </span>
                )}
              </span>
            ))}
          </div>
        </Section>
      ),

    languages: () =>
      sections.languages?.items?.length > 0 && (
        <Section title="Languages" dt={dt}>
          <div
            style={{ display: 'flex', flexWrap: 'wrap', gap: '4px 20px', justifyContent: 'center' }}
          >
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
          <div
            style={{ display: 'flex', flexWrap: 'wrap', gap: '4px 20px', justifyContent: 'center' }}
          >
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
        <Section title="Awards & Honors" dt={dt}>
          {sections.awards.items.map((a, i) => (
            <div key={a.id || i} style={{ marginBottom: '16px' }}>
              <Row
                left={<b>{a.title}</b>}
                right={<i style={{ color: dt.textMuted }}>{a.date}</i>}
              />
              {a.awarder && <div style={{ color: dt.accent }}>{a.awarder}</div>}
              <Description text={a.description} />
            </div>
          ))}
        </Section>
      ),

    certifications: () =>
      sections.certifications?.items?.length > 0 && (
        <Section title="Certifications" dt={dt}>
          {sections.certifications.items.map((c, i) => (
            <div key={c.id || i} style={{ marginBottom: '16px' }}>
              <Row left={<b>{c.name}</b>} right={<i style={{ color: dt.textMuted }}>{c.date}</i>} />
              {c.issuer && <div style={{ color: dt.accent }}>{c.issuer}</div>}
              {c.url && (
                <UrlLink
                  url={c.url}
                  style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                />
              )}
              <Description text={c.description} />
            </div>
          ))}
        </Section>
      ),

    publications: () =>
      sections.publications?.items?.length > 0 && (
        <Section title="Publications" dt={dt}>
          {sections.publications.items.map((pub, i) => (
            <div key={pub.id || i} style={{ marginBottom: '16px' }}>
              <Row
                left={<b>{pub.name}</b>}
                right={<i style={{ color: dt.textMuted }}>{pub.date}</i>}
              />
              {pub.publisher && <div style={{ color: dt.accent }}>{pub.publisher}</div>}
              {pub.url && (
                <UrlLink
                  url={pub.url}
                  style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                />
              )}
              <Description text={pub.description} />
            </div>
          ))}
        </Section>
      ),

    volunteering: () =>
      sections.volunteering?.items?.length > 0 && (
        <Section title="Volunteering" dt={dt}>
          {sections.volunteering.items.map((v, i) => (
            <div key={v.id || i} style={{ marginBottom: '16px' }}>
              <Row
                left={
                  <>
                    <b>{v.position}</b>
                    {v.organization && <span>, {v.organization}</span>}
                  </>
                }
                right={<i style={{ color: dt.textMuted }}>{v.period}</i>}
              />
              {v.location && <div style={{ fontSize: '11px', color: dt.accent }}>{v.location}</div>}
              <Description text={v.description} />
            </div>
          ))}
        </Section>
      ),

    references: () =>
      sections.references?.items?.length > 0 && (
        <Section title="References" dt={dt}>
          {sections.references.items.map((r, i) => (
            <div key={r.id || i} style={{ marginBottom: '12px' }}>
              <div>
                <b>{r.name}</b>
                {r.relationship && <span style={{ color: dt.accent }}> — {r.relationship}</span>}
              </div>
              {(r.phone || r.email) && (
                <div style={{ fontSize: '11px', color: dt.textLight }}>
                  {[r.phone, r.email].filter(Boolean).join(' · ')}
                </div>
              )}
              <Description text={r.description} />
            </div>
          ))}
        </Section>
      ),
  };

  return (
    <div>
      <div style={{ textAlign: 'center', marginBottom: '32px' }}>
        {basics.name && (
          <h1
            style={{
              fontSize: '30px',
              fontWeight: 400,
              margin: '0 0 4px',
              letterSpacing: '0.06em',
            }}
          >
            {basics.name}
          </h1>
        )}
        {basics.headline && (
          <div
            style={{
              fontSize: '13px',
              color: dt.accent,
              fontStyle: 'italic',
              marginBottom: '10px',
            }}
          >
            {basics.headline}
          </div>
        )}
        {contact.length > 0 && (
          <div style={{ fontSize: '11px', color: dt.textLight, letterSpacing: '0.3px' }}>
            <ContactLine
              items={contact}
              separator="·"
              separatorStyle={{ margin: '0 8px' }}
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
              <Section key={cs.id} title={cs.title} dt={dt}>
                {cs.items.map((item, i) => (
                  <div key={item.id || i} style={{ marginBottom: '16px' }}>
                    <Row
                      left={<b>{item.title}</b>}
                      right={<i style={{ color: dt.textMuted }}>{item.date}</i>}
                    />
                    {item.subtitle && <div style={{ color: dt.accent }}>{item.subtitle}</div>}
                    {item.url && (
                      <UrlLink
                        url={item.url}
                        style={{ fontSize: '11px', color: dt.accent, textDecoration: 'none' }}
                      />
                    )}
                    <Description text={item.description} />
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
          fontWeight: 400,
          textTransform: 'uppercase',
          letterSpacing: '0.12em',
          textAlign: 'center',
          margin: `0 0 ${dt.spacingV * 2}px`,
        }}
      >
        <span style={{ borderBottom: `1px solid ${dt.textSeparator}`, paddingBottom: '4px' }}>
          {title}
        </span>
      </h2>
      {children}
    </div>
  );
}

function Row({ left, right }) {
  return (
    <div
      style={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'baseline',
        fontSize: '13px',
      }}
    >
      <div>{left}</div>
      {right && <div>{right}</div>}
    </div>
  );
}

function Description({ text }) {
  if (!text) return null;
  if (text.includes('<')) {
    return (
      <div
        style={{ margin: '4px 0 0' }}
        className="resume-description"
        dangerouslySetInnerHTML={{ __html: text }}
      />
    );
  }
  const lines = text.split('\n').filter((l) => l.trim());
  if (lines.length <= 1 && !text.includes('•') && !text.includes('- ')) {
    return <p style={{ margin: '4px 0 0' }}>{text}</p>;
  }
  return (
    <ul style={{ margin: '4px 0 0', paddingLeft: '18px', listStyleType: 'disc' }}>
      {lines.map((l, i) => (
        <li key={i} style={{ marginBottom: '3px' }}>
          {l.replace(/^[-•*]\s*/, '')}
        </li>
      ))}
    </ul>
  );
}
