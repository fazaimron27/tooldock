/**
 * Hook for managing boolean disclosure states (modals, dialogs, sheets, etc.)
 * Provides simple state management for open/close functionality
 */
import { useState } from 'react';

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
