$(function () {
  const widget = $('#site-chat-widget');
  if (!widget.length) {
    return;
  }

  const messagesContainer = widget.find('#chat-messages');
  const form = widget.find('#chat-form');
  const toggleButton = widget.find('.chat-toggle');
  const closeButton = widget.find('.btn-close');
  const page = widget.data('page');

  if (page === 'index') {
    widget.removeClass('minimized');
  }

  function renderMessages(messages) {
    messagesContainer.empty();
    if (!messages.length) {
      messagesContainer.append('<div class="text-secondary small">Henüz mesaj yok.</div>');
      return;
    }

    messages.forEach((message) => {
      const item = $('<div class="chat-message">');
      item.append(`<div class="chat-author">${escapeHtml(message.username)}</div>`);
      item.append(`<div class="chat-text">${escapeHtml(message.message)}</div>`);
      item.append(`<div class="chat-time text-secondary small">${new Date(message.created_at).toLocaleTimeString('tr-TR')}</div>`);
      messagesContainer.append(item);
    });

    messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
  }

  function escapeHtml(value) {
    return $('<div>').text(value).html();
  }

  function loadHistory() {
    $.getJSON('api.php', { action: 'chat-history' })
      .done(({ data }) => {
        renderMessages(data || []);
      });
  }

  let chatInterval = null;

  function startPolling() {
    if (chatInterval) {
      clearInterval(chatInterval);
    }
    chatInterval = setInterval(loadHistory, 15000);
  }

  toggleButton.on('click', () => {
    widget.toggleClass('minimized');
    if (!widget.hasClass('minimized')) {
      loadHistory();
    }
  });

  closeButton.on('click', () => {
    widget.addClass('minimized');
  });

  form.on('submit', function (event) {
    event.preventDefault();
    const input = form.find('input[name="message"]');
    const message = input.val();
    if (!message) {
      return;
    }

    input.prop('disabled', true);
    const button = form.find('button[type="submit"]').prop('disabled', true);

    $.post('api.php?action=chat-send', { message })
      .done(({ data }) => {
        input.val('');
        loadHistory();
        if (data?.balance !== undefined) {
          updateKiBalance(data.balance);
        }
      })
      .fail((xhr) => {
        alert(xhr.responseJSON?.error || 'Mesaj gönderilemedi.');
      })
      .always(() => {
        input.prop('disabled', false).focus();
        button.prop('disabled', false);
      });
  });

  loadHistory();
  startPolling();

  function updateKiBalance(balance) {
    $('#nav-ki-balance').text(balance);
    if (window.currentUser) {
      window.currentUser.ki_balance = balance;
    }
    $(document).trigger('chat:balance-updated', balance);
  }
});
