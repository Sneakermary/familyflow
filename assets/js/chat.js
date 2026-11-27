// ---------- Suche (Lupe): filtert die Liste nach Spitzname und echtem Name ----------
(function () {
    const btn = document.getElementById('chatSearchBtn');
    const box = document.getElementById('chatSearchBox');
    const input = document.getElementById('chatSearch');
    if (!btn || !box || !input) {
        return;
    }
    const list = document.getElementById('chatList');
    const contacts = document.getElementById('chatSearchContacts');
    const noResults = document.getElementById('chatNoResults');
    const listEmpty = document.getElementById('chatListEmpty');

    // klein schreiben + Akzente weg (é -> e), damit "rene" auch "René" findet
    function norm(text) {
        return text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    function filter() {
        const query = norm(input.value.trim());
        let hits = 0;

        // bestehende Chats: ausblenden, was nicht passt
        list.querySelectorAll('a.chatListItem').forEach(function (item) {
            const match = query === '' || norm(item.dataset.search || '').includes(query);
            item.classList.toggle('hidden', !match);
            if (match) hits++;
        });

        // Kontakte ohne Chat: nur zeigen, wenn gesucht wird
        if (contacts) {
            let contactHits = 0;
            contacts.querySelectorAll('.chatSearchContact').forEach(function (item) {
                const match = query !== '' && norm(item.dataset.search || '').includes(query);
                item.classList.toggle('hidden', !match);
                if (match) contactHits++;
            });
            contacts.classList.toggle('hidden', contactHits === 0);
            hits += contactHits;
        }

        if (listEmpty) listEmpty.classList.toggle('hidden', query !== '');
        noResults.classList.toggle('hidden', query === '' || hits > 0);
    }

    // Lupe: Suchfeld auf-/zuklappen
    btn.addEventListener('click', function () {
        const open = box.classList.toggle('hidden') === false;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.classList.toggle('active', open);
        if (open) {
            input.focus();
        } else {
            input.value = '';
            filter();
        }
    });

    input.addEventListener('input', filter);

    // Escape: Suche leeren und schliessen
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            btn.click();
        }
    });
})();

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
