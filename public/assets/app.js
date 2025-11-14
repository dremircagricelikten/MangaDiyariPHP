$(function () {
  const widgets = window.appWidgets || {};
  const popularWidget = widgets.popular_slider || null;
  const latestWidget = widgets.latest_updates || null;

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
          container.append('<div class="col-12"><div class="empty-state">Henüz sonuç bulunamadı.</div></div>');
          return;
        }

        data.forEach((manga) => {
          container.append(renderMangaCard(manga));
        });
      })
      .fail((xhr) => {
        $('#manga-list').html(`<div class="col-12"><div class="empty-state empty-state--danger">Listeler alınamadı: ${xhr.responseJSON?.error || xhr.statusText}</div></div>`);
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
    return `
      <div class="col-sm-6 col-lg-4 col-xxl-3">
        <article class="manga-card">
          <a href="${url}" class="manga-card__media" style="background-image:url('${cover}')">
            <span class="manga-card__status">${formatStatus(manga.status)}</span>
          </a>
          <div class="manga-card__body">
            <h3 class="manga-card__title"><a href="${url}">${manga.title}</a></h3>
            <p class="manga-card__description text-truncate-3">${manga.description || 'Açıklama bulunmuyor.'}</p>
            <div class="manga-card__meta">
              <span><i class="bi bi-person"></i> ${author}</span>
            </div>
            <a href="${url}" class="btn btn-sm btn-primary w-100">Seri Detayı</a>
          </div>
        </article>
      </div>`;
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
    const cover = featured.cover_image || 'https://placehold.co/1200x700?text=Manga';
    const url = `manga.php?slug=${encodeURIComponent(featured.slug)}`;

    container.html(`
      <a class="feature-card" href="${url}" style="background-image:url('${cover}')">
        <div class="feature-card__overlay">
          <span class="feature-card__badge">${formatStatus(featured.status)}</span>
          <h3>${featured.title}</h3>
          <p>${truncate(featured.description || 'Topluluğun sevdiği popüler serilerden biri.', 180)}</p>
          <div class="feature-card__actions">
            <span><i class="bi bi-person"></i> ${featured.author || 'Bilinmiyor'}</span>
            <span class="feature-card__cta">Seriye Git <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </a>
    `);
  }

  function renderFeaturedItem(manga) {
    const cover = manga.cover_image || 'https://placehold.co/160x220?text=Manga';
    const url = `manga.php?slug=${encodeURIComponent(manga.slug)}`;
    return `
      <a class="featured-item" href="${url}">
        <span class="featured-item__media" style="background-image:url('${cover}')"></span>
        <span class="featured-item__content">
          <span class="featured-item__title">${manga.title}</span>
          <span class="featured-item__meta">${formatStatus(manga.status)} · ${manga.author || 'Bilinmiyor'}</span>
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
      container.append('<div class="col-12"><div class="empty-state">Popüler seri bulunamadı.</div></div>');
      return;
    }

    data.forEach((manga) => container.append(renderMangaCard(manga)));
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
    const chapterTitle = chapter.title || `Bölüm ${chapter.number}`;
    return `
      <div class="col-xl-4 col-lg-6">
        <article class="update-card">
          <div class="update-card__media" style="background-image:url('${cover}')"></div>
          <div class="update-card__body">
            <div class="update-card__series">${chapter.manga_title}</div>
            <div class="update-card__chapter">Bölüm ${chapter.number}</div>
            <p class="update-card__title text-truncate-3">${chapterTitle}</p>
            <div class="update-card__meta">
              <span><i class="bi bi-calendar-event"></i> ${formatDate(chapter.created_at)}</span>
              ${chapter.ki_cost > 0 ? '<span class="badge bg-warning text-dark">Premium</span>' : ''}
            </div>
            <div class="update-card__actions">
              <a href="${mangaUrl}" class="btn btn-outline-light btn-sm">Seri</a>
              <a href="${chapterUrl}" class="btn btn-primary btn-sm">Oku</a>
            </div>
          </div>
        </article>
      </div>`;
  }

  function renderLatestList(chapters) {
    const container = $('#latest-list');
    if (!container.length) {
      return;
    }
    container.empty();

    if (!chapters.length) {
      container.append('<div class="col-12"><div class="empty-state">Yeni bölüm bulunamadı.</div></div>');
      return;
    }

    chapters.forEach((chapter) => container.append(renderChapterCard(chapter)));
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
        sort: sortSelect.val(),
        status: statusSelect.val(),
      };

      $.getJSON('api.php', params)
        .done(({ data }) => {
          renderFeaturedHighlight(data);
          renderFeaturedRail(data);
          renderFeaturedGrid(data);
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
        sort: sortSelect.val(),
        status: statusSelect.val(),
      };

      $.getJSON('api.php', params)
        .done(({ data }) => {
          renderLatestList(data);
        });
    }

    sortSelect.on('change', loadLatest);
    statusSelect.on('change', loadLatest);
    loadLatest();
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
});
