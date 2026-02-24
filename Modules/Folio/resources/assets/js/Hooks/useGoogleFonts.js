import { GOOGLE_FONTS_FAMILY } from '@Folio/constants/fonts';
import { useEffect } from 'react';

const LINK_ID = 'resume-google-fonts';

/**
 * Dynamically loads Google Fonts via a <link> tag in the document head.
 * Cleans up on unmount.
 */
export default function useGoogleFonts(bodyFontKey, headingFontKey) {
  useEffect(() => {
    const families = new Set();
    const bodyFamily = GOOGLE_FONTS_FAMILY[bodyFontKey];
    const headingFamily = GOOGLE_FONTS_FAMILY[headingFontKey];
    if (bodyFamily) families.add(bodyFamily);
    if (headingFamily) families.add(headingFamily);

    let link = document.getElementById(LINK_ID);

    if (families.size === 0) {
      if (link) link.remove();
      return;
    }

    const url = `https://fonts.googleapis.com/css2?${[...families].map((f) => `family=${f}`).join('&')}&display=swap`;

    if (!link) {
      link = document.createElement('link');
      link.id = LINK_ID;
      link.rel = 'stylesheet';
      document.head.appendChild(link);
    }
    link.href = url;

    return () => {
      const el = document.getElementById(LINK_ID);
      if (el) el.remove();
    };
  }, [bodyFontKey, headingFontKey]);
}
