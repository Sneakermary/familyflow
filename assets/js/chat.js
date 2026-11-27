// Chat: Senden ohne Neuladen der Seite + alle 4 Sekunden neue Nachrichten holen.
// Laeuft nur, wenn ein Chat geoeffnet ist (#chatMessages vorhanden).
// Nachrichten werden IMMER mit textContent eingesetzt - so kann niemand HTML/JavaScript einschleusen.
(function () {
    const box = document.getElementById('chatMessages');
    if (!box) {
        return;
    }

    const form = document.getElementById('chatForm');
    const input = document.getElementById('chatBody');
    const convId = box.dataset.conv;
    const myId = parseInt(box.dataset.me, 10);
    const isGroup = box.dataset.group === '1';
    let lastId = parseInt(box.dataset.last, 10) || 0;
    let sending = false;

    function scrollToBottom() {
        box.scrollTop = box.scrollHeight;
    }

    // Eine Nachricht als Blase anhaengen (gleicher Aufbau wie in Chat.php)
    function addMessage(m) {
        if (m.id <= lastId) {
            return; // schon angezeigt
        }
        const empty = document.getElementById('chatNoMessages');
        if (empty) {
            empty.remove();
        }

        const mine = m.sender_id === myId;
        const row = document.createElement('div');
        row.className = 'chatMsg ' + (mine ? 'mine' : 'theirs');

        if (isGroup && !mine) {
            const sender = document.createElement('span');
            sender.className = 'chatMsgSender';
            sender.textContent = m.firstname;
            row.appendChild(sender);
        }

        const bubble = document.createElement('span');
        bubble.className = 'chatBubble';
        bubble.textContent = m.body;
        row.appendChild(bubble);

        const time = document.createElement('span');
        time.className = 'chatMsgTime';
        time.textContent = m.time;
        row.appendChild(time);

        box.appendChild(row);
        lastId = m.id;
    }

    function addMessages(list) {
        // Nur automatisch runterscrollen, wenn man eh schon (fast) unten war
        const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 120;
        list.forEach(addMessage);
        if (list.length && nearBottom) {
            scrollToBottom();
        }
    }

    // ---------- Neue Nachrichten holen ----------
    function poll() {
        if (document.hidden) {
            return; // Tab im Hintergrund -> nicht unnoetig fragen
        }
        fetch('/chat/ChatApi.php?action=poll&c=' + encodeURIComponent(convId) + '&after=' + lastId, { credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : { messages: [] })
            .then(data => addMessages(data.messages || []))
            .catch(() => {}); // kurz offline -> beim naechsten Mal wieder versuchen
    }
    setInterval(poll, 4000);
    document.addEventListener('visibilitychange', poll);

    // ---------- Senden ----------
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const text = input.value.trim();
        if (text === '' || sending) {
            return;
        }
        sending = true;

        const data = new FormData(form);
        data.set('action', 'send');
        data.set('c', convId);
        data.set('after', lastId);

        fetch('/chat/ChatApi.php', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(r => {
                if (!r.ok) {
                    throw new Error('send failed');
                }
                return r.json();
            })
            .then(result => {
                input.value = '';
                autoGrow();
                addMessages(result.messages || []);
                scrollToBottom();
            })
            .catch(() => alert('Die Nachricht konnte nicht gesendet werden. Bitte versuch es nochmal.'))
            .finally(() => {
                sending = false;
                input.focus();
            });
    });

    // Enter = senden, Shift+Enter = neue Zeile (am Handy macht Enter einfach eine neue Zeile)
    input.addEventListener('keydown', function (event) {
        const isTouch = window.matchMedia('(pointer: coarse)').matches;
        if (event.key === 'Enter' && !event.shiftKey && !isTouch) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    // Eingabefeld waechst mit dem Text mit (bis max. ca. 5 Zeilen)
    function autoGrow() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 140) + 'px';
    }
    input.addEventListener('input', autoGrow);

    scrollToBottom();
})();
