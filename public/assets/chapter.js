$(function () {
  const reader = $('#chapter-reader');
  const slug = reader.data('slug');
  const chapterNumber = reader.data('chapter');

  if (!slug) {
    return;
  }

  function loadChapter(number) {
    $.getJSON('api.php', { action: 'chapter', slug, chapter: number })
      .done(({ data, manga, prev, next }) => {
        $('#chapter-title').text(`${manga.title} - Bölüm ${data.number} ${data.title ? '· ' + data.title : ''}`);
        $('#chapter-content').html(formatContent(data.content));
        $('#prev-chapter').toggleClass('disabled', !prev).attr('href', prev ? `chapter.php?slug=${slug}&chapter=${prev.number}` : '#');
        $('#next-chapter').toggleClass('disabled', !next).attr('href', next ? `chapter.php?slug=${slug}&chapter=${next.number}` : '#');

        populateChapterSelect(manga.id, data.number);
      })
      .fail((xhr) => {
        $('#chapter-content').html(`<div class="alert alert-danger">Bölüm yüklenemedi: ${xhr.responseJSON?.error || xhr.statusText}</div>`);
      });
  }

  function populateChapterSelect(mangaId, currentNumber) {
    $.getJSON('api.php', { action: 'manga', slug })
      .done(({ chapters }) => {
        const select = $('#chapter-select');
        select.empty();
        chapters.forEach((chapter) => {
          const option = $('<option>').val(chapter.number).text(`Bölüm ${chapter.number} ${chapter.title ? '· ' + chapter.title : ''}`);
          if (chapter.number === currentNumber) {
            option.prop('selected', true);
          }
          select.append(option);
        });
      });
  }

  $('#chapter-select').on('change', function () {
    const number = $(this).val();
    if (number) {
      window.location.href = `chapter.php?slug=${slug}&chapter=${number}`;
    }
  });

  function formatContent(content) {
    if (!content) {
      return '<p class="text-secondary">Bu bölüm için henüz içerik eklenmemiş.</p>';
    }

    if (content.includes('\n')) {
      return content
        .split(/\n+/)
        .map((line) => `<p>${line}</p>`)
        .join('');
    }

    if (content.includes('http')) {
      return content
        .split(/\s+/)
        .map((url) => `<img class="img-fluid mb-3 rounded" src="${url}" alt="Bölüm sayfası">`)
        .join('');
    }

    return `<p>${content}</p>`;
  }

  loadChapter(chapterNumber);
});
