import Fuse from 'fuse.js';

class LemmeSearch {
    constructor() {
        this.fuse = null;
        this.data = [];
        this.initialized = false;

        // Fuse.js options
        this.options = {
            keys: [
                {
                    name: 'title',
                    weight: 0.7
                },
                {
                    name: 'content',
                    weight: 0.3
                },
                {
                    name: 'category',
                    weight: 0.2
                }
            ],
            threshold: 0.3, // Lower threshold means more exact matches
            includeScore: true,
            includeMatches: true,
            minMatchCharLength: 2,
            shouldSort: true,
            findAllMatches: false,
            location: 0,
            distance: 100,
            ignoreLocation: false,
            ignoreFieldNorm: false
        };
    }

    /**
     * Initialize Fuse.js with data
     * @param {Array} data - Array of search data
     */
    init(data) {
        this.data = data;
        this.fuse = new Fuse(data, this.options);
        this.initialized = true;
    }

    /**
     * Search using Fuse.js
     * @param {string} query - Search query
     * @param {number} limit - Maximum number of results
     * @returns {Array} - Search results
     */
    search(query, limit = 5) {
        if (!this.initialized || !query.trim()) {
            return [];
        }

        const results = this.fuse.search(query, { limit });

        const processedResults = results.map(result => ({
            ...result.item,
            score: result.score,
            matches: result.matches
        }));

        return processedResults;
    }    /**
     * Add new item to search index
     * @param {Object} item - Item to add
     */
    addItem(item) {
        this.data.push(item);
        if (this.initialized) {
            this.fuse.setCollection(this.data);
        }
    }

    /**
     * Update search data
     * @param {Array} data - New search data
     */
    updateData(data) {
        this.init(data);
    }

    /**
     * Escape text for rendering as HTML.
     *
     * Everything this class returns is handed to `x-html`, so the text has to be
     * escaped. It is escaped piece by piece, around the highlight elements rather
     * than before or after them: escaping the finished string would escape the
     * highlights too, and escaping the whole text up front would move every
     * character position the match indices are expressed in.
     *
     * @param {string} text - Text to escape
     * @returns {string} - HTML safe text
     */
    escapeHtml(text) {
        return String(text === null || text === undefined ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * The match ranges for one field, ascending and inside the text.
     *
     * Fuse reports indices into the value it indexed, which is a whole page's
     * content. The text being highlighted can be shorter than that, so a range can
     * begin past the end of it. Dropping those is the fix for the excerpt that read
     * as tag fragments and class names: an out of range start appended a highlight
     * element to the end of the string, and the next one was then spliced into the
     * middle of that element's class attribute.
     *
     * @param {string} text - Text the ranges have to fit
     * @param {Array} matches - Fuse.js matches
     * @param {string} field - Field name to match
     * @returns {Array} - Ranges as {start, end}, ascending
     */
    matchRanges(text, matches, field) {
        if (!Array.isArray(matches)) {
            return [];
        }

        return matches
            .filter(match => match && match.key === field && Array.isArray(match.indices))
            .flatMap(match => match.indices)
            .filter(index => Array.isArray(index) && index.length === 2)
            .map(([start, end]) => ({ start: Number(start), end: Number(end) }))
            .filter(range => Number.isFinite(range.start)
                && Number.isFinite(range.end)
                && range.start >= 0
                && range.end >= range.start
                && range.start < text.length)
            .map(range => ({ start: range.start, end: Math.min(range.end, text.length - 1) }))
            .sort((a, b) => a.start - b.start);
    }

    /**
     * Wrap the given ranges of text in highlight elements, escaping on the way in.
     *
     * Ranges are walked in order behind a cursor, so two ranges that overlap mark
     * one stretch of text rather than nesting one element inside the other.
     *
     * @param {string} text - Text to mark
     * @param {Array} ranges - Ranges as {start, end}, ascending
     * @returns {string} - HTML safe text carrying the highlights
     */
    markRanges(text, ranges) {
        let html = '';
        let cursor = 0;

        ranges.forEach(({ start, end }) => {
            const from = Math.max(start, cursor);

            if (from > end) {
                return;
            }

            html += this.escapeHtml(text.substring(cursor, from));
            html += '<mark class="underline bg-transparent text-lemme-accent">';
            html += this.escapeHtml(text.substring(from, end + 1));
            html += '</mark>';
            cursor = end + 1;
        });

        return html + this.escapeHtml(text.substring(cursor));
    }

    /**
     * Get highlighted search terms
     * @param {string} text - Text to highlight
     * @param {Array} matches - Fuse.js matches
     * @param {string} field - Field name to match
     * @returns {string} - HTML safe text carrying the highlights
     */
    highlightMatches(text, matches, field) {
        const plainText = String(text === null || text === undefined ? '' : text);

        return this.markRanges(plainText, this.matchRanges(plainText, matches, field));
    }

    /**
     * Get a short excerpt of text, highlighted around the first match.
     *
     * The excerpt is cut here rather than before the call because the cut has to
     * happen in the same coordinates the match indices use. The window opens a
     * little before the first match instead of always at the start of the content,
     * so the word that was searched for is in the piece the reader sees.
     *
     * @param {string} text - Full indexed text to excerpt
     * @param {Array} matches - Fuse.js matches
     * @param {string} field - Field name to match
     * @param {number} maxLength - Maximum length of the excerpt
     * @returns {string} - HTML safe excerpt carrying the highlights
     */
    highlightExcerpt(text, matches, field, maxLength = 120) {
        const plainText = String(text === null || text === undefined ? '' : text);
        const ranges = this.matchRanges(plainText, matches, field);

        if (plainText.length <= maxLength) {
            return this.markRanges(plainText, ranges);
        }

        const lead = 30;
        const firstMatch = ranges.length > 0 ? ranges[0].start : 0;
        const start = Math.max(0, Math.min(firstMatch - lead, plainText.length - maxLength));
        const end = start + maxLength;

        const windowRanges = ranges
            .filter(range => range.start < end && range.end >= start)
            .map(range => ({
                start: Math.max(range.start, start) - start,
                end: Math.min(range.end, end - 1) - start
            }));

        return (start > 0 ? '...' : '')
            + this.markRanges(plainText.substring(start, end), windowRanges)
            + (end < plainText.length ? '...' : '');
    }
}

// Create the instance synchronously when this module executes. The
// constructor has no DOM dependency, and Livewire's blocking end-of-body
// script can boot and deliver the mount-time `search-data-ready` event
// before this deferred head bundle would reach DOMContentLoaded — gating
// instantiation on DOMContentLoaded leaves the instance undefined for that
// page view. The guard keeps re-execution from clobbering an existing one.
window.LemmeSearch = LemmeSearch;
window.lemmeSearchInstance = window.lemmeSearchInstance || new LemmeSearch();

// Ask the Livewire SearchComponent for the initial search data. Livewire
// may already be initialized by the time this deferred script runs, so
// dispatch immediately if it is and otherwise wait for the event.
function requestInitialSearchData() {
    window.Livewire && window.Livewire.dispatch('init-search-data');
}

if (window.Livewire) {
    requestInitialSearchData();
} else {
    document.addEventListener('livewire:initialized', requestInitialSearchData, { once: true });
}

export default LemmeSearch;
