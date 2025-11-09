$(function () {
  const slug = $('#manga-detail').data('slug');
  if (!slug) {
    return;
  }

  $.getJSON('api.php', { action: 'manga', slug })
    .done(({ data, chapters }) => {
      $('#manga-title').text(data.title);
      $('#manga-description').text(data.description || 'Bu seri için henüz açıklama eklenmedi.');
      $('#manga-cover').attr('src', data.cover_image || 'https://placehold.co/400x600?text=Manga');
      $('#manga-status').text(formatStatus(data.status));
      $('#manga-author').text(data.author || 'Bilinmiyor');
      $('#manga-genres').text(data.genres || '-');
      $('#manga-tags').text(data.tags || '-');

      if (!chapters.length) {
        $('#chapter-list').append('<div class="list-group-item bg-dark text-secondary">Henüz bölüm eklenmedi.</div>');
        return;
      }

      chapters.forEach((chapter) => {
        const url = `chapter.php?slug=${encodeURIComponent(data.slug)}&chapter=${encodeURIComponent(chapter.number)}`;
        const badge = `<span class="badge bg-primary rounded-pill">${chapter.number}</span>`;
        $('#chapter-list').append(`
          <a class="list-group-item list-group-item-action bg-dark text-light d-flex justify-content-between align-items-center" href="${url}">
            <div>
              <strong>${chapter.title || 'Bölüm ' + chapter.number}</strong>
              <div class="small text-secondary">${new Date(chapter.created_at).toLocaleString('tr-TR')}</div>
            </div>
            ${badge}
          </a>
        `);
      });
    })
    .fail((xhr) => {
      $('#manga-detail').html(`<div class="alert alert-danger">Seri yüklenirken bir hata oluştu: ${xhr.responseJSON?.error || xhr.statusText}</div>`);
    });
});

function formatStatus(status) {
  return (
    {
      ongoing: 'Devam Ediyor',
      completed: 'Tamamlandı',
      hiatus: 'Ara Verildi',
    }[status] || 'Bilinmiyor'
  );
}
