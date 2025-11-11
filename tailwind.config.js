/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {},
    },
    safelist: [
        "text-blue-700",
        "text-green-700",
        "text-red-700",
        "text-indigo-700",
        "bg-blue-100",
        "bg-green-100",
        "bg-red-100",
        "bg-indigo-100",
    ],
    plugins: [],
};
