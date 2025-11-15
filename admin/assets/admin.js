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

  function initCoverUpload(scope) {
    const container = scope ? $(scope) : $(document);
    container.find('[data-cover-trigger]').each(function () {
      const button = $(this);
      const targetSelector = button.data('cover-trigger');
      const defaultLabel = button.data('default-label') || button.text();
      if (!targetSelector) {
        return;
      }
      const input = $(targetSelector);
      if (!input.length) {
        return;
      }
      const feedback = $(`[data-cover-selected-for="${targetSelector}"]`);

      button.off('click.cover').on('click.cover', (event) => {
        event.preventDefault();
        input.trigger('click');
      });

      input.off('change.cover').on('change.cover', function () {
        const file = this.files && this.files[0] ? this.files[0] : null;
        if (file) {
          const label = file.name.length > 26 ? `${file.name.substring(0, 23)}…` : file.name;
          button.text(label).removeClass('btn-outline-light').addClass('btn-success');
          if (feedback.length) {
            feedback.text(`Seçili dosya: ${file.name}`);
          }
        } else {
          button.text(defaultLabel).removeClass('btn-success').addClass('btn-outline-light');
          if (feedback.length) {
            feedback.text('URL girebilir veya dosya seçebilirsiniz.');
          }
        }
      });
    });
  }

  function bindChapterEditForm(onUpdated, onDeleted) {
    const form = $('#chapter-edit-form');
    if (!form.length || form.data('modal-bound')) {
      return;
    }
    form.data('modal-bound', true);
    const message = $('#chapter-edit-message');

    form.on('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(this);
      $.ajax({
        url: 'api.php?action=update-chapter',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Bölüm güncellendi.');
          if (typeof onUpdated === 'function') {
            onUpdated();
          }
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    $('#delete-chapter').on('click', function () {
      const id = $('#edit-chapter-id').val();
      if (!id || !window.confirm('Bu bölümü silmek istediğinize emin misiniz?')) {
        return;
      }
      $.post('api.php?action=delete-chapter', { id })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Bölüm silindi.');
          const modalInstance = bootstrap.Modal.getInstance(document.getElementById('chapter-edit-modal'));
          modalInstance?.hide();
          if (typeof onDeleted === 'function') {
            onDeleted();
          }
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, message));
    });
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
    const modalElement = document.getElementById('manga-edit-modal');
    const editModal = modalElement ? new bootstrap.Modal(modalElement) : null;
    const defaultStorage = (body.data('default-storage') || 'local').toString();
    const inlineForm = $('#inline-chapter-form');
    const inlineMessage = $('#inline-chapter-message');
    const inlineTableBody = $('#inline-chapter-table tbody');
    const inlineEmpty = $('#inline-chapter-empty');
    const inlineSort = $('#inline-chapter-sort');
    const inlineMangaId = $('#inline-chapter-manga-id');
    const quickForm = $('#quick-chapter-form');
    const quickMessage = $('#quick-chapter-message');
    const quickMangaSelect = $('#quick-chapter-manga');
    const quickStorage = $('#quick-chapter-storage');
    let searchTimer = null;
    let currentMangaId = null;

    initCoverUpload(form);
    initCoverUpload('#manga-edit-modal');

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

    function refreshMangaOptions() {
      fetchMangaOptions().then(() => {
        fillMangaSelect($('#manga-select'));
        fillMangaSelect($('#bulk-manga-select'));
        fillMangaSelect($('#chapter-list-manga'), 'Seri seçin');
        fillMangaSelect(quickMangaSelect, 'Seri seçin');
        if (quickMangaSelect && quickMangaSelect.length) {
          quickMangaSelect.val('');
        }
      });
    }

    function applyInlineStorageDefault() {
      if (!inlineForm.length) {
        return;
      }
      if (defaultStorage === 'ftp') {
        $('#inline-storage-ftp').prop('checked', true);
        $('#inline-storage-local').prop('checked', false);
      } else {
        $('#inline-storage-local').prop('checked', true);
        $('#inline-storage-ftp').prop('checked', false);
      }
    }

    function applyQuickStorageDefault() {
      if (!quickStorage.length) {
        return;
      }
      if (defaultStorage === 'ftp') {
        quickStorage.val('ftp');
      } else {
        quickStorage.val('local');
      }
    }

    function disableInlineForm() {
      if (!inlineForm.length) {
        return;
      }
      inlineForm.find('input, textarea, button, select').prop('disabled', true);
      inlineMangaId.val('');
      inlineTableBody.empty();
      inlineEmpty.removeClass('d-none').text('Mangayı düzenlemek için listeden seçim yapın.');
    }

    function renderInlineChapterRow(chapter) {
      const premiumLabel = chapter.ki_cost > 0 ? `<span class="badge bg-warning text-dark">Ki ${chapter.ki_cost}</span>` : '<span class="badge bg-success bg-opacity-50">Ücretsiz</span>';
      const assetCount = Array.isArray(chapter.assets) ? chapter.assets.length : 0;
      const updated = formatDateTime(chapter.updated_at || chapter.created_at);
      return `
        <tr data-chapter-id="${chapter.id}">
          <td><span class="fw-semibold">Bölüm ${escapeHtml(chapter.number)}</span></td>
          <td>${escapeHtml(chapter.title || '')}</td>
          <td>${premiumLabel}</td>
          <td>${assetCount}</td>
          <td>${updated}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-outline-light inline-chapter-edit" type="button"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-outline-danger inline-chapter-delete" type="button"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }

    function loadInlineChapters() {
      if (!inlineForm.length) {
        return;
      }
      if (!currentMangaId) {
        disableInlineForm();
        return;
      }
      inlineForm.find('input, textarea, button, select').prop('disabled', false);
      inlineMangaId.val(currentMangaId);
      inlineEmpty.addClass('d-none');
      inlineTableBody.html('<tr><td colspan="6" class="text-center text-muted">Yükleniyor...</td></tr>');
      $.getJSON('api.php', {
        action: 'list-chapters',
        manga_id: currentMangaId,
        order: inlineSort.val(),
      })
        .done(({ data }) => {
          inlineTableBody.empty();
          if (!data || !data.length) {
            inlineEmpty.removeClass('d-none').text('Bu mangaya ait bölüm bulunamadı.');
            return;
          }
          data.forEach((chapter) => inlineTableBody.append(renderInlineChapterRow(chapter)));
        })
        .fail((xhr) => {
          inlineTableBody.html('<tr><td colspan="6" class="text-center text-danger">Bölümler yüklenemedi.</td></tr>');
          handleError(xhr, inlineMessage);
        });
    }

    form.on('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(this);
      $.ajax({
        url: 'api.php?action=create-manga',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Manga oluşturuldu.');
          this.reset();
          form.find('input[type="file"]').val('').trigger('change');
          refreshMangaOptions();
          loadMangaTable();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    if (quickForm.length) {
      applyQuickStorageDefault();
      quickForm.on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        if (!formData.get('manga_id')) {
          showMessage(quickMessage, 'danger', 'Önce bir manga seçmelisiniz.');
          return;
        }
        $.ajax({
          url: 'api.php?action=create-chapter',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
        })
          .done((response) => {
            showMessage(quickMessage, 'success', response.message || 'Bölüm oluşturuldu.');
            quickForm[0].reset();
            quickForm.find('input[type="file"]').val('');
            quickMangaSelect.val('');
            applyQuickStorageDefault();
            loadMangaTable();
            refreshDashboardStats();
          })
          .fail((xhr) => handleError(xhr, quickMessage));
      });
    }

    $('#refresh-manga-list').on('click', loadMangaTable);
    statusFilter.on('change', loadMangaTable);
    searchInput.on('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(loadMangaTable, 250);
    });

    tableBody.on('click', '.manga-edit', function () {
      const mangaId = $(this).closest('tr').data('manga-id');
      if (!mangaId) {
        return;
      }
      $('#manga-edit-message').empty();
      $.getJSON('api.php', { action: 'get-manga', id: mangaId })
        .done(({ data }) => {
          $('#edit-manga-id').val(data.id);
          $('#edit-manga-title').val(data.title);
          $('#edit-manga-slug').val(data.slug);
          $('#edit-manga-cover').val(data.cover_image);
          $('#edit-manga-description').val(data.description);
          $('#edit-manga-type').val(data.genres);
          $('#edit-manga-status').val(data.status);
          $('#edit-manga-author').val(data.author);
          $('#edit-manga-artist').val(data.artist);
          $('#edit-manga-tags').val(data.tags);
          $('#edit-manga-cover-upload').val('').trigger('change');
          initCoverUpload('#manga-edit-modal');
          if (modalElement) {
            const generalTab = modalElement.querySelector('#manga-tab-general');
            if (generalTab) {
              const tab = new bootstrap.Tab(generalTab);
              tab.show();
            }
          }
          currentMangaId = data.id;
          if (inlineForm.length) {
            inlineForm[0].reset();
            inlineForm.find('input[type="file"]').val('').trigger('change');
            inlineMessage.empty();
            applyInlineStorageDefault();
            loadInlineChapters();
            bindChapterEditForm(() => loadInlineChapters(), () => {
              loadInlineChapters();
              loadMangaTable();
            });
          }
          editModal?.show();
        });
    });

    $('#manga-edit-form').on('submit', function (event) {
      event.preventDefault();
      const editMessage = $('#manga-edit-message');
      const formData = new FormData(this);
      $.ajax({
        url: 'api.php?action=update-manga',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(editMessage, 'success', response.message || 'Manga güncellendi.');
          loadMangaTable();
          refreshMangaOptions();
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
          currentMangaId = null;
          disableInlineForm();
          loadMangaTable();
          refreshMangaOptions();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, editMessage));
    });

    inlineForm.on('submit', function (event) {
      if (!inlineForm.length) {
        return;
      }
      event.preventDefault();
      if (!currentMangaId) {
        showMessage(inlineMessage, 'danger', 'Önce düzenlemek için bir manga seçin.');
        return;
      }
      const formData = new FormData(this);
      formData.set('manga_id', currentMangaId);
      $.ajax({
        url: 'api.php?action=create-chapter',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(inlineMessage, 'success', response.message || 'Bölüm oluşturuldu.');
          inlineForm[0].reset();
          inlineForm.find('input[type="file"]').val('').trigger('change');
          applyInlineStorageDefault();
          loadInlineChapters();
          loadMangaTable();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, inlineMessage));
    });

    inlineSort.on('change', loadInlineChapters);

    inlineTableBody.on('click', '.inline-chapter-edit', function () {
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
          $('#edit-chapter-files').val('');
          bindChapterEditForm(() => loadInlineChapters(), () => {
            loadInlineChapters();
            loadMangaTable();
          });
          const chapterModalEl = document.getElementById('chapter-edit-modal');
          if (chapterModalEl) {
            const modalInstance = bootstrap.Modal.getOrCreateInstance(chapterModalEl);
            modalInstance.show();
          }
        });
    });

    inlineTableBody.on('click', '.inline-chapter-delete', function () {
      const chapterId = $(this).closest('tr').data('chapter-id');
      if (!chapterId || !window.confirm('Bu bölümü silmek istediğinize emin misiniz?')) {
        return;
      }
      $.post('api.php?action=delete-chapter', { id: chapterId })
        .done((response) => {
          showMessage(inlineMessage, 'success', response.message || 'Bölüm silindi.');
          loadInlineChapters();
          loadMangaTable();
          refreshDashboardStats();
        })
        .fail((xhr) => handleError(xhr, inlineMessage));
    });

    refreshMangaOptions();
    loadMangaTable();
    disableInlineForm();
    applyQuickStorageDefault();
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

    bindChapterEditForm(loadChapters, () => {
      loadChapters();
      const modalInstance = bootstrap.Modal.getInstance(document.getElementById('chapter-edit-modal'));
      modalInstance?.hide();
    });

    fetchMangaOptions().then(() => {
      updateMangaSelects();
      loadChapters();
    });
  }

  function initMarket() {
    const offerForm = $('#market-form');
    const offerMessage = $('#market-form-message');
    const offerTableBody = $('#market-table tbody');
    const offerEmptyState = $('#market-table-empty');
    const offerModalElement = document.getElementById('market-edit-modal');
    const offerEditModal = offerModalElement ? new bootstrap.Modal(offerModalElement) : null;

    const paymentForm = $('#payment-method-form');
    const paymentMessage = $('#payment-method-message');
    const paymentTableBody = $('#payment-method-table tbody');
    const paymentEmptyState = $('#payment-method-empty');

    const orderTableBody = $('#market-order-table tbody');
    const orderEmptyState = $('#market-order-empty');
    const orderStatusFilter = $('#market-order-status');
    const orderRefreshButton = $('#refresh-market-orders');
    const orderModalElement = document.getElementById('market-order-modal');
    const orderModal = orderModalElement ? new bootstrap.Modal(orderModalElement) : null;
    const orderForm = $('#market-order-form');
    const orderMessage = $('#market-order-message');

    let marketOffers = [];
    let paymentMethods = [];

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

    function formatOrderStatus(status) {
      const map = {
        pending: ['bg-secondary', 'Beklemede'],
        processing: ['bg-info text-dark', 'İşleniyor'],
        completed: ['bg-success', 'Tamamlandı'],
        cancelled: ['bg-danger', 'İptal'],
      };
      const [cls, label] = map[status] || ['bg-secondary', status || 'Bilinmiyor'];
      return `<span class="badge ${cls}">${label}</span>`;
    }

    function renderOrderRow(order) {
      const buyer = escapeHtml(order.buyer_name || order.username || 'Misafir');
      const method = escapeHtml(order.payment_method_name || '—');
      const amount = `${Number(order.amount).toFixed(2)} ${escapeHtml(order.currency || '')}`;
      const reference = order.reference ? `<div class="small text-muted">Ref: ${escapeHtml(order.reference)}</div>` : '';
      return `
        <tr data-order-id="${order.id}">
          <td>
            <div>${formatDateTime(order.created_at)}</div>
            ${reference}
          </td>
          <td>${buyer}</td>
          <td>${method}</td>
          <td>${amount}</td>
          <td>${order.ki_amount}</td>
          <td>${formatOrderStatus(order.status)}</td>
          <td class="text-end">
            <button class="btn btn-outline-light btn-sm market-order-edit" type="button" data-order-id="${order.id}"><i class="bi bi-pencil"></i> Yönet</button>
          </td>
        </tr>`;
    }

    function syncOrderSelects() {
      const offerSelect = $('#market-order-offer');
      const methodSelect = $('#market-order-method');
      if (offerSelect.length) {
        const current = offerSelect.val();
        offerSelect.empty().append('<option value="">Seçim yapın (opsiyonel)</option>');
        marketOffers.forEach((offer) => {
          offerSelect.append(`<option value="${offer.id}">${escapeHtml(offer.title)} (${offer.ki_amount} Ki)</option>`);
        });
        if (current && offerSelect.find(`option[value="${current}"]`).length) {
          offerSelect.val(current);
        }
      }
      if (methodSelect.length) {
        const currentMethod = methodSelect.val();
        methodSelect.empty().append('<option value="">Seçim yapın</option>');
        paymentMethods.forEach((method) => {
          const suffix = Number(method.is_active) ? '' : ' (pasif)';
          methodSelect.append(`<option value="${method.id}">${escapeHtml(method.name)}${suffix}</option>`);
        });
        if (currentMethod && methodSelect.find(`option[value="${currentMethod}"]`).length) {
          methodSelect.val(currentMethod);
        }
      }
    }

    function loadOffers() {
      offerTableBody.html('<tr><td colspan="6" class="text-center text-muted">Yükleniyor...</td></tr>');
      offerEmptyState.addClass('d-none');
      $.getJSON('api.php', { action: 'list-market-offers' })
        .done(({ data }) => {
          offerTableBody.empty();
          marketOffers = data || [];
          if (!marketOffers.length) {
            offerEmptyState.removeClass('d-none');
            syncOrderSelects();
            return;
          }
          marketOffers.forEach((offer) => offerTableBody.append(renderOfferRow(offer)));
          syncOrderSelects();
        })
        .fail((xhr) => {
          offerTableBody.html('<tr><td colspan="6" class="text-center text-danger">Market teklifleri yüklenemedi.</td></tr>');
          handleError(xhr, offerMessage);
        });
    }

    function loadPaymentMethods() {
      if (!paymentForm.length) {
        return;
      }
      paymentTableBody.html('<tr><td colspan="5" class="text-center text-muted">Yükleniyor...</td></tr>');
      paymentEmptyState.addClass('d-none');
      $.getJSON('api.php', { action: 'list-payment-methods' })
        .done(({ data }) => {
          paymentTableBody.empty();
          paymentMethods = data || [];
          if (!paymentMethods.length) {
            paymentEmptyState.removeClass('d-none');
            syncOrderSelects();
            return;
          }
          paymentMethods.forEach((method) => {
            const badge = method.is_active ? '<span class="badge bg-success bg-opacity-75">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>';
            paymentTableBody.append(`
              <tr data-payment-id="${method.id}">
                <td>
                  <div class="fw-semibold">${escapeHtml(method.name)}</div>
                  <div class="small text-muted">${formatDateTime(method.updated_at || method.created_at)}</div>
                </td>
                <td>${escapeHtml(method.method_key)}</td>
                <td>${badge}</td>
                <td>${method.sort_order}</td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-light payment-method-edit" type="button"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-outline-danger payment-method-delete" type="button"><i class="bi bi-trash"></i></button>
                  </div>
                </td>
              </tr>`);
          });
          syncOrderSelects();
        })
        .fail((xhr) => {
          paymentTableBody.html('<tr><td colspan="5" class="text-center text-danger">Ödeme yöntemleri yüklenemedi.</td></tr>');
          handleError(xhr, paymentMessage);
        });
    }

    function loadMarketOrders() {
      if (!orderTableBody.length) {
        return;
      }
      orderTableBody.html('<tr><td colspan="7" class="text-center text-muted">Yükleniyor...</td></tr>');
      orderEmptyState.addClass('d-none');
      $.getJSON('api.php', {
        action: 'list-market-orders',
        status: orderStatusFilter.val(),
      })
        .done(({ data }) => {
          orderTableBody.empty();
          if (!data || !data.length) {
            orderEmptyState.removeClass('d-none');
            return;
          }
          data.forEach((order) => orderTableBody.append(renderOrderRow(order)));
        })
        .fail((xhr) => {
          orderTableBody.html('<tr><td colspan="7" class="text-center text-danger">Siparişler yüklenemedi.</td></tr>');
          handleError(xhr, orderMessage);
        });
    }

    offerForm.on('submit', function (event) {
      event.preventDefault();
      $.post('api.php?action=save-market-offer', offerForm.serialize())
        .done((response) => {
          showMessage(offerMessage, 'success', response.message || 'Teklif kaydedildi.');
          offerForm.trigger('reset');
          loadOffers();
        })
        .fail((xhr) => handleError(xhr, offerMessage));
    });

    offerTableBody.on('click', '.market-edit', function () {
      const offerId = $(this).closest('tr').data('offer-id');
      if (!offerId) {
        return;
      }
      $('#market-edit-message').empty();
      const offer = marketOffers.find((item) => Number(item.id) === Number(offerId));
      if (!offer) {
        return;
      }
      $('#edit-offer-id').val(offer.id);
      $('#edit-offer-title').val(offer.title);
      $('#edit-offer-ki').val(offer.ki_amount);
      $('#edit-offer-price').val(offer.price);
      $('#edit-offer-currency').val(offer.currency);
      $('#edit-offer-status').val(String(offer.is_active));
      offerEditModal?.show();
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
          offerEditModal?.hide();
          loadOffers();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    paymentForm.on('submit', function (event) {
      event.preventDefault();
      $.post('api.php?action=save-payment-method', paymentForm.serialize())
        .done((response) => {
          showMessage(paymentMessage, 'success', response.message || 'Ödeme yöntemi kaydedildi.');
          paymentForm.trigger('reset');
          $('#payment-method-active').prop('checked', true);
          loadPaymentMethods();
        })
        .fail((xhr) => handleError(xhr, paymentMessage));
    });

    $('#payment-method-reset').on('click', () => {
      paymentForm.trigger('reset');
      $('#payment-method-id').val('');
      $('#payment-method-active').prop('checked', true);
      paymentMessage.empty();
    });

    paymentTableBody.on('click', '.payment-method-edit', function () {
      const row = $(this).closest('tr');
      const methodId = row.data('payment-id');
      const method = paymentMethods.find((item) => Number(item.id) === Number(methodId));
      if (!method) {
        return;
      }
      paymentMessage.empty();
      $('#payment-method-id').val(method.id);
      $('#payment-method-name').val(method.name);
      $('#payment-method-key').val(method.method_key);
      $('#payment-method-order').val(method.sort_order);
      $('#payment-method-active').prop('checked', Number(method.is_active) === 1);
      $('#payment-method-instructions').val(method.instructions || '');
    });

    paymentTableBody.on('click', '.payment-method-delete', function () {
      const methodId = $(this).closest('tr').data('payment-id');
      if (!methodId || !window.confirm('Bu ödeme yöntemini silmek istediğinize emin misiniz?')) {
        return;
      }
      $.post('api.php?action=delete-payment-method', { id: methodId })
        .done((response) => {
          showMessage(paymentMessage, 'success', response.message || 'Ödeme yöntemi silindi.');
          loadPaymentMethods();
        })
        .fail((xhr) => handleError(xhr, paymentMessage));
    });

    orderStatusFilter.on('change', loadMarketOrders);
    orderRefreshButton.on('click', loadMarketOrders);

    $('#market-order-modal').on('show.bs.modal', (event) => {
      orderMessage.empty();
      const trigger = event.relatedTarget;
      if (trigger && trigger.dataset && trigger.dataset.orderMode === 'create') {
        orderForm.trigger('reset');
        $('#market-order-id').val('');
        $('#market-order-status-field').val('pending');
        $('#market-order-currency').val('TRY');
        orderForm.find('input[type="number"]').filter('[name="amount"]').val('0');
        orderForm.find('input[type="number"]').filter('[name="ki_amount"]').val('0');
        syncOrderSelects();
      }
    });

    $('#market-order-modal').on('hidden.bs.modal', () => {
      orderForm.trigger('reset');
      $('#market-order-id').val('');
      $('#market-order-status-field').val('pending');
      $('#market-order-currency').val('TRY');
      orderMessage.empty();
    });

    orderTableBody.on('click', '.market-order-edit', function () {
      const orderId = $(this).data('order-id') || $(this).closest('tr').data('order-id');
      if (!orderId) {
        return;
      }
      orderMessage.empty();
      $.getJSON('api.php', { action: 'get-market-order', id: orderId })
        .done(({ data }) => {
          syncOrderSelects();
          $('#market-order-id').val(data.id);
          $('#market-order-offer').val(data.offer_id ? String(data.offer_id) : '');
          $('#market-order-method').val(data.payment_method_id ? String(data.payment_method_id) : '');
          $('#market-order-user').val(data.user_id || '');
          $('#market-order-status-field').val(data.status || 'pending');
          $('#market-order-buyer').val(data.buyer_name || '');
          $('#market-order-email').val(data.buyer_email || '');
          $('#market-order-amount').val(data.amount || 0);
          $('#market-order-currency').val(data.currency || '');
          $('#market-order-ki').val(data.ki_amount || 0);
          $('#market-order-reference').val(data.reference || '');
          $('#market-order-notes').val(data.notes || '');
          orderModal?.show();
        });
    });

    orderForm.on('submit', function (event) {
      event.preventDefault();
      const action = $('#market-order-id').val() ? 'update-market-order' : 'save-market-order';
      $.post(`api.php?action=${action}`, orderForm.serialize())
        .done((response) => {
          showMessage(orderMessage, 'success', response.message || 'Sipariş kaydedildi.');
          loadMarketOrders();
          if (action === 'save-market-order') {
            orderForm.trigger('reset');
            $('#market-order-status-field').val('pending');
            $('#market-order-currency').val('TRY');
          }
        })
        .fail((xhr) => handleError(xhr, orderMessage));
    });

    loadOffers();
    loadPaymentMethods();
    loadMarketOrders();
  }

  function initSettings() {
    const siteForm = $('#site-settings-form');
    const siteMessage = $('#site-settings-message');
    const storageForm = $('#storage-settings-form');
    const storageMessage = $('#storage-settings-message');
    const smtpForm = $('#smtp-settings-form');
    const smtpMessage = $('#smtp-settings-message');

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

    smtpForm.on('submit', function (event) {
      event.preventDefault();
      $.post('api.php?action=update-smtp-settings', smtpForm.serialize())
        .done((response) => {
          const info = response.data || {};
          let message = response.message || 'SMTP ayarları kaydedildi.';
          if (info.smtp_password_updated) {
            message += ' Parola güncellendi.';
          } else if (info.smtp_password_cleared) {
            message += ' Parola temizlendi.';
          }
          showMessage(smtpMessage, 'success', message);
        })
        .fail((xhr) => handleError(xhr, smtpMessage));
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
    const roleForm = $('#role-form');
    const roleMessage = $('#role-form-message');
    const roleReset = $('#role-reset');
    const roleTableBody = $('#role-table tbody');
    const roleTableEmpty = $('#role-table-empty');
    const roleCurrentField = $('#role-current-key');
    const roleKeyField = $('#role-key');
    const roleLabelField = $('#role-label');
    const roleCapabilitiesField = $('#role-capabilities');
    const roleOrderField = $('#role-order');
    const roleSubmitButton = roleForm.find('button[type="submit"]');
    let roles = [];
    let roleMap = {};
    let rolesLoaded = false;

    function renderCapabilities(capabilities) {
      if (!Array.isArray(capabilities) || !capabilities.length) {
        return '<span class="text-muted">—</span>';
      }
      return capabilities
        .map((capability) => `<span class="badge bg-secondary bg-opacity-50 text-uppercase me-1 mb-1">${escapeHtml(capability)}</span>`)
        .join('');
    }

    function renderRoleOptions() {
      const select = createUserForm.find('select[name="role"]');
      if (!select.length) {
        return;
      }
      const previous = select.val();
      select.empty();
      roles.forEach((role) => {
        select.append(`<option value="${escapeHtml(role.role_key)}">${escapeHtml(role.label)}</option>`);
      });
      if (previous && roleMap[previous]) {
        select.val(previous);
      } else if (roleMap.member) {
        select.val('member');
      } else if (roles[0]) {
        select.val(roles[0].role_key);
      }
    }

    function renderRoleTable() {
      roleTableBody.empty();
      if (!roles.length) {
        roleTableEmpty.removeClass('d-none');
        return;
      }
      roleTableEmpty.addClass('d-none');
      roles.forEach((role) => {
        roleTableBody.append(`
          <tr data-role-key="${escapeHtml(role.role_key)}">
            <td>
              <div class="fw-semibold">${escapeHtml(role.label)}</div>
              ${role.is_system ? '<div class="small text-muted">Sistem rolü</div>' : ''}
            </td>
            <td><code>${escapeHtml(role.role_key)}</code></td>
            <td>
              <div class="d-flex flex-wrap gap-1">${renderCapabilities(role.capabilities)}</div>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-light role-edit" type="button"><i class="bi bi-pencil"></i></button>
                ${role.is_system ? '' : '<button class="btn btn-outline-danger role-delete" type="button"><i class="bi bi-trash"></i></button>'}
              </div>
            </td>
          </tr>`);
      });
    }

    function loadRoles(next) {
      $.getJSON('api.php', { action: 'list-roles' })
        .done(({ data }) => {
          roles = Array.isArray(data) ? data : [];
          roleMap = roles.reduce((acc, role) => ({ ...acc, [role.role_key]: role }), {});
          rolesLoaded = true;
          renderRoleOptions();
          renderRoleTable();
          if (typeof next === 'function') {
            next();
          }
        })
        .fail((xhr) => handleError(xhr, roleMessage));
    }

    function renderUserRow(user) {
      const initial = (user.username || '?').charAt(0).toUpperCase();
      const avatar = user.avatar_url
        ? `<img src="${user.avatar_url}" alt="${escapeHtml(user.username)}" class="rounded-circle me-2" width="36" height="36">`
        : `<div class="avatar-placeholder rounded-circle me-2">${initial}</div>`;
      const active = Number(user.is_active) === 1;
      const roleKey = user.role && roleMap[user.role] ? user.role : user.role || (roleMap.member ? 'member' : roles[0]?.role_key || '');
      let options = roles
        .map((role) => `<option value="${escapeHtml(role.role_key)}" ${role.role_key === roleKey ? 'selected' : ''}>${escapeHtml(role.label)}</option>`)
        .join('');
      if ((!options || !options.length) && roleKey) {
        options = `<option value="${escapeHtml(roleKey)}" selected>${escapeHtml(user.role_label || roleKey)}</option>`;
      } else if (roleKey && !roleMap[roleKey]) {
        options += `<option value="${escapeHtml(roleKey)}" selected>${escapeHtml(user.role_label || roleKey)}</option>`;
      }
      const capabilitiesHtml = renderCapabilities(roleMap[roleKey]?.capabilities || user.role_capabilities || []);
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
              ${options}
            </select>
            <div class="user-role-capabilities mt-2">${capabilitiesHtml}</div>
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
      if (!rolesLoaded) {
        loadRoles(loadUsers);
        return;
      }
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

    function resetRoleForm() {
      roleForm[0]?.reset();
      roleCurrentField.val('');
      roleKeyField.prop('disabled', false);
      roleSubmitButton.html('<i class="bi bi-save me-1"></i>Rolü Kaydet');
      roleMessage.empty();
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
          renderRoleOptions();
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
          row.find('.user-role-capabilities').html(renderCapabilities(roleMap[role]?.capabilities || []));
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

    userTableBody.on('change', '.user-role', function () {
      const selectedRole = $(this).val();
      const capabilities = renderCapabilities(roleMap[selectedRole]?.capabilities || []);
      $(this).closest('tr').find('.user-role-capabilities').html(capabilities);
    });

    roleForm.on('submit', function (event) {
      event.preventDefault();
      const currentKey = roleCurrentField.val();
      const payload = {
        role_key: currentKey ? currentKey : roleKeyField.val(),
        label: roleLabelField.val(),
        capabilities: roleCapabilitiesField.val(),
        sort_order: roleOrderField.val(),
      };
      let action = 'create-role';
      if (currentKey) {
        action = 'update-role';
        payload.new_role_key = roleKeyField.val();
      }
      $.post(`api.php?action=${action}`, payload)
        .done((response) => {
          showMessage(roleMessage, 'success', response.message || 'Rol kaydedildi.');
          resetRoleForm();
          loadRoles(loadUsers);
        })
        .fail((xhr) => handleError(xhr, roleMessage));
    });

    roleReset.on('click', function (event) {
      event.preventDefault();
      resetRoleForm();
    });

    roleTableBody.on('click', '.role-edit', function () {
      const key = $(this).closest('tr').data('role-key');
      const role = roleMap[key];
      if (!role) {
        return;
      }
      roleCurrentField.val(role.role_key);
      roleKeyField.val(role.role_key);
      roleLabelField.val(role.label);
      roleCapabilitiesField.val((role.capabilities || []).join(', '));
      roleOrderField.val(role.sort_order);
      roleKeyField.prop('disabled', Number(role.is_system) === 1);
      roleSubmitButton.html('<i class="bi bi-save me-1"></i>Rolü Güncelle');
      roleMessage.empty();
      roleLabelField.trigger('focus');
    });

    roleTableBody.on('click', '.role-delete', function () {
      const key = $(this).closest('tr').data('role-key');
      if (!key || !window.confirm('Bu rolü silmek istediğinize emin misiniz?')) {
        return;
      }
      $.post('api.php?action=delete-role', { role_key: key })
        .done((response) => {
          showMessage(roleMessage, 'success', response.message || 'Rol silindi.');
          if (roleCurrentField.val() === key) {
            resetRoleForm();
          }
          loadRoles(loadUsers);
        })
        .fail((xhr) => handleError(xhr, roleMessage));
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

    loadRoles(loadUsers);
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

  function initPosts() {
    const form = $('#post-form');
    const message = $('#post-form-message');
    const resetButton = $('#post-reset');
    const tableBody = $('#post-table tbody');
    const emptyState = $('#post-table-empty');
    const statusFilter = $('#post-status-filter');
    const searchInput = $('#post-search');
    const refreshButton = $('#post-refresh');
    const categoryForm = $('#category-form');
    const categoryMessage = $('#category-message');
    const categoryList = $('#category-list');
    const tagForm = $('#tag-form');
    const tagMessage = $('#tag-message');
    const tagList = $('#tag-list');
    const authorSelect = $('#post-author');
    const tagsInput = $('#post-tags');
    const categorySelect = $('#post-categories');
    let searchTimer = null;
    let editingPostId = null;
    let categoryMap = {};
    let tagMap = {};

    function renderPostRow(post) {
      const categories = (post.categories || []).map((slug) => escapeHtml(categoryMap[slug]?.name || slug)).join(', ');
      const author = post.author_id ? `#${post.author_id}` : 'Belirtilmemiş';
      return `
        <tr data-post-id="${post.id}">
          <td>
            <div class="fw-semibold">${escapeHtml(post.title)}</div>
            <div class="small text-muted">${escapeHtml(post.slug)}</div>
          </td>
          <td>${post.status === 'published' ? '<span class="badge bg-success">Yayınlandı</span>' : '<span class="badge bg-secondary">Taslak</span>'}</td>
          <td>${categories || '<span class="text-muted">—</span>'}</td>
          <td>${escapeHtml(author)}</td>
          <td>${formatDateTime(post.updated_at || post.created_at)}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-outline-light post-edit" type="button"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-outline-danger post-delete" type="button"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }

    function populateCategoryOptions() {
      if (!categorySelect.length) {
        return;
      }
      const currentSelection = categorySelect.val();
      categorySelect.empty();
      Object.values(categoryMap)
        .sort((a, b) => a.name.localeCompare(b.name, 'tr'))
        .forEach((category) => {
          categorySelect.append(`<option value="${escapeHtml(category.slug)}">${escapeHtml(category.name)}</option>`);
        });
      if (currentSelection) {
        categorySelect.val(currentSelection);
      }
    }

    function renderTaxonomyItems(container, items, type) {
      container.empty();
      if (!items.length) {
        container.append('<div class="text-muted small">Henüz kayıt yok.</div>');
        return;
      }
      items.forEach((item) => {
        container.append(`
          <div class="taxonomy-item" data-taxonomy="${type}" data-id="${item.id}">
            <div class="d-flex justify-content-between align-items-start gap-3">
              <div>
                <div class="fw-semibold">${escapeHtml(item.name)}</div>
                <div class="small text-muted">${escapeHtml(item.slug)}</div>
              </div>
              <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-light taxonomy-edit" type="button"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger taxonomy-delete" type="button"><i class="bi bi-trash"></i></button>
              </div>
            </div>
            ${item.description ? `<p class="small text-muted mb-0 mt-2">${escapeHtml(item.description)}</p>` : ''}
          </div>`);
      });
    }

    function loadTaxonomy(type) {
      const targetList = type === 'category' ? categoryList : tagList;
      const targetMessage = type === 'category' ? categoryMessage : tagMessage;
      $.getJSON('api.php', { action: 'list-taxonomies', taxonomy: type })
        .done(({ data }) => {
          const items = data || [];
          if (type === 'category') {
            categoryMap = items.reduce((acc, item) => ({ ...acc, [item.slug]: item }), {});
            populateCategoryOptions();
          } else {
            tagMap = items.reduce((acc, item) => ({ ...acc, [item.slug]: item }), {});
          }
          renderTaxonomyItems(targetList, items, type);
          if (targetMessage.length) {
            targetMessage.empty();
          }
        })
        .fail((xhr) => handleError(xhr, targetMessage));
    }

    function loadPosts() {
      tableBody.html('<tr><td colspan="6" class="text-center text-muted">Yükleniyor...</td></tr>');
      emptyState.addClass('d-none');
      $.getJSON('api.php', {
        action: 'list-posts',
        status: statusFilter.val(),
        search: searchInput.val(),
      })
        .done(({ data }) => {
          tableBody.empty();
          if (!data || !data.length) {
            emptyState.removeClass('d-none');
            return;
          }
          data.forEach((post) => tableBody.append(renderPostRow(post)));
        })
        .fail((xhr) => {
          tableBody.html('<tr><td colspan="6" class="text-center text-danger">Yazılar alınamadı.</td></tr>');
          handleError(xhr, message);
        });
    }

    function resetPostForm() {
      editingPostId = null;
      form[0].reset();
      categorySelect.val([]);
      $('#post-id').val('');
      message.empty();
    }

    function loadAuthors() {
      if (!authorSelect.length) {
        return;
      }
      $.getJSON('api.php', { action: 'list-users' }).done(({ data }) => {
        authorSelect.find('option:not(:first)').remove();
        (data || []).forEach((user) => {
          authorSelect.append(`<option value="${user.id}">${escapeHtml(user.username)}</option>`);
        });
      });
    }

    form.on('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(this);
      const id = editingPostId;
      if (id) {
        formData.append('id', String(id));
      }
      $.ajax({
        url: `api.php?action=${id ? 'update-post' : 'create-post'}`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Yazı kaydedildi.');
          resetPostForm();
          loadPosts();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    resetButton.on('click', resetPostForm);

    statusFilter.on('change', loadPosts);
    refreshButton.on('click', loadPosts);
    searchInput.on('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(loadPosts, 250);
    });

    tableBody.on('click', '.post-edit', function () {
      const postId = $(this).closest('tr').data('post-id');
      if (!postId) {
        return;
      }
      $.getJSON('api.php', { action: 'get-post', id: postId })
        .done(({ data }) => {
          if (!data) {
            return;
          }
          editingPostId = data.id;
          $('#post-id').val(data.id);
          $('#post-title').val(data.title);
          $('#post-slug').val(data.slug);
          $('#post-status').val(data.status);
          $('#post-featured').val(data.featured_image);
          $('#post-excerpt').val(data.excerpt);
          $('#post-content').val(data.content);
          $('#post-author').val(data.author_id || '');
          categorySelect.val(data.categories || []);
          tagsInput.val((data.tags || []).join(', '));
          message.empty();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    tableBody.on('click', '.post-delete', function () {
      const postId = $(this).closest('tr').data('post-id');
      if (!postId || !window.confirm('Bu yazıyı silmek istediğinize emin misiniz?')) {
        return;
      }
      $.post('api.php?action=delete-post', { id: postId })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Yazı silindi.');
          loadPosts();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    function handleTaxonomySubmit(type, formEl, messageEl) {
      formEl.on('submit', function (event) {
        event.preventDefault();
        const idField = type === 'category' ? $('#category-id') : $('#tag-id');
        const nameField = type === 'category' ? $('#category-name') : $('#tag-name');
        const slugField = type === 'category' ? $('#category-slug') : $('#tag-slug');
        const descField = type === 'category' ? $('#category-description') : null;
        const payload = {
          taxonomy: type,
          name: nameField.val(),
          slug: slugField.val(),
        };
        const currentId = Number(idField.val());
        if (descField) {
          payload.description = descField.val();
        }
        if (currentId) {
          payload.id = currentId;
        }
        $.post('api.php?action=save-taxonomy', payload)
          .done((response) => {
            showMessage(messageEl, 'success', response.message || 'Kayıt güncellendi.');
            idField.val('');
            formEl[0].reset();
            loadTaxonomy(type);
          })
          .fail((xhr) => handleError(xhr, messageEl));
      });
    }

    handleTaxonomySubmit('category', categoryForm, categoryMessage);
    handleTaxonomySubmit('tag', tagForm, tagMessage);

    categoryList.on('click', '.taxonomy-edit', function () {
      const item = $(this).closest('.taxonomy-item');
      const id = item.data('id');
      const slug = item.find('.small.text-muted').text();
      const name = item.find('.fw-semibold').text();
      $('#category-id').val(id);
      $('#category-name').val(name);
      $('#category-slug').val(slug);
      const desc = item.find('p.small').text();
      $('#category-description').val(desc);
      categoryMessage.empty();
    });

    tagList.on('click', '.taxonomy-edit', function () {
      const item = $(this).closest('.taxonomy-item');
      $('#tag-id').val(item.data('id'));
      $('#tag-name').val(item.find('.fw-semibold').text());
      $('#tag-slug').val(item.find('.small.text-muted').text());
      tagMessage.empty();
    });

    function handleTaxonomyDelete(listEl, type, messageEl) {
      listEl.on('click', '.taxonomy-delete', function () {
        const item = $(this).closest('.taxonomy-item');
        const id = item.data('id');
        if (!id || !window.confirm('Bu kaydı silmek istediğinize emin misiniz?')) {
          return;
        }
        $.post('api.php?action=delete-taxonomy', { taxonomy: type, id })
          .done((response) => {
            showMessage(messageEl, 'success', response.message || 'Kayıt silindi.');
            loadTaxonomy(type);
          })
          .fail((xhr) => handleError(xhr, messageEl));
      });
    }

    handleTaxonomyDelete(categoryList, 'category', categoryMessage);
    handleTaxonomyDelete(tagList, 'tag', tagMessage);

    $('#category-reset').on('click', () => {
      $('#category-form')[0].reset();
      $('#category-id').val('');
      categoryMessage.empty();
    });

    $('#tag-reset').on('click', () => {
      $('#tag-form')[0].reset();
      $('#tag-id').val('');
      tagMessage.empty();
    });

    loadAuthors();
    loadTaxonomy('category');
    loadTaxonomy('tag');
    loadPosts();
  }

  function initMedia() {
    const form = $('#media-form');
    const message = $('#media-form-message');
    const tableBody = $('#media-table tbody');
    const emptyState = $('#media-empty');
    const searchInput = $('#media-search');
    const refreshButton = $('#media-refresh');
    let searchTimer = null;

    function renderMediaRow(item) {
      const isImage = item.mime_type && item.mime_type.startsWith('image/');
      const preview = isImage
        ? `<img src="${item.full_url}" alt="${escapeHtml(item.title || item.filename)}" class="rounded" style="width:64px;height:64px;object-fit:cover;">`
        : '<div class="bg-secondary bg-opacity-25 rounded d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;"><i class="bi bi-file-earmark-text"></i></div>';
      const size = item.size_bytes ? `${(item.size_bytes / 1024).toFixed(1)} KB` : '—';
      return `
        <tr data-media-id="${item.id}">
          <td>${preview}</td>
          <td>
            <div class="fw-semibold">${escapeHtml(item.title || item.filename)}</div>
            <a href="${item.full_url}" target="_blank" rel="noopener" class="small text-decoration-none">${escapeHtml(item.filename)}</a>
          </td>
          <td>${escapeHtml(item.mime_type)}</td>
          <td>${size}</td>
          <td>${item.created_by || '—'}</td>
          <td>${formatDateTime(item.created_at)}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm" role="group">
              <a class="btn btn-outline-light" href="${item.full_url}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i></a>
              <button class="btn btn-outline-danger media-delete" type="button"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }

    function loadMedia() {
      tableBody.html('<tr><td colspan="7" class="text-center text-muted">Yükleniyor...</td></tr>');
      emptyState.addClass('d-none');
      $.getJSON('api.php', {
        action: 'list-media',
        search: searchInput.val(),
      })
        .done(({ data }) => {
          tableBody.empty();
          if (!data || !data.length) {
            emptyState.removeClass('d-none');
            return;
          }
          data.forEach((item) => tableBody.append(renderMediaRow(item)));
        })
        .fail((xhr) => {
          tableBody.html('<tr><td colspan="7" class="text-center text-danger">Medya listesi alınamadı.</td></tr>');
          handleError(xhr, message);
        });
    }

    form.on('submit', function (event) {
      event.preventDefault();
      const formData = new FormData(this);
      $.ajax({
        url: 'api.php?action=upload-media',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Dosya yüklendi.');
          form[0].reset();
          loadMedia();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    refreshButton.on('click', loadMedia);
    searchInput.on('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(loadMedia, 250);
    });

    tableBody.on('click', '.media-delete', function () {
      const mediaId = $(this).closest('tr').data('media-id');
      if (!mediaId || !window.confirm('Bu dosyayı silmek istediğinize emin misiniz?')) {
        return;
      }
      $.post('api.php?action=delete-media', { id: mediaId })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Dosya silindi.');
          loadMedia();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    loadMedia();
  }

  function initComments() {
    const tableBody = $('#comment-table tbody');
    const emptyState = $('#comment-empty');
    const statusFilter = $('#comment-status-filter');
    const searchInput = $('#comment-search');
    const refreshButton = $('#comment-refresh');
    const message = $('#comment-message');
    const purgeButton = $('#purge-comments');
    let searchTimer = null;

    function renderCommentRow(comment, status) {
      const mangaLabel = comment.manga_title ? `<span class="badge bg-info text-dark">${escapeHtml(comment.manga_title)}</span>` : '<span class="text-muted">—</span>';
      return `
        <tr data-comment-id="${comment.id}">
          <td>
            <div class="fw-semibold">${escapeHtml(comment.body.substring(0, 120))}${comment.body.length > 120 ? '…' : ''}</div>
          </td>
          <td>
            <div class="fw-semibold">${escapeHtml(comment.username)}</div>
            <div class="small text-muted">${escapeHtml(comment.email || '')}</div>
          </td>
          <td>${mangaLabel}</td>
          <td>${formatDateTime(comment.created_at)}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm" role="group">
              ${status === 'trash'
                ? '<button class="btn btn-outline-light comment-restore" type="button"><i class="bi bi-arrow-counterclockwise"></i></button>'
                : '<button class="btn btn-outline-danger comment-trash" type="button"><i class="bi bi-trash"></i></button>'}
            </div>
          </td>
        </tr>`;
    }

    function loadComments() {
      tableBody.html('<tr><td colspan="5" class="text-center text-muted">Yükleniyor...</td></tr>');
      emptyState.addClass('d-none');
      const status = statusFilter.val();
      $.getJSON('api.php', {
        action: 'list-comments-admin',
        status,
        search: searchInput.val(),
      })
        .done(({ data }) => {
          tableBody.empty();
          if (!data || !data.length) {
            emptyState.removeClass('d-none');
            return;
          }
          data.forEach((comment) => tableBody.append(renderCommentRow(comment, status)));
        })
        .fail((xhr) => handleError(xhr, message));
    }

    statusFilter.on('change', loadComments);
    refreshButton.on('click', loadComments);
    searchInput.on('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(loadComments, 250);
    });

    tableBody.on('click', '.comment-trash', function () {
      const id = $(this).closest('tr').data('comment-id');
      if (!id) {
        return;
      }
      $.post('api.php?action=trash-comment', { id })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Yorum çöp kutusuna taşındı.');
          loadComments();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    tableBody.on('click', '.comment-restore', function () {
      const id = $(this).closest('tr').data('comment-id');
      if (!id) {
        return;
      }
      $.post('api.php?action=restore-comment', { id })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Yorum geri yüklendi.');
          loadComments();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    purgeButton.on('click', function (event) {
      event.preventDefault();
      if (!window.confirm('Çöp kutusundaki tüm yorumları kalıcı olarak silmek istiyor musunuz?')) {
        return;
      }
      $.post('api.php?action=purge-comments')
        .done((response) => {
          showMessage(message, 'success', response.message || 'Çöp kutusu temizlendi.');
          loadComments();
        })
        .fail((xhr) => handleError(xhr, message));
    });

    loadComments();
  }

  function initPlugins() {
    const list = $('#plugin-list');
    const message = $('#plugin-message');
    let plugins = [];

    function renderPlugins() {
      list.empty();
      if (!plugins.length) {
        list.append('<div class="text-muted">Yüklü eklenti bulunamadı.</div>');
        return;
      }
      plugins.forEach((plugin) => {
        list.append(`
          <div class="col-md-6" data-plugin-key="${plugin.key}">
            <div class="card admin-card h-100">
              <div class="card-body d-flex flex-column gap-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div>
                    <h3 class="h5 mb-0">${escapeHtml(plugin.name)}</h3>
                    <div class="small text-muted">v${escapeHtml(plugin.version)} · ${escapeHtml(plugin.category)}</div>
                  </div>
                  <div class="form-check form-switch">
                    <input class="form-check-input plugin-toggle" type="checkbox" ${plugin.is_active ? 'checked' : ''}>
                  </div>
                </div>
                <p class="text-muted small mb-0 flex-grow-1">${escapeHtml(plugin.description)}</p>
                <div class="d-flex justify-content-between align-items-center small text-muted">
                  <span>Geliştirici: ${escapeHtml(plugin.author || 'Topluluk')}</span>
                  <span>${plugin.is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>'}</span>
                </div>
              </div>
            </div>
          </div>`);
      });
    }

    function savePlugins() {
      const activeKeys = plugins.filter((plugin) => plugin.is_active).map((plugin) => plugin.key);
      $.post('api.php?action=save-plugins', { active: JSON.stringify(activeKeys) })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Eklenti ayarları güncellendi.');
        })
        .fail((xhr) => handleError(xhr, message));
    }

    list.on('change', '.plugin-toggle', function () {
      const key = $(this).closest('[data-plugin-key]').data('plugin-key');
      plugins = plugins.map((plugin) => (plugin.key === key ? { ...plugin, is_active: this.checked } : plugin));
      savePlugins();
      renderPlugins();
    });

    $.getJSON('api.php', { action: 'list-plugins' })
      .done(({ data }) => {
        plugins = data || [];
        renderPlugins();
      })
      .fail((xhr) => handleError(xhr, message));
  }

  function initTools() {
    const message = $('#tool-message');
    $('#tool-list').on('click', '[data-tool]', function () {
      const tool = $(this).data('tool');
      if (!tool) {
        return;
      }
      const button = $(this);
      button.prop('disabled', true);
      $.post('api.php?action=run-tool', { tool })
        .done((response) => {
          showMessage(message, 'success', response.message || 'Araç başarıyla çalıştırıldı.');
        })
        .fail((xhr) => handleError(xhr, message))
        .always(() => button.prop('disabled', false));
    });
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
  if (page === 'posts') {
    initPosts();
  }
  if (page === 'media') {
    initMedia();
  }
  if (page === 'pages') {
    initPages();
  }
  if (page === 'comments') {
    initComments();
  }
  if (page === 'menus') {
    initMenus();
  }
  if (page === 'plugins') {
    initPlugins();
  }
  if (page === 'tools') {
    initTools();
  }
  if (page === 'community') {
    initCommunity();
  }
  if (page === 'integrations') {
    initIntegrations();
  }
});
