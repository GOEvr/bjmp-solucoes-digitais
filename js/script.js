/* =========================================================
   NAF SOLUÇÕES DIGITAIS
   SCRIPT.JS
========================================================= */

"use strict";


/* =========================================================
   LOADER
========================================================= */

window.addEventListener("load", () => {

    const loader = document.getElementById("loader");

    if (!loader) {
        return;
    }

    setTimeout(() => {

        loader.classList.add("hidden");

    }, 700);

});


/* =========================================================
   MENU MOBILE
========================================================= */

const menuToggle = document.getElementById("menuToggle");
const mainNav = document.getElementById("mainNav");

if (menuToggle && mainNav) {

    menuToggle.addEventListener("click", () => {

        const isActive =
            mainNav.classList.toggle("active");

        menuToggle.setAttribute(
            "aria-expanded",
            String(isActive)
        );

        menuToggle.setAttribute(
            "aria-label",
            isActive
                ? "Fechar menu"
                : "Abrir menu"
        );

    });


    /*
     * Fecha o menu quando o usuário
     * seleciona um link.
     */

    mainNav
        .querySelectorAll("a")
        .forEach((link) => {

            link.addEventListener("click", () => {

                mainNav.classList.remove("active");

                menuToggle.setAttribute(
                    "aria-expanded",
                    "false"
                );

                menuToggle.setAttribute(
                    "aria-label",
                    "Abrir menu"
                );

            });

        });

}


/* =========================================================
   HEADER AO ROLAR
========================================================= */

const header = document.getElementById("header");

function updateHeader() {

    if (!header) {
        return;
    }

    if (window.scrollY > 60) {

        header.classList.add("scrolled");

    } else {

        header.classList.remove("scrolled");

    }

}

window.addEventListener(
    "scroll",
    updateHeader,
    {
        passive: true
    }
);

updateHeader();


/* =========================================================
   MOUSE GLOW
========================================================= */

const mouseGlow =
    document.getElementById("mouseGlow");

if (mouseGlow) {

    let mouseX = 0;
    let mouseY = 0;

    let currentX = 0;
    let currentY = 0;


    document.addEventListener(
        "mousemove",
        (event) => {

            mouseX = event.clientX;
            mouseY = event.clientY;

        },
        {
            passive: true
        }
    );


    function animateGlow() {

        currentX +=
            (mouseX - currentX) * 0.08;

        currentY +=
            (mouseY - currentY) * 0.08;

        mouseGlow.style.left =
            `${currentX}px`;

        mouseGlow.style.top =
            `${currentY}px`;

        requestAnimationFrame(
            animateGlow
        );

    }

    animateGlow();

}


/* =========================================================
   PARTÍCULAS
========================================================= */

const canvas =
    document.getElementById("particles");

const ctx =
    canvas
        ? canvas.getContext("2d")
        : null;


if (canvas && ctx) {

    const particles = [];

    let width = window.innerWidth;
    let height = window.innerHeight;


    function resizeCanvas() {

        width = window.innerWidth;
        height = window.innerHeight;

        const ratio =
            Math.min(
                window.devicePixelRatio || 1,
                2
            );

        canvas.width =
            width * ratio;

        canvas.height =
            height * ratio;

        canvas.style.width =
            `${width}px`;

        canvas.style.height =
            `${height}px`;

        ctx.setTransform(
            ratio,
            0,
            0,
            ratio,
            0,
            0
        );

    }


    class Particle {

        constructor() {

            this.x =
                Math.random() * width;

            this.y =
                Math.random() * height;

            this.size =
                Math.random() * 1.4 + 0.3;

            this.speedX =
                (Math.random() - 0.5) * 0.25;

            this.speedY =
                (Math.random() - 0.5) * 0.25;

            this.opacity =
                Math.random() * 0.4 + 0.1;

        }


        update() {

            this.x += this.speedX;
            this.y += this.speedY;


            if (this.x < 0) {
                this.x = width;
            }

            if (this.x > width) {
                this.x = 0;
            }

            if (this.y < 0) {
                this.y = height;
            }

            if (this.y > height) {
                this.y = 0;
            }

        }


        draw() {

            ctx.beginPath();

            ctx.arc(
                this.x,
                this.y,
                this.size,
                0,
                Math.PI * 2
            );

            ctx.fillStyle =
                `rgba(80,130,255,${this.opacity})`;

            ctx.fill();

        }

    }


    function createParticles() {

        particles.length = 0;

        /*
         * Mantém o número de partículas
         * proporcional ao tamanho da tela.
         */

        const amount =
            Math.min(
                60,
                Math.max(
                    25,
                    Math.floor(
                        (width * height) / 25000
                    )
                )
            );


        for (
            let i = 0;
            i < amount;
            i++
        ) {

            particles.push(
                new Particle()
            );

        }

    }


    function animateParticles() {

        ctx.clearRect(
            0,
            0,
            width,
            height
        );


        particles.forEach(
            (particle) => {

                particle.update();
                particle.draw();

            }
        );


        requestAnimationFrame(
            animateParticles
        );

    }


    resizeCanvas();
    createParticles();
    animateParticles();


    let resizeTimeout;

    window.addEventListener(
        "resize",
        () => {

            clearTimeout(
                resizeTimeout
            );

            resizeTimeout =
                setTimeout(() => {

                    resizeCanvas();
                    createParticles();

                }, 200);

        }
    );

}


/* =========================================================
   ANIMAÇÃO DOS ELEMENTOS
========================================================= */

const animatedElements =
    document.querySelectorAll(".fade-up");


if ("IntersectionObserver" in window) {

    const observer =
        new IntersectionObserver(
            (entries, observerInstance) => {

                entries.forEach(
                    (entry) => {

                        if (
                            entry.isIntersecting
                        ) {

                            entry.target.classList.add(
                                "visible"
                            );

                            observerInstance.unobserve(
                                entry.target
                            );

                        }

                    }
                );

            },
            {
                threshold: 0.12
            }
        );


    animatedElements.forEach(
        (element) => {

            observer.observe(element);

        }
    );

} else {

    /*
     * Fallback para navegadores
     * sem IntersectionObserver.
     */

    animatedElements.forEach(
        (element) => {

            element.classList.add(
                "visible"
            );

        }
    );

}


/* =========================================================
   FORMULÁRIO
========================================================= */

const contactForm =
    document.getElementById("contactForm");

const formStatus =
    document.getElementById("formStatus");

const submitButton =
    document.getElementById("submitButton");


/*
 * IMPORTANTE:
 *
 * Substitua o endereço abaixo pelo e-mail
 * real que deverá receber as mensagens.
 *
 * Não utilizamos "seuemail@dominio.com".
 */

const FORM_ENDPOINT =
    "https://formsubmit.co/ajax/contato@nafsolucoes.com";


if (contactForm) {

    contactForm.addEventListener(
        "submit",
        async (event) => {

            event.preventDefault();


            if (formStatus) {

                formStatus.textContent = "";
                formStatus.className =
                    "form-status";

            }


            const formData =
                new FormData(contactForm);


            /*
             * Honeypot:
             *
             * Se o campo oculto estiver preenchido,
             * consideramos o envio potencialmente
             * automatizado.
             */

            const honeypot =
                String(
                    formData.get("website") || ""
                ).trim();


            if (honeypot !== "") {

                return;

            }


            const nome =
                String(
                    formData.get("nome") || ""
                ).trim();

            const email =
                String(
                    formData.get("email") || ""
                ).trim();

            const telefone =
                String(
                    formData.get("telefone") || ""
                ).trim();

            const mensagem =
                String(
                    formData.get("mensagem") || ""
                ).trim();


            /*
             * Validação básica no cliente.
             *
             * A validação no servidor continua sendo
             * necessária caso o formulário seja migrado
             * para um backend próprio.
             */

            if (
                nome.length < 2 ||
                nome.length > 100
            ) {

                showFormError(
                    "Informe seu nome."
                );

                return;

            }


            if (
                !isValidEmail(email)
            ) {

                showFormError(
                    "Informe um e-mail válido."
                );

                return;

            }


            if (
                mensagem.length < 10 ||
                mensagem.length > 2000
            ) {

                showFormError(
                    "Escreva uma mensagem com pelo menos 10 caracteres."
                );

                return;

            }


            /*
             * Evita múltiplos envios enquanto
             * a requisição está sendo processada.
             */

            if (submitButton) {

                submitButton.disabled = true;

                submitButton.style.opacity =
                    "0.65";

                submitButton.innerHTML =
                    "Enviando...";

            }


            try {

                const response =
                    await fetch(
                        FORM_ENDPOINT,
                        {
                            method: "POST",

                            headers: {
                                "Accept":
                                    "application/json",
                                "Content-Type":
                                    "application/json"
                            },

                            body:
                                JSON.stringify({
                                    nome,
                                    email,
                                    telefone,
                                    mensagem,
                                    _subject:
                                        "Novo contato - NAF Soluções Digitais"
                                })
                        }
                    );


                if (!response.ok) {

                    throw new Error(
                        "Falha no envio."
                    );

                }


                if (formStatus) {

                    formStatus.textContent =
                        "Mensagem enviada com sucesso. Entraremos em contato.";

                    formStatus.className =
                        "form-status success";

                }


                contactForm.reset();


            } catch (error) {

                console.error(
                    "Erro no formulário:",
                    error
                );


                showFormError(
                    "Não foi possível enviar a mensagem agora. Tente novamente ou entre em contato pelo WhatsApp."
                );


            } finally {

                if (submitButton) {

                    submitButton.disabled =
                        false;

                    submitButton.style.opacity =
                        "";

                    submitButton.innerHTML =
                        'Enviar mensagem <span>→</span>';

                }

            }

        }
    );

}


/* =========================================================
   FUNÇÕES DO FORMULÁRIO
========================================================= */

function isValidEmail(email) {

    /*
     * Validação simples para interface.
     * Não substitui validação do servidor.
     */

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        .test(email);

}


function showFormError(message) {

    if (!formStatus) {
        return;
    }

    formStatus.textContent =
        message;

    formStatus.className =
        "form-status error";

}