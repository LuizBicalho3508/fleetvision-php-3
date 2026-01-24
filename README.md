```markdown
# 🚛 FleetVision - Sistema de Gestão de Frotas (SaaS)

> Plataforma completa de rastreamento veicular e gestão de frotas, integrada ao Traccar, com arquitetura Multi-Tenant (SaaS).

![Status](https://img.shields.io/badge/Status-Estável-green)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)
![Database](https://img.shields.io/badge/PostgreSQL-12%2B-336791)
![Integration](https://img.shields.io/badge/Traccar-API-orange)

## 📋 Sobre o Projeto

O **FleetVision** é um sistema web desenvolvido em PHP (MVC Nativo) para monitoramento em tempo real e gestão administrativa de frotas. Ele consome a API do **Traccar** para dados de GPS e oferece uma camada de gestão robusta para empresas de rastreamento.

### 🚀 Principais Funcionalidades

* **Rastreamento em Tempo Real:** Visualização de veículos no mapa (OpenStreetMap/Google) com atualização ao vivo.
* **Gestão de Frotas:** Cadastro completo de veículos, motoristas, manutenções e pneus.
* **Módulo Financeiro:** Controle de receitas (mensalidades) e despesas da frota.
* **Multi-Tenant (SaaS):** Suporte para múltiplas empresas/clientes no mesmo sistema, com dados isolados.
* **Painel Administrativo:** Gestão de clientes (Tenants), configuração de planos e personalização (Whitelabel).
* **API Interna (/sys):** Endpoints JSON para comunicação com o Frontend e Apps Mobile.
* **Landing Page Integrada:** Página de apresentação do produto pronta para conversão.

---

## 🛠️ Tecnologias Utilizadas

* **Backend:** PHP 8+ (Estrutura MVC Personalizada).
* **Frontend:** HTML5, JavaScript (Vanilla), TailwindCSS (CDN).
* **Banco de Dados:** PostgreSQL (Compatível com estrutura Traccar).
* **Servidor de Mapas:** Traccar (via API).
* **Servidor Web:** Apache (com `mod_rewrite`) ou Nginx.

---

## 📂 Estrutura de Diretórios

```text
/
├── app/                  # Núcleo da Aplicação
│   ├── Config/           # Configurações de Banco de Dados
│   ├── Controllers/      # Lógica de Negócio (MVC)
│   ├── Core/             # Roteador e Classes Base
│   ├── Middleware/       # Filtros de Acesso (Auth)
│   └── Services/         # Integrações (TraccarApi, Asaas)
├── views/                # Telas e Templates (HTML/PHP)
├── public/               # (Opcional) Assets públicos
├── uploads/              # Imagens e Logos dos Clientes
├── .htaccess             # Regras de Roteamento (Apache)
├── index.php             # Ponto de Entrada (Front Controller)
├── setup_db.php          # Script de Instalação do Banco
└── traccar_config.json   # Credenciais da API Traccar

```

---

## ⚙️ Pré-requisitos

1. **Servidor Web:** Apache ou Nginx.
2. **PHP:** Versão 8.0 ou superior (extensões `pdo`, `pdo_pgsql`, `curl` habilitadas).
3. **Banco de Dados:** PostgreSQL (o mesmo utilizado pelo Traccar).
4. **Traccar:** Instância do Traccar rodando (padrão: porta 8082).

---

## 🚀 Guia de Instalação

### 1. Clonar o Repositório

```bash
git clone [https://seu-repositorio.git](https://seu-repositorio.git) fleetvision
cd fleetvision

```

### 2. Configurar o Banco de Dados

O sistema utiliza a conexão definida em `app/Config/Database.php`.
Certifique-se de que o usuário do banco tenha permissão para criar tabelas.

### 3. Configurar Integração Traccar

Edite (ou crie) o arquivo `traccar_config.json` na raiz:

```json
{
    "url": "http://localhost:8082",
    "email": "admin",
    "password": "admin"
}

```

### 4. Permissões de Pasta

Dê permissão de escrita para a pasta de uploads:

```bash
chmod -R 777 uploads/

```

### 5. Instalação Automática (Setup)

Acesse a seguinte URL no navegador para criar as tabelas e o usuário administrador automaticamente:

```
[https://seu-dominio.com/setup_db.php](https://seu-dominio.com/setup_db.php)

```

*Após ver a mensagem de "SUCESSO", remova este arquivo por segurança.*

---

## 🖥️ Acesso ao Sistema

### Login Padrão (Super Admin)

* **URL:** `/login`
* **E-mail:** `admin@fleet.com`
* **Senha:** `password`

### Rotas Importantes

* **Landing Page:** `/` (Raiz)
* **Login:** `/login`
* **Dashboard:** `/admin/dashboard`
* **Documentação API:** `/api_docs`
* **Diagnóstico:** `/admin_teste`

---

## 🔧 Configuração do Servidor Web

### Apache (.htaccess)

O arquivo `.htaccess` já está incluso na raiz. Certifique-se de que o `mod_rewrite` está ativo no seu Apache.

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]
    RewriteRule ^ index.php [QSA,L]
</IfModule>

```

### Nginx (Exemplo de Configuração)

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# Proteção de Arquivos Sensíveis
location ~ ^/(app|config|includes|vendor|\.env|\.git) {
    deny all;
    return 404;
}

```

---

## 🐛 Diagnóstico e Solução de Problemas

Se encontrar erros 404 ou 500:

1. Acesse **`/admin_teste`** para rodar o diagnóstico automático de rotas e banco de dados.
2. Verifique se o arquivo `app/Config/Database.php` está apontando para o banco correto.
3. Se a API retornar 404, verifique se o prefixo `/sys` está sendo usado corretamente no Javascript (`views/layout.php`).

---

## 📄 Licença

Este projeto é proprietário e desenvolvido para uso comercial.
Todos os direitos reservados a **FleetVision**.

```

```