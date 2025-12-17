📋 Especificação Detalhada - Sistema Controle TI (Versão Simplificada)
🎯 VISÃO GERAL
Sistema web interno para gestão de TI com foco em:

Padronização (checklists)

Rastreabilidade (inventário)

Controle (processos e movimentações)

🔐 1. MÓDULO DE SEGURANÇA
Tela de Login
text
[ LOGIN CONTROLE TI ]
Usuário: [___________]
Senha:   [___________]
[ ENTRAR ]
"Problemas com acesso? Contate o administrador"
Fluxo de Autenticação
Primeiro acesso/Reset:

Usuário: [fornecido pelo admin]

Senha temporária: 102030

Sistema força alteração imediata

Administrador:

Usuário: admin (ou personalizável)

Senha inicial: 

Pode: criar/editar/excluir usuários, resetar senhas

Usuário Comum:

Acesso às funcionalidades operacionais

Seu nome registrado automaticamente em todas as ações

🏢 2. MÓDULO DE CADASTROS
2.1 Cadastro de Localidades
Propósito: Controlar onde estão os equipamentos

text
[ CADASTRO DE LOCALIDADES ]
+ Nova Localidade
─────────────────────────────────────
Nome: [__________________________] *
Código: [FL-001] *
Responsável: [___________________]
☑ Esta é uma filial nova?
    (ativa processos e checklists padrão)

[ SALVAR ] [ CANCELAR ]

─────────────────────────────────────
LISTA DE LOCALIDADES EXISTENTES:
[Buscar: _________]
┌─────────────────────────────────────┐
│ Filial SP Centro     | Cód: FL-001  │
│ Laboratório TI       | Cód: LAB-01  │
│ Filial RJ Nova       | Cód: FL-002  │ ← (NOVA)
└─────────────────────────────────────┘
Campos obrigatórios: Nome, Código

2.2 Cadastro de Tipos de Equipamento
Propósito: Categorizar equipamentos e associar checklists padrão

text
[ TIPOS DE EQUIPAMENTO ]
+ Novo Tipo
─────────────────────────────────────
Nome: [Computador Desktop] *
Descrição: [PC padrão da empresa...]

Checklists Padrão Associados:
☑ Checklist Instalação Básica Windows
☑ Checklist Segurança Antivírus
☐ Checklist Software Especializado
[ + Adicionar outro checklist ]

[ SALVAR ] [ CANCELAR ]

─────────────────────────────────────
TIPOS CADASTRADOS:
• Computador Desktop
• Notebook Corporativo
• Impressora Multifuncional
• Monitor LED
• Roteador WiFi
2.3 Cadastro de Checklists
Propósito: Criar modelos reutilizáveis de verificação

text
[ NOVO CHECKLIST ]
Nome: [Checklist Pós-Formatação] *
Descrição: [Verificações após formatação...]
☑ Checklist Padrão (aparece nas associações automáticas)

ITENS DO CHECKLIST:
┌──┬──────────────────────────────┬─────────────┐
│  │ Descrição do Item           │ Tipo Resp. │
├──┼──────────────────────────────┼─────────────┤
│1 │ Sistema operacional instalado│ ✅ Sim/Não  │
│2 │ Antivírus atualizado        │ ✅ Sim/Não  │
│3 │ Office 365 configurado      │ ✅ Sim/Não  │
│4 │ Anotações                   │ 📝 Texto    │
│5 │ Data conclusão              │ 📅 Data     │
└──┴──────────────────────────────┴─────────────┘
[ + Adicionar Item ] [ - Remover Item ]

Legenda tipos: ✅ Sim/Não | ⭕ OK/Não OK | 📝 Texto | 📅 Data
[ SALVAR ] [ CANCELAR ]
2.4 Cadastro de Processos
Propósito: Procedimentos para filiais (estrutura igual ao checklist)

text
[ NOVO PROCESSO ]
Nome: [Processo de Abertura de Filial] *
Descrição: [Passos para abrir nova filial...]
☑ Processo Padrão (associa a filiais novas)

ITENS DO PROCESSO:
┌──┬──────────────────────────────┬─────────────┐
│1 │ Contrato de internet ativo   │ ✅ Sim/Não  │
│2 │ Rack montado                 │ ✅ Sim/Não  │
│3 │ Backup configurado          │ ✅ Sim/Não  │
│4 │ Observações                 │ 📝 Texto    │
└──┴──────────────────────────────┴─────────────┘
💻 3. MÓDULO DE INVENTÁRIO
3.1 Cadastro de Equipamento
Propósito: Registrar cada item físico

text
[ NOVO EQUIPAMENTO ]
Dados Básicos:
Nome/Identificação: [PC-SALA01] *
Número de Série: [ABC123456] *
Patrimônio: [202400189]
Localidade: [▼ Filial SP Centro] *
Tipo: [▼ Computador Desktop] *
Fornecedor: [Fornecedor XYZ]
Data Entrada: [15/03/2024]

Status Atual: [● Ativo] ● Saída ● Manutenção ● Descartado

Checklists Associados:
☑ Checklist Instalação Básica Windows (herdado do tipo)
☑ Checklist Segurança Antivírus (herdado do tipo)
☐ [Adicionar outro checklist...]

Observações: [_________________________________]
─────────────────────────────────────
[ SALVAR ] [ CANCELAR ]
3.2 Controle de Movimentação
Registro automático quando status muda:

text
Histórico do Equipamento: PC-SALA01
┌────────────────┬─────────────────┬─────────────────────┐
│ Data/Hora      │ Status          │ Responsável         │
├────────────────┼─────────────────┼─────────────────────┤
│ 15/03/24 09:00 │ Entrada         │ Admin (Sistema)     │
│ 20/03/24 14:30 │ Manutenção      │ João Silva          │
│ Motivo: Tela piscando                         │
│ Fornecedor: Assistência técnica ABC           │
├────────────────┼─────────────────┼─────────────────────┤
│ 25/03/24 11:00 │ Ativo           │ Maria Santos        │
│ Observação: Tela trocada, OK                  │
└────────────────┴─────────────────┴─────────────────────┘
🛠️ 4. MÓDULOS OPERACIONAIS
4.1 Dashboard Principal
text
[ DASHBOARD - CONTROLE TI ]
Bem-vindo, [Nome do Usuário] | [ SAIR ]

┌──────────┬──────────┬──────────┬──────────┐
│ ATIVOS   │ SAÍDA    │ MANUT.   │ DESCART. │
│ 1.247    │ 32       │ 15       │ 8        │
└──────────┴──────────┴──────────┴──────────┘

🚨 ALERTAS:
• 3 equipamentos em manutenção há mais de 15 dias
• Filial "RJ Nova" tem 2 processos incompletos
• Checklist "Antivírus Q2" vence em 5 dias

📋 CHECKLISTS PENDENTES:
• PC-SALA01 - Checklist Manutenção Preventiva
• NB-MKT01 - Checklist Atualização Software

🏢 FILIAIS COM PROCESSOS:
• RJ Nova - 75% concluído (3/4 itens)
• SP Centro - 100% concluído

🕒 ATIVIDADES RECENTES:
• 10:15 - João executou checklist em PC-SALA01
• 09:30 - Maria cadastrou novo equipamento
• Ontem 16:45 - Admin resetou senha de Carlos
4.2 Aba "Localidades"
text
[ LOCALIDADES ]
[▼ Todas] [Buscar localidade: ___________]

┌─────────────────────────────────────────────────────────┐
│ FILIAL SP CENTRO (FL-001)                               │
├─────────────────────────────────────────────────────────┤
│ EQUIPAMENTOS: 45                                        │
│ • 32 Ativos | 5 Manutenção | 3 Saída | 5 Descartados    │
│                                                         │
│ PROCESSOS ASSOCIADOS:                                   │
│ ✅ Processo Manutenção Mensal (concluído 28/03)         │
│                                                         │
│ [ Ver Equipamentos ] [ Executar Processo ] [ Editar ]   │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ FILIAL RJ NOVA (FL-002) ⭐ NOVA                         │
├─────────────────────────────────────────────────────────┤
│ EQUIPAMENTOS: 12 (padrão)                               │
│ PROCESSOS PENDENTES:                                    │
│ ⏳ Processo Abertura Filial (1/4 itens)                  │
│                                                         │
│ [ Executar Processo ] [ Ver Checkists Pendentes ]       │
└─────────────────────────────────────────────────────────┘
4.3 Aba "Inventário"
text
[ INVENTÁRIO COMPLETO ]
Filtros: [▼ Todos Status] [▼ Todas Localidades] [▼ Todos Tipos]
Busca: [Nº série, patrimônio, nome...] [ BUSCAR ]

┌────┬──────────────────┬─────────────┬──────────┬─────────┐
│ID  │ Equipamento      │ Localidade  │ Tipo     │ Status  │
├────┼──────────────────┼─────────────┼──────────┼─────────┤
│023 │ PC-SALA01        │ SP Centro   │ Desktop  │ 🔧      │
│024 │ NB-MKT01         │ SP Centro   │ Notebook │ ✅      │
│025 │ IMPR-SALA02      │ RJ Nova     │ Impress. │ 📤      │
└────┴──────────────────┴─────────────┴──────────┴─────────┘
Mostrando 3 de 1.247 equipamentos

[ + NOVO EQUIPAMENTO ] [ EDITAR SELECIONADO ] [ EXPORTAR CSV ]
4.4 Aba "Executar Checklist"
text
[ EXECUTAR CHECKLIST ]
Equipamento: [▼ PC-SALA01] [ BUSCAR ]
Checklist disponíveis para este equipamento:
• Checklist Instalação Básica Windows
• Checklist Manutenção Preventiva
• Checklist Atualização Software

─────────────────────────────────────
CHECKLIST: MANUTENÇÃO PREVENTIVA
Equipamento: PC-SALA01
Local: Filial SP Centro
Data: 02/04/2024
Técnico: João Silva

ITENS:
1. [✅] Limpeza física interna concluída
2. [✅] Ventoinhas funcionando normalmente
3. [⭕] Temperatura dentro dos limites (⚠️ 72°C)
4. [📝] Observação: Trocar pasta térmica na próxima
5. [📅] Próxima manutenção: 02/05/2024

Status geral: ⚠️ Atenção necessária

[ SALVAR E FINALIZAR ] [ SALVAR RASCUNHO ] [ CANCELAR ]
4.5 Aba "Executar Processo"
text
[ EXECUTAR PROCESSO ]
Localidade: [▼ Filial RJ Nova]
Processo: [▼ Processo Abertura Filial]

PROCESSO: ABERTURA DE FILIAL
Localidade: Filial RJ Nova
Data início: 01/04/2024
Responsável: Maria Santos

ITENS:
1. [✅] Contrato de internet ativo
2. [✅] Rack montado e organizado
3. [ ] Backup configurado e testado
4. [📝] Observações: _______________________

Progresso: 50% (2/4 itens)

[ MARCAR COMO CONCLUÍDO ] [ SALVAR PROGRESSO ]
👥 5. MÓDULO ADMINISTRATIVO
5.1 Gerenciamento de Usuários
text
[ GERENCIAR USUÁRIOS ]
[ + NOVO USUÁRIO ]

┌─────────────────────────────────────────────────────┐
│ USUÁRIOS ATIVOS                                     │
├──────┬──────────────────┬─────────────┬─────────────┤
│ João │ joao.silva       │ Técnico TI  │ 10/03/2024  │
│      │                  │             │ [RESETAR]   │
├──────┼──────────────────┼─────────────┼─────────────┤
│ Maria│ maria.santos     │ Coordenadora│ 05/03/2024  │
│      │                  │             │ [RESETAR]   │
└──────┴──────────────────┴─────────────┴─────────────┘

[ NOVO USUÁRIO ]
Nome: [__________________________]
Usuário: [______________________] (para login)
Perfil: [▼ Técnico TI] [▼ Coordenador] [▼ Administrador]
[ GERAR SENHA TEMPORÁRIA ] [ CANCELAR ]
🔄 FLUXOS DE TRABALHO TÍPICOS
Cenário A: Chegada de equipamento novo
Técnico acessa Inventário → + Novo Equipamento

Preenche dados: nome, série, localidade, tipo

Sistema associa automaticamente checklists do tipo

Status automático: "Ativo"

Histórico: "Entrada" com data e técnico

Cenário B: Manutenção de equipamento
Na ficha do equipamento: altera status para "Manutenção"

Preenche motivo, fornecedor, previsão

Ao retornar: altera para "Ativo"

Executa "Checklist Pós-Manutenção"

Histórico registra toda a jornada

Cenário C: Nova filial
Admin cadastra localidade → Marca "Filial Nova"

Sistema automaticamente:

Associa "Processo Abertura Filial"

Sugere equipamentos padrão na criação

Técnico executa processo item por item

Dashboard mostra progresso em tempo real

📊 RELATÓRIOS DISPONÍVEIS
Inventário por localidade (PDF/Excel)

Checklists executados (por período/técnico)

Equipamentos em manutenção (com tempo)

Processos pendentes (por filial)

Movimentação mensal (entradas/saídas)

🎨 REGRAS DE NEGÓCIO
Status únicos: Um equipamento só pode ter um status por vez

Histórico imutável: Ações não podem ser apagadas, apenas novas registradas

Rastreabilidade: Toda ação registra usuário e timestamp

Padronização: Checklists e processos "padrão" aparecem em associações automáticas

Segurança: Senhas nunca exibidas, apenas resetadas
