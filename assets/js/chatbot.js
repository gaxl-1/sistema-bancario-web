/**
 * Chatbot Logic
 * Banco Seguro
 */

class Chatbot {
    constructor() {
        this.isOpen = false;
        this.knowledgeBase = this.getKnowledgeBase();
        this.init();
    }

    init() {
        this.render();
        this.attachEvents();
        setTimeout(() => this.addMessage('bot', '¡Hola! 👋 Soy el asistente virtual de Banco Seguro. ¿En qué puedo ayudarte hoy?'), 500);
    }

    render() {
        const widget = document.createElement('div');
        widget.className = 'chatbot-widget';
        widget.innerHTML = `
            <div class="chatbot-window" id="chatbotWindow">
                <div class="chatbot-header">
                    <div class="chatbot-title">
                        <i class="bi bi-robot"></i> Asistente Virtual
                    </div>
                    <button class="chatbot-close" id="closeChat">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="chatbot-messages" id="chatMessages">
                    <!-- Mensajes se agregarán aquí -->
                </div>
                <div class="chatbot-input-area">
                    <div style="width: 100%">
                        <div class="quick-replies" id="quickReplies">
                            <button class="quick-reply-btn" data-query="transferencia">💸 Transferencias</button>
                            <button class="quick-reply-btn" data-query="pagar servicio">🧾 Pagar Servicios</button>
                            <button class="quick-reply-btn" data-query="horario">🕒 Horarios</button>
                            <button class="quick-reply-btn" data-query="seguridad">🔒 Seguridad</button>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <input type="text" class="chatbot-input" id="chatInput" placeholder="Escribe tu consulta...">
                            <button class="chatbot-send" id="sendMessage">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <button class="chatbot-button" id="toggleChat">
                <i class="bi bi-chat-dots-fill"></i>
            </button>
        `;
        document.body.appendChild(widget);
    }

    attachEvents() {
        document.getElementById('toggleChat').addEventListener('click', () => this.toggleChat());
        document.getElementById('closeChat').addEventListener('click', () => this.toggleChat());

        const input = document.getElementById('chatInput');
        const sendBtn = document.getElementById('sendMessage');

        const handleSend = () => {
            const text = input.value.trim();
            if (text) {
                this.addMessage('user', text);
                input.value = '';
                this.processQuery(text);
            }
        };

        sendBtn.addEventListener('click', handleSend);
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') handleSend();
        });

        // Quick replies
        document.querySelectorAll('.quick-reply-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const text = btn.textContent.replace(/^[^\s]+\s/, ''); // Remove emoji
                this.addMessage('user', text);
                this.processQuery(btn.dataset.query);
            });
        });
    }

    toggleChat() {
        this.isOpen = !this.isOpen;
        const window = document.getElementById('chatbotWindow');
        const btn = document.getElementById('toggleChat');

        if (this.isOpen) {
            window.classList.add('active');
            btn.innerHTML = '<i class="bi bi-chevron-down"></i>';
            document.getElementById('chatInput').focus();
        } else {
            window.classList.remove('active');
            btn.innerHTML = '<i class="bi bi-chat-dots-fill"></i>';
        }
    }

    addMessage(type, text) {
        const messages = document.getElementById('chatMessages');
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${type}`;

        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        msgDiv.innerHTML = `
            <div class="message-content">${this.formatText(text)}</div>
            <div class="message-time">${time}</div>
        `;

        messages.appendChild(msgDiv);
        messages.scrollTop = messages.scrollHeight;
    }

    showTyping() {
        const messages = document.getElementById('chatMessages');
        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.id = 'typingIndicator';
        indicator.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;
        messages.appendChild(indicator);
        messages.scrollTop = messages.scrollHeight;
    }

    hideTyping() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.remove();
    }

    processQuery(query) {
        this.showTyping();

        // Simular delay de red
        setTimeout(() => {
            this.hideTyping();
            const response = this.findAnswer(query.toLowerCase());
            this.addMessage('bot', response);
        }, 1000);
    }

    findAnswer(query) {
        // Búsqueda simple por palabras clave
        for (const [key, data] of Object.entries(this.knowledgeBase)) {
            if (data.keywords.some(k => query.includes(k))) {
                return data.answer;
            }
        }
        return "Lo siento, no entendí tu consulta. Por favor, intenta reformularla o contacta a soporte técnico si necesitas ayuda específica.";
    }

    formatText(text) {
        // Convertir saltos de línea a <br>
        return text.replace(/\n/g, '<br>');
    }

    getKnowledgeBase() {
        return {
            transferencia: {
                keywords: ['transferencia', 'transferir', 'enviar dinero', 'pagar a'],
                answer: "Para realizar una transferencia:\n1. Ve a la sección 'Transferencias' en el menú lateral.\n2. Selecciona tu cuenta de origen.\n3. Ingresa la cuenta destino (formato ES...) y el monto.\n4. Confirma la operación.\n\nEl dinero llegará inmediatamente."
            },
            servicios: {
                keywords: ['servicio', 'luz', 'agua', 'gas', 'internet', 'pagar', 'factura'],
                answer: "Puedes pagar tus servicios desde la opción 'Pagar Servicios'.\nSolo necesitas seleccionar el tipo de servicio (Luz, Agua, etc.) y el número de referencia de tu factura."
            },
            cuenta: {
                keywords: ['cuenta', 'saldo', 'movimientos', 'historial'],
                answer: "En el 'Panel Principal' puedes ver un resumen de todas tus cuentas.\nPara ver detalles específicos y movimientos, ve a 'Mis Cuentas' o 'Historial'."
            },
            seguridad: {
                keywords: ['seguro', 'seguridad', 'clave', 'contraseña', 'password'],
                answer: "Tu seguridad es nuestra prioridad. Utilizamos encriptación avanzada para proteger tus datos.\nNunca compartas tu contraseña con nadie. Si notas actividad sospechosa, contáctanos inmediatamente."
            },
            horario: {
                keywords: ['horario', 'hora', 'abierto', 'oficina'],
                answer: "Nuestra banca online está disponible 24/7.\nEl soporte telefónico atiende de Lunes a Viernes de 8:00 a 20:00."
            },
            contacto: {
                keywords: ['contacto', 'email', 'teléfono', 'llamar', 'soporte'],
                answer: "Puedes contactarnos al:\n📞 900 123 456\n📧 soporte@bancoseguro.com"
            },
            saludo: {
                keywords: ['hola', 'buenos días', 'buenas tardes', 'hey'],
                answer: "¡Hola! ¿Cómo puedo ayudarte hoy?"
            },
            gracias: {
                keywords: ['gracias', 'ok', 'vale', 'listo'],
                answer: "¡De nada! Estoy aquí para ayudarte. ¿Necesitas algo más?"
            }
        };
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new Chatbot();
});
