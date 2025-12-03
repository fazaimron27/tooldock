/**
 * Groups widgets by module, group, and type.
 *
 * @param {array} widgets - Array of widget objects
 * @returns {object} Object with module names as keys, then groups, then widget arrays by type
 */
export function groupWidgetsByModule(widgets) {
  const grouped = {};

  widgets.forEach((widget) => {
    const module = widget.module || 'Other';
    const group = widget.group || 'General';

    if (!grouped[module]) {
      grouped[module] = {};
    }

    if (!grouped[module][group]) {
      grouped[module][group] = {
        stats: [],
        charts: [],
        activities: [],
        systems: [],
        tables: [],
      };
    }

    switch (widget.type) {
      case 'stat':
        grouped[module][group].stats.push(widget);
        break;
      case 'chart':
        grouped[module][group].charts.push(widget);
        break;
      case 'activity':
        grouped[module][group].activities.push(widget);
        break;
      case 'system':
        grouped[module][group].systems.push(widget);
        break;
      case 'table':
        grouped[module][group].tables.push(widget);
        break;
    }
  });

  return grouped;
}

/**
 * Sorts modules with Core first, then alphabetically.
 *
 * @param {array} moduleNames - Array of module names
 * @returns {array} Sorted array of module names
 */
export function sortModules(moduleNames) {
  const coreIndex = moduleNames.indexOf('Core');
  if (coreIndex > -1) {
    const modules = [...moduleNames];
    modules.splice(coreIndex, 1);
    return ['Core', ...modules.sort()];
  }

  return moduleNames.sort();
}
