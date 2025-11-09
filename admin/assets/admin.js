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
});
