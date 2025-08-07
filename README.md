### README.md ATUALIZADO

# Sistema de Vagas - WordPress Plugin

## 📋 Sobre o Plugin

Este é um sistema completo para gerenciar vagas de emprego e candidaturas no WordPress. A versão corrigida resolve todos os problemas identificados na versão anterior e adiciona funcionalidades importantes, como a gestão completa de templates de e-mail.

## 🔧 Problemas Corrigidos

### 1. Estrutura de Arquivos
- ✅ Corrigida a estrutura de pastas (`includes/` e `assets/css/`)
- ✅ Caminhos de includes e CSS funcionando corretamente
- ✅ Plugin carrega todos os arquivos necessários

### 2. Armazenamento de Candidaturas
- ✅ **NOVO:** Custom Post Type para candidaturas
- ✅ **NOVO:** Armazenamento completo no banco de dados do WordPress
- ✅ **NOVO:** Interface administrativa para gerenciar candidaturas
- ✅ **NOVO:** Relacionamento entre vagas e candidaturas

### 3. Melhorias de Segurança
- ✅ Validação aprimorada de arquivos
- ✅ Verificação de candidaturas duplicadas
- ✅ Sanitização melhorada de dados
- ✅ Nonce verification otimizada

### 4. Performance
- ✅ CSS carregado apenas quando necessário
- ✅ Queries otimizadas
- ✅ Cache implementado onde possível

-----

## 🚀 Novas Funcionalidades `(Seção Atualizada)`

### Interface Administrativa

  - **Dashboard Widget:** Estatísticas de candidaturas em tempo real.
  - **Filtros Avançados:** Por vaga, status, data.
  - **Ações em Massa:** Alterar status de múltiplas candidaturas.
  - **Relatórios:** Página dedicada com estatísticas detalhadas.
  - **Colunas Sortáveis:** Ordenação por qualquer campo.
  - ✨ **NOVO: Configurações de E-mail:** Interface para personalizar completamente os e-mails enviados aos candidatos.

### Gestão de Candidaturas

  - **Status Personalizados:** Nova, Em Análise, Entrevista, Aprovada, Rejeitada, Finalizada.
  - **Notificações Automáticas:** Envio de e-mail automático ao candidato quando o status da sua candidatura é alterado.
  - **Meta Boxes Completas:** Todas as informações organizadas.
  - **Histórico Completo:** IP, User Agent, data/hora.
  - **Arquivos Anexados:** Gestão de currículos em PDF.
  - **Observações Internas:** Campo para anotações da equipe.

### Melhorias no Formulário

  - **Auto-seleção de Vaga:** Em páginas de vaga específica.
  - **Validação Duplicada:** Impede candidaturas repetidas.
  - **Feedback Melhorado:** Mensagens de sucesso elaboradas.
  - **Responsividade:** Funciona perfeitamente em mobile.

-----

## 📁 Estrutura de Arquivos `(Seção Atualizada)`

```
sistema-vagas/
├── sistema-vagas.php              # Arquivo principal do plugin
├── assets/
│   └── css/
│       ├── formulario-vagas.css   # Estilos do formulário
│       └── vaga-detalhes.css      # Estilos dos detalhes da vaga
└── includes/
    ├── cpt-vagas.php              # Custom Post Type Vagas (melhorado)
    ├── cpt-candidaturas.php       # Custom Post Type Candidaturas (com lógica de e-mail)
    ├── formulario.php             # Formulário e processamento (corrigido)
    ├── admin-candidaturas.php     # Interface administrativa
    └── admin-email-settings.php   # ✨ NOVO: Configurações dos templates de e-mail
```

-----

## 🛠️ Instalação

*(Esta seção permanece a mesma)*

-----

## 📝 Como Usar `(Seção Atualizada)`

### Para Administradores

#### Gerenciar Vagas

1.  Acesse **Vagas** no menu administrativo.
2.  Clique em **Adicionar Nova** para criar uma vaga.
3.  Preencha todos os campos (empresa, salário, localização, etc.).
4.  Publique a vaga.

#### Gerenciar Candidaturas

1.  Acesse **Candidaturas** no menu administrativo.
2.  Visualize todas as candidaturas recebidas.
3.  Use os filtros para encontrar candidaturas específicas.
4.  Clique em uma candidatura para ver detalhes completos.
5.  Altere o status conforme o processo seletivo. **Um e-mail será enviado automaticamente para o candidato com base no novo status.**

#### ✨ Configurar E-mails de Notificação

1.  Acesse **Candidaturas \> Configurações de E-mail**.
2.  Para cada status (Em Análise, Entrevista, etc.), preencha os campos "Assunto" e "Mensagem".
3.  Utilize os placeholders (ex: `[nome_candidato]`) para personalizar as mensagens.
4.  Clique em "Salvar Alterações".

#### Relatórios

1.  Acesse **Candidaturas \> Relatórios**.
2.  Visualize estatísticas por vaga, status e período.
3.  Use as informações para tomar decisões estratégicas.

### Para Visitantes

*(Esta seção permanece a mesma)*

-----

## 🎯 Shortcodes Disponíveis

### Formulário Geral
```php
[formulario_vagas]
```

### Formulário para Vaga Específica
```php
[formulario_vagas vaga_id="123"]
```

### Formulário com Título Personalizado
```php
[formulario_vagas titulo="Trabalhe Conosco"]
```
-----

## ⚙️ Configurações Técnicas `(Seção Atualizada)`

### E-mails

  - ✨ **Templates Editáveis:** O assunto e o corpo do e-mail para cada status de candidatura podem ser totalmente personalizados em **Candidaturas \> Configurações de E-mail**.
  - **Envio Automático:** Os e-mails são disparados automaticamente quando o status de uma candidatura é alterado no painel administrativo.
  - **Placeholders Disponíveis:**
      - `[nome_candidato]`: Insere o nome do candidato.
      - `[titulo_vaga]`: Insere o título da vaga.
      - `[nome_empresa]`: Insere o nome da empresa contratante.
      - `[link_vaga]`: Insere o link para a página da vaga.
      - `[nome_site]`: Insere o nome do seu site.
  - **Remetente:** `Nome do Site <rh@salesprime.com.br>` (configurável no código).
  - **Confiabilidade:** Para garantir a entrega dos e-mails, é altamente recomendado o uso de um plugin de SMTP (como WP Mail SMTP).

### Arquivos

  - **Formato:** Apenas PDF aceito para currículos.
  - **Tamanho:** Máximo 5MB por arquivo.
  - **Armazenamento:** WordPress Media Library.
  - **Segurança:** Validação completa de tipo e tamanho.

### Performance

*(Esta seção permanece a mesma)*

-----

## 🎨 Personalização

### CSS
Os arquivos CSS estão em `assets/css/` e podem ser personalizados:
- `formulario-vagas.css`: Estilos do formulário
- `vaga-detalhes.css`: Estilos da página de vaga

### Cores e Layout
Todas as cores e layouts podem ser personalizados via CSS. O sistema usa:
- **Cores principais:** Azul (#0073aa), Verde (#28a745)
- **Layout:** Grid responsivo
- **Tipografia:** System fonts

### Hooks e Filtros
O plugin oferece vários hooks para personalização:
- `sv_antes_salvar_candidatura`
- `sv_depois_salvar_candidatura`
- `sv_email_candidatura_dados`
- `sv_formulario_campos_extras`

## 📊 Estatísticas e Relatórios

### Dashboard Widget
- Total de candidaturas
- Vagas ativas
- Candidaturas novas
- Candidaturas em análise
- Candidaturas recentes

### Página de Relatórios
- Candidaturas por vaga
- Candidaturas por status
- Candidaturas por período
- Links úteis para gestão

## 🔒 Segurança

### Validações Implementadas
- ✅ Nonce verification em todos os formulários
- ✅ Sanitização de todos os dados de entrada
- ✅ Validação de tipos de arquivo
- ✅ Verificação de tamanho de arquivo
- ✅ Prevenção de candidaturas duplicadas
- ✅ Escape de dados na saída

### Permissões
- **Candidaturas:** Apenas administradores podem visualizar
- **Vagas:** Públicas (configurável)
- **Arquivos:** Protegidos pelo WordPress
- **AJAX:** Verificação de nonce obrigatória

-----

## 🐛 Solução de Problemas `(Seção Atualizada)`

*(...)*

### Emails não são enviados

1.  **✨ Verifique os Templates:** Acesse **Candidaturas \> Configurações de E-mail** e confirme se os campos de assunto e mensagem para o status desejado estão preenchidos.
2.  **Configure SMTP no WordPress:** Utilize um plugin de SMTP para melhorar a confiabilidade da entrega.
3.  **Verifique configurações do servidor:** Veja se a função `wp_mail()` não está bloqueada na sua hospedagem.

-----

## 📞 Suporte

Para suporte técnico ou dúvidas:
- **Email:** suporte@rugemtugem.com.br
- **Site:** https://rugemtugem.com.br
- **Documentação:** Consulte este README

## 📄 Licença

Este plugin é licenciado sob GPL2. Você pode usar, modificar e distribuir livremente.

## 📝 Changelog

**Versão:** 1.3
**Compatibilidade:** WordPress 5.0+
**PHP:** 7.4+
**Testado até:** WordPress 6.4

  - **✨ Adicionado:** Sistema de templates de e-mail personalizáveis via painel administrativo, com placeholders dinâmicos.
  - **✨ Adicionado:** Envio automático de e-mails para candidatos ao alterar o status da candidatura.
  - **Melhoria:** Refatoração da lógica de salvamento de post para acionar o envio de e-mails de forma segura.

## 👤 Autor

Desenvolvido com ❤️ por RUGEMTUGEM