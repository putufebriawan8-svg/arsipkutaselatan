  const debounce = (fn, delay) => {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), delay);
    };
  };

  // ---------- Theme Management ----------
  const THEME_KEY = 'siparsip_theme';
  const initTheme = () => {
    const saved = localStorage.getItem(THEME_KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = saved || (prefersDark ? 'dark' : 'light');
    document.documentElement.classList.toggle('dark', theme === 'dark');
    qs('#settings-dark-mode').checked = theme === 'dark';
  };
  const toggleTheme = (enabled) => {
    document.documentElement.classList.toggle('dark', enabled);
    localStorage.setItem(THEME_KEY, enabled ? 'dark' : 'light');
  };

  // ---------- Notification (Toast) ----------
  const toastContainer = qs('#toast-container');
  const showToast = (msg, type = 'info', timeout = 5000) => {
    const toast = document.createElement('div');
    toast.className = `p-md rounded-lg shadow-xl text-sm ${type === 'error' ? 'bg-error text-on-error' : 'bg-primary text-on-primary'} animate-fade-in`;
    toast.textContent = msg;
    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.remove();
    }, timeout);
  };

  // ---------- Routing ----------
  const routes = {
    '/dashboard': renderDashboard,
    '/explorer': renderExplorer,
    '/tasks': renderTasks,
    '/governance': renderGovernance,
    '/security': renderSecurity,
    '/help': renderHelp,
  };

  function router() {
    const hash = location.hash || '#/dashboard';
    const path = hash.slice(1); // remove '#'
    const viewFn = routes[path] || renderDashboard;
    viewFn();
    // update active nav link
    qsa('aside nav a').forEach((a) => {
      a.classList.toggle('bg-surface-variant', a.getAttribute('href') === hash);
    });
  }

  // ---------- View Renderers ----------
  const viewContainer = qs('#view-container');

  function clearView() {
    viewContainer.innerHTML = '';
  }

  function renderDashboard() {
    clearView();
    const div = document.createElement('div');
    div.innerHTML = `<h1 class="font-headline-lg text-headline-lg mb-4">Dashboard</h1>
      <p class="text-body-md">Selamat datang di SIPARSIP. Ringkasan aktivitas akan ditampilkan di sini.</p>`;
    viewContainer.appendChild(div);
    const event = new Event('siparsip_render_dashboard');
    window.dispatchEvent(event);
  }

  function renderExplorer() {
    clearView();
    const div = document.createElement('div');
    div.innerHTML = `<h1 class="font-headline-lg text-headline-lg mb-4">File Explorer</h1>
      <p class="text-body-md">Daftar berkas akan muncul di sini.</p>`;
    viewContainer.appendChild(div);
    const ev = new Event('siparsip_load_files');
    window.dispatchEvent(ev);
  }

  function renderTasks() {
    clearView();
    const div = document.createElement('div');
    div.innerHTML = `<h1 class="font-headline-lg text-headline-lg mb-4">Manajemen Tugas</h1>
      <p class="text-body-md">Daftar tugas akan ditampilkan di sini.</p>`;
    viewContainer.appendChild(div);
    const ev = new Event('siparsip_load_tasks');
    window.dispatchEvent(ev);
  }

  function renderGovernance() {
    clearView();
    const div = document.createElement('div');
    div.innerHTML = `<h1 class="font-headline-lg text-headline-lg mb-4">User Governance</h1>
      <p class="text-body-md">Kelola pengguna dan klaster.</p>`;
    viewContainer.appendChild(div);
    const ev = new Event('siparsip_load_users');
    window.dispatchEvent(ev);
  }

  function renderSecurity() {
    clearView();
    viewContainer.innerHTML = '<h1 class="font-headline-lg text-headline-lg mb-4">Security Log</h1><p class="text-body-md">Log keamanan akan ditampilkan di sini.</p>';
  }

  function renderHelp() {
    clearView();
    viewContainer.innerHTML = '<h1 class="font-headline-lg text-headline-lg mb-4">Help</h1><p class="text-body-md">Bantuan dan dokumentasi.</p>';
  }

  // ---------- Event Bindings ----------
  const bindEvents = () => {
    window.addEventListener('hashchange', router);
    router(); // initial load

    qs('#settings-dark-mode')?.addEventListener('change', (e) => {
      toggleTheme(e.target.checked);
    });

    qs('#settings-trigger')?.addEventListener('click', () => {
      qs('#settings-modal').classList.remove('hidden');
    });
    qs('#close-settings-modal')?.addEventListener('click', () => {
      qs('#settings-modal').classList.add('hidden');
    });

    qs('#global-fab')?.addEventListener('click', () => {
      qs('#upload-modal').classList.remove('hidden');
    });
    qs('#close-upload-modal')?.addEventListener('click', () => {
      qs('#upload-modal').classList.add('hidden');
    });

    qs('#logout-btn')?.addEventListener('click', () => {
      localStorage.removeItem('siparsip_user');
      location.reload();
    });

    // Email login handler
    qs('#email-login-btn')?.addEventListener('click', () => {
      const emailInput = qs('#email-login-input');
      const email = emailInput ? emailInput.value.trim() : '';
      if (!email) {
        showToast('Masukkan email', 'error');
        return;
      }
      const users = SIPARSIP_DB.getUsers();
      const user = users.find(u => u.email.toLowerCase() === email.toLowerCase());
      if (user) {
        localStorage.setItem('siparsip_user', JSON.stringify(user));
        // Update UI
        qs('#sidebar-user-name').textContent = user.name;
        qs('#sidebar-user-role').textContent = user.role;
        qs('#admin-shell').classList.remove('hidden');
        qs('#login-view').classList.add('hidden');
        showToast('Berhasil masuk dengan email', 'info');
      } else {
        showToast('Email tidak ditemukan', 'error');
      }
    });

    const searchInput = qs('#global-search-input');
    if (searchInput) {
      searchInput.addEventListener('input', debounce((e) => {
        const query = e.target.value.trim();
        if (!query) {
          qs('#search-results-panel').classList.add('hidden');
          return;
        }
        const resultsPanel = qs('#search-results-panel');
        resultsPanel.innerHTML = `<p class="text-body-sm p-2">Mencari "${query}" ... (fungsionalitas pencarian belum diimplementasikan)</p>`;
        resultsPanel.classList.remove('hidden');
      }, 300));
    }

    qs('#notification-trigger')?.addEventListener('click', () => {
      showToast('Notifikasi belum dihubungkan ke backend.', 'info');
    });
  };

  const init = () => {
    initTheme();
    bindEvents();
    // Session handling
    const storedUser = localStorage.getItem('siparsip_user');
    if (storedUser) {
      const user = JSON.parse(storedUser);
      qs('#sidebar-user-name').textContent = user.name;
      qs('#sidebar-user-role').textContent = user.role;
      qs('#admin-shell').classList.remove('hidden');
      qs('#login-view').classList.add('hidden');
    } else {
      qs('#login-view').classList.remove('hidden');
      qs('#admin-shell').classList.add('hidden');
    }
    window.siparsip = { showToast, router };
  };

  document.addEventListener('DOMContentLoaded', init);
})();
