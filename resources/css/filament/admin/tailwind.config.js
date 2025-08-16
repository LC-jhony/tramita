import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                white: '#F3F4F6',
                platinum: '#E8E9EB',
            },
            maxWidth: {
                '8xl': '88rem',
            },
            transitionTimingFunction: {
                'ease-smooth': 'cubic-bezier(0.08, 0.52, 0.52, 1)',
            }
        }
    }
}