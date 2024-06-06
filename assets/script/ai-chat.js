let lastBotHtmlMessage = '';

const btAiSearchScroll = () => {
    if (window.innerWidth < 992) {
        window.scrollTo(0, document.body.scrollHeight);
    } else {
        document.querySelectorAll('.bt-ai-search-conversation').forEach(conversation => {
            conversation.scrollTo(0, conversation.scrollHeight);
        });
    }
}

const getConversation = () => {
    const conversation = document.querySelector('.bt-ai-search-conversation');

    if (!conversation) {
        throw new Error('Conversation not found');
    }

    return conversation;
}

const getLastBotMessage = (conversation) => {
    if (!conversation) {
        conversation = getConversation();
    }

    const lastMessage = conversation.lastElementChild;

    return lastMessage.classList.contains('bt-ai-search-conversation-message-received')
        ? lastMessage
        : null;
}

const freezeForm = (formButton, searchInput) => {
    formButton.disabled = true;
    searchInput.dataset.placeholder = 'Întreabă în continuare';
    searchInput.innerText = '';
    searchInput.classList.add('disabled');
    lastBotHtmlMessage = '';
}

const unfreezeForm = (formButton, searchInput) => {
    formButton.disabled = false;
    searchInput.classList.remove('disabled');
    searchInput.dataset.placeholder = 'Întreabă în continuare';
    searchInput.dataset.previousPlaceholder = 'Întreabă în continuare';
    searchInput.innerText = '';
    searchInput.focus();
    lastBotHtmlMessage = '';
}

const resetBotMessageStyle = () => {
    const conversation = getConversation();

    conversation
        .querySelectorAll('.bt-ai-search-conversation-message-received-gradient')
        .forEach((element) => {
            element.classList.remove('bt-ai-search-conversation-message-received-gradient');
        });

    conversation
        .querySelectorAll('.bt-ai-search-conversation-message-actions')
        .forEach((element) => {
            element.innerHTML = '';
        });
}

const isContainHtml = (value) => {
    return (/(<[a-z][\s\S]*>)|(<\/[a-z][\s\S]*>)|<[a-z][\s\S]*\/>/i).test(value);
}

const serveEventData = (data) => {
    if (!data) {
        return;
    }

    const decoder = new TextDecoder("utf-8");

    let line = decoder
        .decode(data)
        .toString();

    if (line.startsWith(': ping') && line.endsWith('\r\n\r\n')) {
        return;
    }

    const lines = line
        .split('data: ')
        .map((line) => line.replace(/\n\n$/, ''))
        .filter((line) => line);

    if (!lines.length) {
        return;
    }

    lines.forEach((line) => {
        const event = JSON.parse(line);

        if (!event || !event.content) {
            return;
        }

        if (isContainHtml(event.content)) {
            lastBotHtmlMessage += event.content;
        } else {
            displayBotMessage(event.content);
        }
    });
}

const displayUserMessage = (message) => {
    if (!message) {
        return;
    }

    const conversation = getConversation();

    conversation.innerHTML += `
            <div class="bt-ai-search-conversation-message bt-ai-search-conversation-message-sent">
                <div class="bt-ai-search-conversation-message-user">
                    <img src="https://intreb.bancatransilvania.ro/themes/intreb-bt/assets/images/AI SEARCH USER.svg" alt=""/>
                </div>
                <div class="bt-ai-search-conversation-message-text">${message}</div>
            </div>
        `;

    btAiSearchScroll();
}

const displayBotMessage = (message) => {
    const conversation = getConversation();
    const lastBotMessage = getLastBotMessage(conversation);

    if (lastBotMessage === null) {
        conversation.innerHTML += `
                <div class="bt-ai-search-conversation-message bt-ai-search-conversation-message-received bt-ai-search-conversation-message-received-gradient">
                    <div class="bt-ai-search-conversation-message-user">
                        <img src="https://intreb.bancatransilvania.ro/themes/intreb-bt/assets/images/AI SEARCH INTREB LOGO.svg" alt=""/>
                    </div>
                    <div class="bt-ai-search-conversation-message-text">${message}</div>
                    <div class="bt-ai-search-conversation-message-actions"></div>
                </div>
            `;
    } else {
        lastBotMessage.querySelector('.bt-ai-search-conversation-message-text').append(message);
    }

    btAiSearchScroll();
}

const displayBotHtmlMessage = () => {
    if (!lastBotHtmlMessage) {
        return;
    }

    let lastBotMessage = getLastBotMessage();

    if (!lastBotMessage) {
        displayBotMessage('');
    }

    lastBotMessage = getLastBotMessage();

    lastBotMessage.querySelector('.bt-ai-search-conversation-message-text').innerHTML = lastBotHtmlMessage;

    btAiSearchScroll();
}

const displayResetButton = () => {
    const lastBotMessage = getLastBotMessage();

    if (!lastBotMessage) {
        return;
    }

    lastBotMessage.querySelector('.bt-ai-search-conversation-message-actions').innerHTML = `
        <button type="button" class="bt-ai-search-conversation-message-action-blue" data-refresh>Reîncepe conversația</button>
    `;

    btAiSearchScroll();
}

const onAIFormSubmit = async () => {
    const form = document.querySelector('.bt-ai-search-form');

    if (!form) {
        throw new Error('Form not found');
    }

    const formButton = form.querySelector('.bt-ai-search-form-field-submit-btn');
    const searchInput = form.querySelector('#msg');

    const message = searchInput ? searchInput.innerText : null;
    const token = document.querySelector("input[name=_token]").value;

    if (!formButton || formButton && formButton.disabled || !message || !token) {
        return;
    }

    freezeForm(formButton, searchInput)
    resetBotMessageStyle();
    displayUserMessage(message);

    try {
        const response = await fetch('/api/ai/chat/streaming', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                _token: token,
            }),
        })

        const reader = response.body.getReader();

        while (true) {
            const {value, done} = await reader.read();
            if (value) {
                serveEventData(value);
            }

            if (done) {
                displayBotHtmlMessage();
                displayResetButton();
                unfreezeForm(formButton, searchInput);

                break;
            }
        }
    } catch (e) {
        unfreezeForm(formButton, searchInput);
        console.error('Form request error', e.message);
    }
}

const onBtAiSearchKeypress = event => {
    document.querySelectorAll('#msg').forEach(element => {
        if (element.contains(event.target)) {
            if (element.classList.contains('disabled')) {
                event.preventDefault();
            } else {
                // remove all new line characters
                //element.innerText = element.innerText.replace(/(\r\n|\n|\r)/gm, '');

                // maximum 300 characters
                if (element.innerText.length >= 300) {
                    event.preventDefault();
                    //element.innerText = element.innerText.slice(0, 300);
                }

                if (
                    element.contains(event.target) &&
                    (event.key == 'Enter' || event.keyCode == 'Enter')
                ) {
                    event.preventDefault();
                    //element.innerText = element.innerText.replace(/(\r\n|\n|\r)/gm, '');
                    onAIFormSubmit();
                }
            }
        }
    });
}

document.addEventListener('keypress', onBtAiSearchKeypress);

document.addEventListener('click', event => {
    document.querySelectorAll('.bt-ai-search-form-field-submit-btn').forEach(element => {
        if (element.contains(event.target)) {
            onAIFormSubmit();
        }
    });

    document.querySelectorAll('[data-refresh]').forEach(element => {
        if (element.contains(event.target)) {
            location.reload(true);
        }
    });
});

setInterval(() => {
    document.querySelectorAll('.bt-ai-search-form-field-submit-btn .loading').forEach(loading => {
        if (!loading.dataset.positions) {
            loading.dataset.positions = 1;
        } else {
            positions = Number(loading.dataset.positions);
            positions++;

            if (positions > 3) {
                positions = 1;
            }

            loading.dataset.positions = positions;
        }
    });
}, 500);

setTimeout(() => {
    document.querySelectorAll('.bt-ai-search-mesaj-care-sa-dispara-dupa-20-de-secunde').forEach(element => {
        element.remove();
    });
}, 20000);
