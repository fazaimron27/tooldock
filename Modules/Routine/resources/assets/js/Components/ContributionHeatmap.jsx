/**
 * ContributionHeatmap Component
 *
 * GitHub-style 365-day heatmap grid showing daily habit completions.
 * Colors are derived from the most common habit color or defaults to green.
 * Responsive: scales to fill the available container width.
 */
import { addDays, format, startOfWeek, subDays } from 'date-fns';
import { useEffect, useMemo, useRef, useState } from 'react';

import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

const DAY_LABELS = ['', 'Mon', '', 'Wed', '', 'Fri', ''];

export default function ContributionHeatmap({ habits }) {
  const containerRef = useRef(null);
  const [containerWidth, setContainerWidth] = useState(0);

  const today = useMemo(() => new Date(), []);
  const startDate = useMemo(() => subDays(today, 364), [today]);

  useEffect(() => {
    if (!containerRef.current) return;

    const observer = new ResizeObserver((entries) => {
      for (const entry of entries) {
        setContainerWidth(entry.contentRect.width);
      }
    });

    observer.observe(containerRef.current);
    setContainerWidth(containerRef.current.offsetWidth);

    return () => observer.disconnect();
  }, []);

  const logCounts = useMemo(() => {
    const counts = {};
    (habits || []).forEach((habit) => {
      (habit.logs || []).forEach((log) => {
        const key = format(new Date(log.completed_at), 'yyyy-MM-dd');
        counts[key] = (counts[key] || 0) + 1;
      });
    });
    return counts;
  }, [habits]);

  const maxCount = useMemo(() => {
    const vals = Object.values(logCounts);
    return vals.length > 0 ? Math.max(...vals) : 1;
  }, [logCounts]);

  const primaryColor = '#10b981';

  const getOpacity = (count) => {
    if (count === 0) return 0;
    if (count === 1) return 0.25;
    const ratio = count / maxCount;
    if (ratio <= 0.25) return 0.25;
    if (ratio <= 0.5) return 0.5;
    if (ratio <= 0.75) return 0.75;
    return 1;
  };

  const weeks = useMemo(() => {
    const result = [];
    const alignedStart = startOfWeek(startDate, { weekStartsOn: 0 });
    let current = alignedStart;
    let weekIdx = 0;

    while (current <= today) {
      const week = [];
      for (let d = 0; d < 7; d++) {
        const date = addDays(current, d);
        const key = format(date, 'yyyy-MM-dd');
        const isInRange = date >= startDate && date <= today;
        week.push({
          date,
          key,
          count: isInRange ? logCounts[key] || 0 : -1,
          dayOfWeek: d,
        });
      }
      result.push({ days: week, weekIdx });
      current = addDays(current, 7);
      weekIdx++;
    }
    return result;
  }, [startDate, today, logCounts]);

  const leftPad = 28;
  const topPad = 18;
  const cellGap = 2;

  const numWeeks = weeks.length || 53;
  const availableWidth = Math.max(0, containerWidth - leftPad - 4);
  const step = availableWidth / numWeeks;
  const cellSize = Math.max(2, step - cellGap);

  const svgWidth = containerWidth;
  const svgHeight = topPad + 7 * step + 4;

  const monthHeaders = useMemo(() => {
    const headers = [];
    let lastMonth = -1;
    weeks.forEach((week, i) => {
      const firstValidDay = week.days.find((d) => d.count >= 0);
      if (firstValidDay) {
        const month = firstValidDay.date.getMonth();
        if (month !== lastMonth) {
          headers.push({ label: format(firstValidDay.date, 'MMM'), weekIdx: i });
          lastMonth = month;
        }
      }
    });
    return headers;
  }, [weeks]);

  return (
    <div ref={containerRef} className="w-full">
      {containerWidth > 0 && (
        <svg width={svgWidth} height={svgHeight} className="block">
          {/* Month labels */}
          {monthHeaders.map(({ label, weekIdx }) => (
            <text
              key={`month-${weekIdx}`}
              x={leftPad + weekIdx * step}
              y={12}
              className="fill-muted-foreground"
              fontSize={10}
            >
              {label}
            </text>
          ))}

          {/* Day labels */}
          {DAY_LABELS.map((label, i) =>
            label ? (
              <text
                key={`day-${i}`}
                x={0}
                y={topPad + i * step + cellSize - 2}
                className="fill-muted-foreground"
                fontSize={10}
              >
                {label}
              </text>
            ) : null
          )}

          {/* Cells */}
          <TooltipProvider delayDuration={100}>
            {weeks.map((week) =>
              week.days.map((day) => {
                if (day.count < 0) return null;

                const x = leftPad + week.weekIdx * step;
                const y = topPad + day.dayOfWeek * step;
                const opacity = getOpacity(day.count);
                const dateLabel = format(day.date, 'MMM d, yyyy');

                return (
                  <Tooltip key={day.key}>
                    <TooltipTrigger asChild>
                      <rect
                        x={x}
                        y={y}
                        width={cellSize}
                        height={cellSize}
                        rx={2}
                        className={day.count === 0 ? 'fill-muted' : ''}
                        style={
                          day.count > 0
                            ? {
                                fill: primaryColor,
                                opacity,
                              }
                            : undefined
                        }
                      />
                    </TooltipTrigger>
                    <TooltipContent side="top" className="text-xs">
                      <span>
                        {day.count} completion
                        {day.count !== 1 ? 's' : ''} on {dateLabel}
                      </span>
                    </TooltipContent>
                  </Tooltip>
                );
              })
            )}
          </TooltipProvider>
        </svg>
      )}
    </div>
  );
}
