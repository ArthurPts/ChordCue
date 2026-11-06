# Design System — Dark Blue (Music Companion)

> **Bahasa:** Aturan (rules) ditulis dalam bahasa Inggris agar tidak ambigu bagi agent. Penjelasan ditulis dalam bahasa Indonesia, diawali tanda 💬.

| File | Location in ChordCue | Purpose |
|---|---|---|
| `DESIGN_SYSTEM.md` | `.agents/rules/` | Rules and reasoning (this file). Read first. |
| `tokens.css` | `resources/css/` | Implementation. Imported first in `app.css`; never copy values. |
| `tokens.json` | `01-docs/design-system/` | Same 86 tokens for tooling (Figma, Style Dictionary, other agents). |
| `tailwind.config.js` | project root | Maps tokens to Tailwind utilities (section 14). |

**Source of truth:** `tokens.css` and `tokens.json` MUST contain identical names and values. If they disagree, `tokens.css` wins and `tokens.json` must be fixed.

Every token is tagged `[original]` (from the source HTML) or `[derived]` (added or improved here). See section 13.

### Quick start (ChordCue: Laravel 12 + Tailwind 3)

1. Put `tokens.css` in `resources/css/` and import it first in `app.css`.
2. Replace `tailwind.config.js` with the token mapping.
3. Create the Blade component `<x-icon>`.

All code for steps 1-3 is in **section 14**. Fonts need no setup: the system font stack is used. Every step uses built-in Laravel/Vite features, so no package is installed.

💬 Semua langkah memakai fitur bawaan Laravel dan Vite, jadi tidak melanggar aturan "dilarang install package baru".

---

## 0. Rules for agents (read this first)

1. **MUST** use tokens (`var(--color-surface-1)`). **MUST NOT** write raw hex, raw color values, or invent colors.
2. **MUST** follow the 60/30/10 ratio (section 1). Accent is a scarce resource.
3. **MUST** use only font weights 400 and 500, and only the six sizes in section 3.
4. **MUST** use the radius, spacing, size, motion, and z-index tokens. **MUST NOT** use values outside them.
5. **MUST** keep this theme dark-only.
6. **MUST** use the `<x-icon>` Blade component (Tabler outline SVGs copied into it), sized per section 7. **MUST NOT** install an icon package or icon font.
7. **MUST** pick a component recipe from section 6 before building UI. If none fits, follow section 11.
8. **MUST NOT** use gradients, drop shadows, or glow. Depth comes from surface layers, 1px borders, and the dialog scrim.
9. **MUST** write UI copy per section 9.
10. **MUST** run the checklist in section 12 before delivering output.
11. **MUST** build visuals only with the token utilities from section 14 (`bg-surface-1`, `text-ink-secondary`, `p-16`). **MUST NOT** use Tailwind's default palette or scale, arbitrary values (`bg-[#...]`, `text-[13px]`), opacity modifiers on token colors (`bg-accent/50`), `shadow-*`, `bg-gradient-*`, or `style=""`.

💬 Intinya: agent tidak boleh menebak. Semua nilai harus dari token. Kedalaman dibuat dengan lapisan surface dan border tipis, bukan bayangan.

---

## 1. Principles

| Principle | Meaning |
|---|---|
| **Calm** | Quiet chrome; content comes first. Dark blue, low-saturation surfaces. |
| **Layered** | Hierarchy is made by surface level, color, and weight, not by size jumps or effects. |
| **Accent is a signal** | Accent marks "act here" or "you are here". Never decoration. |
| **Friendly, not casual-sloppy** | Rounded type and soft radii, but precise spacing and clear copy. |

### 60 / 30 / 10

| Share | Role | Tokens |
|---|---|---|
| 60% | Page background | `--color-bg-base` |
| 30% | Surfaces (cards, navbar, panels, inputs) | `--color-surface-1`, `-2`, `-3` |
| ≤10% | Accent (actions, active state, brand) | `--color-accent-*` |

- Accent **MUST NOT** fill large areas. Max one primary button per card, dialog, or section.
- Text, borders, and semantic colors are not counted in the ratio.
- Elevation order: `bg-base` < `surface-1` < `surface-2` < `surface-3`. A child **MUST** be equal to or higher than its parent, never lower.

💬 Semakin tinggi nomor surface, semakin "terangkat". Kartu (surface-1) boleh berisi input (surface-3), tapi jangan sebaliknya.

---

## 2. Color

### 2.1 Background, overlay, borders

| Token | Value | Use for | Do not use for |
|---|---|---|---|
| `--color-bg-base` | `#0A0F1A` | Page background; knob of an "on" toggle | Cards |
| `--color-surface-1` | `#111826` | Default card, navbar, panel | Page background |
| `--color-surface-2` | `#182133` | Card hover, raised panel, dialog, menu | Resting card |
| `--color-surface-3` | `#212C42` | Input, avatar, tooltip, toast | Large areas |
| `--color-overlay` `[derived]` | `rgba(10,15,26,.72)` | Scrim behind dialogs | Anything else |
| `--color-border-subtle` | `#2A3752` | Every border and divider; "off" toggle track; progress track | Text |

### 2.2 Accent

| Token | Hex | Use for |
|---|---|---|
| `--color-accent-primary` | `#5B8DEF` | Primary button fill, active nav, brand icon, "on" toggle, progress fill, input focus border |
| `--color-accent-hover` | `#7AA2F7` | Hover/pressed of accent; focus ring |
| `--color-accent-soft-bg` | `#1B2A4A` | Chip/badge background; selected list row |
| `--color-accent-soft-text` | `#A9C4FF` | Text/icons on `accent-soft-bg` and on `surface-3` |
| `--color-on-accent` `[derived]` | `#0A0F1A` | Text/icon on `accent-primary` fills |

- Text on an accent fill **MUST** be `on-accent` (dark), never white.
- `accent-primary` as **text** is allowed only on `bg-base`, `surface-1`, `surface-2`. On `surface-3` it is 4.3:1 (fails AA); use `accent-soft-text` there.

### 2.3 Text

| Token | Hex | Use for |
|---|---|---|
| `--color-text-primary` | `#E8EEF9` | Titles, main content, active labels |
| `--color-text-secondary` | `#A3AEC4` | Subtitles, inactive nav, neutral icons |
| `--color-text-tertiary` `[improved]` | `#8A95AB` | Placeholder, caption, hint (was `#7A869F`) |
| `--color-text-disabled` | `#465068` | Disabled elements only |

### 2.4 Semantic

| Token | Hex | Tint (alert background) | Meaning |
|---|---|---|---|
| `--color-success` | `#5CC8A1` | `--color-success-tint` `#1A2D35` | Done, saved, healthy |
| `--color-warning` | `#E0B25B` | `--color-warning-tint` `#2A2A2C` | Needs attention |
| `--color-error` | `#E5766E` | `--color-error-tint` `#2A232F` | Failure, destructive action |

- Use semantic colors as text, icon, 1px border, or the matching `-tint` background. Never a solid saturated fill.
- Never rely on color alone. Always pair with an icon and a label.

### 2.5 Contrast reference (WCAG 2.x, computed)

| Pair | Ratio | Status |
|---|---|---|
| text-primary on bg-base / surface-1 / 2 / 3 | 16.5 / 15.3 / 13.8 / 12.0 | ✅ AAA |
| text-secondary on bg-base / surface-1 / 2 / 3 | 8.6 / 8.0 / 7.2 / 6.3 | ✅ AA+ |
| text-tertiary on bg-base / surface-1 / 2 / 3 | 6.4 / 5.9 / 5.3 / 4.6 | ✅ AA everywhere |
| accent-primary text on bg-base / surface-1 | 5.9 / 5.5 | ✅ AA |
| accent-primary text on surface-3 | 4.3 | ❌ do not use |
| on-accent on accent-primary / accent-hover | 5.9 / 7.6 | ✅ AA |
| accent-soft-text on accent-soft-bg / surface-3 | 8.1 / 8.0 | ✅ AAA |
| success / warning / error on surface-1 | 8.7 / 9.0 / 6.1 | ✅ AA |
| success / warning / error on own tint | 7.0 / 7.3 / 5.2 | ✅ AA |
| error on surface-3 | 4.8 | ✅ AA |
| progress fill vs track | 3.7 | ✅ ≥3:1 (UI component) |
| border-subtle on bg-base / surface-1 | 1.6 / 1.5 | decorative only |

- `border-subtle` is decorative. State **MUST NOT** be communicated by border color alone.
- Toggle "off" is an accepted exception (section 13, decision 2).

💬 Warna tertiary dinaikkan sedikit dari aslinya supaya lolos standar keterbacaan di semua surface, termasuk placeholder input.

---

## 3. Typography

**Typeface:** the system font stack (no font files, no CDN). Native, fast, and familiar on every device. Use weights **400 and 500 only**.

| Token | Value | Use for |
|---|---|---|
| `--font-sans` | System sans stack | All UI text |
| `--font-mono` | system mono stack | Hex, IDs, code |
| `--font-size-caption` | 12px | Chip, label kecil, hint, avatar initials |
| `--font-size-body` | 13px | Default UI text: button, nav, subtitle, input, toggle label |
| `--font-size-nav-title` | 14px | App name in navbar |
| `--font-size-title` | 15px | Card title |
| `--font-size-heading` `[derived]` | 18px | Dialog / section heading |
| `--font-size-display` `[derived]` | 24px | Page title, max one per page |
| `--font-weight-regular` / `-medium` | 400 / 500 | Body / titles, buttons, active items |
| `--line-height-base` / `-heading` `[derived]` | 1.4 / 1.25 | Body / heading + display |

- **MUST NOT** add sizes or weights. **MUST NOT** use ALL CAPS or underline for emphasis.
- Hierarchy = color (primary → secondary → tertiary) + weight (400 → 500).
- System fonts differ slightly per device (San Francisco, Segoe UI, Roboto). Design for truncation (`text-overflow: ellipsis`) on single-line titles and never shrink font size to fit. Check layouts on at least two operating systems.

💬 Font sebelumnya (`anthropic-sans`) hanya ada di lingkungan Claude, jadi diganti font bawaan perangkat: cepat, tanpa file tambahan, dan tampil mulus di setiap OS. Nuansa "santai tapi tetap rapi" dijaga lewat radius lembut, warna, dan gaya bahasa, bukan lewat font. Dua ukuran baru (18 dan 24px) ditambahkan karena UI nyata butuh judul halaman dan dialog.

---

## 4. Borders, focus, motion

| Token | Value | Rule |
|---|---|---|
| `--border-width` | 1px | Every border. |
| `--focus-ring-width` / `-offset` | 2px / 2px | `:focus-visible` only |

Focus ring: `outline: var(--focus-ring-width) solid var(--color-accent-hover); outline-offset: var(--focus-ring-offset);`

### Motion `[derived]`

| Token | Value | Use for |
|---|---|---|
| `--motion-duration-fast` | 120ms | Hover, color change, toggle |
| `--motion-duration-base` | 200ms | Dialog, toast, dropdown enter |
| `--motion-duration-slow` | 320ms | Large layout transitions |
| `--motion-ease-standard` | `cubic-bezier(0.2,0,0,1)` | Default |
| `--motion-ease-out` | `cubic-bezier(0,0,0.2,1)` | Elements entering |

- Default transition: `background-color, border-color, color var(--motion-duration-fast) var(--motion-ease-standard)`.
- **MUST NOT** animate `width/height/top/left`. Animate only `opacity` and `transform` (translateY ≤ 8px).
- Exits are faster than entrances (use `fast` to leave, `base` to enter).
- `tokens.css` zeroes all durations under `prefers-reduced-motion: reduce`. **MUST NOT** override this.

---

## 5. Spacing, radius, size, layout

### 5.1 Spacing

`--space-4 · 6 · 8 · 10 · 12 · 14 · 16 · 20 · 24 · 32`

| Typical use | Token |
|---|---|
| Chip vertical padding; tiny gap | `4` |
| Gap between chips | `6` |
| Gap between buttons/icons; button vertical padding; tooltip padding | `8` |
| Chip horizontal padding | `10` |
| Gap between cards; input horizontal padding | `12` |
| Button horizontal padding | `14` |
| Card / navbar padding; page padding below 768px | `16` |
| Gap between navbar items | `20` |
| Dialog padding; page padding from 768px; gap between sections | `24` |
| Empty state padding; gap between large blocks | `32` |

### 5.2 Radius

| Token | Value | Use for |
|---|---|---|
| `--radius-sm` | 8px | Button, input, icon button, tooltip, toast, menu |
| `--radius-md` | 10px | Navbar |
| `--radius-lg` | 12px | Card, dialog |
| `--radius-full` | 999px | Chip, toggle, progress (avatar: `50%`) |

A child's radius **MUST NOT** exceed its parent's.

### 5.3 Fixed sizes

| Token | Value | Element |
|---|---|---|
| `--size-navbar-height` | 48px | Navbar |
| `--size-control-height` | 36px | Input, button |
| `--size-icon-button` | 34px | Icon button |
| `--size-avatar` | 28px | Avatar |
| `--size-toggle-width/height/knob` | 36 / 20 / 16px | Toggle (inset 2px) |
| `--size-progress-height` | 4px | Progress bar, slider track |
| `--size-slider-thumb` | 14px | Slider thumb |
| `--size-dialog-width` | 480px | Dialog max width |
| `--size-toast-width` | 360px | Toast max width |
| `--size-tooltip-max-width` | 240px | Tooltip max width |
| `--size-touch-target-min` | 44px | Minimum hit area on touch |

### 5.4 Layout `[derived]`

- Content container: `max-width: var(--layout-content-max)` (1120px), centered.
- Page padding: `space-16` below 768px, `space-24` from 768px.
- Card grid gap: `space-12`. Section gap: `space-24`.
- Breakpoints: 640 / 768 / 1024 / 1280px (`--breakpoint-*`). CSS variables do not work inside `@media`, so write the number literally.
- Design mobile-first. Below 640px, cards are single column and the navbar collapses to brand + avatar + menu button.
- **Touch:** on `(pointer: coarse)`, every interactive element **MUST** have a hit area ≥ `size-touch-target-min` (extend with padding or a pseudo-element; the visual size stays the same).

### 5.5 Z-index `[derived]`

`z-base 0` · `z-sticky 10` (navbar) · `z-dropdown 20` (menu, tooltip) · `z-overlay 30` (scrim) · `z-modal 40` · `z-toast 50`.
**MUST NOT** use any other z-index value.

---

## 6. Components

All recipes use tokens only.

### 6.1 Navbar
- bg `surface-1`, border `1px border-subtle`, radius `radius-md`, height `size-navbar-height`, padding `0 space-16`, gap `space-20`, sticky at `z-sticky`.
- Brand: icon 18px `accent-primary` + name `font-size-nav-title`/500/`text-primary`, gap `space-8`.
- Link active: `font-size-body`/500/`accent-primary`. Inactive: 400/`text-secondary`, hover `text-primary`.
- Avatar at far right (`margin-left: auto`). Mark the active link with `aria-current="page"`.

### 6.2 Card
- Resting: bg `surface-1`, border `1px border-subtle`, radius `radius-lg`, padding `space-16`. Hover (interactive only): bg `surface-2`.
- Order: title (`font-size-title`/500/primary) → subtitle (`font-size-body`/secondary, margin `2px 0 space-12`) → chips (gap `space-6`, bottom `space-14`) → actions (gap `space-8`).
- At most one primary button. **MUST NOT** nest a card in a card with the same surface; step up one surface level or use a divider.

### 6.3 Chip
- bg `accent-soft-bg`, text `accent-soft-text`, `font-size-caption`, padding `space-4 space-10`, radius `radius-full`; optional icon 13px, gap `space-4`.
- A label, not a button. If clickable, it becomes a `button` and gains hover (`surface-3`) and focus ring.

### 6.4 Buttons
Shared: height `size-control-height`, padding `space-8 space-14`, radius `radius-sm`, `font-size-body`/500, icon 16px with gap `space-6`, default transition.

| Variant | Fill | Border | Text | Hover |
|---|---|---|---|---|
| **Primary** | `accent-primary` | none | `on-accent` | fill `accent-hover` |
| **Secondary** `[derived]` | transparent | `1px border-subtle` | `text-primary` | bg `surface-2` |
| **Danger** `[derived]` | transparent | `1px error` | `error` | bg `error-tint` |
| **Text** `[derived]` | transparent | none | `accent-primary` | text `accent-hover` |

- **Disabled:** Primary → bg `surface-3`, text `text-disabled`. Others → text `text-disabled`, border `border-subtle`. No hover, `cursor: not-allowed`, use `disabled` or `aria-disabled`.
- **Loading:** keep width, replace leading icon with the `loader-2` icon spinning, set `aria-busy="true"`, block clicks.
- Hierarchy per area: one Primary, rest Secondary. Destructive actions use Danger and need confirmation (dialog).

### 6.5 Icon button
- `size-icon-button` square, transparent, border `1px border-subtle`, icon 17px `text-secondary`, radius `radius-sm`.
- Hover `[derived]`: bg `surface-2`, icon `text-primary`. Destructive hover: icon and border `error`.
- **MUST** have `aria-label`. Provide a tooltip (6.10) when the meaning isn't obvious.

### 6.6 Input, textarea, select
- Input: height `size-control-height`, bg `surface-3`, padding `0 space-12`, radius `radius-sm`, gap `space-8`, leading icon 16px `text-tertiary`, placeholder `text-tertiary`, text `text-primary`.
- Textarea `[derived]`: same style, padding `space-12`, min-height 96px, vertical resize only.
- Select `[derived]`: same as input + trailing `chevron-down` icon 16px `text-tertiary`; menu in 6.11.
- Focus: border `1px accent-primary` + focus ring. Error: border `1px error` + helper text `font-size-caption` in `error` with an alert icon. Disabled: text `text-disabled`.
- Every input **MUST** have a visible or `aria-label` label. Helper text sits `space-6` below, in `text-secondary`.
- On a `surface-2` container, inputs use `surface-3`.

### 6.7 Toggle
- Track `size-toggle-width` × `size-toggle-height`, `radius-full`; knob `size-toggle-knob`, inset 2px; knob moves with `motion-duration-fast`.
- **On:** track `accent-primary`, knob `bg-base`, knob right; label `text-primary`.
- **Off:** track `border-subtle`, knob `text-secondary`, knob left; label `text-secondary`. *(Kept as in the original by owner decision.)*
- Label `font-size-body`, left of the track, space-between.
- Markup: `role="switch"` + `aria-checked`. State is communicated by knob position and label color, never track color alone.

### 6.8 Avatar
- `size-avatar` circle, bg `surface-3`, initials `accent-soft-text`, `font-size-caption`/500.

### 6.9 Progress `[derived]`
- Track: height `size-progress-height`, bg `border-subtle`, `radius-full`. Fill: `accent-primary`, `radius-full`.
- Optional label `font-size-caption`/`text-secondary` above, time or value right-aligned.
- Use native `<progress value max class="ui-progress">` (built-in progressbar role; add an accessible label). Indeterminate: omit `value`. **MUST NOT** set the width with an inline style.

### 6.10 Tooltip `[derived]`
- bg `surface-3`, border `1px border-subtle`, text `text-primary` `font-size-caption`, padding `space-4 space-8`, radius `radius-sm`, max-width `size-tooltip-max-width`, `z-dropdown`.
- Shows on hover (400ms delay) and on keyboard focus; hides on Esc. One short sentence, no interactive content.

### 6.11 Menu / dropdown `[derived]`
- Container: bg `surface-2`, border `1px border-subtle`, radius `radius-sm`, padding `space-4`, `z-dropdown`.
- Item: padding `space-8 space-12`, `font-size-body`/`text-primary`, radius `radius-sm`; hover/arrow-key focus: bg `surface-3`; selected: `accent-soft-text` + check icon; destructive item: `error`.
- Keyboard: arrows move, Enter selects, Esc closes and returns focus.

### 6.12 List row `[derived]`
- Row padding `space-12 space-16`, divider `1px border-subtle` between rows, no outer gap. Hover: bg `surface-2`. Selected: bg `accent-soft-bg`.
- Content: leading avatar/icon → title (`font-size-body`/500/primary, ellipsis) + subtitle (`font-size-caption`/secondary) → trailing icon button(s).

### 6.13 Dialog `[derived]`
- Scrim `color-overlay` at `z-overlay`. Panel: bg `surface-2`, border `1px border-subtle`, radius `radius-lg`, padding `space-24`, width `min(size-dialog-width, 100% - 32px)`, `z-modal`.
- Heading `font-size-heading`/500/`line-height-heading`; body `font-size-body`/`text-secondary`; close icon button top-right.
- Footer: right-aligned, gap `space-8`; Secondary (cancel) left of Primary or Danger.
- Enter with fade + translateY(8px) over `motion-duration-base`. `role="dialog"`, `aria-modal="true"`, labelled by heading; trap focus; Esc closes; return focus to the trigger.

### 6.14 Toast and inline alert `[derived]`
- **Toast:** bg `surface-3`, border `1px border-subtle`, radius `radius-sm`, padding `space-12 space-16`, max-width `size-toast-width`, `z-toast`, bottom-center on mobile, bottom-right from 768px. Leading 16px icon in semantic color, text `font-size-body`/`text-primary`.
- Auto-dismiss after 5s; **errors never auto-dismiss**. `role="status"`; errors use `role="alert"`. Max 3 stacked, gap `space-8`.
- **Inline alert:** bg `*-tint`, border `1px` semantic color, radius `radius-sm`, padding `space-12`, icon + text, text `text-primary`.

### 6.15 Empty state `[derived]`
- Centered in the container, padding `space-32`. Optional icon 18px `text-tertiary`; title `font-size-title`/500/primary; one-line explanation `font-size-body`/secondary (max ~40 characters per line); **one** Primary button that fixes the emptiness.

### 6.16 Loading skeleton `[derived]`
- Blocks in `surface-2` with `radius-sm`, matching the final layout. Pulse between `surface-2` and `surface-3`, 1.2s ease-in-out infinite; static under reduced motion.
- Use for loads > 300ms. Never show a bare spinner for page-level content.

### 6.17 Slider `[derived]`
For seeking and volume. Extends Progress (6.9).
- Track and fill as Progress. Thumb: `size-slider-thumb` circle, `accent-primary`, flat (no shadow, no border). Hover: `accent-hover`. Focus: ring from section 4 on the thumb.
- Hit area: extend vertically to `size-touch-target-min` with padding or a pseudo-element; the visible track stays `size-progress-height`.
- Time/value labels: `font-size-caption`, `text-secondary` (never tertiary, they are essential), `font-variant-numeric: tabular-nums`, current value left, total right.
- Markup: `.ui-slider` wrapper holding a `<progress class="ui-progress">` and a transparent native `<input type="range">` with the same value (section 14). The native input already gives the slider role and arrow / PageUp / PageDown / Home / End keys. Add a label and `aria-valuetext` (e.g. "1:24 of 3:45").
- Show the thumb while dragging. Dragging **MUST NOT** use transitions (value follows the pointer instantly).

### 6.18 Tabs `[derived]`
For switching views inside one page (e.g. Library / Queue / Lyrics). Not for page navigation; that is the navbar (6.1).
- Container: bg `surface-2`, radius `radius-sm`, padding `space-4`, gap `space-4`.
- Tab: padding `space-6 space-12`, `font-size-body`/500, radius `radius-sm`, default transition.
- **Active:** bg `accent-soft-bg`, text `accent-soft-text`. **Inactive:** text `text-secondary`; hover bg `surface-3`, text `text-primary`.
- Max 5 tabs; labels one or two words. Below 640px the container scrolls horizontally instead of wrapping.
- `role="tablist"` / `role="tab"` / `role="tabpanel"`, `aria-selected`. Left/Right arrows move between tabs, Home/End jump, Tab key moves into the panel.

---

## 7. Icons

- Source: **Tabler Icons** (MIT), outline style only, copied one by one into the `<x-icon name="...">` Blade component (section 14). No icon package, no icon font.
- Decorative icons: `aria-hidden="true"`. Icon-only controls: `aria-label`.

| Context | Size token | Color |
|---|---|---|
| Navbar brand | `size-icon-nav` 18px | `accent-primary` |
| Button / input | `size-icon-control` 16px | inherits |
| Icon button | `size-icon-button-glyph` 17px | `text-secondary` |
| Inside chip | `size-icon-chip` 13px | `accent-soft-text` |

---

## 8. State rules (apply to every interactive element)

| State | Rule |
|---|---|
| Default | Per recipe. |
| Hover | One step: surface up one level, or `accent-hover`. Transition `motion-duration-fast`. No scale, no shadow. |
| Pressed | Same as hover. |
| Focus-visible | Ring from section 4. Never `outline: none` without a replacement. |
| Disabled | `text-disabled`, no hover, `cursor: not-allowed`. |
| Loading | Section 6.4. |
| Error | Border/text `error` + icon + message. |
| Selected | `accent-soft-bg` (rows, chips) or `accent-primary` (nav, toggle). |

---

## 9. Content and voice `[derived]`

**Tone: casual but not too informal.** Like a helpful colleague: warm, direct, never sloppy.

| Do | Don't |
|---|---|
| Sentence case: "Add to playlist" | Title Case / ALL CAPS |
| Short, active sentences | Long passive explanations |
| Contractions are fine: "You're all set" | Slang, memes, "Oops!!!" |
| Say what happened and what to do next | Blame the user, or vague "Something went wrong" |
| Buttons start with a verb: "Save changes" | "OK", "Submit", "Click here" |
| At most one exclamation mark, only for success moments | Emoji in UI text |

| Situation | Example |
|---|---|
| Success | "Playlist saved." |
| Error | "Couldn't save the playlist. Check your connection and try again." |
| Empty | "No playlists yet. Create your first one." |
| Destructive confirm | "Delete this playlist? This can't be undone." |

**Bahasa Indonesia:** gunakan **"kamu"** (bukan "Anda" yang kaku, bukan "lo/gue" yang terlalu santai). Hindari slang dan singkatan tidak baku. Contoh: "Playlist tersimpan." · "Tidak bisa menyimpan playlist. Periksa koneksimu lalu coba lagi."

---

## 10. Accessibility summary

- Contrast per section 2.5. Text on any surface **MUST** reach 4.5:1 (large text 3:1), except disabled elements.
- All functionality is keyboard operable; focus order follows visual order; focus ring always visible.
- Touch targets ≥ 44px on coarse pointers.
- Respect `prefers-reduced-motion` (handled in `tokens.css`).
- Status uses icon + text, never color alone.
- Use semantic HTML (`button`, `nav`, `main`, `label`) before ARIA. Add ARIA only where section 6 says.

---

## 11. Agent workflow and anti-patterns

**When asked to build UI**
1. Map each needed element to a recipe in section 6. Use it as written.
2. If no recipe fits, **compose** from existing tokens and recipes, then list it under "Proposed additions" in your reply.
3. Decide small choices yourself (within tokens). **Ask the owner** before anything that changes a token, adds a color, size, or font, or contradicts a rule.
4. Never change a token silently. A token change updates `tokens.css`, `tokens.json`, `tailwind.config.js`, and this file together.

| Don't | Do instead |
|---|---|
| Raw hex or `rgb()` | A token |
| Gradient, shadow, glow | Surface step + 1px border |
| Several primary buttons | One Primary, the rest Secondary |
| Large accent-filled area | `surface-1` card + small `accent-soft` chip |
| `accent-primary` text on `surface-3` | `accent-soft-text` |
| Font weight 600/700 | 500 + color contrast |
| Card inside same-surface card | Step up one surface, or a divider |
| Icon-only button without label | Add `aria-label` (+ tooltip) |
| Spinner for a whole page | Skeleton |
| Auto-dismissing error toast | Persist until dismissed |

---

## 12. Pre-delivery checklist

- [ ] No raw color, size, radius, z-index, or duration values; all tokens.
- [ ] Accent ≤10% of visible area; one Primary button per area.
- [ ] Only the six font sizes and weights 400/500; system font stack only (no custom font).
- [ ] Only token utilities (section 14); no arbitrary values, no `style=""`.
- [ ] No gradient, shadow, or glow.
- [ ] Child surface not lower than its parent.
- [ ] `accent-primary` text not on `surface-3`.
- [ ] Text on accent fill uses `on-accent`.
- [ ] Every interactive element has hover, focus ring, and disabled state.
- [ ] Touch targets ≥44px on mobile; layout checked at 360px and 1280px.
- [ ] Icon-only controls have `aria-label`; decorative icons `aria-hidden`.
- [ ] Status not conveyed by color alone.
- [ ] Copy follows section 9.
- [ ] Reduced-motion respected.

---

## 13. Provenance and decision log

**Original (source HTML):** palette (except tertiary), 60/30/10, radii 8/10/12/full, font sizes 12–15, weights, base paddings/gaps, component dimensions for navbar, card, chip, primary button, icon button, search input, toggle, avatar.

**Derived / added:** overlay, semantic tints, `on-accent`, heading + display sizes, line-heights, space-24/32, slider thumb size, control height, touch target, motion, z-index, breakpoints, layout, focus ring, all interaction states, secondary/danger/text buttons, textarea, select, menu, progress, tooltip, list row, dialog, toast, alert, empty state, skeleton, slider, tabs, content and voice rules.

### Decisions

| # | Decision | Status |
|---|---|---|
| 1 | `text-tertiary` lightened `#7A869F` → `#8A95AB` to pass AA on all surfaces. | ✅ Applied |
| 2 | Toggle "off" kept exactly as the original (track `border-subtle`). Accessibility is carried by knob position, label color, and `role="switch"`. | ✅ Owner decision |
| 3 | System font stack, no custom font. (Plus Jakarta Sans was considered and dropped by the owner.) | ✅ Owner decision |
| 4 | UI copy tone: casual but not too informal; Indonesian uses "kamu". | 🔶 Proposed, please confirm |
| 5 | Light theme. | ⛔ Out of scope (dark-only) |
| 6 | Icons are copied SVGs in `<x-icon>`, to respect "no new packages". | ✅ Applied |
| 7 | Tailwind 3 theme is overridden, not extended, so only tokens exist as utilities. Progress and slider use native elements to avoid inline styles. | ✅ Applied |

💬 Item 4 adalah tebakan terarah dari permintaanmu soal font "casual tapi tidak terlalu informal". Tolong konfirmasi apakah gaya bahasanya sudah pas. Semua komponen baru (dialog, toast, dll.) mengikuti aturan yang sama dengan komponen aslimu, jadi tampilannya konsisten.

---

## 14. Implementation in ChordCue (Laravel 12, Tailwind 3)

The project uses Tailwind **3** (`@tailwind base;` in `app.css`). `tailwind.config.js` **overrides** (does not extend) the theme, so only token utilities exist.

### 14.1 Utility cheat sheet

| Token | Utility |
|---|---|
| `bg-base`, `surface-1/2/3`, `overlay` | `bg-base`, `bg-surface-1`, `bg-surface-2`, `bg-surface-3`, `bg-overlay` |
| `border-subtle` | `border-subtle` (width is always 1px: `border`) |
| accent primary / hover | `bg-accent`, `text-accent`, `border-accent`, `bg-accent-hover` |
| accent soft bg / text, on-accent | `bg-accent-soft-bg`, `text-accent-soft-text`, `text-accent-on` |
| text primary / secondary / tertiary / disabled | `text-ink`, `text-ink-secondary`, `text-ink-tertiary`, `text-ink-disabled` |
| semantic | `text-success`, `border-error`, `bg-error-tint` (same for `warning`) |
| font size | `text-caption`, `text-body`, `text-nav-title`, `text-title`, `text-heading`, `text-display` |
| font weight | `font-regular`, `font-medium` |
| radius | `rounded-sm`, `rounded-md`, `rounded-lg`, `rounded-full` |
| spacing (**the number is px**) | `p-16`, `gap-8`, `mt-12`, `px-14` (`4 6 8 10 12 14 16 20 24 32`) |
| fixed sizes | `h-navbar`, `h-control`, `h-icon-button w-icon-button`, `h-avatar w-avatar`, `w-toggle-w h-toggle-h`, `h-toggle-knob w-toggle-knob`, `h-progress`, `h-slider-thumb w-slider-thumb`, `min-h-touch` |
| icon sizes | `h-icon-nav w-icon-nav`, `h-icon-control w-icon-control`, `h-icon-button-glyph w-icon-button-glyph`, `h-icon-chip w-icon-chip` |
| max width | `max-w-dialog`, `max-w-toast`, `max-w-tooltip`, `max-w-content` |
| motion | `duration-fast/base/slow`, `ease-standard`, `ease-out` (`transition-colors` already uses `fast` + `standard`) |
| z-index | `z-sticky`, `z-dropdown`, `z-overlay`, `z-modal`, `z-toast` |
| breakpoints | `sm` 640, `md` 768, `lg` 1024, `xl` 1280 |

💬 Perhatikan: di proyek ini `p-4` berarti **4px**, bukan 16px seperti Tailwind biasa. Angkanya selalu piksel, sama dengan nama token `--space-N`.

### 14.2 `tailwind.config.js`

```js
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Sengaja "theme" langsung (bukan "extend"): palet dan skala bawaan Tailwind dihapus.
    theme: {
        colors: {
            transparent: 'transparent',
            current: 'currentColor',
            base: 'var(--color-bg-base)',
            'surface-1': 'var(--color-surface-1)',
            'surface-2': 'var(--color-surface-2)',
            'surface-3': 'var(--color-surface-3)',
            overlay: 'var(--color-overlay)',
            subtle: 'var(--color-border-subtle)',
            accent: {
                DEFAULT: 'var(--color-accent-primary)',
                hover: 'var(--color-accent-hover)',
                'soft-bg': 'var(--color-accent-soft-bg)',
                'soft-text': 'var(--color-accent-soft-text)',
                on: 'var(--color-on-accent)',
            },
            ink: {
                DEFAULT: 'var(--color-text-primary)',
                secondary: 'var(--color-text-secondary)',
                tertiary: 'var(--color-text-tertiary)',
                disabled: 'var(--color-text-disabled)',
            },
            success: { DEFAULT: 'var(--color-success)', tint: 'var(--color-success-tint)' },
            warning: { DEFAULT: 'var(--color-warning)', tint: 'var(--color-warning-tint)' },
            error: { DEFAULT: 'var(--color-error)', tint: 'var(--color-error-tint)' },
        },

        fontFamily: {
            sans: ['var(--font-sans)'],
            mono: ['var(--font-mono)'],
        },
        fontSize: {
            caption: ['var(--font-size-caption)', 'var(--line-height-base)'],
            body: ['var(--font-size-body)', 'var(--line-height-base)'],
            'nav-title': ['var(--font-size-nav-title)', 'var(--line-height-base)'],
            title: ['var(--font-size-title)', 'var(--line-height-base)'],
            heading: ['var(--font-size-heading)', 'var(--line-height-heading)'],
            display: ['var(--font-size-display)', 'var(--line-height-heading)'],
        },
        fontWeight: {
            regular: 'var(--font-weight-regular)',
            medium: 'var(--font-weight-medium)',
        },

        borderRadius: {
            none: '0',
            sm: 'var(--radius-sm)',
            md: 'var(--radius-md)',
            lg: 'var(--radius-lg)',
            full: 'var(--radius-full)',
        },
        borderWidth: { DEFAULT: 'var(--border-width)', 0: '0' },

        // Angka = piksel. Ukuran komponen ikut di sini agar bisa dipakai h-* dan w-*.
        spacing: {
            0: '0',
            4: 'var(--space-4)',
            6: 'var(--space-6)',
            8: 'var(--space-8)',
            10: 'var(--space-10)',
            12: 'var(--space-12)',
            14: 'var(--space-14)',
            16: 'var(--space-16)',
            20: 'var(--space-20)',
            24: 'var(--space-24)',
            32: 'var(--space-32)',
            navbar: 'var(--size-navbar-height)',
            control: 'var(--size-control-height)',
            'icon-button': 'var(--size-icon-button)',
            avatar: 'var(--size-avatar)',
            'toggle-w': 'var(--size-toggle-width)',
            'toggle-h': 'var(--size-toggle-height)',
            'toggle-knob': 'var(--size-toggle-knob)',
            progress: 'var(--size-progress-height)',
            'slider-thumb': 'var(--size-slider-thumb)',
            'icon-chip': 'var(--size-icon-chip)',
            'icon-control': 'var(--size-icon-control)',
            'icon-button-glyph': 'var(--size-icon-button-glyph)',
            'icon-nav': 'var(--size-icon-nav)',
        },
        minHeight: { 0: '0', full: '100%', screen: '100vh', touch: 'var(--size-touch-target-min)' },
        minWidth: { 0: '0', full: '100%', touch: 'var(--size-touch-target-min)' },
        maxWidth: {
            none: 'none',
            full: '100%',
            dialog: 'var(--size-dialog-width)',
            toast: 'var(--size-toast-width)',
            tooltip: 'var(--size-tooltip-max-width)',
            content: 'var(--layout-content-max)',
        },

        lineHeight: { base: 'var(--line-height-base)', heading: 'var(--line-height-heading)' },
        transitionDuration: {
            DEFAULT: 'var(--motion-duration-fast)',
            fast: 'var(--motion-duration-fast)',
            base: 'var(--motion-duration-base)',
            slow: 'var(--motion-duration-slow)',
        },
        transitionTimingFunction: {
            DEFAULT: 'var(--motion-ease-standard)',
            standard: 'var(--motion-ease-standard)',
            out: 'var(--motion-ease-out)',
        },
        zIndex: {
            base: 'var(--z-base)',
            sticky: 'var(--z-sticky)',
            dropdown: 'var(--z-dropdown)',
            overlay: 'var(--z-overlay)',
            modal: 'var(--z-modal)',
            toast: 'var(--z-toast)',
        },
        // Angka ditulis langsung: variabel CSS tidak bisa dipakai di @media.
        screens: { sm: '640px', md: '768px', lg: '1024px', xl: '1280px' },

        // Tanpa bayangan dan tanpa gradien (aturan 0.8).
        boxShadow: { none: 'none' },
        dropShadow: {},
        backgroundImage: { none: 'none' },
    },

    // strategy 'class': gaya bawaan plugin forms tidak menimpa input kita.
    // Jangan pakai class form-input / form-checkbox dst.
    plugins: [forms({ strategy: 'class' })],
};
```

Notes for agents:
- Token colors are CSS variables, so opacity modifiers (`bg-accent/50`) do not work. Use another token.
- Breeze components use `indigo-*`, `gray-*`, `dark:*`. After this config those classes generate nothing, so each component **MUST** be restyled with the recipes in section 6 (classes only, never component logic).

### 14.3 `resources/css/app.css`

```css
@import './tokens.css';

@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
    body {
        @apply bg-base font-sans text-body font-regular text-ink;
    }

    /* Ring fokus keyboard untuk semua elemen interaktif (DESIGN_SYSTEM section 4). */
    :focus-visible {
        outline: var(--focus-ring-width) solid var(--color-accent-hover);
        outline-offset: var(--focus-ring-offset);
    }
}

@layer components {
    /* Progress (DESIGN_SYSTEM 6.9) */
    .ui-progress {
        @apply block h-progress w-full appearance-none overflow-hidden rounded-full border-0 bg-subtle;
    }
    .ui-progress::-webkit-progress-bar {
        @apply bg-subtle;
    }
    .ui-progress::-webkit-progress-value {
        @apply bg-accent;
    }
    .ui-progress::-moz-progress-bar {
        @apply bg-accent;
    }

    /* Slider (DESIGN_SYSTEM 6.17): progress di bawah, input range transparan di atas. */
    .ui-slider {
        @apply grid w-full items-center;
    }
    .ui-slider > * {
        grid-area: 1 / 1;
    }
    .ui-slider .ui-progress {
        @apply pointer-events-none;
    }
    @media (pointer: coarse) {
        .ui-slider {
            @apply min-h-touch;
        }
    }
    .ui-slider input[type='range'] {
        @apply relative z-base m-0 h-slider-thumb w-full cursor-pointer appearance-none bg-transparent;
    }
    .ui-slider input[type='range']:focus-visible {
        outline: none;
    }
    .ui-slider input[type='range']::-webkit-slider-runnable-track {
        @apply h-slider-thumb bg-transparent;
    }
    .ui-slider input[type='range']::-moz-range-track {
        @apply h-slider-thumb bg-transparent;
    }
    .ui-slider input[type='range']::-webkit-slider-thumb {
        @apply h-slider-thumb w-slider-thumb appearance-none rounded-full border-0 bg-accent;
    }
    .ui-slider input[type='range']::-moz-range-thumb {
        @apply h-slider-thumb w-slider-thumb rounded-full border-0 bg-accent;
    }
    .ui-slider input[type='range']:hover::-webkit-slider-thumb {
        @apply bg-accent-hover;
    }
    .ui-slider input[type='range']:hover::-moz-range-thumb {
        @apply bg-accent-hover;
    }
    .ui-slider input[type='range']:focus-visible::-webkit-slider-thumb {
        outline: var(--focus-ring-width) solid var(--color-accent-hover);
        outline-offset: var(--focus-ring-offset);
    }
    .ui-slider input[type='range']:focus-visible::-moz-range-thumb {
        outline: var(--focus-ring-width) solid var(--color-accent-hover);
        outline-offset: var(--focus-ring-offset);
    }
}
```

Usage example (seek bar, Alpine only sets attributes, never styles):

```blade
<div class="ui-slider">
    <progress class="ui-progress" max="{{ $durationSeconds }}" :value="currentSeconds" aria-label="Playback progress"></progress>
    <input type="range" min="0" max="{{ $durationSeconds }}" step="1" :value="currentSeconds"
           @input="seekTo($event.target.value)" aria-label="Seek" :aria-valuetext="timeLabel">
</div>
```

### 14.4 `resources/views/components/icon.blade.php`

```blade
@props(['name'])

@php
    // Salin path SVG dari Tabler Icons (lisensi MIT, gaya outline) satu per satu saat dibutuhkan.
    $iconPaths = [
        'play' => '<path d="M7 4v16l13 -8z" />',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-icon-control w-icon-control']) }}
     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $iconPaths[$name] !!}
</svg>
```

Usage: `<x-icon name="play" />`, and for another size `<x-icon name="play" class="h-icon-nav w-icon-nav text-accent" />`. Add a new icon by adding one line to `$iconPaths`.

💬 Bagian 14 menerjemahkan seluruh aturan menjadi kode siap pakai untuk proyekmu. Aturan baku tetap di bagian 0-13. Slider memakai dua elemen bawaan browser (progress di bawah, range transparan di atas) supaya tidak perlu `style="width:..%"` maupun gradien, dua hal yang dilarang.

