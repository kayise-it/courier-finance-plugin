/** @type {import('tailwindcss').Config} */
module.exports = {
  important: true, // Add this 
  content: [
    './**/*.php', // All PHP files
    './includes/**/*.php', // Nested PHP files
    './assets/**/*.js' // JS files, if using Tailwind in JS
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}