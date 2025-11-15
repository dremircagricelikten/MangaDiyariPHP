$(function () {
  const widgets = window.appWidgets || {};
  const popularWidget = widgets.popular_slider || null;
  const latestWidget = widgets.latest_updates || null;

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

  function loadMangaList(params = {}) {
    $.getJSON('api.php', Object.assign({ action: 'list' }, params))
      .done(({ data }) => {
        const container = $('#manga-list');
        container.empty();

        if (!data.length) {
          container.append('<div class="empty-state col-span-full">Henüz sonuç bulunamadı.</div>');
          return;
        }

        data.forEach((manga) => {
          container.append(renderMangaCard(manga));
        });
      })
      .fail((xhr) => {
        container.html(`<div class="empty-state empty-state--danger col-span-full">Listeler alınamadı: ${xhr.responseJSON?.error || xhr.statusText}</div>`);
      });
  }

  function formatStatus(status) {
    return (
      {
        ongoing: 'Devam Ediyor',
        completed: 'Tamamlandı',
        hiatus: 'Ara Verildi',
      }[status] || 'Bilinmiyor'
    );
  }

  function renderMangaCard(manga) {
    const cover = manga.cover_image || 'https://placehold.co/400x600?text=Manga';
    const url = `manga.php?slug=${encodeURIComponent(manga.slug)}`;
    const author = manga.author || 'Bilinmiyor';
    const genres = (manga.genres || '')
      .split(',')
      .map((genre) => genre.trim())
      .filter((genre) => genre.length)
      .slice(0, 3)
      .join(', ');
    return `
      <article class="manga-card transition duration-300 ease-out hover:-translate-y-1 hover:shadow-xl">
        <div class="manga-card__media" style="background-image:url('${cover}')">
          <span class="manga-card__badge">${formatStatus(manga.status)}</span>
          <div class="manga-card__overlay">
            <h3 class="manga-card__title"><a href="${url}">${escapeHtml(manga.title)}</a></h3>
            <span class="manga-card__overlay-meta"><i class="bi bi-person"></i> ${escapeHtml(author)}</span>
          </div>
        </div>
        <div class="manga-card__body">
          <p class="manga-card__description text-truncate-3">${escapeHtml(manga.description || 'Açıklama bulunmuyor.')}</p>
          <div class="manga-card__footer">
            <span class="manga-card__genres">${escapeHtml(genres || 'Tür belirtilmedi')}</span>
            <a href="${url}" class="btn btn-outline btn-sm">Seri Detayı</a>
          </div>
        </div>
      </article>`;
  }

  function renderFeaturedHighlight(data) {
    const container = $('#featured-highlight');
    if (!container.length) {
      return;
    }

    container.empty();

    if (!data.length) {
    container.append('<div class="feature-placeholder">Popüler seriler yakında burada yer alacak.</div>');
      return;
    }

    const featured = data[0];
    const cover = featured.cover_image || 'https://placehold.co/600x900?text=Manga';
    const url = `manga.php?slug=${encodeURIComponent(featured.slug)}`;
    const title = escapeHtml(featured.title || 'Seri');
    const authorName = escapeHtml(featured.author || 'Bilinmiyor');
    const artist = escapeHtml(featured.artist || featured.author || 'Bilinmiyor');
    const genres = escapeHtml(featured.genres || 'Belirtilmedi');
    const description = escapeHtml(
      truncate(featured.description || 'Topluluğun sevdiği popüler serilerden biri.', 220)
    );

    container.html(`
      <article class="hero-feature">
        <a class="hero-feature__media" href="${url}" aria-label="${title}">
          <img src="${cover}" alt="${title}" loading="lazy">
        </a>
        <div class="hero-feature__content">
          <span class="hero-feature__badge">${formatStatus(featured.status)}</span>
          <h3 class="hero-feature__title"><a href="${url}">${title}</a></h3>
          <p class="hero-feature__description">${description}</p>
          <dl class="hero-feature__meta">
            <div>
              <dt>Yazar</dt>
              <dd>${authorName}</dd>
            </div>
            <div>
              <dt>Çizer</dt>
              <dd>${artist}</dd>
            </div>
            <div>
              <dt>Tür</dt>
              <dd>${genres}</dd>
            </div>
          </dl>
          <a class="btn btn-primary btn-sm" href="${url}">
            Seriye Git <i class="bi bi-arrow-right ml-2"></i>
          </a>
        </div>
      </article>
    `);
  }

  function renderFeaturedItem(manga) {
    const cover = manga.cover_image || 'https://placehold.co/160x220?text=Manga';
    const url = `manga.php?slug=${encodeURIComponent(manga.slug)}`;
    return `
      <a class="featured-item" href="${url}">
        <span class="featured-item__media" style="background-image:url('${cover}')"></span>
        <span class="featured-item__content">
          <span class="featured-item__title">${escapeHtml(manga.title)}</span>
          <span class="featured-item__meta">${formatStatus(manga.status)} · ${escapeHtml(
            manga.author || 'Bilinmiyor'
          )}</span>
        </span>
      </a>`;
  }

  function renderFeaturedRail(data) {
    const container = $('#featured-rail');
    if (!container.length) {
      return;
    }

    container.empty();

    if (!data.length) {
    container.append('<div class="empty-state">Öne çıkan seri bulunamadı.</div>');
      return;
    }

    data.slice(0, 6).forEach((manga) => {
      container.append(renderFeaturedItem(manga));
    });
  }

  function renderFeaturedGrid(data) {
    const container = $('#featured-grid');
    if (!container.length) {
      return;
    }

    container.empty();

    if (!data.length) {
      container.append('<div class="empty-state col-span-full">Popüler seri bulunamadı.</div>');
      return;
    }

    data.forEach((manga) => container.append(renderMangaCard(manga)));
  }

  function renderFeaturedSidebar(data) {
    const container = $('#featured-sidebar');
    if (!container.length) {
      return;
    }

    container.empty();

    if (!data.length) {
      container.append('<div class="empty-state">Popüler seri bulunamadı.</div>');
      return;
    }

    data.slice(0, 6).forEach((manga) => {
      const cover = manga.cover_image || 'https://placehold.co/120x160?text=Manga';
      const url = `manga.php?slug=${encodeURIComponent(manga.slug)}`;
      container.append(`
        <a class="sidebar-item" href="${url}">
          <span class="sidebar-item__media" style="background-image:url('${cover}')"></span>
          <span class="sidebar-item__content">
            <span class="sidebar-item__title">${escapeHtml(manga.title)}</span>
            <span class="sidebar-item__meta">${formatStatus(manga.status)}</span>
          </span>
        </a>`);
    });
  }

  function formatDate(value) {
    if (!value) {
      return '';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return value;
    }
    return new Intl.DateTimeFormat('tr-TR', { dateStyle: 'medium' }).format(date);
  }

  function renderChapterCard(chapter) {
    const cover = chapter.cover_image || 'https://placehold.co/400x600?text=Manga';
    const mangaUrl = `manga.php?slug=${encodeURIComponent(chapter.manga_slug)}`;
    const chapterUrl = `chapter.php?slug=${encodeURIComponent(chapter.manga_slug)}&chapter=${encodeURIComponent(chapter.number)}`;
    const chapterTitle = chapter.title ? escapeHtml(chapter.title) : `Bölüm ${escapeHtml(chapter.number)}`;
    const seriesTitle = escapeHtml(chapter.manga_title || 'Seri');
    return `
      <article class="update-card transition duration-300 ease-out hover:-translate-y-1 hover:shadow-xl">
        <div class="update-card__media" style="background-image:url('${cover}')"></div>
        <div class="update-card__body">
          <div class="update-card__series">${seriesTitle}</div>
          <div class="update-card__chapter">Bölüm ${escapeHtml(chapter.number)}</div>
          <p class="update-card__title text-truncate-3">${chapterTitle}</p>
          <div class="update-card__meta">
            <span><i class="bi bi-calendar-event"></i> ${formatDate(chapter.created_at)}</span>
            ${chapter.ki_cost > 0 ? '<span class="badge-premium">Premium</span>' : ''}
          </div>
          <div class="update-card__actions">
            <a href="${mangaUrl}" class="btn btn-outline btn-sm">Seri</a>
            <a href="${chapterUrl}" class="btn btn-primary btn-sm">Oku</a>
          </div>
        </div>
      </article>`;
  }

  function renderLatestList(chapters) {
    const container = $('#latest-list');
    if (!container.length) {
      return;
    }
    container.empty();

    if (!chapters.length) {
      container.append('<div class="empty-state col-span-full">Yeni bölüm bulunamadı.</div>');
      return;
    }

    chapters.forEach((chapter) => container.append(renderChapterCard(chapter)));
  }

  function renderLatestSidebar(chapters) {
    const container = $('#latest-sidebar');
    if (!container.length) {
      return;
    }

    container.empty();

    if (!chapters.length) {
      container.append('<div class="empty-state">Yeni bölüm bulunamadı.</div>');
      return;
    }

    chapters.slice(0, 8).forEach((chapter) => {
      const url = `chapter.php?slug=${encodeURIComponent(chapter.manga_slug)}&chapter=${encodeURIComponent(chapter.number)}`;
      const seriesTitle = escapeHtml(chapter.manga_title || 'Seri');
      container.append(`
        <a class="sidebar-item" href="${url}">
          <span class="sidebar-item__content">
            <span class="sidebar-item__title">${seriesTitle}</span>
            <span class="sidebar-item__meta">Bölüm ${escapeHtml(chapter.number)}</span>
          </span>
          ${chapter.ki_cost > 0 ? '<span class="badge-premium">Premium</span>' : ''}
        </a>`);
    });
  }

  function initPopular(widget) {
    const config = widget.config || {};
    const sortSelect = $('#popular-sort');
    const statusSelect = $('#popular-status');

    if (config.sort && sortSelect.find(`[value="${config.sort}"]`).length) {
      sortSelect.val(config.sort);
    }
    if (config.status && statusSelect.find(`[value="${config.status}"]`).length) {
      statusSelect.val(config.status);
    }

    function loadPopular() {
      const params = {
        action: 'popular',
        limit: config.limit || 6,
        sort: sortSelect.length ? sortSelect.val() : config.sort || 'random',
        status: statusSelect.length ? statusSelect.val() : config.status || '',
      };

      $.getJSON('api.php', params)
        .done(({ data }) => {
          renderFeaturedHighlight(data);
          renderFeaturedRail(data);
          renderFeaturedGrid(data);
          renderFeaturedSidebar(data);
        });
    }

    sortSelect.on('change', loadPopular);
    statusSelect.on('change', loadPopular);
    loadPopular();
  }

  function initLatest(widget) {
    const config = widget.config || {};
    const sortSelect = $('#latest-sort');
    const statusSelect = $('#latest-status');

    if (config.sort && sortSelect.find(`[value="${config.sort}"]`).length) {
      sortSelect.val(config.sort);
    }
    if (config.status && statusSelect.find(`[value="${config.status}"]`).length) {
      statusSelect.val(config.status);
    }

    function loadLatest() {
      const params = {
        action: 'latest-chapters',
        limit: config.limit || 8,
        sort: sortSelect.length ? sortSelect.val() : config.sort || 'newest',
        status: statusSelect.length ? statusSelect.val() : config.status || '',
      };

      $.getJSON('api.php', params)
        .done(({ data }) => {
          renderLatestList(data);
          renderLatestSidebar(data);
        });
    }

    sortSelect.on('change', loadLatest);
    statusSelect.on('change', loadLatest);
    loadLatest();
  }

  function renderTopReadsList(container, items, type) {
    container.empty();

    if (!items.length) {
      container.append('<li class="top-reads-empty">Henüz veri bulunmuyor.</li>');
      return;
    }

    items.forEach((item, index) => {
      if (type === 'manga') {
        const cover = item.cover_image || 'https://placehold.co/120x160?text=Manga';
        const url = `manga.php?slug=${encodeURIComponent(item.slug)}`;
        container.append(`
          <li class="top-reads-item">
            <span class="top-reads-rank">${index + 1}</span>
            <a class="top-reads-cover" href="${url}" style="background-image:url('${cover}')">
              <span class="visually-hidden">${escapeHtml(item.title)}</span>
            </a>
            <div class="top-reads-content">
              <a class="top-reads-title" href="${url}">${escapeHtml(item.title)}</a>
              <div class="top-reads-meta">
                <span><i class="bi bi-eye"></i> ${item.total_reads}</span>
                <span>${formatStatus(item.status)}</span>
              </div>
            </div>
          </li>`);
      } else {
        const chapterUrl = `chapter.php?slug=${encodeURIComponent(item.manga_slug)}&chapter=${encodeURIComponent(item.number)}`;
        const mangaUrl = `manga.php?slug=${encodeURIComponent(item.manga_slug)}`;
        const chapterTitle = item.title ? escapeHtml(item.title) : `Bölüm ${item.number}`;
        container.append(`
          <li class="top-reads-item">
            <span class="top-reads-rank">${index + 1}</span>
            <a class="top-reads-cover" href="${chapterUrl}" style="background-image:url('${item.cover_image || 'https://placehold.co/120x160?text=Chapter'}')">
              <span class="visually-hidden">${chapterTitle}</span>
            </a>
            <div class="top-reads-content">
              <a class="top-reads-title" href="${chapterUrl}">${chapterTitle}</a>
              <div class="top-reads-meta">
                <a class="top-reads-link" href="${mangaUrl}">${escapeHtml(item.manga_title)}</a>
                <span>Bölüm ${item.number}</span>
                <span><i class="bi bi-eye"></i> ${item.total_reads}</span>
              </div>
            </div>
          </li>`);
      }
    });
  }

  function initTopReads() {
    const widget = $('[data-widget="top-reads"]');
    if (!widget.length) {
      return;
    }

    const buttons = widget.find('[data-range]');
    const mangaList = widget.find('[data-top-reads="manga"]');
    const chapterList = widget.find('[data-top-reads="chapters"]');
    const statusLabel = widget.find('[data-top-reads-status]');
    const rangeLabels = {
      daily: 'Son 24 saat',
      weekly: 'Son 7 gün',
      monthly: 'Son 30 gün',
    };

    function setLoading() {
      const placeholder = '<li class="top-reads-empty">Yükleniyor...</li>';
      mangaList.html(placeholder);
      chapterList.html(placeholder);
    }

    function showError(message) {
      const markup = `<li class="top-reads-empty text-warning">${escapeHtml(message)}</li>`;
      mangaList.html(markup);
      chapterList.html(markup);
    }

    function fetchRange(range) {
      setLoading();
      $.getJSON('api.php', { action: 'top-reads', range, limit: 5 })
        .done(({ data }) => {
          const payload = data || {};
          renderTopReadsList(mangaList, payload.mangas || [], 'manga');
          renderTopReadsList(chapterList, payload.chapters || [], 'chapters');
          statusLabel.text(rangeLabels[range] || rangeLabels.weekly);
        })
        .fail((xhr) => {
          const response = xhr.responseJSON;
          showError(response?.error || 'Veriler alınamadı.');
        });
    }

    buttons.on('click', function () {
      const button = $(this);
      const range = button.data('range');
      if (!range || button.hasClass('active')) {
        return;
      }
      buttons.removeClass('active');
      button.addClass('active');
      fetchRange(range);
    });

    const initialButton = buttons.filter('.active').first();
    const initialRange = initialButton.data('range') || 'weekly';
    statusLabel.text(rangeLabels[initialRange] || rangeLabels.weekly);
    fetchRange(initialRange);
  }

  $('#search-form').on('submit', function (event) {
    event.preventDefault();
    loadMangaList({ search: $('#search').val(), status: $('#status').val() });
  });

  loadMangaList();

  if (popularWidget) {
    initPopular(popularWidget);
  }

  if (latestWidget) {
    initLatest(latestWidget);
  }

  initTopReads();
});
