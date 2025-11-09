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
});
