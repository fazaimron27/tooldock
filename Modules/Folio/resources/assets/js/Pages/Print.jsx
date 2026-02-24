/**
 * Folio Print Page
 *
 * Standalone page that renders the resume template for printing.
 * Opens in a new tab — user saves as PDF via browser print dialog.
 * True WYSIWYG: same React components, styling, and page-break logic
 * as the live preview in the Builder.
 */
import BoldTemplate from '@Folio/Components/Templates/BoldTemplate';
import ElegantTemplate from '@Folio/Components/Templates/ElegantTemplate';
import ModernTemplate from '@Folio/Components/Templates/ModernTemplate';
import ProfessionalTemplate from '@Folio/Components/Templates/ProfessionalTemplate';
import useGoogleFonts from '@Folio/Hooks/useGoogleFonts';
import usePageBreaks from '@Folio/Hooks/usePageBreaks';
import { FONT_MAP } from '@Folio/constants/fonts';
import { PAGE_SIZES } from '@Folio/constants/pageSizes';
import { Head, usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useRef } from 'react';

import { Button } from '@/Components/ui/button';

import '../css/folio.css';

const TEMPLATES = {
  professional: ProfessionalTemplate,
  modern: ModernTemplate,
  elegant: ElegantTemplate,
  bold: BoldTemplate,
};

export default function Print() {
  const { content, folioName } = usePage().props;
  const contentRef = useRef(null);

  const template = content?.template || 'professional';
  const TemplateComponent = TEMPLATES[template] || TEMPLATES.professional;
  const basics = content?.basics || {};
  const sections = content?.sections || {};
  const settings = content?.settings || {};
  const pageTitle = `${basics.name || folioName || 'Resume'} — Resume`;

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

  const contentAreaHeight = pageSize.height - 2 * marginV;

  useGoogleFonts(bodyFontKey, headingFontKey);
  const pageCount = usePageBreaks(contentRef, contentAreaHeight, [content, settings]);

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

  const handlePrint = () => window.print();
  const gapBetweenPages = 16;

  return (
    <>
      <Head title={pageTitle} />
      <style>{`
        @media print {
          @page {
            size: ${pageFormat === 'letter' ? 'letter' : 'A4'};
            margin: 0;
          }
          body {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
          #print-toolbar {
            display: none !important;
          }
          #print-wrapper {
            padding: 0 !important;
            background: white !important;
            min-height: auto !important;
            gap: 0 !important;
          }
          .print-page {
            margin: 0 !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            break-after: page;
            overflow: hidden !important;
            width: ${pageSize.width}px !important;
            height: ${pageSize.height}px !important;
            page-break-inside: avoid;
          }
          .print-page:last-child {
            break-after: auto;
          }
          .print-page-number {
            display: none !important;
          }
        }
      `}</style>

      <div
        id="print-toolbar"
        className="fixed top-0 left-0 right-0 z-50 border-b bg-background/95 backdrop-blur-sm"
      >
        <div className="flex items-center justify-between px-6 py-3">
          <span className="text-sm font-semibold text-foreground">
            {basics.name || folioName || 'Resume'} — Print Preview
          </span>
          <Button onClick={handlePrint} size="sm">
            <Download className="mr-2 h-4 w-4" />
            Save as PDF
          </Button>
        </div>
      </div>

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

      <div
        id="print-wrapper"
        className="min-h-screen bg-muted/50 flex flex-col items-center"
        style={{ paddingTop: '64px' }}
      >
        {Array.from({ length: pageCount }, (_, i) => (
          <div
            key={i}
            className="print-page shadow-xl border border-gray-200 rounded overflow-hidden"
            style={{
              width: `${pageSize.width}px`,
              height: `${pageSize.height}px`,
              backgroundColor,
              marginTop: i === 0 ? '24px' : 0,
              marginBottom: i < pageCount - 1 ? `${gapBetweenPages}px` : '24px',
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
                className="print-page-number"
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
    </>
  );
}
