$(function () {
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

  function loadFeatured() {
    $.getJSON('api.php', { action: 'featured', limit: 6 })
      .done(({ data }) => {
        renderCarousel(data);
        renderFeaturedGrid(data);
      });
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
    container.empty();
    data.forEach((manga) => container.append(renderMangaCard(manga)));
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

  $('#search-form').on('submit', function (event) {
    event.preventDefault();
    loadMangaList({ search: $('#search').val(), status: $('#status').val() });
  });

  loadMangaList();
  loadFeatured();
});
