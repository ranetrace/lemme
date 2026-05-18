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
     * Get highlighted search terms
     * @param {string} text - Text to highlight
     * @param {Array} matches - Fuse.js matches
     * @param {string} field - Field name to match
     * @returns {string} - Highlighted text
     */
    highlightMatches(text, matches, field) {
        if (!matches || matches.length === 0) {
            return text;
        }

        const fieldMatches = matches.filter(match => match.key === field);
        if (fieldMatches.length === 0) {
            return text;
        }

        let highlightedText = text;
        const ranges = [];

        // Collect all match ranges
        fieldMatches.forEach(match => {
            if (match.indices) {
                match.indices.forEach(([start, end]) => {
                    ranges.push({ start, end });
                });
            }
        });

        // Sort ranges by start position (descending to replace from end to beginning)
        ranges.sort((a, b) => b.start - a.start);

        // Apply highlights
        ranges.forEach(({ start, end }) => {
            const before = highlightedText.substring(0, start);
            const match = highlightedText.substring(start, end + 1);
            const after = highlightedText.substring(end + 1);

            highlightedText = before +
                '<mark class="underline bg-transparent text-lemme-accent">' +
                match +
                '</mark>' +
                after;
        });

        return highlightedText;
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
