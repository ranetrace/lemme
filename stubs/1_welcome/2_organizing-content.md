# Organizing Content

Learn how to structure your documentation with Lemme's directory-based grouping system.

## Directory Groups

Lemme automatically groups pages based on directories. Pages in the same directory appear together in the navigation with a shared header.

## Number Prefixes

Control the order of your directories and files using number prefixes:

- `1_welcome/` - Groups will be sorted numerically
- `1_getting-started.md` - Files within groups are also sorted
- `01_introduction.md` - You can use different number formats

The numbers are automatically removed from display titles.

## Navigation Structure

Your directory structure becomes your navigation:

```
docs/
├── index.md              → "Home" 
├── 1_getting-started/    → "Getting started" (group)
│   ├── 1_installation.md
│   └── 2_setup.md
├── 2_guides/             → "Guides" (group)
│   └── 1_basics.md
└── changelog.md          → "Changelog" (standalone)
```

## Tips

- Keep group names descriptive but concise
- Use number prefixes to control order
- Mix grouped and standalone pages as needed
- Nested directories are supported

That's it! Your content structure drives your navigation automatically.
