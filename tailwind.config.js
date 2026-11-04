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
