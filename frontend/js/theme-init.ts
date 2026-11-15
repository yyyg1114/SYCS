(function () {
    try {
        const savedTheme = localStorage.getItem("sycs_theme");
        const prefersDarkMode = window.matchMedia("(prefers-color-scheme: dark)").matches;
        const requestedTheme = savedTheme || (prefersDarkMode ? "dark" : "light");
        const theme = ["light", "night"].includes(requestedTheme) ? requestedTheme : "dark";
        const themes = ["light-theme", "dark-theme", "night-theme"];
        document.documentElement.classList.remove(...themes);
        document.documentElement.classList.add(`${theme}-theme`);
    } catch (error) {
        console.error("Theme initialization failed", error);
    }
})();
