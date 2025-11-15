$(function () {
  const slug = $('#manga-detail').data('slug');
  if (!slug) {
    return;
  }

  const followBtn = $('#follow-btn');
  const unfollowBtn = $('#unfollow-btn');
  const followInfo = $('#followers-info');
  let currentMangaId = null;

  const formatFollowers = (count) => {
    const numeric = typeof count === 'number' ? count : Number(count);
    const total = Number.isFinite(numeric) ? numeric : 0;
    if (total <= 0) {
      return 'Bu seriyi ilk sen takip edebilirsin!';
    }
    return `${total} kişi takip ediyor`;
  };

  const updateFollowUi = (isFollowing, followerCount) => {
    if (!followBtn.length || !unfollowBtn.length) {
      return;
    }
    followBtn.toggleClass('d-none', !!isFollowing);
    unfollowBtn.toggleClass('d-none', !isFollowing);
    followBtn.prop('disabled', false);
    unfollowBtn.prop('disabled', false);
    followInfo.text(formatFollowers(followerCount)).removeClass('d-none');
  };

  const ensureLoggedIn = () => {
    if (window.currentUser) {
      return true;
    }
    const redirectTarget = `${window.location.pathname}${window.location.search}`;
    const loginUrl = `login.php?redirect=${encodeURIComponent(redirectTarget)}`;
    window.location.href = loginUrl;
    return false;
  };

  const handleFollowRequest = (action) => {
    if (!currentMangaId) {
      return;
    }
    const isFollowAction = action === 'follow';
    const button = isFollowAction ? followBtn : unfollowBtn;
    button.prop('disabled', true);

    $.post(`api.php?action=${isFollowAction ? 'follow-manga' : 'unfollow-manga'}`, {
      manga_id: currentMangaId,
    })
      .done((payload) => {
        const followersRaw = payload?.followers ?? 0;
        const followers = Number(followersRaw);
        updateFollowUi(isFollowAction, Number.isFinite(followers) ? followers : 0);
      })
      .fail((xhr) => {
        const errorMessage = xhr?.responseJSON?.error || 'İşlem tamamlanamadı.';
        alert(errorMessage);
      })
      .always(() => {
        button.prop('disabled', false);
      });
  };

  if (followBtn.length) {
    followBtn.on('click', () => {
      if (!ensureLoggedIn()) {
        return;
      }
      handleFollowRequest('follow');
    });
  }

  if (unfollowBtn.length) {
    unfollowBtn.on('click', () => {
      if (!ensureLoggedIn()) {
        return;
      }
      handleFollowRequest('unfollow');
    });
  }

  $.getJSON('api.php', { action: 'manga', slug })
    .done(({ data, chapters, follow }) => {
      currentMangaId = data.id;
      $('#manga-title').text(data.title);
      $('#manga-description').text(data.description || 'Bu seri için henüz açıklama eklenmedi.');
      $('#manga-cover').attr('src', data.cover_image || 'https://placehold.co/400x600?text=Manga');
      $('#manga-status').text(formatStatus(data.status));
      $('#manga-author').text(data.author || 'Bilinmiyor');
      $('#manga-genres').text(data.genres || '-');
      $('#manga-tags').text(data.tags || '-');

      if (follow) {
        const total = Number(follow.total ?? 0);
        updateFollowUi(!!follow.following, Number.isFinite(total) ? total : 0);
      } else {
        updateFollowUi(false, 0);
      }

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
