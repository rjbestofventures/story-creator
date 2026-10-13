// Read from the XSRF-TOKEN cookie, not the <meta> tag: the meta tag is only rendered on a
// full page load, so it goes stale after Inertia-driven logout/login rotates the token.
export function csrfHeaders() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return { 'X-XSRF-TOKEN': match ? decodeURIComponent(match[1]) : '' };
}
