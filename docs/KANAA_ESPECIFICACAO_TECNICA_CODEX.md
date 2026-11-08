# Kanaã — Especificação técnica para desenvolvimento

**Versão 0.2 · Escopo inicial e evolução**

## 1. Produto

A Kanaã aproxima pessoas de suas comunidades de fé e ajuda a transformar a descoberta online em participação presencial. O lançamento é voltado ao público evangélico brasileiro, com espaço para diferentes denominações, igrejas independentes, ministérios e pequenos grupos. O nome exibido é **Kanaã** e o identificador técnico pode ser **kanaa**.

A jornada principal é encontrar uma igreja ou comunidade, consultar sua agenda, decidir participar e, se quiser, registrar a visita. Em viagem, a pessoa pode pesquisar pelo destino antes de sair ou consultar a região onde está. Conexões entre pessoas são complementares. A área de solteiros é opcional e funciona com interesse mútuo e autorização de contato. Chat privado interno fica fora desta versão.

## 2. Papéis e comunidades

- **Visitante** consulta páginas públicas de igrejas e eventos. Para seguir, confirmar presença, publicar ou fazer check-in, precisa entrar na conta.
- **Usuário** mantém perfil, segue igrejas, participa de grupos, controla visibilidade, denuncia conteúdo e bloqueia pessoas.
- **Administrador de igreja** edita a página, cadastra unidades, delega funções e acompanha os eventos da organização.
- **Editor de igreja** publica avisos e administra eventos das unidades autorizadas. Permissões são conferidas no servidor a cada operação.
- **Gestor de grupo** administra seu grupo e suas solicitações de entrada.
- **Moderação da plataforma** analisa denúncias e solicitações de verificação institucional, com trilha de auditoria.

Uma igreja pode ter várias unidades, cada uma com endereço, localização, horários habituais e agenda. A página reúne descrição, denominação opcional, canais oficiais, fotos, informações de acessibilidade e unidades. Igrejas reivindicam páginas com comprovação por canal oficial e análise da plataforma. O selo indica verificação da gestão da página. Páginas suspensas saem das buscas.

Comunidades podem ser independentes ou vinculadas a igrejas, como ministérios, células e grupos de estudo. Podem ser públicas, de entrada mediante aprovação ou privadas por convite. Seguir igreja e ingressar em grupo são ações distintas. Grupos privados não aparecem na busca pública. Uma igreja pode publicar avisos no mural, mesmo sem criar evento.

## 3. Agenda e participação

Eventos têm título, descrição, organizador, tipo, unidade ou local, início, fim, fuso IANA, modalidade, visibilidade e situação. Tipos iniciais incluem culto, reunião, estudo bíblico, congresso, encontro de jovens e voluntariado. O organizador informa se visitantes são bem-vindos, capacidade opcional, necessidade de inscrição e condições de acesso. Localização ou link de eventos restritos só aparece para quem tem acesso.

- Estados de evento: rascunho, publicado, cancelado e encerrado. Alterações de data ou local e cancelamentos geram avisos a quem confirmou presença.
- Eventos recorrentes geram ocorrências próprias. Cada ocorrência pode ser reagendada ou cancelada sem afetar check-ins anteriores. O editor distingue editar esta ocorrência de editar as próximas.
- A confirmação é opcional e pode ser cancelada. Quando houver limite de vagas, a confirmação reserva uma vaga com transação e bloqueio concorrente. Uma confirmação repetida retorna a existente. A lista nominal de participantes fica restrita à equipe autorizada, conforme a finalidade informada.
- Horários habituais da igreja aparecem como informação institucional. Resultados de eventos por data usam ocorrências publicadas e distinguem horários habituais de encontros específicos.

## 4. Igrejas próximas e modo viagem

A busca aceita cidade/UF ou bairro como entrada manual e localização atual sob permissão explícita. A pessoa escolhe raio, período, tipo de encontro, denominação ou tradição, presença online/presencial e abertura a visitantes. A busca pode mostrar igrejas sem encontro no período em uma seção própria.

O modo viagem usa um destino e um intervalo escolhidos para aquela consulta. Exemplo: pesquisar cultos abertos a visitantes em Curitiba no próximo domingo, começando por igrejas da tradição que a pessoa escolheu. O destino não altera a cidade do perfil. Com permissão de localização negada, a busca manual continua funcionando.

- Exibir lista com distância aproximada, bairro, denominação e próximo encontro. Mapa é uma visualização complementar. Eventos online aparecem em lista própria quando filtrados.
- Exibir motivo da familiaridade, como “Denominação selecionada” ou “Igreja da rede que você segue”. Usar preferências declaradas e vínculos autorizados, com filtro para desligar essa priorização. Não deduzir crenças a partir de check-ins.
- Aplicar acesso, estado do evento, janela de datas e raio antes da ordenação. Oferecer ordenação por proximidade ou início. Sem coordenadas válidas, usar resultado por cidade quando aplicável, sem estimar distância.
- Geocodificar endereços institucionais ao salvar, com revisão manual do ponto. Não persistir localização precisa da pessoa para busca, nem registrá-la em logs. Permitir abrir rota para endereço público da igreja em serviço externo.

## 5. Check-in

O check-in é um registro voluntário, separado da confirmação antecipada. Pode apontar para uma unidade ou para uma ocorrência publicada à qual a pessoa tenha acesso. O check-in em evento ocorre durante sua janela configurada, com tolerância definida pela plataforma. O registro manual é identificado como autodeclarado. QR de verificação pode ser incorporado posteriormente.

1. Escolher igreja ou evento e conferir o local.
2. Escolher a audiência. Opções iniciais: só eu, minhas conexões ou membros do grupo, quando aplicável. O padrão é **só eu**. Um check-in nunca amplia a audiência de um evento restrito.
3. Registrar. O mural mostra apenas registros que o leitor tem autorização de ver. O usuário pode mudar a visibilidade ou excluir o registro.
4. Repetir a ação para o mesmo evento retorna o check-in existente. Para visitas à unidade, usar uma chave de idempotência para evitar duplicações por reenvio e permitir visitas em datas diferentes.

O registro não divulga coordenadas pessoais nem habilita rastreamento em tempo real. Bloqueios impedem exposição entre as contas em buscas de pessoas, mural e sugestões. Check-ins privados não alimentam sugestões nem listas nominais da igreja.

## 6. Pessoas, afinidades e contato

A descoberta geral permite encontrar pessoas pelas comunidades em comum quando ambas permitem essa visibilidade. A área **Solteiros da Kanaã** tem perfil separado, adesão opcional e acesso restrito a adultos. Não ativar automaticamente a partir de estado civil ou do perfil geral. Na primeira versão, a pessoa pode declarar afinidades como fé, família, ministérios e voluntariado, além de escolher sua região de busca. Exibir cidade ou região aproximada. A idade exibida pode ser calculada a partir da data de nascimento privada.

| Etapa | Regra proposta |
|---|---|
| Interesse | A pessoa sinaliza interesse privado. Um interesse unilateral não libera contato. |
| Match | Dois interesses ativos geram um único match. A plataforma avisa as duas pessoas. |
| Autorização | Cada pessoa escolhe qual contato compartilhar, como WhatsApp, e-mail ou Instagram. A liberação é proposta somente após autorização de ambas. |
| Contato | O sistema mostra apenas o canal escolhido, em tela autenticada e enquanto o match estiver ativo. A conversa acontece no serviço escolhido. |
| Revogação | Desfazer match, revogar autorização, pausar o perfil ou bloquear encerra o acesso pelo sistema. Informar no momento da liberação que dados já copiados podem permanecer com quem os recebeu. |

Manter contatos fora do perfil público, das sugestões, das notificações e das respostas genéricas da API. Exigir validação de idade no servidor para ativar a área e decidir a verificação de maioridade antes do piloto. Restringir descoberta de perfis a participantes elegíveis e ativos. Disponibilizar denúncia, bloqueio e pausa a partir do perfil e da tela de match. Limitar envio de interesses para conter spam. A conexão geral entre irmãos e o match de solteiros são fluxos separados.

## 7. Arquitetura proposta

Laravel como aplicação modular, PostgreSQL como banco, interface web responsiva em português e armazenamento de mídia separado. A escolha de versões, biblioteca visual, provedor de mapas e geocodificação fica para o início da implementação. Usar autenticação por sessão para web, verificações de autorização por recurso, filas para avisos e geocodificação e jobs para materializar ocorrências futuras. Usar PostGIS, se disponível no ambiente, para busca por raio com ponto geográfico e índice espacial. Testar o provedor com endereços brasileiros e revisar os custos e condições antes de contratar.

Centralizar regras de audiência, participação e match. Publicar avisos após commit da transação e tornar jobs idempotentes. Usar paginação, índices de busca e datas, auditoria das ações administrativas e registros estruturados sem dados de contato ou localização pessoal. Separar ambientes e usar dados fictícios em desenvolvimento.

| Entidade | Campos e relações essenciais |
|---|---|
| usuários e perfis | Conta, nome, foto, cidade opcional, estado da conta e preferências de visibilidade |
| igrejas e unidades | Igreja, denominação opcional, gestão verificada, dados institucionais, endereço e ponto da unidade |
| comunidades e membros | Igreja opcional, tipo, visibilidade, papel e estado da participação. Uma adesão por pessoa e comunidade |
| publicações | Autor, igreja ou comunidade, conteúdo, mídia, audiência, estado de moderação |
| séries e ocorrências | Organizador, unidade opcional, regra de repetição, datas UTC, fuso, modalidade, visibilidade e situação |
| confirmações | Ocorrência, pessoa, estado. Chave única por pessoa e ocorrência |
| check-ins | Autor, unidade ou ocorrência, data, audiência, origem e chave de idempotência |
| perfis de solteiros e afinidades | Pessoa, adesão, estado do perfil, região, afinidades declaradas |
| interesses e matches | Origem, destino e estado. Interesse único por direção, match único por par ordenado |
| autorizações de contato | Match, titular, canal escolhido, consentimento, revogação e valor protegido |
| bloqueios, denúncias e auditoria | Atores, recurso, motivo, estado, decisão e datas. Evidências restritas |

## 8. Rotas de referência

| Método e rota | Operação |
|---|---|
| GET /igrejas | Buscar por cidade ou coordenadas, raio e filtros |
| GET /eventos | Buscar ocorrências por local, datas e familiaridade |
| POST /igrejas/{igreja}/publicacoes | Publicar como editor autorizado |
| POST /igrejas/{igreja}/eventos | Criar evento ou série |
| PUT /ocorrencias/{ocorrencia}/confirmacao | Confirmar presença |
| DELETE /ocorrencias/{ocorrencia}/confirmacao | Cancelar confirmação |
| POST /check-ins | Registrar check-in em unidade ou ocorrência |
| PATCH /check-ins/{checkin} | Ajustar audiência |
| DELETE /check-ins/{checkin} | Excluir registro próprio |
| POST /solteiros/interesses | Registrar interesse e verificar reciprocidade |
| PUT /solteiros/matches/{match}/autorizacao | Autorizar o canal selecionado |
| DELETE /solteiros/matches/{match}/autorizacao | Revogar compartilhamento próprio |
| GET /solteiros/matches/{match}/contato | Consultar contato autorizado, com resposta sem cache compartilhado |

As rotas são referências para implementação. Aplicar validação de campos, controle de acesso, paginação quando houver listas e limites por conta e origem. Usar 403 para ações proibidas quando revelar a existência do recurso for aceitável, 404 para recursos privados invisíveis e 409 para conflitos como vaga esgotada.

## 9. Privacidade e operação

Vínculos religiosos, localização e contato exigem controles desde o desenho. Coletar somente o necessário, documentar finalidades e prazos de retenção, separar dados públicos de restritos e permitir revisar visibilidade, baixar dados e solicitar exclusão. Antes do piloto, validar o tratamento de dados com a pessoa responsável pela privacidade do produto. Usar HTTPS, proteção de campos de contato, remoção de metadados de localização de fotos e acesso administrativo limitado. Evitar dados pessoais em telemetria. Denúncias cobrem assédio, intolerância, perfis falsos e representação indevida de igreja. Registrar decisões de moderação e disponibilizar contestação.

## 10. Telas e ordem de entrega

1. **Fundação:** cadastro, perfil, início, páginas de igrejas e unidades, gestão e verificação institucional, comunidades e mural.
2. **Descoberta e agenda:** busca local e em viagem, detalhes do evento, administração de ocorrências, confirmação e avisos.
3. **Presença:** check-in, seletor de audiência, mural e histórico pessoal.
4. **Conexões:** descoberta geral, área opcional de solteiros, interesse, match, autorização de contato, denúncias e bloqueios.

As quatro etapas compõem o escopo inicial. A navegação móvel prioriza Início, Explorar, Comunidades, Agenda e Perfil. O botão de check-in aparece na página da unidade e no evento elegível. A identidade visual séria pode aproveitar verde, creme, dourado e o motivo do caminho. O símbolo final é uma decisão de marca separada.

## 11. Critérios de aceite do piloto

- Uma igreja com duas unidades publica agendas próprias e delega a edição de somente uma unidade.
- Um visitante pesquisa outra cidade e encontra cultos nas datas escolhidas sem conceder localização ou alterar seu perfil.
- Filtros por tradição retornam igrejas familiares e explicam a afinidade sem usar histórico de check-in.
- Alterar uma ocorrência recorrente não altera as demais. Cancelar um evento atualiza a agenda e avisa inscritos.
- Duas pessoas disputando a última vaga não excedem a capacidade. Reenvios não duplicam confirmações nem check-ins.
- Um check-in privado é visível apenas ao autor. Restrições do evento e bloqueios são respeitados mesmo em acesso por URL direta.
- Uma pessoa inelegível não acessa a área adulta. Interesses simultâneos geram um único match. Contato permanece oculto até dupla autorização e deixa de aparecer após revogação ou bloqueio.
- Os fluxos principais funcionam em celular e desktop, com navegação por teclado e busca utilizável em lista.

## 12. Decisões a validar antes de implementar

Confirmar se a navegação pública deve ser indexada, quais comprovações são aceitas para páginas de igrejas, como verificar maioridade, quais canais podem ser compartilhados e quais prazos de retenção serão aplicados. Definir ainda raio inicial, tolerância de check-in e política de participação de menores nas áreas gerais. O fluxo de contato por dupla autorização, o check-in autodeclarado e a stack proposta são hipóteses de trabalho desta versão.

## 13. Evolução do produto e piloto regional

As funcionalidades desta seção complementam o escopo inicial. Validar primeiro a agenda regional e o planejamento da primeira visita, com um grupo de igrejas de uma região onde seja possível acompanhar a adesão diretamente. O modo viagem usa a mesma oferta de eventos e unidades, consultada por destino e período.

| Funcionalidade | Fluxo e regras propostas |
|---|---|
| Planejar minha primeira visita | A página da unidade ou do evento informa acessibilidade, estacionamento, programação infantil e abertura a visitantes. A pessoa pode avisar à recepção que pretende ir. Compartilhar apenas o necessário com a equipe autorizada, mediante escolha explícita. O aviso pode ser cancelado e é distinto de confirmação de presença e check-in. |
| Agenda cristã da cidade | Agregar ocorrências públicas, com filtros por cidade, data e tipo. Permitir seguir cidades e receber resumo periódico opcional, com opção de desligar. Excluir eventos cancelados e restritos do resumo público. |
| Servir perto de mim | Igrejas e ministérios publicam ações de voluntariado com data, local, requisitos e vagas. A pessoa pode se inscrever ou cancelar. Aplicar o mesmo controle de capacidade da agenda quando houver vagas limitadas. Gestão de equipes e escalas pode evoluir depois. |
| Calendário de comunidades | Reunir a programação autorizada das igrejas seguidas e dos grupos dos quais a pessoa participa. Links compartilháveis respeitam a audiência original, inclusive após mudanças de acesso. |
| Encontros coletivos para solteiros | Comunidades podem divulgar cafés, passeios e ações de voluntariado para adultos. A inscrição não ativa perfil de solteiros nem libera contatos. O compartilhamento individual continua sujeito a interesse mútuo e autorização de ambas as pessoas. |

Modelar preferências de agenda por usuário e município, pedidos de recepção por pessoa e unidade ou ocorrência, e inscrições de voluntariado vinculadas à ocorrência. Reutilizar o controle de acesso e os estados da agenda. Avisos à recepção e resumos por cidade devem ser enviados após a gravação da mudança, com deduplicação.

## 14. Receita e valoração

Manter gratuitos a busca, a consulta às agendas, a página básica da igreja e o check-in. Testar duas ofertas, com preço e disposição a pagar a validar no piloto:

- **Assinatura institucional.** Gestão de equipes e unidades, fluxo de recepção, escalas de voluntariado e relatórios agregados.
- **Serviço por evento.** Inscrições, lista de espera e credenciamento por QR para organizadores. Separar o credenciamento institucional do check-in social.

Vínculos religiosos, localização pessoal e check-ins individuais ficam fora da oferta comercial. Relatórios institucionais devem respeitar a finalidade da coleta e evitar recortes pequenos que identifiquem pessoas. Validar uso e disposição a pagar antes de implementar cobrança recorrente ou integração de pagamentos.

| Métrica mensal | Definição inicial |
|---|---|
| Igrejas com agenda ativa | Igrejas com ao menos uma ocorrência futura publicada na data de corte. Acompanhar quantas permanecem ativas no mês seguinte. |
| Uso recorrente | Usuários que consultam a agenda ou realizam ações de participação em meses consecutivos, agrupados pelo mês de entrada. |
| Conversão em participação | Medir separadamente intenção de visita, confirmação, check-in autodeclarado e credenciamento, quando houver. Usar denominadores explícitos por etapa. |
| Receita e retenção institucional | Contas pagantes, receita recorrente mensal, renovação e cancelamentos. Acompanhar receita avulsa de eventos separadamente. |
| Custo de aquisição e atendimento | Custos de aquisição por organização pagante e custo mensal de suporte por conta, com metodologia registrada. |

Esses indicadores servem para avaliar frequência de uso, densidade regional de agendas, capacidade de retenção e sustentabilidade da receita. A primeira validação é observar se as igrejas mantêm a agenda atualizada e se visitantes encontram encontros e usam o planejamento de visita. Definir as metas do piloto após estabelecer uma linha de base.

### Critérios adicionais de aceite

- Uma unidade pode informar orientações para visitantes e receber um aviso de visita autorizado, que pode ser cancelado.
- O resumo de uma cidade inclui somente ocorrências públicas e publicadas. O usuário consegue desligá-lo.
- Um link de calendário não revela eventos privados. Inscrições simultâneas respeitam o limite de vagas de voluntariado.
- Uma inscrição em encontro coletivo de solteiros não expõe contato pessoal nem ativa perfil de relacionamento.
- O painel do piloto separa confirmação, visita planejada e check-in, com totais agregados.
