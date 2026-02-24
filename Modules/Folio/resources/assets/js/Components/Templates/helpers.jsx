/**
 * Shared helpers for resume template rendering.
 */
import React from 'react';

/**
 * Derive a full design token palette from settings.design and page spacing.
 * Returns accent, text, bg, opacity-based variants, and spacing values.
 */
export function buildDesignTokens(settings, fallbackAccent = '#2563eb') {
  const design = settings?.design || {};
  const page = settings?.page || {};
  const accent = design.primaryColor || fallbackAccent;
  const text = design.textColor || '#000000';
  const bg = design.backgroundColor || '#ffffff';

  const hexToRgb = (hex) => {
    const h = hex.replace('#', '');
    return [
      parseInt(h.substring(0, 2), 16),
      parseInt(h.substring(2, 4), 16),
      parseInt(h.substring(4, 6), 16),
    ];
  };

  const [r, g, b] = hexToRgb(text);
  const rgba = (a) => `rgba(${r}, ${g}, ${b}, ${a})`;

  return {
    accent,
    text,
    bg,
    textMuted: rgba(0.6),
    textLight: rgba(0.55),
    textSeparator: rgba(0.25),
    headingBorder: rgba(0.85),
    spacingH: page.spacingHorizontal ?? 4,
    spacingV: page.spacingVertical ?? 6,
  };
}

/**
 * Normalize keywords from either an array or comma-separated string.
 * Returns a display-ready string, or empty string if none.
 */
export function formatKeywords(keywords, separator = ', ') {
  if (!keywords) return '';
  if (Array.isArray(keywords)) return keywords.join(separator);
  return String(keywords);
}

/**
 * Build a structured contact items array from basics.
 * Each item has { type, label, value, href }.
 */
export function buildContactItems(basics) {
  const items = [];
  if (basics.location) {
    items.push({ type: 'text', label: 'Location', value: basics.location });
  }
  if (basics.phone) {
    items.push({ type: 'phone', label: 'Phone', value: basics.phone, href: `tel:${basics.phone}` });
  }
  if (basics.email) {
    items.push({
      type: 'email',
      label: 'Email',
      value: basics.email,
      href: `mailto:${basics.email}`,
    });
  }
  if (basics.website?.url) {
    items.push({
      type: 'url',
      label: basics.website.label || 'Website',
      value: basics.website.url,
      href: basics.website.url,
    });
  }
  return items;
}

/**
 * Render a contact value — plain text or a clickable link.
 */
export function ContactValue({ item, style }) {
  if (item.href) {
    return (
      <a href={item.href} target="_blank" rel="noopener noreferrer" style={style}>
        {item.value}
      </a>
    );
  }
  return <span style={style}>{item.value}</span>;
}

/**
 * Render a profile link — opens in new tab.
 */
export function ProfileLink({ profile, style }) {
  if (profile.url) {
    return (
      <a href={profile.url} target="_blank" rel="noopener noreferrer" style={style}>
        {profile.network}
        {profile.username ? ` (${profile.username})` : ''}
      </a>
    );
  }
  return (
    <span style={style}>
      {profile.network}
      {profile.username ? ` (${profile.username})` : ''}
    </span>
  );
}

/**
 * Join contact items with a separator.
 */
export function ContactLine({ items, separator = '|', separatorStyle, itemStyle }) {
  return items.map((item, i) => (
    <React.Fragment key={i}>
      <ContactValue item={item} style={itemStyle} />
      {i < items.length - 1 && (
        <span style={separatorStyle || { margin: '0 6px', color: '#9ca3af' }}>{separator}</span>
      )}
    </React.Fragment>
  ));
}

/**
 * Render a URL as a clickable link — opens in a new tab.
 * Shows the label if provided, otherwise shows the cleaned URL.
 */
export function UrlLink({ url, label, style }) {
  if (!url) return null;
  const displayText = label || url.replace(/^https?:\/\//, '');
  return (
    <a href={url} target="_blank" rel="noopener noreferrer" style={style}>
      {displayText}
    </a>
  );
}
