$(function () {
  const reader = $('#chapter-reader');
  const slug = reader.data('slug');
  const initialChapterNumber = reader.data('chapter');
  const currency = $('#comment-section').data('currency') || (window.kiSettings?.currency_name ?? 'Ki');

  if (!slug) {
    return;
  }

  let currentChapterId = null;
  let currentMangaId = null;
  let lockedChapterId = null;
  let accessInfo = null;
  let currentChapterNumber = initialChapterNumber;

  const chapterContent = $('#chapter-content');
  const chapterTitle = $('#chapter-title');
  const lockState = $('#chapter-lock-state');
  const commentList = $('#comment-list');
  const kiDetails = $('#ki-context-details');

  function renderLoading() {
    chapterContent.html('<div class="text-center py-5 text-secondary">Yükleniyor…</div>');
  }

  function loadChapter(number) {
    renderLoading();
    lockState.empty();
    accessInfo = null;

    $.getJSON('api.php', { action: 'chapter', slug, chapter: number })
      .done(({ data, manga, prev, next, access }) => {
        currentChapterId = parseInt(data.id, 10);
        currentChapterNumber = data.number;
        currentMangaId = parseInt(manga.id, 10);
        lockedChapterId = null;
        accessInfo = access;

        renderChapter(data, manga, prev, next);
        updateLockMessage(null);
        updateCommentTargets();
        populateChapterSelect(manga.id, data.number);
        loadComments();
      })
      .fail((xhr) => {
        if (xhr.status === 402 && xhr.responseJSON) {
          const response = xhr.responseJSON;
          currentMangaId = response.manga?.id ? parseInt(response.manga.id, 10) : null;
          currentChapterId = response.chapter_id ? parseInt(response.chapter_id, 10) : null;
          lockedChapterId = currentChapterId;
          accessInfo = response.access;
          currentChapterNumber = number;

          populateChapterSelect(currentMangaId, number);
          chapterTitle.text(response.manga ? `${response.manga.title} - Bölüm ${number}` : `Bölüm ${number}`);
          chapterContent.empty();
          updateLockMessage(response.access, response.manga, currentChapterId);
          updateCommentTargets();
          loadComments();
        } else {
          chapterContent.html(`<div class="alert alert-danger">Bölüm yüklenemedi: ${xhr.responseJSON?.error || xhr.statusText}</div>`);
        }
      });
  }

  function renderChapter(data, manga, prev, next) {
    chapterTitle.text(`${manga.title} - Bölüm ${data.number}${data.title ? ' · ' + data.title : ''}`);
    chapterContent.html(formatContent(data.content, data.assets));

    $('#prev-chapter')
      .toggleClass('disabled', !prev)
      .attr('href', prev ? `chapter.php?slug=${slug}&chapter=${prev.number}` : '#');

    $('#next-chapter')
      .toggleClass('disabled', !next)
      .attr('href', next ? `chapter.php?slug=${slug}&chapter=${next.number}` : '#');
  }

  function updateLockMessage(access, manga = null, chapterId = null) {
    lockState.empty();
    lockState.removeClass('d-none');

    if (!access || !access.locked) {
      return;
    }

    const requiresLogin = !window.currentUser;
    const expiresAt = access.premium_expires_at
      ? new Date(access.premium_expires_at).toLocaleString('tr-TR')
      : 'Süre belirtilmedi';

    const message = $('<div class="card bg-secondary border-0 text-light">');
    const body = $('<div class="card-body">').appendTo(message);

    body.append('<h3 class="h5">Bu bölüm şu anda kilitli</h3>');
    body.append(
      `<p class="mb-2">Kilidi açmak için <strong>${access.required_ki} ${currency}</strong> harcamanız gerekir. ` +
        `Özel erişim ${expiresAt} tarihinde sona erer.</p>`
    );

    if (requiresLogin) {
      body.append('<div class="alert alert-warning mb-0">Kilidi açmak için lütfen <a class="alert-link" href="login.php">giriş yapın</a>.</div>');
    } else if (chapterId) {
      const button = $(`<button class="btn btn-primary" id="unlock-chapter" data-chapter="${chapterId}">Kilidi Aç (${access.required_ki} ${currency})</button>`);
      body.append(button);
    }

    lockState.append(message);
  }

  function populateChapterSelect(mangaId, currentNumber) {
    $.getJSON('api.php', { action: 'manga', slug })
      .done(({ chapters }) => {
        const select = $('#chapter-select');
        select.empty();
        chapters.forEach((chapter) => {
          const option = $('<option>')
            .val(chapter.number)
            .text(`Bölüm ${chapter.number}${chapter.title ? ' · ' + chapter.title : ''}`);
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

  function formatContent(content, assets = []) {
    const hasAssets = Array.isArray(assets) && assets.length > 0;
    const hasContent = typeof content === 'string' && content.trim() !== '';

    if (!hasAssets && !hasContent) {
      return '<p class="text-secondary">Bu bölüm için henüz içerik eklenmemiş.</p>';
    }

    const parts = [];

    if (hasContent) {
      if (content.includes('\n')) {
        parts.push(
          content
            .split(/\n+/)
            .map((line) => `<p>${line}</p>`)
            .join('')
        );
      } else if (content.includes('http')) {
        parts.push(
          content
            .split(/\s+/)
            .map((url) => `<img class="img-fluid mb-3 rounded" src="${url}" alt="Bölüm sayfası">`)
            .join('')
        );
      } else {
        parts.push(`<p>${content}</p>`);
      }
    }

    if (hasAssets) {
      parts.push(
        assets
          .map((asset) => `<img class="img-fluid mb-3 rounded" src="/${asset}" alt="Bölüm sayfası">`)
          .join('')
      );
    }

    return parts.join('');
  }

  function updateCommentTargets() {
    $('#comment-manga-id').val(currentMangaId || '');
    $('#comment-chapter-id').val(currentChapterId || '');
  }

  function loadComments() {
    if (!currentMangaId) {
      commentList.html('<div class="list-group-item bg-secondary text-light">Henüz yorum yok.</div>');
      return;
    }

    $.getJSON('api.php', {
      action: 'list-comments',
      manga_id: currentMangaId,
      chapter_id: currentChapterId || lockedChapterId || '',
    })
      .done(({ data }) => {
        renderComments(data || []);
      })
      .fail(() => {
        commentList.html('<div class="list-group-item bg-danger text-light">Yorumlar yüklenemedi.</div>');
      });
  }

  function renderComments(comments) {
    commentList.empty();

    if (!comments.length) {
      commentList.append('<div class="list-group-item bg-secondary text-light">İlk yorumu sen yaz!</div>');
      return;
    }

    comments.forEach((comment) => {
      commentList.append(renderComment(comment));
    });
  }

  function renderComment(comment) {
    const item = $('<div class="list-group-item bg-dark text-light border-secondary mb-2 rounded">');
    const header = $('<div class="d-flex justify-content-between align-items-center mb-2">').appendTo(item);
    header.append(`<strong>${comment.username}</strong>`);
    const createdAt = comment.created_at ? new Date(comment.created_at).toLocaleString('tr-TR') : '';
    header.append(`<small class="text-secondary">${createdAt}</small>`);

    item.append(`<p class="mb-2">${escapeHtml(comment.body)}</p>`);

    const reactions = $('<div class="d-flex flex-wrap gap-2 align-items-center"></div>');
    const types = ['like', 'love', 'wow', 'sad', 'angry'];

    types.forEach((type) => {
      const count = comment.reaction_summary?.[type] ?? 0;
      const active = comment.user_reaction === type ? 'active' : '';
      const button = $(`<button type="button" class="btn btn-outline-light btn-sm comment-reaction ${active}" data-comment="${comment.id}" data-reaction="${type}">${formatReaction(type)} <span class="badge bg-light text-dark ms-1">${count}</span></button>`);
      reactions.append(button);
    });

    item.append(reactions);

    return item;
  }

  function formatReaction(type) {
    return (
      {
        like: '👍',
        love: '❤️',
        wow: '😮',
        sad: '😢',
        angry: '😡',
      }[type] || '👍'
    );
  }

  function escapeHtml(value) {
    return $('<div>').text(value).html();
  }

  $('#comment-form').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const data = form.serialize();

    $.post('api.php?action=post-comment', data)
      .done(({ comment }) => {
        form[0].reset();
        loadComments();
        if (comment?.balance !== undefined) {
          updateKiBalance(comment.balance);
        }
      })
      .fail((xhr) => {
        const error = xhr.responseJSON?.error || 'Yorum gönderilemedi.';
        alert(error);
      });
  });

  $('#refresh-comments').on('click', () => {
    loadComments();
  });

  commentList.on('click', '.comment-reaction', function () {
    if (!window.currentUser) {
      alert('Tepki vermek için giriş yapın.');
      return;
    }

    const button = $(this);
    const commentId = button.data('comment');
    const reaction = button.data('reaction');

    $.post('api.php?action=react-comment', { comment_id: commentId, reaction })
      .done(({ summary, balance }) => {
        loadComments();
        if (balance !== null && balance !== undefined) {
          updateKiBalance(balance);
        }
      })
      .fail((xhr) => {
        alert(xhr.responseJSON?.error || 'Tepki kaydedilemedi.');
      });
  });

  $(document).on('click', '#unlock-chapter', function () {
    if (!window.currentUser) {
      alert('Kilidi açmak için giriş yapın.');
      return;
    }

    const button = $(this);
    const chapterId = button.data('chapter');
    const originalText = button.text();
    button.prop('disabled', true).data('original-text', originalText).text('İşlem yapılıyor…');

    $.post('api.php?action=unlock-chapter', { chapter_id: chapterId })
      .done(({ balance }) => {
        if (balance !== undefined) {
          updateKiBalance(balance);
        }
        loadChapter(currentChapterNumber);
      })
      .fail((xhr) => {
        alert(xhr.responseJSON?.error || 'Kilidi açma işlemi başarısız.');
        button.prop('disabled', false).text(button.data('original-text'));
      });
  });

  $('#open-ki-modal').on('click', function () {
    if (!window.currentUser) {
      alert('Ki geçmişini görmek için giriş yapmalısınız.');
      return;
    }

    $.post('api.php?action=ki-context')
      .done(({ data }) => {
        const transactions = (data.transactions || [])
          .map((tx) => `<li>${new Date(tx.created_at).toLocaleString('tr-TR')} – <strong>${tx.amount}</strong> ${data.currency} <span class="text-secondary">(${tx.type})</span></li>`)
          .join('');
        kiDetails
          .removeClass('d-none')
          .html(
            `<div class="d-flex justify-content-between align-items-center mb-2">
              <div><strong>Bakiye:</strong> ${data.balance} ${data.currency}</div>
              <button class="btn btn-sm btn-outline-dark" id="close-ki-context">Kapat</button>
            </div>
            <p class="mb-2 small">Yorum ödülü: ${data.rewards.comment}, Tepki ödülü: ${data.rewards.reaction}, Sohbet/dk: ${data.rewards.chat_per_minute}</p>
            <ol class="mb-0 small">${transactions || '<li>Henüz işlem yok.</li>'}</ol>`
          );
      })
      .fail((xhr) => {
        alert(xhr.responseJSON?.error || 'Ki bilgisi alınamadı.');
      });
  });

  kiDetails.on('click', '#close-ki-context', function () {
    kiDetails.addClass('d-none').empty();
  });

  function updateKiBalance(balance) {
    $('#ki-balance-value').text(balance);
    $('#nav-ki-balance').text(balance);
    if (window.currentUser) {
      window.currentUser.ki_balance = balance;
    }
  }

  function renderChatReward(balance) {
    updateKiBalance(balance);
  }

  $(document).on('chat:balance-updated', function (_event, balance) {
    renderChatReward(balance);
  });

  loadChapter(initialChapterNumber);
});
