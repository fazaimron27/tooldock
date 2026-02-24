/**
 * Resume Preview Component
 *
 * Renders a live preview of the resume data using the selected template.
 * - Applies settings (typography, design, page) as wrapper styles
 * - Dynamically loads Google Fonts from CDN
 * - Auto-detects content overflow and renders multiple pages
 * - Auto-scales to fit available container width
 */
import useAutoScale from '@Folio/Hooks/useAutoScale';
import useGoogleFonts from '@Folio/Hooks/useGoogleFonts';
import usePageBreaks from '@Folio/Hooks/usePageBreaks';
import { FONT_MAP } from '@Folio/constants/fonts';
import { PAGE_SIZES } from '@Folio/constants/pageSizes';
import { useMemo, useRef } from 'react';

import BoldTemplate from './Templates/BoldTemplate';
import ElegantTemplate from './Templates/ElegantTemplate';
import ModernTemplate from './Templates/ModernTemplate';
import ProfessionalTemplate from './Templates/ProfessionalTemplate';

const TEMPLATES = {
  professional: ProfessionalTemplate,
  modern: ModernTemplate,
  elegant: ElegantTemplate,
  bold: BoldTemplate,
};

/**
 * @param {{ content: object, themeId: string }} props
 */
export default function ResumePreview({ content, themeId = 'professional' }) {
  const basics = useMemo(() => content?.basics ?? {}, [content?.basics]);
  const sections = useMemo(() => content?.sections ?? {}, [content?.sections]);
  const settings = useMemo(() => content?.settings ?? {}, [content?.settings]);
  const contentRef = useRef(null);
  const containerRef = useRef(null);

  const hasContent = useMemo(() => {
    return (
      basics.name ||
      basics.email ||
      sections.work?.items?.length > 0 ||
      sections.education?.items?.length > 0 ||
      sections.skills?.items?.length > 0 ||
      sections.projects?.items?.length > 0
    );
  }, [basics, sections]);

  const TemplateComponent = TEMPLATES[themeId] || TEMPLATES.professional;

  const typo = settings.typography || {};
  const design = settings.design || {};
  const page = settings.page || {};

  const bodyFontKey = typo.body?.fontFamily || 'inter';
  const headingFontKey = typo.heading?.fontFamily || bodyFontKey;
  const bodyFont = FONT_MAP[bodyFontKey] || FONT_MAP.inter;
  const headingFont = FONT_MAP[headingFontKey] || bodyFont;
  const bodyFontSize = typo.body?.fontSize ? `${typo.body.fontSize}px` : '12.5px';
  const bodyLineHeight = typo.body?.lineHeight || 1.6;
  const headingFontSize = typo.heading?.fontSize ? `${typo.heading.fontSize}px` : '13px';
  const headingLineHeight = typo.heading?.lineHeight || 1.5;

  const primaryColor = design.primaryColor || '#dc2626';
  const textColor = design.textColor || '#000000';
  const backgroundColor = design.backgroundColor || '#ffffff';

  const pageFormat = page.format || 'a4';
  const pageSize = PAGE_SIZES[pageFormat] || PAGE_SIZES.a4;
  const marginH = page.marginHorizontal ?? 18;
  const marginV = page.marginVertical ?? 18;

  useGoogleFonts(bodyFontKey, headingFontKey);

  const contentAreaHeight = pageSize.height - 2 * marginV;
  const pageCount = usePageBreaks(contentRef, contentAreaHeight, [content, themeId, settings]);

  const scale = useAutoScale(containerRef, pageSize.width);

  const templateStyles = {
    fontFamily: bodyFont,
    fontSize: bodyFontSize,
    lineHeight: bodyLineHeight,
    color: textColor,
    backgroundColor,
    padding: `0 ${marginH}px`,
    '--resume-body-font': bodyFont,
    '--resume-heading-font': headingFont,
    '--resume-body-size': bodyFontSize,
    '--resume-body-lh': bodyLineHeight,
    '--resume-heading-size': headingFontSize,
    '--resume-heading-lh': headingLineHeight,
    '--resume-primary': primaryColor,
    '--resume-text': textColor,
    '--resume-bg': backgroundColor,
    '--resume-margin-h': `${marginH}px`,
    '--resume-margin-v': `${marginV}px`,
  };

  const templateCSS = `
    .resume-wrapper h1, .resume-measure h1,
    .resume-wrapper h2, .resume-measure h2,
    .resume-wrapper h3, .resume-measure h3 {
      font-family: ${headingFont};
      line-height: ${headingLineHeight};
    }
    .resume-wrapper h2, .resume-measure h2 {
      font-size: ${headingFontSize};
    }
    .resume-wrapper a, .resume-measure a {
      color: ${primaryColor};
    }
  `;

  if (!hasContent) {
    return (
      <div ref={containerRef} className="w-full">
        <div
          className="mx-auto shadow-xl border border-gray-200 rounded"
          style={{
            width: `${pageSize.width}px`,
            height: `${pageSize.height}px`,
            backgroundColor: '#fff',
            transform: `scale(${scale})`,
            transformOrigin: 'top center',
          }}
        >
          <div className="flex flex-col items-center justify-center h-full text-gray-300">
            <svg
              className="w-16 h-16 mb-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={1}
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            <p className="text-base font-medium">Your resume preview</p>
            <p className="text-sm mt-1">Fill in the form to see it come to life</p>
          </div>
        </div>
      </div>
    );
  }

  const gapBetweenPages = 16;
  const totalScaledHeight = pageCount * pageSize.height * scale + (pageCount - 1) * gapBetweenPages;

  return (
    <div ref={containerRef} className="w-full">
      <div
        ref={contentRef}
        className="resume-measure"
        aria-hidden="true"
        style={{
          position: 'absolute',
          visibility: 'hidden',
          pointerEvents: 'none',
          width: `${pageSize.width}px`,
          ...templateStyles,
        }}
      >
        <style>{templateCSS}</style>
        <TemplateComponent basics={basics} sections={sections} settings={settings} />
      </div>

      <div style={{ height: `${totalScaledHeight}px`, position: 'relative' }}>
        <div
          style={{
            transformOrigin: 'top center',
            transform: `scale(${scale})`,
            width: `${pageSize.width}px`,
            margin: '0 auto',
            position: 'absolute',
            left: '50%',
            top: 0,
            marginLeft: `${-(pageSize.width / 2)}px`,
          }}
        >
          {Array.from({ length: pageCount }, (_, i) => (
            <div
              key={i}
              className="shadow-xl border border-gray-200 rounded overflow-hidden"
              style={{
                width: `${pageSize.width}px`,
                height: `${pageSize.height}px`,
                backgroundColor,
                marginBottom: i < pageCount - 1 ? `${gapBetweenPages}px` : 0,
                position: 'relative',
              }}
            >
              <div
                style={{
                  position: 'absolute',
                  top: `${marginV}px`,
                  left: 0,
                  width: `${pageSize.width}px`,
                  height: `${contentAreaHeight}px`,
                  overflow: 'hidden',
                }}
              >
                <div
                  className="resume-wrapper resume-page-content"
                  style={{
                    position: 'absolute',
                    top: `${-(i * contentAreaHeight)}px`,
                    left: 0,
                    width: `${pageSize.width}px`,
                    ...templateStyles,
                  }}
                >
                  <style>{templateCSS}</style>
                  <TemplateComponent basics={basics} sections={sections} settings={settings} />
                </div>
              </div>

              {pageCount > 1 && (
                <div
                  style={{
                    position: 'absolute',
                    bottom: '8px',
                    right: '12px',
                    fontSize: '9px',
                    color: '#9ca3af',
                  }}
                >
                  {i + 1} / {pageCount}
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
