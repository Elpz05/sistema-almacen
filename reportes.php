<?php 
// Incluimos seguridad y verificación de Admin
include 'auth.php'; 
restringirAdmin(); 
include 'db.php';

// Consultas rápidas para estadísticas superiores
$total_movimientos = $conn->query("SELECT COUNT(*) as total FROM movimientos")->fetch_assoc()['total'];
$stock_critico = $conn->query("SELECT COUNT(*) as total FROM productos WHERE stock_actual < 5")->fetch_assoc()['total'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes | Almacén Era</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .filter-bar {
            background: white; padding: 20px; border-radius: 12px;
            margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); flex-wrap: wrap;
        }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 0.85rem; font-weight: bold; color: #64748b; }
        .filter-input { padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; min-width: 150px; }
        
        /* Menú Usuario */
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
        
        /* Badges de estado */
        .badge.entrada { background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .badge.salida { background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: bold; }

        /* Bot */
        #chatbot-window { flex-direction: column; }

        @media print {
            .sidebar, .filter-bar, .top-bar .user-info, #chatbot-container { display: none !important; }
            .main-container { display: block; }
            .content { margin-left: 0; padding: 0; }
            .table-container { box-shadow: none; border: 1px solid #eee; }
            body { background: white; }
        }
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
                <li><a href="empresas.php"><i class="fas fa-building"></i> Empresas</a></li>
                <li><a href="empleados.php"><i class="fas fa-users"></i> Empleados</a></li>
                <li class="active"><a href="reportes.php"><i class="fas fa-file-invoice"></i> Reportes</a></li>
            </ul>
        </nav>

        <main class="content">
            <header class="top-bar">
                <h2>Reportes de Inventario</h2>
                <div class="user-info" id="userDropdown">
                    <div class="user-trigger" onclick="toggleUserMenu(event)">
                        <span><?php echo $_SESSION['nombre']; ?></span>
                        <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="logout.php" style="color:#ef4444"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </div>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-exchange-alt blue"></i>
                    <div>
                        <h3><?php echo $total_movimientos; ?></h3>
                        <p>Total Movimientos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-exclamation-triangle orange"></i>
                    <div>
                        <h3><?php echo $stock_critico; ?></h3>
                        <p>Stock Crítico (< 5)</p>
                    </div>
                </div>
            </section>

            <div class="filter-bar">
                <div class="filter-group">
                    <label>Desde:</label>
                    <input type="date" id="fecha_desde" class="filter-input">
                </div>
                <div class="filter-group">
                    <label>Hasta:</label>
                    <input type="date" id="fecha_hasta" class="filter-input">
                </div>
                <div class="filter-group">
                    <label>Tipo:</label>
                    <select id="tipo_filtro" class="filter-input">
                        <option value="Todos">Todos</option>
                        <option value="Entrada">Entradas</option>
                        <option value="Salida">Salidas</option>
                    </select>
                </div>
                <button class="btn btn-entry" onclick="filtrarReporte()">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <button class="btn" style="background:#10b981; color:white" onclick="exportarExcel()">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button class="btn" style="background:#64748b; color:white" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>

            <section class="table-container">
                <h3 id="tituloReporte">Detalle de Actividad Reciente</h3>
                <table id="tablaParaExcel">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Estado</th>
                            <th>Cantidad</th>
                            <th>Empleado</th>
                            <th>Empresa</th>
                        </tr>
                    </thead>
                    <tbody id="tablaReporte"></tbody>
                </table>
            </section>
        </main>
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
                <div style="background: #e2e8f0; padding: 8px; border-radius: 8px; margin-bottom: 10px; max-width: 85%;">¡Hola! Soy el bot. ¿Necesitas ayuda con los reportes o impresión?</div>
            </div>
            <div style="padding: 10px; border-top: 1px solid #eee; display: flex; gap: 5px;">
                <input type="text" id="chatbot-input" placeholder="Pregunta algo..." style="flex: 1; border: 1px solid #ddd; padding: 8px; border-radius: 4px; outline: none;">
                <button id="chatbot-send" style="background: var(--accent); color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer;"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        function toggleUserMenu(e) {
            e.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        }

        // --- FILTROS ---
        async function filtrarReporte() {
            const desde = document.getElementById('fecha_desde').value;
            const hasta = document.getElementById('fecha_hasta').value;
            const tipo = document.getElementById('tipo_filtro').value;

            // Se envían los datos al servidor
            const res = await fetch(`acciones_reportes.php?desde=${desde}&hasta=${hasta}&tipo=${tipo}`);
            const datos = await res.json();
            
            const tbody = document.getElementById('tablaReporte');
            tbody.innerHTML = datos.map(mov => `
                <tr>
                    <td>${mov.fecha}</td>
                    <td>${mov.nombre_producto}</td>
                    <td><span class="badge ${mov.tipo_movimiento.toLowerCase()}">${mov.tipo_movimiento}</span></td>
                    <td>${mov.cantidad}</td>
                    <td>${mov.nombre_empleado}</td>
                    <td>${mov.nombre_empresa}</td>
                </tr>
            `).join('');
        }

        // --- EXPORTAR A EXCEL ---
        function exportarExcel() {
            // Tomamos el HTML de la tabla
            let tabla = document.getElementById("tablaParaExcel");
            let html = tabla.outerHTML;

            // Truco para que Excel respete los acentos (UTF-8)
            let blob = new Blob(['\ufeff', html], {
                type: 'application/vnd.ms-excel'
            });
            
            // Creamos un link oculto, le ponemos el archivo y forzamos el clic
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'Reporte_Inventario.xls';
            a.click();
        }

        window.onclick = () => {
            dropdownMenu.style.display = 'none';
        };
        
        // --- BOT ---
        const chatMessages = document.getElementById('chatbot-messages');
        const chatInput = document.getElementById('chatbot-input');
        function toggleChat() { 
            const win = document.getElementById('chatbot-window');
            win.style.display = (win.style.display === 'none') ? 'flex' : 'none'; 
        }
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
            
            let r = "No entiendo. Prueba con 'imprimir', 'excel', 'filtro' o 'crítico'.";
            if(val.includes('hola')) r = "¡Hola! Como administrador, aquí puedes auditar todos los movimientos.";
            if(val.includes('imprimir')) r = "Haz clic en el botón gris 'Imprimir'. La página se ajustará automáticamente.";
            if(val.includes('excel')) r = "Con el botón verde 'Excel', descargas la tabla que ves en pantalla directamente a tu computadora.";
            if(val.includes('filtro') || val.includes('buscar')) r = "Selecciona un rango de fechas y el tipo y presiona 'Filtrar' para actualizar la tabla.";
            if(val.includes('critico')) r = "La tarjeta naranja arriba te indica cuántos productos tienen menos de 5 unidades.";
            
            setTimeout(() => addBotMsg(r), 500);
        };

        // Carga inicial
        window.onload = filtrarReporte;
    </script>
</body>
</html>