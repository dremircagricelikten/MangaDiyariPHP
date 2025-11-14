(function () {
  const storageKey = 'md-theme-preference';

  function getStoredPreference() {
    try {
      const value = localStorage.getItem(storageKey);
      if (value === 'light' || value === 'dark') {
        return value;
      }
    } catch (error) {
      console.warn('Tema tercihi okunamadı:', error);
    }
    return null;
  }

  function setStoredPreference(value) {
    try {
      localStorage.setItem(storageKey, value);
    } catch (error) {
      console.warn('Tema tercihi kaydedilemedi:', error);
    }
  }

  function prefersLight() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
  }

  function applyTheme(theme) {
    const nextTheme = theme === 'light' ? 'light' : 'dark';
    const body = document.body;
    body.setAttribute('data-theme', nextTheme);

    const icon = nextTheme === 'dark' ? 'bi-moon-stars' : 'bi-sun-fill';
    const label = nextTheme === 'dark' ? 'Açık tema' : 'Koyu tema';

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
      button.setAttribute('aria-label', `Temayı değiştir (${label})`);
      button.setAttribute('title', `Temayı değiştir (${label})`);
      const iconElement = button.querySelector('i');
      if (iconElement) {
        iconElement.className = `bi ${icon}`;
      }
    });
  }

  function detectInitialTheme() {
    const stored = getStoredPreference();
    if (stored) {
      return stored;
    }
    return prefersLight() ? 'light' : 'dark';
  }

  function toggleTheme() {
    const current = document.body.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    const next = current === 'light' ? 'dark' : 'light';
    setStoredPreference(next);
    applyTheme(next);
  }

  document.addEventListener('DOMContentLoaded', () => {
    applyTheme(detectInitialTheme());

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
      button.addEventListener('click', () => {
        toggleTheme();
      });
    });

    if (window.matchMedia) {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: light)');
      const updateFromPreference = (event) => {
        if (getStoredPreference() === null) {
          applyTheme(event.matches ? 'light' : 'dark');
        }
      };

      if (typeof mediaQuery.addEventListener === 'function') {
        mediaQuery.addEventListener('change', updateFromPreference);
      } else if (typeof mediaQuery.addListener === 'function') {
        mediaQuery.addListener(updateFromPreference);
      }
    }
  });
})();
