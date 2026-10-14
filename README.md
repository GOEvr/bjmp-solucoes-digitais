# BJMP Soluções Digitais

**Desenvolvimento de sites, sistemas web, automações e soluções com inteligência artificial.**

Site institucional: https://www.bjmpsolucoes.com.br/  
Contato: contato@bjmpsolucoes.com.br  
WhatsApp: https://wa.me/5524999599347

## Sobre o projeto

Este repositório contém os arquivos do site institucional da BJMP Soluções Digitais. O objetivo do site é apresentar os serviços, demonstrar projetos realizados e facilitar o contato de pessoas e empresas interessadas em soluções digitais.

A atuação é voltada ao atendimento remoto em todo o Brasil, com foco em criação de sites e landing pages, desenvolvimento de sistemas e aplicativos personalizados, automações e soluções com inteligência artificial.

## Principais arquivos

| Arquivo | Finalidade |
|---|---|
| `index.html` | Página principal do site, conteúdo institucional, portfólio e elementos de SEO. |
| `seo-head.html` | Trecho reutilizável de metadados de SEO, quando referenciado pelo projeto. Verificar sua integração antes de editar. |
| `robots.txt` | Orienta os robôs de busca sobre o acesso às áreas do site e indica o sitemap. |
| `sitemap.xml` | Lista as URLs que se deseja apresentar aos mecanismos de busca. |
| `site.webmanifest` | Metadados da experiência instalável do site, conforme a configuração existente. |
| `.nojekyll` | Evita o processamento Jekyll no GitHub Pages, quando aplicável. |

> Antes de alterar arquivos, confirme como o site é publicado e quais arquivos são efetivamente usados na hospedagem. Não remova arquivos auxiliares apenas por parecerem redundantes.

## Publicação

O repositório apresenta uma implantação do GitHub Pages. O domínio institucional também é `https://www.bjmpsolucoes.com.br/`.

Antes de publicar alterações:

1. Confira as mudanças no `index.html`, no `sitemap.xml` e no `robots.txt`.
2. Teste os links, as imagens, o vídeo do hero, o portfólio e o formulário de contato.
3. Verifique se os metadados canônicos apontam para `https://www.bjmpsolucoes.com.br/`.
4. Confirme que o sitemap contém apenas URLs públicas, canônicas e acessíveis.
5. Publique as alterações pelo fluxo de implantação configurado no repositório.
6. Após a publicação, abra o site e os arquivos públicos no navegador para confirmar o resultado.

A publicação no GitHub Pages pode levar alguns minutos para refletir as alterações.

## SEO e Google Search Console

O projeto inclui arquivos relacionados à descoberta e apresentação do site nos mecanismos de busca.

- URL canônica principal: `https://www.bjmpsolucoes.com.br/`
- Robots: `https://www.bjmpsolucoes.com.br/robots.txt`
- Sitemap: `https://www.bjmpsolucoes.com.br/sitemap.xml`

### Procedimento de verificação

1. Abra a propriedade de domínio `bjmpsolucoes.com.br` no Google Search Console.
2. Inspecione a URL canônica com `www`: `https://www.bjmpsolucoes.com.br/`.
3. Se o teste permitir, solicite a indexação.
4. Envie ou reenvie `https://www.bjmpsolucoes.com.br/sitemap.xml` no relatório **Sitemaps**.
5. Acompanhe o status do sitemap e o relatório de indexação de páginas.

A URL sem `www` pode aparecer como “Página com redirecionamento” se redirecionar para a versão canônica com `www`. Nesse caso, inspecione a URL de destino. Enviar um sitemap ou solicitar indexação não garante inclusão imediata nos resultados de pesquisa.

## Segurança e manutenção

Toda alteração de código deve passar por revisão antes de ser considerada concluída. A revisão mínima deve considerar:

- validação e sanitização das entradas;
- riscos de XSS e injeção SQL, quando aplicáveis;
- autenticação e autorização, quando aplicáveis;
- proteção contra CSRF e tentativas automatizadas, quando aplicáveis;
- exposição indevida de endpoints, dados, chaves ou segredos;
- controle de acesso a dados e tratamento seguro de erros;
- funcionamento do formulário e proteção contra abuso.

Essas verificações devem ser proporcionais à arquitetura real do projeto. Uma revisão estática não substitui testes de segurança dinâmicos nem um teste de invasão autorizado.

**Nunca** inclua senhas, tokens, chaves privadas, credenciais de hospedagem ou dados pessoais desnecessários neste repositório.

## Contato

- **E-mail:** contato@bjmpsolucoes.com.br
- **WhatsApp:** https://wa.me/5524999599347
- **Site:** https://www.bjmpsolucoes.com.br/

---

© BJMP Soluções Digitais. Todos os direitos reservados.
