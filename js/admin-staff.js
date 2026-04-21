document.addEventListener('DOMContentLoaded', function() {
    setupTabNavigation();
});

function setupTabNavigation() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');

            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            tabContents.forEach(content => content.classList.remove('active'));

            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            const activeTab = document.getElementById(tabName);
            if (activeTab) {
                activeTab.classList.add('active');
            }
        });
    });
}


function editStaff(id) {
    console.log('Editar item:', id);
    alert('Funcionalidade de edição a ser implementada com AJAX/PHP');
}

function deleteStaff(id) {
    if (confirm('Tem certeza que deseja desativar este item?')) {
        console.log('Deletar item:', id);
        alert('Funcionalidade de deleção a ser implementada com AJAX/PHP');
    }
}

function handleFormSubmit(event) {
    event.preventDefault();
    console.log('Formulário submetido');
    alert('Formulário será processado com PHP/AJAX na fase final');
}
