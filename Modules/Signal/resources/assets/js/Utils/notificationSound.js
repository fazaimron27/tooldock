/**
 * Notification Sound Utility
 *
 * Provides audio feedback for real-time notifications with:
 * - Per-type sound mapping (info, success, warning, error)
 * - Audio preloading to bypass browser autoplay restrictions
 * - Debouncing to prevent audio overlap from rapid notifications
 *
 * @author     Tool Dock Team
 * @license    MIT
 */
/* eslint-env browser */
import defaultSound from '@Signal/../audio/notification-default.mp3';
import errorSound from '@Signal/../audio/notification-error.mp3';
import successSound from '@Signal/../audio/notification-success.mp3';
import warningSound from '@Signal/../audio/notification-warning.mp3';

/**
 * Map notification types to their corresponding audio files.
 * Vite handles these imports by providing the correct resolved URL.
 */
const TYPE_TO_SOUND = {
  info: defaultSound,
  success: successSound,
  warning: warningSound,
  error: errorSound,
};

/**
 * Minimum time (ms) between notification sounds to prevent overlap.
 */
const DEBOUNCE_MS = 500;

/**
 * Cache for preloaded Audio objects.
 * @type {Map<string, HTMLAudioElement>}
 */
const audioCache = new Map();

/**
 * Timestamp of the last played sound for debouncing.
 * @type {number}
 */
let lastPlayedAt = 0;

/**
 * Whether audio has been unlocked via user interaction.
 * @type {boolean}
 */
let audioUnlocked = false;

/**
 * Preload all notification sounds into the audio cache.
 * Should be called after first user interaction to bypass autoplay restrictions.
 *
 * @returns {void}
 */
export function preloadNotificationSounds() {
  if (audioUnlocked) {
    return;
  }

  Object.values(TYPE_TO_SOUND).forEach((url) => {
    const audio = new window.Audio(url);
    audio.preload = 'auto';
    audio.volume = 0.5;

    audio.load();
    audioCache.set(url, audio);
  });

  audioUnlocked = true;

  if (import.meta.env.DEV) {
    console.log('[NotificationSound] Audio preloaded and unlocked');
  }
}

/**
 * Play the notification sound for a given notification type.
 * Respects debouncing to prevent rapid audio overlap.
 *
 * @param {string} type - Notification type (info, success, warning, error)
 * @param {number} volume - Volume level (0.0 to 1.0), defaults to 0.5
 * @returns {void}
 */
export function playNotificationSound(type, volume = 0.5) {
  const now = Date.now();

  if (now - lastPlayedAt < DEBOUNCE_MS) {
    return;
  }

  const url = TYPE_TO_SOUND[type] || TYPE_TO_SOUND.info;
  let audio = audioCache.get(url);

  if (!audio) {
    audio = new window.Audio(url);
    audioCache.set(url, audio);
  }
  audio.volume = Math.max(0, Math.min(1, volume));
  audio.currentTime = 0;

  audio.play().catch((error) => {
    if (import.meta.env.DEV) {
      console.warn('[NotificationSound] Playback failed:', error.message);
    }
  });

  lastPlayedAt = now;
}

/**
 * Initialize audio unlock listener.
 * Attaches a one-time event listener for user interaction to preload sounds.
 *
 * @returns {void}
 */
export function initNotificationSoundUnlock() {
  if (audioUnlocked) {
    return;
  }

  const unlockHandler = () => {
    preloadNotificationSounds();
    document.removeEventListener('click', unlockHandler);
    document.removeEventListener('keydown', unlockHandler);
  };

  document.addEventListener('click', unlockHandler, { once: true });
  document.addEventListener('keydown', unlockHandler, { once: true });
}
