import { useCallback, useEffect, useLayoutEffect, useState } from 'react';

/**
 * Measures a hidden content container and detects sections that cross
 * page boundaries. Returns pageCount and applies paddingTop spacers
 * to visible .resume-page-content elements.
 *
 * Uses paddingTop (not marginTop) to avoid CSS margin collapsing.
 */
export default function usePageBreaks(contentRef, contentAreaHeight, deps) {
  const [pageCount, setPageCount] = useState(1);
  const [breakSpacers, setBreakSpacers] = useState([]);

  const measurePages = useCallback(() => {
    if (!contentRef.current) return;

    const templateRoot = contentRef.current.querySelector(':scope > div');
    const spacers = [];

    if (templateRoot) {
      const children = Array.from(templateRoot.children);

      children.forEach((child) => {
        child.style.paddingTop = '';
      });

      const MAX_PASSES = children.length + 1;
      for (let pass = 0; pass < MAX_PASSES; pass++) {
        let adjusted = false;
        const cTop = contentRef.current.getBoundingClientRect().top;

        for (let idx = 0; idx < children.length; idx++) {
          const child = children[idx];
          const rect = child.getBoundingClientRect();
          const top = rect.top - cTop;
          const bottom = top + rect.height;
          const pageOfTop = Math.floor(top / contentAreaHeight);
          const pageOfBottom = Math.floor(Math.max(0, bottom - 1) / contentAreaHeight);

          if (pageOfTop !== pageOfBottom && rect.height < contentAreaHeight) {
            const nextPageStart = (pageOfTop + 1) * contentAreaHeight;
            const push = nextPageStart - top;
            child.style.paddingTop = `${push}px`;
            spacers.push({ childIndex: idx + 1, paddingTop: push });
            adjusted = true;
            break;
          }
        }

        if (!adjusted) break;
      }
    }

    const contentHeight = contentRef.current.scrollHeight;
    const TOLERANCE = 5;
    const pages = Math.max(1, Math.ceil((contentHeight - TOLERANCE) / contentAreaHeight));
    setPageCount(pages);
    setBreakSpacers(spacers);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [contentAreaHeight]);

  useEffect(() => {
    measurePages();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [measurePages, ...deps]);

  useLayoutEffect(() => {
    const pageContents = document.querySelectorAll('.resume-page-content');
    pageContents.forEach((pc) => {
      const root = pc.querySelector(':scope > div');
      if (!root) return;
      const children = Array.from(root.children);
      children.forEach((child) => {
        child.style.paddingTop = '';
      });
      breakSpacers.forEach((s) => {
        const child = children[s.childIndex - 1];
        if (child) child.style.paddingTop = `${s.paddingTop}px`;
      });
    });
  }, [breakSpacers]);

  return pageCount;
}
