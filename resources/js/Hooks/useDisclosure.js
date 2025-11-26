import { useState } from 'react';

/**
 * Hook for managing boolean disclosure states (modals, dialogs, sheets, etc.)
 * @param {boolean} initialState - Initial open state (default: false)
 * @returns {object} { isOpen, onOpen, onClose, onToggle }
 */
export function useDisclosure(initialState = false) {
  const [isOpen, setIsOpen] = useState(initialState);

  const onOpen = () => {
    setIsOpen(true);
  };

  const onClose = () => {
    setIsOpen(false);
  };

  const onToggle = () => {
    setIsOpen((prev) => !prev);
  };

  return {
    isOpen,
    onOpen,
    onClose,
    onToggle,
  };
}
