module.exports = {
  content: [
    './**/*.php',
    './assets/js/**/*.js',
    './js/**/*.js'
  ],
  safelist: [
    // Layout
    /^container$/,
    /^grid$/,
    /^grid-cols-/,
    /^md:grid-cols-/,
    /^flex$/,
    /^flex-col$/,
    /^items-/,
    /^justify-/,
    /^w-/,
    /^h-/,
    // Spacing
    /^p[trblxy]?-/,
    /^m[trblxy]?-/,
    /^space-[xy]-/,
    // Explicit padding classes for icon inputs
    'pl-12',
    'pr-4',
    'pl-10',
    // Typography & colors
    /^text-/,
    /^font-/,
    /^leading-/,
    /^tracking-/,
    /^bg-/,
    /^from-/,
    /^to-/,
    /^via-/,
    // Borders & radius & shadows
    /^border/,
    /^rounded/,
    /^shadow/,
    // Effects & transitions
    /^transition/,
    /^duration-/,
    /^ease-/,
    /^hover:/,
    /^active:/,
    /^focus:/,
    /^focus:ring/,
    // States
    /^disabled:/,
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
