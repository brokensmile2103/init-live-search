document.addEventListener('DOMContentLoaded', function () {
    const trigger = InitPluginSuiteLiveSearch?.trigger || {};
    const input = document.querySelector('input[name="s"]');

    if (!trigger.input_focus && !trigger.triple_click && !trigger.ctrl_slash) return;

    let modalInitialized = false;
    let modalCreated = false;
    let modal, overlay, resultsContainer, closeButton, inputSearch, hiddenUrl, suggestionBox, btnSearch, recognition;

    let currentCommand = null;
    let currentPage = 1;
    let hasMoreResults = true;
    let isLoadingMore = false;
    let activeFilter = '*';

    const isMobile = /Mobi|Android/i.test(navigator.userAgent);
    const delay = isMobile ? (InitPluginSuiteLiveSearch.debounce * 1.5) : InitPluginSuiteLiveSearch.debounce;

    function renderSuggestions() {
        const keywords = (InitPluginSuiteLiveSearch.suggested || []).filter(k => k.trim().length);
        if (!keywords.length) {
            suggestionBox.innerHTML = '';
            suggestionBox.style.display = 'none';
            return;
        }

        suggestionBox.innerHTML = keywords.map(keyword => 
            `<span class="ils-suggest-pill" role="button" tabindex="0">${keyword}</span>`
        ).join('');

        suggestionBox.classList.remove('ils-command-list');
        suggestionBox.style.display = 'flex';

        suggestionBox.querySelectorAll('.ils-suggest-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                inputSearch.value = pill.textContent;
                inputSearch.dispatchEvent(new Event('input'));
                inputSearch.focus();
                handleSearch();
                suggestionBox.style.display = 'none';
            });
        });
    }

    let commandIndex = -1;
    let suggestionIndex = -1;

    function renderSlashCommandList(matched) {
        const allCommands = InitPluginSuiteLiveSearch.commands || {};

        const oldIndex = commandIndex;

        suggestionBox.innerHTML = matched.map(key => `
            <div class="ils-command-item" data-command="/${key}" role="option" tabindex="0">
                <code class="ils-command-code">/${key}</code>
                <span class="ils-command-desc">${allCommands[key]}</span>
            </div>
        `).join('');

        suggestionBox.classList.add('ils-command-list');
        suggestionBox.style.display = 'block';

        resultsContainer.innerHTML = '';

        const items = Array.from(suggestionBox.querySelectorAll('.ils-command-item'));

        commandIndex = (oldIndex >= 0 && oldIndex < items.length) ? oldIndex : 0;

        items.forEach((item, index) => {
            item.classList.toggle('active', index === commandIndex);
            item.addEventListener('click', () => {
                inputSearch.value = item.getAttribute('data-command') + ' ';
                inputSearch.focus();
                handleSearch();
            });
        });
    }

    let isRecognizing = false;

    function createModal() {
        if (modalCreated) return;
        modalCreated = true;

        modal = document.createElement('div');
        modal.id = 'ils-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');

        const theme = (window.InitPluginSuiteLiveSearchConfig && window.InitPluginSuiteLiveSearchConfig.theme) || '';
        if (theme === 'dark') modal.classList.add('dark');
        if (theme === 'light') modal.classList.add('light');
        if (theme === 'auto') {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                modal.classList.add('dark');
            }
        }

        modal.innerHTML = `
            <div class="ils-overlay"></div>
            <div class="ils-content">
                <button class="ils-search"><svg width="20" height="20" viewBox="0 0 20 20"><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="9" cy="9" r="7"></circle><path fill="none" stroke="currentColor" stroke-width="1.1" d="M14,14 L18,18 L14,14 Z"></path></svg></button>
                <button class="ils-close"><svg width="20" height="20" viewBox="0 0 24 24"><path d="m21 21-9-9m0 0L3 3m9 9 9-9m-9 9-9 9" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                <input type="text" id="init-live-search-input" class="ils-input" placeholder="${InitPluginSuiteLiveSearch.i18n.placeholder}" aria-label="Search input" maxlength="100" />
                <input type="hidden" class="ils-hidden-url" />
                <div class="ils-suggestions" role="region" aria-label="Search suggestions"></div>
                <div class="ils-results" aria-label="Search Results"></div>
            </div>
        `;
        document.body.appendChild(modal);

        overlay = modal.querySelector('.ils-overlay');
        closeButton = modal.querySelector('.ils-close');
        resultsContainer = modal.querySelector('.ils-results');
        inputSearch = modal.querySelector('.ils-input');
        btnSearch = modal.querySelector('.ils-search');
        hiddenUrl = modal.querySelector('.ils-hidden-url');

        btnSearch.addEventListener('click', () => {
            if (btnSearch.classList.contains('ils-clear-active')) {
                inputSearch.value = '';
                inputSearch.dispatchEvent(new Event('input'));
                inputSearch.focus();
                updateSearchIcon(false);
            }
        });

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (SpeechRecognition && InitPluginSuiteLiveSearch.enable_voice) {
            const voiceAutoRestart = InitPluginSuiteLiveSearch.voice_auto_restart === true;
            const voiceAutoStop = InitPluginSuiteLiveSearch.voice_auto_stop !== false;

            const voiceBtn = document.createElement('button');
            voiceBtn.className = 'ils-voice';
            voiceBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 1920 1920" fill="currentColor"><path d="M960.3 96.8a339 339 0 0 0-338.8 338.9v484c0 187 152 339 338.8 339s338.9-152 338.9-339v-484c0-186.9-152-338.9-338.9-338.9M427.8 710v233.4c0 293.6 239 532.5 532.5 532.5 293.6 0 532.5-239 532.5-532.5V710h96.8v233.4a630 630 0 0 1-580.9 627.5v252.3h242v96.8H670v-96.8h242v-252.3a630 630 0 0 1-581-627.5V710zM960.3 0A436 436 0 0 1 1396 435.7v484a436 436 0 0 1-435.7 435.7 436 436 0 0 1-435.7-435.6V435.7A436 436 0 0 1 960.3 0" fill-rule="evenodd"/></svg>';
            inputSearch.insertAdjacentElement('beforebegin', voiceBtn);

            recognition = new SpeechRecognition();
            const langMap = {
                vi: 'vi-VN',
                en: 'en-US',
                fr: 'fr-FR',
                ja: 'ja-JP',
            };

            const htmlLang = (document.documentElement.lang || 'vi').replace('_', '-').toLowerCase();
            recognition.lang = langMap[htmlLang] || (htmlLang.includes('-') ? htmlLang : `${htmlLang}-VN`);
            recognition.interimResults = false;

            function safeStartRecognition() {
                if (isRecognizing) return;
                try {
                    recognition.start();
                    isRecognizing = true;
                } catch (e) {
                    console.warn('[Init Live Search] Recognition already started or blocked.', e);
                }
            }

            function stopRecognition() {
                if (!isRecognizing) return;
                try {
                    recognition.stop();
                    isRecognizing = false;
                } catch (e) {
                    console.warn('[Init Live Search] Failed to stop recognition.', e);
                }
            }

            recognition.onresult = function(event) {
                const text = event.results[0][0].transcript;
                inputSearch.value = text;
                sessionStorage.setItem('ils-term', text);
                handleSearch();
            };

            recognition.onerror = function(event) {
                console.warn('[Init Live Search] Voice recognition error:', event.error);
            };

            recognition.onend = function() {
                isRecognizing = false;
                if (!modal.classList.contains('open')) return;

                if (voiceAutoRestart && !voiceAutoStop) {
                    safeStartRecognition();
                }
            };

            voiceBtn.addEventListener('click', () => {
                safeStartRecognition();
            });

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopRecognition();
                }
            });
        }

        overlay.addEventListener('click', closeModal);
        closeButton.addEventListener('click', closeModal);
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal();
        });

        resultsContainer.addEventListener('click', (e) => {
            const target = e.target.closest('.ils-item');
            if (target && window.innerWidth < 768) {
                closeModal();
            }
        });

        inputSearch.addEventListener('input', debounce(() => {
            sessionStorage.setItem('ils-term', inputSearch.value.trim());
            handleSearch();
        }, delay));

        inputSearch.addEventListener('input', () => {
            const term = inputSearch.value.trim();

            updateSearchIcon(term.length > 0);
        });

        modalInitialized = true;

        resultsContainer.addEventListener('scroll', () => {
            const threshold = 200;
            if (resultsContainer.scrollTop + resultsContainer.clientHeight >= resultsContainer.scrollHeight - threshold) {
                loadMoreResults();
            }
        });

        let selectedIndex = -1;

        suggestionBox = modal.querySelector('.ils-suggestions');

        renderSuggestions();

        function getVisibleResultItems() {
            return Array.from(resultsContainer.querySelectorAll('.ils-item'))
                .filter(item => item.style.display !== 'none');
        }

        inputSearch.addEventListener('keydown', (e) => {
            const isCommandList = suggestionBox.classList.contains('ils-command-list');
            const isSuggestionVisible = suggestionBox.style.display === 'flex';
            const hasResults = resultsContainer.querySelectorAll('.ils-item').length > 0;

            if (isCommandList && !hasResults) {
                const items = Array.from(suggestionBox.querySelectorAll('.ils-command-item'));
                if (!items.length) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    commandIndex = (commandIndex + 1) % items.length;
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    commandIndex = (commandIndex - 1 + items.length) % items.length;
                } else if (e.key === 'Enter') {
                    if (commandIndex >= 0 && commandIndex < items.length) {
                        e.preventDefault();
                        const cmd = items[commandIndex].getAttribute('data-command');
                        inputSearch.value = cmd + ' ';
                        suggestionBox.innerHTML = '';
                        suggestionBox.classList.remove('ils-command-list');
                        suggestionBox.style.display = 'none';
                        inputSearch.focus();
                        handleSearch();
                    }
                    return;
                }

                items.forEach((item, index) => {
                    item.classList.toggle('active', index === commandIndex);
                });

                return;
            }

            if (isSuggestionVisible && !hasResults) {
                const pills = Array.from(suggestionBox.querySelectorAll('.ils-suggest-pill'));
                if (!pills.length) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    suggestionIndex = (suggestionIndex + 1) % pills.length;
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    suggestionIndex = (suggestionIndex - 1 + pills.length) % pills.length;
                } else if (e.key === 'Enter') {
                    if (suggestionIndex >= 0 && suggestionIndex < pills.length) {
                        e.preventDefault();
                        const text = pills[suggestionIndex].textContent;
                        inputSearch.value = text;
                        suggestionIndex = -1;
                        inputSearch.dispatchEvent(new Event('input'));
                        inputSearch.focus();
                        handleSearch();
                    }
                    return;
                } else {
                    suggestionIndex = -1;
                    return;
                }

                pills.forEach((pill, index) => {
                    pill.classList.toggle('active', index === suggestionIndex);
                });

                return;
            }

            const resultItems = getVisibleResultItems();
            if (!resultItems.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % resultItems.length;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + resultItems.length) % resultItems.length;
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (hiddenUrl.value) {
                    selectedIndex = -1;
                    window.location.href = hiddenUrl.value;
                }
                return;
            }

            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                const activeItem = resultsContainer.querySelector('.ils-item.active');
                if (!activeItem) return;

                const postId = activeItem.getAttribute('data-id');
                if (!postId) return;

                const favKey = 'ils-fav-' + postId;
                const favBtn = activeItem.querySelector('.ils-fav-btn');
                if (!favBtn) return;

                if (e.key === 'ArrowRight' && !localStorage.getItem(favKey)) {
                    localStorage.setItem(favKey, Date.now());
                    favBtn.classList.add('active', 'flash');
                    setTimeout(() => favBtn.classList.remove('flash'), 400);
                    e.preventDefault();
                }

                if (e.key === 'ArrowLeft' && localStorage.getItem(favKey)) {
                    localStorage.removeItem(favKey);
                    favBtn.classList.remove('active');
                    favBtn.classList.add('flash');
                    setTimeout(() => favBtn.classList.remove('flash'), 400);
                    e.preventDefault();
                }
            }

            function scrollToItem(item) {
                requestAnimationFrame(() => {
                    item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                });
            }

            let hasActiveItem = false;

            resultItems.forEach((item, index) => {
                const isActive = index === selectedIndex;
                item.classList.toggle("active", isActive);
                item.setAttribute("aria-selected", isActive ? "true" : "false");
                if (isActive) {
                    scrollToItem(item);
                    hiddenUrl.value = item.getAttribute("data-url");
                    hasActiveItem = true;
                }
            });

            if (!hasActiveItem) {
                hiddenUrl.value = '';
            }
        });

        function loadMoreResults() {
            if (!currentCommand || isLoadingMore || !hasMoreResults || activeFilter !== '*') return;

            isLoadingMore = true;
            currentPage++;

            const { cmd, param, arg, term } = currentCommand;

            // Set flag chỉ khi chắc chắn sẽ gọi API
            if (cmd === 'recent') {
                isLoadingMore = true;
                currentPage++;
                loadMoreRecent();
                return;
            }

            if (cmd === 'read') {
                isLoadingMore = true;
                currentPage++;
                loadMoreRead();
                return;
            }

            if (cmd === 'fav') {
                isLoadingMore = true;
                currentPage++;
                loadMoreFav();
                return;
            }

            // Các command còn lại
            isLoadingMore = true;
            currentPage++;

            let url = `${InitPluginSuiteLiveSearch.api.replace('/search', `/${cmd}`)}?${param}=${encodeURIComponent(arg)}&page=${currentPage}&exclude=${InitPluginSuiteLiveSearch.post_id || 0}`;

            if (cmd === 'tax' && term) {
                url += `&term=${encodeURIComponent(term)}`;
            }

            fetch(url)
                .then(res => {
                    if (!res.ok) throw new Error(`[${res.status}] ${res.statusText}`);
                    return res.json();
                })
                .then(data => {
                    isLoadingMore = false;

                    if (!Array.isArray(data) || data.length === 0) {
                        hasMoreResults = false;
                        return;
                    }

                    if (data.length < 10) hasMoreResults = false;

                    const prevUrls = new Set(
                        Array.from(resultsContainer.querySelectorAll('.ils-item'))
                            .map(el => el.getAttribute('data-url')?.replace(/\/$/, ''))
                    );

                    const freshItems = data.filter(item => !prevUrls.has((item.url || '').replace(/\/$/, '')));
                    if (freshItems.length) {
                        renderResults(freshItems, true);
                    }
                })
                .catch(err => {
                    isLoadingMore = false;
                    hasMoreResults = false;
                });
        }
    }

    function loadMoreRecent() {
        const url = `${InitPluginSuiteLiveSearch.api.replace('/search', '/recent')}?page=${currentPage}`;

        fetch(url)
          .then(res => {
              if (!res.ok) throw new Error(`[${res.status}] ${res.statusText}`);
              return res.json();
          })
          .then(data => {
              isLoadingMore = false;

              if (!Array.isArray(data) || data.length === 0) {
                  hasMoreResults = false;
                  return;
              }

              if (data.length < 10) hasMoreResults = false;

              const prevUrls = Array.from(resultsContainer.querySelectorAll('.ils-item'))
                  .map(el => el.getAttribute('data-url').replace(/\/$/, ''));

              const freshItems = data.filter(item => !prevUrls.includes((item.url || '').replace(/\/$/, '')));

              if (freshItems.length) {
                  renderResults(freshItems, true);
              }
          })
          .catch((err) => {
              isLoadingMore = false;
              hasMoreResults = false;
          });
    }

    function loadMoreRead() {
        const start = (currentPage - 1) * 10;
        const end = start + 10;
        const ids = currentCommand.allReadIds?.slice(start, end) || [];
        loadMoreGeneric(InitPluginSuiteLiveSearch.api.replace('/search', '/read'), ids);
    }

    function loadMoreGeneric(endpoint, ids = []) {
        if (!ids.length) {
            hasMoreResults = false;
            return;
        }

        const url = endpoint + (endpoint.includes('?') ? '&' : '?') + 'ids=' + ids.join(',');
        fetch(url)
            .then(res => res.json())
            .then(data => {
                isLoadingMore = false;
                if (!Array.isArray(data) || data.length === 0) {
                    hasMoreResults = false;
                    return;
                }

                if (data.length < 10) hasMoreResults = false;

                const prev = Array.from(resultsContainer.querySelectorAll('.ils-item'))
                    .map(el => el.getAttribute('data-url'));

                const fresh = data.filter(item => !prev.includes(item.url));
                if (fresh.length) {
                    renderResults(fresh, true);
                }
            })
            .catch(() => {
                isLoadingMore = false;
                hasMoreResults = false;
            });
    }

    function loadMoreFav() {
        const start = (currentPage - 1) * 10;
        const end = start + 10;
        const ids = currentCommand.allFavIds?.slice(start, end) || [];

        if (!ids.length) {
            hasMoreResults = false;
            return;
        }

        fetch(`${InitPluginSuiteLiveSearch.api.replace('/search', '/read')}?ids=${ids.join(',')}`)
            .then(res => res.json())
            .then(data => {
                isLoadingMore = false;

                if (!Array.isArray(data) || data.length === 0) {
                    hasMoreResults = false;
                    return;
                }

                if (data.length < 10) hasMoreResults = false;

                const prev = Array.from(resultsContainer.querySelectorAll('.ils-item'))
                    .map(el => el.getAttribute('data-url'));

                const fresh = data.filter(item => !prev.includes(item.url));
                if (fresh.length) {
                    renderResults(fresh, true);
                }
            })
            .catch(() => {
                isLoadingMore = false;
                hasMoreResults = false;
            });
    }

    function openModal() {
        if (!modalInitialized) createModal();
        modal.classList.add('open');

        setTimeout(() => {
            const saved = sessionStorage.getItem('ils-term');
            if (saved) {
                inputSearch.value = saved + ' ';
            }

            inputSearch.focus();

            setTimeout(() => {
                inputSearch.setSelectionRange(0, inputSearch.value.length);
            }, 10);

            window.dispatchEvent(new Event('ils:modal-opened'));
        }, 100);
    }

    function closeModal() {
        modal.classList.remove('open');
        resultsContainer.innerHTML = '';
        inputSearch.value = '';
        hiddenUrl.value = '';
        suggestionBox.classList.remove('ils-command-list');
        suggestionBox.style.display = 'flex';
        selectedIndex = -1;
        commandIndex = -1;
        suggestionIndex = -1;

        if (recognition && isRecognizing) {
            try {
                recognition.abort();
                isRecognizing = false;
            } catch (e) {
                console.warn('Failed to abort recognition:', e);
            }
        }

        sessionStorage.removeItem('ils-term');

        window.dispatchEvent(new Event('ils:modal-closed'));
    }

    function showHelpCommandList() {
        const helpList = Object.entries(InitPluginSuiteLiveSearch.commands || {})
            .map(([key, val]) => `<li><code class="ils-command">/${key}</code> – ${val}</li>`)
            .join('');

        const helpMessage = `<p><strong>${InitPluginSuiteLiveSearch.i18n.supported_commands}</strong></p><ul>${helpList}</ul>`;
        showMessage(helpMessage);

        setTimeout(() => {
            const helpEl = resultsContainer.querySelector('.ils-message');
            if (helpEl) {
                helpEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 50);

        modal.removeEventListener('click', onHelpCommandClick);
        modal.addEventListener('click', onHelpCommandClick);
    }

    function onHelpCommandClick(e) {
        const code = e.target.closest('.ils-command');
        if (code) {
            inputSearch.value = code.textContent.trim() + ' ';
            inputSearch.dispatchEvent(new Event('input'));
            inputSearch.focus();
            handleSearch();
            modal.removeEventListener('click', onHelpCommandClick);
        }
    }

    function parseSlashCommand(term) {
        const parts = term.slice(1).trim().split(/\s+/);
        const cmd = parts[0]?.toLowerCase();
        const arg = parts.slice(1).join(' ');

        if (!cmd) return false;

        if (cmd === 'clear') {
            try {
                const prefix = 'ils-cache-';
                for (let i = localStorage.length - 1; i >= 0; i--) {
                    const key = localStorage.key(i);
                    if (key && key.startsWith(prefix)) {
                        localStorage.removeItem(key);
                    }
                }
                showMessage(InitPluginSuiteLiveSearch.i18n.cache_cleared);
            } catch (e) {
                showMessage(InitPluginSuiteLiveSearch.i18n.cache_failed);
            }
            return true;
        }

        if (cmd === 'reset') {
            inputSearch.value = '';
            inputSearch.dispatchEvent(new Event('input'));
            inputSearch.focus();
            return true;
        }

        if (cmd === 'help') {
            showHelpCommandList();
            return true;
        }

        if (cmd === 'id' && /^\d+$/.test(arg)) {
            const id = parseInt(arg, 10);
            fetch(`${InitPluginSuiteLiveSearch.api.replace(/\/search$/, '')}/id/${id}`)
                .then(res => {
                    if (!res.ok) throw new Error();
                    return res.json();
                })
                .then(post => {
                    if (post && post.url) {
                        window.location.href = post.url;
                    } else {
                        console.warn('[Init Live Search] Post not found.');
                    }
                })
                .catch(() => {
                    console.error('[Init Live Search] Error fetching post info.');
                });
            return true;
        }

        if (cmd === 'recent') {
            const cacheKey = `ils-cache-/recent`;

            if (InitPluginSuiteLiveSearch.use_cache) {
                const cached = localStorage.getItem(cacheKey);
                if (cached) {
                    const data = JSON.parse(cached);
                    setCommand('recent');
                    renderResults(data);
                    return true;
                }
            }

            fetch(`${InitPluginSuiteLiveSearch.api.replace('/search', '/recent')}?page=1`)
                .then(res => {
                    if (!res.ok) throw new Error('Failed to fetch');
                    return res.json();
                })
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                        return;
                    }
                    if (InitPluginSuiteLiveSearch.use_cache) {
                        localStorage.setItem(cacheKey, JSON.stringify(data));
                    }
                    setCommand('recent');
                    renderResults(data);
                })
                .catch(() => {
                    showMessage(InitPluginSuiteLiveSearch.i18n.error);
                });

            return true;
        }

        if (cmd === 'popular') {
            const cacheKey = 'ils-cache-popular';

            if (InitPluginSuiteLiveSearch.use_cache) {
                const cached = localStorage.getItem(cacheKey);
                if (cached) {
                    try {
                        const data = JSON.parse(cached);
                        setCommand('popular');
                        renderResults(data);
                        return true;
                    } catch (e) {
                        localStorage.removeItem(cacheKey);
                    }
                }
            }

            const endpoint = `${location.origin}/wp-json/initvico/v1/top?number=10&fields=full`;

            fetch(endpoint)
                .then(res => {
                    if (!res.ok) throw new Error('not-supported');
                    return res.json();
                })
                .then(posts => {
                    if (!Array.isArray(posts) || posts.length === 0) {
                        showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                        return;
                    }

                    const data = posts.map(post => ({
                        title: post.title,
                        url: post.url || post.link,
                        type: post.type || '',
                        thumb: (typeof post.thumbnail === 'undefined' ? (post.thumb || InitPluginSuiteLiveSearch.default_thumb) : post.thumbnail),
                        date: post.date || '',
                        category: post.category || ''
                    }));

                    if (InitPluginSuiteLiveSearch.use_cache) {
                        localStorage.setItem(cacheKey, JSON.stringify(data));
                    }

                    setCommand('popular');
                    renderResults(data);
                })
                .catch(err => {
                    if (err.message === 'not-supported') {
                        showMessage(InitPluginSuiteLiveSearch.i18n.popular_not_supported);
                    } else {
                        showMessage(InitPluginSuiteLiveSearch.i18n.error);
                    }
                });

            return true;
        }

        if (cmd === 'related') {
            const pageTitle = document.title.trim();
            if (!pageTitle) {
                showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                return true;
            }

            const cacheKey = `ils-cache-related-${pageTitle}`;
            if (InitPluginSuiteLiveSearch.use_cache) {
                const cached = localStorage.getItem(cacheKey);
                if (cached) {
                    try {
                        setCommand('related', 'title', pageTitle);
                        renderResults(JSON.parse(cached));
                        return true;
                    } catch (e) {
                        localStorage.removeItem(cacheKey);
                    }
                }
            }

            const endpoint = `${InitPluginSuiteLiveSearch.api.replace('/search', '/related')}?title=${encodeURIComponent(document.title)}&exclude=${InitPluginSuiteLiveSearch.post_id || 0}`;
            fetch(endpoint)
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                        return;
                    }

                    if (InitPluginSuiteLiveSearch.use_cache) {
                        localStorage.setItem(cacheKey, JSON.stringify(data));
                    }

                    setCommand('related', 'title', pageTitle);
                    renderResults(data);
                })
                .catch(() => {
                    showMessage(InitPluginSuiteLiveSearch.i18n.error);
                });

            return true;
        }

        if (cmd === 'read') {
            const prefix = 'init_rp_';
            const allKeys = Object.keys(localStorage)
                .filter(k => k.startsWith(prefix))
                .map(k => ({
                    key: k,
                    time: parseInt(localStorage.getItem(k), 10)
                }))
                .filter(item => !isNaN(item.time))
                .sort((a, b) => b.time - a.time);

            const allReadIds = allKeys
                .map(item => parseInt(item.key.replace(prefix, ''), 10))
                .filter(id => id > 0);

            if (!allReadIds.length) {
                showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                return true;
            }

            const firstIds = allReadIds.slice(0, 10);

            const endpoint = InitPluginSuiteLiveSearch.api.replace('/search', '/read');
            fetch(endpoint + '?ids=' + firstIds.join(','))
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                        return;
                    }

                    setCommand('read', '', '', { allReadIds });
                    renderResults(data);
                })
                .catch(() => {
                    showMessage(InitPluginSuiteLiveSearch.i18n.error);
                });

            return true;
        }

        if (cmd === 'fav') {
            const prefix = 'ils-fav-';
            const allKeys = Object.keys(localStorage)
                .filter(k => k.startsWith(prefix))
                .map(k => ({
                    id: parseInt(k.replace(prefix, ''), 10),
                    time: parseInt(localStorage.getItem(k), 10) || 0
                }))
                .filter(item => item.id > 0)
                .sort((a, b) => b.time - a.time);

            const allFavIds = allKeys.map(item => item.id);
            if (!allFavIds.length) {
                showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                return true;
            }

            const firstIds = allFavIds.slice(0, 10);
            const endpoint = InitPluginSuiteLiveSearch.api.replace('/search', '/read');

            fetch(endpoint + '?ids=' + firstIds.join(','))
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                        return;
                    }

                    setCommand('fav', '', '', { allFavIds }); // giống /read
                    renderResults(data);
                })
                .catch(() => {
                    showMessage(InitPluginSuiteLiveSearch.i18n.error);
                });

            return true;
        }

        if (cmd === 'fav_clear') {
            const prefix = 'ils-fav-';
            const keys = Object.keys(localStorage).filter(k => k.startsWith(prefix));

            if (!keys.length) {
                showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                return true;
            }

            keys.forEach(k => localStorage.removeItem(k));
            showMessage(InitPluginSuiteLiveSearch.i18n.fav_cleared);
            return true;
        }

        if (cmd === 'random') {
            const endpoint = InitPluginSuiteLiveSearch.api.replace('/search', '/random');
            fetch(endpoint)
                .then(res => res.json())
                .then(post => {
                    if (post && post.url) {
                        window.location.href = post.url;
                    } else {
                        showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                    }
                })
                .catch(() => {
                    showMessage(InitPluginSuiteLiveSearch.i18n.error);
                });
            return true;
        }

        if (cmd === 'categories' || cmd === 'tags') {
            const taxonomy = cmd === 'categories' ? 'category' : 'post_tag';
            const endpoint = InitPluginSuiteLiveSearch.api.replace('/search', '/taxonomies?taxonomy=' + taxonomy);

            fetch(endpoint)
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                        return;
                    }

                    const suggestionBox = document.querySelector('.ils-suggestions');
                    if (suggestionBox) suggestionBox.innerHTML = '';

                    const fragment = document.createDocumentFragment();
                    data.forEach(item => {
                        const pill = document.createElement('a');
                        pill.href = item.url;
                        pill.className = 'ils-suggest-pill';
                        pill.textContent = item.name;
                        pill.title = `${item.name} (${item.count})`;
                        fragment.appendChild(pill);
                    });

                    if (suggestionBox) {
                        suggestionBox.appendChild(fragment);
                        resultsContainer.innerHTML = '';
                        suggestionBox.classList.remove('ils-command-list');
                        suggestionBox.style.display = 'flex';
                    }
                })
                .catch(() => {
                    showMessage(InitPluginSuiteLiveSearch.i18n.error);
                });

            return true;
        }

        if (cmd === 'date' && arg) {
            const normalized = arg.trim().replace(/^\/+|\/+$/g, '');
            const cacheKey = `ils-cache-date-${normalized}`;

            if (InitPluginSuiteLiveSearch.use_cache) {
                const cached = localStorage.getItem(cacheKey);
                if (cached) {
                    try {
                        setCommand('date', 'value', normalized);
                        renderResults(JSON.parse(cached));
                        return true;
                    } catch (e) {
                        localStorage.removeItem(cacheKey);
                    }
                }
            }

            const endpoint = `${InitPluginSuiteLiveSearch.api.replace('/search', '/date')}?value=${encodeURIComponent(normalized)}`;

            fetch(endpoint)
                .then(res => {
                    if (!res.ok) throw new Error();
                    return res.json();
                })
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                        return;
                    }
                    if (InitPluginSuiteLiveSearch.use_cache) {
                        localStorage.setItem(cacheKey, JSON.stringify(data));
                    }

                    setCommand('date', 'value', normalized);
                    renderResults(data);
                })
                .catch(() => {
                    showMessage(InitPluginSuiteLiveSearch.i18n.error);
                });

            return true;
        }

        if (['category', 'tag'].includes(cmd) || /^[a-z0-9_-]+$/.test(cmd)) {
            if (!arg) {
                showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                return true;
            }

            const cacheKey = `ils-cache-tax-${cmd}-${arg}`;
            if (InitPluginSuiteLiveSearch.use_cache) {
                const cached = localStorage.getItem(cacheKey);
                if (cached) {
                    try {
                        setCommand('tax', 'taxonomy', cmd, { term: arg });
                        renderResults(JSON.parse(cached));
                        return true;
                    } catch (e) {
                        localStorage.removeItem(cacheKey);
                    }
                }
            }

            const endpoint = `${InitPluginSuiteLiveSearch.api.replace('/search', '/tax')}?taxonomy=${encodeURIComponent(cmd)}&term=${encodeURIComponent(arg)}`;

            fetch(endpoint)
                .then(res => {
                    if (!res.ok) throw new Error();
                    return res.json();
                })
                .then(posts => {
                    if (!Array.isArray(posts) || posts.length === 0) {
                        showMessage(InitPluginSuiteLiveSearch.i18n.no_results);
                        return;
                    }

                    const data = posts.map(post => ({
                        title: post.title,
                        url: post.url,
                        type: post.type || '',
                        thumb: post.thumb || InitPluginSuiteLiveSearch.default_thumb,
                        date: post.date || '',
                        category: post.category || ''
                    }));

                    if (InitPluginSuiteLiveSearch.use_cache) {
                        localStorage.setItem(cacheKey, JSON.stringify(data));
                    }

                    setCommand('tax', 'taxonomy', cmd, { term: arg });
                    renderResults(data);
                })
                .catch(() => {
                    showMessage(InitPluginSuiteLiveSearch.i18n.error);
                });

            return true;
        }

        return false; // not a valid slash command
    }

    function setCommand(cmd, param = '', arg = '', extra = {}) {
        currentCommand = { cmd, param, arg, ...extra };
        currentPage = 1;
        hasMoreResults = true;
    }

    function showMessage(msg) {
        resultsContainer.innerHTML = `<div class="ils-message">${msg}</div>`;
    }

    function stripHtml(html) {
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = html;
        return tempDiv.textContent || tempDiv.innerText || "";
    }

    function addUtm(url) {
        const utm = InitPluginSuiteLiveSearch.utm?.trim();
        if (!utm) return url;
        return url.includes('?') ? `${url}&${utm}` : `${url}?${utm}`;
    }

    function renderResults(data, append = false) {
        if (!append) {
            resultsContainer.innerHTML = '';
        }

        if (!data.length) {
            resultsContainer.innerHTML = `<p class="ils-empty">${InitPluginSuiteLiveSearch.i18n.no_results}</p>`;
            return;
        }

        const getFavKey = (id) => 'ils-fav-' + id;
        const isFav = (id) => localStorage.getItem(getFavKey(id)) !== null;

        const fragment = document.createDocumentFragment();

        data.forEach(item => {
            const postId = item.id;
            const favKey = getFavKey(postId);
            const favActive = isFav(postId);
            const favBtnStyle = InitPluginSuiteLiveSearch.enable_slash ? '' : ' style="display: none;"';
            const finalUrl = addUtm(item.url);

            const a = document.createElement('a');
            a.href = finalUrl;
            a.className = 'ils-item';
            a.setAttribute('data-url', finalUrl);
            a.setAttribute('data-id', postId);
            a.setAttribute('data-title', stripHtml(item.title));
            a.setAttribute('data-category', item.category || '');


            a.innerHTML = `
                <div class="ils-thumb">
                    <img src="${item.thumb}" onerror="this.src='${InitPluginSuiteLiveSearch.default_thumb}'" alt="">
                </div>
                <div class="ils-meta">
                    <div class="ils-title">${item.title}</div>
                    <div class="ils-info">${[item.date, item.type].filter(Boolean).join(' &middot; ')}</div>
                    <button class="ils-fav-btn${favActive ? ' active' : ''}" title="Yêu thích" aria-label="Favorite"${favBtnStyle}>
                        <svg width="20" height="20" viewBox="0 0 20 20">
                            <polygon fill="none" stroke="currentColor" stroke-width="1.01"
                                points="10 2 12.63 7.27 18.5 8.12 14.25 12.22 15.25 18 10 15.27 4.75 18 5.75 12.22 1.5 8.12 7.37 7.27">
                            </polygon>
                        </svg>
                    </button>
                </div>
            `;

            const btn = a.querySelector('.ils-fav-btn');
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (localStorage.getItem(favKey)) {
                    localStorage.removeItem(favKey);
                    btn.classList.remove('active');
                } else {
                    localStorage.setItem(favKey, Date.now());
                    btn.classList.add('active');
                }
            });

            fragment.appendChild(a);
        });

        resultsContainer.appendChild(fragment);

        const categories = [...new Set(data.map(item => item.category).filter(Boolean))];
        if (categories.length) {
            suggestionBox.innerHTML = `
                <span class="ils-suggest-pill active" data-filter="*">${InitPluginSuiteLiveSearch.i18n.all}</span>
                ${categories.map(cat => `<span class="ils-suggest-pill" data-filter="${cat}">${cat}</span>`).join('')}
            `;
            suggestionBox.classList.remove('ils-command-list');
            suggestionBox.style.display = 'flex';

            suggestionBox.querySelectorAll('.ils-suggest-pill').forEach(pill => {
                pill.addEventListener('click', () => {
                    const filter = pill.getAttribute('data-filter');
                    if (filter === activeFilter) return;

                    activeFilter = filter;
                    selectedIndex = -1;

                    resultsContainer.querySelectorAll('.ils-item').forEach(item => {
                        const itemCat = item.getAttribute('data-category') || '';
                        item.style.display = (filter === '*' || itemCat === filter) ? '' : 'none';
                    });

                    suggestionBox.querySelectorAll('.ils-suggest-pill').forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');
                });
            });
        }

        window.dispatchEvent(new CustomEvent('ils:results-loaded', {
            detail: {
                count: data.length,
                append,
                command: currentCommand
            }
        }));
    }

    function handleSearch() {
        window.dispatchEvent(new CustomEvent('ils:search-started'));

        currentCommand = null;
        currentPage = 1;
        isLoadingMore = false;

        const term = inputSearch.value.trim();

        if (!term.length) {
            hasMoreResults = false;
            renderSuggestions();
            resultsContainer.innerHTML = '';
            return;
        }

        // Slash command prediction
        if (InitPluginSuiteLiveSearch.enable_slash && term.startsWith('/') && !term.includes(' ')) {
            const keyword = term.slice(1).toLowerCase();
            const allCommands = InitPluginSuiteLiveSearch.commands || {};
            const matched = Object.keys(allCommands).filter(k => k.startsWith(keyword));

            if (matched.length >= 1 && matched.includes(keyword)) {
                parseSlashCommand(term);
                return;
            }

            if (matched.length) {
                renderSlashCommandList(matched);
                return;
            }
        }

        if (InitPluginSuiteLiveSearch.enable_slash && term.startsWith('/') && parseSlashCommand(term)) {
            suggestionBox.innerHTML = '';
            suggestionBox.classList.remove('ils-command-list');
            suggestionBox.style.display = 'none';
            return;
        }

        if (term.length < 2) {
            hasMoreResults = false;
            resultsContainer.innerHTML = '';
            return;
        }

        suggestionBox.style.display = 'none';
        resultsContainer.innerHTML = `<p class="ils-loading">${InitPluginSuiteLiveSearch.i18n.loading}</p>`;

        const cacheKey = `ils-cache-${term}`;
        if (InitPluginSuiteLiveSearch.use_cache) {
            const cached = localStorage.getItem(cacheKey);
            if (cached) {
                setCommand('search', 'term', term);
                renderResults(JSON.parse(cached));
                return;
            }
        }

        fetch(`${InitPluginSuiteLiveSearch.api}?term=${encodeURIComponent(term)}`)
            .then(res => res.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    hasMoreResults = false;
                    resultsContainer.innerHTML = `<p class="ils-empty">${InitPluginSuiteLiveSearch.i18n.no_results}</p>`;
                    return;
                }

                if (InitPluginSuiteLiveSearch.use_cache) {
                    localStorage.setItem(cacheKey, JSON.stringify(data));
                }

                setCommand('search', 'term', term);
                renderResults(data);
            })
            .catch(() => {
                hasMoreResults = false;
                resultsContainer.innerHTML = `<p class="ils-error">${InitPluginSuiteLiveSearch.i18n.error}</p>`;
            });
    }

    function debounce(fn, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    function updateSearchIcon(isClear) {
        btnSearch.classList.toggle('ils-clear-active', isClear);
        btnSearch.innerHTML = isClear
            ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M3 9h18v11h-9"/><path d="M8 4 3 9l5 5"/></svg>' // icon clear
            : '<svg width="20" height="20" viewBox="0 0 20 20"><circle fill="none" stroke="currentColor" stroke-width="1.1" cx="9" cy="9" r="7"></circle><path fill="none" stroke="currentColor" stroke-width="1.1" d="M14,14 L18,18 L14,14 Z"></path></svg>'; // icon search
    }

    document.addEventListener('click', function (e) {
        const target = e.target.closest('a[href*="modal=search"]');
        if (target) {
            const url = new URL(target.href, window.location.origin);
            const modal = url.searchParams.get('modal');
            const term = url.searchParams.get('term') || '';

            if (modal === 'search') {
                e.preventDefault();

                history.pushState(null, '', url.pathname + url.search);

                openModal();
                if (term.trim() && inputSearch) {
                    setTimeout(() => {
                        inputSearch.value = term.trim();
                        inputSearch.dispatchEvent(new Event('input'));
                    }, 300);
                }
            }
        }
    });

    if (InitPluginSuiteLiveSearch?.trigger?.input_focus) {
        document.addEventListener('focusin', function (e) {
            if (e.target.matches('input[name="s"]')) {
                e.preventDefault();
                openModal();
            }
        });
    }

    if (InitPluginSuiteLiveSearch?.trigger?.triple_click) {
        let clickCount = 0;
        let lastClickTime = 0;

        document.addEventListener('click', (e) => {
            const now = Date.now();
            const el = e.target;

            const excludedTags = [
                'input', 'textarea', 'button', 'a', 'label', 'select', 'option',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'p', 'span', 'strong', 'em', 'b', 'i', 'u', 'small', 'sub', 'sup',
                'code', 'pre', 'blockquote', 'li', 'dt', 'dd', 'th', 'td'
            ];

            if (excludedTags.includes(el.tagName.toLowerCase())) {
                clickCount = 0;
                return;
            }

            if (now - lastClickTime < 500) {
                clickCount++;
                if (clickCount >= 3) {
                    openModal();
                    clickCount = 0;
                }
            } else {
                clickCount = 1;
            }

            lastClickTime = now;
        });
    }

    if (InitPluginSuiteLiveSearch?.trigger?.ctrl_slash) {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                openModal();
            }
        });
    }

    document.addEventListener('click', function (e) {
        const target = e.target.closest('a[href="#init-live-search"]');
        if (target) {
            e.preventDefault();
            openModal();
        }
    });

    if (window.location.hash === '#search' || new URLSearchParams(window.location.search).get('modal') === 'search') {
        openModal();
        
        const urlParams = new URLSearchParams(window.location.search);
        const term = urlParams.get('term');
        if (term) {
            setTimeout(() => {
                inputSearch.value = term.trim();
                inputSearch.dispatchEvent(new Event('input'));
            }, 300);
        }
    }

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-ils]');
        if (trigger) {
            const command = trigger.getAttribute('data-ils')?.trim();
            if (command) {
                e.preventDefault();
                openModal();
                setTimeout(() => {
                    inputSearch.value = `${command} `;
                    inputSearch.dispatchEvent(new Event('input'));
                }, 200);
            }
        }
    });

    document.addEventListener('mouseup', () => {
        const config = window.InitPluginSuiteLiveSearch || {};
        const maxWords = config.max_select_word ?? 8;
        if (maxWords < 2) return;

        const selection = window.getSelection();
        if (!selection || selection.isCollapsed) return;

        const text = selection.toString().replace(/[“”‘’'"`~!@#$%^&*()\-+={}[\]|\\:;"<>,.?/_]/g, '').trim();
        const words = text.split(/\s+/).filter(Boolean);
        if (words.length < 2 || words.length > maxWords) return;

        if (document.querySelector('.ils-selection-tooltip')) return;

        const rect = selection.getRangeAt(0).getBoundingClientRect();
        const scrollY = window.scrollY || document.documentElement.scrollTop;
        const scrollX = window.scrollX || document.documentElement.scrollLeft;

        const link = document.createElement('a');
        link.href = '#';
        link.className = 'ils-selection-tooltip';
        link.setAttribute('data-ils', text);
        link.textContent = config.i18n?.quick_search || 'Quick search';
        document.body.appendChild(link);

        link.style.position = 'absolute';
        link.style.top = `${rect.top + scrollY - 52}px`;
        link.style.left = `${rect.left + scrollX + rect.width / 2}px`;
        link.style.transform = 'translateX(-50%)';
        link.style.zIndex = '9999';

        link.addEventListener('click', function (e) {
            e.preventDefault();
            if (window.InitLiveSearchAPI?.openModal) {
                window.InitLiveSearchAPI.openModal();
                setTimeout(() => {
                    const input = document.querySelector('#init-live-search-input');
                    if (input) {
                        input.value = text;
                        input.dispatchEvent(new Event('input'));
                        input.focus();
                    }
                    link.remove();
                }, 300);
            } else {
                link.remove();
            }
        });

        setTimeout(() => {
            const removeIfUnclicked = (ev) => {
                if (!link.contains(ev.target)) {
                    link.remove();
                }
            };
            document.addEventListener('mousedown', removeIfUnclicked, { once: true });
            document.addEventListener('scroll', () => link.remove(), { once: true });
        }, 50);
    });
});
