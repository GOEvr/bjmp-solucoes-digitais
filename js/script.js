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

    initLoader();
    initHeader();
    initMobileMenu();
    initSmoothScroll();
    initRevealAnimation();
    initContactForm();
    initMouseGlow();
    initParticles();

});


/* =========================================================
   LOADER
========================================================= */

function initLoader() {

    const loader = document.getElementById('loader');

    if (!loader) {
        return;
    }

    window.addEventListener('load', () => {

        window.setTimeout(() => {

            loader.classList.add('loaded');

            window.setTimeout(() => {
                loader.remove();
            }, 700);

        }, 250);

    });

}


/* =========================================================
   HEADER
========================================================= */

function initHeader() {

    const header = document.getElementById('header');

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

    const toggle =
        document.getElementById('menuToggle');

    const menu =
        document.getElementById('mainNav');

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

        toggle.setAttribute(
            'aria-label',
            isOpen
                ? 'Fechar menu'
                : 'Abrir menu'
        );

    });


    /*
     * Fecha o menu ao clicar em um link.
     */

    const links =
        menu.querySelectorAll('a');

    links.forEach(link => {

        link.addEventListener('click', () => {

            closeMobileMenu(
                toggle,
                menu
            );

        });

    });


    /*
     * Fecha com ESC.
     */

    document.addEventListener(
        'keydown',
        event => {

            if (
                event.key === 'Escape' &&
                menu.classList.contains('active')
            ) {

                closeMobileMenu(
                    toggle,
                    menu
                );

                toggle.focus();

            }

        }
    );


    /*
     * Fecha se o usuário aumentar
     * a janela para desktop.
     */

    window.addEventListener(
        'resize',
        () => {

            if (
                window.innerWidth > 768 &&
                menu.classList.contains('active')
            ) {

                closeMobileMenu(
                    toggle,
                    menu
                );

            }

        },
        {
            passive: true
        }
    );

}


function closeMobileMenu(
    toggle,
    menu
) {

    toggle.classList.remove('active');

    menu.classList.remove('active');

    toggle.setAttribute(
        'aria-expanded',
        'false'
    );

    toggle.setAttribute(
        'aria-label',
        'Abrir menu'
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

                let target = null;

                try {

                    target =
                        document.querySelector(
                            targetId
                        );

                } catch {
                    return;
                }

                if (!target) {
                    return;
                }

                event.preventDefault();

                target.scrollIntoView({
                    behavior:
                        prefersReducedMotion()
                            ? 'auto'
                            : 'smooth',
                    block: 'start'
                });

            }
        );

    });

}


/* =========================================================
   REVEAL / FADE-UP
========================================================= */

function initRevealAnimation() {

    const elements =
        document.querySelectorAll(
            '.fade-up'
        );

    if (!elements.length) {
        return;
    }


    /*
     * Usuário prefere reduzir animações.
     */

    if (prefersReducedMotion()) {

        elements.forEach(element => {

            element.classList.add(
                'visible'
            );

        });

        return;
    }


    /*
     * Fallback para navegadores
     * sem IntersectionObserver.
     */

    if (
        !('IntersectionObserver' in window)
    ) {

        elements.forEach(element => {

            element.classList.add(
                'visible'
            );

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
                threshold: 0.12,
                rootMargin: '0px 0px -40px 0px'
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
        document.getElementById(
            'contactForm'
        );

    if (!form) {
        return;
    }


    const status =
        document.getElementById(
            'formStatus'
        );


    const submitButton =
        document.getElementById(
            'submitButton'
        );


    /*
     * Elementos do formulário.
     */

    const nameInput =
        document.getElementById('nome');

    const emailInput =
        document.getElementById('email');

    const phoneInput =
        document.getElementById('telefone');

    const messageInput =
        document.getElementById('mensagem');

    const honeypot =
        document.getElementById('website');


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
             * Honeypot anti-spam.
             *
             * Não informamos ao visitante
             * que o mecanismo foi acionado.
             */

            if (
                honeypot &&
                honeypot.value.trim() !== ''
            ) {

                showFormStatus(
                    status,
                    'success',
                    'Mensagem enviada com sucesso.'
                );

                form.reset();

                return;
            }


            /*
             * Captura e normalização.
             */

            const name =
                normalizeInput(
                    nameInput?.value
                );

            const email =
                normalizeInput(
                    emailInput?.value
                );

            const phone =
                normalizeInput(
                    phoneInput?.value
                );

            const message =
                normalizeInput(
                    messageInput?.value
                );


            /*
             * Validação client-side.
             *
             * A validação definitiva deve
             * permanecer no servidor.
             */

            if (!validateName(name)) {

                showFormStatus(
                    status,
                    'error',
                    'Informe seu nome.'
                );

                nameInput?.focus();

                return;
            }


            if (!validateEmail(email)) {

                showFormStatus(
                    status,
                    'error',
                    'Informe um e-mail válido.'
                );

                emailInput?.focus();

                return;
            }


            if (
                phone.length > 30
            ) {

                showFormStatus(
                    status,
                    'error',
                    'Informe um telefone válido.'
                );

                phoneInput?.focus();

                return;
            }


            if (
                message.length < 10 ||
                message.length > 2000
            ) {

                showFormStatus(
                    status,
                    'error',
                    'A mensagem deve ter entre 10 e 2000 caracteres.'
                );

                messageInput?.focus();

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

                            credentials:
                                'same-origin',

                            body:
                                JSON.stringify({
                                    name,
                                    email,
                                    phone,
                                    message,

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
                 * Log apenas no console.
                 *
                 * Detalhes internos não são
                 * exibidos ao visitante.
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

                form.dataset.submitting =
                    'false';

                setSubmitState(
                    submitButton,
                    false
                );

            }

        }
    );

}


/* =========================================================
   NORMALIZAÇÃO
========================================================= */

function normalizeInput(value) {

    if (
        value === null ||
        value === undefined
    ) {
        return '';
    }

    return String(value)
        .replace(/\u0000/g, '')
        .trim();

}


/* =========================================================
   VALIDAÇÃO DE NOME
========================================================= */

function validateName(name) {

    if (!name) {
        return false;
    }

    if (name.length < 2) {
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
     * A validação definitiva permanece
     * no servidor.
     */

    const emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailPattern.test(email);

}


/* =========================================================
   STATUS DO FORMULÁRIO
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
     * textContent evita interpretação
     * de conteúdo como HTML.
     */

    element.textContent =
        String(message);

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
   BOTÃO DE ENVIO
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


/* =========================================================
   MOUSE GLOW
========================================================= */

function initMouseGlow() {

    const glow =
        document.getElementById(
            'mouseGlow'
        );

    if (!glow) {
        return;
    }

    /*
     * Não executa o efeito em dispositivos
     * sem apontador preciso.
     */

    if (
        !window.matchMedia(
            '(pointer: fine)'
        ).matches
    ) {
        return;
    }

    if (prefersReducedMotion()) {
        return;
    }


    let frame = null;

    let mouseX = 0;
    let mouseY = 0;


    document.addEventListener(
        'mousemove',
        event => {

            mouseX = event.clientX;
            mouseY = event.clientY;

            if (frame !== null) {
                return;
            }

            frame =
                window.requestAnimationFrame(
                    () => {

                        glow.style.transform =
                            `translate3d(${mouseX}px, ${mouseY}px, 0)`;

                        frame = null;

                    }
                );

        },
        {
            passive: true
        }
    );

}


/* =========================================================
   PARTICLES
========================================================= */

function initParticles() {

    const canvas =
        document.getElementById(
            'particles'
        );

    if (!canvas) {
        return;
    }

    if (prefersReducedMotion()) {
        return;
    }


    const context =
        canvas.getContext('2d');

    if (!context) {
        return;
    }


    const particleCount =
        window.innerWidth < 768
            ? 25
            : 45;


    const particles = [];


    function resizeCanvas() {

        const ratio =
            Math.min(
                window.devicePixelRatio || 1,
                2
            );

        canvas.width =
            Math.floor(
                window.innerWidth * ratio
            );

        canvas.height =
            Math.floor(
                window.innerHeight * ratio
            );

        canvas.style.width =
            `${window.innerWidth}px`;

        canvas.style.height =
            `${window.innerHeight}px`;

        context.setTransform(
            ratio,
            0,
            0,
            ratio,
            0,
            0
        );

    }


    function createParticle() {

        return {
            x:
                Math.random() *
                window.innerWidth,

            y:
                Math.random() *
                window.innerHeight,

            size:
                Math.random() * 1.4 + 0.4,

            speed:
                Math.random() * 0.25 + 0.05,

            opacity:
                Math.random() * 0.35 + 0.05
        };

    }


    function resetParticles() {

        particles.length = 0;

        for (
            let index = 0;
            index < particleCount;
            index++
        ) {

            particles.push(
                createParticle()
            );

        }

    }


    function draw() {

        context.clearRect(
            0,
            0,
            window.innerWidth,
            window.innerHeight
        );


        particles.forEach(
            particle => {

                particle.y -=
                    particle.speed;


                if (particle.y < -5) {

                    particle.y =
                        window.innerHeight + 5;

                    particle.x =
                        Math.random() *
                        window.innerWidth;

                }


                context.beginPath();

                context.arc(
                    particle.x,
                    particle.y,
                    particle.size,
                    0,
                    Math.PI * 2
                );


                context.fillStyle =
                    `rgba(0, 85, 255, ${particle.opacity})`;

                context.fill();

            }
        );


        window.requestAnimationFrame(
            draw
        );

    }


    resizeCanvas();

    resetParticles();

    draw();


    window.addEventListener(
        'resize',
        () => {

            resizeCanvas();
            resetParticles();

        },
        {
            passive: true
        }
    );

}


/* =========================================================
   REDUCED MOTION
========================================================= */

function prefersReducedMotion() {

    return window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

}