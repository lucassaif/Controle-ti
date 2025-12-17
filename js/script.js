// Funções gerais do sistema

// Formatar datas
function formatarData(data) {
    if (!data) return '';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR');
}

// Formatar data e hora
function formatarDataHora(data) {
    if (!data) return '';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR');
}

// Máscara para input de código
function aplicarMascaraCodigo(input) {
    input.addEventListener('input', function(e) {
        let valor = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
        this.value = valor;
    });
}

// Validação de formulários
function validarFormulario(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('[required]');
    let valido = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'var(--danger-color)';
            valido = false;
        } else {
            input.style.borderColor = '';
        }
    });
    
    return valido;
}

// Mostrar/esconder seções
function toggleSecao(id) {
    const secao = document.getElementById(id);
    if (secao) {
        secao.style.display = secao.style.display === 'none' ? 'block' : 'none';
    }
}

// Confirmar exclusão
function confirmarExclusao(mensagem = 'Tem certeza que deseja excluir?') {
    return confirm(mensagem);
}

// Carregar dados dinâmicos (selects dependentes)
function carregarDadosSelect(url, selectId, valorInicial = '') {
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = '<option value="">Selecione...</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.nome;
                    if (item.id == valorInicial) option.selected = true;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Erro ao carregar dados:', error));
}

// Atualizar contador de caracteres
function atualizarContador(textareaId, contadorId, maxLength) {
    const textarea = document.getElementById(textareaId);
    const contador = document.getElementById(contadorId);
    
    if (textarea && contador) {
        textarea.addEventListener('input', function() {
            const atual = this.value.length;
            contador.textContent = `${atual}/${maxLength}`;
            
            if (atual > maxLength) {
                contador.style.color = 'var(--danger-color)';
                this.value = this.value.substring(0, maxLength);
            } else if (atual > maxLength * 0.9) {
                contador.style.color = 'var(--warning-color)';
            } else {
                contador.style.color = 'var(--secondary-color)';
            }
        });
    }
}

// Copiar para área de transferência
function copiarParaClipboard(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        alert('Copiado para a área de transferência!');
    }).catch(err => {
        console.error('Erro ao copiar:', err);
    });
}

// Gerar código automático
function gerarCodigoLocalidade() {
    const prefixo = 'FL-';
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    return prefixo + random;
}

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar máscaras
    document.querySelectorAll('[data-mask="codigo"]').forEach(aplicarMascaraCodigo);
    
    // Auto-complete para data atual
    document.querySelectorAll('[data-today]').forEach(input => {
        if (input.type === 'date' && !input.value) {
            input.valueAsDate = new Date();
        }
    });
    
    // Tooltips básicos
    document.querySelectorAll('[title]').forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-custom';
            tooltip.textContent = this.title;
            tooltip.style.position = 'absolute';
            tooltip.style.background = '#333';
            tooltip.style.color = 'white';
            tooltip.style.padding = '5px 10px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.zIndex = '1000';
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = (rect.left + window.scrollX) + 'px';
            tooltip.style.top = (rect.top + window.scrollY - tooltip.offsetHeight - 5) + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                delete this._tooltip;
            }
        });
    });
});
