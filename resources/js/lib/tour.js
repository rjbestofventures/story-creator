import { TourGuideClient } from '@sjmc11/tourguidejs';
import '@sjmc11/tourguidejs/dist/css/tour.min.css';
import './tour-theme.css';

// Drop steps whose target isn't in the DOM right now (mobile-hidden buttons,
// admin-only variations) so a missing element never breaks or stalls the tour.
const presentSteps = (steps) => steps.filter((s) => !s.target || document.querySelector(s.target));

/**
 * Build, theme, and start a TourGuide.js tour. `onComplete` fires once whether the
 * user finishes or exits early. Returns the client, or null if no step targets exist.
 */
export function runTour(steps, { onComplete, ...options } = {}) {
    const usable = presentSteps(steps);
    if (!usable.length) return null;

    const tg = new TourGuideClient({
        steps: usable,
        exitOnClickOutside: true,
        exitOnEscape: true,
        showStepDots: true,
        showButtons: true,
        progressBar: '#F5A000',
        nextLabel: 'Next',
        prevLabel: 'Back',
        finishLabel: 'Got it',
        dialogZ: 60000,
        ...options,
    });

    if (onComplete) {
        let fired = false;
        const once = () => { if (!fired) { fired = true; onComplete(); } };
        tg.onFinish(once);
        tg.onAfterExit(once);
    }

    tg.start();
    return tg;
}

/**
 * Wait until the first step's target is actually in the DOM, then start the tour.
 * Avoids racing framework hydration with a fixed timeout. Gives up after `timeout`.
 */
export function runTourWhenReady(steps, options = {}, { timeout = 5000, interval = 120 } = {}) {
    const firstTarget = steps.find((s) => s.target)?.target;
    const began = Date.now();
    const tick = () => {
        if (!firstTarget || document.querySelector(firstTarget)) { runTour(steps, options); return; }
        if (Date.now() - began < timeout) setTimeout(tick, interval);
    };
    tick();
}
