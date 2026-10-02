/* =========================================================
   NAF SOLUÇÕES DIGITAIS
   script.js
========================================================= */

'use strict';


/* =========================================================
   CONFIGURAÇÃO
========================================================= */

const CONTACT_ENDPOINT = '/api/contact.php';


/* =========================================================
   DOM READY
========================================================= */

document.addEventListener('DOMContentLoaded', () => {

    initHeader();
    initMobileMenu();
    initSmoothScroll();
    initRevealAnimation();
    initContactForm();

});


/* =========================================================
   HEADER
========================================================= */

function initHeader() {

    const header = document.querySelector('header');

    if (!header) {
        return;
    }

    const updateHeader = () => {

        if (window.scrollY > 40) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }

    };

    updateHeader();

    window.addEventListener(
        'scroll',
        updateHeader,
        {
            passive: true
        }
    );
}


/* =========================================================
   MENU MOBILE
========================================================= */

function initMobileMenu() {

    const toggle = document.querySelector('.menu-toggle');
    const menu = document.querySelector('.nav-menu');

    if (!toggle || !menu) {
        return;
    }

    toggle.addEventListener('click', () => {

        const isOpen =
            toggle.classList.toggle('active');

        menu.classList.toggle(
            'active',
            isOpen
        );

        toggle.setAttribute(
            'aria-expanded',
            String(isOpen)
        );

    });


    /*
     * Fecha o menu ao clicar em um link.
     */

    const links =
        menu.querySelectorAll('a');

    links.forEach(link => {

        link.addEventListener('click', () => {

            toggle.classList.remove('active');

            menu.classList.remove('active');

            toggle.setAttribute(
                'aria-expanded',
                'false'
            );

        });

    });


    /*
     * Fecha ao pressionar ESC.
     */

    document.addEventListener(
        'keydown',
        event => {

            if (
                event.key === 'Escape' &&
                menu.classList.contains('active')
            ) {

                toggle.classList.remove('active');

                menu.classList.remove('active');

                toggle.setAttribute(
                    'aria-expanded',
                    'false'
                );
            }

        }
    );
}


/* =========================================================
   SCROLL SUAVE
========================================================= */

function initSmoothScroll() {

    const links =
        document.querySelectorAll(
            'a[href^="#"]'
        );

    links.forEach(link => {

        link.addEventListener(
            'click',
            event => {

                const targetId =
                    link.getAttribute('href');

                if (
                    !targetId ||
                    targetId === '#'
                ) {
                    return;
                }

                const target =
                    document.querySelector(
                        targetId
                    );

                if (!target) {
                    return;
                }

                event.preventDefault();

                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

            }
        );

    });
}


/* =========================================================
   REVEAL
========================================================= */

function initRevealAnimation() {

    const elements =
        document.querySelectorAll('.reveal');

    if (!elements.length) {
        return;
    }

    /*
     * Se o navegador não oferecer IntersectionObserver,
     * mostramos os elementos normalmente.
     */

    if (
        !('IntersectionObserver' in window)
    ) {

        elements.forEach(element => {
            element.classList.add('visible');
        });

        return;
    }


    const observer =
        new IntersectionObserver(
            entries => {

                entries.forEach(entry => {

                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add(
                        'visible'
                    );

                    observer.unobserve(
                        entry.target
                    );

                });

            },
            {
                threshold: 0.12
            }
        );


    elements.forEach(element => {
        observer.observe(element);
    });
}


/* =========================================================
   FORMULÁRIO DE CONTATO
========================================================= */

function initContactForm() {

    const form =
        document.querySelector(
            '#contact-form'
        );

    if (!form) {
        return;
    }


    const status =
        form.querySelector(
            '.form-status'
        );

    const submitButton =
        form.querySelector(
            'button[type="submit"]'
        );


    form.addEventListener(
        'submit',
        async event => {

            event.preventDefault();


            /*
             * Evita duplo envio.
             */

            if (
                form.dataset.submitting === 'true'
            ) {
                return;
            }


            clearFormStatus(status);


            /*
             * Honeypot.
             */

            const honeypot =
                form.querySelector(
                    'input[name="website"]'
                );

            if (
                honeypot &&
                honeypot.value.trim() !== ''
            ) {

                /*
                 * Não informamos ao usuário
                 * que ele acionou o honeypot.
                 */

                showFormStatus(
                    status,
                    'success',
                    'Mensagem enviada com sucesso.'
                );

                form.reset();

                return;
            }


            /*
             * Captura dos dados.
             */

            const formData =
                new FormData(form);


            const name =
                String(
                    formData.get('name') || ''
                ).trim();

            const email =
                String(
                    formData.get('email') || ''
                ).trim();

            const phone =
                String(
                    formData.get('phone') || ''
                ).trim();

            const message =
                String(
                    formData.get('message') || ''
                ).trim();


            /*
             * Validação client-side.
             *
             * A validação real continuará
             * obrigatoriamente no servidor.
             */

            if (!validateName(name)) {

                showFormStatus(
                    status,
                    'error',
                    'Informe seu nome.'
                );

                return;
            }


            if (!validateEmail(email)) {

                showFormStatus(
                    status,
                    'error',
                    'Informe um e-mail válido.'
                );

                return;
            }


            if (phone.length > 30) {

                showFormStatus(
                    status,
                    'error',
                    'Informe um telefone válido.'
                );

                return;
            }


            if (
                message.length < 10 ||
                message.length > 3000
            ) {

                showFormStatus(
                    status,
                    'error',
                    'A mensagem deve ter entre 10 e 3000 caracteres.'
                );

                return;
            }


            /*
             * Prepara envio.
             */

            form.dataset.submitting = 'true';

            setSubmitState(
                submitButton,
                true
            );


            try {

                const response =
                    await fetch(
                        CONTACT_ENDPOINT,
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/json',

                                'Accept':
                                    'application/json'
                            },

                            credentials: 'same-origin',

                            body: JSON.stringify({
                                name,
                                email,
                                phone,
                                message,

                                /*
                                 * Campo honeypot.
                                 */

                                website:
                                    honeypot
                                        ? honeypot.value
                                        : ''
                            })
                        }
                    );


                /*
                 * Tenta interpretar JSON.
                 */

                let result = null;

                try {

                    result =
                        await response.json();

                } catch {
                    result = null;
                }


                if (
                    response.ok &&
                    result &&
                    result.success === true
                ) {

                    showFormStatus(
                        status,
                        'success',
                        result.message ||
                            'Mensagem enviada com sucesso.'
                    );

                    form.reset();

                } else {

                    showFormStatus(
                        status,
                        'error',
                        result?.message ||
                            'Não foi possível enviar sua mensagem. Tente novamente.'
                    );
                }


            } catch (error) {

                /*
                 * Não mostramos detalhes internos
                 * do erro ao visitante.
                 */

                console.error(
                    'Falha no envio do formulário:',
                    error
                );

                showFormStatus(
                    status,
                    'error',
                    'Não foi possível conectar ao servidor. Tente novamente.'
                );

            } finally {

                form.dataset.submitting = 'false';

                setSubmitState(
                    submitButton,
                    false
                );
            }

        }
    );
}


/* =========================================================
   VALIDAÇÃO DE NOME
========================================================= */

function validateName(name) {

    if (!name) {
        return false;
    }

    if (name.length > 100) {
        return false;
    }

    return true;
}


/* =========================================================
   VALIDAÇÃO DE E-MAIL
========================================================= */

function validateEmail(email) {

    if (!email) {
        return false;
    }

    if (email.length > 254) {
        return false;
    }

    /*
     * Validação básica para UX.
     *
     * A validação definitiva é feita
     * pelo PHP no servidor.
     */

    const emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailPattern.test(email);
}


/* =========================================================
   STATUS
========================================================= */

function showFormStatus(
    element,
    type,
    message
) {

    if (!element) {
        return;
    }

    /*
     * textContent em vez de innerHTML.
     *
     * Isso evita interpretar conteúdo recebido
     * como HTML.
     */

    element.textContent = message;

    element.classList.remove(
        'success',
        'error'
    );

    element.classList.add(type);
}


function clearFormStatus(element) {

    if (!element) {
        return;
    }

    element.textContent = '';

    element.classList.remove(
        'success',
        'error'
    );
}


/* =========================================================
   BOTÃO
========================================================= */

function setSubmitState(
    button,
    loading
) {

    if (!button) {
        return;
    }

    if (loading) {

        button.disabled = true;

        button.dataset.originalText =
            button.textContent;

        button.textContent =
            'Enviando...';

        button.setAttribute(
            'aria-busy',
            'true'
        );

    } else {

        button.disabled = false;

        button.textContent =
            button.dataset.originalText ||
            'Enviar mensagem';

        button.removeAttribute(
            'aria-busy'
        );
    }
}