$(function () {
  const body = $('body');
  const page = body.data('admin-page') || '';
  const messageDismissDelay = 5000;

  function showMessage($container, type, text) {
    if (!$container || !$container.length) {
      return;
    }
    const alertClass =
      type === 'success'
        ? 'alert alert-success'
        : type === 'info'
        ? 'alert alert-info'
        : 'alert alert-danger';
    $container
      .removeClass('text-danger text-success text-info')
      .html(`<div class="${alertClass}">${text}</div>`)
      .stop(true, true)
      .fadeIn(150);

    if (type !== 'danger') {
      setTimeout(() => {
        if ($container.find('.alert').length) {
          $container.fadeOut(200, () => $container.empty().show());
        }
      }, messageDismissDelay);
    }
  }

  function handleError(xhr, $container) {
    if (xhr.status === 403) {
      window.location.href = 'login.php';
      return;
    }
    const error = xhr.responseJSON?.error || 'İşlem sırasında bir hata oluştu.';
    showMessage($container, 'danger', error);
  }

  function escapeHtml(value) {
    return $('<div>').text(value ?? '').html();
  }

  function truncate(text, length) {
    if (!text) {
      return '';
    }
    if (text.length <= length) {
      return text;
    }
    return `${text.substring(0, length - 1)}…`;
  }

  function formatDateTime(value) {
    if (!value) {
      return '';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return value;
    }
    return new Intl.DateTimeFormat('tr-TR', {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(date);
  }

  function formatStatusBadge(status) {
    const map = {
      ongoing: ['bg-info text-dark', 'Devam Ediyor'],
      completed: ['bg-success bg-opacity-75', 'Tamamlandı'],
      hiatus: ['bg-warning text-dark', 'Ara Verildi'],
    };
    const [cls, label] = map[status] || ['bg-secondary bg-opacity-50', status || 'Bilinmiyor'];
    return `<span class="badge ${cls}">${label}</span>`;
  }

  function applyDashboardStats(stats = {}) {
    Object.entries(stats).forEach(([key, value]) => {
      const target = $(`[data-dashboard-stat="${key}"]`);
      if (!target.length) {
        return;
      }
      target.text(new Intl.NumberFormat('tr-TR').format(Number(value) || 0));
    });
  }

  function refreshDashboardStats() {
    return $.getJSON('api.php', { action: 'dashboard-stats' })
      .done(({ data }) => applyDashboardStats(data || {}));
  }

  function renderCommentItem(comment) {
    const initials = (comment.username || '?').charAt(0).toUpperCase();
    const avatar = comment.avatar_url
      ? `<img src="${comment.avatar_url}" alt="${escapeHtml(comment.username)}" class="comment-avatar">`
      : `<div class="comment-avatar placeholder">${initials}</div>`;
    const mangaLink = comment.manga_slug
      ? `<a href="../public/manga.php?slug=${encodeURIComponent(comment.manga_slug)}" target="_blank" rel="noopener" class="comment-link"><i class="bi bi-book"></i> ${escapeHtml(comment.manga_title || 'Seri')}</a>`
      : '';
    const reactionSummary = comment.reaction_summary || {};
    const reactionTotal = Object.values(reactionSummary).reduce((total, count) => total + Number(count || 0), 0);

    return `
      <article class="comment-card">
        ${avatar}
        <div class="comment-content">
          <div class="comment-header">
            <span class="comment-author">${escapeHtml(comment.username)}</span>
            <span class="comment-date">${formatDateTime(comment.created_at)}</span>
          </div>
          <p class="comment-body">${escapeHtml(truncate(comment.body || '', 160))}</p>
          <div class="comment-meta">
            ${mangaLink}
            <span class="comment-reactions"><i class="bi bi-hand-thumbs-up"></i> ${reactionTotal}</span>
          </div>
        </div>
      </article>`;
  }

  function loadDashboardComments() {
    const container = $('#dashboard-comments');
    if (!container.length) {
      return;
    }
    container.html('<div class="comment-empty text-muted">Yükleniyor...</div>');
    $.getJSON('api.php', { action: 'recent-comments' })
      .done(({ data }) => {
        container.empty();
        if (!data || !data.length) {
          container.html('<div class="comment-empty text-muted">Henüz yorum yok.</div>');
          return;
        }
        data.forEach((comment) => {
          container.append(renderCommentItem(comment));
        });
      })
      .fail(() => {
        container.html('<div class="comment-empty text-danger">Yorumlar yüklenemedi.</div>');
      });
  }

  let mangaCache = [];

  function fetchMangaOptions() {
    return $.getJSON('api.php', { action: 'list-manga' })
      .done(({ data }) => {
        mangaCache = data || [];
      });
  }

  function fillMangaSelect($select, placeholder = 'Manga seçin') {
    if (!$select.length) {
      return;
    }
    $select.empty();
    if (!mangaCache.length) {
      $select.append(`<option value="">${placeholder}</option>`).prop('disabled', true);
      return;
    }
    $select.prop('disabled', false);
    $select.append(`<option value="">${placeholder}</option>`);
    mangaCache.forEach((manga) => {
      $select.append(`<option value="${manga.id}">${escapeHtml(manga.title)}</option>`);
    });
  }

  function initDashboard() {
    if (window.dashboardStats) {
      applyDashboardStats(window.dashboardStats);
    } else {
      refreshDashboardStats();
    }
    loadDashboardComments();
    $('#refresh-comments').on('click', loadDashboardComments);
  }

  function initManga() {
    const form = $('#manga-form');
    const message = $('#manga-form-message');
    const tableBody = $('#manga-table tbody');
    const emptyState = $('#manga-table-empty');
    const searchInput = $('#manga-search');
    const statusFilter = $('#manga-status-filter');
    let searchTimer = null;
    const modalElement = document.getElementById('manga-edit-modal');
    const editModal = modalElement ? new bootstrap.Modal(modalElement) : null;

    function renderMangaRow(manga) {
      const cover = manga.cover_image || 'https://placehold.co/64x96?text=Manga';
      const updated = manga.updated_at || manga.created_at;
      return `
        <tr data-manga-id="${manga.id}">
          <td width="72">
            <div class="ratio ratio-2x3 rounded overflow-hidden bg-secondary">
              <img src="${cover}" alt="${escapeHtml(manga.title)}" class="img-fluid object-fit-cover">
            </div>
          </td>
          <td>
            <div class="fw-semibold mb-1">${escapeHtml(manga.title)}</div>
            <div class="small text-muted">${escapeHtml(manga.author || '')}</div>
          </td>
          <td>${escapeHtml(manga.genres || '')}</td>
          <td>${formatStatusBadge(manga.status)}</td>
          <td>${formatDateTime(updated)}</td>
          <td class="text-end">
            <button class="btn btn-outline-light btn-sm manga-edit" type="button"><i class="bi bi-pencil"></i> Düzenle</button>
          </td>
        </tr>`;
    }

    function loadMangaTable() {
      tableBody.html('<tr><td colspan="6" class="text-center text-muted">Yükleniyor...</td></tr>');
      emptyState.addClass('d-none');
      $.getJSON('api.php', {
        action: 'list-manga',
        search: searchInput.val(),
        status: statusFilter.val(),
      })
        .done(({ data }) => {
          tableBody.empty();
          if (!data || !data.length) {
            emptyState.removeClass('d-none');
            return;
          }
          data.forEach((manga) => tableBody.append(renderMangaRow(manga)));
        })
        .fail((xhr) => {
          tableBody.html('<tr><td colspan="6" class="text-center text-danger">Manga listesi alınamadı.</td></tr>');
          handleError(xhr, message);
        });
    }

    form.on('submit', function (event) {
      event.preventDefault();
      $.post('api.php?action=create-manga', form.serialize())
        .done((response) => {
          showMessage(message, 'success', response.message || 'Manga oluşturuldu.');
          form.trigger('reset');
          fetchMangaOptions().then(() => {
            fillMangaSelect($('#manga-select'));
            fillMangaSelect($('#bulk-manga-select'));
            fillMangaSelect($('#chapter-list-manga'), 'Seri seçin');
          });
          loadMangaTable();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    $('#refresh-manga-list').on('click', loadMangaTable);
    statusFilter.on('change', loadMangaTable);
    searchInput.on('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(loadMangaTable, 250);
    });

    tableBody.on('click', '.manga-edit', function () {
      const row = $(this).closest('tr');
      const mangaId = row.data('manga-id');
      if (!mangaId) {
        return;
      }
      $('#manga-edit-message').empty();
      $.getJSON('api.php', { action: 'get-manga', id: mangaId })
        .done(({ data }) => {
          $('#edit-manga-id').val(data.id);
          $('#edit-manga-title').val(data.title);
          $('#edit-manga-cover').val(data.cover_image);
          $('#edit-manga-description').val(data.description);
          $('#edit-manga-type').val(data.genres);
          $('#edit-manga-status').val(data.status);
          $('#edit-manga-author').val(data.author);
          $('#edit-manga-artist').val(data.artist);
          $('#edit-manga-tags').val(data.tags);
          editModal?.show();
        });
    });

    $('#manga-edit-form').on('submit', function (event) {
      event.preventDefault();
      const editMessage = $('#manga-edit-message');
      $.post('api.php?action=update-manga', $(this).serialize())
        .done((response) => {
          showMessage(editMessage, 'success', response.message || 'Manga güncellendi.');
          loadMangaTable();
          fetchMangaOptions().then(() => {
            fillMangaSelect($('#manga-select'));
            fillMangaSelect($('#bulk-manga-select'));
            fillMangaSelect($('#chapter-list-manga'), 'Seri seçin');
          });
        })
        .fail((xhr) => handleError(xhr, editMessage));
    });

    $('#delete-manga').on('click', function () {
      const id = $('#edit-manga-id').val();
      if (!id || !window.confirm('Bu mangayı silmek istediğinize emin misiniz?')) {
        return;
      }
      const editMessage = $('#manga-edit-message');
      $.post('api.php?action=delete-manga', { id })
        .done((response) => {
          showMessage(editMessage, 'success', response.message || 'Manga silindi.');
          editModal?.hide();
          loadMangaTable();
          fetchMangaOptions().then(() => {
            fillMangaSelect($('#manga-select'));
            fillMangaSelect($('#bulk-manga-select'));
            fillMangaSelect($('#chapter-list-manga'), 'Seri seçin');
          });
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, editMessage));
    });

    fetchMangaOptions().then(() => {
      fillMangaSelect($('#manga-select'));
      fillMangaSelect($('#bulk-manga-select'));
      fillMangaSelect($('#chapter-list-manga'), 'Seri seçin');
    });
    loadMangaTable();
  }
  function initChapters() {
    const singleForm = $('#chapter-form');
    const singleMessage = $('#chapter-form-message');
    const bulkForm = $('#bulk-chapter-form');
    const bulkMessage = $('#bulk-chapter-message');
    const chapterTableBody = $('#chapter-table tbody');
    const emptyState = $('#chapter-table-empty');
    const listMangaSelect = $('#chapter-list-manga');
    const sortSelect = $('#chapter-sort');
    const modalElement = document.getElementById('chapter-edit-modal');
    const editModal = modalElement ? new bootstrap.Modal(modalElement) : null;

    function updateMangaSelects() {
      fillMangaSelect($('#manga-select'));
      fillMangaSelect($('#bulk-manga-select'));
      fillMangaSelect(listMangaSelect, 'Seri seçin');
    }

    function loadChapters() {
      const mangaId = listMangaSelect.val();
      if (!mangaId) {
        chapterTableBody.empty();
        emptyState.removeClass('d-none').text('Seçilen seriye ait bölüm bulunamadı.');
        return;
      }
      chapterTableBody.html('<tr><td colspan="6" class="text-center text-muted">Yükleniyor...</td></tr>');
      emptyState.addClass('d-none');
      $.getJSON('api.php', {
        action: 'list-chapters',
        manga_id: mangaId,
        order: sortSelect.val(),
      })
        .done(({ data }) => {
          chapterTableBody.empty();
          if (!data || !data.length) {
            emptyState.removeClass('d-none').text('Bu seriye ait bölüm bulunamadı.');
            return;
          }
          data.forEach((chapter) => {
            chapterTableBody.append(`
              <tr data-chapter-id="${chapter.id}">
                <td><span class="fw-semibold">Bölüm ${escapeHtml(chapter.number)}</span></td>
                <td>${escapeHtml(chapter.title || '')}</td>
                <td>${chapter.ki_cost > 0 ? '<span class="badge bg-warning text-dark">Premium</span>' : '<span class="badge bg-success bg-opacity-50">Ücretsiz</span>'}</td>
                <td>${Array.isArray(chapter.assets) ? chapter.assets.length : 0}</td>
                <td>${formatDateTime(chapter.updated_at || chapter.created_at)}</td>
                <td class="text-end">
                  <button class="btn btn-outline-light btn-sm chapter-edit" type="button"><i class="bi bi-pencil"></i> Düzenle</button>
                </td>
              </tr>`);
          });
        })
        .fail((xhr) => {
          chapterTableBody.html('<tr><td colspan="6" class="text-center text-danger">Bölümler yüklenemedi.</td></tr>');
          handleError(xhr, singleMessage);
        });
    }

    singleForm.on('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(this);
      singleMessage.empty();
      $.ajax({
        url: 'api.php?action=create-chapter',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(singleMessage, 'success', response.message || 'Bölüm oluşturuldu.');
          singleForm[0].reset();
          loadChapters();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, singleMessage));
    });

    bulkForm.on('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(this);
      bulkMessage.empty();
      $.ajax({
        url: 'api.php?action=bulk-create-chapters',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          const createdCount = response.created ? response.created.length : 0;
          const text = createdCount
            ? `${createdCount} bölüm başarıyla yüklendi.`
            : 'Yeni bölüm eklenmedi. Lütfen ZIP içeriğini kontrol edin.';
          showMessage(bulkMessage, 'success', text);
          bulkForm[0].reset();
          loadChapters();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, bulkMessage));
    });

    $('#refresh-chapter-list').on('click', loadChapters);
    listMangaSelect.on('change', loadChapters);
    sortSelect.on('change', loadChapters);

    chapterTableBody.on('click', '.chapter-edit', function () {
      const chapterId = $(this).closest('tr').data('chapter-id');
      if (!chapterId) {
        return;
      }
      $('#chapter-edit-message').empty();
      $.getJSON('api.php', { action: 'get-chapter', id: chapterId })
        .done(({ data }) => {
          $('#edit-chapter-id').val(data.id);
          $('#edit-chapter-number').val(data.number);
          $('#edit-chapter-title').val(data.title);
          $('#edit-chapter-content').val(data.content);
          $('#edit-chapter-ki').val(data.ki_cost);
          $('#edit-chapter-premium').val(data.premium_expires_at ? data.premium_expires_at.replace(' ', 'T') : '');
          editModal?.show();
        });
    });

    $('#chapter-edit-form').on('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(this);
      const message = $('#chapter-edit-message');
      $.ajax({
        url: 'api.php?action=update-chapter',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Bölüm güncellendi.');
          loadChapters();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    $('#delete-chapter').on('click', function () {
      const id = $('#edit-chapter-id').val();
      if (!id || !window.confirm('Bu bölümü silmek istediğinize emin misiniz?')) {
        return;
      }
      const message = $('#chapter-edit-message');
      $.post('api.php?action=delete-chapter', { id })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Bölüm silindi.');
          editModal?.hide();
          loadChapters();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    fetchMangaOptions().then(() => {
      updateMangaSelects();
      loadChapters();
    });
  }

  function initMarket() {
    const form = $('#market-form');
    const message = $('#market-form-message');
    const tableBody = $('#market-table tbody');
    const emptyState = $('#market-table-empty');
    const modalElement = document.getElementById('market-edit-modal');
    const editModal = modalElement ? new bootstrap.Modal(modalElement) : null;

    function renderOfferRow(offer) {
      return `
        <tr data-offer-id="${offer.id}">
          <td>
            <div class="fw-semibold">${escapeHtml(offer.title)}</div>
            <div class="small text-muted">${formatDateTime(offer.updated_at || offer.created_at)}</div>
          </td>
          <td>${offer.ki_amount}</td>
          <td>${Number(offer.price).toFixed(2)} ${escapeHtml(offer.currency)}</td>
          <td>${offer.is_active ? '<span class="badge bg-success bg-opacity-75">Yayında</span>' : '<span class="badge bg-secondary">Taslak</span>'}</td>
          <td>${formatDateTime(offer.updated_at || offer.created_at)}</td>
          <td class="text-end">
            <button class="btn btn-outline-light btn-sm market-edit" type="button"><i class="bi bi-pencil"></i> Düzenle</button>
          </td>
        </tr>`;
    }

    function loadOffers() {
      tableBody.html('<tr><td colspan="6" class="text-center text-muted">Yükleniyor...</td></tr>');
      emptyState.addClass('d-none');
      $.getJSON('api.php', { action: 'list-market-offers' })
        .done(({ data }) => {
          tableBody.empty();
          if (!data || !data.length) {
            emptyState.removeClass('d-none');
            return;
          }
          data.forEach((offer) => tableBody.append(renderOfferRow(offer)));
        })
        .fail((xhr) => {
          tableBody.html('<tr><td colspan="6" class="text-center text-danger">Market teklifleri yüklenemedi.</td></tr>');
          handleError(xhr, message);
        });
    }

    form.on('submit', function (event) {
      event.preventDefault();
      $.post('api.php?action=save-market-offer', form.serialize())
        .done((response) => {
          showMessage(message, 'success', response.message || 'Teklif kaydedildi.');
          form.trigger('reset');
          loadOffers();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    tableBody.on('click', '.market-edit', function () {
      const offerId = $(this).closest('tr').data('offer-id');
      if (!offerId) {
        return;
      }
      $('#market-edit-message').empty();
      $.getJSON('api.php', { action: 'list-market-offers' })
        .done(({ data }) => {
          const offer = (data || []).find((item) => Number(item.id) === Number(offerId));
          if (!offer) {
            return;
          }
          $('#edit-offer-id').val(offer.id);
          $('#edit-offer-title').val(offer.title);
          $('#edit-offer-ki').val(offer.ki_amount);
          $('#edit-offer-price').val(offer.price);
          $('#edit-offer-currency').val(offer.currency);
          $('#edit-offer-status').val(String(offer.is_active));
          editModal?.show();
        });
    });

    $('#market-edit-form').on('submit', function (event) {
      event.preventDefault();
      const message = $('#market-edit-message');
      $.post('api.php?action=save-market-offer', $(this).serialize())
        .done((response) => {
          showMessage(message, 'success', response.message || 'Teklif güncellendi.');
          loadOffers();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    $('#delete-market-offer').on('click', function () {
      const id = $('#edit-offer-id').val();
      if (!id || !window.confirm('Bu teklifi silmek istediğinize emin misiniz?')) {
        return;
      }
      const message = $('#market-edit-message');
      $.post('api.php?action=delete-market-offer', { id })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Teklif silindi.');
          editModal?.hide();
          loadOffers();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    loadOffers();
  }

  function initSettings() {
    const siteForm = $('#site-settings-form');
    const siteMessage = $('#site-settings-message');
    const storageForm = $('#storage-settings-form');
    const storageMessage = $('#storage-settings-message');

    siteForm.on('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(this);
      $.ajax({
        url: 'api.php?action=update-site-settings',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(siteMessage, 'success', response.message || 'Ayarlar güncellendi.');
          if (response.data?.site_logo) {
            $('.sidebar-logo-image img').attr('src', `../public/${response.data.site_logo}`);
          }
        })
        .fail((xhr) => handleError(xhr, siteMessage));
    });

    storageForm.on('submit', function (event) {
      event.preventDefault();
      $.post('api.php?action=update-storage-settings', storageForm.serialize())
        .done((response) => {
          showMessage(storageMessage, 'success', response.message || 'Depolama ayarları güncellendi.');
        })
        .fail((xhr) => handleError(xhr, storageMessage));
    });
  }

  function initAppearance() {
    const themeForm = $('#theme-form');
    const themeMessage = $('#theme-form-message');
    const brandingForm = $('#branding-form');
    const brandingMessage = $('#branding-form-message');

    function loadThemeSettings() {
      $.getJSON('api.php', { action: 'get-settings' })
        .done(({ data }) => {
          if (!data) {
            return;
          }
          const keys = ['primary_color', 'accent_color', 'background_color', 'gradient_start', 'gradient_end'];
          keys.forEach((key) => {
            const field = $(`#${key}`);
            if (field.length && data[key]) {
              field.val(data[key]);
            }
          });
        });
    }

    loadThemeSettings();

    themeForm.on('submit', function (event) {
      event.preventDefault();
      $.post('api.php?action=update-settings', themeForm.serialize())
        .done((response) => showMessage(themeMessage, 'success', response.message || 'Tema ayarları kaydedildi.'))
        .fail((xhr) => handleError(xhr, themeMessage));
    });

    brandingForm.on('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(this);
      $.ajax({
        url: 'api.php?action=update-branding',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(brandingMessage, 'success', response.message || 'Marka ayarları güncellendi.');
          if (response.data?.site_logo) {
            $('#branding-preview').html(`<img src="../public/${response.data.site_logo}" alt="Logo" class="img-fluid rounded shadow-sm" style="max-height: 80px;">`);
            $('.sidebar-logo-image img').attr('src', `../public/${response.data.site_logo}`);
          }
        })
        .fail((xhr) => handleError(xhr, brandingMessage));
    });
  }
  function initWidgets() {
    const container = $('#widget-list');
    if (!container.length) {
      return;
    }

    function renderWidgetForm(widget) {
      const config = widget.config || {};
      const sortOrder = widget.sort_order ?? 0;
      const status = config.status || '';
      const limit = config.limit || 6;
      const sort = config.sort || 'random';
      const area = widget.area || 'main';

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
              <h3 class="h5 mb-1">${escapeHtml(widget.title)}</h3>
              <span class="badge bg-light text-dark">${escapeHtml(widget.type)}</span>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" name="enabled" ${widget.enabled ? 'checked' : ''}>
              <label class="form-check-label">Etkin</label>
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-lg-4">
              <label class="form-label">Başlık</label>
              <input type="text" class="form-control" name="title" value="${escapeHtml(widget.title)}">
            </div>
            <div class="col-lg-4">
              <label class="form-label">Alan</label>
              <select class="form-select" name="area">
                <option value="hero" ${area === 'hero' ? 'selected' : ''}>Hero Başlığı</option>
                <option value="main" ${area === 'main' ? 'selected' : ''}>Ana İçerik</option>
                <option value="sidebar" ${area === 'sidebar' ? 'selected' : ''}>Yan Panel</option>
              </select>
            </div>
            <div class="col-lg-4">
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
          container.empty();
          if (!data || !data.length) {
            container.append('<div class="alert alert-dark">Kayıtlı widget bulunmuyor.</div>');
            return;
          }
          data.forEach((widget) => container.append(renderWidgetForm(widget)));
        });
    }

    container.on('submit', '.widget-form', function (event) {
      event.preventDefault();
      const form = $(this);
      const widgetId = form.data('widget-id');
      const message = form.find('.widget-message');
      const config = {};
      form.find('select[name^="config"], input[name^="config"]').each(function () {
        const field = $(this);
        const match = field.attr('name').match(/config\[(.+)]/);
        if (match && match[1]) {
          config[match[1]] = field.val();
        }
      });

      $.post('api.php?action=update-widget', {
        id: widgetId,
        title: form.find('input[name="title"]').val(),
        area: form.find('select[name="area"]').val(),
        sort_order: form.find('input[name="sort_order"]').val(),
        enabled: form.find('input[name="enabled"]').is(':checked') ? 1 : 0,
        config: JSON.stringify(config),
      })
        .done((response) => {
          message.removeClass('text-danger').addClass('text-success').text(response.message || 'Widget kaydedildi.');
        })
        .fail((xhr) => {
          if (xhr.status === 403) {
            window.location.href = 'login.php';
            return;
          }
          message.removeClass('text-success').addClass('text-danger').text(xhr.responseJSON?.error || 'Widget kaydedilemedi.');
        });
    });

    loadWidgets();
  }

  function initPages() {
    const tableBody = $('#page-table tbody');
    const form = $('#page-form');
    const message = $('#page-form-message');
    const cancelButton = $('#page-cancel-edit');
    const statusFilter = $('#page-status-filter');
    const searchInput = $('#page-search');
    let searchTimer = null;

    function renderPageRow(page) {
      const updated = formatDateTime(page.updated_at || page.created_at);
      return `
        <tr data-page-id="${page.id}">
          <td>
            <div class="fw-semibold">${escapeHtml(page.title)}</div>
            <div class="small text-muted">${updated}</div>
          </td>
          <td>${page.status === 'published' ? '<span class="badge bg-success bg-opacity-75">Yayında</span>' : '<span class="badge bg-secondary bg-opacity-50">Taslak</span>'}</td>
          <td><code>page.php?slug=${escapeHtml(page.slug || '')}</code></td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-light page-edit" type="button">Düzenle</button>
              <button class="btn btn-outline-danger page-delete" type="button">Sil</button>
            </div>
          </td>
        </tr>`;
    }

    function loadPages() {
      tableBody.html('<tr><td colspan="4" class="text-center text-muted">Yükleniyor...</td></tr>');
      $.getJSON('api.php', {
        action: 'list-pages',
        status: statusFilter.val(),
        search: searchInput.val(),
      })
        .done(({ data }) => {
          tableBody.empty();
          if (!data || !data.length) {
            tableBody.html('<tr><td colspan="4" class="text-center text-muted">Henüz sayfa oluşturulmadı.</td></tr>');
            return;
          }
          data.forEach((page) => tableBody.append(renderPageRow(page)));
        })
        .fail(() => {
          tableBody.html('<tr><td colspan="4" class="text-center text-danger">Sayfalar yüklenemedi.</td></tr>');
        });
    }

    function resetForm() {
      form[0].reset();
      $('#page-id').val('');
      message.empty();
      cancelButton.addClass('d-none');
      $('#page-form-hint').text('Yeni sayfalar yayınlandığında menülere ekleyebilirsiniz.');
    }

    form.on('submit', function (event) {
      event.preventDefault();
      const payload = Object.fromEntries(form.serializeArray().map(({ name, value }) => [name, value]));
      const id = $('#page-id').val();
      const action = id ? 'update-page' : 'create-page';
      if (id) {
        payload.id = id;
      }
      message.html('<div class="text-muted">Kaydediliyor...</div>');
      $.post(`api.php?action=${action}`, payload)
        .done((response) => {
          showMessage(message, 'success', response.message || 'Sayfa kaydedildi.');
          resetForm();
          loadPages();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    cancelButton.on('click', resetForm);

    statusFilter.on('change', loadPages);
    searchInput.on('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(loadPages, 250);
    });

    tableBody.on('click', '.page-edit', function () {
      const pageId = $(this).closest('tr').data('page-id');
      if (!pageId) {
        return;
      }
      $.getJSON('api.php', { action: 'get-page', id: pageId })
        .done(({ data }) => {
          if (!data) {
            return;
          }
          $('#page-id').val(data.id);
          $('#page-title').val(data.title);
          $('#page-slug').val(data.slug);
          $('#page-status').val(data.status);
          $('#page-content').val(data.content);
          cancelButton.removeClass('d-none');
          $('#page-form-hint').text('Güncellemeleri kaydedip değişiklikleri hemen yayınlayabilirsiniz.');
          message.empty();
        });
    });

    tableBody.on('click', '.page-delete', function () {
      const pageId = $(this).closest('tr').data('page-id');
      if (!pageId || !window.confirm('Bu sayfayı silmek istediğinize emin misiniz?')) {
        return;
      }
      $.post('api.php?action=delete-page', { id: pageId })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Sayfa silindi.');
          loadPages();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    resetForm();
    loadPages();
  }

  function initMenus() {
    const list = $('#menu-list');
    const editor = $('#menu-editor');
    let menus = [];
    let activeMenuId = null;

    function renderMenuList() {
      list.empty();
      if (!menus.length) {
        list.append('<div class="text-secondary small">Henüz menü oluşturulmadı.</div>');
        editor.html('<div class="text-secondary small">Bir menü seçin veya yeni bir menü oluşturun.</div>');
        return;
      }
      menus.forEach((menu) => {
        const activeClass = menu.id === activeMenuId ? 'active' : '';
        list.append(`<button type="button" class="list-group-item list-group-item-action ${activeClass}" data-menu-id="${menu.id}">${escapeHtml(menu.name)}<div class="small text-muted">${escapeHtml(menu.location)}</div></button>`);
      });
    }

    function renderMenuItemRow(item = {}) {
      const label = escapeHtml(item.label || '');
      const url = escapeHtml(item.url || '');
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
        </div>`;
    }

    function renderMenuEditor(menu) {
      if (!menu) {
        editor.html('<div class="text-secondary small">Bir menü seçin veya yeni bir menü oluşturun.</div>');
        return;
      }
      const items = menu.items || [];
      const itemRows = items.map((item) => renderMenuItemRow(item)).join('');
      editor.html(`
        <form id="menu-details" data-menu-id="${menu.id}">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Menü Adı</label>
              <input type="text" class="form-control" name="name" value="${escapeHtml(menu.name)}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Konum</label>
              <input type="text" class="form-control" name="location" value="${escapeHtml(menu.location)}" placeholder="primary, footer">
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
        </form>`);
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

    list.on('click', '.list-group-item', function () {
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

    editor.on('click', '#add-menu-item', function () {
      const container = $('#menu-items');
      container.find('.text-secondary').remove();
      container.append(renderMenuItemRow());
    });

    editor.on('click', '.remove-menu-item', function () {
      $(this).closest('[data-menu-item]').remove();
    });

    editor.on('submit', '#menu-details', function (event) {
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
          message.text(response.message || 'Menü güncellendi').removeClass('text-danger').addClass('text-success');
          loadMenus(response.menu?.id);
        })
        .fail((xhr) => {
          message.text(xhr.responseJSON?.error || 'Menü güncellenemedi').removeClass('text-success').addClass('text-danger');
        });
    });

    editor.on('click', '#save-menu-items', function () {
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
          message.text(response.message || 'Menü öğeleri kaydedildi').removeClass('text-danger').addClass('text-success');
          loadMenus(response.menu?.id);
        })
        .fail((xhr) => {
          message.text(xhr.responseJSON?.error || 'Menü öğeleri kaydedilemedi').removeClass('text-success').addClass('text-danger');
        });
    });

    editor.on('click', '#delete-menu', function () {
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

    loadMenus();
  }
  function initCommunity() {
    const userTableBody = $('#user-table tbody');
    const createUserForm = $('#create-user-form');
    const createUserMessage = $('#create-user-message');
    const adForm = $('#ad-form');
    const adMessage = $('#ad-form-message');

    function renderUserRow(user) {
      const initial = (user.username || '?').charAt(0).toUpperCase();
      const avatar = user.avatar_url
        ? `<img src="${user.avatar_url}" alt="${escapeHtml(user.username)}" class="rounded-circle me-2" width="36" height="36">`
        : `<div class="avatar-placeholder rounded-circle me-2">${initial}</div>`;
      const active = Number(user.is_active) === 1;
      return `
        <tr data-user-id="${user.id}">
          <td>
            <div class="d-flex align-items-center">
              ${avatar}
              <div>
                <div class="fw-semibold">${escapeHtml(user.username)}</div>
                <div class="small text-muted">${escapeHtml(user.email)}</div>
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
        </tr>`;
    }

    function loadUsers() {
      userTableBody.html('<tr><td colspan="4" class="text-center text-muted">Yükleniyor...</td></tr>');
      $.getJSON('api.php', { action: 'list-users' })
        .done(({ data }) => {
          userTableBody.empty();
          if (!data || !data.length) {
            userTableBody.html('<tr><td colspan="4" class="text-center text-muted">Henüz üye bulunmuyor.</td></tr>');
            return;
          }
          data.forEach((user) => userTableBody.append(renderUserRow(user)));
        })
        .fail(() => {
          userTableBody.html('<tr><td colspan="4" class="text-center text-danger">Üyeler yüklenemedi.</td></tr>');
        });
    }

    createUserForm.on('submit', function (event) {
      event.preventDefault();
      const payload = createUserForm.serializeArray();
      if (!createUserForm.find('[name="is_active"]').is(':checked')) {
        payload.push({ name: 'is_active', value: '0' });
      }
      $.post('api.php?action=create-user', $.param(payload))
        .done((response) => {
          showMessage(createUserMessage, 'success', response.message || 'Üye oluşturuldu.');
          createUserForm.trigger('reset');
          loadUsers();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, createUserMessage));
    });

    userTableBody.on('click', '.user-save', function () {
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
          message.addClass('text-success').text(response.message || 'Üye güncellendi.');
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

    function loadAdSettings() {
      $.getJSON('api.php', { action: 'get-ad-settings' })
        .done(({ data }) => {
          if (!data) {
            return;
          }
          adForm.find('[name="ad_header"]').val(data.ad_header || '');
          adForm.find('[name="ad_sidebar"]').val(data.ad_sidebar || '');
          adForm.find('[name="ad_footer"]').val(data.ad_footer || '');
        });
    }

    adForm.on('submit', function (event) {
      event.preventDefault();
      $.post('api.php?action=update-ad-settings', adForm.serialize())
        .done((response) => showMessage(adMessage, 'success', response.message || 'Reklam alanları güncellendi.'))
        .fail((xhr) => handleError(xhr, adMessage));
    });

    loadUsers();
    loadAdSettings();
  }

  function initIntegrations() {
    const analyticsForm = $('#analytics-form');
    const analyticsMessage = $('#analytics-form-message');
    const kiForm = $('#ki-settings-form');
    const kiMessage = $('#ki-settings-message');

    function loadAnalytics() {
      $.getJSON('api.php', { action: 'get-analytics' })
        .done(({ data }) => {
          if (!data) return;
          analyticsForm.find('[name="analytics_google"]').val(data.analytics_google || '');
          analyticsForm.find('[name="analytics_search_console"]').val(data.analytics_search_console || '');
        });
    }

    function loadKiSettings() {
      $.getJSON('api.php', { action: 'get-ki-settings' })
        .done(({ data }) => {
          if (!data) return;
          kiForm.find('[name="currency_name"]').val(data.currency_name || 'Ki');
          kiForm.find('[name="comment_reward"]').val(data.comment_reward || 0);
          kiForm.find('[name="reaction_reward"]').val(data.reaction_reward || 0);
          kiForm.find('[name="chat_reward_per_minute"]').val(data.chat_reward_per_minute || 0);
          kiForm.find('[name="read_reward_per_minute"]').val(data.read_reward_per_minute || 0);
          kiForm.find('[name="market_enabled"]').prop('checked', Number(data.market_enabled) === 1);
          kiForm.find('[name="unlock_default_duration"]').val(data.unlock_default_duration || 0);
        });
    }

    analyticsForm.on('submit', function (event) {
      event.preventDefault();
      $.post('api.php?action=update-analytics', analyticsForm.serialize())
        .done((response) => showMessage(analyticsMessage, 'success', response.message || 'Analitik ayarları güncellendi.'))
        .fail((xhr) => handleError(xhr, analyticsMessage));
    });

    kiForm.on('submit', function (event) {
      event.preventDefault();
      const payload = kiForm.serializeArray();
      if (!kiForm.find('[name="market_enabled"]').is(':checked')) {
        payload.push({ name: 'market_enabled', value: '0' });
      }
      $.post('api.php?action=update-ki-settings', $.param(payload))
        .done((response) => showMessage(kiMessage, 'success', response.message || 'Ki ayarları güncellendi.'))
        .fail((xhr) => handleError(xhr, kiMessage));
    });

    loadAnalytics();
    loadKiSettings();
  }

  if (page === 'dashboard') {
    initDashboard();
  }
  if (page === 'manga') {
    initManga();
  }
  if (page === 'chapters') {
    initChapters();
  }
  if (page === 'market') {
    initMarket();
  }
  if (page === 'settings') {
    initSettings();
  }
  if (page === 'appearance') {
    initAppearance();
  }
  if (page === 'widgets') {
    initWidgets();
  }
  if (page === 'pages') {
    initPages();
  }
  if (page === 'menus') {
    initMenus();
  }
  if (page === 'community') {
    initCommunity();
  }
  if (page === 'integrations') {
    initIntegrations();
  }
});
