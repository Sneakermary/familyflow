// Live-Zaehler + Pop-ups auf allen App-Seiten.
// Fragt alle 20 Sekunden /api/status.php (nur wenn der Tab sichtbar ist):
//   - aktualisiert die Zaehler an der Sprechblase (Chat) und an der Glocke (Benachrichtigungen)
//   - zeigt fuer NEUE Benachrichtigungen und Nachrichten ein Pop-up oben rechts
// Welche ids schon bekannt sind, steht in localStorage ('ffLive', pro Konto) - so poppt
// nach einem Seitenwechsel nichts doppelt auf, und beim allerersten Mal keine alten Sachen.
// Texte werden IMMER mit textContent eingesetzt (kein HTML aus der Datenbank).
(function () {
    const chatLink = document.querySelector('.chatLink');
    const bellLink = document.querySelector('a.bellLink:not(.chatLink)');
    if (!chatLink || !bellLink) {
        return; // keine Navbar -> nicht eingeloggt / keine App-Seite
    }

    const INTERVAL = 20000;
    const STORE_KEY = 'ffLive';
    const MAX_TOASTS = 3;
    let lastPoll = 0;
    let timer = null;

    // ---------- bekannte ids merken ----------
    function loadSeen(uid) {
        try {
            const seen = JSON.parse(localStorage.getItem(STORE_KEY));
            return seen && seen.uid === uid ? seen : null;
        } catch (e) {
            return null;
        }
    }

    function saveSeen(seen) {
        try {
            localStorage.setItem(STORE_KEY, JSON.stringify(seen));
        } catch (e) { /* privater Modus o.ae. -> dann eben ohne Merken */ }
    }

    // ---------- Zaehler ----------
    function setBadge(link, count) {
        let badge = link.querySelector('.bellBadge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'bellBadge';
                link.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : String(count);
        } else if (badge) {
            badge.remove();
        }
    }

    // ---------- Pop-ups ----------
    let container = null;
    function getContainer() {
        if (!container) {
            container = document.createElement('div');
            container.className = 'liveToasts';
            container.setAttribute('role', 'status');
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
        return container;
    }

    // Nur Links innerhalb von FamilyFlow ("/..." aber nicht "//andere-seite")
    function safeLink(link) {
        return typeof link === 'string' && link.startsWith('/') && !link.startsWith('//') ? link : null;
    }

    // Passendes Symbol je nach Ziel der Benachrichtigung
    function iconFor(link) {
        if (!link) return '🔔';
        if (link.startsWith('/chat/')) return '💬';
        if (link.startsWith('/calendar/')) return '📅';
        if (link.startsWith('/recipe/')) return '🍽️';
        if (link.startsWith('/list/')) return '📋';
        if (link.startsWith('/chore/')) return '🧹';
        if (link.startsWith('/family/')) return '👨‍👩‍👧';
        return '🔔';
    }

    function showToast(icon, title, text, link) {
        const box = getContainer();
        const href = safeLink(link);
        const toast = document.createElement(href ? 'a' : 'div');
        toast.className = 'liveToast';
        if (href) {
            toast.href = href;
        }

        const iconEl = document.createElement('span');
        iconEl.className = 'liveToastIcon';
        iconEl.textContent = icon;

        const body = document.createElement('span');
        body.className = 'liveToastBody';
        if (title) {
            const t = document.createElement('strong');
            t.textContent = title;
            body.appendChild(t);
        }
        const p = document.createElement('span');
        p.textContent = text;
        body.appendChild(p);

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'liveToastClose';
        close.setAttribute('aria-label', 'Schließen');
        close.textContent = '×';
        close.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            remove();
        });

        toast.append(iconEl, body, close);
        box.appendChild(toast);

        // Nach 8 Sekunden ausblenden - nicht solange die Maus darauf ist
        let hideTimer = setTimeout(remove, 8000);
        toast.addEventListener('mouseenter', () => clearTimeout(hideTimer));
        toast.addEventListener('mouseleave', () => { hideTimer = setTimeout(remove, 3000); });

        function remove() {
            clearTimeout(hideTimer);
            toast.classList.add('leaving');
            setTimeout(() => toast.remove(), 250);
        }
    }

    function showNew(data) {
        // Nachrichten aus dem gerade offenen Chat nicht anzeigen - die sieht man ja
        const openChat = document.getElementById('chatMessages');
        const openConv = openChat ? parseInt(openChat.dataset.conv, 10) : 0;

        const items = [];
        data.messages.forEach(function (m) {
            if (m.conversation !== openConv) {
                items.push(['💬', m.group ? m.from + ' in „' + m.group + '“' : m.from, m.preview, m.link]);
            }
        });
        data.notifications.forEach(function (n) {
            items.push([iconFor(n.link), '', n.message, n.link]);
        });

        if (items.length > MAX_TOASTS) {
            items.slice(0, MAX_TOASTS - 1).forEach(i => showToast(...i));
            showToast('🔔', '', (items.length - (MAX_TOASTS - 1)) + ' weitere Neuigkeiten', '/Notifications.php');
        } else {
            items.forEach(i => showToast(...i));
        }
    }

    // ---------- Abfrage ----------
    // Konto-Nummer kommt von head.php (<body data-uid="...">), damit der gespeicherte Stand
    // gleich beim ersten Abfragen benutzt wird - auch direkt nach einem Seitenwechsel
    const uid = parseInt(document.body.dataset.uid || '0', 10);

    function poll() {
        if (document.hidden || !uid) {
            return;
        }
        lastPoll = Date.now();

        // Ohne gespeicherten Stand (allererstes Mal auf diesem Geraet): nur Zaehler holen und
        // den Stand merken - es poppt nichts Altes auf. Danach: alles Neue seit dem Stand.
        const seen = loadSeen(uid);
        const url = '/api/status.php' + (seen ? '?n=' + seen.n + '&m=' + seen.m : '');

        fetch(url, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) {
                if (r.status === 401) {
                    clearInterval(timer); // ausgeloggt -> aufhoeren
                    return null;
                }
                return r.ok ? r.json() : null;
            })
            .then(function (data) {
                if (!data || data.uid !== uid) {
                    return;
                }
                setBadge(chatLink, data.chatUnread);
                setBadge(bellLink, data.notifUnread);

                if (seen) {
                    showNew(data);
                }
                saveSeen({
                    uid: uid,
                    n: Math.max(seen ? seen.n : 0, data.lastNotif),
                    m: Math.max(seen ? seen.m : 0, data.lastMessage),
                });
            })
            .catch(function () { /* kurz offline -> naechstes Mal wieder */ });
    }

    poll();
    timer = setInterval(poll, INTERVAL);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && Date.now() - lastPoll > 5000) {
            poll();
        }
    });
})();
