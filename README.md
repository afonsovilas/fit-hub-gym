# FitHub - LTW Milestone 1

Primeira entrega focada em HTML + CSS.

## Páginas incluídas

1. `index.html` - Landing/Home page
2. `schedules.html` - Lista e filtros de aulas
3. `admin-staff.html` - Gestão de treinadores e staff (formulários + tabelas)

As 3 páginas usam componentes e estruturas diferentes para cobrir padrões variados de UI.

## Estrutura CSS

O styling está organizado em camadas num único ficheiro `css/style.css`:

1. Base: variáveis CSS, reset e estilos globais de elementos HTML.
2. Componentes: botões, cartões, formulários, tabelas, estados e padrões reutilizáveis.
3. Casos específicos: ajustes por secção/página (hero, headers de página, áreas de gestão, etc.).

A estrutura da página admin-staff.html tem um design ligeiramente diferente principalmente no menu, pois não pertence ao website (front-office) , vai futuramente fazer parte do backoffice do admin.

## Como abrir o projeto

### Opção A - Sem servidor

1. Abrir `index.html` no browser.
2. Usar o menu para navegar para `schedules.html` e `admin-staff.html`.

### Opção B - Servidor local PHP (opcional)

1. Na raiz do projeto, executar:

```bash
php -S localhost:9000
```

2. Abrir no browser:

`http://localhost:9000/index.html`

## Nota desta milestone

Esta entrega está limitada ao front-end (HTML + CSS) das páginas principais.
