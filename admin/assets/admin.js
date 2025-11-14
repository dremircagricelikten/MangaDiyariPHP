$(function () {
  function handleError(xhr, messageContainer) {
    if (xhr.status === 403) {
      window.location.href = 'login.php';
      return;
    }
    const error = xhr.responseJSON?.error || 'İşlem başarısız';
    messageContainer.html(`<div class="alert alert-danger">${error}</div>`);
  }

  function refreshMangaOptions() {
    $.getJSON('../public/api.php', { action: 'list' })
      .done(({ data }) => {
        const select = $('#manga-select');
        select.empty();
        if (!data.length) {
          select.append('<option value="">Önce seri ekleyin</option>');
          select.prop('disabled', true);
        } else {
          select.prop('disabled', false);
          data.forEach((manga) => {
            select.append(`<option value="${manga.id}">${manga.title}</option>`);
          });
        }
      });
  }

  function applyDashboardStats(stats = {}) {
    Object.entries(stats).forEach(([key, value]) => {
      const target = $(`[data-dashboard-stat="${key}"]`);
      if (target.length) {
        target.text(new Intl.NumberFormat('tr-TR').format(Number(value) || 0));
      }
    });
  }

  function refreshDashboardStats() {
    $.getJSON('api.php', { action: 'dashboard-stats' })
      .done(({ data }) => {
        applyDashboardStats(data || {});
      });
  }

  $('#manga-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#manga-form-message');
    $.post('api.php?action=create-manga', form.serialize())
      .done((response) => {
        message.html(`<div class="alert alert-success">${response.message}</div>`);
        form.trigger('reset');
        refreshMangaOptions();
        refreshDashboardStats();
      })
      .fail((xhr) => handleError(xhr, message));
  });

  $('#chapter-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#chapter-form-message');
    message.empty();
    const formData = new FormData(this);
    $.ajax({
      url: 'api.php?action=create-chapter',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
    })
      .done((response) => {
        message.html(`<div class="alert alert-success">${response.message}</div>`);
        form[0].reset();
        refreshDashboardStats();
      })
      .fail((xhr) => handleError(xhr, message));
  });

  refreshMangaOptions();
  if (window.dashboardStats) {
    applyDashboardStats(window.dashboardStats);
  }

  function loadThemeSettings() {
    $.getJSON('api.php', { action: 'get-settings' })
      .done(({ data }) => {
        const defaults = {
          primary_color: '#5f2c82',
          accent_color: '#49a09d',
          background_color: '#05060c',
          gradient_start: '#5f2c82',
          gradient_end: '#49a09d',
        };
        const settings = Object.assign(defaults, data || {});
        Object.entries(settings).forEach(([key, value]) => {
          const field = $(`#${key}`);
          if (field.length) {
            field.val(value);
          }
        });
      });
  }

  $('#theme-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#theme-form-message');
    $.post('api.php?action=update-settings', form.serialize())
      .done((response) => {
        message.html(`<div class="alert alert-success">${response.message}</div>`);
      })
      .fail((xhr) => handleError(xhr, message));
  });

  function renderWidgetForm(widget) {
    const config = widget.config || {};
    const sortOrder = widget.sort_order ?? 0;
    const status = config.status || '';
    const limit = config.limit || 6;
    const sort = config.sort || 'random';

    let configFields = '';

    if (widget.type === 'popular_slider') {
      configFields = `
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Gösterim Limiti</label>
            <input type="number" min="1" max="12" step="1" class="form-control" name="config[limit]" value="${limit}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Sıralama</label>
            <select class="form-select" name="config[sort]">
              <option value="random" ${sort === 'random' ? 'selected' : ''}>Rastgele</option>
              <option value="newest" ${sort === 'newest' ? 'selected' : ''}>En Yeni</option>
              <option value="updated" ${sort === 'updated' ? 'selected' : ''}>Son Güncellenen</option>
              <option value="alphabetical" ${sort === 'alphabetical' ? 'selected' : ''}>Alfabetik</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Durum Filtresi</label>
            <select class="form-select" name="config[status]">
              <option value="" ${status === '' ? 'selected' : ''}>Tümü</option>
              <option value="ongoing" ${status === 'ongoing' ? 'selected' : ''}>Devam Ediyor</option>
              <option value="completed" ${status === 'completed' ? 'selected' : ''}>Tamamlandı</option>
              <option value="hiatus" ${status === 'hiatus' ? 'selected' : ''}>Ara Verildi</option>
            </select>
          </div>
        </div>`;
    } else if (widget.type === 'latest_updates') {
      configFields = `
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Gösterim Limiti</label>
            <input type="number" min="1" max="20" step="1" class="form-control" name="config[limit]" value="${limit}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Sıralama</label>
            <select class="form-select" name="config[sort]">
              <option value="newest" ${sort === 'newest' ? 'selected' : ''}>En Yeni Bölümler</option>
              <option value="oldest" ${sort === 'oldest' ? 'selected' : ''}>En Eski Bölümler</option>
              <option value="chapter_desc" ${sort === 'chapter_desc' ? 'selected' : ''}>Bölüm No (Azalan)</option>
              <option value="chapter_asc" ${sort === 'chapter_asc' ? 'selected' : ''}>Bölüm No (Artan)</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Durum Filtresi</label>
            <select class="form-select" name="config[status]">
              <option value="" ${status === '' ? 'selected' : ''}>Tümü</option>
              <option value="ongoing" ${status === 'ongoing' ? 'selected' : ''}>Devam Ediyor</option>
              <option value="completed" ${status === 'completed' ? 'selected' : ''}>Tamamlandı</option>
              <option value="hiatus" ${status === 'hiatus' ? 'selected' : ''}>Ara Verildi</option>
            </select>
          </div>
        </div>`;
    }

    return `
      <form class="widget-form bg-dark bg-opacity-25 border rounded-4 p-4" data-widget-id="${widget.id}">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <h3 class="h5 mb-1">${widget.title}</h3>
            <span class="badge bg-light text-dark">${widget.type}</span>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="enabled_${widget.id}" name="enabled" ${widget.enabled ? 'checked' : ''}>
            <label class="form-check-label" for="enabled_${widget.id}">Etkin</label>
          </div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Başlık</label>
            <input type="text" class="form-control" name="title" value="${widget.title}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Sıra</label>
            <input type="number" class="form-control" name="sort_order" value="${sortOrder}">
          </div>
        </div>
        ${configFields}
        <div class="mt-3 d-flex justify-content-between align-items-center">
          <button type="submit" class="btn btn-outline-light btn-sm">Widgeti Kaydet</button>
          <div class="widget-message small text-info" aria-live="polite"></div>
        </div>
      </form>`;
  }

  function loadWidgets() {
    $.getJSON('api.php', { action: 'list-widgets' })
      .done(({ data }) => {
        const container = $('#widget-list');
        container.empty();
        if (!data.length) {
          container.append('<div class="alert alert-dark">Kayıtlı widget bulunmuyor.</div>');
          return;
        }
        data.forEach((widget) => {
          container.append(renderWidgetForm(widget));
        });
      });
  }

  $(document).on('submit', '.widget-form', function (event) {
    event.preventDefault();
    const form = $(this);
    const widgetId = form.data('widget-id');
    const message = form.find('.widget-message');

    const config = {};
    form.find('select[name^="config"], input[name^="config"]').each(function () {
      const field = $(this);
      const name = field.attr('name').match(/config\[(.+)]/);
      if (name && name[1]) {
        config[name[1]] = field.val();
      }
    });

    const payload = {
      id: widgetId,
      title: form.find('input[name="title"]').val(),
      sort_order: form.find('input[name="sort_order"]').val(),
      enabled: form.find('input[name="enabled"]').is(':checked') ? 1 : 0,
      config: JSON.stringify(config),
    };

    $.post('api.php?action=update-widget', payload)
      .done((response) => {
        message.removeClass('text-danger').addClass('text-success').text(response.message);
      })
      .fail((xhr) => {
        message.removeClass('text-success').addClass('text-danger');
        if (xhr.status === 403) {
          window.location.href = 'login.php';
          return;
        }
        const error = xhr.responseJSON?.error || 'Widget kaydedilemedi';
        message.text(error);
      });
  });

  loadThemeSettings();
  loadWidgets();

  function renderUserRow(user) {
    const initial = (user.username || '?').charAt(0).toUpperCase();
    const avatar = user.avatar_url
      ? `<img src="${user.avatar_url}" alt="${user.username}" class="rounded-circle me-2" width="36" height="36">`
      : `<div class="avatar-placeholder rounded-circle me-2">${initial}</div>`;
    const active = Number(user.is_active) === 1;
    return `
      <tr data-user-id="${user.id}">
        <td>
          <div class="d-flex align-items-center">
            ${avatar}
            <div>
              <div class="fw-semibold">${user.username}</div>
              <div class="small text-muted">${user.email}</div>
            </div>
          </div>
        </td>
        <td>
          <select class="form-select form-select-sm user-role">
            <option value="member" ${user.role === 'member' ? 'selected' : ''}>Üye</option>
            <option value="editor" ${user.role === 'editor' ? 'selected' : ''}>Editör</option>
            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Yönetici</option>
          </select>
        </td>
        <td class="text-center">
          <div class="form-check form-switch d-flex justify-content-center">
            <input class="form-check-input user-active" type="checkbox" ${active ? 'checked' : ''}>
          </div>
        </td>
        <td>
          <div class="input-group input-group-sm">
            <input type="password" class="form-control user-password" placeholder="Yeni parola">
            <button class="btn btn-outline-light user-save" type="button">Kaydet</button>
          </div>
          <div class="user-message small mt-1 text-muted"></div>
        </td>
      </tr>
    `;
  }

  function loadUsers() {
    const tableBody = $('#user-table tbody');
    tableBody.html('<tr><td colspan="4" class="text-center text-muted">Yükleniyor...</td></tr>');
    $.getJSON('api.php', { action: 'list-users' })
      .done(({ data }) => {
        tableBody.empty();
        if (!data.length) {
          tableBody.html('<tr><td colspan="4" class="text-center text-muted">Henüz üye bulunmuyor.</td></tr>');
          return;
        }
        data.forEach((user) => {
          tableBody.append(renderUserRow(user));
        });
      })
      .fail(() => {
        tableBody.html('<tr><td colspan="4" class="text-center text-danger">Üyeler yüklenemedi.</td></tr>');
      });
  }

  $('#create-user-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#create-user-message');
    message.empty();

    const payload = form.serializeArray();
    if (!form.find('[name="is_active"]').is(':checked')) {
      payload.push({ name: 'is_active', value: '0' });
    }

    $.post('api.php?action=create-user', $.param(payload))
      .done((response) => {
        message.html(`<div class="alert alert-success">${response.message}</div>`);
        form.trigger('reset');
        loadUsers();
        refreshDashboardStats();
      })
      .fail((xhr) => handleError(xhr, message));
  });

  $(document).on('click', '.user-save', function () {
    const row = $(this).closest('tr');
    const userId = row.data('user-id');
    const role = row.find('.user-role').val();
    const isActive = row.find('.user-active').is(':checked') ? 1 : 0;
    const password = row.find('.user-password').val();
    const message = row.find('.user-message');

    message.removeClass('text-success text-danger').text('Kaydediliyor...');

    $.post('api.php?action=update-user', {
      id: userId,
      role,
      is_active: isActive,
      password,
    })
      .done((response) => {
        message.addClass('text-success').text(response.message);
        row.find('.user-password').val('');
        refreshDashboardStats();
      })
      .fail((xhr) => {
        if (xhr.status === 403) {
          window.location.href = 'login.php';
          return;
        }
        const error = xhr.responseJSON?.error || 'Güncelleme başarısız';
        message.addClass('text-danger').text(error);
      });
  });

  function loadFtpSettings() {
    const form = $('#ftp-form');
    $.getJSON('api.php', { action: 'get-ftp-settings' })
      .done(({ data }) => {
        Object.entries(data || {}).forEach(([key, value]) => {
          const field = form.find(`[name="${key}"]`);
          if (!field.length) {
            return;
          }
          if (field.attr('type') === 'checkbox') {
            field.prop('checked', value === '1' || value === 1);
          } else {
            field.val(value);
          }
        });
      });
  }

  $('#ftp-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#ftp-form-message');
    message.removeClass('text-danger text-success').text('Kaydediliyor...');
    const payload = form.serializeArray();
    const passiveField = form.find('[name="ftp_passive"]');
    if (!passiveField.is(':checked')) {
      payload.push({ name: 'ftp_passive', value: '0' });
    }
    $.post('api.php?action=update-ftp-settings', $.param(payload))
      .done((response) => {
        message.removeClass('text-danger').addClass('text-success').text(response.message);
      })
      .fail((xhr) => {
        if (xhr.status === 403) {
          window.location.href = 'login.php';
          return;
        }
        const error = xhr.responseJSON?.error || 'Kaydetme başarısız';
        message.removeClass('text-success').addClass('text-danger').text(error);
      });
  });

  function loadBranding() {
    const form = $('#branding-form');
    $.getJSON('api.php', { action: 'get-branding' })
      .done(({ data }) => {
        if (!data) return;
        form.find('[name="site_name"]').val(data.site_name || '');
        form.find('[name="site_tagline"]').val(data.site_tagline || '');
        if (data.site_logo) {
          $('#branding-preview').html(`<img src="../public/${data.site_logo}" alt="Logo" class="img-fluid rounded shadow-sm" style="max-height: 80px;">`);
        }
      });
  }

  $('#branding-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#branding-form-message');
    const formData = new FormData(this);
    $.ajax({
      url: 'api.php?action=update-branding',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
    })
      .done((response) => {
        message.html(`<div class="alert alert-success">${response.message}</div>`);
        if (response.data?.site_logo) {
          $('#branding-preview').html(`<img src="../public/${response.data.site_logo}" alt="Logo" class="img-fluid rounded shadow-sm" style="max-height: 80px;">`);
        }
      })
      .fail((xhr) => handleError(xhr, message));
  });

  function loadAdSettings() {
    const form = $('#ad-form');
    $.getJSON('api.php', { action: 'get-ad-settings' })
      .done(({ data }) => {
        if (!data) return;
        form.find('[name="ad_header"]').val(data.ad_header || '');
        form.find('[name="ad_sidebar"]').val(data.ad_sidebar || '');
        form.find('[name="ad_footer"]').val(data.ad_footer || '');
      });
  }

  $('#ad-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#ad-form-message');
    $.post('api.php?action=update-ad-settings', form.serialize())
      .done((response) => {
        message.html(`<div class="alert alert-success">${response.message}</div>`);
      })
      .fail((xhr) => handleError(xhr, message));
  });

  function loadAnalyticsSettings() {
    const form = $('#analytics-form');
    $.getJSON('api.php', { action: 'get-analytics' })
      .done(({ data }) => {
        if (!data) return;
        form.find('[name="analytics_google"]').val(data.analytics_google || '');
        form.find('[name="analytics_search_console"]').val(data.analytics_search_console || '');
      });
  }

  $('#analytics-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#analytics-form-message');
    $.post('api.php?action=update-analytics', form.serialize())
      .done((response) => {
        message.html(`<div class="alert alert-success">${response.message}</div>`);
      })
      .fail((xhr) => handleError(xhr, message));
  });

  let menus = [];
  let activeMenuId = null;

  function renderMenuList() {
    const list = $('#menu-list');
    list.empty();
    if (!menus.length) {
      list.append('<div class="text-secondary small">Henüz menü oluşturulmadı.</div>');
      $('#menu-editor').html('<div class="text-secondary small">Bir menü seçin veya yeni bir menü oluşturun.</div>');
      return;
    }

    menus.forEach((menu) => {
      const activeClass = menu.id === activeMenuId ? 'active' : '';
      list.append(`<button type="button" class="list-group-item list-group-item-action ${activeClass}" data-menu-id="${menu.id}">${menu.name}<div class="small text-muted">${menu.location}</div></button>`);
    });
  }

  function renderMenuEditor(menu) {
    if (!menu) {
      $('#menu-editor').html('<div class="text-secondary small">Bir menü seçin veya yeni bir menü oluşturun.</div>');
      return;
    }

    const items = menu.items || [];
    const itemRows = items
      .map((item) => renderMenuItemRow(item))
      .join('');

    $('#menu-editor').html(`
      <form id="menu-details" data-menu-id="${menu.id}">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Menü Adı</label>
            <input type="text" class="form-control" name="name" value="${menu.name}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Konum</label>
            <input type="text" class="form-control" name="location" value="${menu.location}" placeholder="primary, footer">
            <div class="form-text">Konumlar şablonda kullanılır, benzersiz olmalıdır.</div>
          </div>
        </div>
        <div class="vstack" id="menu-items">
          ${itemRows || '<div class="text-secondary small">Henüz menü öğesi eklenmemiş.</div>'}
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <button class="btn btn-outline-light btn-sm" type="button" id="add-menu-item">Öğe Ekle</button>
          <div class="ms-auto d-flex gap-2">
            <button class="btn btn-outline-danger btn-sm" type="button" id="delete-menu">Menüyü Sil</button>
            <button class="btn btn-outline-light btn-sm" type="button" id="save-menu-items">Menü Öğelerini Kaydet</button>
          </div>
        </div>
        <div class="mt-3 d-flex gap-2 align-items-center">
          <button class="btn btn-primary btn-sm" type="submit">Menü Bilgilerini Kaydet</button>
          <div class="small text-info" id="menu-message"></div>
        </div>
      </form>
    `);
  }

  function renderMenuItemRow(item = {}) {
    const label = item.label || '';
    const url = item.url || '';
    const target = item.target || '_self';
    const sortOrder = item.sort_order ?? '';
    return `
      <div class="menu-item-row" data-menu-item>
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Başlık</label>
            <input type="text" class="form-control" name="label" value="${label}" placeholder="Menü Başlığı">
          </div>
          <div class="col-md-5">
            <label class="form-label">Bağlantı</label>
            <input type="text" class="form-control" name="url" value="${url}" placeholder="https:// veya /sayfa">
          </div>
          <div class="col-md-2">
            <label class="form-label">Hedef</label>
            <select class="form-select" name="target">
              <option value="_self" ${target === '_self' ? 'selected' : ''}>Aynı Sekme</option>
              <option value="_blank" ${target === '_blank' ? 'selected' : ''}>Yeni Sekme</option>
            </select>
          </div>
          <div class="col-md-1">
            <label class="form-label">Sıra</label>
            <input type="number" class="form-control" name="sort_order" value="${sortOrder}">
          </div>
        </div>
        <div class="d-flex justify-content-end mt-2">
          <button type="button" class="btn btn-link text-danger text-decoration-none p-0 remove-menu-item">Öğeyi Sil</button>
        </div>
      </div>
    `;
  }

  function loadMenus(selectedId = null) {
    $.getJSON('api.php', { action: 'list-menus' })
      .done(({ data }) => {
        menus = data || [];
        if (selectedId) {
          activeMenuId = selectedId;
        } else if (!menus.find((menu) => menu.id === activeMenuId)) {
          activeMenuId = menus[0]?.id ?? null;
        }
        renderMenuList();
        renderMenuEditor(menus.find((menu) => menu.id === activeMenuId));
      });
  }

  $('#menu-list').on('click', '.list-group-item', function () {
    activeMenuId = Number($(this).data('menu-id'));
    renderMenuList();
    renderMenuEditor(menus.find((menu) => menu.id === activeMenuId));
  });

  $('#create-menu-btn').on('click', function () {
    const name = window.prompt('Menü adı:');
    if (!name) return;
    const location = window.prompt('Menü konumu (örn. primary, footer):');
    if (!location) return;

    $.post('api.php?action=create-menu', { name, location })
      .done((response) => {
        activeMenuId = response.menu?.id || null;
        loadMenus(activeMenuId);
      })
      .fail((xhr) => alert(xhr.responseJSON?.error || 'Menü oluşturulamadı.'));
  });

  $('#menu-editor').on('click', '#add-menu-item', function () {
    const container = $('#menu-items');
    container.find('.text-secondary').remove();
    container.append(renderMenuItemRow());
  });

  $('#menu-editor').on('click', '.remove-menu-item', function () {
    $(this).closest('[data-menu-item]').remove();
  });

  $('#menu-editor').on('submit', '#menu-details', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = form.find('#menu-message');
    const menuId = form.data('menu-id');
    $.post('api.php?action=update-menu', {
      id: menuId,
      name: form.find('[name="name"]').val(),
      location: form.find('[name="location"]').val(),
    })
      .done((response) => {
        message.text(response.message).removeClass('text-danger').addClass('text-success');
        loadMenus(response.menu?.id);
      })
      .fail((xhr) => {
        message.text(xhr.responseJSON?.error || 'Menü güncellenemedi').removeClass('text-success').addClass('text-danger');
      });
  });

  $('#menu-editor').on('click', '#save-menu-items', function () {
    const form = $('#menu-details');
    if (!form.length) return;
    const menuId = form.data('menu-id');
    const message = form.find('#menu-message');
    const items = [];
    form.find('[data-menu-item]').each(function () {
      const row = $(this);
      const label = row.find('[name="label"]').val();
      const url = row.find('[name="url"]').val();
      if (!label || !url) {
        return;
      }
      items.push({
        label,
        url,
        target: row.find('[name="target"]').val(),
        sort_order: row.find('[name="sort_order"]').val(),
      });
    });

    $.post('api.php?action=save-menu-items', {
      menu_id: menuId,
      items: JSON.stringify(items),
    })
      .done((response) => {
        message.text(response.message).removeClass('text-danger').addClass('text-success');
        loadMenus(response.menu?.id);
      })
      .fail((xhr) => {
        message.text(xhr.responseJSON?.error || 'Menü öğeleri kaydedilemedi').removeClass('text-success').addClass('text-danger');
      });
  });

  $('#menu-editor').on('click', '#delete-menu', function () {
    const form = $('#menu-details');
    if (!form.length) return;
    const menuId = form.data('menu-id');
    if (!window.confirm('Menüyü silmek istediğinize emin misiniz?')) {
      return;
    }
    $.post('api.php?action=delete-menu', { id: menuId })
      .done(() => {
        activeMenuId = null;
        loadMenus();
      })
      .fail((xhr) => alert(xhr.responseJSON?.error || 'Menü silinemedi.'));
  });

  loadUsers();
  loadFtpSettings();
  loadBranding();
  loadAdSettings();
  loadAnalyticsSettings();
  loadMenus();
});
