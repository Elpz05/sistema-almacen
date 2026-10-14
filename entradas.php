<?php 
// Protección de sesión y roles
include 'auth.php'; 
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Entradas | Almacén Era</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos Menú Usuario */
        .user-info { position: relative; cursor: pointer; }
        .user-trigger { display: flex; align-items: center; gap: 10px; }
        .dropdown-menu {
            display: none; position: absolute; top: 100%; right: 0;
            background: white; min-width: 180px; border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1); margin-top: 10px; z-index: 1100;
            border: 1px solid #e2e8f0; overflow: hidden;
        }
        .dropdown-menu a { display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: #334155; text-decoration: none; font-size: 0.9rem; }
        .dropdown-menu a:hover { background: #f1f5f9; }
        .badge.entry { background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-weight: bold; }

        /* Estilos Buscador */
        .search-container {
            margin-bottom: 15px; display: flex; align-items: center; background: white;
            padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); width: 350px;
        }
        .search-container i { color: #94a3b8; margin-right: 10px; }
        .search-input { border: none; outline: none; width: 100%; font-size: 0.95rem; color: #334155; }

        /* Bot */
        #chatbot-window { flex-direction: column; }
    </style>
</head>
<body>
    <div class="main-container">
        <nav class="sidebar">
            <div class="sidebar-header"><i class="fas fa-warehouse"></i> <span>Almacén Era</span></div>
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="active"><a href="entradas.php"><i class="fas fa-arrow-down"></i> Entradas</a></li>
                <li><a href="salidas.php"><i class="fas fa-arrow-up"></i> Salidas</a></li>
                <li><a href="productos.php"><i class="fas fa-boxes-stacked"></i> Productos</a></li>
                <?php if (esAdmin()): ?>
                    <li><a href="empresas.php"><i class="fas fa-building"></i> Empresas</a></li>
                    <li><a href="empleados.php"><i class="fas fa-users"></i> Empleados</a></li>
                    <li><a href="reportes.php"><i class="fas fa-file-invoice"></i> Reportes</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="content">
            <header class="top-bar">
                <h2>Gestión de Entradas</h2>
                <div class="user-info" id="userDropdown">
                    <div class="user-trigger" onclick="toggleUserMenu(event)">
                        <span><?php echo $_SESSION['nombre']; ?></span>
                        <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <hr style="margin:0; border:0; border-top:1px solid #eee;">
                        <a href="logout.php" style="color:#ef4444"><i class="fas fa-sign-out-alt"></i> Salir</a>
                    </div>
                </div>
            </header>

            <div class="actions">
                <button class="btn btn-entry" id="btnNuevaEntrada"><i class="fas fa-plus"></i> Registrar Entrada</button>
            </div>

            <section class="table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3>Historial de Entradas</h3>
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Buscar por producto...">
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>No. Importe</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Proveedor</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEntradas"></tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="modalEntrada" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-download blue"></i> Nueva Entrada</h3>
                <span class="close-btn" onclick="closeModal('modalEntrada')">&times;</span>
            </div>
            <form id="formEntrada">
                <div class="form-group">
                    <label>No. Importe / Factura</label>
                    <input type="text" name="num_importe" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Producto</label>
                    <select name="id_producto" id="selectProducto" class="form-control" required></select>
                </div>
                <div class="form-group">
                    <label>Proveedor</label>
                    <select name="id_empresa" id="selectEmpresa" class="form-control" required></select>
                </div>
                <div class="form-group">
                    <label>Cantidad</label>
                    <input type="number" name="cantidad" class="form-control" min="1" required>
                </div>
                <button type="submit" class="btn btn-entry" style="width:100%; margin-top:10px;">Guardar</button>
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
                <div style="background: #e2e8f0; padding: 8px; border-radius: 8px; margin-bottom: 10px; max-width: 85%;">¡Hola! Soy el bot. ¿Dudas sobre cómo registrar entradas?</div>
            </div>
            <div style="padding: 10px; border-top: 1px solid #eee; display: flex; gap: 5px;">
                <input type="text" id="chatbot-input" placeholder="Pregunta algo..." style="flex: 1; border: 1px solid #ddd; padding: 8px; border-radius: 4px; outline: none;">
                <button id="chatbot-send" style="background: var(--accent); color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer;"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById("modalEntrada");
        const dropdownMenu = document.getElementById('dropdownMenu');

        // --- INTERFAZ ---
        function toggleUserMenu(e) {
            e.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        }

        function closeModal(id) { document.getElementById(id || "modalEntrada").style.display = "none"; }

        window.onclick = (e) => {
            if (e.target.className === 'modal') e.target.style.display = "none";
            if (!document.getElementById('userDropdown').contains(e.target)) dropdownMenu.style.display = 'none';
        };

        // --- DATOS ---
        async function cargarPagina() {
            const resP = await fetch('acciones_producto.php?accion=leer');
            const prods = await resP.json();
            document.getElementById('selectProducto').innerHTML = prods.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');

            const resE = await fetch('acciones_empresa.php?accion=leer');
            const emps = await resE.json();
            document.getElementById('selectEmpresa').innerHTML = emps.map(e => `<option value="${e.id}">${e.nombre_empresa}</option>`).join('');

            const resH = await fetch('acciones_reportes.php?tipo=Entrada');
            const entradas = await resH.json();
            document.getElementById('tablaEntradas').innerHTML = entradas.map(e => `
                <tr>
                    <td>${e.fecha}</td>
                    <td>${e.num_importe}</td>
                    <td>${e.nombre_producto}</td>
                    <td><span class="badge entry">+ ${e.cantidad}</span></td>
                    <td>${e.nombre_empresa}</td>
                    <td>${e.nombre_empleado}</td>
                </tr>
            `).join('');
        }

        document.getElementById("btnNuevaEntrada").onclick = () => {
            document.getElementById('formEntrada').reset();
            modal.style.display = "flex";
        };

        document.getElementById('formEntrada').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('tipo_movimiento', 'Entrada');
            const res = await fetch('acciones_movimientos.php', { method: 'POST', body: formData });
            const result = await res.json();
            if (result.status === "success") { closeModal(); cargarPagina(); }
            else { alert("Error: " + result.message); }
        };

        // --- BUSCADOR ---
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tablaEntradas tr');
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
            });
        });

        // --- BOT ---
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
            let resp = "Prueba con: 'registrar', 'factura' o 'historial'.";
            if(val.includes('hola')) resp = "¡Hola! Estoy listo para ayudarte con las entradas.";
            if(val.includes('registrar')) resp = "Haz clic en el botón '+ Registrar Entrada' para añadir stock al inventario.";
            if(val.includes('factura') || val.includes('importe')) resp = "El 'No. Importe' se refiere al folio de la factura o nota de remisión del proveedor.";
            if(val.includes('historial')) resp = "Abajo puedes ver todas las entradas recientes. Usa el buscador para filtrar por nombre o folio.";
            setTimeout(() => addBotMsg(resp), 500);
        };

        cargarPagina();
    </script>
</body>
</html>