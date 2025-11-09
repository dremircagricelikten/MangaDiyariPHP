$(function () {
  const widgets = window.appWidgets || {};
  const popularWidget = widgets.popular_slider || null;
  const latestWidget = widgets.latest_updates || null;

  function loadMangaList(params = {}) {
    $.getJSON('api.php', Object.assign({ action: 'list' }, params))
      .done(({ data }) => {
        const container = $('#manga-list');
        container.empty();

        if (!data.length) {
          container.append('<div class="col-12"><div class="alert alert-secondary">Hiç sonuç bulunamadı.</div></div>');
          return;
        }

        data.forEach((manga) => {
          container.append(renderMangaCard(manga));
        });
      })
      .fail((xhr) => {
        $('#manga-list').html(`<div class="col-12"><div class="alert alert-danger">Listeler alınamadı: ${xhr.responseJSON?.error || xhr.statusText}</div></div>`);
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
    return `
      <div class="col-md-4 col-lg-3 mb-4">
        <div class="card h-100 bg-secondary text-light border-0 shadow-sm">
          <img src="${cover}" class="card-img-top" alt="${manga.title} kapağı">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">${manga.title}</h5>
            <p class="card-text text-truncate-3">${manga.description || 'Açıklama bulunmuyor.'}</p>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <span class="badge bg-info">${formatStatus(manga.status)}</span>
              <a href="${url}" class="btn btn-outline-light btn-sm">Seri Detayı</a>
            </div>
          </div>
        </div>
      </div>`;
  }

  function renderCarousel(data) {
    const container = $('#featured-content');
    if (!container.length) {
      return;
    }
    container.empty();

    if (!data.length) {
      container.append('<div class="carousel-item active"><img src="https://placehold.co/800x500?text=Manga" class="d-block w-100" alt="Öne çıkan"></div>');
      return;
    }

    data.forEach((manga, index) => {
      container.append(`
        <div class="carousel-item ${index === 0 ? 'active' : ''}">
          <img src="${manga.cover_image || 'https://placehold.co/800x500?text=Manga'}" class="d-block w-100" alt="${manga.title}">
          <div class="carousel-caption d-none d-md-block">
            <h5>${manga.title}</h5>
            <p>${manga.description ? manga.description.substring(0, 120) + '…' : ''}</p>
          </div>
        </div>
      `);
    });
  }

  function renderFeaturedGrid(data) {
    const container = $('#featured-list');
    if (!container.length) {
      return;
    }
    container.empty();

    if (!data.length) {
      container.append('<div class="col-12"><div class="alert alert-secondary">Popüler seri bulunamadı.</div></div>');
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
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 bg-secondary text-light border-0 shadow-sm">
          <div class="row g-0 h-100">
            <div class="col-4">
              <img src="${cover}" class="img-fluid rounded-start h-100 object-fit-cover" alt="${chapter.manga_title} kapağı">
            </div>
            <div class="col-8">
              <div class="card-body d-flex flex-column">
                <h5 class="card-title mb-1">${chapter.manga_title}</h5>
                <div class="small text-secondary mb-2">Bölüm ${chapter.number}</div>
                <p class="card-text text-truncate-3 mb-2">${chapterTitle}</p>
                <div class="mt-auto d-flex justify-content-between align-items-center gap-2">
                  <small class="text-secondary">${formatDate(chapter.created_at)}</small>
                  <div class="btn-group btn-group-sm">
                    <a href="${mangaUrl}" class="btn btn-outline-light">Seri</a>
                    <a href="${chapterUrl}" class="btn btn-primary">Oku</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>`;
  }

  function renderLatestList(chapters) {
    const container = $('#latest-list');
    if (!container.length) {
      return;
    }
    container.empty();

    if (!chapters.length) {
      container.append('<div class="col-12"><div class="alert alert-secondary">Yeni bölüm bulunamadı.</div></div>');
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
          renderCarousel(data);
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
