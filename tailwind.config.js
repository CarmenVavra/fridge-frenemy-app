/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],
    theme: {
        // Modernist design system: radius 0 everywhere.
        borderRadius: {
            none: '0',
            DEFAULT: '0',
        },
        extend: {
            colors: {
                bg: '#f3f2f2',
                surface: '#eae9e9',
                ink: '#201e1d',
                accent: {
                    DEFAULT: '#ec3013',
                    100: '#fff2ef',
                    200: '#ffe0d9',
                    300: '#ffc4b8',
                    400: '#ff9783',
                    500: '#ff563c',
                    600: '#dd2b0f',
                    700: '#ae1800',
                    800: '#7c1405',
                },
                neutral: {
                    100: '#f8f4f4',
                    200: '#eae7e7',
                    300: '#d7d3d3',
                    400: '#bab6b6',
                    500: '#9b9797',
                    600: '#7d7979',
                    700: '#605d5d',
                    800: '#444141',
                    900: '#2d2b2b',
                },
            },
            fontFamily: {
                sans: ['Archivo', 'system-ui', 'sans-serif'],
            },
            borderWidth: {
                3: '3px',
            },
            boxShadow: {
                lg: '0 12px 32px rgba(45, 43, 43, .22)',
            },
            keyframes: {
                'ff-pulse': {
                    '0%, 100%': { backgroundColor: '#ec3013' },
                    '50%': { backgroundColor: '#dd2b0f' },
                },
                'ff-scan': {
                    from: { transform: 'translateY(0)' },
                    to: { transform: 'translateY(176px)' },
                },
                'ff-toast': {
                    from: { transform: 'translateY(16px)', opacity: '0' },
                    to: { transform: 'none', opacity: '1' },
                },
                'ff-push': {
                    from: { transform: 'translateY(-130%)' },
                    to: { transform: 'none' },
                },
                'ff-stamp': {
                    from: { transform: 'rotate(-12deg) scale(1.8)', opacity: '0' },
                    to: { transform: 'rotate(-12deg) scale(1)', opacity: '1' },
                },
                'ff-shake': {
                    '0%, 100%': { transform: 'translateX(0)' },
                    '25%': { transform: 'translateX(-2px)' },
                    '75%': { transform: 'translateX(2px)' },
                },
            },
            animation: {
                'ff-pulse': 'ff-pulse 1.2s ease-in-out infinite',
                'ff-scan': 'ff-scan .7s ease-in-out infinite alternate',
                'ff-toast': 'ff-toast .25s ease-out',
                'ff-push': 'ff-push .4s cubic-bezier(.2,.9,.3,1)',
                'ff-stamp': 'ff-stamp .35s cubic-bezier(.2,.9,.3,1) both',
                'ff-shake': 'ff-shake .5s infinite',
                'ff-shake-fast': 'ff-shake .4s infinite',
            },
        },
    },
    plugins: [],
};
