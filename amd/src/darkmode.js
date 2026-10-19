// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Hacker mode toggle: saves the darkmode-on preference and swaps the body class without a reload.
 *
 * @module     theme_ismos/darkmode
 * @copyright  2026 ISMOS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {setUserPreference} from 'core_user/repository';
import Notification from 'core/notification';

const SELECTOR = '[data-action="theme_ismos/toggle-darkmode"]';
const MODES = ['ismos-mode-light', 'ismos-mode-dark', 'ismos-mode-auto'];

/**
 * Whether the person currently sees Hacker mode (explicit, or the device default in auto mode).
 *
 * @returns {boolean}
 */
const isDark = () => {
    const classes = document.body.classList;
    if (classes.contains('ismos-mode-dark')) {
        return true;
    }
    if (classes.contains('ismos-mode-auto')) {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    return false;
};

/**
 * Reflect the visible scheme in every toggle.
 */
const syncToggles = () => {
    const dark = isDark();
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    document.querySelectorAll(SELECTOR).forEach((toggle) => {
        toggle.setAttribute('aria-checked', dark ? 'true' : 'false');
    });
};

let initialised = false;

/**
 * Wire up the toggle. Safe to call more than once.
 */
export const init = () => {
    syncToggles();
    if (initialised) {
        return;
    }
    initialised = true;

    // In auto mode the visible scheme follows the device, so keep aria-checked current.
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', syncToggles);

    document.addEventListener('click', (e) => {
        const toggle = e.target.closest(SELECTOR);
        if (!toggle) {
            return;
        }
        e.preventDefault();
        // Store the opposite of what the person sees now.
        const makeDark = !isDark();
        document.body.classList.remove(...MODES);
        document.body.classList.add(makeDark ? 'ismos-mode-dark' : 'ismos-mode-light');
        syncToggles();
        // Guests and logged-out visitors switch the view for this page only; nothing is saved.
        if (toggle.dataset.persist !== '0') {
            setUserPreference('darkmode-on', makeDark ? 1 : 0).catch(Notification.exception);
        }
    });
};
