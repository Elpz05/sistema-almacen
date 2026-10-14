<?php 
// 1. Incluimos el guardián de sesión y seguridad
include 'auth.php'; 
// Solo el administrador puede gestionar empresas
restringirAdmin(); 
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas | Almacén Era</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Menú de usuario desplegable */
        .user-info { position: relative; cursor: pointer; }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-top: 10px;
            z-index: 1100;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .dropdown-menu a {
            display: flex;
            align-items: center; gap: 10px;
            padding: 12px 15px;
            color: #334155;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .dropdown-menu a:hover { background: #f1f5f9; }

        /* Ajuste Bot */
        #chatbot-window { flex-direction: column; }
    </style>
</head>
<body>
    <div class="main-container">
        <nav class="sidebar">
            <div class="sidebar-header"><i class="fas fa-warehouse"></i> <span>Almacén Era</span></div>
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="entradas.php"><i class="fas fa-arrow-down"></i> Entradas</a></li>
                <li><a href="salidas.php"><i class="fas fa-arrow-up"></i> Salidas</a></li>
                <li><a href="productos.php"><i class="fas fa-boxes-stacked"></i> Productos</a></li>
                <li class="active"><a href="empresas.php"><i class="fas fa-building"></i> Empresas</a></li>
                <li><a href="empleados.php"><i class="fas fa-users"></i> Empleados</a></li>
                <li><a href="reportes.php"><i class="fas fa-file-invoice"></i> Reportes</a></li>
            </ul>
        </nav>

        <main class="content">
            <header class="top-bar">
                <h2>Gestión de Empresas</h2>
                
                <div class="user-info" id="userDropdown">
                    <div class="user-trigger" onclick="toggleUserMenu(event)">
                        <span><?php echo $_SESSION['nombre']; ?></span>
                        <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <hr style="margin:0; border:0; border-top:1px solid #eee;">
                        <a href="logout.php" style="color:red;"><i class="fas fa-sign-out-alt"></i> Salir</a>
                    </div>
                </div>
            </header>
            
            <div class="actions">
                <button class="btn btn-entry" id="btnNuevaEmpresa"><i class="fas fa-plus"></i> Nueva Empresa</button>
            </div>

            <section class="table-container">
                <h3>Directorio de Empresas</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEmpresas"></tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="modalEmpresa" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Empresa</h3>
                <span class="close-btn" onclick="closeModal('modalEmpresa')">&times;</span>
            </div>
            <form id="formEmpresa">
                <input type="hidden" name="id" id="empresa_id">
                <div class="form-group">
                    <label>Nombre de la Empresa</label>
                    <input type="text" class="form-control" name="nombre_empresa" id="nombre_empresa" required>
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" class="form-control" name="direccion" id="direccion" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" class="form-control" name="telefono" id="telefono" required>
                </div>
                <button type="submit" class="btn btn-entry" style="width: 100%; margin-top: 20px;">Guardar Empresa</button>
            </form>
        </div>
    </div>

    <div id="modalPassword" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Cambiar Contraseña</h3>
                <span class="close-btn" onclick="closeModal('modalPassword')">&times;</span>
            </div>
            <form id="formChangePassword">
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-entry" style="width:100%">Actualizar</button>
            </form>
        </div>
    </div>

    <div id="chatbot-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
        <button id="chatbot-launcher" style="background: var(--accent); color: white; border: none; padding: 15px; border-radius: 50%; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
            <i class="fas fa-robot" style="font-size: 24px;"></i>
        </button>
        <div id="chatbot-window" style="display: none; width: 300px; height: 400px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); position: absolute; bottom: 70px; right: 0; overflow: hidden;">
            <div style="background: var(--accent); color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between;">
                <span>Asistente ERA</span>
                <span onclick="toggleChat()" style="cursor:pointer;">&times;</span>
            </div>
            <div id="chatbot-messages" style="flex: 1; padding: 15px; overflow-y: auto; font-size: 0.9rem; background: #f8fafc; height: 280px;">
                <div style="background: #e2e8f0; padding: 8px; border-radius: 8px; margin-bottom: 10px; max-width: 85%;">¡Hola! Soy el bot de Almacén ERA. ¿En qué te ayudo con las empresas?</div>
            </div>
            <div style="padding: 10px; border-top: 1px solid #eee; display: flex; gap: 5px;">
                <input type="text" id="chatbot-input" placeholder="Pregunta algo..." style="flex: 1; border: 1px solid #ddd; padding: 8px; border-radius: 4px; outline: none;">
                <button id="chatbot-send" style="background: var(--accent); color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer;"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
const form = document.getElementById("formEmpresa");
        const modal = document.getElementById("modalEmpresa");
        const dropdownMenu = document.getElementById('dropdownMenu');

        // --- INTERFAZ USUARIO ---
        function toggleUserMenu(e) {
            e.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        }

        function openModal(id) {
            document.getElementById(id).style.display = "flex";
            dropdownMenu.style.display = 'none';
        }

        function closeModal(id) { document.getElementById(id).style.display = "none"; }

        window.onclick = (e) => {
            if (e.target.className === 'modal') e.target.style.display = "none";
            if (!document.getElementById('userDropdown').contains(e.target)) dropdownMenu.style.display = 'none';
        }

        // --- CRUD EMPRESAS ---
        async function cargarEmpresas() {
            const res = await fetch('acciones_empresa.php?accion=leer');
            const data = await res.json();
            document.getElementById("tablaEmpresas").innerHTML = data.map(emp => `
                <tr>
                    <td>${emp.id}</td>
                    <td>${emp.nombre_empresa}</td>
                    <td>${emp.direccion}</td>
                    <td>${emp.telefono}</td>
                    <td>
                        <button onclick='prepararEdicion(${JSON.stringify(emp)})' class="btn" style="background:#3b82f6; color:white; padding:5px;"><i class="fas fa-edit"></i></button>
                        <button onclick='eliminarEmpresa(${emp.id})' class="btn" style="background:#ef4444; color:white; padding:5px;"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }

        form.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('acciones_empresa.php?accion=guardar', { method: 'POST', body: new FormData(form) });
            const result = await res.json();
            if(result.status === "success") { closeModal('modalEmpresa'); cargarEmpresas(); }
        };

        // NUEVA FUNCIÓN: ELIMINAR EMPRESA
        async function eliminarEmpresa(id) {
            if(confirm("¿Estás seguro de que deseas eliminar esta empresa?")) {
                const formData = new FormData();
                formData.append('id', id);
                const res = await fetch('acciones_empresa.php?accion=eliminar', { 
                    method: 'POST', 
                    body: formData 
                });
                const result = await res.json();
                if(result.status === "success") {
                    cargarEmpresas();
                } else {
                    // Si hay un error, probablemente sea por la llave foránea (la empresa tiene productos o movimientos asociados)
                    alert("No se pudo eliminar: " + (result.message || "Asegúrate de que no haya productos ligados a esta empresa."));
                }
            }
        }

        function prepararEdicion(emp) {
            form.reset();
            document.getElementById("modalTitle").innerText = "Editar Empresa";
            document.getElementById("empresa_id").value = emp.id;
            document.getElementById("nombre_empresa").value = emp.nombre_empresa;
            document.getElementById("direccion").value = emp.direccion;
            document.getElementById("telefono").value = emp.telefono;
            modal.style.display = "flex";
        }

        document.getElementById("btnNuevaEmpresa").onclick = () => {
            form.reset();
            document.getElementById("modalTitle").innerText = "Registrar Nueva Empresa";
            document.getElementById("empresa_id").value = "";
            modal.style.display = "flex";
        };

        // --- LÓGICA DEL BOT ---
        const chatWindow = document.getElementById('chatbot-window');
        const chatInput = document.getElementById('chatbot-input');
        const chatMessages = document.getElementById('chatbot-messages');

        function toggleChat() { chatWindow.style.display = (chatWindow.style.display === 'none') ? 'flex' : 'none'; }
        document.getElementById('chatbot-launcher').onclick = toggleChat;

        function addBotMsg(text, isUser = false) {
            const div = document.createElement('div');
            div.style.cssText = `background:${isUser?'#3b82f6':'#e2e8f0'}; color:${isUser?'white':'#334155'}; padding:8px; border-radius:8px; margin-bottom:10px; max-width:85%; margin-left:${isUser?'auto':'0'}`;
            div.innerText = text;
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        document.getElementById('chatbot-send').onclick = () => {
            const val = chatInput.value.toLowerCase();
            if(!val) return;
            addBotMsg(chatInput.value, true);
            chatInput.value = '';
            
            let resp = "No entiendo. Prueba con: 'empresa', 'borrar' o 'editar'.";
            if(val.includes('hola')) resp = "¡Hola! Aquí puedes gestionar tus proveedores y clientes.";
            if(val.includes('empresa')) resp = "Usa el botón 'Nueva Empresa' para registrar un nuevo proveedor o cliente en el sistema.";
            if(val.includes('editar') || val.includes('borrar')) resp = "En la tabla, usa el botón azul para editar los datos o el rojo para eliminar una empresa.";
            
            setTimeout(() => addBotMsg(resp), 500);
        };

        cargarEmpresas();
    </script>
</body>
</html>