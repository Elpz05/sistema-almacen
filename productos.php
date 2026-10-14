<?php 
// 1. Incluimos el guardián de sesión y seguridad
include 'auth.php'; 
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos | Almacén Era</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos Menú Usuario */
        .user-info { position: relative; cursor: pointer; }
        .user-trigger { display: flex; align-items: center; gap: 10px; }
        .dropdown-menu {
            display: none; position: absolute; top: 100%; right: 0;
            background: white; min-width: 180px; box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px; margin-top: 10px; z-index: 1100; border: 1px solid #e2e8f0; overflow: hidden;
        }
        .dropdown-menu a { display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: #334155; text-decoration: none; font-size: 0.9rem; }
        .dropdown-menu a:hover { background: #f1f5f9; }

        /* Buscador */
        .search-container {
            margin-bottom: 15px; display: flex; align-items: center; background: white;
            padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); width: 350px;
        }
        .search-container i { color: #94a3b8; margin-right: 10px; }
        .search-input { border: none; outline: none; width: 100%; font-size: 0.95rem; }

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
                <li><a href="entradas.php"><i class="fas fa-arrow-down"></i> Entradas</a></li>
                <li><a href="salidas.php"><i class="fas fa-arrow-up"></i> Salidas</a></li>
                <li class="active"><a href="productos.php"><i class="fas fa-boxes-stacked"></i> Productos</a></li>
                <?php if (esAdmin()): ?>
                    <li><a href="empresas.php"><i class="fas fa-building"></i> Empresas</a></li>
                    <li><a href="empleados.php"><i class="fas fa-users"></i> Empleados</a></li>
                    <li><a href="reportes.php"><i class="fas fa-file-invoice"></i> Reportes</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="content">
            <header class="top-bar">
                <h2>Gestión de Productos</h2>
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
                <button class="btn btn-entry" id="btnNuevoProducto"><i class="fas fa-plus"></i> Nuevo Producto</button>
            </div>

            <section class="table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3>Catálogo de Productos</h3>
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Buscar por nombre o código...">
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Código Barras</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Peso</th>
                            <th>Empresa</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaProductos"></tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="modalProducto" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="modalTitle">Producto</h3>
                <span class="close-btn" onclick="closeModal('modalProducto')">&times;</span>
            </div>
            <form id="formProducto">
                <input type="hidden" name="id" id="prod_id">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group"><label>Código de Barras</label><input type="text" name="codigo_barras" id="codigo_barras" class="form-control" required></div>
                    <div class="form-group"><label>Nombre</label><input type="text" name="nombre" id="nombre" class="form-control" required></div>
                    <div class="form-group"><label>Categoría</label><input type="text" name="tipo_producto" id="tipo_producto" class="form-control"></div>
                    <div class="form-group">
                        <label>Proveedor</label>
                        <select name="id_empresa" id="id_empresa" class="form-control" required></select>
                    </div>
                    <div class="form-group"><label>Peso (kg)</label><input type="number" step="0.01" name="peso" id="peso" class="form-control"></div>
                    <div class="form-group"><label>Precio ($)</label><input type="number" step="0.01" name="precio" id="precio" class="form-control"></div>
                </div>
                <button type="submit" class="btn btn-entry" style="width: 100%; margin-top: 15px;">Guardar Producto</button>
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
                <div style="background: #e2e8f0; padding: 8px; border-radius: 8px; margin-bottom: 10px; max-width: 85%;">¡Hola <strong><?php echo $_SESSION['nombre']; ?></strong>! Soy el bot de Almacén ERA. ¿En qué te ayudo hoy?</div>
            </div>
            <div style="padding: 10px; border-top: 1px solid #eee; display: flex; gap: 5px;">
                <input type="text" id="chatbot-input" placeholder="Pregunta algo..." style="flex: 1; border: 1px solid #ddd; padding: 8px; border-radius: 4px; outline: none;">
                <button id="chatbot-send" style="background: var(--accent); color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer;"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
 <script>
  const form = document.getElementById("formProducto");
        const modal = document.getElementById("modalProducto");
        const dropdownMenu = document.getElementById('dropdownMenu');
        const tablaBody = document.getElementById("tablaProductos");

        // --- INTERFAZ ---
        function toggleUserMenu(e) {
            e.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        }

        function closeModal(id) { document.getElementById(id || "modalProducto").style.display = "none"; }

        window.onclick = (e) => {
            if (e.target.className === 'modal') e.target.style.display = "none";
            if (!document.getElementById('userDropdown').contains(e.target)) dropdownMenu.style.display = 'none';
        }

        // --- BUSCADOR ---
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tablaProductos tr');
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
            });
        });

        // --- DATOS ---
        async function cargarEmpresasSelect() {
            const res = await fetch('acciones_empresa.php?accion=leer');
            const data = await res.json();
            document.getElementById("id_empresa").innerHTML = data.map(e => `<option value="${e.id}">${e.nombre_empresa}</option>`).join('');
        }

        async function cargarProductos() {
            const res = await fetch('acciones_producto.php?accion=leer');
            const data = await res.json();
            tablaBody.innerHTML = data.map(p => `
                <tr>
                    <td>${p.codigo_barras}</td>
                    <td>${p.nombre}</td>
                    <td>${p.tipo_producto}</td>
                    <td>${p.peso} kg</td>
                    <td>${p.nombre_empresa ? p.nombre_empresa : '<span style="color:gray">N/A</span>'}</td>
                    <td>$${p.precio}</td>
                    <td>
                        <button onclick='prepararEdicion(${JSON.stringify(p)})' class="btn" style="background:#3b82f6; color:white; padding:5px;"><i class="fas fa-edit"></i></button>
                        <button onclick='eliminarProducto(${p.id})' class="btn" style="background:#ef4444; color:white; padding:5px;"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }

        form.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('acciones_producto.php?accion=guardar', { method: 'POST', body: new FormData(form) });
            const result = await res.json();
            if(result.status === "success") { closeModal(); cargarProductos(); }
        };

        // NUEVA FUNCIÓN: ELIMINAR PRODUCTO
        async function eliminarProducto(id) {
            if(confirm("¿Estás seguro de que deseas eliminar este producto del catálogo?")) {
                const formData = new FormData();
                formData.append('id', id);
                const res = await fetch('acciones_producto.php?accion=eliminar', { 
                    method: 'POST', 
                    body: formData 
                });
                const result = await res.json();
                if(result.status === "success") {
                    cargarProductos();
                } else {
                    alert("Error al eliminar: " + result.message);
                }
            }
        }

        function prepararEdicion(p) {
            form.reset();
            cargarEmpresasSelect().then(() => {
                document.getElementById("modalTitle").innerText = "Editar Producto";
                document.getElementById("prod_id").value = p.id;
                document.getElementById("codigo_barras").value = p.codigo_barras;
                document.getElementById("nombre").value = p.nombre;
                document.getElementById("tipo_producto").value = p.tipo_producto;
                document.getElementById("id_empresa").value = p.id_empresa;
                document.getElementById("peso").value = p.peso;
                document.getElementById("precio").value = p.precio;
                modal.style.display = "flex";
            });
        }

        document.getElementById("btnNuevoProducto").onclick = () => {
            form.reset();
            document.getElementById("modalTitle").innerText = "Nuevo Producto";
            document.getElementById("prod_id").value = "";
            cargarEmpresasSelect();
            modal.style.display = "flex";
        };

        // --- BOT ---
        const chatMessages = document.getElementById('chatbot-messages');
        const chatInput = document.getElementById('chatbot-input');
        function toggleChat() { 
            const win = document.getElementById('chatbot-window');
            win.style.display = (win.style.display === 'none') ? 'flex' : 'none'; 
        }
        document.getElementById('chatbot-launcher').onclick = toggleChat;

        function addMsg(text, isUser = false) {
            const div = document.createElement('div');
            div.style.cssText = `background:${isUser?'#3b82f6':'#e2e8f0'}; color:${isUser?'white':'#334155'}; padding:8px; border-radius:8px; margin-bottom:10px; max-width:85%; margin-left:${isUser?'auto':'0'}`;
            div.innerText = text;
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        document.getElementById('chatbot-send').onclick = () => {
            const val = chatInput.value.toLowerCase();
            if(!val) return;
            addMsg(chatInput.value, true);
            chatInput.value = '';
            let r = "Prueba con 'crear', 'buscar', 'empresa' o 'eliminar'.";
            if(val.includes('hola')) r = "¡Hola! Gestiona aquí todo tu inventario.";
            if(val.includes('crear') || val.includes('nuevo')) r = "Presiona el botón '+ Nuevo Producto' y rellena los campos, incluyendo el código de barras.";
            if(val.includes('buscar')) r = "Escribe el nombre o el código en la barra blanca de arriba para filtrar la tabla.";
            if(val.includes('empresa') || val.includes('proveedor')) r = "Cada producto debe estar ligado a una empresa. Si no ves la empresa, primero regístrala en la sección 'Empresas'.";
            if(val.includes('eliminar') || val.includes('borrar')) r = "Usa el botón rojo con icono de bote de basura en la tabla para eliminar un producto del catálogo.";
            setTimeout(() => addMsg(r), 500);
        };

        cargarProductos();
        </script>
</body>
</html>