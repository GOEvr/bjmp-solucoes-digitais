/*
 * BJMP Soluções Digitais — ferramentas WebMCP somente para consulta.
 * A API WebMCP ainda é experimental e não está disponível em todos os navegadores.
 * Nenhuma ferramenta envia formulários, altera dados ou acessa informações pessoais.
 */
(() => {
    "use strict";

    const context = document.modelContext;

    if (!context || typeof context.registerTool !== "function") {
        return;
    }

    const tools = [
        {
            name: "bjmp_list_services",
            description: "Lista os serviços públicos oferecidos pela BJMP Soluções Digitais e informa onde consultar detalhes.",
            inputSchema: {
                type: "object",
                properties: {},
                additionalProperties: false
            },
            annotations: {
                readOnlyHint: true
            },
            execute() {
                return {
                    company: "BJMP Soluções Digitais",
                    service_area: "Atendimento remoto em todo o Brasil",
                    services: [
                        {
                            name: "Landing Pages",
                            description: "Páginas estratégicas para apresentar produtos, serviços e campanhas e facilitar o contato.",
                            url: "https://www.bjmpsolucoes.com.br/servicos/#landing-pages"
                        },
                        {
                            name: "Sites Institucionais",
                            description: "Sites profissionais e responsivos para apresentar empresas, serviços e canais de atendimento.",
                            url: "https://www.bjmpsolucoes.com.br/servicos/#sites-institucionais"
                        },
                        {
                            name: "Sistemas Web",
                            description: "Aplicações web personalizadas para organizar informações e apoiar processos.",
                            url: "https://www.bjmpsolucoes.com.br/servicos/#sistemas-web"
                        },
                        {
                            name: "Automações e Inteligência Artificial",
                            description: "Integrações e fluxos automatizados, com IA quando adequada ao objetivo do projeto.",
                            url: "https://www.bjmpsolucoes.com.br/servicos/#automacoes-ia"
                        }
                    ]
                };
            }
        },
        {
            name: "bjmp_get_contact_information",
            description: "Retorna os canais públicos de contato da BJMP Soluções Digitais. Não envia mensagens nem realiza agendamentos.",
            inputSchema: {
                type: "object",
                properties: {},
                additionalProperties: false
            },
            annotations: {
                readOnlyHint: true
            },
            execute() {
                return {
                    company: "BJMP Soluções Digitais",
                    email: "contato@bjmpsolucoes.com.br",
                    whatsapp_url: "https://wa.me/5524999599347",
                    contact_page: "https://www.bjmpsolucoes.com.br/contato/",
                    service_area: "Atendimento remoto em todo o Brasil"
                };
            }
        }
    ];

    (async () => {
        for (const tool of tools) {
            try {
                await document.modelContext.registerTool(tool);
            } catch (error) {
                // Falha de compatibilidade não deve afetar a navegação do site.
                console.info("BJMP WebMCP: ferramenta não registrada neste navegador.", error);
            }
        }
    })();
})();
