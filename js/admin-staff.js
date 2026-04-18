/* ================================================================
   ADMIN STAFF - JAVASCRIPT
   Funcionalidades interativas do painel de administração
   ================================================================ */

document.addEventListener('DOMContentLoaded', function() {
    setupTabNavigation();
});

/**
 * Configura navegação entre abas (Treinadores e Staff)
 */
function setupTabNavigation() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');

            // Remove classe active de todos os botões e conteúdos
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Adiciona classe active ao botão e conteúdo clicado
            this.classList.add('active');
            const activeTab = document.getElementById(tabName);
            if (activeTab) {
                activeTab.classList.add('active');
            }
        });
    });
}

/**
 * Exemplo: Funções para editar/deletar (a serem expandidas com PHP/AJAX)
 */

// Editar treinador/staff
function editStaff(id) {
    console.log('Editar item:', id);
    // Implementar depois com AJAX
    alert('Funcionalidade de edição a ser implementada com AJAX/PHP');
}

// Deletar treinador/staff
function deleteStaff(id) {
    if (confirm('Tem certeza que deseja desativar este item?')) {
        console.log('Deletar item:', id);
        // Implementar depois com AJAX
        alert('Funcionalidade de deleção a ser implementada com AJAX/PHP');
    }
}

// Submeter formulário (exemplo)
function handleFormSubmit(event) {
    event.preventDefault();
    console.log('Formulário submetido');
    // Implementar depois com AJAX/PHP
    alert('Formulário será processado com PHP/AJAX na fase final');
}
