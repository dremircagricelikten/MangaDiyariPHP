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

  $('#manga-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#manga-form-message');
    $.post('api.php?action=create-manga', form.serialize())
      .done((response) => {
        message.html(`<div class="alert alert-success">${response.message}</div>`);
        form.trigger('reset');
        refreshMangaOptions();
      })
      .fail((xhr) => handleError(xhr, message));
  });

  $('#chapter-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const message = $('#chapter-form-message');
    $.post('api.php?action=create-chapter', form.serialize())
      .done((response) => {
        message.html(`<div class="alert alert-success">${response.message}</div>`);
        form.trigger('reset');
      })
      .fail((xhr) => handleError(xhr, message));
  });

  refreshMangaOptions();

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

  loadUsers();
  loadFtpSettings();
});
