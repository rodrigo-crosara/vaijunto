# Carona.online (antigo VaiJunto) 🚗💨

**Carona.online** é uma solução moderna e leve para gestão de caronas colaborativas, focada em simplicidade, segurança e mobilidade. Desenvolvido como um **PWA (Progressive Web App)**, o sistema oferece uma experiência nativa de smartphone rodando diretamente no navegador através de PHP e MySQL.

## 🚀 Missão
Conectar motoristas e passageiros da mesma comunidade para reduzir custos de deslocamento, diminuir o tráfego e promover a integração social de forma segura e eficiente.

---

## 🛠️ Stack Tecnológica
- **Backend:** PHP 7.4+ (Arquitetura orientada a endpoints de API JSON).
- **Banco de Dados:** MySQL com motor `InnoDB` e suporte a `JSON fields`.
- **Frontend:** HTML5 Semântico, Vanilla JavaScript, jQuery (para manipulação assíncrona da DOM).
- **Estilização:** Tailwind CSS (via CDN/Componentes utilitários) para design responsivo "Mobile-First".
- **Componentes:** 
  - [Bootstrap Icons](https://icons.getbootstrap.com/)
  - [SweetAlert2](https://sweetalert2.github.io/) (Modais e alertas premium)
  - [jQuery Mask](https://igorescobar.github.io/jQuery-Mask-Plugin/) (Validação de inputs)

---

## ✨ Principais Funcionalidades

### 👤 Perfil & Autenticação
- **Login Simplificado:** Cadastro e login automático via número de celular.
- **Segurança:** Proteção por PIN de 4 dígitos e alteração segura exigindo PIN atual.
- **Onboarding:** Fluxo que garante que usuários tenham nome e foto antes de interagir.

### 🚘 Para Motoristas (Driver Mode)
- **Oferta de Carona:** Criação rápida de rotas com pontos intermediários (waypoints).
- **Painel de Gestão:** Acompanhamento em tempo real de passageiros confirmados e solicitações pendentes.
- **Controle de Vagas:** Opção de fechar vagas manualmente (lotado externamente).
- **Gestão de Pagamentos:** Confirmação manual de repasse de contribuição (badge PAID ✅).

### 🙋‍♂️ Para Passageiros (Passenger Mode)
- **Busca Inteligente:** Filtros por destino e horário com reset rápido.
- **Reserva com Contexto:** Solicitação de vaga informando ponto de encontro e observações.
- **Bilhete Digital:** Acesso rápido aos dados do motorista, placa do carro, chave Pix e link para WhatsApp.
- **Histórico:** Visualização de viagens passadas e status de confirmação.

### 🔔 Comunicação & Notificações
- **Alertas em Tempo Real:** Sistema de polling para notificações de novas reservas ou aceites.
- **Alertas Sonoros:** Notificação sonora e vibração (se suportado pelo dispositivo).
- **Integração WhatsApp:** Links diretos para facilitar o contato e o compartilhamento de viagens.

---

## 🛡️ Segurança & Integridade
- **Proteção XSS:** Todas as saídas de dados inseridas por usuários são sanitizadas com `htmlspecialchars`.
- **Prevenção de SQL Injection:** Uso estrito de `PDO` com *Prepared Statements*.
- **Integridade Referencial:** Banco de dados configurado com `ON DELETE CASCADE` para remoção limpa de contas.
- **Validação de Upload:** Checagem de MIME Type real (binário) para aceitar apenas JPG, PNG e WEBP.
- **Timezone Sync:** Sincronização forçada entre PHP e MySQL para o fuso horário de Brasília (`America/Sao_Paulo`).

---

## 📂 Estrutura do Projeto
- `/api`: Endpoints que processam a lógica de negócio e retornam JSON.
- `/assets`: Recursos estáticos (estilos, logos, scripts globais).
- `/config`: Configurações centrais (Conexão DB, Timezone).
- `/db`: Esquema do banco de dados e migrações.
- `/helpers`: Funções auxiliares (Notificações, formatação).
- `/includes`: Componentes da casca do app (Header, Footer, Nav).
- `/views`: Páginas do sistema (Feed, Profile, My Rides, etc).

---

## 🔧 Instalação
1. Clone o repositório em seu servidor local (ex: XAMPP, Laragon).
2. Importe o arquivo `/db/schema.sql` no seu MySQL.
3. Configure as credenciais no arquivo `/config/db.php`.
4. Certifique-se de que a pasta `/assets/media/uploads/` tenha permissão de escrita.
5. Acesse via navegador `localhost/vaijunto`.

---

## 📈 Roadmap / Futuro
- [ ] Implementação de Chats Internos.
- [ ] Integração com Maps API para cálculo de tempo real.
- [ ] Sistema de Geofencing para alertas de proximidade.
- [ ] Verificação de perfis via documento (Kyc).

---
*Este projeto foi desenvolvido com foco em performance e experiência de usuário "App-Like".*
