const chatbotLauncher = document.getElementById('chatbot-launcher');
const chatbotWindow = document.getElementById('chatbot-window');
const chatbotInput = document.getElementById('chatbot-input');
const chatbotSend = document.getElementById('chatbot-send');
const chatbotMessages = document.getElementById('chatbot-messages');

// Abrir/Cerrar chat
function toggleChat() {
    chatbotWindow.style.display = (chatbotWindow.style.display === 'none' || chatbotWindow.style.display === '') ? 'flex' : 'none';
}

chatbotLauncher.onclick = toggleChat;

// Base de datos de respuestas básicas
const respuestas = [
    { keywords: ['hola', 'buen', 'que tal'], response: "¡Hola! Estoy aquí para ayudarte a navegar por el Almacén ERA." },
    { keywords: ['entrada', 'ingresar', 'registrar'], response: "Para registrar una entrada, ve a la sección 'Entradas' en el menú lateral o usa el botón verde '+' en el Dashboard." },
    { keywords: ['salida', 'despacho', 'retirar'], response: "Las salidas se registran en la sección 'Salidas' (botón rojo). Recuerda que no puedes retirar más de lo que hay en stock." },
    { keywords: ['producto', 'stock', 'inventario'], response: "En la pestaña 'Productos' puedes ver el catálogo completo, precios y el stock disponible actual." },
    { keywords: ['usuario', 'perfil', 'nombre'], response: "Tu nombre y puesto aparecen en la esquina superior derecha. Desde ahí puedes cerrar sesión." },
    { keywords: ['contraseña', 'password', 'pass'], response: "Puedes cambiar tu contraseña haciendo clic en tu nombre (arriba a la derecha) y seleccionando 'contraseña'." },
    { keywords: ['admin', 'permisos', 'reportes'], response: "Si no ves los Reportes o Empleados, es porque solo los Administradores tienen acceso a esas secciones." }
];

function addMessage(text, isUser = false) {
    const msg = document.createElement('div');
    msg.style.padding = '8px';
    msg.style.borderRadius = '8px';
    msg.style.marginBottom = '10px';
    msg.style.maxWidth = '85%';
    msg.style.fontSize = '0.85rem';
    
    if (isUser) {
        msg.style.background = '#3b82f6';
        msg.style.color = 'white';
        msg.style.marginLeft = 'auto';
    } else {
        msg.style.background = '#e2e8f0';
        msg.style.color = '#334155';
    }
    
    msg.innerText = text;
    chatbotMessages.appendChild(msg);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function procesarPregunta() {
    const input = chatbotInput.value.toLowerCase().trim();
    if (!input) return;

    addMessage(chatbotInput.value, true);
    chatbotInput.value = '';

    // Buscar coincidencia
    let encontrada = false;
    for (let r of respuestas) {
        if (r.keywords.some(k => input.includes(k))) {
            setTimeout(() => addMessage(r.response), 500);
            encontrada = true;
            break;
        }
    }

    if (!encontrada) {
        setTimeout(() => addMessage("Lo siento, no entiendo esa pregunta. Prueba con palabras como 'entrada', 'salida', 'productos' o 'contraseña'."), 500);
    }
}

chatbotSend.onclick = procesarPregunta;
chatbotInput.onkeypress = (e) => { if (e.key === 'Enter') procesarPregunta(); };