const onCompleteReply = data => {
    let form = document.querySelector('.bt-ai-search-form'),
        submitBtn = form.querySelector('.bt-ai-search-form-field-submit-btn'),
        searchInput = form.querySelector('#msg');

    displayMessage({
        text: data[0].content,
        type: 'received'
    });

    if (data[1]) {
        document.querySelectorAll('.bt-ai-search-form').forEach(element => {
            element.remove();
        });

        displayMessage({
            text: data[1].content,
            type: 'received',
            last: true
        });
    }

    searchInput.classList.remove('disabled');
    searchInput.dataset.placeholder = 'Întreabă în continuare';
    searchInput.dataset.previousPlaceholder = 'Întreabă în continuare';
    searchInput.innerText = '';
    searchInput.focus();
    submitBtn.disabled = false;
};

const btAiSearchScroll = () => {
    if (window.innerWidth < 992) {
        window.scrollTo(0, document.body.scrollHeight);
    } else {
        document.querySelectorAll('.bt-ai-search-conversation').forEach(conversation => {
            conversation.scrollTo(0, conversation.scrollHeight);
        });
    }
}

const displayMessage = content => {
    if (content.hasOwnProperty('text') && content.hasOwnProperty('type')) {

        document.querySelectorAll('.bt-ai-search-conversation').forEach(conversation => {
            if (content.text != '') {
                let className = `bt-ai-search-conversation-message bt-ai-search-conversation-message-${content.type}`;
                if (content.hasOwnProperty('last')) {
                    className += ' bt-ai-search-conversation-message-received-gradient';
                }

                let html = `<div class="${className}">
                    <div class="bt-ai-search-conversation-message-user">
                        <img src="https://intreb.bancatransilvania.ro/themes/intreb-bt/assets/images/${content.type == 'sent' ? 'AI SEARCH USER' : 'AI SEARCH INTREB LOGO'}.svg"/>
                    </div>
                    <div class="bt-ai-search-conversation-message-text">${content.text}</div>`;

                    if (content.hasOwnProperty('last')) {
                        html += `<div class="bt-ai-search-conversation-message-actions">
                            <button type="button" class="bt-ai-search-conversation-message-action-blue" data-refresh>Reîncepe conversația</button>
                        </div>`;
                    }

                html += `</div>`;

                conversation.innerHTML += html;

                if (content.type == 'sent') {
                    btAiSearchScroll();
                } else {
                    const lastMessage = document.querySelectorAll('.bt-ai-search-conversation-message-received')[document.querySelectorAll('.bt-ai-search-conversation-message-received').length - 1];
                    if (lastMessage) {
                        const animation = gsap.timeline({onComplete: btAiSearchScroll});
                        let mySplitText = new SplitText(lastMessage.querySelectorAll('.bt-ai-search-conversation-message-text'), { type: 'words,chars' }),
                            chars = mySplitText.chars;

                        chars.forEach(char => {
                            animation.from(char, {
                                duration: char.innerText.length * gsap.utils.random(.01, .04, .01),
                                text: '',
                                ease: 'none',
                                onUpdate: btAiSearchScroll,
                                onComplete: btAiSearchScroll
                            });
                        });
                    }
                }
            }
        });
    }
}

const onAIFormSubmit = () => {
    let form = document.querySelector('.bt-ai-search-form');

    if (form) {
        let submitBtn = form.querySelector('.bt-ai-search-form-field-submit-btn'),
            searchInput = form.querySelector('#msg');

        if (!submitBtn.disabled && !searchInput.classList.contains('disabled')) {
            $.request('onValidateMessage', {
                data: {
                    msg: searchInput.innerText,
                    _token: document.querySelector("input[name=_token]").value
                },
                complete: data => {
                    if (data['msg']) {
                        displayMessage({
                            text: data['msg'],
                            type: 'sent'
                        });

                        // const socket = new WebSocket('wss://37.251.255.8:8000/chat/streaming');
                        //
                        // socket.onmessage = function (event) {
                        //     console.log(event.data);
                        // };
                        //
                        // socket.send('{"messages": [{"role": "user", "content": "Ce faci?"}]}');

                        $.request('onMessage', {
                            data: {
                                msg: data['msg'],
                                _token: document.querySelector("input[name=_token]").value
                            },
                            complete: data => {
                                onCompleteReply(data);
                            }
                        });

                        submitBtn.disabled = true;
                        searchInput.dataset.placeholder = 'Întreabă în continuare';
                        searchInput.innerText = '';
                        searchInput.classList.add('disabled');
                    }
                }
            });
        }
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
