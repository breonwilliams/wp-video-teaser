/**
 * Plyr type declarations
 */

interface PlyrPlayer {
  currentTime: number;
  duration: number;
  muted: boolean;
  ratio: string;
  play(): Promise<void>;
  stop(): void;
  restart(): void;
  destroy(): void;
  toggleControls(show: boolean): void;
  on(event: string, callback: () => void): void;
}

interface PlyrOptions {
  controls?: string[];
  settings?: string[];
  speed?: { selected: number; options: number[] };
  ratio?: string;
  storage?: { enabled: boolean };
  keyboard?: { focused: boolean; global: boolean };
  tooltips?: { controls: boolean; seek: boolean };
  clickToPlay?: boolean;
  hideControls?: boolean;
  autopause?: boolean;
  resetOnEnd?: boolean;
  muted?: boolean;
  autoplay?: boolean;
  loop?: { active: boolean };
  youtube?: {
    noCookie?: boolean;
    rel?: number;
    modestbranding?: number;
    playsinline?: number;
  };
  vimeo?: {
    byline?: boolean;
    portrait?: boolean;
    title?: boolean;
    transparent?: boolean;
  };
}

declare class Plyr {
  constructor(element: string | HTMLElement, options?: PlyrOptions);
  currentTime: number;
  duration: number;
  muted: boolean;
  ratio: string;
  play(): Promise<void>;
  stop(): void;
  restart(): void;
  destroy(): void;
  toggleControls(show: boolean): void;
  on(event: string, callback: () => void): void;
}
